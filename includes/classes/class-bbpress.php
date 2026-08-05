<?php
/**
 * bbPress integration helpers for display automation.
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

if ( ! class_exists( 'WP_Ulike_Pro_BbPress' ) ) {

	/**
	 * bbPress compatibility and display condition helpers.
	 */
	class WP_Ulike_Pro_BbPress {

		/**
		 * Whether bbPress is active.
		 *
		 * @return bool
		 */
		public static function is_active() {
			return function_exists( 'is_bbpress' ) && function_exists( 'bbp_get_topic_id' );
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
				'bbpress'         => is_bbpress(),
				'bbp_forum'       => function_exists( 'bbp_is_forum' ) && bbp_is_forum(),
				'bbp_topic'       => function_exists( 'bbp_is_single_topic' ) && bbp_is_single_topic(),
				'bbp_reply'       => function_exists( 'bbp_is_single_reply' ) && bbp_is_single_reply(),
				'bbp_topic_archive' => function_exists( 'bbp_is_topic_archive' ) && bbp_is_topic_archive(),
				'bbp_search'      => function_exists( 'bbp_is_search' ) && bbp_is_search(),
			);

			return apply_filters( 'wp_ulike_pro_bbpress_display_contexts', $contexts );
		}

		/**
		 * Forum options for admin filters.
		 *
		 * @return array<int, string>
		 */
		public static function get_forum_options() {
			if ( ! self::is_active() || ! function_exists( 'bbp_get_forum_post_type' ) ) {
				return array();
			}

			$forums = get_posts(
				array(
					'post_type'              => bbp_get_forum_post_type(),
					'post_status'            => 'publish',
					'posts_per_page'         => 200,
					'orderby'                => 'title',
					'order'                  => 'ASC',
					'no_found_rows'          => true,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
				)
			);

			$options = array();

			foreach ( $forums as $forum ) {
				$options[ (int) $forum->ID ] = $forum->post_title;
			}

			return $options;
		}

		/**
		 * Resolve the current bbPress item post ID.
		 *
		 * @return int
		 */
		public static function get_current_item_id() {
			if ( ! self::is_active() ) {
				return 0;
			}

			$reply_id = function_exists( 'bbp_get_reply_id' ) ? (int) bbp_get_reply_id() : 0;

			if ( $reply_id > 0 ) {
				return $reply_id;
			}

			return function_exists( 'bbp_get_topic_id' ) ? (int) bbp_get_topic_id() : 0;
		}

		/**
		 * Whether a placement targets forum replies.
		 *
		 * @param string $placement Placement key.
		 * @return bool
		 */
		public static function placement_targets_reply( $placement ) {
			return in_array( $placement, array( 'bbp_before_reply', 'bbp_after_reply' ), true );
		}

		/**
		 * Whether a placement targets forum topics.
		 *
		 * @param string $placement Placement key.
		 * @return bool
		 */
		public static function placement_targets_topic( $placement ) {
			return in_array(
				$placement,
				array(
					'bbp_before_topic',
					'bbp_after_topic',
					'bbp_topic_content',
					'bbp_after_topic_title',
				),
				true
			);
		}

		/**
		 * Whether the current item matches placement target (topic vs reply).
		 *
		 * @param string $placement Placement key.
		 * @return bool
		 */
		public static function matches_placement_item( $placement ) {
			return self::matches_placement_for_post( $placement, self::get_current_item_id() );
		}

		/**
		 * Whether a specific post ID matches a placement target (topic vs reply).
		 *
		 * @param string $placement Placement key.
		 * @param int    $post_id   Post ID.
		 * @return bool
		 */
		public static function matches_placement_for_post( $placement, $post_id ) {
			$post_id = (int) $post_id;

			if ( $post_id <= 0 ) {
				return false;
			}

			if ( self::placement_targets_reply( $placement ) ) {
				return function_exists( 'bbp_is_reply' ) && bbp_is_reply( $post_id );
			}

			if ( self::placement_targets_topic( $placement ) ) {
				return function_exists( 'bbp_is_topic' ) && bbp_is_topic( $post_id );
			}

			return true;
		}

		/**
		 * Whether inside-topic content output should run for a topic ID.
		 *
		 * Covers the lead topic on single topics and the topic row when lead topics are disabled.
		 *
		 * @param int $topic_id Topic post ID.
		 * @return bool
		 */
		public static function is_inside_topic_content_context( $topic_id ) {
			$topic_id = (int) $topic_id;

			if ( $topic_id <= 0 || ! function_exists( 'bbp_is_topic' ) || ! bbp_is_topic( $topic_id ) ) {
				return false;
			}

			if ( function_exists( 'bbp_is_search' ) && bbp_is_search() ) {
				return true;
			}

			if ( function_exists( 'bbp_is_single_topic' ) && bbp_is_single_topic() ) {
				return true;
			}

			return false;
		}

		/**
		 * Match optional bbPress rule conditions.
		 *
		 * @param array $conditions Rule conditions.
		 * @param int   $post_id    Optional explicit post ID (topic or reply).
		 * @return bool
		 */
		public static function matches_conditions( $conditions, $post_id = 0 ) {
			if ( ! self::is_active() ) {
				return true;
			}

			$bbpress = isset( $conditions['bbpress'] ) && is_array( $conditions['bbpress'] ) ? $conditions['bbpress'] : array();
			$post_id = $post_id > 0 ? (int) $post_id : self::get_current_item_id();

			if ( $post_id <= 0 ) {
				return empty( $bbpress['item_type'] ) && empty( $bbpress['forum_ids'] ) && empty( $bbpress['topic_ids'] );
			}

			$item_type = sanitize_key( $bbpress['item_type'] ?? '' );

			if ( 'topic' === $item_type && ( ! function_exists( 'bbp_is_topic' ) || ! bbp_is_topic( $post_id ) ) ) {
				return false;
			}

			if ( 'reply' === $item_type && ( ! function_exists( 'bbp_is_reply' ) || ! bbp_is_reply( $post_id ) ) ) {
				return false;
			}

			$forum_ids = isset( $bbpress['forum_ids'] ) && is_array( $bbpress['forum_ids'] ) ? array_map( 'absint', $bbpress['forum_ids'] ) : array();
			$topic_ids = isset( $bbpress['topic_ids'] ) && is_array( $bbpress['topic_ids'] ) ? array_map( 'absint', $bbpress['topic_ids'] ) : array();

			if ( ! empty( $forum_ids ) || ! empty( $topic_ids ) ) {
				$topic_id = $post_id;

				if ( function_exists( 'bbp_is_reply' ) && bbp_is_reply( $post_id ) && function_exists( 'bbp_get_reply_topic_id' ) ) {
					$topic_id = (int) bbp_get_reply_topic_id( $post_id );
				}

				if ( ! empty( $topic_ids ) && ! in_array( $topic_id, $topic_ids, true ) ) {
					return false;
				}

				if ( ! empty( $forum_ids ) && function_exists( 'bbp_get_topic_forum_id' ) ) {
					$forum_id = (int) bbp_get_topic_forum_id( $topic_id );

					if ( ! in_array( $forum_id, $forum_ids, true ) ) {
						return false;
					}
				}
			}

			return true;
		}
	}
}

