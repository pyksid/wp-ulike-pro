<?php
/**
 * Stats bootstrap meta for the pro React statistics dashboard.
 *
 * @package WP_Ulike_Pro
 *
 * @package    wp-ulike-pro
 * @author     TechnoWich 2026
 * @link       https://wpulike.com
 */

// no direct access allowed
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'WP_Ulike_Pro_Stats_Meta' ) ) {
	/**
	 * Builds bootstrap and intelligence meta for WP_Ulike_Pro_Stats_V2.
	 */
	final class WP_Ulike_Pro_Stats_Meta {

		/** @var array<string,string> Stats table keys to WP ULike setting types. @deprecated Use WP_Ulike_Pro_Stats_Type_Resolver */
		private static $content_type_map = array(
			'posts'      => 'post',
			'comments'   => 'comment',
			'activities' => 'activity',
			'topics'     => 'topic',
		);

		/**
		 * Map stats content type to WP ULike setting type.
		 *
		 * @param string $type posts|comments|activities|topics
		 * @return string
		 */
		private static function map_content_type( $type ) {
			if ( class_exists( 'WP_Ulike_Pro_Stats_Type_Resolver' ) ) {
				return WP_Ulike_Pro_Stats_Type_Resolver::map_content_type_public( $type );
			}

			return isset( self::$content_type_map[ $type ] ) ? self::$content_type_map[ $type ] : $type;
		}

		/**
		 * Combined stats meta for React admin bootstrap + intelligence payloads.
		 *
		 * @param array $content_types Active stats content types.
		 * @return array{voting_mode:string,dislikes_enabled:bool}
		 */
		public static function get_site_stats_meta( $content_types = array() ) {
			static $cache = array();

			$types = array_values( array_filter( (array) $content_types ) );
			if ( empty( $types ) ) {
				$types = array( 'posts' );
			}

			sort( $types );
			$cache_key = implode( ',', $types );

			if ( isset( $cache[ $cache_key ] ) ) {
				return $cache[ $cache_key ];
			}

			$dislikes_enabled = false;

			foreach ( $types as $type ) {
				if ( self::content_type_supports_dislikes( $type ) ) {
					$dislikes_enabled = true;
					break;
				}
			}

		$reactions_by_type = array();
		$stars_by_type     = array();
		$modes_by_type     = array();
		$reaction_options  = array();
		$star_max_by_type  = array();

		foreach ( $types as $type ) {
			if ( ! class_exists( 'WP_Ulike_Pro_Engagement_Settings' ) ) {
				continue;
			}

			$item_type = self::map_content_type( $type );
			$mode      = WP_Ulike_Pro_Engagement_Settings::get_mode( $item_type );

			// Stats filters/UI are data-oriented: always expose reaction + star
			// options so Top Content can filter/display every engagement kind
			// that exists (display automation / mixed templates included).
			// modes_by_type stays as the primary template for insight copy only.
			$modes_by_type[ $type ] = $mode;

			$reaction_options[ $type ] = array_values(
				array_map(
					static function ( $reaction ) {
						return array(
							'slug'  => (string) $reaction['slug'],
							'emoji' => (string) $reaction['emoji'],
							'label' => wp_strip_all_tags( (string) $reaction['label'] ),
						);
					},
					WP_Ulike_Pro_Engagement_Settings::get_configured_reactions( $item_type )
				)
			);
			$reactions_by_type[ $type ] = ! empty( $reaction_options[ $type ] );

			if ( class_exists( 'WP_Ulike_Pro_Engagement_Registry' ) ) {
				$star_max_by_type[ $type ] = (int) WP_Ulike_Pro_Engagement_Registry::get_star_config( $item_type )['max'];
				$stars_by_type[ $type ]    = $star_max_by_type[ $type ] > 0;
			} else {
				$stars_by_type[ $type ] = false;
			}
		}

		$cache[ $cache_key ] = array(
			'voting_mode'             => self::get_site_voting_mode( $types ),
			'dislikes_enabled'        => $dislikes_enabled,
			'reactions_enabled'       => ! empty( array_filter( $reactions_by_type ) ),
			'star_ratings_enabled'    => ! empty( array_filter( $stars_by_type ) ),
			'reactions_by_type'       => $reactions_by_type,
			'stars_by_type'           => $stars_by_type,
			'modes_by_type'           => $modes_by_type,
			'reaction_options_by_type'=> $reaction_options,
			'star_max_by_type'        => $star_max_by_type,
		);

			return $cache[ $cache_key ];
		}

		/**
		 * Whether any active stats type uses an engagement mode.
		 *
		 * @param array  $content_types Stats table keys.
		 * @param string $mode          emoji|star.
		 * @return bool
		 */
		public static function site_uses_engagement_mode( $content_types, $mode ) {
			if ( ! class_exists( 'WP_Ulike_Pro_Engagement_Settings' ) ) {
				return false;
			}

			foreach ( (array) $content_types as $type ) {
				if ( $mode === WP_Ulike_Pro_Engagement_Settings::get_mode( self::map_content_type( $type ) ) ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * @param string $type posts|comments|activities|topics
		 * @return string
		 */
		public static function map_content_type_public( $type ) {
			return self::map_content_type( $type );
		}

		/**
		 * Whether dislikes are available for a stats content type.
		 *
		 * @param string $type posts|comments|activities|topics
		 * @return bool
		 */
		public static function content_type_supports_dislikes( $type ) {
			$template = self::get_content_type_template( $type );

			if ( empty( $template ) || ! function_exists( 'wp_ulike_generate_templates_list' ) ) {
				return false;
			}

			$templates = wp_ulike_generate_templates_list();

			if ( ! isset( $templates[ $template ] ) ) {
				return false;
			}

			$template_data = $templates[ $template ];

			return ! empty( $template_data['is_percentage_support'] ) || ! empty( $template_data['has_subtotal'] );
		}

		/**
		 * @param array $content_types Active stats content types.
		 * @return string logged_in_only|guest_only|both
		 */
		private static function get_site_voting_mode( $content_types ) {
			if ( ! class_exists( 'wp_ulike_setting_repo' ) ) {
				return 'both';
			}

			$requires_login = 0;
			$allows_guests  = 0;

			foreach ( (array) $content_types as $type ) {
				if ( self::type_requires_login( $type ) ) {
					++$requires_login;
				} else {
					++$allows_guests;
				}
			}

			if ( $allows_guests === 0 ) {
				return 'logged_in_only';
			}

			// All active types allow guest voting; logged-in members can still vote and
			// appear in Top members — not guest-only voting.
			return 'both';
		}

		/**
		 * @param string $type posts|comments|activities|topics
		 * @return string
		 */
		private static function get_content_type_template( $type ) {
			if ( ! class_exists( 'wp_ulike_setting_type' ) || ! function_exists( 'wp_ulike_get_option' ) ) {
				return 'wpulike-default';
			}

			$setting_type = self::map_content_type( $type );
			$setting_key  = wp_ulike_setting_type::get_instance( $setting_type )->getSettingKey();
			$template     = wp_ulike_get_option( $setting_key . '|template', 'wpulike-default' );

			return is_string( $template ) && $template ? $template : 'wpulike-default';
		}

		/**
		 * @param string $type posts|comments|activities|topics
		 * @return bool
		 */
		private static function type_requires_login( $type ) {
			return wp_ulike_setting_repo::requireLogin( self::map_content_type( $type ) );
		}
	}
}

