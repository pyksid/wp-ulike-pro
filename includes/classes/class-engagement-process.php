<?php
/**
 * Engagement vote processor.
 *
 * @package WP ULike Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WP_Ulike_Pro_Engagement_Process {

	protected $wpdb;
	protected $item_id;
	protected $item_type;
	protected $engagement_kind;
	protected $engagement_key;
	protected $value;
	protected $settings;
	protected $current_user;
	protected $current_ip;
	protected $current_fingerprint;
	protected $is_user_logged_in;
	protected $prev_state = array();
	protected $current_status = 'active';
	protected $row_id = 0;

	/**
	 * Per-request memo for get_user_state().
	 *
	 * Keyed by user|type|kind → item_id → state array (empty = never engaged).
	 *
	 * @var array<string,array<int,array>>
	 */
	private static $request_cache = array();

	/**
	 * Constructor.
	 *
	 * @param array $args Process arguments.
	 */
	public function __construct( $args ) {
		global $wpdb;

		$this->wpdb = $wpdb;

		$defaults = array(
			'item_id'         => 0,
			'item_type'       => 'post',
			'engagement_kind' => '',
			'engagement_key'  => '',
			'value'           => null,
			'user_ip'         => null,
		);

		$args = wp_parse_args( $args, $defaults );

		$this->item_id         = absint( $args['item_id'] );
		$this->item_type       = sanitize_key( $args['item_type'] );
		$this->engagement_kind = wp_ulike_pro_sanitize_engagement_kind( $args['engagement_kind'] );
		$this->engagement_key  = wp_ulike_pro_sanitize_engagement_key( $args['engagement_key'] );
		$this->value           = is_null( $args['value'] ) ? null : absint( $args['value'] );

		$this->settings            = wp_ulike_setting_type::get_instance( $this->item_type );
		$this->is_user_logged_in   = is_user_logged_in();
		$this->current_user        = $this->is_user_logged_in ? (string) get_current_user_id() : (string) wp_ulike_generate_user_id( wp_ulike_get_user_ip() );
		$this->current_ip          = $args['user_ip'] ? $args['user_ip'] : wp_ulike_get_user_ip();
		$this->current_fingerprint = wp_ulike_generate_fingerprint();
	}

	/**
	 * Run engagement update.
	 *
	 * @return bool
	 */
	public function update() {
		if ( ! $this->item_id || ! $this->engagement_kind ) {
			return false;
		}

		if ( ! $this->validate_payload() ) {
			return false;
		}

		$this->prev_state = self::get_user_state( $this->item_id, $this->item_type, $this->engagement_kind, $this->current_user );
		$this->resolve_status();

		if ( ! $this->has_permission() ) {
			return false;
		}

		if ( $this->allows_multi_vote() ) {
			$this->insert_row();
		} elseif ( ! empty( $this->prev_state['row_id'] ) ) {
			$this->update_row();
		} else {
			$this->insert_row();
		}

		if ( ! $this->row_id && 'active' === $this->current_status ) {
			return false;
		}

		$this->sync_counters();
		$this->update_user_state();

		do_action(
			'wp_ulike_after_engagement_process',
			array(
				'id'              => $this->row_id,
				'item_id'         => $this->item_id,
				'item_type'       => $this->item_type,
				'engagement_kind' => $this->engagement_kind,
				'engagement_key'  => $this->engagement_key,
				'value'           => $this->value,
				'status'          => $this->current_status,
				'user_id'         => $this->current_user,
				'prev_state'      => $this->prev_state,
			)
		);

		return true;
	}

	/**
	 * Validate incoming payload for kind.
	 *
	 * @return bool
	 */
	protected function validate_payload() {
		if ( 'emoji' === $this->engagement_kind ) {
			$reactions = WP_Ulike_Pro_Engagement_Registry::get_enabled_reactions( $this->item_type );

			return isset( $reactions[ $this->engagement_key ] );
		}

		if ( 'star' === $this->engagement_kind ) {
			$config = WP_Ulike_Pro_Engagement_Registry::get_star_config( $this->item_type );
			$this->engagement_key = $config['key'];

			return $this->value >= 1 && $this->value <= (int) $config['max'];
		}

		return (bool) apply_filters( 'wp_ulike_pro_engagement_validate_payload', false, $this->engagement_kind, $this->item_type, $this );
	}

	/**
	 * Resolve active/removed status (toggle & switch).
	 *
	 * @return void
	 */
	protected function resolve_status() {
		if ( $this->allows_multi_vote() ) {
			$this->current_status = 'active';
			return;
		}

		$prev_key    = isset( $this->prev_state['engagement_key'] ) ? $this->prev_state['engagement_key'] : '';
		$prev_value  = isset( $this->prev_state['value'] ) ? (int) $this->prev_state['value'] : 0;
		$prev_active = ! empty( $this->prev_state['status'] ) && 'active' === $this->prev_state['status'];

		if ( 'emoji' === $this->engagement_kind ) {
			if ( $prev_active && $prev_key === $this->engagement_key ) {
				$this->current_status = 'removed';
			} else {
				$this->current_status = 'active';
			}
		}

		if ( 'star' === $this->engagement_kind ) {
			if ( $prev_active && $prev_value === (int) $this->value ) {
				$this->current_status = 'removed';
				$this->value          = null;
			} else {
				$this->current_status = 'active';
			}
		}

		$this->current_status = wp_ulike_pro_sanitize_engagement_status(
			apply_filters(
				'wp_ulike_engagement_current_status',
				$this->current_status,
				$this->prev_state,
				array(
					'item_id'         => $this->item_id,
					'item_type'       => $this->item_type,
					'engagement_kind' => $this->engagement_kind,
					'engagement_key'  => $this->engagement_key,
					'value'           => $this->value,
				)
			)
		);
	}

	/**
	 * Permission check for engagement (emoji/star).
	 *
	 * Engagement state is kind-scoped (dedupe_token includes engagement_kind +
	 * engagement_key, and prev_state only reads rows of the current kind), so a
	 * prior classic like/dislike on the same item must NOT block emoji/star
	 * interaction. This was previously broken because the distinct path
	 * delegated to classic hasPermission() (which applies classic cookie state)
	 * and the append/cookie paths summed classic + engagement fingerprints.
	 *
	 * @return bool
	 */
	protected function has_permission() {
		$args = array(
			'item_id'              => $this->item_id,
			'type'                 => $this->item_type,
			'current_user'         => $this->current_user,
			'current_status'       => $this->current_status,
			'prev_status'          => ! empty( $this->prev_state['status'] ) ? $this->prev_state['status'] : false,
			'current_finger_print' => $this->current_fingerprint,
			'method'               => 'process',
		);

		if ( wp_ulike_is_bot_request() ) {
			return false;
		}

		// Distinct engagement (star always; emoji when logging is distinct):
		// one vote per user per (item, kind, key) is enforced by the kind-scoped
		// dedupe_token + prev_state toggle in update(). Do NOT delegate to the
		// classic hasPermission() — that applies classic like/dislike cookie
		// state, which would block emoji/star just because the user previously
		// liked/disliked the same item on the old template.
		if ( $this->uses_distinct_voting() ) {
			return (bool) apply_filters( 'wp_ulike_permission_status', true, $args, $this->settings );
		}

		$method = wp_ulike_setting_repo::getMethod( $this->item_type );

		// Append/multi-vote engagement (emoji with No Limit logging): enforce
		// the vote limit against ENGAGEMENT rows of the CURRENT kind only — a
		// prior classic like/dislike OR a prior star/emoji of a different kind
		// must not consume this engagement type's vote budget.
		if ( 'do_not_log' === $method ) {
			$engagement_count = wp_ulike_pro_count_engagement_fingerprint( $this->current_fingerprint, $this->item_id, $this->item_type, $this->engagement_kind );
			$vote_limit       = (int) wp_ulike_setting_repo::getVoteLimitNumber( $this->item_type );

			if ( $vote_limit > 0 && $engagement_count >= $vote_limit ) {
				return false;
			}

			return (bool) apply_filters( 'wp_ulike_permission_status', true, $args, $this->settings );
		}

		// Cookie-based guest engagement: block only when the user already has an
		// engagement row of the CURRENT kind for this item. A prior classic
		// like/dislike OR a prior emoji/star of a different kind must not block
		// this engagement type.
		if ( in_array( $method, array( 'by_cookie', 'by_user_ip_cookie' ), true ) ) {
			// Guests only. The fingerprint check runs regardless of whether a
			// classic like/dislike cookie exists -- a prior classic vote must not
			// exempt a guest from this engagement's own dedupe check. Logged-in
			// users are deliberately NOT gated here: cookie-based logging does not
			// limit them, and gating them would block toggling a reaction off or
			// switching to a different one (has_permission() runs before update()).
			if ( ! is_user_logged_in() ) {
				$engagement_count = wp_ulike_pro_count_engagement_fingerprint( $this->current_fingerprint, $this->item_id, $this->item_type, $this->engagement_kind );

				if ( $engagement_count >= 1 ) {
					return false;
				}
			}

			return (bool) apply_filters( 'wp_ulike_permission_status', true, $args, $this->settings );
		}

		return (bool) apply_filters( 'wp_ulike_permission_status', true, $args, $this->settings );
	}

	/**
	 * Star ratings always use one vote per user; emoji follows logging method.
	 *
	 * @return bool
	 */
	protected function uses_distinct_voting() {
		if ( 'star' === $this->engagement_kind ) {
			return true;
		}

		return wp_ulike_setting_repo::isDistinct( $this->item_type );
	}

	/**
	 * Whether emoji reactions allow stacked votes (no limit / cookie logging).
	 *
	 * @return bool
	 */
	protected function allows_multi_vote() {
		return 'emoji' === $this->engagement_kind && ! $this->uses_distinct_voting();
	}

	/**
	 * Insert engagement row via Free Pulse Writer (single write path).
	 *
	 * @return void
	 */
	protected function insert_row() {
		if ( ! class_exists( 'WP_Ulike_Pulse_Writer' ) ) {
			return;
		}

		$payload = $this->writer_payload( ! $this->allows_multi_vote() );
		$result  = $this->allows_multi_vote()
			? WP_Ulike_Pulse_Writer::insert( $payload )
			: WP_Ulike_Pulse_Writer::upsert( $payload );

		if ( false !== $result ) {
			$this->row_id = (int) $result;
			$this->clear_fingerprint_cache();
			$this->fire_recorded( 'inserted' );
		} elseif ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'WP ULike Pro: engagement insert via Pulse Writer failed.' );
		}
	}

	/**
	 * Update existing engagement row via Free Pulse Writer.
	 *
	 * @return void
	 */
	protected function update_row() {
		if ( ! class_exists( 'WP_Ulike_Pulse_Writer' ) ) {
			return;
		}

		if ( $this->allows_multi_vote() ) {
			return;
		}

		$payload = $this->writer_payload( true );
		$row_id  = ! empty( $this->prev_state['row_id'] ) ? (int) $this->prev_state['row_id'] : 0;

		// Prefer update-by-id so emoji key switches keep a single kind-scoped row.
		$result = $row_id
			? WP_Ulike_Pulse_Writer::update_by_id( $row_id, $payload )
			: WP_Ulike_Pulse_Writer::upsert( $payload );

		if ( false !== $result ) {
			$this->row_id = (int) $result;
			$this->clear_fingerprint_cache();
			$this->fire_recorded( 'updated' );
		} elseif ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'WP ULike Pro: engagement update via Pulse Writer failed.' );
		}
	}

	/**
	 * Fire the engagement hook for a recorded row.
	 *
	 * One hook for both paths: `$atts['event']` is `inserted` for a brand-new row
	 * and `updated` when an existing one changed (reaction switched, star
	 * re-rated). Listeners that must run only on a new row -- anything that
	 * increments site-wide statistics or stamps geo/device data -- have to check
	 * that key, otherwise they will fire again on every change.
	 *
	 * @param string $event inserted|updated
	 * @return void
	 */
	protected function fire_recorded( $event ) {
		$atts          = $this->get_action_atts();
		$atts['event'] = $event;

		do_action( 'wp_ulike_engagement_recorded', $atts );
	}

	/**
	 * Build Pulse Writer payload for emoji/star rows.
	 *
	 * @param bool $distinct Whether to set dedupe token.
	 * @return array<string,mixed>
	 */
	protected function writer_payload( $distinct ) {
		return array(
			'item_id'         => $this->item_id,
			'item_type'       => $this->item_type,
			'engagement_kind' => $this->engagement_kind,
			'engagement_key'  => $this->engagement_key,
			'value'           => $this->value,
			'status'          => $this->current_status,
			'ip'              => $this->maybe_anonymise_ip( $this->current_ip ),
			'user_id'         => $this->current_user,
			'fingerprint'     => $this->current_fingerprint,
			'is_distinct'     => (bool) $distinct,
		);
	}

	/**
	 * Sync counter meta after vote.
	 *
	 * @return void
	 */
	protected function sync_counters() {
		if ( 'emoji' === $this->engagement_kind ) {
			if ( $this->allows_multi_vote() ) {
				if ( 'active' === $this->current_status ) {
					WP_Ulike_Pro_Engagement_Counter::bump_reaction( $this->item_id, $this->item_type, $this->engagement_key, 1 );
				}
				return;
			}

			$prev_key   = isset( $this->prev_state['engagement_key'] ) ? $this->prev_state['engagement_key'] : '';
			$was_active = ! empty( $this->prev_state['status'] ) && 'active' === $this->prev_state['status'];

			if ( $was_active && $prev_key && ( $prev_key !== $this->engagement_key || 'removed' === $this->current_status ) ) {
				WP_Ulike_Pro_Engagement_Counter::bump_reaction( $this->item_id, $this->item_type, $prev_key, -1 );
			}

			if ( 'active' === $this->current_status && ( ! $was_active || $prev_key !== $this->engagement_key ) ) {
				WP_Ulike_Pro_Engagement_Counter::bump_reaction( $this->item_id, $this->item_type, $this->engagement_key, 1 );
			}
		}

		if ( 'star' === $this->engagement_kind ) {
			$prev_value = isset( $this->prev_state['value'] ) ? (int) $this->prev_state['value'] : 0;
			$was_active = ! empty( $this->prev_state['status'] ) && 'active' === $this->prev_state['status'];
			$old        = $was_active ? $prev_value : 0;
			$new        = 'active' === $this->current_status ? (int) $this->value : 0;

			WP_Ulike_Pro_Engagement_Counter::update_star_aggregates( $this->item_id, $this->item_type, $old, $new );
		}
	}

	/**
	 * Persist user engagement cache.
	 *
	 * @return void
	 */
	protected function update_user_state() {
		$meta_key = self::user_meta_key( $this->item_type );
		$cache    = wp_ulike_get_meta_data( $this->current_user, 'user', $meta_key, true );

		if ( ! is_array( $cache ) ) {
			$cache = array();
		}

		// Empty array = known negative (never / no longer engaged). Keep the key
		// so archive pages do not re-query Pulse for the same miss.
		if ( 'removed' === $this->current_status ) {
			$state = array();
		} else {
			$state = array(
				'engagement_key' => $this->engagement_key,
				'value'          => $this->value,
				'status'         => $this->current_status,
				'row_id'         => $this->row_id,
			);
		}

		$cache[ $this->item_id ][ $this->engagement_kind ] = $state;
		wp_ulike_update_meta_data( $this->current_user, 'user', $meta_key, $cache );

		$bucket = self::request_cache_bucket( $this->current_user, $this->item_type, $this->engagement_kind );
		self::$request_cache[ $bucket ][ $this->item_id ] = $state;
	}

	/**
	 * User meta key for engagement history.
	 *
	 * @param string $item_type Content type.
	 * @return string
	 */
	public static function user_meta_key( $item_type ) {
		return sanitize_key( $item_type . '_engagements' );
	}

	/**
	 * Drop per-request user-state memos.
	 *
	 * @return void
	 */
	public static function flush_request_cache() {
		self::$request_cache = array();
	}

	/**
	 * @param string $user_id         User identity.
	 * @param string $item_type       Content type.
	 * @param string $engagement_kind Engagement kind.
	 * @return string
	 */
	private static function request_cache_bucket( $user_id, $item_type, $engagement_kind ) {
		return (string) $user_id . '|' . sanitize_key( $item_type ) . '|' . sanitize_key( $engagement_kind );
	}

	/**
	 * Get user engagement state for item/kind.
	 *
	 * Path: request memo → user meta (incl. known negatives) → one Pulse lookup.
	 *
	 * @param int    $item_id         Item ID.
	 * @param string $item_type       Content type.
	 * @param string $engagement_kind Engagement kind.
	 * @param string $user_id         User identifier.
	 * @return array
	 */
	public static function get_user_state( $item_id, $item_type, $engagement_kind, $user_id ) {
		$item_id         = absint( $item_id );
		$item_type       = sanitize_key( $item_type );
		$engagement_kind = sanitize_key( $engagement_kind );
		$bucket          = self::request_cache_bucket( $user_id, $item_type, $engagement_kind );

		if ( isset( self::$request_cache[ $bucket ] ) && array_key_exists( $item_id, self::$request_cache[ $bucket ] ) ) {
			return apply_filters(
				'wp_ulike_engagement_user_state',
				self::$request_cache[ $bucket ][ $item_id ],
				$item_id,
				$item_type,
				$engagement_kind,
				$user_id
			);
		}

		$meta_key = self::user_meta_key( $item_type );
		$cache    = wp_ulike_get_meta_data( $user_id, 'user', $meta_key, true );
		if ( ! is_array( $cache ) ) {
			$cache = array();
		}

		// isset() is true for empty arrays — that is the negative-cache marker.
		if ( isset( $cache[ $item_id ][ $engagement_kind ] ) ) {
			$state = is_array( $cache[ $item_id ][ $engagement_kind ] )
				? $cache[ $item_id ][ $engagement_kind ]
				: array();
			self::$request_cache[ $bucket ][ $item_id ] = $state;

			return apply_filters( 'wp_ulike_engagement_user_state', $state, $item_id, $item_type, $engagement_kind, $user_id );
		}

		$state = self::get_user_state_from_db( $item_id, $item_type, $engagement_kind, $user_id );
		self::$request_cache[ $bucket ][ $item_id ] = $state;

		// Persist hits always. Persist misses for logged-in users so later
		// views skip Pulse; guests stay request-memo only.
		$is_logged_in = is_numeric( $user_id ) && (int) $user_id > 0;
		if ( ! empty( $state ) || $is_logged_in ) {
			$cache[ $item_id ][ $engagement_kind ] = $state;
			wp_ulike_update_meta_data( $user_id, 'user', $meta_key, $cache );
		}

		return apply_filters( 'wp_ulike_engagement_user_state', $state, $item_id, $item_type, $engagement_kind, $user_id );
	}

	/**
	 * Load latest user engagement from DB.
	 *
	 * @param int    $item_id         Item ID.
	 * @param string $item_type       Content type.
	 * @param string $engagement_kind Engagement kind.
	 * @param string $user_id         User identifier.
	 * @return array
	 */
	public static function get_user_state_from_db( $item_id, $item_type, $engagement_kind, $user_id ) {
		global $wpdb;

		$table = wp_ulike_pro_engagement_table();
		if ( empty( $table ) ) {
			return array();
		}

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT id, engagement_key, value, status FROM `{$table}`
				WHERE item_id = %d AND item_type = %s AND engagement_kind = %s AND user_id = %s
				ORDER BY id DESC LIMIT 1",
				absint( $item_id ),
				sanitize_key( $item_type ),
				sanitize_key( $engagement_kind ),
				(string) $user_id
			),
			ARRAY_A
		);

		if ( empty( $row ) ) {
			return array();
		}

		return array(
			'row_id'         => (int) $row['id'],
			'engagement_key' => (string) $row['engagement_key'],
			'value'          => is_null( $row['value'] ) ? null : (int) $row['value'],
			'status'         => wp_ulike_pro_sanitize_engagement_status( $row['status'] ),
		);
	}

	/**
	 * Status code for AJAX (mirrors classic codes).
	 *
	 * @return int
	 */
	public function get_status_code() {
		if ( 'removed' === $this->current_status ) {
			return 2;
		}

		if ( $this->allows_multi_vote() ) {
			return 4;
		}

		return 3;
	}

	/**
	 * Response counter payload.
	 *
	 * @return array
	 */
	public function get_counter_payload() {
		if ( 'emoji' === $this->engagement_kind ) {
			$counts           = WP_Ulike_Pro_Engagement_Counter::get_all_reaction_counts( $this->item_id, $this->item_type );
			$total            = WP_Ulike_Pro_Engagement_Counter::get_total_reactions( $this->item_id, $this->item_type, $counts );
			$formatted_counts = array();

			foreach ( $counts as $slug => $count ) {
				$formatted_counts[ $slug ] = wp_ulike_pro_format_engagement_count( $count );
			}

			return array(
				'kind'             => 'emoji',
				'counts'           => $counts,
				'formatted_counts' => $formatted_counts,
				'total'            => $total,
				'formatted_total'  => wp_ulike_pro_format_engagement_count( $total ),
				'active'           => 'active' === $this->current_status ? $this->engagement_key : '',
			);
		}

		if ( 'star' === $this->engagement_kind ) {
			$agg = WP_Ulike_Pro_Engagement_Counter::get_star_aggregates( $this->item_id, $this->item_type );

			return array(
				'kind'              => 'star',
				'average'           => $agg['average'],
				'formatted_average' => number_format_i18n( $agg['average'], 1 ),
				'count'             => $agg['count'],
				'formatted_count'   => wp_ulike_pro_format_engagement_count( $agg['count'] ),
				'user'              => 'active' === $this->current_status ? (int) $this->value : 0,
			);
		}

		return array();
	}

	/**
	 * Action hook attributes.
	 *
	 * @return array
	 */
	protected function get_action_atts() {
		return array(
			'id'              => $this->row_id,
			'item_id'         => $this->item_id,
			'item_type'       => $this->item_type,
			'engagement_kind' => $this->engagement_kind,
			'engagement_key'  => $this->engagement_key,
			'value'           => $this->value,
			'status'          => $this->current_status,
			'user_id'         => $this->current_user,
			'ip'              => $this->current_ip,
			'table'           => wp_ulike_pro_engagement_table(),
		);
	}

	/**
	 * Anonymise IP when configured.
	 *
	 * @param string $ip IP address.
	 * @return string
	 */
	protected function maybe_anonymise_ip( $ip ) {
		if ( wp_ulike_setting_repo::isAnonymiseIpOn() ) {
			if ( wp_ulike_setting_repo::isIpLoggingOff() ) {
				return '0.0.0.0';
			}

			if ( strpos( $ip, '.' ) !== false ) {
				return preg_replace( '~[0-9]+$~', '0', $ip );
			}

			return preg_replace( '~[0-9]*:[0-9]+$~', '0000:0000', $ip );
		}

		return esc_sql( $ip );
	}

	/**
	 * @return string
	 */
	public function get_current_status() {
		return $this->current_status;
	}

	/**
	 * @return string
	 */
	public function get_engagement_key() {
		return $this->engagement_key;
	}

	/**
	 * @return int|null
	 */
	public function get_value() {
		return $this->value;
	}

	/**
	 * Whether emoji reactions allow stacked votes.
	 *
	 * @return bool
	 */
	public function allows_multi_vote_public() {
		return $this->allows_multi_vote();
	}

	/**
	 * Clear cached fingerprint counts after a new vote row.
	 *
	 * @return void
	 */
	protected function clear_fingerprint_cache() {
		$base = $this->item_type . '_' . $this->item_id . '_' . $this->current_fingerprint;
		// Invalidate the kind-scoped cache entry (used by has_permission()).
		wp_cache_delete( 'engagement_fingerprint_' . md5( $base . '_' . $this->engagement_kind ), WP_ULIKE_SLUG );
		// Invalidate the kind-agnostic entry (used by callers that omit $kind).
		wp_cache_delete( 'engagement_fingerprint_' . md5( $base . '_' ), WP_ULIKE_SLUG );
	}
}

