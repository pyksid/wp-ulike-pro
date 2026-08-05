<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Embedded validator include (harder to remove)
if ( ! class_exists( 'WP_Ulike_Pro_License_Validator' ) && file_exists( __DIR__ . '/class-license-validator.php' ) ) {
	require_once __DIR__ . '/class-license-validator.php';
}

class WP_Ulike_Pro_License {

	const PAGE_ID = 'wp-ulike-pro-license';

	/**
	 * Admin notices & error cards for non-valid license states.
	 *
	 * @param array $context Optional. Keys: renew_url, license_page_url, pricing_url.
	 * @return array<string, array<string, string>>
	 */
	public static function get_errors_details( $context = array() ) {
		$license_page = ! empty( $context['license_page_url'] ) ? $context['license_page_url'] : self::get_url();
		$pricing_url  = ! empty( $context['pricing_url'] ) ? $context['pricing_url'] : WP_Ulike_Pro_API::get_pricing_url( 'pricing', 'license-page' );
		$renew_url    = ! empty( $context['renew_url'] ) ? $context['renew_url'] : $pricing_url;

		return [
			WP_Ulike_Pro_API::STATUS_EXPIRED => [
				'title'         => esc_html__( 'Your license has expired', WP_ULIKE_PRO_DOMAIN ),
				'description'   => esc_html__( 'Renew your subscription to restore automatic updates, support, and Pro features on this site.', WP_ULIKE_PRO_DOMAIN ),
				'button_text'   => esc_html__( 'Renew license', WP_ULIKE_PRO_DOMAIN ),
				'button_url'    => $renew_url,
				'button_target' => '_blank',
			],
			WP_Ulike_Pro_API::STATUS_DISABLED => [
				'title'         => esc_html__( 'License cancelled', WP_ULIKE_PRO_DOMAIN ),
				'description'   => esc_html__( 'This license was cancelled (for example after a refund) and cannot be used again. Purchase a new license, then enter the new key on the License page. Contact support if you believe this is a mistake.', WP_ULIKE_PRO_DOMAIN ),
				'button_text'   => esc_html__( 'Get a new license', WP_ULIKE_PRO_DOMAIN ),
				'button_url'    => $pricing_url,
				'button_target' => '_blank',
			],
			WP_Ulike_Pro_API::STATUS_INVALID => [
				'title'         => esc_html__( 'License does not match this site', WP_ULIKE_PRO_DOMAIN ),
				'description'   => esc_html__( 'The saved key is not valid for this website address (common after a domain change or HTTPS migration). Open the License page and click Reactivate for this site — you do not need a new purchase.', WP_ULIKE_PRO_DOMAIN ),
				'button_text'   => esc_html__( 'Open License page', WP_ULIKE_PRO_DOMAIN ),
				'button_url'    => $license_page,
				'button_target' => '_self',
			],
			WP_Ulike_Pro_API::STATUS_SITE_INACTIVE => [
				'title'         => esc_html__( 'License not active on this site', WP_ULIKE_PRO_DOMAIN ),
				'description'   => esc_html__( 'Your key is valid but not activated for this website address. Open the License page and click Reactivate for this site.', WP_ULIKE_PRO_DOMAIN ),
				'button_text'   => esc_html__( 'Open License page', WP_ULIKE_PRO_DOMAIN ),
				'button_url'    => $license_page,
				'button_target' => '_self',
			],
		];
	}

	public static function deactivate() {
		WP_Ulike_Pro_API::deactivate_license();

		delete_option( 'wp_ulike_pro_license_key' );
		delete_option( 'wp_ulike_pro_license_checksum' );
		delete_option( 'wp_ulike_pro_license_signature' );
		// License data is stored as options (via set_transient which uses update_option)
		delete_option( 'wp_ulike_pro_license_data' );
		delete_option( 'wp_ulike_pro_license_data_fallback' );
		// Also clear request lock
		WP_Ulike_Pro_API::clear_request_lock( 'get_license_data' );
	}

	public static function get_hidden_license_key() {
		$input_string = self::get_license_key();

		$start = 5;
		$length = mb_strlen( $input_string ) - $start - 5;

		$mask_string = preg_replace( '/\S/', 'X', $input_string );
		$mask_string = mb_substr( $mask_string, $start, $length );
		$input_string = substr_replace( $input_string, $mask_string, $start, $length );

		return $input_string;
	}

	public static function get_license_key() {
		return trim( (string) get_option( 'wp_ulike_pro_license_key', '' ) );
	}

	public static function set_license_key( $license_key ) {
		$result = update_option( 'wp_ulike_pro_license_key', $license_key );

		// Store checksum for integrity validation
		if ( $result && ! empty( $license_key ) ) {
			$checksum = hash( 'sha256', $license_key . wp_ulike_pro_get_audit_token() . home_url() );
			update_option( 'wp_ulike_pro_license_checksum', $checksum );
		}

		return $result;
	}

	/**
	 * Activate license on this site.
	 *
	 * @param string $license_key Raw license key.
	 * @return array|\WP_Error License payload on success.
	 */
	public static function process_activate( $license_key ) {
		$license_key = self::sanitize_license_key_input( $license_key );

		if ( '' === $license_key ) {
			return new WP_Error(
				'empty_key',
				esc_html__( 'Please paste your license key first.', WP_ULIKE_PRO_DOMAIN )
			);
		}

		if ( strlen( $license_key ) < 10 ) {
			return new WP_Error(
				'invalid_format',
				esc_html__( 'That key looks too short. Copy the full key from your WP ULike account.', WP_ULIKE_PRO_DOMAIN )
			);
		}

		$data = WP_Ulike_Pro_API::activate_license( $license_key );

		if ( is_wp_error( $data ) ) {
			return self::wrap_api_wp_error( $data );
		}

		if ( ! isset( $data['license'] ) || WP_Ulike_Pro_API::STATUS_VALID !== $data['license'] ) {
			$error_key = isset( $data['error'] ) ? $data['error'] : 'unknown_error';

			return new WP_Error(
				$error_key,
				wp_kses_post( WP_Ulike_Pro_API::get_error_message( $error_key ) )
			);
		}

		self::set_license_key( $license_key );
		WP_Ulike_Pro_API::set_license_data( $data );

		return $data;
	}

	/**
	 * Deactivate license on this site.
	 *
	 * @return true|\WP_Error
	 */
	public static function process_deactivate() {
		if ( ! self::get_license_key() ) {
			return new WP_Error(
				'no_license',
				esc_html__( 'No license is stored on this site.', WP_ULIKE_PRO_DOMAIN )
			);
		}

		WP_Ulike_Pro_API::deactivate_license();
		self::deactivate();

		return true;
	}

	/**
	 * Render license panel markup for full page or AJAX refresh.
	 *
	 * @param array $args Arguments for get_license_view_data().
	 * @return string
	 */
	public static function get_panel_html( $args = array() ) {
		if ( is_array( $args ) && isset( $args['help_url'] ) ) {
			$data = $args;
		} else {
			$data = self::get_license_view_data( is_array( $args ) ? $args : array() );
		}

		ob_start();
		include WP_ULIKE_PRO_ADMIN_DIR . '/includes/templates/license-panel.php';

		return (string) ob_get_clean();
	}

	/**
	 * Sanitize license key from request input.
	 *
	 * @param string $license_key Raw input.
	 * @return string
	 */
	private static function sanitize_license_key_input( $license_key ) {
		$license_key = sanitize_text_field( trim( (string) $license_key ) );

		if ( strlen( $license_key ) > 128 ) {
			$license_key = substr( $license_key, 0, 128 );
		}

		return $license_key;
	}

	/**
	 * Map API WP_Error to a user-safe AJAX message.
	 *
	 * @param WP_Error $error API error.
	 * @return array{message: string}
	 */
	public static function get_user_error_payload( WP_Error $error ) {
		$raw  = $error->get_error_message();
		$data = $error->get_error_data();

		if ( is_array( $data ) && ! empty( $data['user_message'] ) ) {
			return array( 'message' => (string) $data['user_message'] );
		}

		if ( is_string( $raw ) && false !== strpos( $raw, '<' ) ) {
			return array(
				'message' => esc_html__( 'Could not reach the WP ULike license server. Copy “Details for support” below and contact us if this continues.', WP_ULIKE_PRO_DOMAIN ),
			);
		}

		return array( 'message' => wp_strip_all_tags( $raw ) );
	}

	/**
	 * @param WP_Error $error API error.
	 * @return WP_Error
	 */
	private static function wrap_api_wp_error( WP_Error $error ) {
		$payload = self::get_user_error_payload( $error );

		return new WP_Error(
			$error->get_error_code(),
			$payload['message'],
			array( 'user_message' => $payload['message'] )
		);
	}

	/**
	 * Verify AJAX request.
	 *
	 * @return true|\WP_Error
	 */
	private static function verify_license_ajax_request() {
		if ( ! wp_doing_ajax() ) {
			return new WP_Error(
				'invalid_request',
				esc_html__( 'Invalid request.', WP_ULIKE_PRO_DOMAIN )
			);
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'forbidden',
				esc_html__( 'You do not have permission to manage the license.', WP_ULIKE_PRO_DOMAIN )
			);
		}

		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'wp-ulike-pro-license' ) ) {
			return new WP_Error(
				'invalid_nonce',
				esc_html__( 'Security check failed. Please refresh the page and try again.', WP_ULIKE_PRO_DOMAIN )
			);
		}

		return true;
	}

	/**
	 * @param WP_Error $error Error object.
	 * @return void
	 */
	private static function send_json_error( WP_Error $error ) {
		$payload = self::get_user_error_payload( $error );
		$payload['panel_html'] = self::get_panel_html();

		wp_send_json_error( $payload );
	}

	/**
	 * AJAX: activate license.
	 */
	public function ajax_activate_license() {
		$verified = self::verify_license_ajax_request();
		if ( is_wp_error( $verified ) ) {
			self::send_json_error( $verified );
		}

		if ( ! empty( $_POST['use_stored_key'] ) ) {
			$license_key = self::get_license_key();
		} else {
			$license_key = isset( $_POST['license_key'] )
				? self::sanitize_license_key_input( wp_unslash( $_POST['license_key'] ) )
				: '';
		}

		$result = self::process_activate( $license_key );

		if ( is_wp_error( $result ) ) {
			self::send_json_error( $result );
		}

		wp_send_json_success(
			array(
				'message'    => esc_html__( 'License activated successfully. Updates and Pro features are now enabled on this site.', WP_ULIKE_PRO_DOMAIN ),
				'panel_html' => self::get_panel_html(),
				'lead'       => self::get_lead_text( true ),
			)
		);
	}

	/**
	 * AJAX: deactivate license.
	 */
	public function ajax_deactivate_license() {
		$verified = self::verify_license_ajax_request();
		if ( is_wp_error( $verified ) ) {
			self::send_json_error( $verified );
		}

		$result = self::process_deactivate();

		if ( is_wp_error( $result ) ) {
			self::send_json_error( $result );
		}

		wp_send_json_success(
			array(
				'message'    => esc_html__( 'License removed from this site. You can activate it again here or on another site.', WP_ULIKE_PRO_DOMAIN ),
				'panel_html' => self::get_panel_html(),
				'lead'       => self::get_lead_text( false ),
			)
		);
	}

	/**
	 * AJAX: refresh license status from server.
	 */
	public function ajax_refresh_license() {
		$verified = self::verify_license_ajax_request();
		if ( is_wp_error( $verified ) ) {
			self::send_json_error( $verified );
		}

		if ( ! self::get_license_key() ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Add a license key before refreshing status.', WP_ULIKE_PRO_DOMAIN ),
				)
			);
		}

		delete_option( 'wp_ulike_pro_license_data' );
		WP_Ulike_Pro_API::clear_request_lock( 'get_license_data' );

		$license_data = WP_Ulike_Pro_API::get_license_data( true );
		if ( is_array( $license_data ) && isset( $license_data['license'] ) && WP_Ulike_Pro_API::STATUS_HTTP_ERROR === $license_data['license'] ) {
			wp_send_json_error(
				array(
					'message'     => esc_html__( 'Could not refresh license status from the server. Your previous cached status may still be shown.', WP_ULIKE_PRO_DOMAIN ),
					'panel_html'  => self::get_panel_html( array( 'force_refresh' => true ) ),
				)
			);
		}

		wp_send_json_success(
			array(
				'message'    => esc_html__( 'License status updated from WP ULike.', WP_ULIKE_PRO_DOMAIN ),
				'panel_html' => self::get_panel_html( array( 'force_refresh' => true ) ),
				'lead'       => self::get_lead_text( true ),
			)
		);
	}

	public function action_activate_license() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', WP_ULIKE_PRO_DOMAIN ), esc_html__( 'WP ULike Pro', WP_ULIKE_PRO_DOMAIN ), [
				'back_link' => true,
				'response'  => 403,
			] );
		}

		check_admin_referer( 'wp-ulike-pro-license' );

		$license_key = wp_ulike_pro_unstable_get_super_global_value( $_POST, 'wp_ulike_pro_license_key' );
		$result      = self::process_activate( $license_key );

		if ( is_wp_error( $result ) ) {
			wp_die( wp_kses_post( $result->get_error_message() ), esc_html__( 'WP ULike Pro', WP_ULIKE_PRO_DOMAIN ), [
				'back_link' => true,
			] );
		}

		$redirect_url = add_query_arg( 'activated', '1', self::get_url() );
		$this->safe_redirect( $redirect_url );
		die;
	}

	protected function safe_redirect( $url ) {
		// Ensure URL is safe and within admin area
		if ( ! $url || ! wp_http_validate_url( $url ) ) {
			$url = self::get_url();
		}

		// Only allow redirects within admin area
		if ( strpos( $url, admin_url() ) !== 0 ) {
			$url = self::get_url();
		}

		wp_safe_redirect( esc_url_raw( $url ) );
		die;
	}

	public function action_deactivate_license() {
		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', WP_ULIKE_PRO_DOMAIN ), esc_html__( 'WP ULike Pro', WP_ULIKE_PRO_DOMAIN ), [
				'back_link' => true,
				'response'  => 403,
			] );
		}

		check_admin_referer( 'wp-ulike-pro-license' );

		$this->deactivate();

		// Redirect with deactivated message
		$redirect_url = add_query_arg( 'deactivated', '1', self::get_url() );
		$this->safe_redirect( $redirect_url );
		die;
	}

	public static function get_url() {
		return admin_url( 'admin.php?page=' . self::PAGE_ID );
	}

	/**
	 * Intro line under the page title.
	 *
	 * @param bool $has_license Whether a key is stored.
	 * @return string
	 */
	public static function get_lead_text( $has_license ) {
		if ( $has_license ) {
			return esc_html__( 'Status and actions for this website only. Your license key is not removed unless you expand “Moving to another domain?”.', WP_ULIKE_PRO_DOMAIN );
		}

		return esc_html__( 'Paste your license key once to unlock updates, support, and all Pro features. Takes less than a minute.', WP_ULIKE_PRO_DOMAIN );
	}

	/**
	 * Whether the license is tied to an active subscription / auto-renewal at wpulike.com.
	 *
	 * Set `subscription_active` (or similar) in the license API response, or use the filter.
	 *
	 * @param array $license_data Cached license payload.
	 * @return bool
	 */
	public static function license_may_auto_renew( $license_data ) {
		if ( ! is_array( $license_data ) ) {
			return false;
		}

		if ( ! empty( $license_data['subscription_active'] ) ) {
			return true;
		}

		foreach ( array( 'auto_renew', 'has_active_subscription' ) as $key ) {
			if ( ! empty( $license_data[ $key ] ) ) {
				return true;
			}
		}

		return (bool) apply_filters( 'wp_ulike_pro_license_has_auto_renewal', false, $license_data );
	}

	/**
	 * Plain-language guidance for the current license state.
	 *
	 * @param array $data View data.
	 * @return array{title: string, message: string, type: string}
	 */
	public static function get_next_step( $data ) {
		if ( empty( $data['has_license'] ) ) {
			return array(
				'type'    => 'info',
				'title'   => esc_html__( 'What to do now', WP_ULIKE_PRO_DOMAIN ),
				'message' => esc_html__( 'Paste your license key in the form below, then click Activate license.', WP_ULIKE_PRO_DOMAIN ),
			);
		}

		if ( ! empty( $data['is_expired'] ) ) {
			return array(
				'type'    => 'warn',
				'title'   => esc_html__( 'What to do now', WP_ULIKE_PRO_DOMAIN ),
				'message' => esc_html__( 'Your license has expired. Click Renew license to restore updates and support.', WP_ULIKE_PRO_DOMAIN ),
			);
		}

		if ( ! empty( $data['needs_reactivate'] ) ) {
			return array(
				'type'    => 'warn',
				'title'   => esc_html__( 'What to do now', WP_ULIKE_PRO_DOMAIN ),
				'message' => esc_html__( 'This site’s address changed. Click Reactivate for this site — you do not need a new key.', WP_ULIKE_PRO_DOMAIN ),
			);
		}

		if ( WP_Ulike_Pro_API::STATUS_DISABLED === ( $data['status'] ?? '' ) ) {
			return array(
				'type'    => 'warn',
				'title'   => esc_html__( 'What to do now', WP_ULIKE_PRO_DOMAIN ),
				'message' => esc_html__( 'Purchase a new license, then enter your new key below. You can remove the cancelled key first if you prefer.', WP_ULIKE_PRO_DOMAIN ),
			);
		}

		if ( ! empty( $data['is_expiring_soon'] ) ) {
			if ( ! empty( $data['license_has_auto_renewal'] ) ) {
				return array(
					'type'    => 'good',
					'title'   => esc_html__( 'What to do now', WP_ULIKE_PRO_DOMAIN ),
					'message' => esc_html__( 'Your license is still active. With an active subscription at wpulike.com, renewal is usually automatic—check the expiry notice below only if you cancelled billing.', WP_ULIKE_PRO_DOMAIN ),
				);
			}

			return array(
				'type'    => 'warn',
				'title'   => esc_html__( 'What to do now', WP_ULIKE_PRO_DOMAIN ),
				'message' => esc_html__( 'Your license expires soon. Renew before the date below to keep updates and support.', WP_ULIKE_PRO_DOMAIN ),
			);
		}

		if ( ! empty( $data['is_valid'] ) ) {
			return array(
				'type'    => 'good',
				'title'   => esc_html__( 'You\'re all set', WP_ULIKE_PRO_DOMAIN ),
				'message' => esc_html__( 'No action needed. To use this key on another website, open “Moving to another domain?” on this page.', WP_ULIKE_PRO_DOMAIN ),
			);
		}

		if ( ! empty( $data['error_detail']['description'] ) ) {
			return array(
				'type'    => 'warn',
				'title'   => esc_html__( 'What to do now', WP_ULIKE_PRO_DOMAIN ),
				'message' => wp_strip_all_tags( $data['error_detail']['description'] ),
			);
		}

		return array(
			'type'    => 'neutral',
			'title'   => esc_html__( 'What to do now', WP_ULIKE_PRO_DOMAIN ),
			'message' => esc_html__( 'Click Refresh status. If the problem continues, copy the support details below and contact us.', WP_ULIKE_PRO_DOMAIN ),
		);
	}

	/**
	 * Rows for the support / technical details table.
	 *
	 * @param array $data View data.
	 * @return array<int, array{label: string, value: string, mono?: bool}>
	 */
	/**
	 * Known security / firewall plugin slugs => display name.
	 *
	 * @return array<string, string>
	 */
	private static function get_security_plugin_map() {
		return array(
			'wordfence/wordfence.php'                               => 'Wordfence',
			'sucuri-scanner/sucuri.php'                             => 'Sucuri Scanner',
			'better-wp-security/better-wp-security.php'             => 'Solid Security',
			'ithemes-security-pro/ithemes-security-pro.php'         => 'Solid Security Pro',
			'all-in-one-wp-security-and-firewall/wp-security.php'   => 'All-In-One Security',
			'wp-security-audit-log/wp-security-audit-log.php'         => 'WP Activity Log',
			'shield-security/shield-security.php'                   => 'Shield Security',
			'jetpack/jetpack.php'                                   => 'Jetpack',
			'ninjafirewall/ninjafirewall.php'                       => 'NinjaFirewall',
			'bulletproof-security/bulletproof-security.php'         => 'BulletProof Security',
			'security-ninja/security-ninja.php'                     => 'Security Ninja',
			'wp-cerber/wp-cerber.php'                               => 'WP Cerber',
			'malcare-security/malcare.php'                          => 'MalCare',
			'sg-security/sg-security.php'                           => 'SiteGround Security',
			'cloudflare/cloudflare.php'                             => 'Cloudflare Plugin',
			'wp-defender/wp-defender.php'                           => 'Defender',
			'limit-login-attempts-reloaded/limit-login-attempts-reloaded.php' => 'Limit Login Attempts',
			'loginizer/loginizer.php'                               => 'Loginizer',
			'wps-hide-login/wps-hide-login.php'                     => 'WPS Hide Login',
		);
	}

	/**
	 * Active security-related plugins (name + version).
	 *
	 * @return array<int, string>
	 */
	private static function detect_active_security_plugins() {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$found = array();

		foreach ( self::get_security_plugin_map() as $slug => $label ) {
			if ( ! is_plugin_active( $slug ) ) {
				continue;
			}

			$plugin_file = WP_PLUGIN_DIR . '/' . $slug;
			$version     = '';

			if ( file_exists( $plugin_file ) ) {
				$plugin_data = get_plugin_data( $plugin_file, false, false );
				if ( ! empty( $plugin_data['Version'] ) ) {
					$version = ' ' . $plugin_data['Version'];
				}
			}

			$found[] = $label . $version;
		}

		return $found;
	}

	/**
	 * WAF, CDN, and reverse-proxy hints from constants and request headers.
	 *
	 * @return array<int, string>
	 */
	private static function detect_waf_and_proxy_signals() {
		$signals = array();

		if ( defined( 'WORDFENCE_VERSION' ) ) {
			$signals[] = 'Wordfence (plugin/WAF)';
		}

		if ( defined( 'JETPACK__VERSION' ) && file_exists( WP_CONTENT_DIR . '/jetpack-waf/bootstrap.php' ) ) {
			$signals[] = 'Jetpack WAF';
		}

		if ( defined( 'NINJAFIREWALL_VERSION' ) ) {
			$signals[] = 'NinjaFirewall WAF';
		}

		$header_map = array(
			'HTTP_CF_RAY'                 => 'Cloudflare',
			'HTTP_CF_CONNECTING_IP'       => 'Cloudflare',
			'HTTP_CF_VISITOR'               => 'Cloudflare',
			'HTTP_X_SUCURI_CLIENTIP'      => 'Sucuri WAF',
			'HTTP_X_SUCURI'                 => 'Sucuri',
			'HTTP_X_SUCURI_COUNTRY'         => 'Sucuri',
			'HTTP_INCAP_CLIENT_IP'          => 'Imperva / Incapsula',
			'HTTP_X_IINFO'                  => 'Imperva / Incapsula',
			'HTTP_X_AKAMAI_TRANSFORMED'     => 'Akamai',
			'HTTP_TRUE_CLIENT_IP'           => 'CDN / proxy (True-Client-IP)',
			'HTTP_X_FORWARDED_FOR'          => 'Reverse proxy',
			'HTTP_X_REAL_IP'                => 'Reverse proxy (X-Real-IP)',
			'HTTP_X_FORWARDED_PROTO'        => 'Reverse proxy',
			'HTTP_X_CLUSTER_CLIENT_IP'      => 'Load balancer',
		);

		foreach ( $header_map as $server_key => $label ) {
			if ( ! empty( $_SERVER[ $server_key ] ) ) {
				$signals[] = $label;
			}
		}

		if ( defined( 'CLOUDFLARE_PLUGIN_DIR' ) || defined( 'CLOUDFLARE_VERSION' ) ) {
			$signals[] = 'Cloudflare plugin';
		}

		return array_values( array_unique( $signals ) );
	}

	/**
	 * Managed host and outbound HTTP hints (license API connectivity).
	 *
	 * @return array<int, string>
	 */
	private static function detect_hosting_and_http_hints() {
		$hints = array();

		$host_map = array(
			'WPE_PLUGIN_VERSION'   => 'WP Engine',
			'KINSTAMU_VERSION'     => 'Kinsta',
			'FLYWHEEL_CONFIG_DIR'  => 'Flywheel',
			'PANTHEON_ENVIRONMENT' => 'Pantheon',
			'PRESSABLE'            => 'Pressable',
			'GRIDPANE'             => 'GridPane',
			'SPINUPWP_CACHE_PATH'  => 'SpinupWP',
		);

		foreach ( $host_map as $constant => $label ) {
			if ( defined( $constant ) ) {
				$hints[] = $label;
			}
		}

		if ( defined( 'WP_HTTP_BLOCK_EXTERNAL' ) && WP_HTTP_BLOCK_EXTERNAL ) {
			$hints[] = esc_html__( 'Outbound HTTP blocked (WP_HTTP_BLOCK_EXTERNAL)', WP_ULIKE_PRO_DOMAIN );
		}

		if ( ! ini_get( 'allow_url_fopen' ) && ! function_exists( 'curl_init' ) ) {
			$hints[] = esc_html__( 'No allow_url_fopen or cURL (license API may fail)', WP_ULIKE_PRO_DOMAIN );
		}

		return $hints;
	}

	/**
	 * Security, WAF, and connectivity rows for support export.
	 *
	 * @return array<int, array{label: string, value: string, mono?: bool}>
	 */
	private static function get_security_support_rows() {
		$rows = array();

		$security_plugins = self::detect_active_security_plugins();
		$rows[]           = array(
			'label' => esc_html__( 'Security plugins', WP_ULIKE_PRO_DOMAIN ),
			'value' => ! empty( $security_plugins )
				? implode( ', ', $security_plugins )
				: esc_html__( 'None detected', WP_ULIKE_PRO_DOMAIN ),
		);

		$waf_signals = self::detect_waf_and_proxy_signals();
		$rows[]      = array(
			'label' => esc_html__( 'WAF / CDN / proxy', WP_ULIKE_PRO_DOMAIN ),
			'value' => ! empty( $waf_signals )
				? implode( ', ', $waf_signals )
				: esc_html__( 'None detected from headers', WP_ULIKE_PRO_DOMAIN ),
		);

		$host_hints = self::detect_hosting_and_http_hints();
		if ( ! empty( $host_hints ) ) {
			$rows[] = array(
				'label' => esc_html__( 'Hosting / HTTP notes', WP_ULIKE_PRO_DOMAIN ),
				'value' => implode( '; ', $host_hints ),
			);
		}

		$server_software = isset( $_SERVER['SERVER_SOFTWARE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) : '';
		if ( $server_software && preg_match( '/apache|nginx|litespeed|iis/i', $server_software ) ) {
			$rows[] = array(
				'label' => esc_html__( 'Web server', WP_ULIKE_PRO_DOMAIN ),
				'value' => $server_software,
			);
		}

		return apply_filters( 'wp_ulike_pro_license_security_support_rows', $rows );
	}

	/**
	 * PHP / SSL connectivity rows (license API).
	 *
	 * @return array<int, array{label: string, value: string, mono?: bool}>
	 */
	private static function get_connectivity_support_rows() {
		$rows = array();

		$curl_version = function_exists( 'curl_version' ) ? curl_version() : false;
		if ( $curl_version ) {
			$curl_label = $curl_version['version'];
			if ( ! empty( $curl_version['ssl_version'] ) ) {
				$curl_label .= ' (SSL: ' . $curl_version['ssl_version'] . ')';
			}
			$rows[] = array(
				'label' => esc_html__( 'cURL', WP_ULIKE_PRO_DOMAIN ),
				'value' => $curl_label,
			);
		}

		if ( defined( 'OPENSSL_VERSION_TEXT' ) ) {
			$rows[] = array(
				'label' => esc_html__( 'OpenSSL', WP_ULIKE_PRO_DOMAIN ),
				'value' => OPENSSL_VERSION_TEXT,
			);
		}

		$rows[] = array(
			'label' => esc_html__( 'allow_url_fopen', WP_ULIKE_PRO_DOMAIN ),
			'value' => ini_get( 'allow_url_fopen' ) ? esc_html__( 'Enabled', WP_ULIKE_PRO_DOMAIN ) : esc_html__( 'Disabled', WP_ULIKE_PRO_DOMAIN ),
		);

		$theme = wp_get_theme();
		$rows[] = array(
			'label' => esc_html__( 'Theme', WP_ULIKE_PRO_DOMAIN ),
			'value' => trim( $theme->get( 'Name' ) . ( $theme->get( 'Version' ) ? ' ' . $theme->get( 'Version' ) : '' ) ),
		);

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$debug_flags = array( 'WP_DEBUG' );
			if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
				$debug_flags[] = 'WP_DEBUG_LOG';
			}
			if ( defined( 'WP_DEBUG_DISPLAY' ) && WP_DEBUG_DISPLAY ) {
				$debug_flags[] = 'WP_DEBUG_DISPLAY';
			}
			$rows[] = array(
				'label' => esc_html__( 'WordPress debug', WP_ULIKE_PRO_DOMAIN ),
				'value' => implode( ', ', $debug_flags ),
			);
		}

		return $rows;
	}

	/**
	 * Last license API failure (if recent).
	 *
	 * @return array<int, array{label: string, value: string, mono?: bool}>
	 */
	private static function get_last_api_error_support_rows() {
		if ( ! class_exists( 'WP_Ulike_Pro_API' ) || ! method_exists( 'WP_Ulike_Pro_API', 'get_last_license_api_error' ) ) {
			return array();
		}

		$last = WP_Ulike_Pro_API::get_last_license_api_error();
		if ( ! $last ) {
			return array();
		}

		$rows = array();

		if ( ! empty( $last['http_code'] ) ) {
			$rows[] = array(
				'label' => esc_html__( 'License API HTTP status', WP_ULIKE_PRO_DOMAIN ),
				'value' => (string) (int) $last['http_code'],
				'mono'  => true,
			);
		} elseif ( ! empty( $last['connection'] ) ) {
			$rows[] = array(
				'label' => esc_html__( 'License API connection', WP_ULIKE_PRO_DOMAIN ),
				'value' => (string) $last['connection'],
				'mono'  => true,
			);
		}

		if ( ! empty( $last['http_message'] ) ) {
			$rows[] = array(
				'label' => esc_html__( 'License API message', WP_ULIKE_PRO_DOMAIN ),
				'value' => (string) $last['http_message'],
			);
		}

		if ( ! empty( $last['body_excerpt'] ) ) {
			$rows[] = array(
				'label' => esc_html__( 'License API response excerpt', WP_ULIKE_PRO_DOMAIN ),
				'value' => (string) $last['body_excerpt'],
				'mono'  => true,
			);
		}

		if ( ! empty( $last['time'] ) ) {
			$rows[] = array(
				'label' => esc_html__( 'Last license API error at', WP_ULIKE_PRO_DOMAIN ),
				'value' => date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), (int) $last['time'] ),
			);
		}

		return $rows;
	}

	/**
	 * Environment rows useful for hosting / license support.
	 *
	 * @return array<int, array{label: string, value: string, mono?: bool}>
	 */
	private static function get_environment_url_rows() {
		$rows     = array();
		$home_url = home_url();
		$site_url = site_url();

		$rows[] = array(
			'label' => esc_html__( 'License site URL (home)', WP_ULIKE_PRO_DOMAIN ),
			'value' => $home_url,
		);

		if ( untrailingslashit( $home_url ) !== untrailingslashit( $site_url ) ) {
			$rows[] = array(
				'label' => esc_html__( 'WordPress URL (site)', WP_ULIKE_PRO_DOMAIN ),
				'value' => $site_url,
			);
		}

		return $rows;
	}

	/**
	 * Server / hosting rows (shown in expandable technical section).
	 *
	 * @return array<int, array{label: string, value: string, mono?: bool}>
	 */
	private static function get_environment_hosting_rows() {
		$public_ip = esc_html__( 'Unavailable', WP_ULIKE_PRO_DOMAIN );
		if ( class_exists( 'WP_Ulike_Pro_API' ) && method_exists( 'WP_Ulike_Pro_API', 'get_public_server_ip' ) ) {
			$public_ip = WP_Ulike_Pro_API::get_public_server_ip();
		}

		return array(
			array(
				'label' => esc_html__( 'Server public IP', WP_ULIKE_PRO_DOMAIN ),
				'value' => $public_ip,
				'mono'  => true,
			),
			array(
				'label' => esc_html__( 'HTTPS', WP_ULIKE_PRO_DOMAIN ),
				'value' => is_ssl() ? esc_html__( 'Yes', WP_ULIKE_PRO_DOMAIN ) : esc_html__( 'No', WP_ULIKE_PRO_DOMAIN ),
			),
			array(
				'label' => esc_html__( 'Multisite', WP_ULIKE_PRO_DOMAIN ),
				'value' => is_multisite() ? esc_html__( 'Yes', WP_ULIKE_PRO_DOMAIN ) : esc_html__( 'No', WP_ULIKE_PRO_DOMAIN ),
			),
			array(
				'label' => esc_html__( 'Timezone', WP_ULIKE_PRO_DOMAIN ),
				'value' => wp_timezone_string(),
			),
		);
	}

	private static function get_environment_support_rows() {
		return array_merge(
			self::get_environment_url_rows(),
			self::get_environment_hosting_rows(),
			self::get_connectivity_support_rows(),
			self::get_security_support_rows(),
			self::get_last_api_error_support_rows()
		);
	}

	/**
	 * Version + license summary rows (always visible on License page).
	 *
	 * @param array $data View data.
	 * @return array<int, array{label: string, value: string, mono?: bool}>
	 */
	private static function get_support_summary_rows( $data ) {
		$rows = array(
			array(
				'label' => esc_html__( 'WP ULike Pro', WP_ULIKE_PRO_DOMAIN ),
				'value' => $data['pro_version'] ?? '',
			),
		);

		if ( defined( 'WP_ULIKE_VERSION' ) ) {
			$rows[] = array(
				'label' => esc_html__( 'WP ULike (free)', WP_ULIKE_PRO_DOMAIN ),
				'value' => WP_ULIKE_VERSION,
			);
		}

		$rows[] = array(
			'label' => esc_html__( 'WordPress', WP_ULIKE_PRO_DOMAIN ),
			'value' => get_bloginfo( 'version' ),
		);
		$rows[] = array(
			'label' => esc_html__( 'PHP', WP_ULIKE_PRO_DOMAIN ),
			'value' => PHP_VERSION,
		);

		if ( empty( $data['has_license'] ) ) {
			$rows[] = array(
				'label' => esc_html__( 'License on this site', WP_ULIKE_PRO_DOMAIN ),
				'value' => esc_html__( 'Not activated', WP_ULIKE_PRO_DOMAIN ),
			);

			return array_merge( $rows, self::get_environment_url_rows() );
		}

		$rows[] = array(
			'label' => esc_html__( 'License key (masked)', WP_ULIKE_PRO_DOMAIN ),
			'value' => $data['hidden_key'] ?? '',
			'mono'  => true,
		);
		$rows[] = array(
			'label' => esc_html__( 'Status', WP_ULIKE_PRO_DOMAIN ),
			'value' => $data['status_label'] ?? '',
		);

		if ( ! empty( $data['expires_label'] ) ) {
			$rows[] = array(
				'label' => esc_html__( 'Expires', WP_ULIKE_PRO_DOMAIN ),
				'value' => $data['expires_label'] . ( ! empty( $data['expires_hint'] ) ? ' — ' . $data['expires_hint'] : '' ),
			);
		}

		if ( ! empty( $data['sites_label'] ) ) {
			$value = $data['sites_label'];
			if ( isset( $data['activations_left'] ) && is_int( $data['activations_left'] ) ) {
				$value .= ' (' . sprintf(
					/* translators: %d: activations remaining */
					esc_html__( '%d activations left', WP_ULIKE_PRO_DOMAIN ),
					$data['activations_left']
				) . ')';
			} elseif ( isset( $data['activations_left'] ) && 'unlimited' === $data['activations_left'] ) {
				$value .= ' (' . esc_html__( 'unlimited activations', WP_ULIKE_PRO_DOMAIN ) . ')';
			}
			$rows[] = array(
				'label' => esc_html__( 'Sites on license', WP_ULIKE_PRO_DOMAIN ),
				'value' => $value,
			);
		}

		$license_data = $data['license_data'] ?? array();
		if ( ! empty( $license_data['payment_id'] ) && '0' !== (string) $license_data['payment_id'] ) {
			$rows[] = array(
				'label' => esc_html__( 'Order number', WP_ULIKE_PRO_DOMAIN ),
				'value' => '#' . $license_data['payment_id'],
			);
		}

		if ( ! empty( $license_data['renewal_discount'] ) ) {
			$rows[] = array(
				'label' => esc_html__( 'Renewal discount', WP_ULIKE_PRO_DOMAIN ),
				'value' => $license_data['renewal_discount'] . '%',
			);
		}

		return array_merge( $rows, self::get_environment_url_rows() );
	}

	/**
	 * Diagnostics rows (collapsed on License page; included in Copy for support).
	 *
	 * @param array $data View data.
	 * @return array<int, array{label: string, value: string, mono?: bool}>
	 */
	private static function get_support_technical_rows( $data ) {
		$rows = array_merge(
			self::get_environment_hosting_rows(),
			self::get_connectivity_support_rows(),
			self::get_security_support_rows(),
			self::get_last_api_error_support_rows()
		);

		if ( ! empty( $data['status'] ) ) {
			$rows[] = array(
				'label' => esc_html__( 'Server status code', WP_ULIKE_PRO_DOMAIN ),
				'value' => (string) $data['status'],
				'mono'  => true,
			);
		}

		$cache = get_option( 'wp_ulike_pro_license_data' );
		if ( is_array( $cache ) && ! empty( $cache['timeout'] ) ) {
			$rows[] = array(
				'label' => esc_html__( 'Cached status valid until', WP_ULIKE_PRO_DOMAIN ),
				'value' => date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), (int) $cache['timeout'] ),
			);
		}

		return $rows;
	}

	/**
	 * Support rows split for display (summary vs expandable technical block).
	 *
	 * @param array $data View data.
	 * @return array{summary: array, technical: array, all: array}
	 */
	public static function get_support_display_groups( $data ) {
		$summary   = self::get_support_summary_rows( $data );
		$technical = self::get_support_technical_rows( $data );
		$all       = array_merge( $summary, $technical );

		$all = apply_filters( 'wp_ulike_pro_license_support_detail_rows', $all, $data );

		return array(
			'summary'   => apply_filters( 'wp_ulike_pro_license_support_summary_rows', $summary, $data ),
			'technical' => apply_filters( 'wp_ulike_pro_license_support_technical_rows', $technical, $data ),
			'all'       => $all,
		);
	}

	/**
	 * Drop rows with empty values.
	 *
	 * @param array<int, array{label: string, value: string, mono?: bool}> $rows Rows.
	 * @return array<int, array{label: string, value: string, mono?: bool}>
	 */
	public static function filter_nonempty_support_rows( $rows ) {
		return array_values(
			array_filter(
				$rows,
				static function ( $row ) {
					return ! empty( $row['value'] );
				}
			)
		);
	}

	public static function get_support_detail_rows( $data ) {
		return self::get_support_display_groups( $data )['all'];
	}

	/**
	 * Attach support row groups and clipboard export to license view data.
	 *
	 * @param array $data View data.
	 * @return array
	 */
	private static function attach_support_view_data( $data ) {
		$support_groups = self::get_support_display_groups( $data );

		$data['support_rows']           = $support_groups['all'];
		$data['support_rows_summary']   = self::filter_nonempty_support_rows( $support_groups['summary'] );
		$data['support_rows_technical'] = self::filter_nonempty_support_rows( $support_groups['technical'] );
		$data['support_export']         = self::get_support_export_text( $data );

		return $data;
	}

	/**
	 * Plain-text block for support tickets (clipboard).
	 *
	 * @param array $data View data.
	 * @return string
	 */
	public static function get_support_export_text( $data ) {
		$lines = array( 'WP ULike Pro — License details', str_repeat( '-', 40 ) );

		foreach ( self::get_support_detail_rows( $data ) as $row ) {
			if ( empty( $row['value'] ) ) {
				continue;
			}
			$lines[] = wp_strip_all_tags( $row['label'] ) . ': ' . wp_strip_all_tags( $row['value'] );
		}

		return apply_filters( 'wp_ulike_pro_license_support_export_text', implode( "\n", $lines ), $data );
	}

	public static function get_license_view_data( $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'force_refresh'       => false,
				'include_url_notices' => true,
			)
		);

		$license_key   = self::get_license_key();
		$force_refresh = ! empty( $args['force_refresh'] );

		if ( ! $force_refresh && isset( $_GET['refresh'] ) && '1' === sanitize_key( wp_unslash( $_GET['refresh'] ) ) ) {
			$force_refresh = true;
		}

		$data = array(
			'license_key'   => $license_key,
			'has_license'   => ! empty( $license_key ),
			'force_refresh' => $force_refresh,
			'notice'        => '',
			'notice_type'   => '',
			'help_url'      => admin_url( 'admin.php?page=wp-ulike-about' ),
			'account_url'   => WP_Ulike_Pro_API::get_account_url( 'get-license', 'license-page' ),
			'pricing_url'   => WP_Ulike_Pro_API::get_pricing_url( 'pricing', 'license-page' ),
			'pro_version'   => defined( 'WP_ULIKE_PRO_VERSION' ) ? WP_ULIKE_PRO_VERSION : '',
			'site_url'      => home_url(),
			'ajax_nonce'    => wp_create_nonce( 'wp-ulike-pro-license' ),
			'lead_text'     => self::get_lead_text( ! empty( $license_key ) ),
		);

		if ( $args['include_url_notices'] ) {
			if ( isset( $_GET['activated'] ) && '1' === sanitize_key( wp_unslash( $_GET['activated'] ) ) ) {
				$data['notice']      = esc_html__( 'Your license has been activated successfully.', WP_ULIKE_PRO_DOMAIN );
				$data['notice_type'] = 'success';
			} elseif ( isset( $_GET['deactivated'] ) && '1' === sanitize_key( wp_unslash( $_GET['deactivated'] ) ) ) {
				$data['notice']      = esc_html__( 'Your license has been deactivated on this site.', WP_ULIKE_PRO_DOMAIN );
				$data['notice_type'] = 'info';
			}
		}

		if ( empty( $license_key ) ) {
			$data['next_step']      = self::get_next_step( $data );
			return self::attach_support_view_data( $data );
		}

		if ( $force_refresh ) {
			delete_option( 'wp_ulike_pro_license_data' );
			WP_Ulike_Pro_API::clear_request_lock( 'get_license_data' );
		}

		$license_data = WP_Ulike_Pro_API::get_license_data( $force_refresh );

		if ( ! is_array( $license_data ) ) {
			$license_data = array(
				'license' => WP_Ulike_Pro_API::STATUS_HTTP_ERROR,
				'success' => false,
			);
		}

		$data['license_data'] = $license_data;
		$data['status']       = $license_data['license'] ?? '';
		$data['is_valid']     = WP_Ulike_Pro_API::STATUS_VALID === $data['status'];
		$data['is_expired']   = WP_Ulike_Pro_API::STATUS_EXPIRED === $data['status'];
		$data['status_label'] = self::get_status_label( $license_data );
		$data['status_state'] = self::get_status_state( $license_data );
		$data['hidden_key']   = self::get_hidden_license_key();
		$data['renew_url']    = WP_Ulike_Pro_API::get_renew_url(
			$license_key,
			'renew-license',
			'license-page',
			array( 'edd_action' => 'renew' )
		);
		$data['needs_reactivate'] = in_array(
			$data['status'],
			array( WP_Ulike_Pro_API::STATUS_SITE_INACTIVE, WP_Ulike_Pro_API::STATUS_INVALID ),
			true
		);
		$error_details        = self::get_errors_details(
			array(
				'renew_url'        => $data['renew_url'],
				'license_page_url' => self::get_url(),
				'pricing_url'      => $data['pricing_url'],
			)
		);
		$data['is_cancelled'] = WP_Ulike_Pro_API::STATUS_DISABLED === $data['status'];
		$data['error_detail'] = isset( $data['status'], $error_details[ $data['status'] ] )
			? $error_details[ $data['status'] ]
			: null;

		if ( $data['is_valid'] && ! empty( $license_data['expires'] ) ) {
			if ( 'lifetime' === $license_data['expires'] ) {
				$data['expires_label'] = esc_html__( 'Lifetime', WP_ULIKE_PRO_DOMAIN );
				$data['expires_hint']  = '';
			} else {
				$expires_ts = strtotime( $license_data['expires'] );
				if ( $expires_ts ) {
					$data['expires_label'] = date_i18n( get_option( 'date_format' ), $expires_ts );
					if ( $expires_ts > current_time( 'timestamp' ) ) {
						$data['expires_hint'] = sprintf(
							/* translators: %s: human-readable time until expiry */
							esc_html__( 'Renews in %s', WP_ULIKE_PRO_DOMAIN ),
							human_time_diff( current_time( 'timestamp' ), $expires_ts )
						);
					} else {
						$data['expires_hint'] = esc_html__( 'Past expiry date', WP_ULIKE_PRO_DOMAIN );
					}
				}
			}
		}

		if ( isset( $license_data['license_limit'] ) && '0' !== (string) $license_data['license_limit'] ) {
			$site_count       = isset( $license_data['site_count'] ) ? (int) $license_data['site_count'] : 0;
			$license_limit    = (int) $license_data['license_limit'];
			$activations_left = isset( $license_data['activations_left'] )
				? $license_data['activations_left']
				: max( 0, $license_limit - $site_count );

			$data['sites_label'] = sprintf(
				/* translators: 1: sites in use, 2: license limit */
				esc_html__( '%1$s of %2$s', WP_ULIKE_PRO_DOMAIN ),
				number_format_i18n( $site_count ),
				number_format_i18n( $license_limit )
			);

			if ( is_numeric( $activations_left ) ) {
				$data['activations_left'] = (int) $activations_left;
			} elseif ( 'unlimited' === $activations_left ) {
				$data['activations_left'] = 'unlimited';
			}
		}

		$data['is_expiring_soon']          = $data['is_valid']
			&& ! empty( $license_data['expires'] )
			&& 'lifetime' !== $license_data['expires']
			&& WP_Ulike_Pro_API::is_license_about_to_expire();
		$data['license_has_auto_renewal'] = self::license_may_auto_renew( $license_data );
		$data['renewal_discount']         = ( ! empty( $license_data['renewal_discount'] ) && (int) $license_data['renewal_discount'] > 0 )
			? (int) $license_data['renewal_discount']
			: 0;

		$data['next_step']      = self::get_next_step( $data );
		$data = self::attach_support_view_data( $data );

		return $data;
	}

	/**
	 * Human-readable license status for status cards.
	 *
	 * @param array $license_data License payload.
	 * @return string
	 */
	public static function get_status_label( $license_data ) {
		$license_errors = array(
			WP_Ulike_Pro_API::STATUS_EXPIRED        => esc_html__( 'Expired', WP_ULIKE_PRO_DOMAIN ),
			WP_Ulike_Pro_API::STATUS_SITE_INACTIVE  => esc_html__( 'Mismatch', WP_ULIKE_PRO_DOMAIN ),
			WP_Ulike_Pro_API::STATUS_INVALID        => esc_html__( 'Invalid', WP_ULIKE_PRO_DOMAIN ),
			WP_Ulike_Pro_API::STATUS_DISABLED       => esc_html__( 'Cancelled', WP_ULIKE_PRO_DOMAIN ),
			WP_Ulike_Pro_API::STATUS_HTTP_ERROR     => esc_html__( 'Connection error', WP_ULIKE_PRO_DOMAIN ),
			WP_Ulike_Pro_API::STATUS_MISSING        => esc_html__( 'Missing', WP_ULIKE_PRO_DOMAIN ),
			WP_Ulike_Pro_API::STATUS_REQUEST_LOCKED => esc_html__( 'Checking…', WP_ULIKE_PRO_DOMAIN ),
		);

		$status = $license_data['license'] ?? '';

		if ( WP_Ulike_Pro_API::STATUS_VALID === $status ) {
			return esc_html__( 'Active', WP_ULIKE_PRO_DOMAIN );
		}

		if ( isset( $license_errors[ $status ] ) ) {
			return $license_errors[ $status ];
		}

		return esc_html__( 'Unknown', WP_ULIKE_PRO_DOMAIN );
	}

	/**
	 * Status card state: good, bad, or neutral.
	 *
	 * @param array $license_data License payload.
	 * @return string
	 */
	public static function get_status_state( $license_data ) {
		$status = $license_data['license'] ?? '';

		if ( WP_Ulike_Pro_API::STATUS_VALID === $status ) {
			if ( WP_Ulike_Pro_API::is_license_about_to_expire() ) {
				return 'neutral';
			}
			return 'good';
		}

		if ( in_array( $status, array( WP_Ulike_Pro_API::STATUS_EXPIRED, WP_Ulike_Pro_API::STATUS_INVALID, WP_Ulike_Pro_API::STATUS_SITE_INACTIVE, WP_Ulike_Pro_API::STATUS_DISABLED ), true ) ) {
			return 'bad';
		}

		return 'neutral';
	}

	public static function render_part_license_status_header( $license_data ) {
		$license_errors = [
			WP_Ulike_Pro_API::STATUS_EXPIRED        => esc_html__( 'Expired',WP_ULIKE_PRO_DOMAIN ),
			WP_Ulike_Pro_API::STATUS_SITE_INACTIVE  => esc_html__( 'Mismatch',WP_ULIKE_PRO_DOMAIN ),
			WP_Ulike_Pro_API::STATUS_INVALID        => esc_html__( 'Invalid',WP_ULIKE_PRO_DOMAIN ),
			WP_Ulike_Pro_API::STATUS_DISABLED       => esc_html__( 'Cancelled',WP_ULIKE_PRO_DOMAIN ),
			WP_Ulike_Pro_API::STATUS_HTTP_ERROR     => esc_html__( 'HTTP Error',WP_ULIKE_PRO_DOMAIN ),
			WP_Ulike_Pro_API::STATUS_MISSING        => esc_html__( 'Missing',WP_ULIKE_PRO_DOMAIN ),
			WP_Ulike_Pro_API::STATUS_REQUEST_LOCKED => esc_html__( 'Request Locked',WP_ULIKE_PRO_DOMAIN ),
		];

		echo esc_html__( 'Status',WP_ULIKE_PRO_DOMAIN ); ?>:
		<?php if ( $license_data['license'] === WP_Ulike_Pro_API::STATUS_VALID ) : ?>
			<span style="color: #008000; font-style: italic;"><?php echo esc_html__( 'Active',WP_ULIKE_PRO_DOMAIN ); ?></span>
		<?php else : ?>
			<span style="color: #ff0000; font-style: italic;">
				<?php
				echo isset( $license_data['license'], $license_errors[ $license_data['license'] ] )
					? esc_html( $license_errors[ $license_data['license'] ] )
					: esc_html__( 'Unknown',WP_ULIKE_PRO_DOMAIN ) . ' (' . esc_html( $license_data['license'] ) . ')';
				?>
			</span>
		<?php endif;
	}

	private function is_block_editor_page() {
		$current_screen = get_current_screen();

		if ( method_exists( $current_screen, 'is_block_editor' ) && $current_screen->is_block_editor() ) {
			return true;
		}

		if ( function_exists( 'is_gutenberg_page' ) && is_gutenberg_page() ) {
			return true;
		}

		return false;
	}

	public function admin_license_details() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( $this->is_block_editor_page() ) {
			return;
		}

		$license_key = self::get_license_key();

		if ( empty( $license_key ) ) {
			echo wp_ulike_get_notice_render([
				'id'          => 'wp_ulike_license_activate',
				'title'       => esc_html__( 'Welcome to WP ULike PRO!', WP_ULIKE_PRO_DOMAIN ),
				'description' => esc_html__( 'Please activate your license to get automatic updates, premium support, and unlimited access to our pro features.' , WP_ULIKE_PRO_DOMAIN ),
				'skin'        => 'error',
				'has_close'   => false,
				'buttons'     => array(
					array(
						'label'      => esc_html__( 'Activate License', WP_ULIKE_PRO_DOMAIN ),
						'link'       => self_admin_url( 'admin.php?page=wp-ulike-pro-license' ),
						'target'     => '_self'
					)
				)
			]);
			return;
		}

		$license_data = WP_Ulike_Pro_API::get_license_data( false );
		if ( empty( $license_data['license'] ) ) {
			return;
		}

		$renew_url = WP_Ulike_Pro_API::get_renew_url(
			$license_key,
			'renew-license',
			'license-page',
			array( 'edd_action' => 'renew' )
		);

		$errors = self::get_errors_details(
			array(
				'renew_url'        => $renew_url,
				'license_page_url' => self::get_url(),
				'pricing_url'      => WP_Ulike_Pro_API::get_pricing_url( 'pricing', 'license-page' ),
			)
		);

		if ( isset( $errors[ $license_data['license'] ] ) ) {
			$error_data  = $errors[ $license_data['license'] ];
			echo wp_ulike_get_notice_render([
				'id'          => 'wp_ulike_license_' . $license_data['license'],
				'title'       => $error_data['title'],
				'description' => $error_data['description'],
				'skin'        => 'info',
				'has_close'   => false,
				'buttons'     => array(
					array(
						'label'      => $error_data['button_text'],
						'link'       => $error_data['button_url'],
						'target'     => ! empty( $error_data['button_target'] ) ? $error_data['button_target'] : '_self',
					),
					array(
						'label'      => esc_html__('Remind Me Later', WP_ULIKE_PRO_DOMAIN),
						'type'       => 'skip',
						'color_name' => 'info',
						'expiration' => DAY_IN_SECONDS * 3
					)
				)
			]);
			return;
		}

		if ( WP_Ulike_Pro_API::is_license_active() && WP_Ulike_Pro_API::is_license_about_to_expire() ) {
			$renew_url = WP_Ulike_Pro_API::get_renew_url( $license_key, 'renew-license', 'license-page' );

			$title       = sprintf( esc_html__( 'Your License Will Expire in %s.', WP_ULIKE_PRO_DOMAIN ), human_time_diff( current_time( 'timestamp' ), strtotime( $license_data['expires'] ) ) );
			$description = esc_html__( 'Your WP ULike Pro license is about to expire. Renew now and get updates, support, Pro templates for another year.', WP_ULIKE_PRO_DOMAIN );

			if ( isset( $license_data['renewal_discount'] ) && 0 < $license_data['renewal_discount'] ) {
				$description = sprintf(
					/* translators: %s: Renewal discount. */
					esc_html__( 'Your WP ULike Pro license is about to expire. Renew now and get an exclusive, time-limited %s discount.', WP_ULIKE_PRO_DOMAIN ),
					$license_data['renewal_discount'] . '&#37;'
				);
			}

			echo wp_ulike_get_notice_render([
				'id'          => 'wp_ulike_license_renewal',
				'title'       => $title,
				'description' => $description,
				'has_close'   => false,
				'buttons'     => array(
					array(
						'label'      => esc_html__( 'Renew License', WP_ULIKE_PRO_DOMAIN ),
						'link'       => $renew_url,
						'target'     => '_self'
					),
					array(
						'label'      => esc_html__('Remind Me Later', WP_ULIKE_PRO_DOMAIN),
						'type'       => 'skip',
						'color_name' => 'info',
						'expiration' => DAY_IN_SECONDS * 3
					)
				)
			]);
		}
	}

	public function plugin_action_links( $links ) {
		$license_key = self::get_license_key();

		if ( empty( $license_key ) ) {
			$links['active_license'] = sprintf( '<a href="%s" class="wp-ulike-plugins-gopro">%s</a>', self::get_url(), esc_html__( 'Activate License', WP_ULIKE_PRO_DOMAIN ) );
		}

		return $links;
	}

	public function register_page( $submenus ) {
		$license_submenu_page = array( 'license' => array(
			'title'       => esc_html__( 'License', WP_ULIKE_PRO_DOMAIN ),
			'parent_slug' => 'wp-ulike-settings',
			'capability'  => 'manage_options',
			'path'        => WP_ULIKE_PRO_ADMIN_DIR . '/includes/templates/license.php',
			'menu_slug'   => self::PAGE_ID,
			'load_screen' => false
		) );
		array_splice( $submenus, 5, 0, $license_submenu_page  );

		return $submenus;
	}

	private function update_old_license_info(){
		$old_license_info = get_site_option( 'wp_ulike_pro_license_info' );
		if( ! empty( $old_license_info['purchase_code'] ) ){
			self::set_license_key( $old_license_info['purchase_code'] );
			delete_site_option( 'wp_ulike_pro_license_info' );
		}
	}

	public function __construct() {
		// Update old license info to the new value
		$this->update_old_license_info();

		// Migrate existing licenses to have checksum and signature (backward compatibility)
		$this->migrate_existing_license_data();

		add_filter( 'wp_ulike_admin_pages', [ $this, 'register_page' ], 10, 15 );
		add_action( 'admin_post_wp_ulike_pro_activate_license', [ $this, 'action_activate_license' ] );
		add_action( 'admin_post_wp_ulike_pro_deactivate_license', [ $this, 'action_deactivate_license' ] );
		add_action( 'wp_ajax_wp_ulike_pro_license_activate', [ $this, 'ajax_activate_license' ] );
		add_action( 'wp_ajax_wp_ulike_pro_license_deactivate', [ $this, 'ajax_deactivate_license' ] );
		add_action( 'wp_ajax_wp_ulike_pro_license_refresh', [ $this, 'ajax_refresh_license' ] );

		add_action( 'admin_notices', [ $this, 'admin_license_details' ], 25 );

		add_filter( 'plugin_action_links_' . WP_ULIKE_PRO_BASENAME, [ $this, 'plugin_action_links' ], 50 );
	}

	/**
	 * Migrate existing licenses to have checksum and signature
	 * This ensures backward compatibility with licenses activated before this update
	 */
	private function migrate_existing_license_data() {
		// Only run once per site
		if ( get_option( 'wp_ulike_pro_license_migrated', false ) ) {
			return;
		}

		$license_key = self::get_license_key();

		// If license key exists but no checksum, create it
		if ( ! empty( $license_key ) && empty( get_option( 'wp_ulike_pro_license_checksum', '' ) ) ) {
			$checksum = hash( 'sha256', $license_key . wp_ulike_pro_get_audit_token() . home_url() );
			update_option( 'wp_ulike_pro_license_checksum', $checksum );
		}

		// If license data exists but no signature, create it
		$license_data = WP_Ulike_Pro_API::get_license_data( false );
		if ( is_array( $license_data ) && ! empty( $license_data['license'] ) && empty( get_option( 'wp_ulike_pro_license_signature', '' ) ) ) {
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

		// Mark as migrated
		update_option( 'wp_ulike_pro_license_migrated', true );
	}
}