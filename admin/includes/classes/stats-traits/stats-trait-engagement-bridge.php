<?php
/**
 * @package WP_Ulike_Pro
 */
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

trait WP_Ulike_Pro_Stats_Trait_Engagement_Bridge {
		public function get_engagement_api_data( $type ) {
			$tables = $this->get_tables();

			if ( ! isset( $tables[ $type ] ) ) {
				return null;
			}

			$item_type = WP_Ulike_Pro_Stats_Meta::map_content_type_public( $type );
			$mode      = class_exists( 'WP_Ulike_Pro_Engagement_Settings' )
				? WP_Ulike_Pro_Engagement_Settings::get_mode( $item_type )
				: 'none';

			if ( in_array( $mode, array( 'emoji', 'star' ), true ) ) {
				return $this->get_pro_engagement_trends_api_data( $type, $item_type, $mode );
			}

			$table = $tables[ $type ];

			$week      = (int) $this->count_logs( array( 'table' => $table, 'date' => 'week' ) );
			$last_week = (int) $this->count_logs( array( 'table' => $table, 'date' => 'last_week' ) );

			return array(
				'type'    => $type,
				'metrics' => array(
					'week'       => $week,
					'last_week'  => $last_week,
					'month'      => $this->count_logs( array( 'table' => $table, 'date' => 'month' ) ),
					'last_month' => $this->count_logs( array( 'table' => $table, 'date' => 'last_month' ) ),
					'year'       => $this->count_logs( array( 'table' => $table, 'date' => 'year' ) ),
					'last_year'  => $this->count_logs( array( 'table' => $table, 'date' => 'last_year' ) ),
					'all'        => $this->count_logs( array( 'table' => $table, 'date' => 'all' ) ),
				),
				'summary' => $this->get_type_engagement_summary( $table ),
				'chart'   => $this->get_dataset( $table ),
			);
		}

		/**
		 * Marketing summary for a single content-type engagement report.
		 *
		 * @param string $table Log table name.
		 * @return array
		 */
		private function get_type_engagement_summary( $table ) {
			$type_key         = array_search( $table, $this->tables, true );
			$dislikes_enabled = is_string( $type_key )
				? WP_Ulike_Pro_Stats_Meta::content_type_supports_dislikes( $type_key )
				: false;

			$week_likes    = $this->count_status_logs( $table, 'like', 'week' );
			$week_dislikes = $dislikes_enabled ? $this->count_status_logs( $table, 'dislike', 'week' ) : 0;
			$reactions     = $week_likes + $week_dislikes;
			$like_ratio    = $reactions > 0 ? round( ( $week_likes / $reactions ) * 100, 1 ) : ( $week_likes > 0 ? 100 : 0 );

			$unique_voters   = (int) $this->count_total_interactions( array( 'table' => $table, 'date' => 'week' ) );
			$unique_engagers = (int) $this->count_unique_members_table( $table, 'week' );

			return array(
				'week_likes'        => $week_likes,
				'week_dislikes'     => $week_dislikes,
				'like_ratio'        => $like_ratio,
				'unique_engagers'   => $unique_engagers,
				'unique_voters'     => $unique_voters,
				'dislikes_enabled'  => $dislikes_enabled,
				'week_growth'       => $this->calculate_growth_percent(
					(int) $this->count_logs( array( 'table' => $table, 'date' => 'week' ) ),
					(int) $this->count_logs( array( 'table' => $table, 'date' => 'last_week' ) )
				),
			);
		}

		/**
		 * Marketing summary for emoji/star engagement reports.
		 *
		 * @param string $type_key  posts|comments|activities|topics.
		 * @param string $item_type post|comment|activity|topic.
		 * @param string $mode      emoji|star.
		 * @return array
		 */
		private function get_engagement_type_engagement_summary( $type_key, $item_type, $mode ) {
			$reader     = $this->engagement();
			$week_count = (int) $reader->count_logs( $item_type, 'week', $mode );
			$last_week  = (int) $reader->count_logs( $item_type, 'last_week', $mode );

			return array(
				'week_likes'       => $week_count,
				'week_dislikes'    => 0,
				'like_ratio'       => $week_count > 0 ? 100 : 0,
				'unique_engagers'  => (int) $reader->count_unique_members( $item_type, 'week', $mode ),
				'unique_voters'    => (int) $reader->count_unique_voters( $item_type, 'week', $mode ),
				'dislikes_enabled' => false,
				'week_growth'      => $this->calculate_growth_percent( $week_count, $last_week ),
				'mode'             => $mode,
			);
		}

		/**
		 * Distinct registered members for an engagement item type.
		 *
		 * @param string $item_type post|comment|activity|topic.
		 * @param mixed  $date      Period filter.
		 * @return int
		 */
		private function count_engagement_unique_members( $item_type, $date = 'week' ) {
			return $this->engagement()->count_unique_members( $item_type, $date );
		}

		/**
		 * Distinct voters (members + guests) for an engagement item type.
		 *
		 * @param string $item_type post|comment|activity|topic.
		 * @param mixed  $date      Period filter.
		 * @return int
		 */
		private function count_engagement_unique_voters( $item_type, $date = 'week' ) {
			return $this->engagement()->count_unique_voters( $item_type, $date );
		}

		/**
		 * Trends payload for emoji/star templates (replaces classic like metrics).
		 *
		 * @param string $type      posts|comments|activities|topics.
		 * @param string $item_type post|comment|activity|topic.
		 * @param string $mode      emoji|star.
		 * @return array
		 */
		private function get_pro_engagement_trends_api_data( $type, $item_type, $mode ) {
			$reader  = $this->engagement();
			$periods = array( 'week', 'last_week', 'month', 'last_month', 'year', 'last_year', 'all' );
			$metrics = array();

			foreach ( $periods as $period ) {
				$metrics[ $period ] = (int) $reader->count_logs( $item_type, $period, $mode );
			}

			$chart = array_map(
				static function ( $row ) {
					return array(
						'date'  => $row['date'],
						'total' => (int) $row['total'],
					);
				},
				$reader->get_dataset( $item_type, 'month', $mode )
			);

			return array(
				'type'    => $type,
				'mode'    => $mode,
				'metrics' => $metrics,
				'summary' => $this->get_engagement_type_engagement_summary( $type, $item_type, $mode ),
				'chart'   => $chart,
			);
		}

		/**
		 * Count engagement rows for a period.
		 *
		 * @param string $item_type post|comment|activity|topic.
		 * @param string $period    week|last_week|month|all.
		 * @return int
		 */
		private function count_engagement_logs( $item_type, $period = 'all' ) {
			return $this->engagement()->count_logs( $item_type, $period );
		}

		/**
		 * Daily engagement chart data.
		 *
		 * @param string $item_type Content type slug.
		 * @param string $range     SQL interval phrase.
		 * @return array
		 */
		private function get_engagement_dataset( $item_type, $period = 'month' ) {
			return $this->engagement()->get_dataset( $item_type, $period );
		}

		private function engagement() {
			if ( null === $this->engagement_reader ) {
				$this->engagement_reader = WP_Ulike_Pro_Stats_Engagement_Reader::get_instance();
			}

			return $this->engagement_reader;
		}

		/**
		 * Mode-aware log count for a stats content type.
		 *
		 * @param string $type_key posts|comments|activities|topics.
		 * @param mixed  $date     Period filter.
		 * @return int
		 */
	private function count_logs_for_stats_type( $type_key, $date = 'all' ) {
		$item_type = WP_Ulike_Pro_Stats_Type_Resolver::map_stats_type_to_item_type( $type_key );

		// Classic like/dislike votes: legacy tables + pulse kind=vote rows
		// (mode-aware via the free Pulse_Query). Vote-only so the emoji/star
		// rows counted separately below are not double-counted —
		// count_logs_for_type() includes all kinds and is used by the free
		// "all logs" path instead.
		$total = (int) WP_Ulike_Pulse_Query::count_vote_logs_for_type( $item_type, $date );

		// Emoji + star engagement rows from pulse (active only). Counted
		// regardless of the type's current template mode so display-automation
		// renders and historical engagement data are always reflected.
		$total += (int) $this->engagement()->count_logs( $item_type, $date, 'emoji' );
		$total += (int) $this->engagement()->count_logs( $item_type, $date, 'star' );

		return $total;
	}

		/**
		 * Whether a stats type has any logged activity in its active data source.
		 *
		 * @param string $type_key posts|comments|activities|topics.
		 * @return bool
		 */
	private function stats_type_has_activity( $type_key ) {
		if ( ! isset( $this->tables[ $type_key ] ) ) {
			return false;
		}

		$item_type = WP_Ulike_Pro_Stats_Type_Resolver::map_stats_type_to_item_type( $type_key );

		// A type has activity when ANY interaction exists: classic votes
		// (legacy + pulse kind=vote), emoji rows, or star rows. Checked
		// regardless of the type's current template mode so a type is never
		// hidden after switching templates or when using display automation.
		$has = WP_Ulike_Pulse_Query::count_logs_for_type( $item_type, 'all' ) > 0
			|| $this->engagement()->count_logs( $item_type, 'all', 'emoji' ) > 0
			|| $this->engagement()->count_logs( $item_type, 'all', 'star' ) > 0;

		return $has;
	}
}

