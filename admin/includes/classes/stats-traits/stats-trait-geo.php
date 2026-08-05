<?php
/**
 * @package WP_Ulike_Pro
 */
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

	trait WP_Ulike_Pro_Stats_Trait_Geo {
		public function count_device_types( $dateRange = [], $type = 'device' ) {
			// Set the date range if provided
			if (!empty($dateRange)) {
				$this->setDateRange($dateRange);
			}

 			// Allowed columns - whitelist to prevent SQL injection
			$allowed_types = ['device', 'os', 'browser'];
			if (!in_array($type, $allowed_types, true)) {
				$type = 'device'; // fallback
			}

			// Sanitize column name for SQL
			$type = esc_sql( $type );

			// Check cache first
			$cache_key = "{$type}_types_" . md5(json_encode($this->dateRange));
			$counts = wp_cache_get($cache_key, WP_ULIKE_PRO_DOMAIN);
			if ($counts !== false) {
				return $counts;
			}

			$counts = [];
			$mode   = $this->stats_pulse_read_mode();
			$date_condition = $this->getMySqlDateRange( wp_ulike_pro_pulse_table() );
			$pulse      = $this->stats_pulse_table_esc();
			$rows_sql   = $this->stats_pulse_all_active_rows_sql();
			$actor_sql  = $this->stats_pulse_distinct_actor_sql();
			$device_grp = "CONVERT(TRIM(SUBSTRING_INDEX(`{$type}`, ' ', GREATEST(CHAR_LENGTH(`{$type}`) - CHAR_LENGTH(REPLACE(`{$type}`, ' ', '')), 1))) USING utf8mb4)";
			$parts      = array();

			// Collect actors across all content types, then COUNT(DISTINCT) once
			// so multi-type voters are not inflated.
			foreach ( $this->tables as $content_type => $table_name ) {
				if ( ! WP_Ulike_Pro_Stats_Type_Resolver::is_type_available( $content_type ) ) {
					continue;
				}

				$item_type = esc_sql( WP_Ulike_Pro_Stats_Type_Resolver::map_stats_type_to_item_type( $content_type ) );

				if ( 'pulse' === $mode || 'merged' === $mode ) {
					$since_sql = $this->stats_pulse_all_kinds_since_sql();
					$parts[]   = "
						SELECT {$device_grp} AS device_group, {$actor_sql} AS actor
						FROM `{$pulse}`
						WHERE {$date_condition}
							AND item_type = '{$item_type}'
							AND {$rows_sql}
							{$since_sql}
							AND `{$type}` != ''
							AND `{$type}` IS NOT NULL
					";
				}

				if ( 'legacy' === $mode || 'merged' === $mode ) {
					$legacy_select = $this->stats_pulse_legacy_device_actor_select( $content_type, $type, $date_condition );
					if ( $legacy_select ) {
						$parts[] = $legacy_select;
					}
				}

				if ( 'legacy' === $mode && $this->stats_pulse_table_available() ) {
					$pro_rows = $this->stats_pulse_pro_rows_sql();
					$parts[]  = "
						SELECT {$device_grp} AS device_group, {$actor_sql} AS actor
						FROM `{$pulse}`
						WHERE {$date_condition}
							AND item_type = '{$item_type}'
							AND {$pro_rows}
							AND `{$type}` != ''
							AND `{$type}` IS NOT NULL
					";
				}
			}

			if ( ! empty( $parts ) ) {
				$query = '
					SELECT device_group, COUNT(DISTINCT actor) AS device_count
					FROM ( ' . implode( ' UNION ', $parts ) . ' ) AS device_actors
					WHERE actor IS NOT NULL AND device_group IS NOT NULL AND device_group != \'\'
					GROUP BY device_group
					ORDER BY device_count DESC
				';

				$results = $this->wpdb->get_results( $query, ARRAY_A );

				foreach ( (array) $results as $row ) {
					$counts[ $row['device_group'] ] = (int) $row['device_count'];
				}
			}

			// Cache for 10 seconds
			wp_cache_set($cache_key, $counts, WP_ULIKE_PRO_DOMAIN, 10);

			return $counts;
		}

		/**
		 * Legacy device/OS/browser actor SELECT for UNION.
		 *
		 * @param string $content_type   Stats type key.
		 * @param string $type           Escaped column name (device|os|browser).
		 * @param string $date_condition SQL date fragment.
		 * @return string Empty when unavailable.
		 */
		private function stats_pulse_legacy_device_actor_select( $content_type, $type, $date_condition ) {
			if ( ! class_exists( 'WP_Ulike_Pulse_Registry' ) ) {
				return '';
			}

			$item_type = WP_Ulike_Pro_Stats_Type_Resolver::map_stats_type_to_item_type( $content_type );
			$source    = WP_Ulike_Pulse_Registry::legacy_source_for_type( $item_type );
			if ( ! $source || ! WP_Ulike_Pulse_Registry::table_exists( $source['table'] ) ) {
				return '';
			}
			if ( ! $this->legacy_geo_column_exists( $source['table'], $type ) ) {
				return '';
			}

			$table     = esc_sql( $source['table'] );
			$device_grp = "CONVERT(TRIM(SUBSTRING_INDEX(`{$type}`, ' ', GREATEST(CHAR_LENGTH(`{$type}`) - CHAR_LENGTH(REPLACE(`{$type}`, ' ', '')), 1))) USING utf8mb4)";

			if ( $this->legacy_geo_column_exists( $source['table'], 'fingerprint' ) ) {
				$actor_sql = $this->stats_pulse_distinct_actor_sql();
			} else {
				// CONVERT: keep this arm's collation aligned with the pulse arm (see
				// stats_pulse_distinct_actor_sql()).
				$actor_sql = "CONVERT(CASE
					WHEN user_id IS NOT NULL AND CAST(user_id AS CHAR) NOT IN ('', '0') THEN CONCAT('u:', user_id)
					ELSE NULL
				END USING utf8mb4)";
			}

			return "
				SELECT {$device_grp} AS device_group, {$actor_sql} AS actor
				FROM `{$table}`
				WHERE {$date_condition}
					AND status IN ('like','dislike')
					AND `{$type}` != ''
					AND `{$type}` IS NOT NULL
			";
		}

		public function count_country_codes( $dateRange = [], $selected_status = [], $types = [], $filters = array() ) {
			// Set the date range if provided
			if (!empty($dateRange)) {
				$this->setDateRange($dateRange);
			}

			// Generate a unique cache key based on the date range
			$cache_key = 'country_counts_' . md5(json_encode([
				'dateRange' => $this->dateRange,
				'status'    => $selected_status,
				'types'     => $types,
				'filters'   => $filters,
			]));
			$country_counts = wp_cache_get($cache_key, WP_ULIKE_PRO_DOMAIN);
			if (false !== $country_counts) {
				$decoded = json_decode($country_counts, true);
				// Calculate growth if not already included
				if (!empty($decoded)) {
					$first_key = key($decoded);
					if ($first_key && !isset($decoded[$first_key]['growth'])) {
						$decoded = $this->add_country_growth($decoded, $dateRange, $selected_status, $types, $filters);
					}
				}
				return $decoded;
			}

			// Reaction/rating filters are engagement-scoped — ignore vote status.
			if ( ! empty( $filters['engagement_keys'] ) || ! empty( $filters['values'] ) ) {
				$selected_status = array();
			}

			$date_condition = $this->getMySqlDateRange( wp_ulike_pro_pulse_table() );
			$parts          = array();

			foreach ( $this->tables as $content_type => $table_name ) {
				if ( ! empty( $types ) && ! in_array( $content_type, $types, true ) ) {
					continue;
				}

				if ( ! WP_Ulike_Pro_Stats_Type_Resolver::is_type_available( $content_type ) ) {
					continue;
				}

				$parts = array_merge(
					$parts,
					$this->stats_pulse_country_actor_parts_for_type(
						$content_type,
						$date_condition,
						$selected_status,
						$filters
					)
				);
			}

			// One DISTINCT across all types so multi-type voters are not inflated.
			$country_counts = $this->stats_pulse_country_counts_from_parts( $parts );

			// Calculate and add growth for each country
			// Use the actual dateRange that was used (either provided or from class property)
			$actual_dateRange = ! empty( $dateRange ) ? $dateRange : ( ! empty( $this->dateRange ) ? $this->dateRange : null );
			$country_counts = $this->add_country_growth($country_counts, $actual_dateRange, $selected_status, $types, $filters);

			// Cache the result for 10 seconds
			wp_cache_set($cache_key, json_encode($country_counts), WP_ULIKE_PRO_DOMAIN, 10);

			return $country_counts;
		}

		/**
		 * Calculate growth percentage for each country (period-over-period comparison)
		 * Similar to Google Analytics active users map growth calculation
		 *
		 * @param array $current_counts Current period country counts
		 * @param array $dateRange      Current date range
		 * @param array $selected_status Selected status filter
		 * @param array $types          Selected content types
		 * @return array                Country counts with growth added
		 */
		private function add_country_growth( $current_counts, $dateRange = [], $selected_status = [], $types = [], $filters = array() ) {
			// Determine current period - use provided dateRange or current class property
			$current_period = ! empty( $dateRange ) ? $dateRange : $this->dateRange;

			// If no date range at all, use default: last 30 days
			if ( empty( $current_period ) ) {
				$end = current_time( 'Y-m-d' );
				$start = date( 'Y-m-d', strtotime( '-30 days' ) );
				$current_period = array( 'start' => $start, 'end' => $end );
			}

			// Calculate previous period of same length
			$previous_dateRange = $this->calculate_previous_period( $current_period );

			// Get previous period country counts (cached separately for performance)
			$previous_counts = $this->get_country_counts_for_period( $previous_dateRange, $selected_status, $types, $filters );

			// Calculate growth for each country
			foreach ( $current_counts as $country_code => $data ) {
				$current_total = 0;
				$previous_total = 0;

				// Get current period total (handle both 'total' key and status-based structure)
				if ( isset( $data['total'] ) ) {
					$current_total = (int) $data['total'];
				} else {
					// Sum all status counts if no 'total' key
					$current_total = array_sum( array_map( 'intval', $data ) );
				}

				// Get previous period total
				if ( isset( $previous_counts[$country_code] ) ) {
					if ( isset( $previous_counts[$country_code]['total'] ) ) {
						$previous_total = (int) $previous_counts[$country_code]['total'];
					} else {
						// Sum all status counts if no 'total' key
						$previous_total = array_sum( array_map( 'intval', $previous_counts[$country_code] ) );
					}
				}

				// Calculate growth percentage: ((current - previous) / previous) * 100
				$growth = 0;
				if ( $previous_total > 0 ) {
					$growth = ( ( $current_total - $previous_total ) / $previous_total ) * 100;
				} elseif ( $current_total > 0 && $previous_total == 0 ) {
					// If previous period had no data but current does, it's 100% growth
					$growth = 100;
				}

				// Add growth to the country data
				$current_counts[$country_code]['growth'] = round( $growth, 2 );
			}

			return $current_counts;
		}

		/**
		 * Get country counts for a specific date range (used for previous period calculation)
		 * Optimized with caching to avoid duplicate queries
		 *
		 * @param array $dateRange      Date range array with 'start' and 'end'
		 * @param array $selected_status Selected status filter
		 * @param array $types          Selected content types
		 * @return array                Country counts for the period
		 */
		private function get_country_counts_for_period( $dateRange, $selected_status = [], $types = [], $filters = array() ) {
			// Cache key for previous period
			$cache_key = 'country_counts_prev_' . md5( json_encode( [
				'dateRange' => $dateRange,
				'status'    => $selected_status,
				'types'     => $types,
				'filters'   => $filters,
			] ) );

			$cached = wp_cache_get( $cache_key, WP_ULIKE_PRO_DOMAIN );
			if ( false !== $cached ) {
				return json_decode( $cached, true );
			}

			// Store current date range to restore later
			$original_dateRange = $this->dateRange;

			// Set the previous period date range
			$this->setDateRange( $dateRange );

			if ( ! empty( $filters['engagement_keys'] ) || ! empty( $filters['values'] ) ) {
				$selected_status = array();
			}

			$date_condition = $this->getMySqlDateRange( wp_ulike_pro_pulse_table() );
			$parts          = array();

			foreach ( $this->tables as $content_type => $table_name ) {
				if ( ! empty( $types ) && ! in_array( $content_type, $types, true ) ) {
					continue;
				}

				if ( ! WP_Ulike_Pro_Stats_Type_Resolver::is_type_available( $content_type ) ) {
					continue;
				}

				$parts = array_merge(
					$parts,
					$this->stats_pulse_country_actor_parts_for_type(
						$content_type,
						$date_condition,
						$selected_status,
						$filters
					)
				);
			}

			$country_counts = $this->stats_pulse_country_counts_from_parts( $parts );

			// Restore original date range
			$this->dateRange = $original_dateRange;

			// Cache the result
			wp_cache_set( $cache_key, json_encode( $country_counts ), WP_ULIKE_PRO_DOMAIN, 10 );

			return $country_counts;
		}

		/**
		 * Calculate previous period date range based on current date range
		 * Simple and optimized: calculates previous period of same length
		 *
		 * @param array $current_period Current date range array with 'start' and 'end'
		 * @return array                Previous period date range
		 */
		private function calculate_previous_period( $current_period ) {
			if ( ! isset( $current_period['start'] ) || ! isset( $current_period['end'] ) ) {
				// Default: last 30 days vs previous 30 days
				$end = current_time( 'Y-m-d' );
				$start = date( 'Y-m-d', strtotime( '-30 days' ) );
				$current_period = array( 'start' => $start, 'end' => $end );
			}

			$start = strtotime( $current_period['start'] );
			$end = strtotime( $current_period['end'] );

			// Calculate number of days in current period
			$days_diff = ( $end - $start ) / DAY_IN_SECONDS;

			// Previous period ends 1 day before current period starts
			$prev_end = date( 'Y-m-d', strtotime( $current_period['start'] . ' -1 day' ) );
			// Previous period starts (days_diff) days before prev_end
			$prev_start = date( 'Y-m-d', strtotime( $prev_end . ' -' . $days_diff . ' days' ) );

			return array(
				'start' => $prev_start,
				'end'   => $prev_end
			);
		}


		/**
		 * Calculate engagement rate and views for an item
		 * Smart calculation: Only counts likes/dislikes from the period where views exist
		 * This ensures accurate, logical engagement rates for marketing purposes
		 *
		 * @param int    $item_id      Item ID
		 * @param string $type          Content type (post, comment, activity, topic)
		 * @param int    $like_count    Like count (all-time, will be filtered if needed)
		 * @param int    $dislike_count Dislike count (all-time, will be filtered if needed)
		 * @param mixed  $period        Period setting (array with start/end or string)
		 * @return array                Array with 'total_views' and 'engagement_rate'
		 */
}

