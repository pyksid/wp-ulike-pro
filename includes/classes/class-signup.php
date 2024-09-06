<?php

final class WP_Ulike_Pro_SignUp extends wp_ulike_ajax_listener_base {

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
		$this->data['username']  = isset( $_POST['username'] ) ? sanitize_user( wp_unslash( $_POST['username'] ) ) : NULL;
		$this->data['firstname'] = isset( $_POST['firstname'] ) ? sanitize_text_field( wp_unslash( $_POST['firstname'] ) ) : NULL;
		$this->data['lastname']  = isset( $_POST['lastname'] ) ? sanitize_text_field( wp_unslash( $_POST['lastname'] ) ) : NULL;
		$this->data['email']     = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : NULL;
		$this->data['password']  = isset( $_POST['password'] ) ? sanitize_text_field( wp_unslash( $_POST['password'] ) ) : NULL;
		$this->data['security']  = isset( $_POST['security'] ) ? sanitize_text_field( wp_unslash( $_POST['security'] ) ) : NULL;
		// Set form ID for action usage
		$this->data['_form_id']  = isset( $_POST['_form_id'] ) ? sanitize_text_field ( wp_unslash( $_POST['_form_id'] ) ) : 1;
		// Custom redirect url
		$this->data['_redirect_to']  = isset( $_POST['_redirect_to'] ) ? esc_url( wp_unslash(  $_POST['_redirect_to'] ) ) : NULL;
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

			$userdata = array(
				'user_login' => $this->data['username'],
				'user_email' => $this->data['email'],
				'user_pass'  => $this->data['password'],
				'first_name' => $this->data['firstname'],
				'last_name'  => $this->data['lastname']
			);

			if( WP_Ulike_Pro_Options::isEmailVerifyEnabled() ){
				$userdata['role'] = 'pending';
			}

			$user_id = wp_insert_user( $userdata );

            if ( is_wp_error( $user_id ) ) {
                throw new \Exception( wp_ulike_pro_clean_tags( $user_id->get_error_message() ) );
            }

			$mail = new WP_Ulike_Pro_Mail();

			$message = '';

			if( WP_Ulike_Pro_Options::isEmailVerifyEnabled() ){
				// Save verification key in user meta
				update_user_meta( $user_id, 'ulp_email_verification_key', wp_generate_password( 20, false ) );
				// Send verification email
				$mail->send( $this->data['email'], 'checkmail', array( 'user_id' => $user_id ) );
				// message
				$message = WP_Ulike_Pro_Options::getNoticeMessage( 'email_verification', esc_html__( 'Please check your email to activate your account.', WP_ULIKE_PRO_DOMAIN ) );
			} else {
				// auto login
				if( WP_Ulike_Pro_Options::checkAutoLogin() ){
					$creds = array(
						'user_login'    => $this->data['username'],
						'user_password' => $this->data['password'],
						'remember'      => false
					);
					$user = wp_signon( $creds, false );

					if ( is_wp_error( $user ) ) {
						throw new \Exception( wp_ulike_pro_clean_tags( $user->get_error_message() ) );
					}

					wp_set_current_user( $user->ID );

					// redirect to a page
					$this->data['_redirect_to'] = WP_Ulike_Pro_Options::isProfileVisible() ? wp_ulike_pro_get_user_profile_permalink( $user->ID ) : home_url();
				}

				$mail->send( $this->data['email'], 'welcome', array( 'user_id' => $user_id ) );

				// message
				$message = WP_Ulike_Pro_Options::getNoticeMessage( 'signup_success', esc_html__( 'Signup successful.', WP_ULIKE_PRO_DOMAIN ) );
			}

			// Add user id param for use in after action hook
			$this->data['user_id']  = $user_id;
			// Set redirect URL
			if( empty( $this->data['_redirect_to'] ) ){
				$this->data['_redirect_to'] = WP_Ulike_Pro_Options::getSignUpRedirectUrl();
			}

            $this->afterAction();

			$this->response( array(
                'message'  => $message,
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
		do_action_ref_array('wp_ulike_pro_before_signup_process', array( &$this ) );
	}

	/**
	* After Action
	* Provides hook for performing actions after a process
	*/
	private function afterAction(){
		do_action_ref_array( 'wp_ulike_pro_after_signup_process', array( &$this ) );
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
		if( ! wp_verify_nonce( $this->data['security'], 'wp-ulike-pro-forms-nonce') && ! wp_ulike_is_cache_exist() ){
            throw new \Exception( WP_Ulike_Pro_Options::getNoticeMessage( 'permission_denied', esc_html__( 'Something went wrong. Please try again or contact the admin.', WP_ULIKE_PRO_DOMAIN ) ) );
        }

		// Return false when nonce invalid
		if( empty( $this->data['username'] ) || empty( $this->data['password'] ) || empty( $this->data['email'] ) ){
            throw new \Exception( WP_Ulike_Pro_Options::getNoticeMessage( 'required_fields', esc_html__( 'Please enter required fields.', WP_ULIKE_PRO_DOMAIN ) ) );
		}

		if ( ! get_option( 'users_can_register' ) ) {
			throw new \Exception( WP_Ulike_Pro_Options::getNoticeMessage( 'disabled_registration', esc_html__( 'Registration is currently disabled.', WP_ULIKE_PRO_DOMAIN ) ) );
		}

		// Validate email address
		if( ! is_email( $this->data['email'] ) ){
            throw new \Exception( WP_Ulike_Pro_Options::getNoticeMessage( 'invalid_email', esc_html__( 'Email address is not valid!', WP_ULIKE_PRO_DOMAIN ) ) );
		}

		// Check email exists
		if( email_exists( $this->data['email'] ) ){
            throw new \Exception( WP_Ulike_Pro_Options::getNoticeMessage( 'email_exist', esc_html__( 'Sorry, that email address is already used!', WP_ULIKE_PRO_DOMAIN ) ) );
        }

		// Check username exists
		if( username_exists( $this->data['username'] ) ){
            throw new \Exception( WP_Ulike_Pro_Options::getNoticeMessage( 'username_exist', esc_html__( 'Sorry, that username is already used!', WP_ULIKE_PRO_DOMAIN ) ) );
        }

	}
}