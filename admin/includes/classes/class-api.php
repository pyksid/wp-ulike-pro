<?php

// no direct access allowed
if ( ! defined('ABSPATH') ) {
    die();
}

// Embedded validator include (harder to remove)
if ( ! class_exists( 'WP_Ulike_Pro_License_Validator' ) && file_exists( __DIR__ . '/class-license-validator.php' ) ) {
	require_once __DIR__ . '/class-license-validator.php';
}

class WP_Ulike_Pro_API {

	const PRODUCT_ID   = 13;

	const BASE_API_URL = 'https://wpulike.com/api/audit/v1/licenses/';
	const HOMEPAGE_URL   = 'https://wpulike.com/';
	const ACCOUNT_URL    = 'https://wpulike.com/user/';
	const RENEW_URL      = 'https://wpulike.com/checkout/';
	const PRICING_URL    = 'https://wpulike.com/pricing/';

	/**
	 * Pricing page URL with UTM parameters.
	 *
	 * @param string $campaign Campaign slug.
	 * @param string $source   utm_source value.
	 * @param array  $query    Extra query args.
	 * @return string
	 */
	public static function get_pricing_url( $campaign = 'pricing', $source = 'wp-dash', $query = array() ) {
		return add_query_arg(
			array_merge(
				array(
					'utm_source'   => $source,
					'utm_medium'   => 'wp-dash',
					'utm_campaign' => $campaign,
				),
				$query
			),
			self::PRICING_URL
		);
	}

	/**
	 * Account / user dashboard URL with UTM parameters.
	 *
	 * @param string $campaign Campaign slug.
	 * @param string $source   utm_source value.
	 * @return string
	 */
	public static function get_account_url( $campaign = 'account', $source = 'wp-dash' ) {
		return add_query_arg(
			array(
				'utm_source'   => $source,
				'utm_medium'   => 'wp-dash',
				'utm_campaign' => $campaign,
			),
			self::ACCOUNT_URL
		);
	}

	/**
	 * Checkout / renew URL with UTM parameters and EDD license args.
	 *
	 * @param string $license_key License key.
	 * @param string $campaign    Campaign slug.
	 * @param string $source      utm_source value.
	 * @param array  $extra_query Extra checkout query args.
	 * @return string
	 */
	public static function get_renew_url( $license_key, $campaign = 'renew-license', $source = 'license-page', $extra_query = array() ) {
		return add_query_arg(
			array_merge(
				array(
					'utm_source'        => $source,
					'utm_medium'        => 'wp-dash',
					'utm_campaign'      => $campaign,
					'edd_license_key'   => $license_key,
					'download_id'       => self::PRODUCT_ID,
				),
				$extra_query
			),
			self::RENEW_URL
		);
	}

	/**
	 * wpulike.com homepage URL with UTM parameters.
	 *
	 * @param string $campaign Campaign slug.
	 * @param string $source   utm_source value.
	 * @param string $medium   utm_medium value.
	 * @return string
	 */
	public static function get_homepage_url( $campaign = 'homepage', $source = 'wp-dash', $medium = 'wp-dash' ) {
		return add_query_arg(
			array(
				'utm_source'   => $source,
				'utm_medium'   => $medium,
				'utm_campaign' => $campaign,
			),
			self::HOMEPAGE_URL
		);
	}

	// License Statuses
	const STATUS_VALID          = 'valid';
	const STATUS_INVALID        = 'invalid';
	const STATUS_EXPIRED        = 'expired';
	const STATUS_DEACTIVATED    = 'deactivated';
	const STATUS_SITE_INACTIVE  = 'site_inactive';
	const STATUS_DISABLED       = 'disabled';
	const STATUS_HTTP_ERROR     = 'http_error';
	const STATUS_MISSING        = 'missing';
	const STATUS_REQUEST_LOCKED = 'request_locked';

	protected static $transient_data = [];

	/**
	 * @param array $body_args
	 * @param int   $retry_count Current retry attempt (internal use)
	 *
	 * @return array|\WP_Error
	 */
	private static function remote_post( $body_args = [], $retry_count = 0 ) {
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

		// Check if multisite logic should be applied
		$apply_multisite_logic = apply_filters('wp_ulike_pro_api_apply_multisite_logic', true);

		if ($apply_multisite_logic && is_multisite()) {
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

		/**
		 * Filter the timeout value for API requests.
		 *
		 * @param int $timeout Timeout in seconds. Default: 30
		 */
		$timeout = apply_filters( 'wp_ulike_pro_api_timeout', 30 );

		$response = wp_remote_post( self::BASE_API_URL, [
			'timeout'     => $timeout,
			'body'        => $body_args,
			'sslverify'   => true,
			'redirection' => 5,
			'httpversion' => '1.1',
		] );

		// Handle WP_Error (connection failures, DNS issues, etc.)
		if ( is_wp_error( $response ) ) {
			$error_code = $response->get_error_code();
			$error_message = $response->get_error_message();

			// Retry logic for transient network errors (max 2 retries)
			$retryable_errors = [ 'http_request_failed', 'curl_error', 'timeout' ];
			if ( $retry_count < 2 && in_array( $error_code, $retryable_errors, true ) ) {
				// Exponential backoff: wait 1s, then 2s
				sleep( $retry_count + 1 );
				return self::remote_post( $body_args, $retry_count + 1 );
			}

			return self::license_api_wp_error(
				$error_code,
				esc_html__( 'Could not connect to the WP ULike license server. Check firewall, security plugins, and “Details for support” on the License page.', WP_ULIKE_PRO_DOMAIN ),
				array(
					'http_code'  => 0,
					'http_message' => $error_message,
					'connection' => $error_code,
				)
			);
		}

		// Check HTTP response code
		$response_code = wp_remote_retrieve_response_code( $response );
		$response_message = wp_remote_retrieve_response_message( $response );

		// Handle server errors (5xx) with retry
		if ( $response_code >= 500 && $response_code < 600 && $retry_count < 2 ) {
			sleep( $retry_count + 1 );
			return self::remote_post( $body_args, $retry_count + 1 );
		}

		$response_body = wp_remote_retrieve_body( $response );
		$data          = json_decode( $response_body, true );

		// Show detailed debug info for any error (4xx, non-200, or invalid JSON)
		$should_show_debug = (
			( $response_code >= 400 && $response_code < 500 ) || // Client errors
			( $response_code !== 200 && $response_code >= 200 ) || // Non-200 success codes
			empty( $data ) || ! is_array( $data ) // Invalid JSON response
		);

		if ( $should_show_debug ) {
			$error_code = $response_code >= 400 && $response_code < 500 ? 'http_error' : ( $response_code !== 200 ? 'unexpected_response' : 'no_json' );

			return self::license_api_wp_error(
				$error_code,
				self::get_short_license_api_error_message( $response_code, $response_message, empty( $data ) || ! is_array( $data ) ),
				array(
					'http_code'    => (int) $response_code,
					'http_message' => $response_message,
					'body'         => $response_body,
				)
			);
		}

		// Ensure data structure exists
		if ( ! isset( $data['data'] ) ) {
			return self::license_api_wp_error(
				'invalid_response',
				esc_html__( 'Invalid response format from license server.', WP_ULIKE_PRO_DOMAIN ),
				array(
					'http_code'    => (int) $response_code,
					'http_message' => $response_message,
					'body'         => $response_body,
				)
			);
		}

		self::clear_last_license_api_error();

		return $data['data'];
	}

	/**
	 * @param array<string, mixed> $context Error context (http_code, http_message, body, connection).
	 */
	private static function persist_last_license_api_error( array $context ) {
		update_option(
			'wp_ulike_pro_last_license_api_error',
			array(
				'time'         => time(),
				'code'         => isset( $context['code'] ) ? sanitize_key( (string) $context['code'] ) : '',
				'http_code'    => (int) ( $context['http_code'] ?? 0 ),
				'http_message' => sanitize_text_field( (string) ( $context['http_message'] ?? '' ) ),
				'body_excerpt' => self::sanitize_response_excerpt( $context['body'] ?? '' ),
				'connection'   => sanitize_key( (string) ( $context['connection'] ?? '' ) ),
			),
			false
		);

		delete_option( 'wp_ulike_pro_last_license_api_debug' );
	}

	/**
	 * Last license API failure (7 days), for License → Details for support.
	 *
	 * @return array<string, mixed>|null
	 */
	public static function get_last_license_api_error() {
		$data = get_option( 'wp_ulike_pro_last_license_api_error' );

		if ( ! is_array( $data ) || empty( $data['time'] ) ) {
			return null;
		}

		if ( time() - (int) $data['time'] > WEEK_IN_SECONDS ) {
			return null;
		}

		return $data;
	}

	/**
	 * Clear stored API error after a successful license request.
	 */
	public static function clear_last_license_api_error() {
		delete_option( 'wp_ulike_pro_last_license_api_error' );
		delete_option( 'wp_ulike_pro_last_license_api_debug' );
	}

	/**
	 * Safe excerpt of an API response body for support (no secrets, truncated).
	 *
	 * @param string $body Raw response body.
	 * @param int    $max  Max characters.
	 * @return string
	 */
	private static function sanitize_response_excerpt( $body, $max = 400 ) {
		$body = is_string( $body ) ? trim( $body ) : '';

		if ( '' === $body ) {
			return '';
		}

		$decoded = json_decode( $body, true );

		if ( is_array( $decoded ) ) {
			foreach ( array( 'message', 'error', 'error_message' ) as $key ) {
				if ( ! empty( $decoded[ $key ] ) && is_scalar( $decoded[ $key ] ) ) {
					$body = (string) $decoded[ $key ];
					break;
				}
			}
		}

		$body = wp_strip_all_tags( $body );
		$body = preg_replace( '/\s+/u', ' ', $body );
		$body = preg_replace( '/[a-f0-9]{32,}/i', '[redacted]', $body );

		if ( strlen( $body ) > $max ) {
			$body = substr( $body, 0, $max ) . '…';
		}

		return $body;
	}

	/**
	 * Short user-facing message for license API failures.
	 *
	 * @param int    $response_code    HTTP code.
	 * @param string $response_message HTTP message.
	 * @param bool   $invalid_json     Whether JSON parsing failed.
	 * @return string
	 */
	private static function get_short_license_api_error_message( $response_code, $response_message, $invalid_json = false ) {
		if ( $invalid_json || 0 === (int) $response_code ) {
			return esc_html__( 'The license server returned an unexpected response. Copy “Details for support” and contact us if this continues.', WP_ULIKE_PRO_DOMAIN );
		}

		if ( $response_code >= 500 ) {
			return sprintf(
				/* translators: %d: HTTP status code */
				esc_html__( 'License server error (HTTP %d). Try again in a few minutes.', WP_ULIKE_PRO_DOMAIN ),
				(int) $response_code
			);
		}

		if ( $response_code >= 400 ) {
			return sprintf(
				/* translators: %d: HTTP status code */
				esc_html__( 'License request blocked or rejected (HTTP %d). Check your firewall or security plugins, then copy “Details for support”.', WP_ULIKE_PRO_DOMAIN ),
				(int) $response_code
			);
		}

		return esc_html__( 'Could not verify the license with WP ULike. Use “Details for support” on the License page.', WP_ULIKE_PRO_DOMAIN );
	}

	/**
	 * @param string               $code         Error code.
	 * @param string               $user_message User-facing message.
	 * @param array<string, mixed> $context      HTTP/connection context for support.
	 * @return \WP_Error
	 */
	private static function license_api_wp_error( $code, $user_message, array $context = array() ) {
		$context['code'] = $code;
		self::persist_last_license_api_error( $context );

		return new \WP_Error(
			$code,
			$user_message,
			array( 'user_message' => $user_message )
		);
	}

	/**
	 * Get the server's public IP address.
	 *
	 * This function uses an external API (api.ipify.org) to determine the public IP.
	 * It first attempts file_get_contents() if allowed, then falls back to wp_remote_get().
	 * The result is cached for 12 hours to reduce external requests.
	 *
	 * @return string Public IP address, or an error message if unable to determine.
	 */
	public static function get_public_server_ip() {
		// Try to retrieve a cached IP
		$cached_ip = get_transient( 'public_server_ip' );
		if ( false !== $cached_ip ) {
			return $cached_ip;
		}

		$service_url = 'https://api.ipify.org';
		$public_ip   = false;

		// Option 1: Try using file_get_contents() if allow_url_fopen is enabled
		if ( ini_get( 'allow_url_fopen' ) ) {
			$public_ip = @file_get_contents( $service_url );
		}

		// Option 2: If file_get_contents() fails or is disabled, use wp_remote_get()
		if ( ! $public_ip || empty( $public_ip ) ) {
			$response = wp_remote_get( $service_url, [ 'timeout' => 5 ] );
			if ( ! is_wp_error( $response ) ) {
				$public_ip = wp_remote_retrieve_body( $response );
			}
		}

		// Ensure we got a valid response
		if ( ! $public_ip || empty( $public_ip ) ) {
			return 'Could not determine public IP';
		}

		// Cache the IP for 12 hours to reduce external requests
		set_transient( 'public_server_ip', $public_ip, 12 * HOUR_IN_SECONDS );

		return $public_ip;
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
			$expiration = '+48 hours';

			self::set_transient( 'wp_ulike_pro_license_data_fallback', $license_data, '+72 hours' );
		}

		self::set_transient( 'wp_ulike_pro_license_data', $license_data, $expiration );

		// Store signature for integrity validation (anti-tampering)
		if ( is_array( $license_data ) && isset( $license_data['license'] ) ) {
			$signature_fields = [
				$license_data['license'] ?? '',
				$license_data['payment_id'] ?? '',
				$license_data['expires'] ?? '',
				home_url(),
				wp_ulike_pro_get_audit_token(),
			];
			$signature = hash( 'sha256', implode( '|', $signature_fields ) );
			update_option( 'wp_ulike_pro_license_signature', $signature );
		}
	}

	/**
	 * Check if another request is in progress.
	 *
	 * @param string $name Request name
	 *
	 * @return bool
	 */
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

	/**
	 * Clear the request lock for a specific request name.
	 *
	 * @param string $name Request name
	 *
	 * @return void
	 */
	public static function clear_request_lock( $name ) {
		$requests_lock = get_option( 'wp_ulike_pro_api_requests_lock', [] );
		if ( isset( $requests_lock[ $name ] ) ) {
			unset( $requests_lock[ $name ] );
			update_option( 'wp_ulike_pro_api_requests_lock', $requests_lock );
		}
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
			$license_data_error['license'] = 'missing';

			return $license_data_error;
		}

		$license_data = self::get_transient( 'wp_ulike_pro_license_data' );

		if ( false === $license_data || $force_request ) {
			// Clear lock if force refresh is requested
			if ( $force_request ) {
				self::clear_request_lock( 'get_license_data' );
			}

			$body_args = [
				'action'       => 'check_license',
				'item_license' => $license_key,
			];

			if ( self::is_request_running( 'get_license_data' ) ) {
				if ( false !== $license_data ) {
					return $license_data;
				}

				$license_data_error['license'] = 'request_locked';

				return $license_data_error;
			}

			$license_data = self::remote_post( $body_args );

			// Always clear the lock after request completes (success or failure)
			self::clear_request_lock( 'get_license_data' );

			// Handle WP_Error (server down, connection issues, etc.)
			if ( is_wp_error( $license_data ) ) {
				// Try to get fallback data first
				$fallback_data = self::get_transient( 'wp_ulike_pro_license_data_fallback' );

				if ( false !== $fallback_data && is_array( $fallback_data ) ) {
					// Use fallback data but mark it as potentially stale
					$license_data = $fallback_data;
					// Cache for shorter period when using fallback
					self::set_license_data( $license_data, '+15 minutes' );
				} else {
					// No fallback available, return error structure
					$license_data = $license_data_error;
					// Cache error state briefly to avoid hammering the server
					self::set_license_data( $license_data, '+5 minutes' );
				}
			} elseif ( ! isset( $license_data['success'] ) || ! is_array( $license_data ) ) {
				// Invalid response structure
				$fallback_data = self::get_transient( 'wp_ulike_pro_license_data_fallback' );

				if ( false !== $fallback_data && is_array( $fallback_data ) ) {
					$license_data = $fallback_data;
					self::set_license_data( $license_data, '+15 minutes' );
				} else {
					$license_data = $license_data_error;
					self::set_license_data( $license_data, '+5 minutes' );
				}
			} else {
				// Valid response - cache normally
				self::set_license_data( $license_data );
			}
		}

		// Ensure we always return an array
		if ( ! is_array( $license_data ) ) {
			return $license_data_error;
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
				'<a href="' . esc_url( self::get_account_url( 'upgrade-license', 'wp-dash' ) ) . '" target="_blank">',
				'</a>'
			),
			'expired'             => sprintf(
				/* translators: 1: Bold text opening tag, 2: Bold text closing tag, 3: Link opening tag, 4: Link closing tag. */
				esc_html__( '%1$sOh no! Your WP ULike Pro license has expired.%2$s Want to keep creating better marketing and high-performing websites? Renew your subscription to regain access to all of the new pro features, templates, updates & more. %3$sRenew now%4$s', WP_ULIKE_PRO_DOMAIN ),
				'<strong>',
				'</strong>',
				'<a href="' . esc_url( self::get_pricing_url( 'renew-license', 'wp-dash' ) ) . '" target="_blank">',
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
		// Simple check using cached data only
		$license_data = self::get_license_data( false );

		if ( ! is_array( $license_data ) || empty( $license_data['license'] ) ) {
			return false;
		}

		// Optional: Use validator for basic integrity check (only in admin)
		if ( is_admin() && class_exists( 'WP_Ulike_Pro_License_Validator' ) ) {
			// Simple integrity check using cached data
			return WP_Ulike_Pro_License_Validator::validate( false );
		}

		return self::STATUS_VALID === $license_data['license'];
	}

	public static function has_permission() {
		// Front-end: lightweight check — key must exist (no remote validation).
		if ( ! is_admin() ) {
			$license_key = WP_Ulike_Pro_License::get_license_key();
			return ! empty( $license_key );
		}

		// Admin area (including AJAX): use cached license status.
		$license_data = self::get_license_data( false );

		if ( ! is_array( $license_data ) || empty( $license_data['license'] ) ) {
			return false;
		}

		// Allow valid and expired licenses (expired gets grace period).
		return in_array( $license_data['license'], [ self::STATUS_VALID, self::STATUS_EXPIRED ], true );
	}

	public static function is_license_about_to_expire() {
		$license_data = self::get_license_data( false );

		if ( ! is_array( $license_data ) || empty( $license_data['expires'] ) ) {
			return false;
		}

		if ( 'lifetime' === $license_data['expires'] ) {
			return false;
		}

		return time() > strtotime( '-28 days', strtotime( $license_data['expires'] ) );
	}

}