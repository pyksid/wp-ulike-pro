<?php
/**
 * Legacy REST stats datasets (/stats) — Pulse-native, admin-panel independent.
 *
 * @package WP_Ulike_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'WP_Ulike_Pro_Stats_Rest' ) ) {

	/**
	 * Serves the public REST /stats endpoint (dataset_ulike, count_logs_*, get_top_*).
	 */
	final class WP_Ulike_Pro_Stats_Rest {

		/** @var self|null */
		private static $instance = null;

		/** @var wpdb */
		private $wpdb;

		/** @var array<string,string> */
		private $date_range = array();

		/** @var array */
		private $selected_status = array();

		/**
		 * Constructor.
		 */
		private function __construct() {
			global $wpdb;
			$this->wpdb = $wpdb;
		}

		/**
		 * @return self
		 */
		public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Resolve a legacy REST dataset key to response data.
		 *
		 * @param array  $date_range       Optional start/end dates.
		 * @param array  $selected_status  Optional status filter.
		 * @param string $dataset          Dataset key from REST params.
		 * @param string $filter           Post type filter for get_top_posts.
		 * @param bool   $is_api           When true, dataset_* returns flat numeric series.
		 * @return mixed
		 */
		public function get_data( $date_range = array(), $selected_status = array(), $dataset = '', $filter = '', $is_api = false ) {
			$this->set_date_range( $date_range );
			$this->selected_status = is_array( $selected_status ) ? $selected_status : array();

			$has_daterange = ! empty( $this->date_range );
			$dataset       = sanitize_text_field( (string) $dataset );

			if ( false !== strpos( $dataset, 'dataset_' ) ) {
				$table = str_replace( 'dataset_', '', $dataset );
				if ( ! $has_daterange && empty( $this->selected_status ) ) {
					wp_ulike_delete_transient( 'wp_ulike_pro_daterange_of_' . $table );
				}

				return $this->get_dataset( $table, $has_daterange, $is_api );
			}

			if ( false !== strpos( $dataset, 'get_top_' ) ) {
				$type = str_replace( 'get_top_', '', $dataset );
				if ( ! $has_daterange && empty( $this->selected_status ) ) {
					wp_ulike_delete_transient( 'wp_ulike_pro_daterange_of_top_' . $type );
				}

				return $this->get_top( $type, $has_daterange, $filter );
			}

			if ( false !== strpos( $dataset, 'count_logs_' ) ) {
				if ( preg_match( '/count_logs_(\w+)_(week|month|year|all)/', $dataset, $matches ) ) {
					return $this->count_logs( $matches[1], $matches[2] );
				}

				return '';
			}

			if ( false !== strpos( $dataset, 'count_all_' ) ) {
				switch ( $dataset ) {
					case 'count_all_logs_all':
						return wp_ulike_count_all_logs( 'all' );
					case 'count_all_logs_today':
						return wp_ulike_count_all_logs( 'today' );
					case 'count_all_logs_yesterday':
						return wp_ulike_count_all_logs( 'yesterday' );
				}
			}

			return 0;
		}

		/**
		 * @param string $table_suffix Legacy table suffix.
		 * @param bool   $has_daterange Whether a custom range was supplied.
		 * @param bool   $is_api        Flat API output.
		 * @return array
		 */
		private function get_dataset( $table_suffix, $has_daterange, $is_api = false ) {
			$table_suffix = $this->sanitize_table_suffix( $table_suffix );

			if ( ! $has_daterange ) {
				$last_range = wp_ulike_get_transient( 'wp_ulike_pro_daterange_of_' . $table_suffix );
				$first_date = $this->get_first_log_date( $table_suffix );
				$this->set_date_range(
					$last_range ? $last_range : array(
						'start' => empty( $first_date ) ? gmdate( 'Y-m-d', strtotime( '-1 month' ) ) : $first_date,
						'end'   => current_time( 'Y-m-d' ),
					)
				);
			} else {
				wp_ulike_set_transient( 'wp_ulike_pro_daterange_of_' . $table_suffix, $this->date_range, YEAR_IN_SECONDS );
			}

			$labels  = $this->get_date_labels();
			$results = $this->query_linear_counts( $table_suffix );
			$output  = array( 'label' => $labels );

			if ( $is_api ) {
				$output['data'] = array();
				foreach ( $results as $info ) {
					foreach ( $labels as $date ) {
						$output['data'][] = isset( $info['rawInfo'][ $date ] ) ? (int) $info['rawInfo'][ $date ] : 0;
					}
				}
				return $output;
			}

			$output['datasets'] = array();
			foreach ( $results as $info ) {
				foreach ( $labels as $date ) {
					$info['datasets']['data'][] = isset( $info['rawInfo'][ $date ] ) ? (int) $info['rawInfo'][ $date ] : 0;
				}
				$output['datasets'][] = $info['datasets'];
			}

			$output['options'] = array(
				'title' => array(
					'display' => true,
					'text'    => sprintf(
						'%s %s %s %s',
						esc_html__( 'Growth ratings chart from', WP_ULIKE_PRO_DOMAIN ),
						$this->date_range['start'],
						esc_html__( 'To', WP_ULIKE_PRO_DOMAIN ),
						$this->date_range['end']
					),
				),
			);

			return $output;
		}

		/**
		 * @param string $table_suffix Legacy suffix.
		 * @param string $period       week|month|year|all.
		 * @return int|string
		 */
	private function count_logs( $table_suffix, $period ) {
		$table_suffix = $this->sanitize_table_suffix( $table_suffix );
		$type_key     = WP_Ulike_Pro_Stats_Type_Resolver::table_to_stats_type( $table_suffix );

		// Always use the unified content-type count (vote + emoji + star) so the
		// REST count matches the admin overview regardless of the type's current
		// template mode (display automation, historical data, template switches).
		if ( $type_key ) {
			$count = WP_Ulike_Pro_Stats_V2::get_instance()->count_for_content_type( $type_key, $period );
			return number_format_i18n( $count );
		}

		$cache_key = sanitize_key( sprintf( 'count_logs_for_%s_table_in_%s_daterange', $table_suffix, $period ) );

		if ( 'all' === $period ) {
			$cached = wp_ulike_get_meta_data( 1, 'statistics', $cache_key, true );
			if ( ! empty( $cached ) || is_numeric( $cached ) ) {
				return number_format_i18n( $cached );
			}
		}

		$count = wp_cache_get( $cache_key, WP_ULIKE_PRO_DOMAIN );
		if ( false === $count ) {
			$count = WP_Ulike_Pro_Pulse_Reader::count_logs_for_table_suffix( $table_suffix, $period );
			wp_cache_set( $cache_key, $count, WP_ULIKE_PRO_DOMAIN );
			}

			if ( 'all' === $period ) {
				wp_ulike_update_meta_data( 1, 'statistics', $cache_key, $count );
			}

			return number_format_i18n( (int) $count );
		}

		/**
		 * @param string $type          posts|comments|activities|topics|likers.
		 * @param bool   $has_daterange Whether range was supplied.
		 * @param string $filter        Post type filter.
		 * @return string
		 */
		private function get_top( $type, $has_daterange, $filter = '' ) {
			if ( ! $has_daterange ) {
				$last_range = wp_ulike_get_transient( 'wp_ulike_pro_daterange_of_top_' . $type );
				$this->set_date_range( $last_range ? $last_range : 'all' );
			} else {
				wp_ulike_set_transient( 'wp_ulike_pro_daterange_of_top_' . $type, $this->date_range, YEAR_IN_SECONDS );
			}

			$period = empty( $this->date_range ) ? 'all' : $this->date_range;

			switch ( $type ) {
				case 'posts':
					return $this->render_top_posts( $filter, $period );
				case 'comments':
					return $this->render_top_comments( $period );
				case 'activities':
					return $this->render_top_activities( $period );
				case 'topics':
					return $this->render_top_topics( $period );
				case 'likers':
					return $this->render_top_likers( $period );
			}

			return '';
		}

		/**
		 * @param string       $table_suffix Legacy suffix.
		 * @return array<int,array<string,mixed>>
		 */
		private function query_linear_counts( $table_suffix ) {
			$item_type = WP_Ulike_Pulse_Registry::resolve_log_identifier( $table_suffix );
			if ( ! $item_type ) {
				return array();
			}

			$range = $this->get_mysql_date_range_sql();

			if ( empty( $this->selected_status ) ) {
				$rows = $this->rest_merged_daily_rows( $item_type, $range, '' );

				return array(
					array(
						'datasets' => $this->chart_series_options( 'all' ),
						'rawInfo'  => $this->map_count_rows( $rows ),
					),
				);
			}

			$output = array();
			foreach ( $this->selected_status as $status ) {
				$status = $this->sanitize_legacy_status( $status );
				$rows   = $this->rest_merged_daily_rows( $item_type, $range, $status );

				$output[] = array(
					'datasets' => $this->chart_series_options( $status ),
					'rawInfo'  => $this->map_count_rows( $rows ),
				);
			}

			return $output;
		}

		/**
		 * Mode-aware daily counts for one type (pulse + legacy merge) for REST charts.
		 *
		 * @param string $item_type     Canonical item type.
		 * @param string $range         SQL date fragment (date_time-bound).
		 * @param string $legacy_status Empty for all active, or like|dislike|unlike|undislike.
		 * @return array<int,array<string,mixed>> Rows with {labels, counts}.
		 */
	private function rest_merged_daily_rows( $item_type, $range, $legacy_status ) {
		$pulse = esc_sql( wp_ulike_pro_pulse_table() );
		$kind  = esc_sql( WP_Ulike_Pulse_Registry::KIND_VOTE );
		$mode  = $this->rest_read_mode();

		// Vote-scoped dual_since: emoji/star have no legacy counterpart so they
		// are never since-filtered; only vote rows are scoped to avoid
		// double-counting with legacy votes in merged mode.
		$since = '';
		if ( 'merged' === $mode && class_exists( 'WP_Ulike_Pulse_Config' ) && WP_Ulike_Pulse_Config::dual_since() ) {
			$since = $this->wpdb->prepare(
				" AND ( engagement_kind IN ('emoji','star') OR ( engagement_kind = %s AND date_time >= %s ) )",
				WP_Ulike_Pulse_Registry::KIND_VOTE,
				WP_Ulike_Pulse_Config::dual_since()
			);
		}

		$union = array();

		if ( 'legacy' !== $mode ) {
			if ( '' === $legacy_status ) {
				// No status filter: count ALL engagement kinds (vote + emoji + star)
				// so the growth chart reflects total activity, not just votes.
				$union[] = $this->wpdb->prepare(
					"SELECT date_time FROM `{$pulse}`
					WHERE item_type = %s AND status = 'active'{$since} AND {$range}",
					$item_type
				);
			} else {
				// Vote-specific status filter (like/dislike): keep vote-only.
				$row = WP_Ulike_Pulse_Vote_Map::legacy_to_row( $legacy_status );
				$vote_since = 'merged' === $mode && class_exists( 'WP_Ulike_Pulse_Config' )
					? $this->wpdb->prepare( ' AND date_time >= %s', WP_Ulike_Pulse_Config::dual_since() )
					: '';
				$union[] = $this->wpdb->prepare(
					"SELECT date_time FROM `{$pulse}`
					WHERE item_type = %s AND engagement_kind = '{$kind}'
					AND engagement_key = %s AND status = %s{$vote_since} AND {$range}",
					$item_type,
					$row['engagement_key'],
					$row['status']
				);
			}
		}

			if ( 'legacy' === $mode || 'merged' === $mode ) {
				$source = class_exists( 'WP_Ulike_Pulse_Registry' )
					? WP_Ulike_Pulse_Registry::legacy_source_for_type( $item_type )
					: null;
				if ( $source && WP_Ulike_Pulse_Registry::table_exists( $source['table'] ) ) {
					$lt   = esc_sql( $source['table'] );
					$st   = '' === $legacy_status ? "status IN ('like','dislike')" : $this->wpdb->prepare( 'status = %s', $legacy_status );
					$union[] = "SELECT date_time FROM `{$lt}` WHERE {$st} AND {$range}";
				}
			}

			if ( empty( $union ) ) {
				return array();
			}

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- fragments built from prepared statements.
			return $this->wpdb->get_results(
				'SELECT DATE(date_time) AS labels, COUNT(date_time) AS counts FROM ('
				. implode( ' UNION ALL ', $union )
				. ') AS combined GROUP BY labels ORDER BY labels ASC',
				ARRAY_A
			);
		}

		/**
		 * Current pulse read mode for REST context.
		 *
		 * @return string
		 */
		private function rest_read_mode() {
			if ( ! class_exists( 'WP_Ulike_Pulse_Query' ) || ! method_exists( 'WP_Ulike_Pulse_Query', 'read_mode' ) ) {
				return 'pulse';
			}
			return WP_Ulike_Pulse_Query::read_mode();
		}

		/**
		 * @param array<int,array<string,mixed>> $rows Query rows.
		 * @return array<string,int>
		 */
		private function map_count_rows( $rows ) {
			$output = array();
			foreach ( (array) $rows as $row ) {
				$output[ $row['labels'] ] = (int) $row['counts'];
			}
			krsort( $output );
			return $output;
		}

		/**
		 * @param string $table_suffix Legacy suffix.
		 * @return string|null
		 */
		private function get_first_log_date( $table_suffix ) {
			$item_type = WP_Ulike_Pulse_Registry::resolve_log_identifier( $table_suffix );
			if ( ! $item_type ) {
				return null;
			}

			$pulse = esc_sql( wp_ulike_pro_pulse_table() );
			$kind  = esc_sql( WP_Ulike_Pulse_Registry::KIND_VOTE );
			$mode  = $this->rest_read_mode();
			$since = 'merged' === $mode && class_exists( 'WP_Ulike_Pulse_Config' )
				? $this->wpdb->prepare( ' AND date_time >= %s', WP_Ulike_Pulse_Config::dual_since() )
				: '';

			$union = array();

			if ( 'legacy' !== $mode ) {
				$union[] = $this->wpdb->prepare(
					"SELECT date_time FROM `{$pulse}`
					WHERE item_type = %s AND engagement_kind = '{$kind}' AND status = 'active'{$since}",
					$item_type
				);
			}

			if ( 'legacy' === $mode || 'merged' === $mode ) {
				$source = class_exists( 'WP_Ulike_Pulse_Registry' )
					? WP_Ulike_Pulse_Registry::legacy_source_for_type( $item_type )
					: null;
				if ( $source && WP_Ulike_Pulse_Registry::table_exists( $source['table'] ) ) {
					$lt       = esc_sql( $source['table'] );
					$union[]  = "SELECT date_time FROM `{$lt}` WHERE status IN ('like','dislike')";
				}
			}

			if ( empty( $union ) ) {
				return null;
			}

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- fragments built from prepared statements.
			$value = $this->wpdb->get_var(
				'SELECT MIN(date_time) FROM (' . implode( ' UNION ALL ', $union ) . ') AS combined'
			);

			return $value ? gmdate( 'Y-m-d', strtotime( $value ) ) : null;
		}

		/**
		 * @return array<int,string>
		 */
		private function get_date_labels() {
			if ( empty( $this->date_range['start'] ) || empty( $this->date_range['end'] ) ) {
				return array();
			}

			$start  = new DateTime( $this->date_range['start'] );
			$end    = new DateTime( $this->date_range['end'] );
			$end    = $end->modify( '+1 day' );
			$period = new DatePeriod( $start, new DateInterval( 'P1D' ), $end );
			$labels = array();

			foreach ( $period as $day ) {
				$labels[] = $day->format( 'Y-m-d' );
			}

			return $labels;
		}

		/**
		 * @return string
		 */
		private function get_mysql_date_range_sql() {
			if ( empty( $this->date_range['start'] ) || empty( $this->date_range['end'] ) ) {
				return '1=1';
			}

			$start = sanitize_text_field( $this->date_range['start'] );
			$end   = sanitize_text_field( $this->date_range['end'] );

			if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $start ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $end ) ) {
				return '1=1';
			}

			$start = esc_sql( $start );
			$end   = esc_sql( $end );

			if ( $start === $end ) {
				return sprintf( "DATE(`date_time`) = '%s'", $start );
			}

			return sprintf( "DATE(`date_time`) >= '%s' AND DATE(`date_time`) <= '%s'", $start, $end );
		}

		/**
		 * @param mixed $raw_date Date range or period token.
		 * @return void
		 */
		private function set_date_range( $raw_date ) {
			if ( empty( $raw_date ) || ! is_array( $raw_date ) || empty( $raw_date['start'] ) ) {
				$this->date_range = array();
				return;
			}

			$this->date_range['start'] = gmdate( 'Y-m-d', strtotime( $raw_date['start'] ) );
			$this->date_range['end']   = ! empty( $raw_date['end'] )
				? gmdate( 'Y-m-d', strtotime( $raw_date['end'] ) )
				: $this->date_range['start'];
		}

		/**
		 * @param string $suffix Legacy table suffix.
		 * @return string
		 */
		private function sanitize_table_suffix( $suffix ) {
			$suffix = sanitize_key( (string) $suffix );
			return WP_Ulike_Pulse_Registry::type_by_table_suffix( $suffix ) ? $suffix : 'ulike';
		}

		/**
		 * @param string $status Legacy vote status.
		 * @return string
		 */
		private function sanitize_legacy_status( $status ) {
			$allowed = array( 'like', 'unlike', 'dislike', 'undislike' );
			$status  = sanitize_key( (string) $status );
			return in_array( $status, $allowed, true ) ? $status : 'like';
		}

		/**
		 * @param string $name Series key.
		 * @return array<string,mixed>
		 */
		private function chart_series_options( $name ) {
			$palette = array(
				'all'       => array( '#888add', esc_html__( 'All', WP_ULIKE_PRO_DOMAIN ) ),
				'like'      => array( '#12b89b', esc_html__( 'Like', WP_ULIKE_PRO_DOMAIN ) ),
				'dislike'   => array( '#e24f4e', esc_html__( 'Dislike', WP_ULIKE_PRO_DOMAIN ) ),
				'unlike'    => array( '#f18e2d', esc_html__( 'Un-Like', WP_ULIKE_PRO_DOMAIN ) ),
				'undislike' => array( '#4a5ca4', esc_html__( 'Un-Dislike', WP_ULIKE_PRO_DOMAIN ) ),
			);

			$color = $palette[ $name ][0] ?? '#888add';
			$label = $palette[ $name ][1] ?? $name;

			return array(
				'label'                => $label,
				'backgroundColor'      => $color,
				'borderColor'          => $color,
				'pointBackgroundColor' => 'rgba(255,255,255,1)',
				'fill'                 => false,
				'lineTension'          => 0,
				'borderWidth'          => 'all' === $name ? 3 : 2,
			);
		}

		/**
		 * @param string       $rel_type Post type filter.
		 * @param string|array $period   Period filter.
		 * @return string
		 */
		private function render_top_posts( $rel_type, $period ) {
			$posts = wp_ulike_get_most_liked_posts( 15, $rel_type, 'post', $period, array( 'like', 'dislike' ) );
			if ( empty( $posts ) ) {
				return $this->empty_top_message( $period );
			}

			$html = '';
			foreach ( $posts as $post ) {
				if ( empty( $post->post_title ) ) {
					continue;
				}

				$item_id       = wp_ulike_get_the_id( $post->ID );
				$is_distinct   = wp_ulike_setting_repo::isDistinct( 'post' );
				$like_count    = wp_ulike_pro_get_counter_value( $item_id, 'post', 'like', $is_distinct, $period );
				$dislike_count = wp_ulike_pro_get_counter_value( $item_id, 'post', 'dislike', $is_distinct, $period );

				$html .= sprintf(
					'<li><a href="%s">%s</a> <span class="wp_ulike_item_counter">%s%s</span></li>',
					esc_url( get_permalink( $post->ID ) ),
					esc_html( stripslashes( $post->post_title ) ),
					$this->vote_count_html( $like_count, 'up' ),
					$this->vote_count_html( $dislike_count, 'down' )
				);
			}

			return $html;
		}

		/**
		 * @param string|array $period Period filter.
		 * @return string
		 */
		private function render_top_comments( $period ) {
			$comments = wp_ulike_get_most_liked_comments( 15, '', $period, array( 'like', 'dislike' ) );
			if ( empty( $comments ) ) {
				return $this->empty_top_message( $period );
			}

			$html = '';
			foreach ( $comments as $comment ) {
				$is_distinct   = wp_ulike_setting_repo::isDistinct( 'comment' );
				$like_count    = wp_ulike_pro_get_counter_value( $comment->comment_ID, 'comment', 'like', $is_distinct, $period );
				$dislike_count = wp_ulike_pro_get_counter_value( $comment->comment_ID, 'comment', 'dislike', $is_distinct, $period );

				$html .= sprintf(
					'<li><span class="comment-info"><span class="comment-author-link">%s</span> %s <a href="%s">%s</a></span><span class="wp_ulike_item_counter">%s%s</span></li>',
					esc_html( stripslashes( $comment->comment_author ) ),
					esc_html__( 'on', WP_ULIKE_PRO_DOMAIN ),
					esc_url( get_comment_link( $comment->comment_ID ) ),
					esc_html( get_the_title( $comment->comment_post_ID ) ),
					$this->vote_count_html( $like_count, 'up' ),
					$this->vote_count_html( $dislike_count, 'down' )
				);
			}

			return $html;
		}

		/**
		 * @param string|array $period Period filter.
		 * @return string
		 */
		private function render_top_activities( $period ) {
			if ( ! defined( 'BP_VERSION' ) ) {
				return '<li>' . sprintf(
					esc_html__( '%s is Not Activated!', WP_ULIKE_PRO_DOMAIN ),
					esc_html__( 'BuddyPress', WP_ULIKE_PRO_DOMAIN )
				) . '</li>';
			}

			$activities = wp_ulike_get_most_liked_activities( 15, $period, array( 'like', 'dislike' ) );
			if ( empty( $activities ) ) {
				return $this->empty_top_message( $period );
			}

			$html = '';
			foreach ( $activities as $activity ) {
				$action = ! empty( $activity->content ) ? $activity->content : $activity->action;
				if ( empty( $action ) ) {
					continue;
				}

				$is_distinct   = wp_ulike_setting_repo::isDistinct( 'activity' );
				$like_count    = wp_ulike_pro_get_counter_value( $activity->id, 'activity', 'like', $is_distinct, $period );
				$dislike_count = wp_ulike_pro_get_counter_value( $activity->id, 'activity', 'dislike', $is_distinct, $period );
				$permalink     = function_exists( 'bp_activity_get_permalink' ) ? bp_activity_get_permalink( $activity->id ) : '';

				$html .= sprintf(
					'<li><a href="%s">%s</a> <span class="wp_ulike_item_counter">%s%s</span></li>',
					esc_url( $permalink ),
					esc_html( wp_trim_words( $action, 20, null ) ),
					$this->vote_count_html( $like_count, 'up' ),
					$this->vote_count_html( $dislike_count, 'down' )
				);
			}

			return $html;
		}

		/**
		 * @param string|array $period Period filter.
		 * @return string
		 */
		private function render_top_topics( $period ) {
			if ( ! function_exists( 'is_bbpress' ) ) {
				return '<li>' . sprintf(
					esc_html__( '%s is Not Activated!', WP_ULIKE_PRO_DOMAIN ),
					esc_html__( 'bbPress', WP_ULIKE_PRO_DOMAIN )
				) . '</li>';
			}

			$posts = wp_ulike_get_most_liked_posts( 15, array( 'topic', 'reply' ), 'topic', $period, array( 'like', 'dislike' ) );
			if ( empty( $posts ) ) {
				return $this->empty_top_message( $period );
			}

			$html = '';
			foreach ( $posts as $post ) {
				$title     = function_exists( 'bbp_get_forum_title' ) ? bbp_get_forum_title( $post->ID ) : $post->post_title;
				$permalink = 'topic' === get_post_type( $post->ID ) ? bbp_get_topic_permalink( $post->ID ) : bbp_get_reply_url( $post->ID );
				$distinct  = wp_ulike_setting_repo::isDistinct( 'topic' );
				$likes     = wp_ulike_pro_get_counter_value( $post->ID, 'topic', 'like', $distinct, $period );
				$dislikes  = wp_ulike_pro_get_counter_value( $post->ID, 'topic', 'dislike', $distinct, $period );

				$html .= sprintf(
					'<li><a href="%s">%s</a> <span class="wp_ulike_item_counter">%s%s</span></li>',
					esc_url( $permalink ),
					esc_html( $title ),
					$this->vote_count_html( $likes, 'up' ),
					$this->vote_count_html( $dislikes, 'down' )
				);
			}

			return $html;
		}

		/**
		 * @param string|array $period Period filter.
		 * @return string
		 */
		private function render_top_likers( $period ) {
			$top_likers = wp_ulike_get_best_likers_info( 5, $period );
			if ( empty( $top_likers ) ) {
				return $this->empty_top_message( $period, true );
			}

			$html    = '';
			$counter = 1;
			foreach ( $top_likers as $user ) {
				$userdata = get_userdata( (int) $user->user_id );
				$name     = empty( $userdata ) ? esc_html__( 'Guest User', WP_ULIKE_PRO_DOMAIN ) : esc_html( $userdata->display_name );

				$html .= sprintf(
					'<div class="wp-ulike-flex wp-ulike-users-list"><div class="wp-ulike-counter"><i class="wp-ulike-icons-trophy"></i><span class="wp-ulike-counter">%1$dth</span></div><div class="wp-ulike-info"><i class="wp-ulike-icons-profile-male"></i><span class="wp-ulike-user-name">%2$s</span></div><div class="wp-ulike-total"><i class="wp-ulike-icons-heart"></i><span class="wp-ulike-user-name">%3$s</span></div></div>',
					$counter++,
					$name,
					number_format_i18n( (int) $user->SumUser )
				);
			}

			return $html;
		}

		/**
		 * @param string|array $period Period filter.
		 * @param bool         $flex   Use flex wrapper markup.
		 * @return string
		 */
		private function empty_top_message( $period, $flex = false ) {
			$period_info = is_array( $period ) ? implode( ' - ', $period ) : $period;
			$message     = sprintf(
				'%s "%s" %s',
				esc_html__( 'No results were found in', WP_ULIKE_PRO_DOMAIN ),
				esc_html( (string) $period_info ),
				esc_html__( 'period', WP_ULIKE_PRO_DOMAIN )
			);

			return $flex
				? sprintf( '<div class="wp-ulike-flex wp-ulike-users-list">%s</div>', $message )
				: sprintf( '<li>%s</li>', $message );
		}

		/**
		 * @param int    $count Vote count.
		 * @param string $type  up|down.
		 * @return string
		 */
		private function vote_count_html( $count, $type ) {
			if ( empty( $count ) ) {
				return '';
			}

			$icon  = 'up' === $type ? 'thumbs-up2' : 'thumbs-down2';
			$class = 'up' === $type ? 'wp_ulike_up_vote_count' : 'wp_ulike_down_vote_count';

			return sprintf(
				'<span class="%s"><i class="wp-ulike-icons-%s"></i> %s</span>',
				esc_attr( $class ),
				esc_attr( $icon ),
				number_format_i18n( (int) $count )
			);
		}
	}
}

