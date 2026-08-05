<?php
/**
 * Advanced display automation for WP ULike Pro.
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

if ( ! class_exists( 'WP_Ulike_Pro_Display_Automation' ) ) {

	/**
	 * Rule-based auto display engine.
	 */
	class WP_Ulike_Pro_Display_Automation {

		const OPTION_KEY = 'wp_ulike_pro_display_rules';

		/**
		 * Track registered content filters to avoid duplicate output.
		 *
		 * @var array<string, bool>
		 */
		private $rendered_content_filters = array();

		/**
		 * Whether frontend hooks were already registered.
		 *
		 * @var bool
		 */
		private static $hooks_registered = false;

		/**
		 * Cached active rules for the current request.
		 *
		 * @var array<int, array<string, mixed>>|null
		 */
		private static $active_rules_cache = null;

		/**
		 * Cached placement config index (placement key => config).
		 *
		 * @var array<string, array<string, mixed>>|null
		 */
		private static $placement_config_index = null;

		/**
		 * Cached context map for the current request (per instance).
		 *
		 * @var array<string, bool>|null
		 */
		private $request_context_map = null;

		/**
		 * Constructor.
		 */
		public function __construct() {
			add_action( 'wp', array( $this, 'register_hooks' ), 20 );
			add_action( 'bp_init', array( $this, 'register_hooks' ), 20 );
			add_filter( 'wp_ulike_enable_auto_display', array( $this, 'maybe_disable_default_display' ), 20, 2 );
		}

		/**
		 * Get saved rules.
		 *
		 * @return array<int, array<string, mixed>>
		 */
		public static function get_rules() {
			$rules = get_option( self::OPTION_KEY, array() );
			return is_array( $rules ) ? $rules : array();
		}

		/**
		 * Save sanitized rules.
		 *
		 * @param array $rules Raw rules.
		 * @return bool
		 */
		public static function save_rules( $rules ) {
			$sanitized = self::sanitize_rules( $rules );
			$saved     = update_option( self::OPTION_KEY, $sanitized, false );

			if ( $saved ) {
				self::clear_runtime_caches();
			}

			return $saved;
		}

		/**
		 * Clear in-memory caches after rules are updated.
		 *
		 * @return void
		 */
		public static function clear_runtime_caches() {
			self::$active_rules_cache     = null;
			self::$placement_config_index = null;
		}

		/**
		 * Get enabled rules sorted by priority.
		 *
		 * @return array<int, array<string, mixed>>
		 */
		public static function get_active_rules() {
			if ( null !== self::$active_rules_cache ) {
				return self::$active_rules_cache;
			}

			$rules = self::get_rules();

			$rules = array_values(
				array_filter(
					$rules,
					static function( $rule ) {
						return ! empty( $rule['enabled'] );
					}
				)
			);

			usort(
				$rules,
				static function( $a, $b ) {
					return (int) ( $a['priority'] ?? 10 ) <=> (int) ( $b['priority'] ?? 10 );
				}
			);

			self::$active_rules_cache = $rules;

			return self::$active_rules_cache;
		}

		/**
		 * Register frontend hooks for active rules.
		 *
		 * @return void
		 */
		public function register_hooks() {
			if ( is_admin() && ! wp_doing_ajax() ) {
				return;
			}

			if ( self::$hooks_registered ) {
				return;
			}

			foreach ( self::get_active_rules() as $rule ) {
				$this->register_rule_hook( $rule );
			}

			self::$hooks_registered = true;
		}

		/**
		 * Disable default auto display when a matching pro rule overrides it.
		 *
		 * @param bool   $status Current status.
		 * @param string $type   Content type.
		 * @return bool
		 */
		public function maybe_disable_default_display( $status, $type ) {
			if ( is_admin() && ! wp_doing_ajax() ) {
				return $status;
			}

			if ( ! $status ) {
				return $status;
			}

			foreach ( self::get_active_rules() as $rule ) {
				if ( empty( $rule['override_default'] ) ) {
					continue;
				}

				$content_type = $rule['content_type'] ?? 'post';
				$map          = array(
					'post'            => 'post',
					'comment'         => 'comment',
					'product_review'  => 'comment',
					'activity'         => 'activity',
					'activity_comment' => 'activity_comment',
					'topic'            => 'topic',
				);

				if ( ( $map[ $content_type ] ?? '' ) !== $type ) {
					continue;
				}

				$placement = $rule['placement'] ?? '';
				if ( ! self::placement_uses_content_position( $placement ) ) {
					continue;
				}

				if ( $this->matches_conditions( $rule ) ) {
					return false;
				}
			}

			return $status;
		}

		/**
		 * Register a single rule hook.
		 *
		 * @param array $rule Rule data.
		 * @return void
		 */
		private function register_rule_hook( $rule ) {
			$placement = $rule['placement'] ?? '';

			if ( 'edd_shop_excerpt' === $placement ) {
				$priority = (int) ( $rule['hook_priority'] ?? 15 );
				$this->register_edd_shop_actions( $rule, max( 5, $priority ) );
				return;
			}

			if ( in_array( $placement, array( 'bbp_after_topic', 'bbp_before_topic', 'bbp_topic_content' ), true ) ) {
				$priority = (int) ( $rule['hook_priority'] ?? 15 );
				$this->register_bbpress_topic_placements( $rule, $placement, max( 5, $priority ) );
				return;
			}

			if ( in_array( $placement, array( 'bp_activity_content', 'bp_activity_meta', 'bp_activity_comment_content', 'bp_activity_comment_options' ), true ) ) {
				$priority = (int) ( $rule['hook_priority'] ?? 15 );
				$this->register_buddypress_activity_placements( $rule, $placement, max( 5, $priority ) );
				return;
			}

			$config = self::get_placement_config( $placement );

			if ( empty( $config ) ) {
				$custom_hook = sanitize_key( $rule['custom_hook'] ?? '' );
				if ( $custom_hook ) {
					add_action(
						$custom_hook,
						function() use ( $rule ) {
							$this->maybe_render( $rule );
						},
						(int) ( $rule['hook_priority'] ?? 10 )
					);
				}
				return;
			}

			if ( 'filter' === ( $config['type'] ?? '' ) ) {
				$priority = (int) ( $rule['hook_priority'] ?? 15 );
				$priority = max( 5, $priority );

				add_filter(
					$config['hook'],
					function( $content, ...$args ) use ( $rule, $config ) {
						$comment = null;
						if ( 'comment_text' === ( $config['hook'] ?? '' ) && isset( $args[0] ) && is_object( $args[0] ) ) {
							$comment = $args[0];
						}
						return $this->maybe_append_to_content( $content, $rule, $config, $comment );
					},
					$priority,
					(int) ( $config['accepted_args'] ?? 1 )
				);
				return;
			}

			add_action(
				$config['hook'],
				function() use ( $rule ) {
					$this->maybe_render( $rule );
				},
				(int) ( $config['priority'] ?? $rule['hook_priority'] ?? 10 )
			);
		}

		/**
		 * Register EDD shop listing actions (no excerpt/content required).
		 *
		 * @param array $rule     Rule data.
		 * @param int   $priority Hook priority.
		 * @return void
		 */
		private function register_edd_shop_actions( $rule, $priority ) {
			add_action(
				'edd_purchase_link_top',
				function( $download_id ) use ( $rule ) {
					$this->maybe_render_edd_shop_item( $rule, (int) $download_id );
				},
				5,
				1
			);

			add_action(
				'edd_download_after_title',
				function() use ( $rule ) {
					$this->maybe_render_edd_shop_item( $rule );
				},
				$priority
			);

			add_action(
				'edd_blocks_downloads_after_entry_title',
				function() use ( $rule ) {
					$this->maybe_render_edd_shop_item( $rule );
				},
				$priority
			);
		}

		/**
		 * Render like button after each item in EDD download listings.
		 *
		 * @param array $rule Rule data.
		 * @return void
		 */
		private function maybe_render_edd_shop_item( $rule, $download_id = 0 ) {
			$post_id = $download_id > 0 ? $download_id : (int) get_the_ID();

			if ( $post_id <= 0 || 'download' !== get_post_type( $post_id ) ) {
				return;
			}

			if ( is_singular( 'download' ) ) {
				return;
			}

			if ( ! WP_Ulike_Pro_Easy_Digital_Downloads::is_shop_listing() && ! WP_Ulike_Pro_Easy_Digital_Downloads::in_downloads_shortcode_loop() ) {
				return;
			}

			$filter_key = 'edd_shop:' . $this->get_rule_cache_key( $rule ) . ':' . $post_id;

			if ( isset( $this->rendered_content_filters[ $filter_key ] ) ) {
				return;
			}

			$previous_post = isset( $GLOBALS['post'] ) ? $GLOBALS['post'] : null;
			$download_post = get_post( $post_id );

			if ( $download_post instanceof WP_Post ) {
				$GLOBALS['post'] = $download_post;
			}

			$matched = $this->matches_conditions( $rule ) && $this->matches_content_type_context( $rule );

			if ( $previous_post instanceof WP_Post ) {
				$GLOBALS['post'] = $previous_post;
			} elseif ( null === $previous_post ) {
				unset( $GLOBALS['post'] );
			}

			if ( ! $matched ) {
				return;
			}

			if ( $download_post instanceof WP_Post ) {
				$GLOBALS['post'] = $download_post;
			}

			$button_args = isset( $rule['button_args'] ) && is_array( $rule['button_args'] ) ? $rule['button_args'] : array();
			$button_args['id'] = $post_id;
			$rule_for_render = $rule;
			$rule_for_render['button_args'] = $button_args;

			$button = $this->render_button( $rule_for_render );

			if ( $previous_post instanceof WP_Post ) {
				$GLOBALS['post'] = $previous_post;
			} elseif ( null === $previous_post ) {
				unset( $GLOBALS['post'] );
			}

			if ( '' === $button ) {
				return;
			}

			$this->rendered_content_filters[ $filter_key ] = true;

			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $button;
		}

		/**
		 * Register BuddyPress activity placements (including AJAX load more / read more).
		 *
		 * @param array  $rule      Rule data.
		 * @param string $placement Placement key.
		 * @param int    $priority  Hook priority.
		 * @return void
		 */
		private function register_buddypress_activity_placements( $rule, $placement, $priority ) {
			$content_type = $rule['content_type'] ?? 'activity';

			if ( 'bp_activity_comment_content' === $placement || ( 'bp_activity_content' === $placement && 'activity_comment' === $content_type ) ) {
				$this->register_buddypress_comment_content_filter( $rule, $priority );
				return;
			}

			if ( 'bp_activity_comment_options' === $placement || ( 'bp_activity_meta' === $placement && 'activity_comment' === $content_type ) ) {
				add_action(
					'bp_activity_comment_options',
					function() use ( $rule ) {
						$this->maybe_render_buddypress_activity_item( $rule, 0, true );
					},
					$priority
				);
				return;
			}

			if ( 'bp_activity_content' === $placement ) {
				add_action(
					'bp_activity_entry_content',
					function() use ( $rule ) {
						$this->maybe_render_buddypress_activity_item( $rule );
					},
					$priority
				);

				add_action(
					'bp_nouveau_get_single_activity_content',
					function( $activity ) use ( $rule, $priority ) {
						if ( ! is_object( $activity ) || empty( $activity->id ) ) {
							return;
						}

						$activity_id = (int) $activity->id;

						add_filter(
							'bp_get_activity_content_body',
							function( $content ) use ( $rule, $activity_id, $priority ) {
								return $this->maybe_append_buddypress_activity_content( $content, $rule, $activity_id, false );
							},
							$priority,
							1
						);
					},
					$priority,
					1
				);

				return;
			}

			add_action(
				'bp_activity_entry_meta',
				function() use ( $rule ) {
					$this->maybe_render_buddypress_activity_item( $rule );
				},
				$priority
			);
		}

		/**
		 * Register the BuddyPress activity comment content filter.
		 *
		 * @param array $rule     Rule data.
		 * @param int   $priority Hook priority.
		 * @return void
		 */
		private function register_buddypress_comment_content_filter( $rule, $priority ) {
			add_filter(
				'bp_activity_comment_content',
				function( $content, $context ) use ( $rule ) {
					if ( 'get' !== $context ) {
						return $content;
					}

					return $this->maybe_append_buddypress_activity_content( $content, $rule, 0, true );
				},
				$priority,
				2
			);
		}

		/**
		 * Render like button for a BuddyPress activity or activity comment.
		 *
		 * @param array $rule         Rule data.
		 * @param int   $activity_id  Optional activity ID.
		 * @param bool  $is_comment   Whether the target is an activity comment.
		 * @return void
		 */
		private function maybe_render_buddypress_activity_item( $rule, $activity_id = 0, $is_comment = false ) {
			if ( $is_comment ) {
				$item_id = function_exists( 'bp_get_activity_comment_id' ) ? (int) bp_get_activity_comment_id() : 0;
			} else {
				$item_id = $activity_id > 0 ? $activity_id : ( function_exists( 'bp_get_activity_id' ) ? (int) bp_get_activity_id() : 0 );
			}

			if ( $item_id <= 0 ) {
				return;
			}

			$filter_key = ( $is_comment ? 'bp_activity_comment' : 'bp_activity' ) . ':' . $this->get_rule_cache_key( $rule ) . ':' . $item_id;

			if ( isset( $this->rendered_content_filters[ $filter_key ] ) ) {
				return;
			}

			if ( ! $this->matches_conditions( $rule ) || ! $this->matches_content_type_context( $rule ) ) {
				return;
			}

			$button_args = isset( $rule['button_args'] ) && is_array( $rule['button_args'] ) ? $rule['button_args'] : array();
			$button_args['id'] = $item_id;
			$rule_for_render = $rule;
			$rule_for_render['button_args'] = $button_args;

			$button = $this->render_button( $rule_for_render );

			if ( '' === $button ) {
				return;
			}

			$this->rendered_content_filters[ $filter_key ] = true;

			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $button;
		}

		/**
		 * Append like button inside BuddyPress activity or comment content.
		 *
		 * @param string $content      Original content.
		 * @param array  $rule         Rule data.
		 * @param int    $activity_id  Activity ID for AJAX read-more requests.
		 * @param bool   $is_comment   Whether the target is an activity comment.
		 * @return string
		 */
		private function maybe_append_buddypress_activity_content( $content, $rule, $activity_id = 0, $is_comment = false ) {
			if ( $is_comment ) {
				$item_id = function_exists( 'bp_get_activity_comment_id' ) ? (int) bp_get_activity_comment_id() : 0;
			} else {
				$item_id = $activity_id > 0 ? $activity_id : ( function_exists( 'bp_get_activity_id' ) ? (int) bp_get_activity_id() : 0 );
			}

			if ( $item_id <= 0 ) {
				return $content;
			}

			$filter_key = ( $is_comment ? 'bp_activity_comment_content' : 'bp_activity_content' ) . ':' . $this->get_rule_cache_key( $rule ) . ':' . $item_id;

			if ( isset( $this->rendered_content_filters[ $filter_key ] ) ) {
				return $content;
			}

			$button_args = isset( $rule['button_args'] ) && is_array( $rule['button_args'] ) ? $rule['button_args'] : array();
			$button_args['id'] = $item_id;
			$rule_for_render = $rule;
			$rule_for_render['button_args'] = $button_args;

			if ( ! $this->matches_conditions( $rule ) || ! $this->matches_content_type_context( $rule ) ) {
				return $content;
			}

			$button = $this->render_button( $rule_for_render );

			if ( '' === $button ) {
				return $content;
			}

			$this->rendered_content_filters[ $filter_key ] = true;
			$position = $rule['position'] ?? 'bottom';

			switch ( $position ) {
				case 'top':
					return $button . $content;
				case 'top_bottom':
					return $button . $content . $button;
				default:
					return $content . $button;
			}
		}

		/**
		 * Stable per-rule cache key for deduplication.
		 *
		 * @param array $rule Rule data.
		 * @return string
		 */
		private function get_rule_cache_key( array $rule ) {
			if ( ! empty( $rule['id'] ) ) {
				return (string) $rule['id'];
			}

			return md5( wp_json_encode( $rule ) );
		}

		/**
		 * Cached context map for repeated condition checks in one request.
		 *
		 * @return array<string, bool>
		 */
		private function get_request_context_map() {
			if ( null === $this->request_context_map ) {
				$this->request_context_map = self::get_context_map();
			}

			return $this->request_context_map;
		}

		/**
		 * Whether a placement supports the inside-content position control.
		 *
		 * @param string $placement Placement key.
		 * @return bool
		 */
		public static function placement_uses_content_position( $placement ) {
			$placements = array(
				'the_content',
				'the_excerpt',
				'comment_text',
				'wc_product_reviews',
				'bp_activity_comment_content',
				'bbp_topic_content',
			);

			if ( in_array( $placement, $placements, true ) ) {
				return true;
			}

			return false;
		}

		/**
		 * Register bbPress topic placements on every hook the default theme fires.
		 *
		 * Forum topic lists only expose title/meta hooks — not before/after_topic_content.
		 *
		 * @param array  $rule      Rule data.
		 * @param string $placement Placement key.
		 * @param int    $priority  Hook priority.
		 * @return void
		 */
		private function register_bbpress_topic_placements( $rule, $placement, $priority ) {
			if ( 'bbp_topic_content' === $placement ) {
				add_filter(
					'bbp_get_topic_content',
					function( $content, $topic_id = 0 ) use ( $rule ) {
						return $this->maybe_append_bbpress_topic_content( $content, $rule, (int) $topic_id );
					},
					$priority,
					2
				);

				add_filter(
					'bbp_get_reply_content',
					function( $content, $reply_id = 0 ) use ( $rule ) {
						$reply_id = (int) $reply_id;

						if ( $reply_id <= 0 || ! function_exists( 'bbp_is_topic' ) || ! bbp_is_topic( $reply_id ) ) {
							return $content;
						}

						return $this->maybe_append_bbpress_topic_content( $content, $rule, $reply_id );
					},
					$priority,
					2
				);

				return;
			}

			$hooks = array();

			if ( 'bbp_after_topic' === $placement ) {
				$hooks = array( 'bbp_theme_after_topic_content', 'bbp_theme_after_topic_meta' );
			} elseif ( 'bbp_before_topic' === $placement ) {
				$hooks = array( 'bbp_theme_before_topic_content', 'bbp_theme_before_topic_meta' );
			}

			foreach ( $hooks as $hook ) {
				add_action(
					$hook,
					function() use ( $rule ) {
						$this->maybe_render_bbpress_topic_item( $rule );
					},
					$priority
				);
			}
		}

		/**
		 * Render like button for a bbPress topic (deduped across theme hooks).
		 *
		 * @param array $rule Rule data.
		 * @return void
		 */
		private function maybe_render_bbpress_topic_item( $rule ) {
			$topic_id = function_exists( 'bbp_get_topic_id' ) ? (int) bbp_get_topic_id() : 0;

			if ( $topic_id <= 0 || ! function_exists( 'bbp_is_topic' ) || ! bbp_is_topic( $topic_id ) ) {
				return;
			}

			$filter_key = 'bbp_topic:' . $this->get_rule_cache_key( $rule ) . ':' . $topic_id;

			if ( isset( $this->rendered_content_filters[ $filter_key ] ) ) {
				return;
			}

			$previous_post = isset( $GLOBALS['post'] ) ? $GLOBALS['post'] : null;
			$topic_post    = get_post( $topic_id );

			if ( $topic_post instanceof WP_Post ) {
				$GLOBALS['post'] = $topic_post;
			}

			$matched = $this->matches_conditions( $rule ) && $this->matches_content_type_context( $rule );

			if ( $previous_post instanceof WP_Post ) {
				$GLOBALS['post'] = $previous_post;
			} elseif ( null === $previous_post ) {
				unset( $GLOBALS['post'] );
			}

			if ( ! $matched ) {
				return;
			}

			if ( $topic_post instanceof WP_Post ) {
				$GLOBALS['post'] = $topic_post;
			}

			$button_args = isset( $rule['button_args'] ) && is_array( $rule['button_args'] ) ? $rule['button_args'] : array();
			$button_args['id'] = $topic_id;
			$rule_for_render = $rule;
			$rule_for_render['button_args'] = $button_args;

			$button = $this->render_button( $rule_for_render );

			if ( $previous_post instanceof WP_Post ) {
				$GLOBALS['post'] = $previous_post;
			} elseif ( null === $previous_post ) {
				unset( $GLOBALS['post'] );
			}

			if ( '' === $button ) {
				return;
			}

			$this->rendered_content_filters[ $filter_key ] = true;

			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $button;
		}

		/**
		 * Append like button inside bbPress topic content.
		 *
		 * @param string $content   Topic content HTML.
		 * @param array  $rule      Rule data.
		 * @param int    $topic_id  Topic post ID from the filter.
		 * @return string
		 */
		private function maybe_append_bbpress_topic_content( $content, $rule, $topic_id ) {
			if ( $topic_id <= 0 && function_exists( 'bbp_get_topic_id' ) ) {
				$topic_id = (int) bbp_get_topic_id();
			}

			if ( $topic_id <= 0 || ! function_exists( 'bbp_is_topic' ) || ! bbp_is_topic( $topic_id ) ) {
				return $content;
			}

			if ( ! WP_Ulike_Pro_BbPress::is_inside_topic_content_context( $topic_id ) ) {
				return $content;
			}

			$filter_key = 'bbp_topic_content:' . $this->get_rule_cache_key( $rule ) . ':' . $topic_id;

			if ( isset( $this->rendered_content_filters[ $filter_key ] ) ) {
				return $content;
			}

			$previous_post = isset( $GLOBALS['post'] ) ? $GLOBALS['post'] : null;
			$topic_post    = get_post( $topic_id );

			if ( $topic_post instanceof WP_Post ) {
				$GLOBALS['post'] = $topic_post;
			}

			if ( ! $this->matches_bbpress_topic_rule( $rule, $topic_id ) ) {
				if ( $previous_post instanceof WP_Post ) {
					$GLOBALS['post'] = $previous_post;
				} elseif ( null === $previous_post ) {
					unset( $GLOBALS['post'] );
				}
				return $content;
			}

			$button_args = isset( $rule['button_args'] ) && is_array( $rule['button_args'] ) ? $rule['button_args'] : array();
			$button_args['id'] = $topic_id;
			$rule_for_render = $rule;
			$rule_for_render['button_args'] = $button_args;

			$button = $this->render_button( $rule_for_render );

			if ( $previous_post instanceof WP_Post ) {
				$GLOBALS['post'] = $previous_post;
			} elseif ( null === $previous_post ) {
				unset( $GLOBALS['post'] );
			}

			if ( '' === $button ) {
				return $content;
			}

			$this->rendered_content_filters[ $filter_key ] = true;
			$position = $rule['position'] ?? 'bottom';

			switch ( $position ) {
				case 'top':
					return $button . $content;
				case 'top_bottom':
					return $button . $content . $button;
				default:
					return $content . $button;
			}
		}

		/**
		 * Match a bbPress topic rule using an explicit topic post ID.
		 *
		 * @param array $rule     Rule data.
		 * @param int   $topic_id Topic post ID.
		 * @return bool
		 */
		private function matches_bbpress_topic_rule( $rule, $topic_id ) {
			if ( 'topic' !== ( $rule['content_type'] ?? '' ) ) {
				return false;
			}

			$placement = $rule['placement'] ?? '';

			if ( ! WP_Ulike_Pro_BbPress::matches_placement_for_post( $placement, $topic_id ) ) {
				return false;
			}

			$conditions = isset( $rule['conditions'] ) && is_array( $rule['conditions'] ) ? $rule['conditions'] : array();

			return WP_Ulike_Pro_BbPress::matches_conditions( $conditions, $topic_id );
		}

		/**
		 * Append button markup to filtered content.
		 *
		 * @param string $content Original content.
		 * @param array  $rule    Rule data.
		 * @param array  $config  Placement config.
		 * @return string
		 */
		private function maybe_append_to_content( $content, $rule, $config, $comment = null ) {
			if ( $comment instanceof WP_Comment ) {
				$GLOBALS['comment'] = $comment;
			}

			if ( ! $this->matches_conditions( $rule, $comment ) ) {
				return $content;
			}

			if ( ! $this->matches_content_type_context( $rule, $comment ) ) {
				return $content;
			}

			$placement = $rule['placement'] ?? '';

			if ( 'bbp_topic_content' === $placement ) {
				return $content;
			}

			$filter_key = $config['hook'] . ':' . $this->get_rule_cache_key( $rule );
			if ( $comment instanceof WP_Comment ) {
				$filter_key .= ':' . (int) $comment->comment_ID;
			} elseif ( 'bp_activity_comment_content' === ( $config['hook'] ?? '' ) && function_exists( 'bp_get_activity_comment_id' ) && bp_get_activity_comment_id() ) {
				$filter_key .= ':' . (int) bp_get_activity_comment_id();
			} elseif ( get_the_ID() ) {
				$filter_key .= ':' . (int) get_the_ID();
			}

			if ( isset( $this->rendered_content_filters[ $filter_key ] ) ) {
				return $content;
			}

			if ( ! empty( $config['requires_loop'] ) && ! in_the_loop() ) {
				return $content;
			}

			if ( ! empty( $config['requires_main_query'] ) && ! is_main_query() ) {
				return $content;
			}

			$button = $this->render_button( $rule, $comment );
			if ( '' === $button ) {
				return $content;
			}

			$this->rendered_content_filters[ $filter_key ] = true;
			$position = $rule['position'] ?? 'bottom';

			switch ( $position ) {
				case 'top':
					return $button . $content;
				case 'top_bottom':
					return $button . $content . $button;
				default:
					return $content . $button;
			}
		}

		/**
		 * Render button when conditions match.
		 *
		 * @param array $rule Rule data.
		 * @return void
		 */
		private function maybe_render( $rule ) {
			if ( ! $this->matches_conditions( $rule ) ) {
				return;
			}

			if ( ! $this->matches_content_type_context( $rule ) ) {
				return;
			}

			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $this->render_button( $rule );
		}

		/**
		 * Ensure the current comment context matches review-only rules.
		 *
		 * @param array           $rule    Rule data.
		 * @param WP_Comment|null $comment Comment object.
		 * @return bool
		 */
		private function matches_content_type_context( $rule, $comment = null ) {
			$content_type = $rule['content_type'] ?? 'post';

			if ( 'activity' === $content_type ) {
				return WP_Ulike_Pro_BuddyPress::is_activity_post_context();
			}

			if ( 'activity_comment' === $content_type ) {
				return WP_Ulike_Pro_BuddyPress::is_activity_comment_context();
			}

			if ( 'topic' === $content_type && WP_Ulike_Pro_BbPress::is_active() ) {
				$placement = $rule['placement'] ?? '';
				if ( ! WP_Ulike_Pro_BbPress::matches_placement_item( $placement ) ) {
					return false;
				}
			}

			if ( ! in_array( $content_type, array( 'comment', 'product_review' ), true ) ) {
				return true;
			}

			if ( ! ( $comment instanceof WP_Comment ) ) {
				$comment = isset( $GLOBALS['comment'] ) && $GLOBALS['comment'] instanceof WP_Comment ? $GLOBALS['comment'] : null;
			}

			if ( ! $comment ) {
				return false;
			}

			$type = get_comment_type( $comment );

			if ( 'product_review' === $content_type ) {
				return 'review' === $type && 'product' === get_post_type( (int) $comment->comment_post_ID );
			}

			if ( 'comment' === $content_type && 'review' === $type ) {
				return false;
			}

			return true;
		}

		/**
		 * Render the like button for a rule.
		 *
		 * @param array           $rule    Rule data.
		 * @param WP_Comment|null $comment Optional comment context.
		 * @return string
		 */
		private function render_button( $rule, $comment = null ) {
			$content_type = $rule['content_type'] ?? 'post';
			$button_args  = isset( $rule['button_args'] ) && is_array( $rule['button_args'] ) ? $rule['button_args'] : array();
			$button_args  = self::apply_rule_button_args( $rule, $button_args );
			$item_type    = self::map_rule_item_type( $content_type );
			$eng_context  = self::get_rule_engagement_context( $rule );
			$pushed       = false;

			if ( ! empty( $eng_context ) && class_exists( 'WP_Ulike_Pro_Engagement_Settings' ) ) {
				WP_Ulike_Pro_Engagement_Settings::push_context( $item_type, $eng_context );
				$pushed = true;
			}

			try {
				$output = '';

				switch ( $content_type ) {
					case 'product_review':
					case 'comment':
						if ( $comment instanceof WP_Comment ) {
							$GLOBALS['comment'] = $comment;
						}
						if ( ! isset( $GLOBALS['comment']->comment_ID ) ) {
							break;
						}
						if ( ! $this->matches_content_type_context( $rule, $GLOBALS['comment'] ) ) {
							break;
						}
						$button_args['id'] = (int) $GLOBALS['comment']->comment_ID;
						$button_args       = wp_ulike_pro_merge_comment_button_args( $button_args['id'], $button_args );
						$output            = wp_ulike_comments( 'put', $button_args );
						break;

					case 'activity':
						if ( empty( $button_args['id'] ) && function_exists( 'bp_get_activity_id' ) ) {
							$button_args['id'] = (int) bp_get_activity_id();
						}
						if ( ! empty( $button_args['id'] ) ) {
							$output = wp_ulike_buddypress( 'put', $button_args );
						}
						break;

					case 'activity_comment':
						if ( empty( $button_args['id'] ) && function_exists( 'bp_get_activity_comment_id' ) ) {
							$button_args['id'] = (int) bp_get_activity_comment_id();
						}
						if ( ! empty( $button_args['id'] ) ) {
							$output = wp_ulike_buddypress( 'put', $button_args );
						}
						break;

					case 'topic':
						if ( function_exists( 'wp_ulike_bbpress' ) ) {
							if ( empty( $button_args['id'] ) ) {
								$item_id = WP_Ulike_Pro_BbPress::get_current_item_id();
								if ( $item_id > 0 ) {
									$button_args['id'] = $item_id;
								}
							}
							if ( ! empty( $button_args['id'] ) ) {
								$output = wp_ulike_bbpress( 'put', $button_args );
							}
						}
						break;

					case 'post':
					default:
						$output = wp_ulike( 'put', $button_args );
						break;
				}
			} finally {
				if ( $pushed ) {
					WP_Ulike_Pro_Engagement_Settings::pop_context( $item_type );
				}
			}

			return $output;
		}

		/**
		 * Whether the current loop item is the main singular queried object.
		 *
		 * @param array           $rule    Rule data.
		 * @param WP_Comment|null $comment Optional comment context.
		 * @return bool
		 */
		private function is_main_singular_item( $rule, $comment = null ) {
			unset( $comment );

			// Only post/page loops need queried-object matching. Other content
			// types are already scoped by their own placement hooks.
			if ( 'post' !== ( $rule['content_type'] ?? 'post' ) ) {
				return true;
			}

			if ( ! is_singular() ) {
				return false;
			}

			$queried = (int) get_queried_object_id();
			$current = (int) get_the_ID();

			return $queried > 0 && $current > 0 && $queried === $current;
		}

		/**
		 * Evaluate whether current request matches rule conditions.
		 *
		 * @param array $rule Rule data.
		 * @return bool
		 */
		private function matches_conditions( $rule, $comment = null ) {
			$conditions = isset( $rule['conditions'] ) && is_array( $rule['conditions'] ) ? $rule['conditions'] : array();
			$contexts   = $this->get_request_context_map();
			$content_type = $rule['content_type'] ?? 'post';

			if ( $comment instanceof WP_Comment ) {
				$GLOBALS['comment'] = $comment;
			}

			$show_on = isset( $conditions['show_on'] ) && is_array( $conditions['show_on'] ) ? $conditions['show_on'] : array();
			if ( ! empty( $show_on ) ) {
				$matched            = false;
				$matched_via_single = false;
				$matched_via_other  = false;

				foreach ( $show_on as $context ) {
					if ( empty( $contexts[ $context ] ) ) {
						continue;
					}

					$matched = true;
					if ( 'single' === $context ) {
						$matched_via_single = true;
					} else {
						$matched_via_other = true;
					}
				}

				if ( ! $matched ) {
					return false;
				}

				// "Singular Pages" must mean the main queried item — not related
				// posts / secondary loops that still run while is_singular() is true.
				if ( $matched_via_single && ! $matched_via_other && ! $this->is_main_singular_item( $rule, $comment ) ) {
					return false;
				}
			}

			$hide_on = isset( $conditions['hide_on'] ) && is_array( $conditions['hide_on'] ) ? $conditions['hide_on'] : array();
			foreach ( $hide_on as $context ) {
				if ( ! empty( $contexts[ $context ] ) ) {
					return false;
				}
			}

			$post_types = isset( $conditions['post_types'] ) && is_array( $conditions['post_types'] ) ? $conditions['post_types'] : array();
			if ( ! empty( $post_types ) ) {
				if ( in_array( $content_type, array( 'comment', 'product_review' ), true ) ) {
					$comment_obj = ( $comment instanceof WP_Comment ) ? $comment : ( isset( $GLOBALS['comment'] ) && $GLOBALS['comment'] instanceof WP_Comment ? $GLOBALS['comment'] : null );
					if ( ! $comment_obj ) {
						return false;
					}
					$parent_type = get_post_type( (int) $comment_obj->comment_post_ID );
					if ( ! $parent_type || ! in_array( $parent_type, $post_types, true ) ) {
						return false;
					}
				} else {
					$current_type = get_post_type();
					if ( ! $current_type || ! in_array( $current_type, $post_types, true ) ) {
						return false;
					}
				}
			}

			$term_ids = isset( $conditions['term_ids'] ) && is_array( $conditions['term_ids'] ) ? array_map( 'absint', $conditions['term_ids'] ) : array();
			if ( ! empty( $term_ids ) && ! in_array( $content_type, array( 'comment', 'product_review' ), true ) ) {
				$post_id = get_the_ID();

				if ( $post_id ) {
					$taxonomy = ! empty( $conditions['taxonomy'] ) ? sanitize_key( $conditions['taxonomy'] ) : '';
					if ( $taxonomy ) {
						if ( ! has_term( $term_ids, $taxonomy, $post_id ) ) {
							return false;
						}
					} elseif ( ! has_term( $term_ids, '', $post_id ) ) {
						return false;
					}
				} elseif ( is_category() || is_tag() || is_tax() ) {
					if ( ! in_array( (int) get_queried_object_id(), $term_ids, true ) ) {
						return false;
					}
				} else {
					return false;
				}
			}

			if ( ! WP_Ulike_Pro_WooCommerce::matches_product_conditions( $conditions, get_the_ID() ) ) {
				return false;
			}

			if ( 'topic' === $content_type && ! WP_Ulike_Pro_BbPress::matches_conditions( $conditions, WP_Ulike_Pro_BbPress::get_current_item_id() ) ) {
				return false;
			}

			$placement_group = $rule['placement_group'] ?? 'wordpress';
			if ( 'edd' === $placement_group && 'post' === $content_type ) {
				$current_type = get_post_type();
				if ( 'download' !== $current_type ) {
					return false;
				}
			}

			return (bool) apply_filters( 'wp_ulike_pro_display_automation_matches', true, $rule, $conditions, $comment );
		}

		/**
		 * Build page context map.
		 *
		 * @return array<string, bool>
		 */
		public static function get_context_map() {
			$contexts = apply_filters(
				'wp_ulike_auto_diplay_filter_list',
				array(
					'home'        => is_front_page() || is_home(),
					'single'      => is_singular(),
					'archive'     => is_archive(),
					'category'    => is_category(),
					'search'      => is_search(),
					'tag'         => is_tag(),
					'author'      => is_author(),
					'buddypress'  => function_exists( 'is_buddypress' ) && is_buddypress(),
					'bbpress'     => function_exists( 'is_bbpress' ) && is_bbpress(),
					'woocommerce' => function_exists( 'is_woocommerce' ) && is_woocommerce(),
				)
			);

			if ( class_exists( 'WP_Ulike_Pro_WooCommerce' ) ) {
				$contexts = array_merge( $contexts, WP_Ulike_Pro_WooCommerce::get_context_map() );
			}

			if ( class_exists( 'WP_Ulike_Pro_Easy_Digital_Downloads' ) ) {
				$contexts = array_merge( $contexts, WP_Ulike_Pro_Easy_Digital_Downloads::get_context_map() );
			}

			if ( class_exists( 'WP_Ulike_Pro_BuddyPress' ) ) {
				$contexts = array_merge( $contexts, WP_Ulike_Pro_BuddyPress::get_context_map() );
			}

			if ( class_exists( 'WP_Ulike_Pro_BbPress' ) ) {
				$contexts = array_merge( $contexts, WP_Ulike_Pro_BbPress::get_context_map() );
			}

			return apply_filters( 'wp_ulike_pro_display_automation_contexts', $contexts );
		}

		/**
		 * Get available placement groups for admin UI.
		 *
		 * @return array<string, array<string, array<string, mixed>>>
		 */
		public static function get_placement_groups() {
			$groups = array(
				'wordpress' => array(
					'label'       => esc_html__( 'WordPress', WP_ULIKE_PRO_DOMAIN ),
					'description' => esc_html__( 'Blog posts, pages, and standard site content.', WP_ULIKE_PRO_DOMAIN ),
					'icon'        => 'dashicons-wordpress',
					'placements'  => array(
						'the_content' => array(
							'label'       => esc_html__( 'Inside Post/Page Content', WP_ULIKE_PRO_DOMAIN ),
							'description' => esc_html__( 'Inserts the button into post or page content.', WP_ULIKE_PRO_DOMAIN ),
							'type'        => 'filter',
							'hook'        => 'the_content',
							'content_types' => array( 'post' ),
						),
						'the_excerpt' => array(
							'label'       => esc_html__( 'Inside Post Excerpt', WP_ULIKE_PRO_DOMAIN ),
							'description' => esc_html__( 'Useful for archive and blog listing templates.', WP_ULIKE_PRO_DOMAIN ),
							'type'        => 'filter',
							'hook'        => 'the_excerpt',
							'content_types' => array( 'post' ),
						),
						'comment_text' => array(
							'label'         => esc_html__( 'Below Each Comment', WP_ULIKE_PRO_DOMAIN ),
							'description'   => esc_html__( 'Inserts the like button under blog comments on posts and pages.', WP_ULIKE_PRO_DOMAIN ),
							'type'          => 'filter',
							'hook'          => 'comment_text',
							'content_types' => array( 'comment' ),
							'accepted_args' => 2,
						),
						'custom'      => array(
							'label'       => esc_html__( 'Custom Theme Hook', WP_ULIKE_PRO_DOMAIN ),
							'description' => esc_html__( 'Enter any WordPress action hook from your theme or plugin.', WP_ULIKE_PRO_DOMAIN ),
							'type'        => 'custom',
							'content_types' => array( 'post', 'comment', 'product_review', 'activity', 'topic' ),
						),
					),
				),
			);

			if ( WP_Ulike_Pro_WooCommerce::is_active() ) {
				$groups['woocommerce'] = array(
					'label'       => esc_html__( 'WooCommerce', WP_ULIKE_PRO_DOMAIN ),
					'description' => esc_html__( 'Online store product pages, shop listings, and cart areas.', WP_ULIKE_PRO_DOMAIN ),
					'icon'        => 'dashicons-cart',
					'placements'  => array(
						'wc_single_after_cart' => array(
							'label'       => esc_html__( 'Product Page — After Add to Cart', WP_ULIKE_PRO_DOMAIN ),
							'description' => esc_html__( 'Recommended for single product pages.', WP_ULIKE_PRO_DOMAIN ),
							'type'        => 'action',
							'hook'        => 'woocommerce_single_product_summary',
							'priority'    => 35,
						),
						'wc_single_after_price' => array(
							'label'       => esc_html__( 'Product Page — After Price', WP_ULIKE_PRO_DOMAIN ),
							'description' => esc_html__( 'Shows the button right after the product price.', WP_ULIKE_PRO_DOMAIN ),
							'type'        => 'action',
							'hook'        => 'woocommerce_single_product_summary',
							'priority'    => 11,
						),
						'wc_single_after_summary' => array(
							'label'       => esc_html__( 'Product Page — After Summary Block', WP_ULIKE_PRO_DOMAIN ),
							'description' => esc_html__( 'Shows after the entire product summary section.', WP_ULIKE_PRO_DOMAIN ),
							'type'        => 'action',
							'hook'        => 'woocommerce_after_single_product_summary',
							'priority'    => 10,
						),
						'wc_single_before_cart' => array(
							'label'       => esc_html__( 'Product Page — Before Add to Cart', WP_ULIKE_PRO_DOMAIN ),
							'description' => esc_html__( 'Shows above the quantity and add-to-cart controls.', WP_ULIKE_PRO_DOMAIN ),
							'type'        => 'action',
							'hook'        => 'woocommerce_single_product_summary',
							'priority'    => 25,
						),
						'wc_single_meta_end' => array(
							'label'       => esc_html__( 'Product Page — After Meta (SKU, Categories)', WP_ULIKE_PRO_DOMAIN ),
							'description' => esc_html__( 'Shows after product meta information.', WP_ULIKE_PRO_DOMAIN ),
							'type'        => 'action',
							'hook'        => 'woocommerce_product_meta_end',
						),
						'wc_shop_loop_item' => array(
							'label'       => esc_html__( 'Shop & Categories — Below Each Product', WP_ULIKE_PRO_DOMAIN ),
							'description' => esc_html__( 'Shows on shop, category, and tag archive loops.', WP_ULIKE_PRO_DOMAIN ),
							'type'        => 'action',
							'hook'        => 'woocommerce_after_shop_loop_item',
						),
						'wc_after_single_product' => array(
							'label'       => esc_html__( 'Product Page — After Product Area', WP_ULIKE_PRO_DOMAIN ),
							'description' => esc_html__( 'Shows below the full single product container.', WP_ULIKE_PRO_DOMAIN ),
							'type'        => 'action',
							'hook'        => 'woocommerce_after_single_product',
							'content_types' => array( 'post' ),
						),
						'wc_product_reviews' => array(
							'label'         => esc_html__( 'Product Reviews — Below Each Review', WP_ULIKE_PRO_DOMAIN ),
							'description'   => esc_html__( 'Adds likes on WooCommerce customer reviews (stored as comments).', WP_ULIKE_PRO_DOMAIN ),
							'type'          => 'filter',
							'hook'          => 'comment_text',
							'content_types' => array( 'product_review' ),
							'accepted_args' => 2,
						),
						'custom' => array(
							'label'       => esc_html__( 'Custom WooCommerce Hook', WP_ULIKE_PRO_DOMAIN ),
							'description' => esc_html__( 'Example: woocommerce_share, woocommerce_product_thumbnails', WP_ULIKE_PRO_DOMAIN ),
							'type'        => 'custom',
							'content_types' => array( 'post', 'product_review' ),
						),
					),
				);
			}

			if ( WP_Ulike_Pro_BuddyPress::is_active() ) {
				$groups['buddypress'] = array(
					'label'       => esc_html__( 'BuddyPress', WP_ULIKE_PRO_DOMAIN ),
					'description' => esc_html__( 'Activity updates and comments in the community stream.', WP_ULIKE_PRO_DOMAIN ),
					'icon'        => 'dashicons-groups',
					'placements'  => array(
						'bp_activity_meta' => array(
							'label'         => esc_html__( 'Activity Post — Meta Area', WP_ULIKE_PRO_DOMAIN ),
							'description' => esc_html__( 'Beside activity buttons (stream, profile, groups; includes AJAX load more).', WP_ULIKE_PRO_DOMAIN ),
							'type'          => 'action',
							'hook'          => 'bp_activity_entry_meta',
							'content_types' => array( 'activity' ),
						),
						'bp_activity_content' => array(
							'label'         => esc_html__( 'Activity Post — Content Area', WP_ULIKE_PRO_DOMAIN ),
							'description' => esc_html__( 'Below the activity text (includes AJAX load more and read more).', WP_ULIKE_PRO_DOMAIN ),
							'type'          => 'action',
							'hook'          => 'bp_activity_entry_content',
							'content_types' => array( 'activity' ),
						),
						'bp_activity_comment_options' => array(
							'label'         => esc_html__( 'Activity Comment — Meta Area', WP_ULIKE_PRO_DOMAIN ),
							'description' => esc_html__( 'Beside comment action buttons (same as WP ULike meta position).', WP_ULIKE_PRO_DOMAIN ),
							'type'          => 'action',
							'hook'          => 'bp_activity_comment_options',
							'content_types' => array( 'activity_comment' ),
						),
						'bp_activity_comment_content' => array(
							'label'         => esc_html__( 'Activity Comment — Inside Content', WP_ULIKE_PRO_DOMAIN ),
							'description' => esc_html__( 'Inside the comment text with top/bottom control (same as WP ULike content position).', WP_ULIKE_PRO_DOMAIN ),
							'type'          => 'filter',
							'hook'          => 'bp_activity_comment_content',
							'content_types' => array( 'activity_comment' ),
						),
						'custom' => array(
							'label'       => esc_html__( 'Custom BuddyPress Hook', WP_ULIKE_PRO_DOMAIN ),
							'description' => esc_html__( 'Use any BuddyPress action hook.', WP_ULIKE_PRO_DOMAIN ),
							'type'        => 'custom',
						),
					),
				);
			}

			if ( WP_Ulike_Pro_Easy_Digital_Downloads::is_active() ) {
				$groups['edd'] = array(
					'label'       => esc_html__( 'Easy Digital Downloads', WP_ULIKE_PRO_DOMAIN ),
					'description' => esc_html__( 'Single download pages and the main downloads shop/archive listing.', WP_ULIKE_PRO_DOMAIN ),
					'icon'        => 'dashicons-download',
					'placements'  => array(
						'edd_single_after_content' => array(
							'label'       => esc_html__( 'Single Download Page', WP_ULIKE_PRO_DOMAIN ),
							'description' => esc_html__( 'Shows on each individual download page (after the description / purchase area).', WP_ULIKE_PRO_DOMAIN ),
							'type'        => 'action',
							'hook'        => 'edd_after_download_content',
							'priority'    => 15,
						),
						'edd_shop_excerpt' => array(
							'label'         => esc_html__( 'Downloads Shop / Archive', WP_ULIKE_PRO_DOMAIN ),
							'description' => esc_html__( 'Shows on each download in shop listings — before the purchase button when available, otherwise after the title.', WP_ULIKE_PRO_DOMAIN ),
							'type'          => 'action',
							'hook'          => 'edd_purchase_link_top',
							'priority'      => 15,
							'content_types' => array( 'post' ),
						),
					),
				);
			}

			if ( WP_Ulike_Pro_BbPress::is_active() ) {
				$groups['bbpress'] = array(
					'label'       => esc_html__( 'bbPress', WP_ULIKE_PRO_DOMAIN ),
					'description' => esc_html__( 'Forum topics and replies with flexible theme hooks.', WP_ULIKE_PRO_DOMAIN ),
					'icon'        => 'dashicons-format-chat',
					'placements'  => array(
						'bbp_after_topic_title' => array(
							'label'         => esc_html__( 'Topic — After Title', WP_ULIKE_PRO_DOMAIN ),
							'description' => esc_html__( 'After the topic title in lists and single topics (recommended).', WP_ULIKE_PRO_DOMAIN ),
							'type'          => 'action',
							'hook'          => 'bbp_theme_after_topic_title',
							'content_types' => array( 'topic' ),
						),
						'bbp_after_topic' => array(
							'label'         => esc_html__( 'Topic — After Content', WP_ULIKE_PRO_DOMAIN ),
							'description' => esc_html__( 'Below the topic body when shown; on forum lists, after the topic meta line.', WP_ULIKE_PRO_DOMAIN ),
							'type'          => 'action',
							'hook'          => 'bbp_theme_after_topic_content',
							'content_types' => array( 'topic' ),
						),
						'bbp_before_topic' => array(
							'label'         => esc_html__( 'Topic — Before Content', WP_ULIKE_PRO_DOMAIN ),
							'description' => esc_html__( 'Above the topic body when shown; on forum lists, before the topic meta line.', WP_ULIKE_PRO_DOMAIN ),
							'type'          => 'action',
							'hook'          => 'bbp_theme_before_topic_content',
							'content_types' => array( 'topic' ),
						),
						'bbp_topic_content' => array(
							'label'         => esc_html__( 'Topic — Inside Content', WP_ULIKE_PRO_DOMAIN ),
							'description' => esc_html__( 'Inside the single topic opening post (lead topic or first post when lead topic is off).', WP_ULIKE_PRO_DOMAIN ),
							'type'          => 'filter',
							'hook'          => 'bbp_get_topic_content',
							'content_types' => array( 'topic' ),
						),
						'bbp_before_reply' => array(
							'label'         => esc_html__( 'Reply — Before Content', WP_ULIKE_PRO_DOMAIN ),
							'description' => esc_html__( 'Above each reply in a topic thread.', WP_ULIKE_PRO_DOMAIN ),
							'type'          => 'action',
							'hook'          => 'bbp_theme_before_reply_content',
							'content_types' => array( 'topic' ),
						),
						'bbp_after_reply' => array(
							'label'         => esc_html__( 'Reply — After Content', WP_ULIKE_PRO_DOMAIN ),
							'description' => esc_html__( 'Below each reply in a topic thread.', WP_ULIKE_PRO_DOMAIN ),
							'type'          => 'action',
							'hook'          => 'bbp_theme_after_reply_content',
							'content_types' => array( 'topic' ),
						),
						'custom' => array(
							'label'       => esc_html__( 'Custom bbPress Hook', WP_ULIKE_PRO_DOMAIN ),
							'description' => esc_html__( 'Use any bbPress theme hook.', WP_ULIKE_PRO_DOMAIN ),
							'type'        => 'custom',
						),
					),
				);
			}

			return apply_filters( 'wp_ulike_pro_display_automation_placements', $groups );
		}

		/**
		 * Whether basic Automatic Display is enabled in settings for a content type.
		 *
		 * Reads the same option as {@see wp_ulike_setting_repo::isAutoDisplayOn()} without
		 * runtime frontend overrides, so admin notices reflect the saved setting.
		 *
		 * @param string $type Content type slug (post, comment, activity, topic).
		 * @return bool
		 */
		private static function is_basic_auto_display_setting_enabled( $type ) {
			if ( ! class_exists( 'wp_ulike_setting_type' ) || ! function_exists( 'wp_ulike_get_option' ) ) {
				return false;
			}

			$group = wp_ulike_setting_type::get_instance( $type )->getSettingKey();
			if ( ! $group ) {
				return false;
			}

			$value = wp_ulike_get_option( $group . '|enable_auto_display' );
			if ( null === $value ) {
				$value = true;
			}

			return function_exists( 'wp_ulike_is_true' ) ? wp_ulike_is_true( $value ) : (bool) $value;
		}

		/**
		 * Labels for content types that have basic Automatic Display enabled.
		 *
		 * @return array<int, string>
		 */
		public static function get_active_basic_auto_display_labels() {
			$types = array(
				'post'    => esc_html__( 'Posts', WP_ULIKE_PRO_DOMAIN ),
				'comment' => esc_html__( 'Comments', WP_ULIKE_PRO_DOMAIN ),
			);

			if ( WP_Ulike_Pro_BuddyPress::is_active() ) {
				$types['activity'] = esc_html__( 'BuddyPress Activities', WP_ULIKE_PRO_DOMAIN );
				if ( function_exists( 'wp_ulike_get_option' ) && wp_ulike_is_true( wp_ulike_get_option( 'buddypress_group|enable_comments', false ) ) ) {
					$types['activity_comment'] = esc_html__( 'BuddyPress Activity Comments', WP_ULIKE_PRO_DOMAIN );
				}
			}

			if ( function_exists( 'is_bbpress' ) ) {
				$types['topic'] = esc_html__( 'bbPress', WP_ULIKE_PRO_DOMAIN );
			}

			$active = array();

			foreach ( $types as $type => $label ) {
				if ( 'activity_comment' === $type ) {
					if ( class_exists( 'wp_ulike_setting_repo' ) && wp_ulike_setting_repo::isActivityCommentAutoDisplayOn() ) {
						$active[] = $label;
					}
					continue;
				}

				if ( self::is_basic_auto_display_setting_enabled( $type ) ) {
					$active[] = $label;
				}
			}

			return $active;
		}

		/**
		 * Whether basic Automatic Display is enabled for any supported content type.
		 *
		 * @return bool
		 */
		public static function has_any_basic_auto_display_enabled() {
			return ! empty( self::get_active_basic_auto_display_labels() );
		}

		/**
		 * Get placements for a specific integration group.
		 *
		 * @param string $group_key Integration key.
		 * @return array<string, array<string, mixed>>
		 */
		public static function get_placements_for_group( $group_key, $content_type = '' ) {
			$groups = self::get_placement_groups();
			$placements = array();

			if ( isset( $groups[ $group_key ]['placements'] ) ) {
				$placements = $groups[ $group_key ]['placements'];
			} else {
				$placements = $groups['wordpress']['placements'] ?? array();
			}

			if ( empty( $content_type ) ) {
				return $placements;
			}

			return array_filter(
				$placements,
				static function( $placement ) use ( $content_type ) {
					$allowed = $placement['content_types'] ?? array();
					if ( empty( $allowed ) ) {
						return in_array( $content_type, array( 'post', 'activity', 'activity_comment', 'topic' ), true );
					}
					return in_array( $content_type, $allowed, true );
				}
			);
		}

		/**
		 * Get integration group description.
		 *
		 * @param string $group_key Integration key.
		 * @return string
		 */
		public static function get_group_description( $group_key ) {
			$groups = self::get_placement_groups();
			return $groups[ $group_key ]['description'] ?? '';
		}

		/**
		 * Resolve placement config by key.
		 *
		 * @param string $placement Placement key.
		 * @return array<string, mixed>
		 */
		public static function get_placement_config( $placement ) {
			if ( null === self::$placement_config_index ) {
				self::$placement_config_index = array();

				foreach ( self::get_placement_groups() as $group ) {
					if ( empty( $group['placements'] ) || ! is_array( $group['placements'] ) ) {
						continue;
					}

					foreach ( $group['placements'] as $key => $config ) {
						self::$placement_config_index[ $key ] = $config;
					}
				}
			}

			if ( ! isset( self::$placement_config_index[ $placement ] ) ) {
				return array();
			}

			$config = self::$placement_config_index[ $placement ];

			if ( 'custom' === $placement || 'custom' === ( $config['type'] ?? '' ) ) {
				return array();
			}

			if ( 'filter' !== ( $config['type'] ?? '' ) ) {
				return $config;
			}

			if ( in_array( $config['hook'] ?? '', array( 'comment_text', 'bbp_get_topic_content' ), true ) ) {
				$config['requires_loop']         = false;
				$config['requires_main_query'] = false;
				$config['accepted_args']       = 2;
			} else {
				$config['requires_loop']        = true;
				$config['requires_main_query']  = true;
				$config['accepted_args']        = 1;
			}

			return $config;
		}

		/**
		 * Get context labels for admin UI.
		 *
		 * @return array<string, string>
		 */
		public static function get_context_options() {
			$options = array(
				'home'        => esc_html__( 'Blog Home', WP_ULIKE_PRO_DOMAIN ),
				'single'      => esc_html__( 'Singular Pages', WP_ULIKE_PRO_DOMAIN ),
				'archive'     => esc_html__( 'Archives', WP_ULIKE_PRO_DOMAIN ),
				'category'    => esc_html__( 'Categories', WP_ULIKE_PRO_DOMAIN ),
				'search'      => esc_html__( 'Search Results', WP_ULIKE_PRO_DOMAIN ),
				'tag'         => esc_html__( 'Tags', WP_ULIKE_PRO_DOMAIN ),
				'author'      => esc_html__( 'Author Pages', WP_ULIKE_PRO_DOMAIN ),
				'buddypress'            => esc_html__( 'BuddyPress (all)', WP_ULIKE_PRO_DOMAIN ),
				'bp_activity_directory' => esc_html__( 'Activity Directory', WP_ULIKE_PRO_DOMAIN ),
				'bp_member_profile'   => esc_html__( 'Member Profiles', WP_ULIKE_PRO_DOMAIN ),
				'bp_groups'             => esc_html__( 'Groups', WP_ULIKE_PRO_DOMAIN ),
				'bbpress'               => esc_html__( 'bbPress (all)', WP_ULIKE_PRO_DOMAIN ),
				'bbp_forum'             => esc_html__( 'Forum Pages', WP_ULIKE_PRO_DOMAIN ),
				'bbp_topic'             => esc_html__( 'Single Topic', WP_ULIKE_PRO_DOMAIN ),
				'bbp_reply'             => esc_html__( 'Single Reply', WP_ULIKE_PRO_DOMAIN ),
				'bbp_topic_archive'   => esc_html__( 'Topic Archives', WP_ULIKE_PRO_DOMAIN ),
				'bbp_search'            => esc_html__( 'Forum Search', WP_ULIKE_PRO_DOMAIN ),
				'woocommerce' => esc_html__( 'WooCommerce Pages', WP_ULIKE_PRO_DOMAIN ),
			);

			if ( WP_Ulike_Pro_WooCommerce::is_active() ) {
				$options['woocommerce_shop']     = esc_html__( 'WooCommerce Shop', WP_ULIKE_PRO_DOMAIN );
				$options['woocommerce_product']  = esc_html__( 'Single Product', WP_ULIKE_PRO_DOMAIN );
				$options['woocommerce_category'] = esc_html__( 'Product Category', WP_ULIKE_PRO_DOMAIN );
				$options['woocommerce_tag']      = esc_html__( 'Product Tag', WP_ULIKE_PRO_DOMAIN );
				$options['woocommerce_cart']     = esc_html__( 'Cart', WP_ULIKE_PRO_DOMAIN );
				$options['woocommerce_checkout'] = esc_html__( 'Checkout', WP_ULIKE_PRO_DOMAIN );
				$options['woocommerce_account']  = esc_html__( 'My Account', WP_ULIKE_PRO_DOMAIN );
			}

			if ( WP_Ulike_Pro_Easy_Digital_Downloads::is_active() ) {
				$options['edd_single_download'] = esc_html__( 'Single Download Page', WP_ULIKE_PRO_DOMAIN );
				$options['edd_download_shop']   = esc_html__( 'Downloads Shop / Archive', WP_ULIKE_PRO_DOMAIN );
			}

			return apply_filters( 'wp_ulike_pro_display_automation_context_options', $options );
		}

		/**
		 * Context options scoped to a content type.
		 *
		 * @param string $group_key    Integration group.
		 * @param string $content_type Content type slug.
		 * @return array<string, string>
		 */
		public static function get_context_options_for_content( $group_key, $content_type ) {
			$options = self::get_context_options();
			$allowed = array();

			switch ( $content_type ) {
				case 'product_review':
					$allowed = array( 'single', 'woocommerce', 'woocommerce_product' );
					break;

				case 'comment':
					$allowed = array( 'home', 'single', 'archive', 'category', 'search', 'tag', 'author' );
					break;

				case 'activity':
				case 'activity_comment':
					$allowed = array( 'buddypress', 'bp_activity_directory', 'bp_member_profile', 'bp_groups' );
					break;

				case 'topic':
					$allowed = array( 'bbpress', 'bbp_forum', 'bbp_topic', 'bbp_reply', 'bbp_topic_archive', 'bbp_search' );
					break;

				case 'post':
				default:
					if ( 'woocommerce' === $group_key ) {
						$allowed = array(
							'woocommerce',
							'woocommerce_shop',
							'woocommerce_product',
							'woocommerce_category',
							'woocommerce_tag',
							'woocommerce_cart',
							'woocommerce_checkout',
							'woocommerce_account',
							'single',
							'archive',
							'home',
						);
					} elseif ( 'edd' === $group_key ) {
						$allowed = array(
							'edd_single_download',
							'edd_download_shop',
						);
					} elseif ( 'buddypress' === $group_key ) {
						$allowed = array_keys( array_intersect_key( self::get_context_options(), array_flip( array( 'buddypress', 'bp_activity_directory', 'bp_member_profile', 'bp_groups' ) ) ) );
					} elseif ( 'bbpress' === $group_key ) {
						$allowed = array_keys( array_intersect_key( self::get_context_options(), array_flip( array( 'bbpress', 'bbp_forum', 'bbp_topic', 'bbp_reply', 'bbp_topic_archive', 'bbp_search' ) ) ) );
					} else {
						$allowed = array_keys( $options );
					}
					break;
			}

			return array_intersect_key(
				$options,
				array_flip( $allowed )
			);
		}

		/**
		 * Admin UI copy keyed by content type.
		 *
		 * @return array<string, array<string, string>>
		 */
		public static function get_ui_copy_profiles() {
			return array(
				'post' => array(
					'step_content_title'  => esc_html__( 'What should be liked?', WP_ULIKE_PRO_DOMAIN ),
					'step_content_desc'   => esc_html__( 'Choose whether this rule targets posts, pages, or products.', WP_ULIKE_PRO_DOMAIN ),
					'content_type_label'  => esc_html__( 'Apply to', WP_ULIKE_PRO_DOMAIN ),
					'step_placement_title' => esc_html__( 'Where should the button appear?', WP_ULIKE_PRO_DOMAIN ),
					'step_placement_desc'  => esc_html__( 'Pick the exact area on the page where visitors will see the like button.', WP_ULIKE_PRO_DOMAIN ),
					'placement_label'     => esc_html__( 'Button position', WP_ULIKE_PRO_DOMAIN ),
				),
				'comment' => array(
					'step_content_title'  => esc_html__( 'What should be liked?', WP_ULIKE_PRO_DOMAIN ),
					'step_content_desc'   => esc_html__( 'This rule will add likes on blog comments (not product reviews).', WP_ULIKE_PRO_DOMAIN ),
					'content_type_label'  => esc_html__( 'Apply to', WP_ULIKE_PRO_DOMAIN ),
					'step_placement_title' => esc_html__( 'Where on comments?', WP_ULIKE_PRO_DOMAIN ),
					'step_placement_desc'  => esc_html__( 'Usually you want the button directly below each comment.', WP_ULIKE_PRO_DOMAIN ),
					'placement_label'     => esc_html__( 'Comment position', WP_ULIKE_PRO_DOMAIN ),
				),
				'product_review' => array(
					'step_content_title'  => esc_html__( 'What should be liked?', WP_ULIKE_PRO_DOMAIN ),
					'step_content_desc'   => esc_html__( 'WooCommerce reviews are stored as comments on products.', WP_ULIKE_PRO_DOMAIN ),
					'content_type_label'  => esc_html__( 'Apply to', WP_ULIKE_PRO_DOMAIN ),
					'step_placement_title' => esc_html__( 'Where on reviews?', WP_ULIKE_PRO_DOMAIN ),
					'step_placement_desc'  => esc_html__( 'The button appears below each customer review on the product page.', WP_ULIKE_PRO_DOMAIN ),
					'placement_label'     => esc_html__( 'Review position', WP_ULIKE_PRO_DOMAIN ),
				),
				'activity' => array(
					'step_content_title'  => esc_html__( 'What should be liked?', WP_ULIKE_PRO_DOMAIN ),
					'step_content_desc'   => esc_html__( 'Top-level BuddyPress activity updates (not comments on activities).', WP_ULIKE_PRO_DOMAIN ),
					'content_type_label'  => esc_html__( 'Apply to', WP_ULIKE_PRO_DOMAIN ),
					'step_placement_title' => esc_html__( 'Where on activities?', WP_ULIKE_PRO_DOMAIN ),
					'step_placement_desc'  => esc_html__( 'Meta area or content area — same choices as WP ULike BuddyPress auto display.', WP_ULIKE_PRO_DOMAIN ),
					'placement_label'     => esc_html__( 'Activity position', WP_ULIKE_PRO_DOMAIN ),
				),
				'activity_comment' => array(
					'step_content_title'  => esc_html__( 'What should be liked?', WP_ULIKE_PRO_DOMAIN ),
					'step_content_desc'   => esc_html__( 'Comments on BuddyPress activity items (requires BuddyPress comments enabled in WP ULike settings).', WP_ULIKE_PRO_DOMAIN ),
					'content_type_label'  => esc_html__( 'Apply to', WP_ULIKE_PRO_DOMAIN ),
					'step_placement_title' => esc_html__( 'Where on activity comments?', WP_ULIKE_PRO_DOMAIN ),
					'step_placement_desc'  => esc_html__( 'Meta area or inside content — same choices as WP ULike activity comment auto display.', WP_ULIKE_PRO_DOMAIN ),
					'placement_label'     => esc_html__( 'Comment position', WP_ULIKE_PRO_DOMAIN ),
				),
				'topic' => array(
					'step_content_title'  => esc_html__( 'What should be liked?', WP_ULIKE_PRO_DOMAIN ),
					'step_content_desc'   => esc_html__( 'Forum topics and replies powered by bbPress.', WP_ULIKE_PRO_DOMAIN ),
					'content_type_label'  => esc_html__( 'Apply to', WP_ULIKE_PRO_DOMAIN ),
					'step_placement_title' => esc_html__( 'Where on forum content?', WP_ULIKE_PRO_DOMAIN ),
					'step_placement_desc'  => esc_html__( 'Choose where the button appears on topics or replies.', WP_ULIKE_PRO_DOMAIN ),
					'placement_label'     => esc_html__( 'Forum position', WP_ULIKE_PRO_DOMAIN ),
				),
			);
		}

		/**
		 * Sanitize placement group key.
		 *
		 * @param string $group_key Raw group key.
		 * @return string
		 */
		public static function sanitize_placement_group( $group_key ) {
			return WP_Ulike_Pro_Display_Automation_Sanitizer::sanitize_placement_group( $group_key );
		}

		/**
		 * Admin URL for Content Types settings (Optiwich deep link).
		 *
		 * @param string $section Optional section id (e.g. posts_group, comments_group).
		 * @return string
		 */
		public static function get_content_types_settings_url( $section = 'content-types' ) {
			$args = array(
				'page'           => 'wp-ulike-settings',
				'settings-page'  => 'content-types',
			);

			if ( $section && 'content-types' !== $section ) {
				$args['settings-section'] = sanitize_key( $section );
			}

			return add_query_arg( $args, admin_url( 'admin.php' ) );
		}

		/**
		 * Sanitize submitted rules.
		 *
		 * @param array $rules Raw rules.
		 * @return array<int, array<string, mixed>>
		 */
		public static function sanitize_rules( $rules ) {
			return WP_Ulike_Pro_Display_Automation_Sanitizer::sanitize_rules( $rules );
		}

		/**
		 * Base conditions array for preset / starter rules.
		 *
		 * @param array<string, mixed> $overrides Optional condition overrides.
		 * @return array<string, mixed>
		 */
		private static function get_preset_conditions( $overrides = array() ) {
			$conditions = array(
				'show_on'     => array(),
				'hide_on'     => array(),
				'post_types'  => array(),
				'taxonomy'    => '',
				'term_ids'    => array(),
				'woocommerce' => array(
					'on_sale'      => '',
					'featured'     => '',
					'product_type' => array(),
				),
				'edd' => array(
					'free'             => '',
					'variable_pricing' => '',
				),
				'bbpress' => array(
					'item_type' => '',
					'forum_ids' => array(),
					'topic_ids' => array(),
				),
			);

			return array_replace_recursive( $conditions, $overrides );
		}

		/**
		 * Quick-start presets shown in the empty state.
		 *
		 * @return array<int, array<string, mixed>>
		 */
		public static function get_quick_start_presets() {
			$presets = array(
				array(
					'id'          => 'blog_posts',
					'label'       => esc_html__( 'Blog Posts', WP_ULIKE_PRO_DOMAIN ),
					'description' => esc_html__( 'Like button at the end of single blog posts.', WP_ULIKE_PRO_DOMAIN ),
					'icon'        => 'dashicons-admin-post',
					'rule'        => array(
						'id'               => 'rule_blog_posts',
						'enabled'          => true,
						'title'            => esc_html__( 'Blog Posts', WP_ULIKE_PRO_DOMAIN ),
						'content_type'     => 'post',
						'placement'        => 'the_content',
						'placement_group'  => 'wordpress',
						'override_default' => true,
						'conditions'       => self::get_preset_conditions(
							array(
								'show_on'    => array( 'single' ),
								'post_types' => array( 'post' ),
							)
						),
					),
				),
				array(
					'id'          => 'blog_archives',
					'label'       => esc_html__( 'Blog Archives', WP_ULIKE_PRO_DOMAIN ),
					'description' => esc_html__( 'Compact likes in blog, category, and tag listings.', WP_ULIKE_PRO_DOMAIN ),
					'icon'        => 'dashicons-grid-view',
					'rule'        => array(
						'id'               => 'rule_blog_archives',
						'enabled'          => true,
						'title'            => esc_html__( 'Blog Archives', WP_ULIKE_PRO_DOMAIN ),
						'content_type'     => 'post',
						'placement'        => 'the_excerpt',
						'placement_group'  => 'wordpress',
						'override_default' => false,
						'display_counter'  => 'no',
						'conditions'       => self::get_preset_conditions(
							array(
								'show_on' => array( 'home', 'archive', 'category', 'tag' ),
								'post_types' => array( 'post' ),
							)
						),
					),
				),
				array(
					'id'          => 'pages',
					'label'       => esc_html__( 'Pages', WP_ULIKE_PRO_DOMAIN ),
					'description' => esc_html__( 'Like button on static pages.', WP_ULIKE_PRO_DOMAIN ),
					'icon'        => 'dashicons-admin-page',
					'rule'        => array(
						'id'               => 'rule_pages',
						'enabled'          => true,
						'title'            => esc_html__( 'Pages', WP_ULIKE_PRO_DOMAIN ),
						'content_type'     => 'post',
						'placement'        => 'the_content',
						'placement_group'  => 'wordpress',
						'override_default' => true,
						'conditions'       => self::get_preset_conditions(
							array(
								'show_on'    => array( 'single' ),
								'post_types' => array( 'page' ),
							)
						),
					),
				),
				array(
					'id'          => 'comments',
					'label'       => esc_html__( 'Comments', WP_ULIKE_PRO_DOMAIN ),
					'description' => esc_html__( 'Likes below each blog or page comment.', WP_ULIKE_PRO_DOMAIN ),
					'icon'        => 'dashicons-admin-comments',
					'rule'        => array(
						'id'               => 'rule_comments',
						'enabled'          => true,
						'title'            => esc_html__( 'Comments', WP_ULIKE_PRO_DOMAIN ),
						'content_type'     => 'comment',
						'placement'        => 'comment_text',
						'placement_group'  => 'wordpress',
						'override_default' => false,
						'conditions'       => self::get_preset_conditions(),
					),
				),
			);

			if ( WP_Ulike_Pro_WooCommerce::is_active() ) {
				$presets[] = array(
					'id'          => 'wc_single',
					'label'       => esc_html__( 'WooCommerce Product', WP_ULIKE_PRO_DOMAIN ),
					'description' => esc_html__( 'After add to cart on single product pages.', WP_ULIKE_PRO_DOMAIN ),
					'icon'        => 'dashicons-cart',
					'rule'        => array(
						'id'               => 'rule_wc_single',
						'enabled'          => false,
						'title'            => esc_html__( 'WooCommerce Single Product', WP_ULIKE_PRO_DOMAIN ),
						'content_type'     => 'post',
						'placement'        => 'wc_single_after_cart',
						'placement_group'  => 'woocommerce',
						'override_default' => true,
						'conditions'       => self::get_preset_conditions(
							array(
								'show_on'    => array( 'woocommerce_product' ),
								'post_types' => array( 'product' ),
							)
						),
					),
				);

				$presets[] = array(
					'id'          => 'wc_shop',
					'label'       => esc_html__( 'WooCommerce Shop', WP_ULIKE_PRO_DOMAIN ),
					'description' => esc_html__( 'Below each product in shop and category loops.', WP_ULIKE_PRO_DOMAIN ),
					'icon'        => 'dashicons-store',
					'rule'        => array(
						'id'               => 'rule_wc_shop',
						'enabled'          => false,
						'title'            => esc_html__( 'WooCommerce Shop Loop', WP_ULIKE_PRO_DOMAIN ),
						'content_type'     => 'post',
						'placement'        => 'wc_shop_loop_item',
						'placement_group'  => 'woocommerce',
						'override_default' => false,
						'display_counter'  => 'no',
						'conditions'       => self::get_preset_conditions(
							array(
								'show_on'    => array( 'woocommerce_shop', 'woocommerce_category', 'woocommerce_tag' ),
								'post_types' => array( 'product' ),
							)
						),
					),
				);

				$presets[] = array(
					'id'          => 'wc_reviews',
					'label'       => esc_html__( 'WooCommerce Reviews', WP_ULIKE_PRO_DOMAIN ),
					'description' => esc_html__( 'Likes below each customer review on product pages.', WP_ULIKE_PRO_DOMAIN ),
					'icon'        => 'dashicons-star-filled',
					'rule'        => array(
						'id'               => 'rule_wc_reviews',
						'enabled'          => false,
						'title'            => esc_html__( 'WooCommerce Reviews', WP_ULIKE_PRO_DOMAIN ),
						'content_type'     => 'product_review',
						'placement'        => 'wc_product_reviews',
						'placement_group'  => 'woocommerce',
						'override_default' => false,
						'conditions'       => self::get_preset_conditions(
							array(
								'show_on' => array( 'woocommerce_product' ),
							)
						),
					),
				);
			}

			if ( WP_Ulike_Pro_Easy_Digital_Downloads::is_active() ) {
				$presets[] = array(
					'id'          => 'edd_single',
					'label'       => esc_html__( 'EDD Download', WP_ULIKE_PRO_DOMAIN ),
					'description' => esc_html__( 'On each single download page.', WP_ULIKE_PRO_DOMAIN ),
					'icon'        => 'dashicons-download',
					'rule'        => array(
						'id'               => 'rule_edd_single',
						'enabled'          => false,
						'title'            => esc_html__( 'EDD Single Download', WP_ULIKE_PRO_DOMAIN ),
						'content_type'     => 'post',
						'placement'        => 'edd_single_after_content',
						'placement_group'  => 'edd',
						'override_default' => true,
						'conditions'       => self::get_preset_conditions(
							array(
								'show_on' => array( 'edd_single_download' ),
							)
						),
					),
				);

				$presets[] = array(
					'id'          => 'edd_shop',
					'label'       => esc_html__( 'EDD Shop', WP_ULIKE_PRO_DOMAIN ),
					'description' => esc_html__( 'In the downloads shop and archive listings.', WP_ULIKE_PRO_DOMAIN ),
					'icon'        => 'dashicons-list-view',
					'rule'        => array(
						'id'               => 'rule_edd_shop',
						'enabled'          => false,
						'title'            => esc_html__( 'EDD Downloads Shop', WP_ULIKE_PRO_DOMAIN ),
						'content_type'     => 'post',
						'placement'        => 'edd_shop_excerpt',
						'placement_group'  => 'edd',
						'override_default' => false,
						'display_counter'  => 'no',
						'conditions'       => self::get_preset_conditions(
							array(
								'show_on' => array( 'edd_download_shop' ),
							)
						),
					),
				);
			}

			if ( WP_Ulike_Pro_BuddyPress::is_active() ) {
				$presets[] = array(
					'id'          => 'bp_activity',
					'label'       => esc_html__( 'BuddyPress Activity', WP_ULIKE_PRO_DOMAIN ),
					'description' => esc_html__( 'Beside activity stream buttons.', WP_ULIKE_PRO_DOMAIN ),
					'icon'        => 'dashicons-groups',
					'rule'        => array(
						'id'               => 'rule_bp_activity',
						'enabled'          => false,
						'title'            => esc_html__( 'BuddyPress Activity', WP_ULIKE_PRO_DOMAIN ),
						'content_type'     => 'activity',
						'placement'        => 'bp_activity_meta',
						'placement_group'  => 'buddypress',
						'override_default' => false,
						'conditions'       => self::get_preset_conditions(
							array(
								'show_on' => array( 'buddypress', 'bp_activity_directory', 'bp_member_profile', 'bp_groups' ),
							)
						),
					),
				);
			}

			if ( WP_Ulike_Pro_BbPress::is_active() ) {
				$presets[] = array(
					'id'          => 'bbp_topics',
					'label'       => esc_html__( 'bbPress Topics', WP_ULIKE_PRO_DOMAIN ),
					'description' => esc_html__( 'After topic titles in forums and threads.', WP_ULIKE_PRO_DOMAIN ),
					'icon'        => 'dashicons-format-chat',
					'rule'        => array(
						'id'               => 'rule_bbp_topics',
						'enabled'          => false,
						'title'            => esc_html__( 'bbPress Topics', WP_ULIKE_PRO_DOMAIN ),
						'content_type'     => 'topic',
						'placement'        => 'bbp_after_topic_title',
						'placement_group'  => 'bbpress',
						'override_default' => false,
						'conditions'       => self::get_preset_conditions(
							array(
								'show_on' => array( 'bbpress', 'bbp_forum', 'bbp_topic', 'bbp_topic_archive' ),
							)
						),
					),
				);
			}

			return apply_filters( 'wp_ulike_pro_display_automation_quick_start_presets', $presets );
		}

		/**
		 * Merge a preset rule with blank defaults.
		 *
		 * @param array<string, mixed> $preset_rule Partial rule from a preset.
		 * @return array<string, mixed>
		 */
		public static function merge_preset_rule( $preset_rule ) {
			$rule = self::get_blank_rule();

			return array_replace_recursive( $rule, $preset_rule );
		}

		/**
		 * Default starter rules for new installs.
		 *
		 * @return array<int, array<string, mixed>>
		 */
		public static function get_default_rules() {
			$rules = array_map(
				function ( $preset ) {
					return self::merge_preset_rule( $preset['rule'] );
				},
				self::get_quick_start_presets()
			);

			return apply_filters( 'wp_ulike_pro_display_automation_default_rules', $rules );
		}

		/**
		 * Blank rule used as admin starter template.
		 *
		 * @return array<string, mixed>
		 */
		public static function get_blank_rule() {
			return array(
				'id'               => 'rule_new',
				'enabled'          => false,
				'title'            => esc_html__( 'New Rule', WP_ULIKE_PRO_DOMAIN ),
				'content_type'     => 'post',
				'placement'        => 'the_content',
				'placement_group'  => 'wordpress',
				'custom_hook'      => '',
				'hook_priority'    => 10,
				'priority'         => 10,
				'position'         => 'bottom',
				'override_default' => false,
				'template'         => '',
				'display_counter'  => '',
				'display_likers'   => '',
				'likers_style'     => '',
				'engagement_reactions'    => array(),
				'engagement_picker_style' => '',
				'conditions'       => array(
					'show_on'     => array(),
					'hide_on'     => array(),
					'post_types'  => array(),
					'taxonomy'    => '',
					'term_ids'    => array(),
					'woocommerce' => array(
						'on_sale'      => '',
						'featured'     => '',
						'product_type' => array(),
					),
					'edd' => array(
						'free'             => '',
						'variable_pricing' => '',
					),
				),
			);
		}

		/**
		 * Rules prepared for admin UI (saved rules only — empty on first visit).
		 *
		 * @return array<int, array<string, mixed>>
		 */
		public static function get_rules_for_admin() {
			$rules = self::get_rules();
			return is_array( $rules ) ? $rules : array();
		}

		/**
		 * Whether display rules have been saved at least once.
		 *
		 * @return bool
		 */
		public static function has_saved_rules() {
			$rules = get_option( self::OPTION_KEY, null );
			return is_array( $rules ) && ! empty( $rules );
		}

		/**
		 * Content type options scoped to an integration group.
		 *
		 * @param string $group_key Integration key.
		 * @return array<string, string>
		 */
		public static function get_content_types_for_group( $group_key ) {
			$types = array(
				'post' => esc_html__( 'Posts & Pages', WP_ULIKE_PRO_DOMAIN ),
			);

			switch ( $group_key ) {
				case 'woocommerce':
					$types = array(
						'post'           => esc_html__( 'Products', WP_ULIKE_PRO_DOMAIN ),
						'product_review' => esc_html__( 'Product Reviews', WP_ULIKE_PRO_DOMAIN ),
					);
					break;

				case 'edd':
					$types = array(
						'post' => esc_html__( 'Downloads', WP_ULIKE_PRO_DOMAIN ),
					);
					break;

				case 'buddypress':
					$types = array(
						'activity'         => esc_html__( 'Activity Posts', WP_ULIKE_PRO_DOMAIN ),
						'activity_comment' => esc_html__( 'Activity Comments', WP_ULIKE_PRO_DOMAIN ),
					);
					break;

				case 'bbpress':
					$types = array(
						'topic' => esc_html__( 'Forum Topics & Replies', WP_ULIKE_PRO_DOMAIN ),
					);
					break;

				case 'wordpress':
				default:
					$types = array(
						'post'    => esc_html__( 'Posts & Pages', WP_ULIKE_PRO_DOMAIN ),
						'comment' => esc_html__( 'Comments', WP_ULIKE_PRO_DOMAIN ),
					);
					break;
			}

			return apply_filters( 'wp_ulike_pro_display_automation_content_types', $types, $group_key );
		}

		/**
		 * Sanitize yes/no tri-state option (empty = inherit global settings).
		 *
		 * @param string $value Raw value.
		 * @return string
		 */
		public static function sanitize_tri_state_bool( $value ) {
			return WP_Ulike_Pro_Display_Automation_Sanitizer::sanitize_tri_state_bool( $value );
		}

		/**
		 * Sanitize likers box style for a display rule.
		 *
		 * @param string $value           Raw style value.
		 * @param string $display_likers  Likers visibility tri-state.
		 * @return string
		 */
		public static function sanitize_likers_style( $value, $display_likers = '' ) {
			return WP_Ulike_Pro_Display_Automation_Sanitizer::sanitize_likers_style( $value, $display_likers );
		}

		/**
		 * Sanitize emoji reaction slugs for a display rule (empty = inherit settings).
		 *
		 * @param mixed $value Raw value.
		 * @return string[]
		 */
		public static function sanitize_engagement_reactions( $value ) {
			return WP_Ulike_Pro_Display_Automation_Sanitizer::sanitize_engagement_reactions( $value );
		}

		/**
		 * Sanitize reaction picker style for a display rule (empty = inherit settings).
		 *
		 * @param string $value Raw value.
		 * @return string
		 */
		public static function sanitize_engagement_picker_style( $value ) {
			return WP_Ulike_Pro_Display_Automation_Sanitizer::sanitize_engagement_picker_style( $value );
		}

		/**
		 * Map display automation content type to WP ULike item type slug.
		 *
		 * @param string $content_type Rule content type.
		 * @return string
		 */
		public static function map_rule_item_type( $content_type ) {
			$map = array(
				'post'            => 'post',
				'comment'         => 'comment',
				'product_review'  => 'comment',
				'activity'        => 'activity',
				'activity_comment'=> 'activity',
				'topic'           => 'topic',
			);

			return isset( $map[ $content_type ] ) ? $map[ $content_type ] : 'post';
		}

		/**
		 * Reaction checkbox options keyed by display automation content type.
		 *
		 * @return array<string, array<string, string>>
		 */
		public static function get_engagement_reactions_admin_options() {
			if ( ! class_exists( 'WP_Ulike_Pro_Engagement_Settings' ) ) {
				return array();
			}

			$content_types = array( 'post', 'comment', 'product_review', 'activity', 'activity_comment', 'topic' );
			$options       = array();

			foreach ( $content_types as $content_type ) {
				$item_type = self::map_rule_item_type( $content_type );
				$rows      = array();

				foreach ( WP_Ulike_Pro_Engagement_Settings::get_configured_reactions( $item_type ) as $reaction ) {
					if ( empty( $reaction['slug'] ) || empty( $reaction['emoji'] ) ) {
						continue;
					}

					$rows[] = array(
						'slug'  => $reaction['slug'],
						'emoji' => $reaction['emoji'],
						'label' => wp_strip_all_tags( (string) ( $reaction['label'] ?? '' ) ),
					);
				}

				$options[ $content_type ] = $rows;
			}

			return $options;
		}

		/**
		 * Build engagement context overrides from a display rule.
		 *
		 * @param array<string, mixed> $rule Rule data.
		 * @return array<string, mixed>
		 */
		public static function get_rule_engagement_context( $rule ) {
			$context = array();
			$template = self::sanitize_template( $rule['template'] ?? '' );

			if ( $template && class_exists( 'WP_Ulike_Pro_Engagement_Settings' ) && WP_Ulike_Pro_Engagement_Settings::is_engagement_template( $template ) ) {
				$context['template'] = $template;
			}

			$reactions = self::sanitize_engagement_reactions( $rule['engagement_reactions'] ?? array() );
			if ( ! empty( $reactions ) && class_exists( 'WP_Ulike_Pro_Engagement_Settings' ) ) {
				$item_type = self::map_rule_item_type( $rule['content_type'] ?? 'post' );
				$allowed   = array_fill_keys(
					WP_Ulike_Pro_Engagement_Settings::get_selected_reaction_slugs( $item_type ),
					true
				);
				$reactions = array_values(
					array_filter(
						$reactions,
						static function ( $slug ) use ( $allowed ) {
							return isset( $allowed[ $slug ] );
						}
					)
				);
			}
			if ( ! empty( $reactions ) ) {
				$context['engagement_reactions'] = $reactions;
			}

			$picker_style = self::sanitize_engagement_picker_style( $rule['engagement_picker_style'] ?? '' );
			if ( '' !== $picker_style ) {
				$context['engagement_picker_style'] = $picker_style;
			}

			return $context;
		}

		/**
		 * Merge rule-level button options into wp_ulike args.
		 *
		 * @param array<string, mixed> $rule        Rule data.
		 * @param array<string, mixed> $button_args Existing button args.
		 * @return array<string, mixed>
		 */
		public static function apply_rule_button_args( $rule, $button_args = array() ) {
			if ( ! is_array( $button_args ) ) {
				$button_args = array();
			}

			if ( ! empty( $rule['template'] ) ) {
				$button_args['style'] = self::sanitize_template( $rule['template'] );
			}

			if ( 'no' === self::sanitize_tri_state_bool( $rule['display_counter'] ?? '' ) ) {
				$button_args['wrapper_class'] = trim( (string) ( $button_args['wrapper_class'] ?? '' ) . ' wpulike-hide-counter' );
			}

			$display_likers = self::sanitize_tri_state_bool( $rule['display_likers'] ?? '' );
			if ( 'yes' === $display_likers ) {
				$button_args['display_likers'] = 1;
				$likers_style                  = self::sanitize_likers_style( $rule['likers_style'] ?? '', 'yes' );
				if ( '' !== $likers_style ) {
					$button_args['likers_style'] = $likers_style;
				}
			} elseif ( 'no' === $display_likers ) {
				$button_args['display_likers'] = 0;
			}

			return apply_filters( 'wp_ulike_pro_display_automation_button_args', $button_args, $rule );
		}

		/**
		 * Sanitize button template key for a display rule.
		 *
		 * @param string $template Raw template key.
		 * @return string
		 */
		public static function sanitize_template( $template ) {
			return WP_Ulike_Pro_Display_Automation_Sanitizer::sanitize_template( $template );
		}

		/**
		 * Button templates for the display automation admin UI.
		 *
		 * @return array<int, array<string, string>>
		 */
		public static function get_button_templates_for_admin() {
			if ( ! function_exists( 'wp_ulike_generate_templates_list' ) ) {
				return array();
			}

			$templates = wp_ulike_generate_templates_list();
			$options   = array();

			foreach ( $templates as $key => $args ) {
				if ( ! is_array( $args ) ) {
					continue;
				}

				$options[] = array(
					'id'     => sanitize_key( $key ),
					'name'   => isset( $args['name'] ) ? (string) $args['name'] : (string) $key,
					'symbol' => ! empty( $args['symbol'] ) ? esc_url( $args['symbol'] ) : '',
				);
			}

			return apply_filters( 'wp_ulike_pro_display_automation_button_templates', $options );
		}

		/**
		 * Get admin configuration payload.
		 *
		 * @return array<string, mixed>
		 */
		public static function get_admin_config() {
			$rules = self::get_rules_for_admin();

			$post_types = get_post_types( array( 'public' => true ), 'objects' );

			return array(
				'rules'            => $rules,
				'has_saved_rules'  => self::has_saved_rules(),
				'placement_groups' => self::get_placement_groups(),
				'context_options'  => self::get_context_options(),
				'post_types'       => wp_list_pluck( $post_types, 'label', 'name' ),
				'product_types'    => WP_Ulike_Pro_WooCommerce::get_product_types(),
				'is_woocommerce'   => WP_Ulike_Pro_WooCommerce::is_active(),
				'is_edd'           => WP_Ulike_Pro_Easy_Digital_Downloads::is_active(),
				'is_buddypress'    => WP_Ulike_Pro_BuddyPress::is_active(),
				'is_bbpress'       => WP_Ulike_Pro_BbPress::is_active(),
				'bbpress_forums'   => WP_Ulike_Pro_BbPress::get_forum_options(),
				'button_templates' => self::get_button_templates_for_admin(),
				'content_types_by_group' => array(
					'wordpress'    => self::get_content_types_for_group( 'wordpress' ),
					'woocommerce'  => self::get_content_types_for_group( 'woocommerce' ),
					'edd'          => self::get_content_types_for_group( 'edd' ),
					'buddypress'   => self::get_content_types_for_group( 'buddypress' ),
					'bbpress'      => self::get_content_types_for_group( 'bbpress' ),
				),
				'ui_copy_profiles'       => self::get_ui_copy_profiles(),
				'context_profiles'       => array(
					'post'           => array_keys( self::get_context_options_for_content( 'wordpress', 'post' ) ),
					'comment'        => array_keys( self::get_context_options_for_content( 'wordpress', 'comment' ) ),
					'product_review' => array_keys( self::get_context_options_for_content( 'woocommerce', 'product_review' ) ),
					'activity'         => array_keys( self::get_context_options_for_content( 'buddypress', 'activity' ) ),
					'activity_comment' => array_keys( self::get_context_options_for_content( 'buddypress', 'activity_comment' ) ),
					'topic'            => array_keys( self::get_context_options_for_content( 'bbpress', 'topic' ) ),
				),
				'context_profiles_by_group' => array(
					'wordpress'   => array(
						'post'    => array_keys( self::get_context_options_for_content( 'wordpress', 'post' ) ),
						'comment' => array_keys( self::get_context_options_for_content( 'wordpress', 'comment' ) ),
					),
					'woocommerce' => array(
						'post'           => array_keys( self::get_context_options_for_content( 'woocommerce', 'post' ) ),
						'product_review' => array_keys( self::get_context_options_for_content( 'woocommerce', 'product_review' ) ),
					),
					'edd'         => array(
						'post' => array_keys( self::get_context_options_for_content( 'edd', 'post' ) ),
					),
					'buddypress'  => array(
						'activity'         => array_keys( self::get_context_options_for_content( 'buddypress', 'activity' ) ),
						'activity_comment' => array_keys( self::get_context_options_for_content( 'buddypress', 'activity_comment' ) ),
					),
					'bbpress'     => array(
						'topic' => array_keys( self::get_context_options_for_content( 'bbpress', 'topic' ) ),
					),
				),
				'engagement_reactions_by_content_type' => self::get_engagement_reactions_admin_options(),
				'strings' => array(
					'confirm_remove_rule'      => esc_html__( 'Remove this display rule?', WP_ULIKE_PRO_DOMAIN ),
					'custom_hook_required'   => esc_html__( 'Enter a custom hook name for rules that use a custom theme hook placement.', WP_ULIKE_PRO_DOMAIN ),
					'taxonomy_any'           => esc_html__( 'Any', WP_ULIKE_PRO_DOMAIN ),
					'taxonomy_select'        => esc_html__( 'Select taxonomy', WP_ULIKE_PRO_DOMAIN ),
					'post_type_label'        => esc_html__( 'Content types', WP_ULIKE_PRO_DOMAIN ),
					'post_type_parent_label' => esc_html__( 'Parent content types', WP_ULIKE_PRO_DOMAIN ),
					'multiselect_placeholder' => esc_html__( 'Select options…', WP_ULIKE_PRO_DOMAIN ),
					'multiselect_search'      => esc_html__( 'Search…', WP_ULIKE_PRO_DOMAIN ),
					'load_error'             => esc_html__( 'Could not load options. Please try again.', WP_ULIKE_PRO_DOMAIN ),
					'placement_one_per_rule' => esc_html__( 'Each rule controls one button location. To show likes in more than one place (for example product page and shop archive), add a separate rule for each location.', WP_ULIKE_PRO_DOMAIN ),
					'duplicate_rule_suffix'  => esc_html__( '(copy)', WP_ULIKE_PRO_DOMAIN ),
					'unsaved_changes_notice' => esc_html__( 'You have unsaved changes', WP_ULIKE_PRO_DOMAIN ),
					'unsaved_changes_tab'    => esc_html__( 'You have unsaved changes. Switch sections anyway?', WP_ULIKE_PRO_DOMAIN ),
					'same_location_label'    => esc_html__( 'Same location', WP_ULIKE_PRO_DOMAIN ),
					'same_location_hint'     => esc_html__( 'Same button location as rule %s', WP_ULIKE_PRO_DOMAIN ),
				),
			);
		}
	}
}

