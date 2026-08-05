<?php
/**
 * Engagement-table reads for the stats panel (emoji / star ratings).
 *
 * @package WP_Ulike_Pro
 */

// no direct access allowed
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'WP_Ulike_Pro_Stats_Engagement_Reader' ) ) {

	/**
	 * Centralizes ulike_pulse emoji/star queries used by WP_Ulike_Pro_Stats_V2.
	 */
	class WP_Ulike_Pro_Stats_Engagement_Reader {

		/**
		 * @var self|null
		 */
		protected static $instance = null;

		/**
		 * @var wpdb
		 */
		private $wpdb;

		/**
		 * Constructor.
		 */
		private function __construct() {
			global $wpdb;
			$this->wpdb = $wpdb;
		}

		/**
		 * Singleton accessor.
		 *
		 * @return self
		 */
		public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Active engagement kind for an item type (emoji|star), or null when classic likes apply.
		 *
		 * @param string $item_type post|comment|activity|topic.
		 * @return string|null
		 */
		public function resolve_kind( $item_type ) {
			if ( ! function_exists( 'wp_ulike_pro_get_engagement_mode_for_type' ) ) {
				return null;
			}

			$mode = wp_ulike_pro_get_engagement_mode_for_type( $item_type );

			return in_array( $mode, array( 'emoji', 'star' ), true ) ? $mode : null;
		}

		/**
		 * Build AND clauses for engagement_key (emoji) / value (star) filters.
		 *
		 * @param array  $filters {engagement_keys?: string[], values?: int[]}
		 * @param string $alias   Optional table alias including trailing dot.
		 * @return string
		 */
		private function build_filter_sql( $filters = array(), $alias = '' ) {
			$sql    = '';
			$keys   = ! empty( $filters['engagement_keys'] )
				? array_values( array_filter( array_map( 'strval', (array) $filters['engagement_keys'] ) ) )
				: array();
			$values = ! empty( $filters['values'] )
				? array_values( array_filter( array_map( 'absint', (array) $filters['values'] ) ) )
				: array();

			if ( $keys ) {
				$placeholders = implode( ',', array_fill( 0, count( $keys ), '%s' ) );
				$sql         .= $this->wpdb->prepare(
					" AND {$alias}engagement_key IN ({$placeholders})",
					...$keys
				);
			}

			if ( $values ) {
				$placeholders = implode( ',', array_fill( 0, count( $values ), '%d' ) );
				$sql         .= $this->wpdb->prepare(
					" AND {$alias}value IN ({$placeholders})",
					...$values
				);
			}

			return $sql;
		}

		/**
		 * Count active engagement rows for a period.
		 *
		 * @param string      $item_type post|comment|activity|topic.
		 * @param string      $period    Period key or {start,end} range.
		 * @param string|null $kind      Optional engagement_kind filter.
		 * @param array       $filters   {engagement_keys?: string[], values?: int[]}.
		 * @return int
		 */
	public function count_logs( $item_type, $period = 'all', $kind = null, $filters = array() ) {
		$table = wp_ulike_pro_engagement_table();
		if ( empty( $table ) ) {
			return 0;
		}

		if ( null === $kind ) {
			$kind = $this->resolve_kind( $item_type );
		}

		$logical_key = sprintf(
			'eng_count_%s_%s_%s_%s',
			$item_type,
			$kind ? $kind : 'any',
			is_array( $period ) ? wp_json_encode( $period ) : $period,
			wp_json_encode( $filters )
		);

		return (int) WP_Ulike_Query_Cache::remember_stats(
			$logical_key,
			function () use ( $table, $item_type, $kind, $filters, $period ) {
				$sql = $this->wpdb->prepare(
					"SELECT COUNT(*) FROM `{$table}` WHERE item_type = %s AND status = %s",
					$item_type,
					'active'
				);

				if ( $kind ) {
					$sql .= $this->wpdb->prepare( ' AND engagement_kind = %s', $kind );
				}

				$sql .= $this->build_filter_sql( $filters );

				$period_sql = wp_ulike_get_period_limit_sql( $period );
				if ( ! empty( $period_sql ) ) {
					$sql .= $period_sql;
				}

				return (int) $this->wpdb->get_var( $sql );
			}
		);
	}

		/**
		 * Daily engagement chart data.
		 *
		 * @param string      $item_type post|comment|activity|topic.
		 * @param string      $period    Period key or {start,end} range.
		 * @param string|null $kind      Optional engagement_kind filter.
		 * @param array       $filters   {engagement_keys?: string[], values?: int[]}.
		 * @return array<int,array{date:string,total:int}>
		 */
		public function get_dataset( $item_type, $period = 'month', $kind = null, $filters = array() ) {
			$table = wp_ulike_pro_engagement_table();
			if ( empty( $table ) ) {
				return array();
			}

			if ( null === $kind ) {
				$kind = $this->resolve_kind( $item_type );
			}

			$where = $this->wpdb->prepare(
				'item_type = %s AND status = %s',
				$item_type,
				'active'
			);

			if ( $kind ) {
				$where .= $this->wpdb->prepare( ' AND engagement_kind = %s', $kind );
			}

			$where .= $this->build_filter_sql( $filters );

			$period_sql = wp_ulike_get_period_limit_sql( $period );
			if ( ! empty( $period_sql ) ) {
				$where .= ' ' . $period_sql;
			}

			$results = $this->wpdb->get_results(
				"SELECT DATE(date_time) AS date, COUNT(*) AS total
				FROM `{$table}`
				WHERE {$where}
				GROUP BY DATE(date_time)
				ORDER BY date ASC",
				ARRAY_A
			);

			$dataset = array();
			foreach ( (array) $results as $row ) {
				$dataset[] = array(
					'date'  => $row['date'],
					'total' => (int) $row['total'],
				);
			}

			return $dataset;
		}

		/**
		 * Daily chart data broken down by reaction slug (emoji) or rating value (star).
		 *
		 * Returns one row per date with a column per selected key, mirroring the
		 * classic status multi-series chart contract.
		 *
		 * @param string $item_type  post|comment|activity|topic.
		 * @param mixed  $date_range Period key or {start,end} range.
		 * @param string $kind       emoji|star.
		 * @param array  $filters    {engagement_keys?: string[], values?: int[]}.
		 * @return array<int,array<string,mixed>>
		 */
		public function get_dataset_breakdown( $item_type, $date_range, $kind, $filters = array() ) {
			$table = wp_ulike_pro_engagement_table();
			if ( empty( $table ) || ! in_array( $kind, array( 'emoji', 'star' ), true ) ) {
				return array();
			}

			$where = $this->wpdb->prepare(
				'item_type = %s AND engagement_kind = %s AND status = %s',
				$item_type,
				$kind,
				'active'
			);

			$where .= $this->build_filter_sql( $filters );

			$period_sql = wp_ulike_get_period_limit_sql( $date_range );
			if ( ! empty( $period_sql ) ) {
				$where .= ' ' . $period_sql;
			}

			$key_col = 'star' === $kind ? 'CAST(value AS UNSIGNED)' : 'engagement_key';

			$results = $this->wpdb->get_results(
				"SELECT DATE(date_time) AS date, {$key_col} AS k, COUNT(*) AS total
				FROM `{$table}`
				WHERE {$where}
				GROUP BY date, k
				ORDER BY date ASC",
				ARRAY_A
			);

			$grouped = array();
			foreach ( (array) $results as $row ) {
				$date = $row['date'];
				$key  = (string) $row['k'];
				if ( '' === $key ) {
					continue;
				}
				if ( ! isset( $grouped[ $date ] ) ) {
					$grouped[ $date ] = array( 'date' => $date );
				}
				$grouped[ $date ][ $key ] = (int) $row['total'];
			}

			$selected = 'star' === $kind
				? array_map( 'strval', array_filter( array_map( 'absint', (array) ( $filters['values'] ?? array() ) ) ) )
				: array_filter( array_map( 'strval', (array) ( $filters['engagement_keys'] ?? array() ) ) );

			$output = array_values( $grouped );
			if ( ! empty( $selected ) ) {
				foreach ( $output as $idx => $row ) {
					foreach ( $selected as $key ) {
						if ( ! isset( $row[ $key ] ) ) {
							$output[ $idx ][ $key ] = 0;
						}
					}
				}
			}

			return $output;
		}

		/**
		 * Site-wide star aggregates for a content type (latest rating per user per item).
		 *
		 * @param string $item_type post|comment|activity|topic.
		 * @param mixed  $period    Period key or {start,end}/{interval_*} range.
		 * @return array{count:int,sum:int,average:float}
		 */
		public function aggregate_stars_for_type( $item_type, $period = 'all' ) {
			$table = wp_ulike_pro_engagement_table();
			if ( empty( $table ) ) {
				return array(
					'count'   => 0,
					'sum'     => 0,
					'average' => 0,
				);
			}

			$cache_key = sanitize_key(
				'eng_stars_type_' . $item_type . '_' . ( is_array( $period ) ? wp_json_encode( $period ) : $period )
			);

			return WP_Ulike_Query_Cache::remember_stats(
				$cache_key,
				function () use ( $table, $item_type, $period ) {
					$period_sql = wp_ulike_get_period_limit_sql( $period );

					$row = $this->wpdb->get_row(
						$this->wpdb->prepare(
							"SELECT COUNT(*) AS rating_count, COALESCE(SUM(latest.value), 0) AS rating_sum
							FROM (
								SELECT t.value
								FROM `{$table}` t
								INNER JOIN (
									SELECT user_id, item_id, MAX(id) AS max_id
									FROM `{$table}`
									WHERE item_type = %s AND engagement_kind = %s AND status = %s{$period_sql}
									GROUP BY user_id, item_id
								) latest_ids ON t.id = latest_ids.max_id
							) AS latest",
							$item_type,
							'star',
							'active'
						),
						ARRAY_A
					);

					$count = isset( $row['rating_count'] ) ? (int) $row['rating_count'] : 0;
					$sum   = isset( $row['rating_sum'] ) ? (int) $row['rating_sum'] : 0;

					return array(
						'count'   => $count,
						'sum'     => $sum,
						'average' => $count > 0 ? round( $sum / $count, 1 ) : 0,
					);
				}
			);
		}

		/**
		 * Active engagement counts grouped by engagement_key (emoji) or value (star).
		 *
		 * @param string $item_type post|comment|activity|topic.
		 * @param mixed  $period    Period filter.
		 * @param string $kind      emoji|star.
		 * @return array<string|int,int> Map of key/value => count.
		 */
		public function count_logs_grouped( $item_type, $period = 'all', $kind = 'emoji' ) {
			$table = wp_ulike_pro_engagement_table();
			if ( empty( $table ) || ! in_array( $kind, array( 'emoji', 'star' ), true ) ) {
				return array();
			}

			$cache_key = sanitize_key(
				sprintf(
					'eng_grouped_%s_%s_%s',
					$item_type,
					$kind,
					is_array( $period ) ? wp_json_encode( $period ) : $period
				)
			);

			return WP_Ulike_Query_Cache::remember_stats(
				$cache_key,
				function () use ( $table, $item_type, $period, $kind ) {
					$group_col  = 'star' === $kind ? 'CAST(value AS UNSIGNED)' : 'engagement_key';
					$period_sql = wp_ulike_get_period_limit_sql( $period );

					$rows = $this->wpdb->get_results(
						$this->wpdb->prepare(
							"SELECT {$group_col} AS group_key, COUNT(*) AS total
							FROM `{$table}`
							WHERE item_type = %s AND engagement_kind = %s AND status = %s{$period_sql}
							GROUP BY group_key",
							$item_type,
							$kind,
							'active'
						),
						ARRAY_A
					);

					$grouped = array();
					foreach ( (array) $rows as $row ) {
						$key = 'star' === $kind
							? (int) $row['group_key']
							: (string) $row['group_key'];
						$grouped[ $key ] = (int) $row['total'];
					}

					return $grouped;
				}
			);
		}

		/**
		 * Emoji reaction breakdown for a content type.
		 *
		 * @param string $item_type post|comment|activity|topic.
		 * @return array<int,array{engagement_kind:string,engagement_key:string,total:int}>
		 */
		public function get_emoji_breakdown( $item_type ) {
			$table = wp_ulike_pro_engagement_table();
			if ( empty( $table ) ) {
				return array();
			}

			$actor_sql       = function_exists( 'wp_ulike_pro_engagement_distinct_actor_sql' )
				? wp_ulike_pro_engagement_distinct_actor_sql( '' )
				: 'user_id';
			$emoji_count_sql = class_exists( 'wp_ulike_setting_repo' ) && wp_ulike_setting_repo::isDistinct( $item_type )
				? "COUNT(DISTINCT {$actor_sql})"
				: 'COUNT(*)';

			$rows = $this->wpdb->get_results(
				$this->wpdb->prepare(
					"SELECT engagement_key, {$emoji_count_sql} AS total
					FROM `{$table}`
					WHERE item_type = %s AND engagement_kind = %s AND status = %s
					GROUP BY engagement_key
					ORDER BY total DESC",
					$item_type,
					'emoji',
					'active'
				),
				ARRAY_A
			);

			$breakdown = array();
			foreach ( (array) $rows as $row ) {
				$breakdown[] = array(
					'engagement_kind' => 'emoji',
					'engagement_key'  => (string) $row['engagement_key'],
					'total'           => (int) $row['total'],
				);
			}

			return $breakdown;
		}

		/**
		 * Distinct registered members for an engagement item type.
		 *
		 * @param string      $item_type post|comment|activity|topic.
		 * @param mixed       $date      Period filter.
		 * @param string|null $kind      Optional engagement_kind filter.
		 * @return int
		 */
		public function count_unique_members( $item_type, $date = 'week', $kind = null ) {
			$table = wp_ulike_pro_engagement_table();
			if ( empty( $table ) ) {
				return 0;
			}

			if ( null === $kind ) {
				$kind = $this->resolve_kind( $item_type );
			}

		$cache_key = sanitize_key(
			sprintf(
				'eng_unique_members_%s_%s_%s',
				$item_type,
				$kind ? $kind : 'any',
				is_array( $date ) ? wp_json_encode( $date ) : $date
			)
		);

		return (int) WP_Ulike_Query_Cache::remember_stats(
			$cache_key,
			function () use ( $table, $item_type, $kind, $date ) {
				$table_esc = esc_sql( $table );
				$query     = $this->wpdb->prepare(
					"SELECT COUNT(DISTINCT CAST(v.user_id AS UNSIGNED))
					FROM `{$table_esc}` v
					INNER JOIN {$this->wpdb->users} u ON u.ID = CAST(v.user_id AS UNSIGNED)
					WHERE v.item_type = %s AND v.status = 'active'
					AND v.user_id > 0 AND v.user_id NOT LIKE 't\_%%'",
					$item_type
				);

				if ( $kind ) {
					$query .= $this->wpdb->prepare( ' AND v.engagement_kind = %s', $kind );
				}

				$query .= wp_ulike_get_period_limit_sql( $date );

				return absint( $this->wpdb->get_var( $query ) );
			}
		);
		}

		/**
		 * @param string      $item_type post|comment|activity|topic.
		 * @param mixed       $date      Period filter.
		 * @param string|null $kind      Optional engagement_kind filter.
		 * @return int
		 */
		public function count_unique_voters( $item_type, $date = 'week', $kind = null ) {
			$table = wp_ulike_pro_engagement_table();
			if ( empty( $table ) ) {
				return 0;
			}

			if ( null === $kind ) {
				$kind = $this->resolve_kind( $item_type );
			}

		$cache_key = sanitize_key(
			sprintf(
				'eng_unique_voters_%s_%s_%s',
				$item_type,
				$kind ? $kind : 'any',
				is_array( $date ) ? wp_json_encode( $date ) : $date
			)
		);

		return (int) WP_Ulike_Query_Cache::remember_stats(
			$cache_key,
			function () use ( $table, $item_type, $kind, $date ) {
				$table_esc = esc_sql( $table );
				$actor_sql = function_exists( 'wp_ulike_pro_engagement_distinct_actor_sql' )
					? wp_ulike_pro_engagement_distinct_actor_sql( 'v' )
					: 'v.user_id';
				$query     = $this->wpdb->prepare(
					"SELECT COUNT(DISTINCT {$actor_sql})
					FROM `{$table_esc}` v
					WHERE v.item_type = %s AND v.status = 'active'",
					$item_type
				);

				if ( $kind ) {
					$query .= $this->wpdb->prepare( ' AND v.engagement_kind = %s', $kind );
				}

				$query .= wp_ulike_get_period_limit_sql( $date );

				return absint( $this->wpdb->get_var( $query ) );
			}
		);
	}
}
}

