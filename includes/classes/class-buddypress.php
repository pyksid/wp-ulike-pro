<?php
/**
 * BuddyPress integration helpers for display automation.
 *
 *
 * @package    wp-ulike-pro
 * @author     TechnoWich 2026
 * @link       https://wpulike.com
 */

// no direct access allowed
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'WP_Ulike_Pro_BuddyPress' ) ) {

	/**
	 * BuddyPress compatibility and display condition helpers.
	 */
	class WP_Ulike_Pro_BuddyPress {

		/**
		 * BuddyPress Nouveau activity AJAX actions.
		 *
		 * @return array<int, string>
		 */
		public static function get_activity_ajax_actions() {
			return array(
				'activity_filter',
				'get_single_activity_content',
				'new_activity_comment',
				'activity_mark_fav',
				'activity_mark_unfav',
				'activity_clear_new_mentions',
				'delete_activity',
				'bp_nouveau_get_activity_objects',
				'post_update',
				'bp_spam_activity',
				'heartbeat',
			);
		}

		/**
		 * Whether the current request is a BuddyPress activity-related AJAX call.
		 *
		 * @return bool
		 */
		public static function is_buddypress_activity_ajax() {
			if ( ! wp_doing_ajax() ) {
				return false;
			}

			$action = sanitize_key( wp_unslash( $_REQUEST['action'] ?? '' ) );

			if ( in_array( $action, self::get_activity_ajax_actions(), true ) ) {
				return true;
			}

			$object = sanitize_key( wp_unslash( $_POST['object'] ?? $_REQUEST['object'] ?? '' ) );

			return 'activity' === $object;
		}

		/**
		 * Whether BuddyPress is active.
		 *
		 * @return bool
		 */
		public static function is_active() {
			return defined( 'BP_VERSION' ) && function_exists( 'bp_get_activity_id' );
		}

		/**
		 * Page context map for display automation rules.
		 *
		 * @return array<string, bool>
		 */
		public static function get_context_map() {
			if ( ! self::is_active() ) {
				return array();
			}

			$contexts = array(
				'buddypress'            => self::is_buddypress_page(),
				'bp_activity_directory' => self::is_activity_directory_context(),
				'bp_member_profile'   => self::is_member_profile_context(),
				'bp_groups'             => self::is_groups_context(),
			);

			if ( wp_doing_ajax() ) {
				foreach ( self::get_ajax_request_contexts() as $key => $active ) {
					if ( $active ) {
						$contexts[ $key ] = true;
					}
				}
			}

			return apply_filters( 'wp_ulike_pro_buddypress_display_contexts', $contexts );
		}

		/**
		 * Infer BuddyPress page contexts during AJAX when BP template tags are unreliable.
		 *
		 * @return array<string, bool>
		 */
		public static function get_ajax_request_contexts() {
			$contexts = array(
				'buddypress'            => false,
				'bp_activity_directory' => false,
				'bp_member_profile'     => false,
				'bp_groups'             => false,
			);

			if ( ! self::is_buddypress_activity_ajax() ) {
				return $contexts;
			}

			$contexts['buddypress'] = true;

			if ( self::is_groups_context() ) {
				$contexts['bp_groups'] = true;
				return $contexts;
			}

			if ( self::is_member_profile_context() ) {
				$contexts['bp_member_profile'] = true;
				return $contexts;
			}

			if ( self::is_activity_directory_context() ) {
				$contexts['bp_activity_directory'] = true;
				return $contexts;
			}

			$scope = sanitize_key( wp_unslash( $_POST['scope'] ?? $_REQUEST['scope'] ?? '' ) );

			if ( in_array( $scope, array( 'groups', 'group' ), true ) ) {
				$contexts['bp_groups'] = true;
				return $contexts;
			}

			if ( in_array( $scope, array( 'personal', 'friends', 'mentions', 'favorites', 'following' ), true ) ) {
				$contexts['bp_member_profile'] = true;
				return $contexts;
			}

			$contexts['bp_activity_directory'] = true;

			return $contexts;
		}

		/**
		 * Whether the request is on a BuddyPress screen.
		 *
		 * @return bool
		 */
		private static function is_buddypress_page() {
			if ( function_exists( 'is_buddypress' ) && is_buddypress() ) {
				return true;
			}

			return self::is_buddypress_activity_ajax();
		}

		/**
		 * Whether the activity directory is the current context.
		 *
		 * @return bool
		 */
		private static function is_activity_directory_context() {
			if ( function_exists( 'bp_is_activity_directory' ) && bp_is_activity_directory() ) {
				return true;
			}

			if ( function_exists( 'bp_is_activity_component' ) && bp_is_activity_component() && function_exists( 'bp_is_user' ) && ! bp_is_user() && function_exists( 'bp_is_group' ) && ! bp_is_group() ) {
				return true;
			}

			if ( ! self::is_buddypress_activity_ajax() ) {
				return false;
			}

			return ! self::is_member_profile_context() && ! self::is_groups_context();
		}

		/**
		 * Whether a member profile activity stream is the current context.
		 *
		 * @return bool
		 */
		private static function is_member_profile_context() {
			if ( function_exists( 'bp_is_user' ) && bp_is_user() && function_exists( 'bp_is_activity_component' ) && bp_is_activity_component() ) {
				return true;
			}

			return self::referer_matches( array( '/members/', '/profile/' ) );
		}

		/**
		 * Whether a group activity stream is the current context.
		 *
		 * @return bool
		 */
		private static function is_groups_context() {
			if ( function_exists( 'bp_is_group' ) && bp_is_group() ) {
				return true;
			}

			if ( function_exists( 'bp_is_group_activity' ) && bp_is_group_activity() ) {
				return true;
			}

			return self::referer_matches( array( '/groups/' ) );
		}

		/**
		 * Match the HTTP referer path against BuddyPress route fragments.
		 *
		 * @param array<int, string> $needles Path fragments.
		 * @return bool
		 */
		private static function referer_matches( $needles ) {
			$referer = wp_get_referer();

			if ( ! $referer ) {
				return false;
			}

			$path = wp_parse_url( $referer, PHP_URL_PATH );

			if ( ! is_string( $path ) || '' === $path ) {
				return false;
			}

			foreach ( $needles as $needle ) {
				if ( false !== strpos( $path, $needle ) ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Whether the current BuddyPress loop item is an activity comment.
		 *
		 * @return bool
		 */
		public static function is_activity_comment_context() {
			return self::is_active() && function_exists( 'bp_get_activity_comment_id' ) && (bool) bp_get_activity_comment_id();
		}

		/**
		 * Whether the current BuddyPress loop item is a top-level activity update.
		 *
		 * @return bool
		 */
		public static function is_activity_post_context() {
			return self::is_active() && ! self::is_activity_comment_context() && (bool) bp_get_activity_id();
		}
	}
}

