<?php

// no direct access allowed
if ( ! defined('ABSPATH') ) {
    die();
}

class WP_Ulike_Pro_API {

	const PRODUCT_ID   = 13;

	const BASE_API_URL = 'https://wpulike.com/api/audit/v1/licenses/';
	const RENEW_URL    = 'https://wpulike.com/checkout/';
	const PRICING_URL  = 'https://wpulike.com/pricing/';

	// License Statuses
	const STATUS_VALID         = 'valid';
	const STATUS_INVALID       = 'invalid';
	const STATUS_EXPIRED       = 'expired';
	const STATUS_DEACTIVATED   = 'deactivated';
	const STATUS_SITE_INACTIVE = 'site_inactive';
	const STATUS_DISABLED      = 'disabled';

	protected static $transient_data = [];

	/**
	 * @param array $body_args
	 *
	 * @return \stdClass|\WP_Error
	 */
	private static function remote_post( $body_args = [] ) {
		$use_home_url = true;

		/**
		 * The license API uses `home_url()` function to retrieve the URL. This hook allows
		 * developers to use `get_site_url()` instead of `home_url()` to set the URL.
		 *
		 * When set to `true` (default) it uses `home_url()`.
		 * When set to `false` it uses `get_site_url()`.
		 *
		 * @param boolean $use_home_url Whether to use `home_url()` or `get_site_url()`.
		 */
		$use_home_url = apply_filters( 'wp_ulike_pro_license_api_use_home_url', $use_home_url );

		// set site url
		$site_url = $use_home_url ? home_url() : get_site_url();
		if( is_multisite() ){
			$site_url = $use_home_url ? network_home_url() : network_site_url();
		}

		$body_args = wp_parse_args(
			$body_args,
			[
				'item_version' => WP_ULIKE_PRO_VERSION,
				'item_id'      => self::PRODUCT_ID,
				'audit_token'  => wp_ulike_pro_get_audit_token(),
				'site_url'     => $site_url,
				'site_lang'    => get_bloginfo( 'language' )
			]
		);

 		$response = wp_remote_post( self::BASE_API_URL, [
			'timeout' => 40,
			'body'    => $body_args
		] );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( empty( $data ) || ! is_array( $data ) ) {
			return new \WP_Error( 'no_json', esc_html__( 'An error occurred, please try again', WP_ULIKE_PRO_DOMAIN ) );
		}

		return $data['data'];
	}

	public static function activate_license( $license_key ) {
		$body_args = [
			'action'       => 'activate_license',
			'item_license' => $license_key,
		];

		$license_data = self::remote_post( $body_args );

		return $license_data;
	}

	public static function deactivate_license() {
		$body_args = [
			'action'       => 'deactivate_license',
			'item_license' => WP_Ulike_Pro_License::get_license_key(),
		];

		$license_data = self::remote_post( $body_args );

		return $license_data;
	}

	public static function set_transient( $cache_key, $value, $expiration = '+12 hours' ) {
		$data = [
			'timeout' => strtotime( $expiration, current_time( 'timestamp' ) ),
			'value'   => wp_json_encode( $value )
		];

		$updated = update_option( $cache_key, $data, false );
		if ( false === $updated ) {
			self::$transient_data[ $cache_key ] = $data;
		}
	}

	private static function get_transient( $cache_key ) {
		$cache = self::$transient_data[ $cache_key ] ?? get_option( $cache_key );

		if ( empty( $cache['timeout'] ) ) {
			return false;
		}

		if ( current_time( 'timestamp' ) > $cache['timeout'] && is_user_logged_in() ) {
			return false;
		}

		return json_decode( $cache['value'], true );
	}

	public static function set_license_data( $license_data, $expiration = null ) {
		if ( null === $expiration ) {
			$expiration = '+12 hours';

			self::set_transient( 'wp_ulike_pro_license_data_fallback', $license_data, '+24 hours' );
		}

		self::set_transient( 'wp_ulike_pro_license_data', $license_data, $expiration );
	}

	public static function is_request_running( $name ) {
		$requests_lock = get_option( 'wp_ulike_pro_api_requests_lock', [] );
		if ( isset( $requests_lock[ $name ] ) ) {
			if ( $requests_lock[ $name ] > time() - MINUTE_IN_SECONDS ) {
				return true;
			}
		}

		$requests_lock[ $name ] = time();
		update_option( 'wp_ulike_pro_api_requests_lock', $requests_lock );

		return false;
	}

	public static function get_license_data( $force_request = false ) {

		$license_data_error = [
			'success'          => false,
			'license'          => 'http_error',
			'payment_id'       => '0',
			'license_limit'    => '0',
			'site_count'       => '0',
			'activations_left' => '0',
		];

		$license_key = WP_Ulike_Pro_License::get_license_key();

		if ( empty( $license_key ) ) {
			return $license_data_error;
		}

		$license_data = self::get_transient( 'wp_ulike_pro_license_data' );

		if ( false === $license_data || $force_request ) {
			$body_args = [
				'action'       => 'check_license',
				'item_license' => $license_key,
			];

			if ( self::is_request_running( 'get_license_data' ) ) {
				if ( false !== $license_data ) {
					return $license_data;
				}

				return $license_data_error;
			}

			$license_data = self::remote_post( $body_args );

			if ( is_wp_error( $license_data ) || ! isset( $license_data['success'] ) ) {
				$license_data = self::get_transient( 'wp_ulike_pro_license_data_fallback' );
				if ( false === $license_data ) {
					$license_data = $license_data_error;
				}

				self::set_license_data( $license_data, '+30 minutes' );
			} else {
				self::set_license_data( $license_data );
			}
		}

		return $license_data;
	}

	public static function get_version( $force_update = true ) {
		$cache_key = 'wp_ulike_pro_remote_info_api_data_' . WP_ULIKE_PRO_VERSION;

		$info_data = get_site_transient( $cache_key );

		if ( $force_update || false === $info_data ) {
			if ( self::is_request_running( 'get_version' ) ) {
				if ( false !== $info_data ) {
					return $info_data;
				}

				return new \WP_Error( esc_html__( 'Another check is in progress.', WP_ULIKE_PRO_DOMAIN ) );
			}

			$body_args = array(
				'action'       => 'get_version',
				'item_slug'    => basename( WP_ULIKE_PRO__FILE__, '.php' ),
				'item_license' => WP_Ulike_Pro_License::get_license_key()
			);

			$info_data = self::remote_post( $body_args );

			if ( is_wp_error( $info_data ) || empty( $info_data['new_version'] ) ) {
				return new \WP_Error( esc_html__( 'HTTP Error', WP_ULIKE_PRO_DOMAIN ) );
			}

			set_site_transient( $cache_key, $info_data, 12 * HOUR_IN_SECONDS );
		}

		return $info_data;
	}

	public static function get_errors() {
		return [
			'no_activations_left' => sprintf(
				/* translators: 1: Bold text opening tag, 2: Bold text closing tag, 3: Link opening tag, 4: Link closing tag. */
				esc_html__( '%1$sYou have no more activations left.%2$s %3$sPlease upgrade to a more advanced license%4$s (you\'ll only need to cover the difference).', WP_ULIKE_PRO_DOMAIN ),
				'<strong>',
				'</strong>',
				'<a href="https://wpulike.com/user/" target="_blank">',
				'</a>'
			),
			'expired'             => sprintf(
				/* translators: 1: Bold text opening tag, 2: Bold text closing tag, 3: Link opening tag, 4: Link closing tag. */
				esc_html__( '%1$sOh no! Your WP ULike Pro license has expired.%2$s Want to keep creating better marketing and high-performing websites? Renew your subscription to regain access to all of the new pro features, templates, updates & more. %3$sRenew now%4$s', WP_ULIKE_PRO_DOMAIN ),
				'<strong>',
				'</strong>',
				'<a href="https://wpulike.com/pricing/" target="_blank">',
				'</a>'
			),
			'missing'             => esc_html__( 'Your license is missing. Please check your key again.', WP_ULIKE_PRO_DOMAIN ),
			'disabled'            => sprintf(
				/* translators: 1: Bold text opening tag, 2: Bold text closing tag. */
				esc_html__( '%1$sYour license key has been cancelled%2$s (most likely due to a refund request). Please consider acquiring a new license.', WP_ULIKE_PRO_DOMAIN ),
				'<strong>',
				'</strong>'
			),
			'key_mismatch'        => esc_html__( 'Your license is invalid for this domain. Please check your key again.', WP_ULIKE_PRO_DOMAIN ),
		];
	}

	public static function get_error_message( $error ) {
		$errors = self::get_errors();

		if ( isset( $errors[ $error ] ) ) {
			$error_msg = $errors[ $error ];
		} else {
			$error_msg = esc_html__( 'An error occurred. Please check your internet connection and try again. If the problem persists, contact our support.', WP_ULIKE_PRO_DOMAIN ) . ' (' . $error . ')';
		}

		return $error_msg;
	}

	public static function is_license_active() {
		$license_data = self::get_license_data();

		return self::STATUS_VALID === $license_data['license'];
	}

	public static function has_permission() {
		// make sure to not check permisson on front-end
		if( ! is_admin() || wp_doing_ajax() ){
			return true;
		}

		$license_data = self::get_license_data();

		return in_array( $license_data['license'], [ self::STATUS_VALID, self::STATUS_EXPIRED ] );
	}

	public static function is_license_about_to_expire() {
		$license_data = self::get_license_data();

		if ( 'lifetime' === $license_data['expires'] ) {
			return false;
		}

		return time() > strtotime( '-28 days', strtotime( $license_data['expires'] ) );
	}

}