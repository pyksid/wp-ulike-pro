<?php
/**
 * Permalinks
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

class WP_Ulike_Pro_Permalinks {

	/**
	 * @var
	 */
	var $current_url;


	/**
	 * Permalinks constructor.
	 */
	function __construct() {
		add_action( 'init',  array( &$this, 'set_current_url' ), 0 );
	}


	/**
	 * Set current URL variable
	 */
	function set_current_url() {
		$this->current_url = $this->get_current_url();
	}


	/**
	 * Get query as array
	 *
	 * @return array
	 */
	function get_query_array() {
		$parts = parse_url( $this->get_current_url() );
		if ( isset( $parts['query'] ) ) {
			parse_str( $parts['query'], $query );
			return $query;
		}

		return array();
	}


	/**
	 * Get current URL anywhere
	 *
	 * @param bool $no_query_params
	 *
	 * @return mixed|void
	 */
	function get_current_url( $no_query_params = false ) {
		//use WP native function for fill $_SERVER variables by correct values
		wp_fix_server_vars();

		//check if WP-CLI there isn't set HTTP_HOST, use localhost instead
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			$host = isset( $_SERVER['HTTP_HOST'] ) ? $_SERVER['HTTP_HOST'] : 'localhost';
		} else{
			if ( isset( $_SERVER['HTTP_HOST'] ) ) {
			  $host = $_SERVER['HTTP_HOST'];
			}else{
			  $host = 'localhost';
			}
		}

		$page_url = ( is_ssl() ? 'https://' : 'http://' ) . $host . $_SERVER['REQUEST_URI'];

		if ( $no_query_params == true ) {
			$page_url = strtok( $page_url, '?' );
		}

		return apply_filters( 'wp_ulike_pro_get_current_page_url', $page_url );
	}

	/**
	* @param $slug
	*
	* @return int|null|string
	*/
	public static function slug_exists_user_id( $slug ) {
		global $wpdb;

		$permalink_base = WP_Ulike_Pro_Options::getProfilePermalinkBase();

		$user_id = $wpdb->get_var(
			"SELECT user_id
			FROM {$wpdb->usermeta}
			WHERE meta_key = 'ulp_user_profile_url_slug_{$permalink_base}' AND
					meta_value = '{$slug}'
			ORDER BY umeta_id ASC
			LIMIT 1"
		);

		if ( ! empty( $user_id ) ) {
			return $user_id;
		}

		return false;
	}

	/**
	 * Get Profile Permalink
	 *
	 * @param  string $slug
	 * @return string $profile_url
	 */
	public static function profile_permalink( $slug ) {
		$page_id     = WP_Ulike_Pro_Options::getProfilePage();
		$profile_url = WP_Ulike_Pro_Options::getProfilePageUrl();
		$profile_url = apply_filters( 'wp_ulike_pro_localize_permalink_filter', $profile_url, $page_id );

		if ( get_option('permalink_structure') ) {
			$profile_url = trailingslashit( untrailingslashit( $profile_url ) );
			$profile_url = $profile_url . strtolower( $slug ). '/';
		} else {
			$profile_url =  add_query_arg( 'wp_ulike_user', strtolower( $slug ), $profile_url );
		}

		return ! empty( $profile_url ) ? $profile_url : '';
	}

	/**
	 * Generate profile slug
	 *
	 * @param string $full_name
	 * @param string $first_name
	 * @param string $last_name
	 * @return string
	 */
	public static function profile_slug( $full_name, $first_name, $last_name ){

		$permalink_base = WP_Ulike_Pro_Options::getProfilePermalinkBase();

		$user_in_url = '';

		$full_name = str_replace("'", "", $full_name );
		$full_name = str_replace("&", "", $full_name );
		$full_name = str_replace("/", "", $full_name );

		switch( $permalink_base ) {
			case 'name': // dotted

				$full_name_slug = $full_name;
				$difficulties = 0;


				if( strpos( $full_name, '.' ) > -1 ){
					$full_name = str_replace(".", "_", $full_name );
					$difficulties++;
				}

				$full_name = strtolower( str_replace( " ", ".", $full_name ) );

				if( strpos( $full_name, '_.' ) > -1 ){
					$full_name  = str_replace('_.', '_', $full_name );
					$difficulties++;
				}

				$full_name_slug = str_replace( '-' ,  '.', $full_name_slug );
				$full_name_slug = str_replace( ' ' ,  '.', $full_name_slug );
				$full_name_slug = str_replace( '..' , '.', $full_name_slug );

				if( strpos( $full_name, '.' ) > -1 ){
					$full_name  = str_replace('.', ' ', $full_name );
					$difficulties++;
				}

				$user_in_url = rawurlencode( $full_name_slug );

				break;

			case 'name_dash': // dashed

				$difficulties = 0;

				$full_name_slug = strtolower( $full_name );

				// if last name has dashed replace with underscore
				if( strpos( $last_name, '-') > -1 && strpos( $full_name, '-' ) > -1 ){
					$difficulties++;
					$full_name  = str_replace('-', '_', $full_name  );
				}
				// if first name has dashed replace with underscore
				if( strpos( $first_name, '-') > -1 && strpos( $full_name, '-' ) > -1 ){
					$difficulties++;
					$full_name  = str_replace('-', '_', $full_name  );
				}
				// if name has space, replace with dash
				$full_name_slug = str_replace( ' ' ,  '-', $full_name_slug );

				// if name has period
				if( strpos( $last_name, '.') > -1 && strpos( $full_name, '.' ) > -1 ){
					$difficulties++;
				}

				$full_name_slug = str_replace( '.' ,  '-', $full_name_slug );
				$full_name_slug = str_replace( '--' , '-', $full_name_slug );

				$user_in_url = rawurlencode(  $full_name_slug );

				break;

			case 'name_plus': // plus

				$difficulties = 0;

				$full_name_slug = strtolower( $full_name );

				// if last name has dashed replace with underscore
				if( strpos( $last_name, '+') > -1 && strpos( $full_name, '+' ) > -1 ){
					$difficulties++;
					$full_name  = str_replace('-', '_', $full_name  );
				}
				// if first name has dashed replace with underscore
				if( strpos( $first_name, '+') > -1 && strpos( $full_name, '+' ) > -1 ){
					$difficulties++;
					$full_name  = str_replace('-', '_', $full_name  );
				}
				if( strpos( $last_name, '-') > -1 || strpos( $first_name, '-') > -1 || strpos( $full_name, '-') > -1 ){
					$difficulties++;
				}
				// if name has dash, replace with space
				$full_name_slug = str_replace( '-' ,  ' ', $full_name_slug );

				// if name has period
				if( strpos( $last_name, '.') > -1 && strpos( $full_name, '.' ) > -1 ){
					$difficulties++;
				}

				$full_name_slug = str_replace( '.' ,  ' ', $full_name_slug );
				$full_name_slug = str_replace( '++' , ' ', $full_name_slug );

				$user_in_url = urlencode( $full_name_slug );

				break;
		}

		return $user_in_url ;
	}

	/**
	 * Get logout URL
	 *
	 * @param string $redirect
	 * @return string
	 */
	public static function get_logout_url( $redirect = '' ) {
		$args = array( 'action' => 'logout' );
		if ( ! empty( $redirect ) ) {
			$args['redirect_to'] = urlencode( $redirect );
		}

		$custom_redirect = WP_Ulike_Pro_Options::getLogoutRedirectUrl();
		if( ! empty( $custom_redirect ) ){
			$args['redirect_to'] = urlencode( $custom_redirect );
		}

		$login_page = self::get_login_url();
		$logout_url = add_query_arg( $args, $login_page );
		$logout_url = wp_nonce_url( $logout_url, 'log-out' );

		return apply_filters( 'wp_ulike_pro_logout_url', $logout_url, $redirect );
	}

	/**
	 * Get login url
	 *
	 * @return void
	 */
	public static function get_login_url() {
		$login_page = WP_Ulike_Pro_Options::getLoginPageUrl();

		if ( get_option('permalink_structure') ) {
			$login_page = trailingslashit( untrailingslashit( $login_page ) );
		}

		return $login_page;
	}

	/**
	 * Get Reset URL
	 *
	 * @return bool|string
	 */
	public static function reset_url( $user_id ) {
		//new reset password key via WP native field
		$user_data = get_userdata( $user_id );
		$key = get_password_reset_key( $user_data );
		$url = WP_Ulike_Pro_Options::getResetPasswordPageUrl();
		$url = add_query_arg( array( 'action' => 'changepassword', 'key' => $key, 'login' => $user_data->user_login ), $url );

		return $url;
	}

	/**
	 * Get social login Callback URL
	 *
	 * @param  string $provider Network provider.
	 * @return string $callback_url Site's current callback URL.
	 */
	public static function get_social_login_callback_url( $provider = '' ) {

		$params = array();
		$params['ulp-api'] = 'auth';

		// add provider
		if( ! empty( $provider ) ){
			$params[ 'provider' ] = esc_attr( $provider );
		}

		return add_query_arg( $params, home_url( '/' ) );
	}

	/**
	 * Add a query param to url
	 *
	 * @param $key
	 * @param $value
	 *
	 * @return string
	 */
	function add_query( $key, $value ) {
		$this->current_url =  add_query_arg( $key, $value, $this->get_current_url() );
		return $this->current_url;
	}


	/**
	 * Remove a query param from url
	 *
	 * @param $key
	 * @param $value
	 *
	 * @return string
	 */
	function remove_query( $key, $value ) {
		$this->current_url = remove_query_arg( $key, $this->current_url );
		return $this->current_url;
	}

}