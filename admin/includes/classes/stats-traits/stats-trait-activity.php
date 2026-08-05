<?php
/**
 * @package WP_Ulike_Pro
 */
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

trait WP_Ulike_Pro_Stats_Trait_Activity {
		public function get_activity_schedule( $args = array() ) {
			$defaults = array(
				'interval'     => '30 DAY',
				'start'        => null,
				'end'          => null,
				'summary_only' => false,
			);
			$args     = wp_parse_args( $args, $defaults );

			$cache_key = 'activity_schedule_' . md5( wp_json_encode( $args ) );
			$cached    = wp_cache_get( $cache_key, WP_ULIKE_PRO_DOMAIN );

			if ( false !== $cached ) {
				return $cached;
			}

			$where_sql = $this->build_activity_date_where( $args );
			$union_sql = $this->build_activity_union_subquery( $where_sql );

			if ( empty( $union_sql ) ) {
				return $this->empty_activity_schedule( $args['summary_only'] );
			}

			$hour_query = sprintf(
				"SELECT HOUR(date_time) AS hour_slot, COUNT(*) AS total_count
				FROM ( %s ) AS combined
				GROUP BY hour_slot
				ORDER BY hour_slot ASC",
				$union_sql
			);
			$hour_rows  = $this->wpdb->get_results( $hour_query );
			$hour_counts = array_fill( 0, 24, 0 );

			foreach ( (array) $hour_rows as $row ) {
				$slot = (int) $row->hour_slot;
				if ( $slot >= 0 && $slot <= 23 ) {
					$hour_counts[ $slot ] = absint( $row->total_count );
				}
			}

			$day_query = sprintf(
				"SELECT (DAYOFWEEK(date_time) + 5) %% 7 AS day_slot, COUNT(*) AS total_count
				FROM ( %s ) AS combined
				GROUP BY day_slot
				ORDER BY day_slot ASC",
				$union_sql
			);
			$day_rows   = $this->wpdb->get_results( $day_query );
			$day_counts = array_fill( 0, 7, 0 );
			$day_labels = $this->get_weekday_labels();

			foreach ( (array) $day_rows as $row ) {
				$slot = (int) $row->day_slot;
				if ( $slot >= 0 && $slot <= 6 ) {
					$day_counts[ $slot ] = absint( $row->total_count );
				}
			}

			$total = array_sum( $hour_counts );
			$hours = array();

			for ( $h = 0; $h < 24; $h++ ) {
				$count = $hour_counts[ $h ];
				$hours[] = array(
					'hour'  => $h,
					'label' => $this->format_hour_label( $h ),
					'count' => $count,
					'share' => $total > 0 ? round( ( $count / $total ) * 100, 1 ) : 0,
				);
			}

			usort(
				$hours,
				function( $a, $b ) {
					return ( $b['count'] ?? 0 ) <=> ( $a['count'] ?? 0 );
				}
			);
			$top_hours = array_slice( $hours, 0, 3 );
			usort(
				$hours,
				function( $a, $b ) {
					return ( $a['hour'] ?? 0 ) <=> ( $b['hour'] ?? 0 );
				}
			);

			$days = array();
			$peak_day = null;
			for ( $d = 0; $d < 7; $d++ ) {
				$count = $day_counts[ $d ];
				$day_item = array(
					'day'   => $d,
					'label' => $day_labels[ $d ],
					'count' => $count,
					'share' => $total > 0 ? round( ( $count / $total ) * 100, 1 ) : 0,
				);
				$days[] = $day_item;
				if ( ! $peak_day || $count > ( $peak_day['count'] ?? 0 ) ) {
					$peak_day = $day_item;
				}
			}

			$windows = $this->build_activity_windows( $hour_counts, $total );
			$peak_hour = null;
			foreach ( $hours as $slot ) {
				if ( ! $peak_hour || ( $slot['count'] ?? 0 ) > ( $peak_hour['count'] ?? 0 ) ) {
					$peak_hour = $slot;
				}
			}

			$heatmap = array();
			if ( ! $args['summary_only'] ) {
				$matrix_query = sprintf(
					"SELECT (DAYOFWEEK(date_time) + 5) %% 7 AS day_slot,
						HOUR(date_time) AS hour_slot,
						COUNT(*) AS total_count
					FROM ( %s ) AS combined
					GROUP BY day_slot, hour_slot",
					$union_sql
				);
				$matrix_rows = $this->wpdb->get_results( $matrix_query );
				$matrix      = array();

				for ( $d = 0; $d < 7; $d++ ) {
					$matrix[ $d ] = array_fill( 0, 24, 0 );
				}

				$matrix_max = 0;
				foreach ( (array) $matrix_rows as $row ) {
					$d = (int) $row->day_slot;
					$h = (int) $row->hour_slot;
					if ( $d >= 0 && $d <= 6 && $h >= 0 && $h <= 23 ) {
						$matrix[ $d ][ $h ] = absint( $row->total_count );
						$matrix_max         = max( $matrix_max, $matrix[ $d ][ $h ] );
					}
				}

				for ( $d = 0; $d < 7; $d++ ) {
					$cells = array();
					for ( $h = 0; $h < 24; $h++ ) {
						$cells[] = array(
							'hour'       => $h,
							'hour_label' => $this->format_hour_label( $h ),
							'count'      => $matrix[ $d ][ $h ],
							'intensity'  => $matrix_max > 0 ? round( $matrix[ $d ][ $h ] / $matrix_max, 3 ) : 0,
						);
					}
					$heatmap[] = array(
						'day'        => $d,
						'day_label'  => $day_labels[ $d ],
						'cells'      => $cells,
						'total'      => $day_counts[ $d ],
					);
				}
			}

			$peak_combo = $this->find_peak_schedule_combo( $union_sql, $day_labels );

			$data = array(
				'total'      => $total,
				'hours'      => $hours,
				'top_hours'  => $top_hours,
				'days'       => $days,
				'windows'    => $windows,
				'peak_hour'  => $peak_hour,
				'peak_day'   => $peak_day,
				'peak_combo' => $peak_combo,
				'heatmap'    => $heatmap,
			);

			wp_cache_set( $cache_key, $data, WP_ULIKE_PRO_DOMAIN, 10 );
			return $data;
		}

		/**
		 * Empty schedule structure.
		 *
		 * @param bool $summary_only Whether heatmap is omitted.
		 * @return array
		 */
		private function empty_activity_schedule( $summary_only = false ) {
			$hours = array();
			for ( $h = 0; $h < 24; $h++ ) {
				$hours[] = array(
					'hour'  => $h,
					'label' => $this->format_hour_label( $h ),
					'count' => 0,
					'share' => 0,
				);
			}

			$day_labels = $this->get_weekday_labels();
			$days       = array();
			for ( $d = 0; $d < 7; $d++ ) {
				$days[] = array(
					'day'   => $d,
					'label' => $day_labels[ $d ],
					'count' => 0,
					'share' => 0,
				);
			}

			return array(
				'total'      => 0,
				'hours'      => $hours,
				'top_hours'  => array(),
				'days'       => $days,
				'windows'    => $this->build_activity_windows( array_fill( 0, 24, 0 ), 0 ),
				'peak_hour'  => null,
				'peak_day'   => null,
				'peak_combo' => null,
				'heatmap'    => $summary_only ? array() : array(),
			);
		}

		/**
		 * Build SQL date filter for activity queries.
		 *
		 * @param array $args Date args.
		 * @return string
		 */
		private function build_activity_date_where( $args ) {
			if ( ! empty( $args['start'] ) && ! empty( $args['end'] ) ) {
				return $this->wpdb->prepare(
					' AND date_time >= %s AND date_time <= %s',
					$args['start'] . ' 00:00:00',
					$args['end'] . ' 23:59:59'
				);
			}

			$interval = ! empty( $args['interval'] ) ? $args['interval'] : '30 DAY';
			if ( ! preg_match( '/^\d+\s+(DAY|MONTH|YEAR)$/i', $interval ) ) {
				$interval = '30 DAY';
			}

			return ' AND date_time >= NOW() - INTERVAL ' . $interval;
		}

		/**
		 * Union subquery across all log tables.
		 *
		 * @param string $where_sql Date filter SQL.
		 * @return string
		 */
		private function build_activity_union_subquery( $where_sql ) {
			$union_parts = array();

			foreach ( $this->tables as $type_key => $table ) {
				$part = $this->stats_pulse_date_time_select_sql( $type_key, $where_sql );

				if ( $part ) {
					$union_parts[] = $part;
				}
			}

			return empty( $union_parts ) ? '' : implode( ' UNION ALL ', $union_parts );
		}

		/**
		 * Localized weekday labels (Mon–Sun).
		 *
		 * @return array
		 */
		private function get_weekday_labels() {
			$labels = array();
			for ( $d = 0; $d < 7; $d++ ) {
				$labels[] = wp_date( 'D', strtotime( 'Monday +' . $d . ' days' ) );
			}
			return $labels;
		}

		/**
		 * Group hours into marketer-friendly windows.
		 *
		 * @param array $hour_counts Count per hour.
		 * @param int   $total       Total engagements.
		 * @return array
		 */
		private function build_activity_windows( $hour_counts, $total ) {
			$windows_def = array(
				array(
					'key'   => 'night',
					'label' => esc_html__( 'Night', 'wp-ulike-pro' ),
					'range' => esc_html__( '12 AM – 6 AM', 'wp-ulike-pro' ),
					'hours' => range( 0, 5 ),
				),
				array(
					'key'   => 'morning',
					'label' => esc_html__( 'Morning', 'wp-ulike-pro' ),
					'range' => esc_html__( '6 AM – 12 PM', 'wp-ulike-pro' ),
					'hours' => range( 6, 11 ),
				),
				array(
					'key'   => 'afternoon',
					'label' => esc_html__( 'Afternoon', 'wp-ulike-pro' ),
					'range' => esc_html__( '12 PM – 6 PM', 'wp-ulike-pro' ),
					'hours' => range( 12, 17 ),
				),
				array(
					'key'   => 'evening',
					'label' => esc_html__( 'Evening', 'wp-ulike-pro' ),
					'range' => esc_html__( '6 PM – 12 AM', 'wp-ulike-pro' ),
					'hours' => range( 18, 23 ),
				),
			);

			$result = array();
			$best   = null;

			foreach ( $windows_def as $window ) {
				$count = 0;
				foreach ( $window['hours'] as $hour ) {
					$count += (int) ( $hour_counts[ $hour ] ?? 0 );
				}
				$item = array(
					'key'   => $window['key'],
					'label' => $window['label'],
					'range' => $window['range'],
					'count' => $count,
					'share' => $total > 0 ? round( ( $count / $total ) * 100, 1 ) : 0,
				);
				$result[] = $item;
				if ( ! $best || $count > ( $best['count'] ?? 0 ) ) {
					$best = $item;
				}
			}

			if ( $best ) {
				foreach ( $result as $index => $window ) {
					$result[ $index ]['is_peak'] = ( $window['key'] === $best['key'] );
				}
			}

			return $result;
		}

		/**
		 * Find the strongest day + hour combination.
		 *
		 * @param string $union_sql Union subquery.
		 * @param array  $day_labels Weekday labels.
		 * @return array|null
		 */
		private function find_peak_schedule_combo( $union_sql, $day_labels ) {
			$query = sprintf(
				"SELECT (DAYOFWEEK(date_time) + 5) %% 7 AS day_slot,
					HOUR(date_time) AS hour_slot,
					COUNT(*) AS total_count
				FROM ( %s ) AS combined
				GROUP BY day_slot, hour_slot
				ORDER BY total_count DESC
				LIMIT 1",
				$union_sql
			);

			$row = $this->wpdb->get_row( $query );
			if ( ! $row || (int) $row->total_count <= 0 ) {
				return null;
			}

			$day_slot  = (int) $row->day_slot;
			$hour_slot = (int) $row->hour_slot;

			return array(
				'day'        => $day_slot,
				'day_label'  => $day_labels[ $day_slot ] ?? '',
				'hour'       => $hour_slot,
				'hour_label' => $this->format_hour_label( $hour_slot ),
				'count'      => absint( $row->total_count ),
			);
		}

		/**
		 * Hour-of-day engagement distribution.
		 *
		 * @param string $interval SQL interval (e.g. '30 DAY').
		 * @return array
		 */
		public function get_peak_hours( $interval = '30 DAY' ) {
			$schedule = $this->get_activity_schedule(
				array(
					'interval'     => $interval,
					'summary_only' => true,
				)
			);

			return $schedule['hours'] ?? array();
		}

		/**
		 * Top-performing categories / taxonomies by engagement.
		 *
		 * @param int $limit Max terms to return.
		 * @return array
		 */
		private function format_hour_label( $hour ) {
			$hour = max( 0, min( 23, (int) $hour ) );
			return wp_date( 'g A', strtotime( sprintf( 'today %02d:00', $hour ) ) );
		}

		/**
		 * Primary blog taxonomy for category reports.
		 *
		 * @return string
		 */
}

