<?php

final class WP_Ulike_Pro_Two_Factor_Validation extends wp_ulike_ajax_listener_base {

	public function __construct(){
		parent::__construct();
		$this->setFormData();
		$this->process();
	}

	/**
	 * Set Form Data
	 *
	 * @return void
	 */
	private function setFormData(){
		$this->data['secret'] = ! empty( $_POST['secret'] ) ? $_POST['secret'] : NULL;
		$this->data['otp']    = ! empty( $_POST['otp'] ) ? $_POST['otp'] : NULL;
		$this->data['nonce']  = ! empty( $_POST['nonce'] )  ? $_POST['nonce'] : NULL;
	}

	/**
	 * Process request
	 *
	 * @return void
	 */
	public function process(){
		try {

			$this->beforeAction();

			if ( !$this->validates() ){
				throw new \Exception( WP_Ulike_Pro_Options::getNoticeMessage( 'permission_denied', esc_html__( 'Something went wrong. Please try again or contact the admin.', WP_ULIKE_PRO_DOMAIN ) ) );
			}

			// check two factor is enabled
			if( ! WP_Ulike_Pro_Options::is2FactorAuthEnabled() ){
				throw new \Exception(  esc_html__( '2-factor support is not enabled!', WP_ULIKE_PRO_DOMAIN ) );
			}

			// create secret args
			$secret = array( $this->data['secret'] => array(
				'created_at' => current_time( 'timestamp' ),
				'user_agent' => isset( $_SERVER['HTTP_USER_AGENT'] ) ? wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) : ''
			) );

            if( ! wp_ulike_pro_is_valid_otp( $this->data['otp'], $secret ) ){
                throw new \Exception( WP_Ulike_Pro_Options::getNoticeMessage( 'incorrect_tfa', esc_html__( 'The one-time password (TFA code) you entered was incorrect', WP_ULIKE_PRO_DOMAIN ) ) );
            }

			// update user meta
			$user_code = get_user_meta( $this->user, 'ulp_two_factor_secrets', true );
			$user_code = empty( $user_code ) ? $secret :  array_merge_recursive( $user_code, $secret );
			update_user_meta( $this->user, 'ulp_two_factor_secrets', $user_code );

            $this->afterAction();

			$this->response( array(
				'refresh' => true
			) );

		} catch ( \Exception $e ){
			return $this->sendError( array(
                'message' => $e->getMessage(),
                'status'  => 'error',
            ) );
		}
	}

	/**
	* Before Action
	* Provides hook for performing actions before a process
	*/
	private function beforeAction(){
		do_action_ref_array('wp_ulike_pro_before_two_factor_validation', array( &$this ) );
	}

	/**
	* After Action
	* Provides hook for performing actions after a process
	*/
	private function afterAction(){
		do_action_ref_array( 'wp_ulike_pro_after_two_factor_validation', array( &$this ) );
    }

	/**
	* Validate the Favorite
	*/
	private function validates(){
		// Return false when ID not exist
		if( empty( $this->data['secret'] ) || ! is_array( $this->data['otp'] ) ) return false;
		// check nonce field
		if( ! $this->user || ! wp_verify_nonce( $this->data['nonce'], 'wp_ulike_pro_two_factor_nonce_field' )  ) return false;

		return true;
	}
}