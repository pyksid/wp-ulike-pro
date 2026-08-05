<?php
/**
 * Easy Digital Downloads integration helpers.
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

if ( ! class_exists( 'WP_Ulike_Pro_Easy_Digital_Downloads' ) ) {

	/**
	 * EDD compatibility and display condition helpers.
	 */
	class WP_Ulike_Pro_Easy_Digital_Downloads {

		/**
		 * Whether Easy Digital Downloads is active.
		 *
		 * @return bool
		 */
		public static function is_active() {
			return class_exists( 'Easy_Digital_Downloads' ) || defined( 'EDD_VERSION' );
		}

		/**
		 * Download shop / archive listing (not single download pages).
		 *
		 * Includes native archives, taxonomy archives, the EDD Shop Page setting,
		 * and any page that contains the [downloads] shortcode.
		 *
		 * @return bool
		 */
		public static function is_shop_listing() {
			if ( is_post_type_archive( 'download' ) || is_tax( 'download_category' ) || is_tax( 'download_tag' ) ) {
				return true;
			}

			if ( self::is_products_page() ) {
				return true;
			}

			if ( is_singular( 'page' ) && self::page_has_downloads_listing() ) {
				return true;
			}

			return false;
		}

		/**
		 * Whether the current request is the EDD "Shop Page" from settings.
		 *
		 * @return bool
		 */
		public static function is_products_page() {
			if ( ! function_exists( 'edd_get_option' ) ) {
				return false;
			}

			$page_id = (int) edd_get_option( 'products_page', 0 );

			return $page_id > 0 && is_page( $page_id );
		}

		/**
		 * Whether a page contains the downloads listing shortcode.
		 *
		 * @param WP_Post|null $post Post object. Defaults to the current singular page.
		 * @return bool
		 */
		public static function page_has_downloads_listing( $post = null ) {
			if ( null === $post ) {
				$post = get_post();
			}

			if ( ! $post instanceof WP_Post || 'page' !== $post->post_type ) {
				return false;
			}

			if ( has_shortcode( $post->post_content, 'downloads' ) || has_shortcode( $post->post_content, 'edd_downloads' ) ) {
				return true;
			}

			return function_exists( 'has_block' ) && has_block( 'edd/downloads', $post );
		}

		/**
		 * Whether we are inside an active [downloads] shortcode loop.
		 *
		 * @return bool
		 */
		public static function in_downloads_shortcode_loop() {
			return did_action( 'edd_downloads_list_before' ) > did_action( 'edd_downloads_list_after' );
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

			return array(
				'edd_single_download' => is_singular( 'download' ),
				'edd_download_shop'   => self::is_shop_listing(),
			);
		}
	}
}

