<?php
/**
 * Content-type routing for stats (pulse votes vs emoji/star on ulike_pulse).
 *
 * @package WP_Ulike_Pro
 */

// no direct access allowed
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'WP_Ulike_Pro_Stats_Type_Resolver' ) ) {

	/**
	 * Maps stats panel keys to item types and engagement modes.
	 */
	final class WP_Ulike_Pro_Stats_Type_Resolver {

		/** @var array<string,string> */
		private static $content_type_map = array(
			'posts'      => 'post',
			'comments'   => 'comment',
			'activities' => 'activity',
			'topics'     => 'topic',
		);

		/** @var array<string,string> */
		private static $tables = array(
			'posts'      => 'ulike',
			'comments'   => 'ulike_comments',
			'activities' => 'ulike_activities',
			'topics'     => 'ulike_forums',
		);

		/**
		 * Stats table map.
		 *
		 * @return array<string,string>
		 */
		public static function get_tables_map() {
			return self::$tables;
		}

		/**
		 * Map stats content type key to WP ULike item type.
		 *
		 * @param string $type_key posts|comments|activities|topics|post|comment|...
		 * @return string
		 */
		public static function map_stats_type_to_item_type( $type_key ) {
			if ( isset( self::$content_type_map[ $type_key ] ) ) {
				return self::$content_type_map[ $type_key ];
			}

			return $type_key;
		}

		/**
		 * Map stats content type key to item type (public alias for meta/bootstrap).
		 *
		 * @param string $type posts|comments|activities|topics.
		 * @return string
		 */
		public static function map_content_type_public( $type ) {
			return self::map_stats_type_to_item_type( $type );
		}

		/**
		 * Reverse map: log table slug, item type, or stats key → stats type key.
		 *
		 * @param string $table ulike|ulike_comments|... or wp_ulike_comments|... or post|comment|... or posts|comments|...
		 * @return string|null
		 */
		public static function table_to_stats_type( $table ) {
			$table = (string) $table;

			// Pulse_Registry sources use prefixed table names (wp_ulike_comments).
			// Strip the prefix so bare suffixes (ulike_comments) still match.
			global $wpdb;
			if ( ! empty( $wpdb->prefix ) && 0 === strpos( $table, $wpdb->prefix ) ) {
				$table = substr( $table, strlen( $wpdb->prefix ) );
			}

			$table = sanitize_key( $table );

			$found = array_search( $table, self::$tables, true );
			if ( false !== $found ) {
				return (string) $found;
			}

			if ( isset( self::$content_type_map[ $table ] ) ) {
				return $table;
			}

			$found = array_search( $table, self::$content_type_map, true );
			return false !== $found ? (string) $found : null;
		}

		/**
		 * Current engagement mode for a stats content type.
		 *
		 * @param string $type_key posts|comments|activities|topics.
		 * @return string none|emoji|star
		 */
		public static function get_engagement_mode_for_stats_type( $type_key ) {
			if ( ! function_exists( 'wp_ulike_pro_get_engagement_mode_for_type' ) ) {
				return 'none';
			}

			return wp_ulike_pro_get_engagement_mode_for_type(
				self::map_stats_type_to_item_type( $type_key )
			);
		}

		/**
		 * Whether a stats type reads emoji/star rows from ulike_pulse.
		 *
		 * @param string $type_key posts|comments|activities|topics.
		 * @return bool
		 */
		public static function stats_type_uses_engagement_table( $type_key ) {
			return in_array(
				self::get_engagement_mode_for_stats_type( $type_key ),
				array( 'emoji', 'star' ),
				true
			);
		}

		/**
		 * Skip type when plugin dependency missing.
		 *
		 * @param string $type_key posts|comments|activities|topics.
		 * @return bool
		 */
		public static function is_type_available( $type_key ) {
			if ( 'topics' === $type_key && ! function_exists( 'is_bbpress' ) ) {
				return false;
			}

			if ( 'activities' === $type_key && ! defined( 'BP_VERSION' ) ) {
				return false;
			}

			return true;
		}
	}
}

