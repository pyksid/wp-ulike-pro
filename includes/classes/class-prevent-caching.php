<?php
/**
 * Cache helper
 *
 * 
 * @package    wp-ulike-pro
 * @author     TechnoWich 2024
 * @link       https://wpulike.com
*/

// no direct access allowed
if ( ! defined('ABSPATH') ) {
    die();
}

/**
 * WP_Ulike_Pro_Cache_Helper.
 */
class WP_Ulike_Pro_Prevent_Caching {

	/**
	 * Hook in methods.
	 */
	public static function init() {
		add_action( 'wp', array( __CLASS__, 'prevent_caching' ) );
	}

	/**
	 * Prevent caching on certain pages
	 */
	public static function prevent_caching() {
		if ( ! is_blog_installed() || WP_Ulike_Pro::is_preview_mode() ) {
			return;
		}

		if ( WP_Ulike_Pro_Options::isCorePage() ) {
			self::set_nocache_constants();
			nocache_headers();
			// set security headers
			// header('X-Frame-Options: DENY');
			// header('X-XSS-Protection: 1; mode=block');
			// header('X-Content-Type-Options: nosniff');
			do_action( 'wp_ulike_pro_set_cookies', true );
		}
	}

	/**
	 * Set constants to prevent caching by some plugins.
	 *
	 * @param  mixed $return Value to return. Previously hooked into a filter.
	 * @return mixed
	 */
	public static function set_nocache_constants( $return = true ) {
		wp_ulike_maybe_define_constant( 'DONOTCACHEPAGE', true );
		wp_ulike_maybe_define_constant( 'DONOTCACHEOBJECT', true );
		wp_ulike_maybe_define_constant( 'DONOTCACHEDB', true );
		return $return;
	}

}