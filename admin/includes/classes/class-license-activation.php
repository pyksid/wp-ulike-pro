<?php

// no direct access allowed
if ( ! defined('ABSPATH') ) {
    die();
}

/**
 * Legacy License Activation Class
 *
 * @deprecated This class is maintained for backward compatibility.
 *             New code should use WP_Ulike_Pro_License and WP_Ulike_Pro_API instead.
 *
 * @since 1.0.0
 */
class WP_Ulike_Pro_License_Activation{

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance  = null;

	/**
	 * Option name for legacy license info.
	 *
	 * @var string
	 */
	protected $option_prefix = 'wp_ulike_pro_license_info';

	function __construct(){
		$this->option_prefix = 'wp_ulike_pro_license_info';
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Activate or deactivate license if license info is correct
	 *
	 * @param  string $purchase_code item purchase code
	 * @param  string $action        activate or deactivate license
	 *
	 * @return array   An array containing result of activation or deactivation
	 */
	public function license_action( $purchase_code, $action = 'activate_license' ){

		$output = array(
			'success' 	=> 0,
			'status'    => 'invalid',
			'message' 	=> '',
		);

		// Sanitize input
		$purchase_code = sanitize_text_field( trim( $purchase_code ) );

		if( empty( $purchase_code ) ){
	    	$output['message'] = esc_html__( 'Your license is missing. Please check your key again.', WP_ULIKE_PRO_DOMAIN );
	    	return $output;
		}

		// Basic validation
		if ( strlen( $purchase_code ) < 10 ) {
			$output['message'] = esc_html__( 'Invalid license key format. Please check your key and try again.', WP_ULIKE_PRO_DOMAIN );
			return $output;
		}

	    // fetch license info
		$response = WP_Ulike_Pro_API::activate_license( $purchase_code );

		if ( is_wp_error( $response ) ) {
			$output['message'] = wp_kses_post( $response->get_error_message() );
		} else {
			if ( ! isset( $response['license'] ) || WP_Ulike_Pro_API::STATUS_VALID !== $response['license'] ) {
				$error_key = isset( $response['error'] ) ? $response['error'] : 'unknown_error';
				$output['message'] = WP_Ulike_Pro_API::get_error_message( $error_key );
			} else {
				// Use the new license system instead of old site_option
				WP_Ulike_Pro_License::set_license_key( $purchase_code );
				WP_Ulike_Pro_API::set_license_data( $response );

				// Keep old option for backward compatibility (deprecated)
				$license_info = array();
                $license_info['license']       = $response['license'];
                $license_info['purchase_code'] = $purchase_code;
                $license_info['expires']       = isset( $response['expires'] ) ? $response['expires'] : '';
                update_site_option( $this->option_prefix, $license_info );

				$output['status']  = 'valid';
				$output['message'] = esc_html__( 'License has been activated successfully.', WP_ULIKE_PRO_DOMAIN );
				$output['success'] = 1;
			}
		}

        do_action( 'wp_ulike_pro_on_license_action', $action, $output );

	    return $output;
	}


    public function maybe_invalid_license(){

		$license_data = WP_Ulike_Pro_API::get_license_data( false );

		if ( empty( $license_data ) || ! is_array( $license_data ) || empty( $license_data['license'] ) ) {
			return $license_data;
		}

        if ( WP_Ulike_Pro_API::STATUS_VALID !== $license_data['license'] ) {
            // if token is no longer valid to be used on this domain
            // Keep old option for backward compatibility (deprecated)
            $license_info = get_site_option( $this->option_prefix, array() );
            $license_info['license'] = $license_data['license'];
			update_site_option( $this->option_prefix, $license_info );
        }

        return $license_data;
    }

}

