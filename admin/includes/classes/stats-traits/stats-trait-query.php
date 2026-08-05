<?php
/**
 * @package WP_Ulike_Pro
 */
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

trait WP_Ulike_Pro_Stats_Trait_Query {
	private function getMySqlDateRange( $table ) {
		$table = esc_sql( $table );

		// Return early if date range is set
		if ( ! empty( $this->dateRange ) ) {
			// Sanitize date values - ensure they are valid date format
			$start = isset( $this->dateRange['start'] ) ? sanitize_text_field( $this->dateRange['start'] ) : '';
			$end = isset( $this->dateRange['end'] ) ? sanitize_text_field( $this->dateRange['end'] ) : '';

			// Validate date format (YYYY-MM-DD)
			if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $start ) ) {
				$start = '';
			}
			if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $end ) ) {
				$end = '';
			}

			if ( empty( $start ) || empty( $end ) ) {
				return '1=1'; // Return neutral condition if dates are invalid
			}

			// Escape date values for safe use in SQL (since this string is inserted into other queries)
			$start_escaped = esc_sql( $start );
			$end_escaped = esc_sql( $end );

			// Compare the raw column against day-boundary timestamps instead of
			// wrapping `date_time` in DATE() -- functionally identical result
			// (same calendar-day rows), but sargable so MySQL can use an index
			// on date_time instead of evaluating DATE() per row on every scan.
			if ( $start === $end ) {
				return sprintf( "`date_time` BETWEEN '%s 00:00:00' AND '%s 23:59:59'", $start_escaped, $start_escaped );
			} else {
				return sprintf( "`date_time` BETWEEN '%s 00:00:00' AND '%s 23:59:59'", $start_escaped, $end_escaped );
			}
		}

			// Cache for the latest date query
			$cache_key = 'latest_date_' . $table;
			$latest_date = wp_cache_get( $cache_key, WP_ULIKE_PRO_DOMAIN );

			if ( false === $latest_date ) {
				$latest_date = $this->wpdb->get_var( "SELECT MAX(date_time) FROM `$table`" );
				wp_cache_set( $cache_key, $latest_date, WP_ULIKE_PRO_DOMAIN, 10 );
			}

			if( empty( $latest_date ) ){
				$latest_date = current_time( 'mysql' );
			}

		// Calculate the 30 days range
		$latest_date_timestamp = strtotime( $latest_date );
		if ( false === $latest_date_timestamp ) {
			$latest_date_timestamp = current_time( 'timestamp' );
		}
		$date_30_days_before   = date( 'Y-m-d', $latest_date_timestamp - DAY_IN_SECONDS * 30 );
		$latest_date_formatted = date( 'Y-m-d', $latest_date_timestamp );

		// Escape date values for safe use in SQL (since this string is inserted into other queries)
		$date_30_days_before_escaped = esc_sql( $date_30_days_before );
		$latest_date_formatted_escaped = esc_sql( $latest_date_formatted );

		return sprintf( "DATE(`date_time`) BETWEEN '%s' AND '%s'", $date_30_days_before_escaped, $latest_date_formatted_escaped );
		}


	/**
	 * Set date range with our format
	 *
	 * @param array $rawDate
	 * @return void
	 */
	private function setDateRange( $rawDate ){
		if ( empty( $rawDate['start'] ) ) {
			$this->dateRange = [];
			return;
		}

		// Sanitize and validate start date
		$start_raw = sanitize_text_field( $rawDate['start'] );
		$start_timestamp = strtotime( $start_raw );
		if ( false === $start_timestamp ) {
			$this->dateRange = [];
			return;
		}
		$this->dateRange['start'] = date( "Y-m-d", $start_timestamp );

		// Sanitize and validate end date
		if ( ! empty( $rawDate['end'] ) ) {
			$end_raw = sanitize_text_field( $rawDate['end'] );
			$end_timestamp = strtotime( $end_raw );
			if ( false !== $end_timestamp ) {
				$this->dateRange['end'] = date( "Y-m-d", $end_timestamp );
			} else {
				$this->dateRange['end'] = $this->dateRange['start'];
			}
		} else {
			$this->dateRange['end'] = $this->dateRange['start'];
		}
	}


		/**
		 * Get The Logs Data From Tables
		 *
		 * @return Object
		 */
		public function select_data( $table ){
			$allowed_tables = array( 'ulike', 'ulike_comments', 'ulike_activities', 'ulike_forums' );
			if ( ! in_array( $table, $allowed_tables, true ) ) {
				$table = 'ulike';
			}

			$data_limit = absint( apply_filters( 'wp_ulike_stats_data_limit', 30 ) );
			$cache_key  = 'select_data_' . md5( $table . '_' . $data_limit );
			$cached     = wp_cache_get( $cache_key, WP_ULIKE_PRO_DOMAIN );

			if ( false !== $cached ) {
				return $cached;
			}

			$result = $this->stats_pulse_chart_dataset( $table, $data_limit );

			if ( empty( $result ) ) {
				// get_dataset() expects an array of {labels, counts} row objects.
				$result = array();
			}

			wp_cache_set( $cache_key, $result, WP_ULIKE_PRO_DOMAIN, 10 );

			return $result;
		}

		/**
		 * Count all logs from the tables
		 *
		 * @since 3.5
		 * @param string $date
		 * @return integer
		 */
	public function count_all_logs( $date = 'all' ){
		$logical_key = 'pro_count_all_logs_' . ( is_array( $date ) ? wp_json_encode( $date ) : $date );

		return (int) WP_Ulike_Query_Cache::remember_stats(
			$logical_key,
			function () use ( $date ) {
				$total = 0;

				foreach ( $this->tables as $type_key => $table ) {
					if ( ! function_exists( 'is_bbpress' ) && 'topics' === $type_key ) {
						continue;
					}

					if ( ! defined( 'BP_VERSION' ) && 'activities' === $type_key ) {
						continue;
					}

					$total += $this->count_logs_for_stats_type( $type_key, $date );
				}

				return $total;
			}
		);
	}

		/**
		 * Count all logs
		 *
		 * @return array
		 */
		private function get_count_logs() {
			$tables = $this->get_tables();
			$count_logs = array();

			foreach ($tables as $type => $table) {

				// check bbpress installation status
				if( ! function_exists( 'is_bbpress' ) && $type === 'topics' ) {
					continue;
				}

				// check buddpress installation status
				if( ! defined( 'BP_VERSION' ) && $type === 'activities' ) {
					continue;
				}

				$count_logs[$type] = array(
					'week'       => $this->count_logs_for_stats_type( $type, 'week' ),
					'last_week'  => $this->count_logs_for_stats_type( $type, 'last_week' ),
					'month'      => $this->count_logs_for_stats_type( $type, 'month' ),
					'last_month' => $this->count_logs_for_stats_type( $type, 'last_month' ),
					'year'       => $this->count_logs_for_stats_type( $type, 'year' ),
					'last_year'  => $this->count_logs_for_stats_type( $type, 'last_year' ),
					'all'        => $this->count_logs_for_stats_type( $type, 'all' ),
				);
			}

			return $count_logs;
		}

		/**
		 * Count logs by table
		 *
		 * @param array $args
		 * @return void
		 */
		public function count_logs( $args = array() ){
			//Main Data
			$defaults  = array(
				"table" => 'ulike',
				"date"  => 'all'
			);

			$parsed_args = wp_parse_args( $args, $defaults );

			// Extract variables safely instead of using extract()
			$table = isset( $parsed_args['table'] ) ? $parsed_args['table'] : 'ulike';
			$date = isset( $parsed_args['date'] ) ? $parsed_args['date'] : 'all';

			// Versioned cache (auto-invalidated on every vote/engagement bump)
			// so the "all" total stays consistent with week/month/year cards
			// and never returns a stale persisted meta like the old path did.
			$logical_key = sprintf(
				'count_logs_%s_%s',
				$table,
				is_array( $date ) ? wp_json_encode( $date ) : $date
			);

			return (int) WP_Ulike_Query_Cache::remember_stats(
				$logical_key,
				function () use ( $table, $date ) {
					return (int) $this->stats_pulse_count_for_table_suffix( $table, $date );
				}
			);
		}

		/**
		 * Count engaged users by table
		 *
		 * @param array $args
		 * @return void
		 */
		public function count_total_interactions( $args = array() ){
			//Main Data
			$defaults  = array(
				"table" => 'ulike',
				"date"  => 'all'
			);

			$parsed_args = wp_parse_args( $args, $defaults );

			// Extract variables safely instead of using extract()
			$table = isset( $parsed_args['table'] ) ? $parsed_args['table'] : 'ulike';
			$date = isset( $parsed_args['date'] ) ? $parsed_args['date'] : 'all';

			// Versioned cache so unique-voter counts invalidate immediately on
			// any vote/engagement bump instead of lagging up to 10s.
			$logical_key = sprintf(
				'count_total_interactions_%s_%s',
				$table,
				is_array( $date ) ? wp_json_encode( $date ) : $date
			);

			return (int) WP_Ulike_Query_Cache::remember_stats(
				$logical_key,
				function () use ( $table, $date ) {
					return (int) $this->stats_pulse_count_unique_voters_for_table_suffix( $table, $date );
				}
			);
		}

		/**
		 * Get top items of each type
		 *
		 * @param string $type
		 * @param array|string $date_range
		 * @param array $args
		 * @return array
		 */
		public function get_dataset( $table ){
			$output  = array();
			// Get data
			$results = $this->select_data( $table );

			// Create chart dataset
			foreach( $results as $result ){
				if( isset( $result->labels ) && isset( $result->counts ) ){
					$output[]= [
						'date'  => wp_date( "Y-m-d", strtotime( $result->labels ) ),
						'total' => (int) $result->counts
					];
				}
			}

			return $output;
		}

		/**
		 * Get custom dataset for each type.
		 *
		 * @param string $type            Stats type key (posts|comments|…).
		 * @param string $start_date      Start date (Y-m-d) or empty.
		 * @param string $end_date        End date (Y-m-d) or empty.
		 * @param array  $selected_status Legacy vote status filter.
		 * @param array  $filters         {engagement_keys?: string[], values?: int[]}.
		 * @return array
		 */
		public function get_custom_dataset( $type, $start_date, $end_date, $selected_status, $filters = array() ){
			$output  = array();

			$tables = $this->get_tables();
			if( ! isset( $tables[$type] ) ) {
				return $output;
			}

			if( $start_date && $end_date ){
				$this->setDateRange( [
					'start' => $start_date,
					'end'   => $end_date
				]);
			}
			if( $selected_status ){
				$this->selectedStatus = $selected_status;
			} else {
				$this->selectedStatus = array();
			}

			$has_reaction = ! empty( $filters['engagement_keys'] );
			$has_rating   = ! empty( $filters['values'] );

			// Reaction/rating filters are engagement-table scoped — do not
			// union classic vote series (that leaked unfiltered likes into
			// a "Love" reaction chart).
			if ( $has_reaction || $has_rating ) {
				return $this->get_engagement_chart_rows( $type, $start_date, $end_date, $filters );
			}

			// Default: union classic votes with emoji/star series.
			$vote_rows = $this->select_charts_data( $tables[ $type ] );
			$eng_rows  = $this->get_engagement_chart_rows( $type, $start_date, $end_date, $filters );

			return $this->merge_chart_datasets( array( $vote_rows, $eng_rows ) );
		}

		/**
		 * Emoji/star daily chart rows for a stats type (optional reaction/rating filters).
		 *
		 * @param string $type       Stats type key.
		 * @param string $start_date Y-m-d or empty.
		 * @param string $end_date   Y-m-d or empty.
		 * @param array  $filters    {engagement_keys?: string[], values?: int[]}.
		 * @return array<int,array<string,mixed>>
		 */
		private function get_engagement_chart_rows( $type, $start_date, $end_date, $filters = array() ) {
			$item_type  = WP_Ulike_Pro_Stats_Type_Resolver::map_stats_type_to_item_type( $type );
			$reader     = $this->engagement();
			$date_range = ( $start_date && $end_date )
				? array( 'start' => $start_date, 'end' => $end_date )
				: 'month';

			$rows         = array();
			$has_reaction = ! empty( $filters['engagement_keys'] );
			$has_rating   = ! empty( $filters['values'] );

			// Emoji series: always when unfiltered; only filtered breakdown when reaction set.
			// Skip entirely when rating-only (stars are separate).
			if ( $has_reaction ) {
				$rows = array_merge(
					$rows,
					$reader->get_dataset_breakdown( $item_type, $date_range, 'emoji', $filters )
				);
			} elseif ( ! $has_rating ) {
				foreach ( (array) $reader->get_dataset( $item_type, $date_range, 'emoji' ) as $row ) {
					$rows[] = array(
						'date'  => $row['date'],
						'emoji' => (int) ( $row['total'] ?? 0 ),
					);
				}
			}

			// Star series: always when unfiltered; only filtered breakdown when rating set.
			// Skip entirely when reaction-only.
			if ( $has_rating ) {
				$rows = array_merge(
					$rows,
					$reader->get_dataset_breakdown( $item_type, $date_range, 'star', $filters )
				);
			} elseif ( ! $has_reaction ) {
				foreach ( (array) $reader->get_dataset( $item_type, $date_range, 'star' ) as $row ) {
					$rows[] = array(
						'date' => $row['date'],
						'star' => (int) ( $row['total'] ?? 0 ),
					);
				}
			}

			return $rows;
		}

		/**
		 * Merge multiple date-keyed chart datasets into one series list.
		 *
		 * @param array<int,array<int,array<string,mixed>>> $datasets Datasets to merge.
		 * @return array<int,array<string,mixed>>
		 */
		private function merge_chart_datasets( array $datasets ) {
			$by_date = array();

			foreach ( $datasets as $rows ) {
				foreach ( (array) $rows as $row ) {
					if ( empty( $row['date'] ) ) {
						continue;
					}
					$date = (string) $row['date'];
					if ( ! isset( $by_date[ $date ] ) ) {
						$by_date[ $date ] = array( 'date' => $date );
					}
					foreach ( $row as $key => $value ) {
						if ( 'date' === $key ) {
							continue;
						}
						$by_date[ $date ][ $key ] = (int) ( $by_date[ $date ][ $key ] ?? 0 ) + (int) $value;
					}
				}
			}

			ksort( $by_date );

			return array_values( $by_date );
		}

		/**
		 * @deprecated Kept for callers; prefer get_custom_dataset() union path.
		 *
		 * @param string $type       Stats type key.
		 * @param string $start_date Y-m-d or empty.
		 * @param string $end_date   Y-m-d or empty.
		 * @param array  $filters    {engagement_keys?: string[], values?: int[]}.
		 * @return array<int,array<string,mixed>>
		 */
		private function get_engagement_custom_dataset( $type, $start_date, $end_date, $filters = array() ) {
			return $this->get_engagement_chart_rows( $type, $start_date, $end_date, $filters );
		}

		/**
		 * Select charts data.
		 *
		 * @param string $table
		 * @return array
		 */
		public function select_charts_data( $table ) {
			$output = array();
			$allowed_tables = array( 'ulike', 'ulike_comments', 'ulike_activities', 'ulike_forums' );
			if ( ! in_array( $table, $allowed_tables, true ) ) {
				$table = 'ulike';
			}

			$cache_key = 'charts_data_' . md5( $table . serialize( $this->selectedStatus ) . serialize( $this->dateRange ) );
			$cached    = wp_cache_get( $cache_key, WP_ULIKE_PRO_DOMAIN );
			if ( false !== $cached ) {
				return $cached;
			}

			$item_type = WP_Ulike_Pulse_Registry::resolve_log_identifier( $table );
			if ( ! $item_type ) {
				return $output;
			}

			$range     = $this->getMySqlDateRange( wp_ulike_pro_pulse_table() );
			$statuses  = (array) $this->selectedStatus;
			$mode      = $this->stats_pulse_read_mode();
			$bucket    = array(); // date => ['total' => n] or date => [status => n].

			// Pulse slice (pulse + merged modes).
			if ( 'pulse' === $mode || 'merged' === $mode ) {
				$pulse      = $this->stats_pulse_table_esc();
				$kind_vote  = esc_sql( WP_Ulike_Pulse_Registry::KIND_VOTE );
				$since_sql  = $this->stats_pulse_dual_since_sql();
				$status_sql = $this->stats_pulse_legacy_status_sql( $statuses );
				// When status filters include unlike/undislike, do not force
				// status='active' — that AND-conflicts with status='removed'.
				$rows_sql = empty( $statuses )
					? $this->stats_pulse_vote_rows_sql()
					: "engagement_kind = '{$kind_vote}'";

				if ( empty( $statuses ) ) {
					$rows = $this->wpdb->get_results(
						$this->wpdb->prepare(
							"SELECT DATE(`date_time`) AS labels, COUNT(`date_time`) AS counts
							FROM `{$pulse}`
							WHERE item_type = %s AND {$rows_sql}{$since_sql} AND {$range}
							GROUP BY labels ORDER BY labels ASC",
							$item_type
						)
					);
					foreach ( (array) $rows as $row ) {
						$date = wp_date( 'Y-m-d', strtotime( $row->labels ) );
						$bucket[ $date ]['total'] = ( $bucket[ $date ]['total'] ?? 0 ) + (int) $row->counts;
					}
				} else {
					$map_key_sql = $this->stats_pulse_legacy_status_key_sql();
					$rows        = $this->wpdb->get_results(
						$this->wpdb->prepare(
							"SELECT DATE(`date_time`) AS labels, {$map_key_sql} AS status_key, COUNT(`date_time`) AS counts
							FROM `{$pulse}`
							WHERE item_type = %s AND {$rows_sql}{$since_sql} AND {$range} {$status_sql}
							GROUP BY labels, status_key
							ORDER BY labels, status_key ASC",
							$item_type
						)
					);
					foreach ( (array) $rows as $row ) {
						$date   = wp_date( 'Y-m-d', strtotime( $row->labels ) );
						$status = ! empty( $row->status_key ) ? (string) $row->status_key : 'like';
						$bucket[ $date ][ $status ] = ( $bucket[ $date ][ $status ] ?? 0 ) + (int) $row->counts;
					}
				}
			}

			// Legacy slice (legacy + merged modes) — pre-cutover daily activity.
			if ( 'legacy' === $mode || 'merged' === $mode ) {
				$this->merge_legacy_daily_chart( $bucket, $item_type, $range, $statuses );
			}

			$output = $this->finalize_chart_bucket( $bucket, $statuses );

			wp_cache_set( $cache_key, $output, WP_ULIKE_PRO_DOMAIN, 10 );
			return $output;
		}

		/**
		 * Merge legacy daily counts into a date-keyed chart bucket.
		 *
		 * @param array  $bucket    date => [key => count] (passed by reference).
		 * @param string $item_type Canonical item type.
		 * @param string $range     SQL date fragment (date_time-bound).
		 * @param array  $statuses  Selected legacy statuses; empty = total only.
		 * @return void
		 */
		private function merge_legacy_daily_chart( array &$bucket, $item_type, $range, $statuses ) {
			if ( ! class_exists( 'WP_Ulike_Pulse_Registry' ) ) {
				return;
			}

			$source = WP_Ulike_Pulse_Registry::legacy_source_for_type( $item_type );
			if ( ! $source || ! WP_Ulike_Pulse_Registry::table_exists( $source['table'] ) ) {
				return;
			}

			$table     = esc_sql( $source['table'] );
			$legacy_st = $statuses ? array_values( $statuses ) : array( 'like', 'dislike' );
			$status_in = "'" . implode( "','", array_map( 'esc_sql', $legacy_st ) ) . "'";

			if ( empty( $statuses ) ) {
				$rows = $this->wpdb->get_results(
					"SELECT DATE(`date_time`) AS labels, COUNT(`date_time`) AS counts
					FROM `{$table}`
					WHERE status IN ({$status_in}) AND {$range}
					GROUP BY labels ORDER BY labels ASC"
				);
				foreach ( (array) $rows as $row ) {
					$date = wp_date( 'Y-m-d', strtotime( $row->labels ) );
					$bucket[ $date ]['total'] = ( $bucket[ $date ]['total'] ?? 0 ) + (int) $row->counts;
				}
				return;
			}

			$rows = $this->wpdb->get_results(
				"SELECT DATE(`date_time`) AS labels, status, COUNT(`date_time`) AS counts
				FROM `{$table}`
				WHERE status IN ({$status_in}) AND {$range}
				GROUP BY labels, status
				ORDER BY labels, status ASC"
			);
			foreach ( (array) $rows as $row ) {
				$date  = wp_date( 'Y-m-d', strtotime( $row->labels ) );
				$key   = (string) $row->status;
				$bucket[ $date ][ $key ] = ( $bucket[ $date ][ $key ] ?? 0 ) + (int) $row->counts;
			}
		}

		/**
		 * Convert a date-keyed chart bucket into the chart payload shape.
		 *
		 * @param array $bucket   date => [key => count].
		 * @param array $statuses Selected legacy statuses (empty = total-only series).
		 * @return array<int,array<string,mixed>>
		 */
		private function finalize_chart_bucket( array $bucket, $statuses ) {
			if ( empty( $bucket ) ) {
				return array();
			}

			ksort( $bucket );
			$output = array();

			foreach ( $bucket as $date => $keys ) {
				if ( empty( $statuses ) ) {
					$output[] = array(
						'date'  => $date,
						'total' => (int) ( $keys['total'] ?? 0 ),
					);
					continue;
				}

				$row = array( 'date' => $date );
				foreach ( $statuses as $sv ) {
					$row[ $sv ] = (int) ( $keys[ $sv ] ?? 0 );
				}
				$output[] = $row;
			}

			return $output;
		}

		/**
		 * Daily chart rows from pulse for a legacy table suffix.
		 *
		 * @param string $table_suffix ulike|ulike_comments|…
		 * @param int    $data_limit   Number of days.
		 * @return object|null
		 */
	private function stats_pulse_chart_dataset( $table_suffix, $data_limit = 30 ) {
		$item_type = WP_Ulike_Pulse_Registry::resolve_log_identifier( $table_suffix );
		if ( ! $item_type ) {
			return null;
		}

		// Delegate to the free plugin's mode-aware bridge so dual/legacy sites
		// include pre-cutover daily activity. Returns [{labels, counts}] rows.
		if ( class_exists( 'WP_Ulike_Pulse_Log_Bridge' ) && method_exists( 'WP_Ulike_Pulse_Log_Bridge', 'get_chart_dataset' ) ) {
			return WP_Ulike_Pulse_Log_Bridge::get_chart_dataset( $table_suffix, $data_limit );
		}

		$data_limit = max( 1, absint( $data_limit ) );
		$pulse      = $this->stats_pulse_table_esc();
		$rows_sql   = $this->stats_pulse_vote_rows_sql();
		$latest     = $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT MAX(date_time) FROM `{$pulse}` WHERE item_type = %s AND {$rows_sql}",
				$item_type
			)
		);

		if ( ! $latest ) {
			return null;
		}

	$start = gmdate( 'Y-m-d H:i:s', strtotime( $latest ) - ( $data_limit * DAY_IN_SECONDS ) );

	// Return rows as {labels, counts} objects — the contract get_dataset() expects.
	return $this->wpdb->get_results(
		$this->wpdb->prepare(
			"SELECT DATE(date_time) AS labels, COUNT(date_time) AS counts
			FROM `{$pulse}`
			WHERE item_type = %s AND {$rows_sql}
			AND date_time >= %s AND date_time <= %s
			GROUP BY labels ORDER BY labels ASC",
			$item_type,
			$start,
			$latest
		)
	);
}

	/**
	 * Get MySQL date range format.
	 *
	 * @param string $table
	 * @return string
	 */
		private function count_status_logs( $table, $status, $date = 'week' ) {
			$allowed_statuses = array( 'like', 'dislike', 'unlike', 'undislike' );
			if ( ! in_array( $status, $allowed_statuses, true ) ) {
				return 0;
			}

			$cache_key = sanitize_key(
				sprintf(
					'count_status_%s_%s_%s',
					$table,
					$status,
					is_array( $date ) ? wp_json_encode( $date ) : $date
				)
			);
			$cached = wp_cache_get( $cache_key, WP_ULIKE_PRO_DOMAIN );
			if ( false !== $cached ) {
				return (int) $cached;
			}

			$count = (int) $this->stats_pulse_count_status_for_table_suffix( $table, $status, $date );
			wp_cache_set( $cache_key, $count, WP_ULIKE_PRO_DOMAIN, 10 );

			return $count;
		}

		/**
		 * Enriched overview counters with period comparisons.
		 *
		 * @return array
		 */
}

