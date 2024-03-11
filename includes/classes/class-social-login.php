<?php
defined( 'ABSPATH' ) or exit;

use Hybridauth\Exception\Exception;
use Hybridauth\Hybridauth;
use Hybridauth\HttpClient;
use Hybridauth\Storage\Session;

/**
 * HybridAuth class
 */
class WP_Ulike_Pro_Social_Login {

	/** @var array configuration */
	private $config;

	/**
	 * Constructor.
	 *
	 * @param string $base_auth_path base authentication path
	 */
	public function __construct() {
		$this->init_config();
	}

	/**
	 * Initialize HybridAuth configuration
	 */
	public function init_config() {

		$config = array(
			'callback'     => self::getCurrentUrl(),
			'providers'    => array()
		);

		// Loop over available providers and add their configuration
		foreach ( $this->get_available_providers() as $provider => $args ) {
			$config['providers'][ $provider ] = $args;
		}

		$this->config  = apply_filters( 'wp_ulike_pro_social_login_hybridauth_config', $config );
	}

	/**
	 * Get availabe providers info
	 *
	 * @return array
	 */
	public function get_available_providers(){
		$social_logins = WP_Ulike_Pro_Options::getAvailabeSocialLogins();
		$providers     = array();

		if( empty( $social_logins ) ){
			return $providers;
		}

		foreach ( $social_logins as $key => $value ) {
			$providers[$value['network']] = [
				'enabled' => true,
				'keys'    => [
					'key'    => $value['key'],
					'secret' => $value['secret']
				]
			];
		}

		return $providers;
	}

	/**
	 * Authenticate using HybridAuth
	 */
	public function connectUser() {
		$user_id = null;
		$storage = self::getSession();

		try {

			// Return false in preview mode
			if( WP_Ulike_Pro::is_preview_mode() ){
				throw new \Exception( esc_html__( 'It is not possible to perform this process in preview mode!', WP_ULIKE_PRO_DOMAIN ) );
			}

			// set callback url for provider
			$this->config['callback'] =  WP_Ulike_Pro_Permalinks::get_social_login_callback_url( $_GET['provider'] ?? $storage->get('provider') );
			$hybridauth  = $this->load_hybridauth();

			if (isset($_GET['provider'])) {
				// Validate provider exists in the $config
				if (in_array($_GET['provider'], $hybridauth->getProviders())) {
					// Store the provider for the callback event
					$storage->set('provider', $_GET['provider']);
				} else {
					throw new \Exception( __( 'No provider class found!', WP_ULIKE_PRO_DOMAIN ) );
				}
			}

			if ( $provider_id = $storage->get('provider') ) {
				$adapter = $hybridauth->authenticate( $provider_id );
				// ask for the user's profile from the provider
				$ha_profile = $adapter->getUserProfile();

			} else {
				throw new \Exception( __( 'No provider class found!', WP_ULIKE_PRO_DOMAIN ) );
			}

		} catch ( \Exception $e ) {
			if ( ! empty( $hybridauth ) ) {
				$hybridauth->disconnectAllAdapters();
			}

			wp_ulike_pro_add_notice( __( 'Provider authentication error', WP_ULIKE_PRO_DOMAIN ), 'error' );
			// redirect to current page and show notice
			$this->redirect();
		}

		// convert Hybrid_User_Profile to an associative array with snake_case keys
		$profile_data = (array) $ha_profile;

		if ( ! empty( $profile_data ) ) {
			foreach ( $profile_data as $key => $value ) {

				unset( $profile_data[ $key ] );

				$profile_data[ WP_Ulike_Pro_Validation::decamelize( $key ) ] = $value;
			}
		}

		$profile = new \WP_Ulike_Pro_Social_Login_Provider_Profile( $provider_id, $profile_data );

		// process user profile and log in
		try {
			$user_id = $this->process_profile( $profile, $provider_id );
		} catch ( \Exception $e ) {
			wp_ulike_pro_add_notice( $e->getMessage(), 'error' );
		}

		// extra level of security for hosts that may leak HybridAuth sessions to other visitors >:(
		if ( ! empty( $hybridauth ) ) {
			$hybridauth->disconnectAllAdapters();
		}

		$this->redirect( $user_id );
	}

	/**
	 * Loads and returns the HybridAuth class instance.
	 *
	 * @return \Hybrid_Auth hybridauth instance
	 */
	private function load_hybridauth() {
		return new Hybridauth( $this->config );
	}

	/**
	 * Redirect back to the provided return_url
	 *
	 * @param int $user_id the user ID. Default 0.
	 */
	public function redirect( $user_id = 0 ) {
		global $ulp_session;

		$user       = get_user_by( 'id', $user_id );
		$return_url = $user_id ? wp_ulike_pro_get_user_profile_permalink( $user->ID ) : $ulp_session->get( 'current_url' );

		// unset current url
		$ulp_session->__unset( 'current_url' );

		wp_safe_redirect( esc_url_raw( $return_url ) );
		exit;
	}

	/**
	 * Process authenticated user's profile
	 */
	private function process_profile( $profile, $provider_id ) {
		global $wpdb;

		// this should never happen, but let's make sure we handle this anyway
		if ( ! $provider_id ) {
			throw new \Exception( sprintf( __( 'No provider class found for %s', WP_ULIKE_PRO_DOMAIN ), $profile->get_provider_id() ) );
		}

		$user         = null;
		$found_via    = null;
		$new_user = false;

		// ensure that providers can't return a blank identifier
		if ( ! $profile->get_identifier() ) {
			throw new \Exception( sprintf( __( '%s returned an invalid user identifier.', WP_ULIKE_PRO_DOMAIN ), $profile->get_provider_id() ) );
		}

		// look up if the user already exists on WP

		// first, try to identify user based on the social identifier
		$user_id = $wpdb->get_var( $wpdb->prepare( "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = %s AND meta_value = %s", '_ulp_social_login_' . $provider_id . '_identifier', $profile->get_identifier() ) );

		if ( $user_id ) {

			$user = get_user_by( 'id', $user_id );

			if ( $user ) {
				$found_via = 'identifier';
			}
		}

		// same email as in their social profile
		if ( ! $user && $profile->has_email() ) {

			$user = get_user_by( 'email', $profile->get_email() );

			if ( $user ) {
				$found_via = 'email';
			}
		}

		// if a user is already logged in...
		if ( is_user_logged_in() ) {

			// ...and a user matching the social profile was found,
			// check that the logged in user and found user are the same.
			// This happens when user is linking a new social profile to their account.
			if ( $user && get_current_user_id() !== $user->ID ) {

				if ( 'identifier' === $found_via ) {
					throw new \Exception( __( 'This account is already linked to another user account.', WP_ULIKE_PRO_DOMAIN ) );
				} else {
					throw new \Exception( __( 'A user account using the same email address as this account already exists.', WP_ULIKE_PRO_DOMAIN ) );
				}
			}

			// if the social profile is not linked to any user accounts,
			// use the currently logged in user as the user
			if ( ! $user ) {
				$user = get_user_by( 'id', get_current_user_id() );
			}
		}

		// check if a user is found via email and not in one of the allowed roles
		if ( ! is_user_logged_in() && $user && 'email' === $found_via && isset( $user->roles[0] ) && ! in_array( $user->roles[0], apply_filters( 'wp_like_pro_social_login_find_by_email_allowed_user_roles', array( 'subscriber' ) ) ) ) {
			throw new \Exception( __( 'Oops, it looks like you may already have an account&hellip; please log in to link your profile.', WP_ULIKE_PRO_DOMAIN ) );
		}

		// if no user was found, create one
		if ( ! $user ) {
			do_action( 'wp_like_pro_social_login_before_create_user', $profile, $provider_id );

			$user_id = $this->create_new_user( $profile );
			$user    = get_user_by( 'id', $user_id );

			// indicate that a new user was created
			$new_user = true;
		}

		// update user's WP user profile and billing details
		$profile->update_user_profile( $user->ID, $new_user );

		// log user in or add account linked notice for a logged in user
		if ( ! is_user_logged_in() ) {

			if ( ! $message = apply_filters( 'wp_like_pro_social_login_set_auth_cookie', '', $user ) ) {

				do_action( 'wp_like_pro_social_login_before_user_login', $user->ID, $provider_id, $profile );

				wp_ulike_pro_set_user_auth_cookie( $user->ID );

				// Store login timestamp
				update_user_meta( $user->ID, '_ulp_social_login_' . $provider_id . '_login_timestamp', current_time( 'timestamp' ) );

				/** this hook is documened in wp-includes/user.php */
				do_action( 'wp_login', $user->user_login, $user );

				/**
				 * User authenticated via social login.
				 *
				 * @param int $user_id ID of the user
				 * @param string $provider_id Social Login provider ID
				 */
				do_action( 'wp_like_pro_social_login_user_authenticated', $user->ID, $provider_id );

			} else {
				wp_ulike_pro_add_notice( $message, 'notice' );
			}

		} else {
			wp_ulike_pro_add_notice( 'Your account is now linked to your profile.', 'notice' );
		}

		return $user->ID;
	}

	/**
	 * Create a WP user from the provider's data
	 *
	 * @param $profile user profile object
	 */
	private function create_new_user( $profile ) {

		$userdata = apply_filters( 'wp_ulike_pro_social_login_new_user_data', array(
			'role'       => 'subscriber',
			'user_login' => $profile->has_email() ? sanitize_email( $profile->get_email() ) : $profile->get_username(),
			'user_email' => $profile->get_email(),
			'user_pass'  => wp_generate_password(),
			'first_name' => $profile->get_first_name(),
			'last_name'  => $profile->get_last_name(),
		), $profile );

		// ensure username is not blank - if it is, use first and last name to generate a username
		if ( empty( $userdata['user_login'] ) ) {
			$userdata['user_login'] = sanitize_key( $userdata['first_name'] . $userdata['last_name'] );
		}

		// mimics behavior of wp_insert_user() which would strip encoded characters and could prompt empty_user_login error
		$username = sanitize_user( $userdata['user_login'], true );

		// if the username is empty, try to build one from other profile properties
		if ( '' === $username ) {

			// try to make a username from user first and last name
			if ( '' !== $userdata['first_name'] || '' !== $userdata['last_name'] ) {
				$name     = is_rtl() ? implode( '_', array_filter( array( $userdata['last_name'], $userdata['first_name'] ) ) ) : implode( '_', array_filter( array( $userdata['first_name'], $userdata['last_name'] ) ) );
				$username = sanitize_user( strtolower( $name ), true );
			}

			// if that didn't work, replace the empty username with a unique user_* ID (tries to use a localized name for user first)
			if ( '' === $username ) {
				$user     = sanitize_user( strtolower( __( 'User', WP_ULIKE_PRO_DOMAIN ) ), true );
				$username = uniqid( empty( $user ) ? 'user_' : "{$user}_", false );
			}

			$userdata['user_login'] = $username;
		}

		// ensure username is unique
		$append = 1;

		while ( username_exists( $userdata['user_login'] ) ) {
			$userdata['user_login'] = $username . $append;
			$append ++;
		}

		$user_id = wp_insert_user( $userdata );

		if ( is_wp_error( $user_id ) ) {
			throw new \Exception( '<strong>' . __( 'ERROR', WP_ULIKE_PRO_DOMAIN ) . '</strong>: ' . __( 'Couldn&#8217;t register you&hellip; please contact us if you continue to have problems.', WP_ULIKE_PRO_DOMAIN ) );
		}

		// trigger New Account email
		$mail = new WP_Ulike_Pro_Mail();
		$mail->send( $userdata['user_email'], 'welcome', array( 'user_id' => $user_id ) );

		return $user_id;
	}




	/**
	 * Get Session
	 * @return object
	 */
	public static function getSession() {

		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return;
		}

		if ( is_admin() ) {
			return;
		}

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}

		if ( wp_doing_cron() ) {
			return;
		}

		if ( headers_sent() ) {
			return;
		}

		if ( session_id() === '' ) {
			session_start();
		}

		if ( session_status() === PHP_SESSION_NONE ) {
			// session has not started.
			session_start();
		}

		$storage = new Session();

		return $storage;
	}

	/**
	 * Get Login URL
	 *
	 * @param string  $provider Social Provider's ID.
	 *
	 * @return string
	 */
	public static function getConnectUrl( $provider = '' ) {
		global $ulp_session;

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}

		if ( isset( $_REQUEST['provider'] ) || isset( $_REQUEST['code'] ) || isset( $_REQUEST['state'] ) ) {
			return;
		}

		// set current url in session to use it for redirection
		$ulp_session->set( 'current_url', self::getCurrentUrl() );

		return WP_Ulike_Pro_Permalinks::get_social_login_callback_url( $provider );
	}

	/**
	 * Get current URL
	 *
	 * @return string $current_url Site's current URL excluding specific parameters.
	 */
	public static function getCurrentUrl() {

		global $wp;

		$current_url = '';

		$current_url = home_url( add_query_arg( array(), $wp->request ) );

		$current_url = remove_query_arg( array( 'provider', 'state', 'code' ), $current_url );

		// Reject all file URLs from current URL.
		if ( strpos( $current_url , "/wp-content/" ) !== false ) {
			exit;
		}

		return $current_url;
	}

}