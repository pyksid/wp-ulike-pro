<?php
/**
 * @package WP_Ulike_Pro
 */
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

trait WP_Ulike_Pro_Stats_Trait_Trends {
		private function get_aggregated_data($cache_key, $interval, $selectExpression, $orderByExpression, callable $formatter) {
			// Try to fetch a cached result.
			$cached = wp_cache_get($cache_key, WP_ULIKE_PRO_DOMAIN);
			if ( false !== $cached ) {
				return $cached;
			}

			// Validate interval format to avoid potential SQL injection.
			if ( ! preg_match('/^\d+\s+(DAY|MONTH|YEAR)$/i', $interval) ) {
				// Fallback to a default interval if invalid.
				$interval = '5 MONTH';
			}

			// Build the union query to aggregate date_time from all relevant tables.
			$unionQuery = $this->build_trend_union_query_by_interval( $interval );

			if ( empty( $unionQuery ) ) {
				wp_cache_set( $cache_key, array(), WP_ULIKE_PRO_DOMAIN, 10 );
				return array();
			}

			// Build the full query using the provided SELECT expression and ORDER BY clause.
			$query = "
				SELECT
					{$selectExpression} AS period,
					COUNT(*) AS total_count
				FROM (
					{$unionQuery}
				) AS combined
				GROUP BY period
				ORDER BY {$orderByExpression} ASC
			";

			$results = $this->wpdb->get_results($query);

			$data = [];
			if ( ! empty($results) ) {
				foreach ( $results as $result ) {
					$data[] = $formatter($result);
				}
			}

			// Cache the result for a short duration (10 seconds) to ensure near real-time data.
			wp_cache_set($cache_key, $data, WP_ULIKE_PRO_DOMAIN, 10);

			return $data;
		}

		/**
		 * Get aggregated data by past months.
		 *
		 * @param string $interval Time interval (e.g., '5 MONTH').
		 * @return array
		 */
		public function get_aggregated_data_by_month($interval = '5 MONTH') {
			$cache_key = 'aggregated_data_by_month_' . md5($interval);
			return $this->get_aggregated_data(
				$cache_key,
				$interval,
				"DATE_FORMAT(date_time, '%Y-%m')",  // Group by year-month.
				"DATE_FORMAT(date_time, '%Y-%m')",  // Order by year-month.
				function( $result ) {
					// Convert the period (year-month) to a timestamp and format it.
					$date = DateTime::createFromFormat('Y-m', $result->period);
					return [
						'total_count' => absint($result->total_count),
						'month_name'  => wp_date("F Y", $date->getTimestamp())
					];
				}
			);
		}

		/**
		 * Get aggregated data by past days.
		 *
		 * @param string $interval Time interval (e.g., '6 DAY').
		 * @return array
		 */
		public function get_aggregated_data_by_date($interval = '6 DAY') {
			$cache_key = 'aggregated_data_by_date_' . md5($interval);
			return $this->get_aggregated_data(
				$cache_key,
				$interval,
				"DATE(date_time)",   // Group by date.
				"DATE(date_time)",   // Order by date.
				function( $result ) {
					return [
						'total_count' => absint($result->total_count),
						'vote_date'   => wp_date("Y-m-d", strtotime($result->period))
					];
				}
			);
		}

		/**
		 * Get aggregated engagement data for an explicit date range.
		 *
		 * @param string $start Start date (Y-m-d).
		 * @param string $end   End date (Y-m-d).
		 * @return array
		 */
		public function get_aggregated_data_by_date_range( $start, $end ) {
			$start = sanitize_text_field( $start );
			$end   = sanitize_text_field( $end );

			if (
				! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $start ) ||
				! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $end )
			) {
				return array();
			}

			$cache_key = 'aggregated_data_by_date_range_' . md5( $start . $end );
			$cached    = wp_cache_get( $cache_key, WP_ULIKE_PRO_DOMAIN );
			if ( false !== $cached ) {
				return $cached;
			}

			$union_query = $this->build_trend_union_query_by_range( $start, $end );

			if ( empty( $union_query ) ) {
				wp_cache_set( $cache_key, array(), WP_ULIKE_PRO_DOMAIN, 10 );
				return array();
			}
			$query       = "
				SELECT
					DATE(date_time) AS period,
					COUNT(*) AS total_count
				FROM (
					{$union_query}
				) AS combined
				GROUP BY period
				ORDER BY period ASC
			";

			$results = $this->wpdb->get_results( $query );
			$data    = array();

			if ( ! empty( $results ) ) {
				foreach ( $results as $result ) {
					$data[] = array(
						'total_count' => absint( $result->total_count ),
						'vote_date'   => wp_date( 'Y-m-d', strtotime( $result->period ) ),
					);
				}
			}

			wp_cache_set( $cache_key, $data, WP_ULIKE_PRO_DOMAIN, 10 );

			return $data;
		}

		/**
		 * Get aggregated button views by day for a date range.
		 *
		 * @param string $start Start date (Y-m-d).
		 * @param string $end   End date (Y-m-d).
		 * @return array
		 */
		public function get_daily_views_by_date_range( $start, $end ) {
			if ( ! $this->button_views || ! $this->is_any_view_tracking_enabled() ) {
				return array();
			}

			$start = sanitize_text_field( $start );
			$end   = sanitize_text_field( $end );

			if (
				! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $start ) ||
				! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $end )
			) {
				return array();
			}

			$cache_key = 'daily_views_by_date_range_' . md5( $start . $end );
			$cached    = wp_cache_get( $cache_key, WP_ULIKE_PRO_DOMAIN );
			if ( false !== $cached ) {
				return $cached;
			}

			global $wpdb;
			$table   = $wpdb->prefix . 'ulike_views';
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT view_date, SUM(view_count) AS total_count
					FROM {$table}
					WHERE view_date >= %s AND view_date <= %s
					GROUP BY view_date
					ORDER BY view_date ASC",
					$start,
					$end
				)
			);

			$data = array();
			if ( ! empty( $results ) ) {
				foreach ( $results as $result ) {
					$data[] = array(
						'total_count' => absint( $result->total_count ),
						'vote_date'   => $result->view_date,
					);
				}
			}

			wp_cache_set( $cache_key, $data, WP_ULIKE_PRO_DOMAIN, 10 );

			return $data;
		}

		// Method to count device types across all relevant tables
		private function build_trend_union_query_by_interval( $interval ) {
			$parts = array();

			foreach ( $this->tables as $type_key => $table_name ) {
				$part = $this->stats_pulse_date_time_select_sql(
					$type_key,
					"AND date_time >= NOW() - INTERVAL {$interval}"
				);

				if ( $part ) {
					$parts[] = $part;
				}
			}

			return empty( $parts ) ? '' : implode( ' UNION ALL ', $parts );
		}

		/**
		 * Build UNION ALL parts for engagement trend charts in a date range.
		 *
		 * @param string $start Start date Y-m-d.
		 * @param string $end   End date Y-m-d.
		 * @return string
		 */
		private function build_trend_union_query_by_range( $start, $end ) {
			$parts = array();

			foreach ( $this->tables as $type_key => $table_name ) {
				// Compare the raw column against day-boundary timestamps instead of
				// wrapping date_time in DATE() so this stays sargable on the
				// multi-million-row pulse table (same calendar-day rows either way --
				// $start/$end are already validated as Y-m-d above).
				$part = $this->stats_pulse_date_time_select_sql(
					$type_key,
					$this->wpdb->prepare(
						'AND date_time >= %s AND date_time <= %s',
						$start . ' 00:00:00',
						$end . ' 23:59:59'
					)
				);

				if ( $part ) {
					$parts[] = $part;
				}
			}

			return empty( $parts ) ? '' : implode( ' UNION ALL ', $parts );
		}

		/**
		 * Count like/dislike or reaction engagements across all content types.
		 *
		 * @param mixed $date Period filter.
		 * @return int
		 */
}

