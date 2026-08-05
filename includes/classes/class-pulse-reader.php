<?php
/**
 * Pro Pulse read helpers.
 *
 * Vote reads delegate to the free plugin's WP_Ulike_Pulse_Query, the canonical
 * mode-aware router (legacy / dual / pulse). Pro requires WP ULike 5.2+, which
 * always provides that router, so there is no pulse-only fallback here — one
 * source of truth, one read path. Pro only owns the reads that have no
 * free-plugin counterpart: the legacy-shaped log row mapping and the chart
 * dataset used by the legacy stats widget.
 *
 * @package WP_Ulike_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_Ulike_Pro_Pulse_Reader' ) ) {

	/**
	 * Centralized Pulse reads for maintenance, REST, and legacy stats widget.
	 */
	final class WP_Ulike_Pro_Pulse_Reader {

		/**
		 * Whether the free plugin's mode-aware query router is available.
		 *
		 * Guards every delegated call below so an unexpected absence (Free
		 * deactivated mid-request, or a future Free release renaming this
		 * internal class) degrades to a safe default instead of a fatal error.
		 *
		 * @return bool
		 */
		private static function pulse_query_available() {
			return class_exists( 'WP_Ulike_Pulse_Query' );
		}

		/**
		 * Count vote rows for a legacy table suffix (mode-aware).
		 *
		 * @param string $table_suffix ulike|ulike_comments|…
		 * @param mixed  $period       Period filter.
		 * @return int
		 */
		public static function count_logs_for_table_suffix( $table_suffix, $period = 'all' ) {
			if ( ! self::pulse_query_available() ) {
				return 0;
			}

			return (int) WP_Ulike_Pulse_Query::count_logs_for_table( $table_suffix, $period );
		}

		/**
		 * Count vote rows for one legacy status by table suffix (mode-aware).
		 *
		 * @param string $table_suffix    ulike|ulike_comments|…
		 * @param string $legacy_status   like|dislike|unlike|undislike.
		 * @param mixed  $period          Period filter.
		 * @return int
		 */
		public static function count_status_for_table_suffix( $table_suffix, $legacy_status, $period = 'all' ) {
			if ( ! self::pulse_query_available() ) {
				return 0;
			}

			return (int) WP_Ulike_Pulse_Query::count_status_for_table( $table_suffix, $legacy_status, $period );
		}

		/**
		 * Daily chart rows for a legacy table suffix (legacy admin shape).
		 *
		 * No mode-aware counterpart in the free router — used by the legacy stats
		 * widget where the recent 30-day window is dominated by pulse writes.
		 *
		 * @param string $table_suffix ulike|ulike_comments|…
		 * @param int    $data_limit   Number of days.
		 * @return array<int,object>
		 */
		public static function get_chart_dataset( $table_suffix, $data_limit = 30 ) {
			// Delegate to the free plugin's mode-aware bridge so dual/legacy sites
			// include pre-cutover daily activity. Returns [{labels, counts}] rows.
			if ( class_exists( 'WP_Ulike_Pulse_Log_Bridge' ) && method_exists( 'WP_Ulike_Pulse_Log_Bridge', 'get_chart_dataset' ) ) {
				return WP_Ulike_Pulse_Log_Bridge::get_chart_dataset( $table_suffix, $data_limit );
			}

			global $wpdb;

			$item_type = WP_Ulike_Pulse_Registry::type_by_table_suffix( $table_suffix );
			if ( ! $item_type ) {
				return array();
			}

			$data_limit = max( 1, absint( $data_limit ) );
			$table      = esc_sql( wp_ulike_pro_pulse_table() );
			$latest     = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT MAX(date_time) FROM `{$table}`
					WHERE item_type = %s AND engagement_kind = %s AND status = 'active'",
					WP_Ulike_Pulse_Registry::normalize_item_type( $item_type ),
					WP_Ulike_Pulse_Registry::KIND_VOTE
				)
			);

			if ( ! $latest ) {
				return array();
			}

			$start = gmdate( 'Y-m-d H:i:s', strtotime( $latest ) - ( $data_limit * DAY_IN_SECONDS ) );
			$rows  = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT DATE(date_time) AS labels, COUNT(date_time) AS counts
					FROM `{$table}`
					WHERE item_type = %s AND engagement_kind = %s AND status = 'active'
					AND date_time >= %s AND date_time <= %s
					GROUP BY labels ORDER BY labels ASC",
					WP_Ulike_Pulse_Registry::normalize_item_type( $item_type ),
					WP_Ulike_Pulse_Registry::KIND_VOTE,
					$start,
					$latest
				)
			);

			$output = array();
			foreach ( (array) $rows as $row ) {
				$output[] = (object) array(
					'labels' => $row->labels,
					'counts' => (int) $row->counts,
				);
			}

			return $output;
		}

		/**
		 * Single vote row by ID, shaped like a legacy log row (mode-aware).
		 *
		 * Delegates to the free plugin's WP_Ulike_Pulse_Log_Bridge so legacy,
		 * merged, and pulse read modes all resolve correctly and any identifier
		 * form (legacy suffix or item type) is accepted.
		 *
		 * @param string $table_suffix ulike|ulike_comments|… or item type.
		 * @param int    $row_id       Vote row ID.
		 * @return object|null
		 */
		public static function get_log_row( $table_suffix, $row_id ) {
			$row_id = absint( $row_id );
			if ( ! $row_id ) {
				return null;
			}

			if ( class_exists( 'WP_Ulike_Pulse_Log_Bridge' ) && method_exists( 'WP_Ulike_Pulse_Log_Bridge', 'get_log_row' ) ) {
				return WP_Ulike_Pulse_Log_Bridge::get_log_row( $table_suffix, $row_id );
			}

			global $wpdb;

			$item_type = WP_Ulike_Pulse_Registry::resolve_log_identifier( $table_suffix );
			$source    = $item_type ? WP_Ulike_Pulse_Registry::legacy_source_for_type( $item_type ) : null;

			if ( ! $source ) {
				return null;
			}

			$table = esc_sql( wp_ulike_pro_pulse_table() );
			$row   = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM `{$table}` WHERE id = %d AND item_type = %s AND engagement_kind = %s",
					$row_id,
					$source['item_type'],
					WP_Ulike_Pulse_Registry::KIND_VOTE
				)
			);

			if ( ! $row ) {
				return null;
			}

			return self::map_pulse_row_to_legacy( $row, $source );
		}

		/**
		 * Count votes for one item (mode-aware).
		 *
		 * @param int    $item_id     Item ID.
		 * @param string $type        Setting type slug.
		 * @param string $status      like|dislike|all|unlike|undislike.
		 * @param bool   $is_distinct Distinct users.
		 * @param mixed  $date_range  Period filter.
		 * @return int
		 */
		public static function count_item_votes( $item_id, $type, $status = 'like', $is_distinct = true, $date_range = null ) {
			if ( ! self::pulse_query_available() ) {
				return 0;
			}

			return (int) WP_Ulike_Pulse_Query::count_item_votes( $item_id, $type, $status, $is_distinct, $date_range );
		}

		/**
		 * Latest vote activity for a user on one item (mode-aware), formatted
		 * with Pro's richer labels and geo/device fields.
		 *
		 * @param int    $item_id Item ID.
		 * @param int    $user_id User ID.
		 * @param string $type    Setting type slug.
		 * @return array<string,mixed>|null
		 */
		public static function get_user_latest_activity( $item_id, $user_id, $type ) {
			if ( ! self::pulse_query_available() ) {
				return null;
			}

			$row = WP_Ulike_Pulse_Query::get_user_latest_activity( $item_id, $user_id, $type );

			if ( ! $row ) {
				return null;
			}

			$raw_status = isset( $row->status ) ? sanitize_key( (string) $row->status ) : '';

			// Pulse rows: engagement_key (like|dislike) + status (active|removed).
			// Legacy rows: status holds like|unlike|dislike|undislike directly.
			// Guard against older Free builds that aliased engagement_key AS status
			// (turning active likes into "unlike" via row_to_legacy).
			if ( isset( $row->engagement_key ) ) {
				$engagement_key = sanitize_key( (string) $row->engagement_key );
				$row_status     = in_array( $raw_status, array( 'active', 'removed' ), true )
					? $raw_status
					: 'active';
				$legacy_status  = WP_Ulike_Pulse_Vote_Map::row_to_legacy( $engagement_key, $row_status );
			} else {
				$legacy_status = $raw_status ? $raw_status : WP_Ulike_Pulse_Vote_Map::KEY_LIKE;
			}

			$labels = array(
				'like'      => esc_html__( 'Like (Up Vote)', WP_ULIKE_PRO_DOMAIN ),
				'unlike'    => esc_html__( 'Un-Like (Removed Up Vote)', WP_ULIKE_PRO_DOMAIN ),
				'dislike'   => esc_html__( 'Dislike (Down Vote)', WP_ULIKE_PRO_DOMAIN ),
				'undislike' => esc_html__( 'Un-Dislike (Removed Down Vote)', WP_ULIKE_PRO_DOMAIN ),
			);

			return array(
				'date_time'        => wp_ulike_date_i18n( $row->date_time ),
				'status'           => isset( $labels[ $legacy_status ] ) ? $labels[ $legacy_status ] : $legacy_status,
				// Machine key for stats chips (status is a localized label).
				'status_key'       => $legacy_status,
				'engagement_kind'  => 'vote',
				'country_code'     => isset( $row->country_code ) ? (string) $row->country_code : '',
				'device'           => isset( $row->device ) ? (string) $row->device : '',
			);
		}

		/**
		 * Rebuild likers list for one item (mode-aware).
		 *
		 * @param int    $item_id Item ID.
		 * @param string $type    Setting type slug.
		 * @param int    $limit   Max rows.
		 * @return array<int,int>
		 */
		public static function rebuild_likers_list( $item_id, $type, $limit = 10 ) {
			if ( ! self::pulse_query_available() ) {
				return array();
			}

			$info = wp_ulike_get_table_info( $type );
			if ( empty( $info['table'] ) || empty( $info['column'] ) ) {
				return array();
			}

			global $wpdb;

			return array_values(
				array_filter(
					array_map(
						'absint',
						(array) WP_Ulike_Pulse_Query::rebuild_likers_list(
							$wpdb->prefix . $info['table'],
							$info['column'],
							$item_id,
							$limit
						)
					)
				)
			);
		}

		/**
		 * Distinct item IDs with vote rows for a content type (mode-aware).
		 *
		 * @param string $item_type post|comment|activity|topic.
		 * @return int[]
		 */
		public static function distinct_voted_item_ids( $item_type ) {
			if ( ! self::pulse_query_available() ) {
				return array();
			}

			return WP_Ulike_Pulse_Query::distinct_voted_item_ids( $item_type );
		}

		/**
		 * Map a Pulse vote row to the legacy log object shape.
		 *
		 * @param object              $row    Pulse row.
		 * @param array<string,mixed> $source Legacy source config.
		 * @return object
		 */
		private static function map_pulse_row_to_legacy( $row, $source ) {
			$legacy              = new stdClass();
			$legacy->id          = (int) $row->id;
			$legacy->date_time   = $row->date_time;
			$legacy->user_id     = $row->user_id;
			$legacy->ip          = isset( $row->ip ) ? $row->ip : '';
			$legacy->fingerprint = isset( $row->fingerprint ) ? $row->fingerprint : '';
			$legacy->status      = WP_Ulike_Pulse_Vote_Map::row_to_legacy(
				$row->engagement_key,
				$row->status
			);

			$column            = $source['column'];
			$legacy->{$column} = (int) $row->item_id;

			return $legacy;
		}
	}
}

