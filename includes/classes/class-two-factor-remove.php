<?php

use RobThree\Auth\TwoFactorAuth;

final class WP_Ulike_Pro_Two_Factor_Remove extends wp_ulike_ajax_listener_base {

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
		$this->data['key']   = ! empty( $_REQUEST['key'] )  ? $_REQUEST['key'] : NULL;
		$this->data['nonce'] = ! empty( $_REQUEST['nonce'] )  ? $_REQUEST['nonce'] : NULL;
	}

	/**
	 * Process request
	 *
	 * @return void
	 */
	public function process(){
		try {
			$this->beforeAction();

			$permission_denied =  WP_Ulike_Pro_Options::getNoticeMessage( 'permission_denied', esc_html__( 'Something went wrong. Please try again or contact the admin.', WP_ULIKE_PRO_DOMAIN ) );

			if ( !$this->validates() ){
				throw new \Exception( $permission_denied );
			}

			// check two factor is enabled
			if( ! WP_Ulike_Pro_Options::is2FactorAuthEnabled() ){
				throw new \Exception(  esc_html__( '2-factor support is not enabled!', WP_ULIKE_PRO_DOMAIN ) );
			}

			$removed = false;

			// get secrets list
			$secrets = get_user_meta( $this->user, 'ulp_two_factor_secrets', true );

			if( ! empty( $secrets ) ){
				foreach ($secrets as $secret_value => $secret_args) {
					if( $this->data['key'] == $secret_value ){
						unset( $secrets[$secret_value] );
						$removed = true;
					}
				}
			}

			if ( ! $removed  ){
				throw new \Exception( $permission_denied );
			}

			// update list
			update_user_meta( $this->user, 'ulp_two_factor_secrets', $secrets );

            $this->afterAction();

			$this->response( array(
				'message' => esc_html__( '2-factor account has been removed', WP_ULIKE_PRO_DOMAIN ),
				'status'  => 'success',
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
		if( empty( $this->data['key'] ) || empty( $this->data['nonce'] ) ) return false;
		// check nonce field
		if( ! $this->user || ! wp_verify_nonce( $this->data['nonce'], 'wp_ulike_pro_two_factor_nonce_field' )  ) return false;

		return true;
	}
}