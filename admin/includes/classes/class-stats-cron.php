<?php
/**
 * Scheduled maintenance for engagement counter sync.
 *
 * @package WP_Ulike_Pro
 */

// no direct access allowed
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'WP_Ulike_Pro_Stats_Cron' ) ) {

	/**
	 * Weekly reconciliation of ulike_meta engagement counters from ulike_pulse.
	 */
	final class WP_Ulike_Pro_Stats_Cron {

		const HOOK = 'wp_ulike_pro_reconcile_engagement_counters';

		/**
		 * Register cron hook.
		 *
		 * @return void
		 */
		public static function init() {
			add_action( self::HOOK, array( __CLASS__, 'run_reconciliation' ) );
			add_action( 'init', array( __CLASS__, 'maybe_schedule' ) );
		}

		/**
		 * Schedule weekly event when engagements are available.
		 *
		 * @return void
		 */
		public static function maybe_schedule() {
			if ( ! wp_next_scheduled( self::HOOK ) ) {
				wp_schedule_event( time() + HOUR_IN_SECONDS, 'weekly', self::HOOK );
			}
		}

		/**
		 * Clear scheduled event.
		 *
		 * @return void
		 */
		public static function unschedule() {
			$timestamp = wp_next_scheduled( self::HOOK );
			if ( $timestamp ) {
				wp_unschedule_event( $timestamp, self::HOOK );
			}
		}

		/**
		 * Rebuild engagement counters for all item types.
		 *
		 * @return void
		 */
		public static function run_reconciliation() {
			if ( ! class_exists( 'WP_Ulike_Pro_Maintenance' ) ) {
				return;
			}

			foreach ( WP_Ulike_Pro_Maintenance::CONTENT_TYPES as $type ) {
				if ( ! self::is_type_available( $type ) ) {
					continue;
				}

				WP_Ulike_Pro_Maintenance::sync_counters( $type );
			}
		}

		/**
		 * @param string $type Content type slug.
		 * @return bool
		 */
		private static function is_type_available( $type ) {
			if ( 'activity' === $type ) {
				return defined( 'BP_VERSION' );
			}
			if ( 'topic' === $type ) {
				return function_exists( 'is_bbpress' );
			}
			return true;
		}
	}
}

