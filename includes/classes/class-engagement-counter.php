<?php
/**
 * Engagement counter cache (ulike_meta).
 *
 * @package WP ULike Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WP_Ulike_Pro_Engagement_Counter {

	const META_PREFIX = 'eng_';

	/**
	 * Build counter meta key for emoji reaction.
	 *
	 * @param string $reaction_slug Reaction slug.
	 * @return string
	 */
	public static function reaction_meta_key( $reaction_slug ) {
		return self::META_PREFIX . 'count_' . sanitize_key( $reaction_slug );
	}

	/**
	 * Star aggregate meta keys.
	 *
	 * @return array{sum:string,count:string,average:string}
	 */
	public static function star_meta_keys() {
		return array(
			'sum'     => self::META_PREFIX . 'rating_sum',
			'count'   => self::META_PREFIX . 'rating_count',
			'average' => self::META_PREFIX . 'rating_average',
		);
	}

	/**
	 * Get cached reaction count.
	 *
	 * @param int    $item_id       Item ID.
	 * @param string $item_type     Content type.
	 * @param string $reaction_slug Reaction slug.
	 * @return int
	 */
	public static function get_reaction_count( $item_id, $item_type, $reaction_slug ) {
		$key   = self::reaction_meta_key( $reaction_slug );
		$value = wp_ulike_get_meta_data( $item_id, $item_type, $key, true );

		if ( $value === '' || $value === false || $value === null ) {
			$value = self::count_reaction_from_db( $item_id, $item_type, $reaction_slug );
			wp_ulike_update_meta_data( $item_id, $item_type, $key, (int) $value );
		}

		return max( 0, (int) $value );
	}

	/**
	 * Count active emoji reactions from DB.
	 *
	 * @param int    $item_id       Item ID.
	 * @param string $item_type     Content type.
	 * @param string $reaction_slug Reaction slug.
	 * @return int
	 */
	public static function count_reaction_from_db( $item_id, $item_type, $reaction_slug ) {
		global $wpdb;

		$table = wp_ulike_pro_engagement_table();
		if ( empty( $table ) ) {
			return 0;
		}

		$actor_sql  = function_exists( 'wp_ulike_pro_engagement_distinct_actor_sql' )
			? wp_ulike_pro_engagement_distinct_actor_sql( '' )
			: 'user_id';
		$count_expr = class_exists( 'wp_ulike_setting_repo' ) && wp_ulike_setting_repo::isDistinct( $item_type )
			? "COUNT(DISTINCT {$actor_sql})"
			: 'COUNT(*)';

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT {$count_expr} FROM `{$table}`
				WHERE item_id = %d AND item_type = %s AND engagement_kind = %s
				AND engagement_key = %s AND status = %s",
				$item_id,
				$item_type,
				'emoji',
				$reaction_slug,
				'active'
			)
		);
	}

	/**
	 * Increment or decrement reaction counter.
	 *
	 * @param int    $item_id       Item ID.
	 * @param string $item_type     Content type.
	 * @param string $reaction_slug Reaction slug.
	 * @param int    $delta         +1 or -1.
	 * @return void
	 */
	public static function bump_reaction( $item_id, $item_type, $reaction_slug, $delta ) {
		self::clear_item_cache( $item_id, $item_type );

		$key     = self::reaction_meta_key( $reaction_slug );
		$current = wp_ulike_get_meta_data( $item_id, $item_type, $key, true );

		if ( $current === '' || $current === false || $current === null ) {
			$current = self::count_reaction_from_db( $item_id, $item_type, $reaction_slug );
		}

		$next = max( 0, (int) $current + (int) $delta );
		wp_ulike_update_meta_data( $item_id, $item_type, $key, $next );
	}

	/**
	 * Get star rating aggregates.
	 *
	 * @param int    $item_id   Item ID.
	 * @param string $item_type Content type.
	 * @return array{sum:int,count:int,average:float}
	 */
	public static function get_star_aggregates( $item_id, $item_type ) {
		$keys = self::star_meta_keys();
		$sum  = wp_ulike_get_meta_data( $item_id, $item_type, $keys['sum'], true );
		$cnt  = wp_ulike_get_meta_data( $item_id, $item_type, $keys['count'], true );

		if ( $sum === '' || $sum === false || $sum === null || $cnt === '' || $cnt === false || $cnt === null ) {
			$from_db = self::aggregate_stars_from_db( $item_id, $item_type );
			$sum     = $from_db['sum'];
			$cnt     = $from_db['count'];
			wp_ulike_update_meta_data( $item_id, $item_type, $keys['sum'], $sum );
			wp_ulike_update_meta_data( $item_id, $item_type, $keys['count'], $cnt );
		}

		$sum   = max( 0, (int) $sum );
		$count = max( 0, (int) $cnt );
		$avg   = $count > 0 ? round( $sum / $count, 1 ) : 0.0;

		return array(
			'sum'     => $sum,
			'count'   => $count,
			'average' => $avg,
		);
	}

	/**
	 * Aggregate star ratings from DB.
	 *
	 * @param int    $item_id   Item ID.
	 * @param string $item_type Content type.
	 * @return array{sum:int,count:int,average:float}
	 */
	public static function aggregate_stars_from_db( $item_id, $item_type ) {
		global $wpdb;

		$table = wp_ulike_pro_engagement_table();
		if ( empty( $table ) ) {
			return array( 'sum' => 0, 'count' => 0, 'average' => 0.0 );
		}

		$actor_sql = function_exists( 'wp_ulike_pro_engagement_distinct_actor_sql' )
			? wp_ulike_pro_engagement_distinct_actor_sql( '' )
			: 'user_id';
		$row       = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT COALESCE(SUM(value), 0) AS rating_sum, COUNT(DISTINCT {$actor_sql}) AS rating_count
				FROM `{$table}`
				WHERE item_id = %d AND item_type = %s AND engagement_kind = %s AND status = %s",
				$item_id,
				$item_type,
				'star',
				'active'
			),
			ARRAY_A
		);

		$sum   = isset( $row['rating_sum'] ) ? (int) $row['rating_sum'] : 0;
		$count = isset( $row['rating_count'] ) ? (int) $row['rating_count'] : 0;

		return array(
			'sum'     => $sum,
			'count'   => $count,
			'average' => $count > 0 ? round( $sum / $count, 1 ) : 0.0,
		);
	}

	/**
	 * Update star aggregates after vote change.
	 *
	 * @param int $item_id   Item ID.
	 * @param string $item_type Content type.
	 * @param int $old_value Previous value (0 if none).
	 * @param int $new_value New value (0 if removed).
	 * @return void
	 */
	public static function update_star_aggregates( $item_id, $item_type, $old_value, $new_value ) {
		self::clear_item_cache( $item_id, $item_type );

		$keys    = self::star_meta_keys();
		$current = self::get_star_aggregates( $item_id, $item_type );

		$sum   = $current['sum'] - max( 0, (int) $old_value ) + max( 0, (int) $new_value );
		$count = $current['count'];

		if ( $old_value > 0 && $new_value <= 0 ) {
			--$count;
		} elseif ( $old_value <= 0 && $new_value > 0 ) {
			++$count;
		}

		$sum   = max( 0, $sum );
		$count = max( 0, $count );
		$avg   = $count > 0 ? round( $sum / $count, 1 ) : 0.0;

		wp_ulike_update_meta_data( $item_id, $item_type, $keys['sum'], $sum );
		wp_ulike_update_meta_data( $item_id, $item_type, $keys['count'], $count );
	}

	/**
	 * All reaction counts for an item.
	 *
	 * @param int    $item_id   Item ID.
	 * @param string $item_type Content type.
	 * @return array<string,int>
	 */
	public static function get_all_reaction_counts( $item_id, $item_type ) {
		$counts   = array();
		$reactions = WP_Ulike_Pro_Engagement_Registry::get_enabled_reactions( $item_type );

		foreach ( array_keys( $reactions ) as $slug ) {
			$counts[ $slug ] = self::get_reaction_count( $item_id, $item_type, $slug );
		}

		return apply_filters( 'wp_ulike_pro_engagement_reaction_counts', $counts, $item_id, $item_type );
	}

	/**
	 * Total active emoji reactions.
	 *
	 * @param int    $item_id   Item ID.
	 * @param string $item_type Content type.
	 * @return int
	 */
	public static function get_total_reactions( $item_id, $item_type, $counts = null ) {
		if ( is_array( $counts ) ) {
			return array_sum( $counts );
		}

		return array_sum( self::get_all_reaction_counts( $item_id, $item_type ) );
	}

	/**
	 * Rebuild cached engagement counters from DB (after admin deletes or drift).
	 *
	 * @param int    $item_id   Item ID.
	 * @param string $item_type Content type slug.
	 * @return void
	 */
	public static function rebuild_item_counters( $item_id, $item_type ) {
		$item_id   = absint( $item_id );
		$item_type = sanitize_key( $item_type );

		if ( ! $item_id || ! $item_type || ! class_exists( 'WP_Ulike_Pro_Engagement_Settings' ) ) {
			return;
		}

		self::clear_item_cache( $item_id, $item_type );

		$mode = WP_Ulike_Pro_Engagement_Settings::get_mode( $item_type );

		if ( 'emoji' === $mode ) {
			$reactions = WP_Ulike_Pro_Engagement_Registry::get_enabled_reactions( $item_type );

			foreach ( array_keys( $reactions ) as $slug ) {
				$count = self::count_reaction_from_db( $item_id, $item_type, $slug );
				wp_ulike_update_meta_data( $item_id, $item_type, self::reaction_meta_key( $slug ), $count );
			}

			return;
		}

		if ( 'star' === $mode ) {
			$from_db = self::aggregate_stars_from_db( $item_id, $item_type );
			$keys    = self::star_meta_keys();

			wp_ulike_update_meta_data( $item_id, $item_type, $keys['sum'], $from_db['sum'] );
			wp_ulike_update_meta_data( $item_id, $item_type, $keys['count'], $from_db['count'] );
		}
	}

	/**
	 * Set cached reaction count (admin bulk tools; does not create vote logs).
	 *
	 * @param int    $item_id       Item ID.
	 * @param string $item_type     Content type.
	 * @param string $reaction_slug Reaction slug.
	 * @param int    $count         Target count.
	 * @return void
	 */
	public static function set_reaction_count( $item_id, $item_type, $reaction_slug, $count ) {
		self::clear_item_cache( $item_id, $item_type );
		wp_ulike_update_meta_data(
			$item_id,
			$item_type,
			self::reaction_meta_key( $reaction_slug ),
			max( 0, (int) $count )
		);
	}

	/**
	 * Set star rating aggregates (admin bulk tools; does not create vote logs).
	 *
	 * @param int    $item_id   Item ID.
	 * @param string $item_type Content type.
	 * @param int    $count     Rating count.
	 * @param float  $average   Target average (0 when count is 0).
	 * @return void
	 */
	public static function set_star_aggregates( $item_id, $item_type, $count, $average ) {
		self::clear_item_cache( $item_id, $item_type );

		$keys  = self::star_meta_keys();
		$count = max( 0, (int) $count );
		$max   = 5;

		if ( class_exists( 'WP_Ulike_Pro_Engagement_Registry' ) ) {
			$config = WP_Ulike_Pro_Engagement_Registry::get_star_config( $item_type );
			$max    = max( 1, (int) $config['max'] );
		}

		$average = $count > 0 ? min( (float) $max, max( 0, (float) $average ) ) : 0.0;
		$sum     = (int) round( $average * $count );

		wp_ulike_update_meta_data( $item_id, $item_type, $keys['sum'], $sum );
		wp_ulike_update_meta_data( $item_id, $item_type, $keys['count'], $count );
	}

	/**
	 * Clear object cache for item engagement meta.
	 *
	 * @param int    $item_id   Item ID.
	 * @param string $item_type Content type.
	 * @return void
	 */
	public static function clear_item_cache( $item_id, $item_type ) {
		if ( wp_ulike_is_cache_exist() && $item_id ) {
			wp_cache_delete( $item_id, wp_ulike_pro_engagement_meta_cache_group( $item_type ) );
		}
	}
}

