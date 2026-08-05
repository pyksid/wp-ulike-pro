<?php
/**
 * Sanitization helpers for Display Automation rules.
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

if ( ! class_exists( 'WP_Ulike_Pro_Display_Automation_Sanitizer' ) ) {

	/**
	 * Validates and normalizes display automation rule payloads.
	 */
	class WP_Ulike_Pro_Display_Automation_Sanitizer {

		/**
		 * Sanitize submitted rules.
		 *
		 * @param array $rules Raw rules.
		 * @return array<int, array<string, mixed>>
		 */
		public static function sanitize_rules( $rules ) {
			if ( ! is_array( $rules ) ) {
				return array();
			}

			$sanitized = array();

			foreach ( $rules as $index => $rule ) {
				if ( ! is_array( $rule ) ) {
					continue;
				}

				$placement_group = self::sanitize_placement_group( $rule['placement_group'] ?? 'wordpress' );
				$content_type    = sanitize_key( $rule['content_type'] ?? 'post' );
				if ( ! in_array( $content_type, array( 'post', 'comment', 'product_review', 'activity', 'activity_comment', 'topic' ), true ) ) {
					$content_type = 'post';
				}

				$group_placements = WP_Ulike_Pro_Display_Automation::get_placements_for_group( $placement_group, $content_type );
				$placement        = sanitize_key( $rule['placement'] ?? 'the_content' );

				$edd_shop_placements = array(
					'edd_archive_excerpt',
					'edd_archive_content',
					'edd_download_grid_item',
					'edd_download_excerpt',
				);
				if ( in_array( $placement, $edd_shop_placements, true ) ) {
					$placement = 'edd_shop_excerpt';
				}

				$edd_single_placements = array(
					'edd_the_content',
					'edd_single_before_content',
				);
				if ( in_array( $placement, $edd_single_placements, true ) ) {
					$placement = 'edd_single_after_content';
				}

				if ( 'custom' === $placement ) {
					$custom_hook = sanitize_key( $rule['custom_hook'] ?? '' );
					if ( '' === $custom_hook ) {
						continue;
					}
				} elseif ( ! isset( $group_placements[ $placement ] ) ) {
					$placement = (string) array_key_first( $group_placements );
					if ( '' === $placement ) {
						$placement = 'the_content';
					}
				}

				$position = sanitize_key( $rule['position'] ?? 'bottom' );
				if ( ! in_array( $position, array( 'top', 'bottom', 'top_bottom' ), true ) ) {
					$position = 'bottom';
				}

				$conditions = isset( $rule['conditions'] ) && is_array( $rule['conditions'] ) ? $rule['conditions'] : array();

				if ( 'woocommerce' === $placement_group && 'post' === $content_type ) {
					$conditions['post_types'] = array();
				}

				if ( 'edd' === $placement_group && 'post' === $content_type ) {
					$conditions['post_types'] = array();
				}

				if ( in_array( $placement_group, array( 'buddypress', 'bbpress' ), true ) ) {
					$conditions['post_types'] = array();
					$conditions['taxonomy']   = '';
					$conditions['term_ids']   = array();
				}

				$bbpress   = isset( $conditions['bbpress'] ) && is_array( $conditions['bbpress'] ) ? $conditions['bbpress'] : array();
				$item_type = sanitize_key( $bbpress['item_type'] ?? '' );
				if ( ! in_array( $item_type, array( '', 'topic', 'reply' ), true ) ) {
					$item_type = '';
				}
				$topic_ids_raw = isset( $bbpress['topic_ids'] ) && is_string( $bbpress['topic_ids'] ) ? $bbpress['topic_ids'] : '';
				$topic_ids     = array_values(
					array_filter(
						array_map( 'absint', array_map( 'trim', explode( ',', $topic_ids_raw ) ) )
					)
				);

				if ( isset( $conditions['show_on'] ) && is_array( $conditions['show_on'] ) ) {
					$edd_show_on_map = array(
						'edd'                   => 'edd_download_shop',
						'edd_download_archive'  => 'edd_download_shop',
						'edd_download_category' => 'edd_download_shop',
						'edd_download_tag'      => 'edd_download_shop',
						'edd_downloads_grid'    => 'edd_download_shop',
					);
					$mapped_show_on = array();
					foreach ( $conditions['show_on'] as $context_key ) {
						$mapped_show_on[] = $edd_show_on_map[ $context_key ] ?? $context_key;
					}
					$conditions['show_on'] = array_values( array_unique( $mapped_show_on ) );
				}

				$sanitized[] = array(
					'id'                      => ! empty( $rule['id'] ) ? sanitize_key( $rule['id'] ) : 'rule_' . ( $index + 1 ),
					'enabled'                 => ! empty( $rule['enabled'] ),
					'title'                   => sanitize_text_field( $rule['title'] ?? sprintf( esc_html__( 'Rule %d', WP_ULIKE_PRO_DOMAIN ), $index + 1 ) ),
					'content_type'            => $content_type,
					'placement'               => $placement,
					'placement_group'         => $placement_group,
					'custom_hook'             => sanitize_key( $rule['custom_hook'] ?? '' ),
					'hook_priority'           => max( 1, min( 100, (int) ( $rule['hook_priority'] ?? 10 ) ) ),
					'priority'                => max( 1, min( 100, (int) ( $rule['priority'] ?? ( $index + 1 ) * 10 ) ) ),
					'position'                => $position,
					'override_default'        => ! empty( $rule['override_default'] ),
					'template'                => self::sanitize_template( $rule['template'] ?? '' ),
					'display_counter'         => self::sanitize_tri_state_bool( $rule['display_counter'] ?? '' ),
					'display_likers'          => self::sanitize_tri_state_bool( $rule['display_likers'] ?? '' ),
					'likers_style'            => self::sanitize_likers_style( $rule['likers_style'] ?? '', $rule['display_likers'] ?? '' ),
					'engagement_reactions'    => self::sanitize_engagement_reactions( $rule['engagement_reactions'] ?? array() ),
					'engagement_picker_style' => self::sanitize_engagement_picker_style( $rule['engagement_picker_style'] ?? '' ),
					'conditions'              => array(
						'show_on'     => array_values(
							array_filter(
								array_map( 'sanitize_key', (array) ( $conditions['show_on'] ?? array() ) )
							)
						),
						'hide_on'     => array_values(
							array_filter(
								array_map( 'sanitize_key', (array) ( $conditions['hide_on'] ?? array() ) )
							)
						),
						'post_types'  => array_values(
							array_filter(
								array_map( 'sanitize_key', (array) ( $conditions['post_types'] ?? array() ) )
							)
						),
						'taxonomy'    => sanitize_key( $conditions['taxonomy'] ?? '' ),
						'term_ids'    => array_values(
							array_filter(
								array_map( 'absint', (array) ( $conditions['term_ids'] ?? array() ) )
							)
						),
						'woocommerce' => array(
							'on_sale'      => in_array( $conditions['woocommerce']['on_sale'] ?? '', array( 'yes', 'no' ), true ) ? $conditions['woocommerce']['on_sale'] : '',
							'featured'     => in_array( $conditions['woocommerce']['featured'] ?? '', array( 'yes', 'no' ), true ) ? $conditions['woocommerce']['featured'] : '',
							'product_type' => array_values(
								array_filter(
									array_map( 'sanitize_key', (array) ( $conditions['woocommerce']['product_type'] ?? array() ) )
								)
							),
						),
						'edd'         => array(
							'free'             => in_array( $conditions['edd']['free'] ?? '', array( 'yes', 'no' ), true ) ? $conditions['edd']['free'] : '',
							'variable_pricing' => in_array( $conditions['edd']['variable_pricing'] ?? '', array( 'yes', 'no' ), true ) ? $conditions['edd']['variable_pricing'] : '',
						),
						'bbpress'     => array(
							'item_type' => $item_type,
							'forum_ids' => array_values(
								array_filter(
									array_map( 'absint', (array) ( $bbpress['forum_ids'] ?? array() ) )
								)
							),
							'topic_ids' => $topic_ids,
						),
					),
				);
			}

			return $sanitized;
		}

		/**
		 * Sanitize integration group key.
		 *
		 * @param string $group_key Raw group key.
		 * @return string
		 */
		public static function sanitize_placement_group( $group_key ) {
			$group_key = sanitize_key( $group_key );
			$groups    = WP_Ulike_Pro_Display_Automation::get_placement_groups();

			if ( isset( $groups[ $group_key ] ) ) {
				return $group_key;
			}

			return 'wordpress';
		}

		/**
		 * Sanitize yes/no tri-state option (empty = inherit global settings).
		 *
		 * @param string $value Raw value.
		 * @return string
		 */
		public static function sanitize_tri_state_bool( $value ) {
			$value = sanitize_key( $value );
			return in_array( $value, array( 'yes', 'no' ), true ) ? $value : '';
		}

		/**
		 * Sanitize likers box style for a display rule.
		 *
		 * @param string $value          Raw style value.
		 * @param string $display_likers Likers visibility tri-state.
		 * @return string
		 */
		public static function sanitize_likers_style( $value, $display_likers = '' ) {
			if ( 'yes' !== sanitize_key( $display_likers ) ) {
				return '';
			}

			$value = sanitize_key( $value );
			return in_array( $value, array( 'default', 'popover', 'pile' ), true ) ? $value : '';
		}

		/**
		 * Sanitize emoji reaction slugs for a display rule (empty = inherit settings).
		 *
		 * @param mixed $value Raw value.
		 * @return string[]
		 */
		public static function sanitize_engagement_reactions( $value ) {
			if ( empty( $value ) || ! class_exists( 'WP_Ulike_Pro_Engagement_Settings' ) ) {
				return array();
			}

			if ( is_string( $value ) ) {
				$value = array_map( 'trim', explode( ',', $value ) );
			}

			return WP_Ulike_Pro_Engagement_Settings::sanitize_reaction_slugs( (array) $value );
		}

		/**
		 * Sanitize reaction picker style for a display rule (empty = inherit settings).
		 *
		 * @param string $value Raw value.
		 * @return string
		 */
		public static function sanitize_engagement_picker_style( $value ) {
			$value = sanitize_key( $value );

			if ( '' === $value ) {
				return '';
			}

			if ( 'click' === $value ) {
				$value = 'hover';
			}

			return in_array( $value, array( 'hover', 'inline' ), true ) ? $value : '';
		}

		/**
		 * Sanitize button template key for a display rule.
		 *
		 * @param string $template Raw template key.
		 * @return string
		 */
		public static function sanitize_template( $template ) {
			$template = sanitize_key( $template );
			if ( '' === $template ) {
				return '';
			}

			if ( ! function_exists( 'wp_ulike_pro_get_templates_list_by_name' ) ) {
				return '';
			}

			$allowed = array_keys( wp_ulike_pro_get_templates_list_by_name() );
			return in_array( $template, $allowed, true ) ? $template : '';
		}
	}
}

