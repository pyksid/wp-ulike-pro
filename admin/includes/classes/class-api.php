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
	const RENEW_URL    = 'https://wpulike.com/checkout/';
	const PRICING_URL  = 'https://wpulike.com/pricing/';

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

			// Return user-friendly error message
			return new \WP_Error(
				$error_code,
				sprintf(
					'<p><strong>%s</strong></p><p>%s</p><p>%s <a href="mailto:info@wpulike.com">info@wpulike.com</a>.</p>',
					esc_html__( 'Connection Error', WP_ULIKE_PRO_DOMAIN ),
					esc_html__( 'Unable to connect to the license server. This could be due to:', WP_ULIKE_PRO_DOMAIN ),
					esc_html__( 'If the problem persists, please contact our support at', WP_ULIKE_PRO_DOMAIN )
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
		$data = json_decode( $response_body, true );

		// Show detailed debug info for any error (4xx, non-200, or invalid JSON)
		$should_show_debug = (
			( $response_code >= 400 && $response_code < 500 ) || // Client errors
			( $response_code !== 200 && $response_code >= 200 ) || // Non-200 success codes
			empty( $data ) || ! is_array( $data ) // Invalid JSON response
		);

		if ( $should_show_debug ) {
			return new \WP_Error(
				$response_code >= 400 && $response_code < 500 ? 'http_error' : ( $response_code !== 200 ? 'unexpected_response' : 'no_json' ),
				self::get_debug_error_message( $response, $response_code, $response_message, $response_body, $body_args, $site_url )
			);
		}

		// Ensure data structure exists
		if ( ! isset( $data['data'] ) ) {
			return new \WP_Error(
				'invalid_response',
				esc_html__( 'Invalid response format from license server.', WP_ULIKE_PRO_DOMAIN )
			);
		}

		return $data['data'];
	}

	/**
	 * Generate comprehensive debug error message with copy functionality
	 *
	 * @param array|\WP_Error $response The HTTP response
	 * @param int $response_code HTTP response code
	 * @param string $response_message HTTP response message
	 * @param string $response_body Response body
	 * @param array $body_args Request body arguments
	 * @param string $site_url Site URL
	 * @return string HTML formatted debug message
	 */
	private static function get_debug_error_message( $response, $response_code, $response_message, $response_body, $body_args, $site_url ) {
		// Safely get server info without blocking on IP lookup
		$remote_ip = isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) : ( isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'Unknown' );

		// Try to get public IP, but don't block if it fails
		$public_ip = 'Not available';
		try {
			$public_ip = self::get_public_server_ip();
		} catch ( Exception $e ) {
			// Silently fail - public IP is not critical for error reporting
		}

		// Get response headers
		$response_headers = wp_remote_retrieve_headers( $response );
		$headers_string = '';
		if ( $response_headers && is_array( $response_headers ) ) {
			$headers_array = [];
			foreach ( $response_headers as $key => $value ) {
				$headers_array[] = $key . ': ' . ( is_array( $value ) ? implode( ', ', $value ) : $value );
			}
			$headers_string = implode( "\n", $headers_array );
		}

		// Prepare request body (hide sensitive data)
		$request_body = $body_args;
		if ( isset( $request_body['item_license'] ) ) {
			$request_body['item_license'] = substr( $request_body['item_license'], 0, 8 ) . '...' . substr( $request_body['item_license'], -4 );
		}
		$request_body_json = wp_json_encode( $request_body, JSON_PRETTY_PRINT );

		// Get additional debugging information
		$server_software = isset( $_SERVER['SERVER_SOFTWARE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) : 'Unknown';
		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : 'Unknown';
		$memory_limit = ini_get( 'memory_limit' );
		$max_execution_time = ini_get( 'max_execution_time' );
		$curl_version = function_exists( 'curl_version' ) ? curl_version() : false;
		$curl_info = $curl_version ? ( $curl_version['version'] . ' (SSL: ' . ( isset( $curl_version['ssl_version'] ) ? $curl_version['ssl_version'] : 'N/A' ) . ')' ) : 'Not available';
		$openssl_version = defined( 'OPENSSL_VERSION_TEXT' ) ? OPENSSL_VERSION_TEXT : ( function_exists( 'openssl_version_text' ) ? openssl_version_text() : 'Not available' );

		// WordPress debug info
		$wp_debug = defined( 'WP_DEBUG' ) && WP_DEBUG ? 'Yes' : 'No';
		$wp_debug_log = defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ? 'Yes' : 'No';
		$wp_debug_display = defined( 'WP_DEBUG_DISPLAY' ) && WP_DEBUG_DISPLAY ? 'Yes' : 'No';

		// Active plugins count
		$active_plugins = get_option( 'active_plugins', [] );
		$active_plugins_count = is_array( $active_plugins ) ? count( $active_plugins ) : 0;
		if ( is_multisite() ) {
			$network_plugins = get_site_option( 'active_sitewide_plugins', [] );
			$active_plugins_count += is_array( $network_plugins ) ? count( $network_plugins ) : 0;
		}

		// Theme info
		$theme = wp_get_theme();
		$theme_name = $theme->get( 'Name' ) ?: 'Unknown';
		$theme_version = $theme->get( 'Version' ) ?: 'Unknown';

		// SSL/TLS info
		$is_ssl = is_ssl() ? 'Yes' : 'No';
		$ssl_version = isset( $_SERVER['SERVER_PROTOCOL'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_PROTOCOL'] ) ) : 'Unknown';

		// Request timing (if available from response)
		$request_time = isset( $response['http_response'] ) && method_exists( $response['http_response'], 'get_response_object' ) ? 'Available' : 'Not tracked';

		// Build comprehensive debug message
		$debug_message = sprintf(
			"=== WP ULike Pro API Debug Information ===\n\n" .
			"ERROR DETAILS:\n" .
			"Response Code: %s\n" .
			"Response Message: %s\n" .
			"API Endpoint: %s\n" .
			"Request Method: POST\n\n" .
			"SERVER INFORMATION:\n" .
			"Server IP: %s\n" .
			"Remote IP: %s\n" .
			"Public IP: %s\n" .
			"Server Software: %s\n" .
			"Site URL: %s\n" .
			"Is Multisite: %s\n" .
			"Is SSL: %s\n" .
			"Protocol: %s\n\n" .
			"SOFTWARE VERSIONS:\n" .
			"PHP Version: %s\n" .
			"WordPress Version: %s\n" .
			"WP ULike Pro Version: %s\n" .
			"cURL Version: %s\n" .
			"OpenSSL Version: %s\n" .
			"Theme: %s (v%s)\n" .
			"Active Plugins Count: %d\n\n" .
			"PHP CONFIGURATION:\n" .
			"Memory Limit: %s\n" .
			"Max Execution Time: %s\n" .
			"allow_url_fopen: %s\n\n" .
			"WORDPRESS DEBUG:\n" .
			"WP_DEBUG: %s\n" .
			"WP_DEBUG_LOG: %s\n" .
			"WP_DEBUG_DISPLAY: %s\n\n" .
			"REQUEST INFORMATION:\n" .
			"User Agent: %s\n" .
			"Request Body: %s\n\n" .
			"RESPONSE INFORMATION:\n" .
			"Response Headers: %s\n" .
			"Response Body: %s\n\n" .
			"TIMESTAMP:\n" .
			"Date: %s\n" .
			"Timezone: %s\n",
			$response_code ?: 'Unknown',
			$response_message ?: 'Unknown',
			self::BASE_API_URL,
			isset( $_SERVER['SERVER_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_ADDR'] ) ) : 'Unknown',
			$remote_ip,
			$public_ip,
			$server_software,
			esc_url( $site_url ),
			is_multisite() ? 'Yes' : 'No',
			$is_ssl,
			$ssl_version,
			phpversion(),
			get_bloginfo( 'version' ),
			defined( 'WP_ULIKE_PRO_VERSION' ) ? WP_ULIKE_PRO_VERSION : 'Unknown',
			$curl_info,
			$openssl_version,
			$theme_name,
			$theme_version,
			$active_plugins_count,
			$memory_limit,
			$max_execution_time,
			ini_get( 'allow_url_fopen' ) ? 'Enabled' : 'Disabled',
			$wp_debug,
			$wp_debug_log,
			$wp_debug_display,
			$user_agent,
			$request_body_json ?: 'Not available',
			$headers_string ?: 'Not available',
			! empty( $response_body ) ? ( strlen( $response_body ) > 1000 ? substr( $response_body, 0, 1000 ) . '... (truncated)' : $response_body ) : 'Empty response',
			current_time( 'mysql' ),
			wp_timezone_string()
		);

		// Simple debug message with pre/code block
		$debug_info = sprintf(
			'<div class="notice notice-error" style="margin: 15px 0;">
				<p><strong>%s</strong></p>
				<p>%s <a href="mailto:info@wpulike.com">info@wpulike.com</a> %s:</p>
				<pre style="background: #f4f4f4; padding: 15px; border: 1px solid #ddd; border-radius: 4px; overflow: auto; max-height: 500px; max-width: 100%%; font-size: 12px; line-height: 1.6; white-space: pre-wrap; word-wrap: break-word;"><code style="display: block; overflow: auto;">%s</code></pre>
			</div>',
			esc_html__( 'Error occurred', WP_ULIKE_PRO_DOMAIN ),
			esc_html__( 'If the problem persists, please contact our support at', WP_ULIKE_PRO_DOMAIN ),
			esc_html__( 'and share the following debug information', WP_ULIKE_PRO_DOMAIN ),
			esc_html( trim( $debug_message ) )
		);

		return $debug_info;
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
		// Front-end: Just check if license key exists (no validation, no server requests)
		if( ! is_admin() || wp_doing_ajax() ){
			$license_key = WP_Ulike_Pro_License::get_license_key();
			return ! empty( $license_key );
		}

		// Admin area: Simple check using cached data
		$license_data = self::get_license_data( false );

		if ( ! is_array( $license_data ) || empty( $license_data['license'] ) ) {
			return false;
		}

		// Allow valid and expired licenses (expired gets grace period)
		return in_array( $license_data['license'], [ self::STATUS_VALID, self::STATUS_EXPIRED ] );
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