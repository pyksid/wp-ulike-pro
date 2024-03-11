<?php

// no direct access allowed
if ( ! defined('ABSPATH') ) {
    die();
}

class WP_Ulike_Pro_License_Activation{

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance  = null;

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

		if( empty( $purchase_code ) ){
	    	$output['message'] = esc_html__( 'Your license is missing. Please check your key again.', WP_ULIKE_PRO_DOMAIN );
	    	return $output;
		}

	    // fetch license info
		$response = WP_Ulike_Pro_API::activate_license( $purchase_code );

		if ( is_wp_error( $response ) ) {
			$output['message'] = sprintf( '%s (%s) ', $response->get_error_message(), $response->get_error_code() );
		} else {
			if ( WP_Ulike_Pro_API::STATUS_VALID !== $response['license'] ) {
				$output['message'] =  WP_Ulike_Pro_API::get_error_message( $response['error'] );
			} else {
				// Remove token transient
                wp_ulike_delete_transient( 'wp_ulike_pro_check_license_status' );
				$license_info = array();
                $license_info['license']       = $response['license'];
                $license_info['purchase_code'] = $purchase_code;
                $license_info['expires']       = $response['expires'];
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

		$license_data = WP_Ulike_Pro_API::get_license_data();

		if ( empty( $license_data['license'] ) ) {
			return;
		}

        if ( WP_Ulike_Pro_API::STATUS_VALID !== $license_data['license'] ) {
            // if token is no longer valid to be used on this domain
            $license_info = get_site_option( $this->option_prefix, array() );
            $license_info['license'] = $license_data['license'];
			update_site_option( $this->option_prefix, $license_info );
			wp_ulike_delete_transient( 'wp_ulike_pro_check_license_status' );
        }

        return $license_data;
    }

}
