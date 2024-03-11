<?php

final class WP_Ulike_Pro_Login extends wp_ulike_ajax_listener_base {

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
		$this->data['username'] = isset( $_POST['username'] ) ? sanitize_user( $_POST['username'] ) : NULL;
		$this->data['password'] = isset( $_POST['password'] ) ? sanitize_text_field( $_POST['password'] ) : NULL;
		$this->data['security'] = isset( $_POST['security'] ) ? sanitize_text_field( $_POST['security'] ) : NULL;
		$this->data['remember'] = empty( $_POST['remember'] ) ? false : true;
		// Set form ID for action usage
		$this->data['_form_id'] = isset( $_POST['_form_id'] ) ? sanitize_text_field ( $_POST['_form_id'] ) : 1;
		// Custom redirect url
		$this->data['_redirect_to'] = isset( $_POST['_redirect_to'] ) ? esc_url( $_POST['_redirect_to'] ) : NULL;
	}

	/**
	 * Process request
	 *
	 * @return void
	 */
	private function process(){
		try {
			$this->beforeAction();

			$this->validates();

            $creds = array(
                'user_login'    => $this->data['username'],
                'user_password' => $this->data['password'],
                'remember'      => $this->data['remember']
            );

            $user = wp_signon( $creds );

            if ( is_wp_error( $user ) ) {
                throw new \Exception( WP_Ulike_Pro_Options::getNoticeMessage( 'login_failed', esc_html__( 'Invalid username or incorrect password!', WP_ULIKE_PRO_DOMAIN ) ) );
            }

			wp_set_current_user( $user->ID );
			// Add user id param for use in after action hook
			$this->data['user_id'] = $user->ID;

			// Set redirect URL
			if( empty( $this->data['_redirect_to'] ) ){
				$this->data['_redirect_to'] = WP_Ulike_Pro_Options::getLoginRedirectUrl();
				if( empty( $this->data['_redirect_to'] ) ){
					$this->data['_redirect_to'] = WP_Ulike_Pro_Options::isProfileVisible() ? wp_ulike_pro_get_user_profile_permalink( $user->ID ) : home_url();
				}
			}

            $this->afterAction();

			$this->response( array(
                'message'  => WP_Ulike_Pro_Options::getNoticeMessage( 'login_success', esc_html__( 'Login successful.', WP_ULIKE_PRO_DOMAIN ) ),
                'status'   => 'success',
                'redirect' => esc_url( $this->data['_redirect_to'] )
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
		do_action_ref_array('wp_ulike_pro_before_login_process', array( &$this ) );
	}


	/**
	* After Action
	* Provides hook for performing actions after a process
	*/
	private function afterAction(){
		do_action_ref_array( 'wp_ulike_pro_after_login_process', array( &$this ) );
    }

	/**
	* Validate the Favorite
	*/
	private function validates(){
		// Return false in preview mode
		if( WP_Ulike_Pro::is_preview_mode() ){
			throw new \Exception( esc_html__( 'It is not possible to perform this process in preview mode!', WP_ULIKE_PRO_DOMAIN ) );
		}

		// Return false when nonce invalid
		if( empty( $this->data['username'] ) || empty( $this->data['password'] ) ){
            throw new \Exception( WP_Ulike_Pro_Options::getNoticeMessage( 'required_fields', esc_html__( 'Please enter required fields.', WP_ULIKE_PRO_DOMAIN ) ) );
        }

		// Return false when nonce invalid
		if( ! wp_verify_nonce( $this->data['security'], 'wp-ulike-pro-forms-nonce') && ! wp_ulike_is_cache_exist() ){
            throw new \Exception( WP_Ulike_Pro_Options::getNoticeMessage( 'permission_denied', esc_html__( 'Something went wrong. Please try again or contact the admin.', WP_ULIKE_PRO_DOMAIN ) ) );
        }

	}
}