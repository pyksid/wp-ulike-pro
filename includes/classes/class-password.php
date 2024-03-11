<?php

final class WP_Ulike_Pro_Password extends wp_ulike_ajax_listener_base {

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
		// Reset Password
		$this->data['username'] = isset( $_POST['username'] ) ? sanitize_user( $_POST['username'] ) : NULL;
		// Change Password
		$this->data['newpassword'] = isset( $_POST['newpassword'] ) ? sanitize_text_field( $_POST['newpassword'] ) : NULL;
		$this->data['repassword']  = isset( $_POST['repassword'] ) ? sanitize_text_field( $_POST['repassword'] ) : NULL;
		$this->data['rp_key']      = isset( $_POST['rp_key'] ) ? sanitize_text_field( $_POST['rp_key'] ) : NULL;
		// General params
		$this->data['security'] = isset( $_POST['security'] ) ? sanitize_text_field( $_POST['security'] ) : NULL;
		// Set form ID for action usage
		$this->data['_form_id'] = isset( $_POST['_form_id'] ) ? sanitize_text_field ( $_POST['_form_id'] ) : 1;
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

			// Set message text
			$this->data['message']  = '';
			// Set redirect URL
            $this->data['_redirect_to'] = '';
            if( ! empty( $this->data['rp_key'] ) ){
				$this->change_password();
				$this->data['message']  = WP_Ulike_Pro_Options::getNoticeMessage( 'password_reset', esc_html__( 'Your password has been reset.', WP_ULIKE_PRO_DOMAIN ) );
				$this->data['_redirect_to'] = WP_Ulike_Pro_Permalinks::get_login_url();
			} else {
				$this->retrieve_password();
				// add notice
				wp_ulike_pro_add_notice( WP_Ulike_Pro_Options::getFormLabel( 'rp', 'mail_message', esc_html__( 'Check your e-mail address linked to the account for the confirmation link, including the spam or junk folder.
				', WP_ULIKE_PRO_DOMAIN ) ), 'success' );

				$this->data['_redirect_to'] = add_query_arg( array(
					'action' => 'checkemail'
				), WP_Ulike_Pro_Options::getResetPasswordPageUrl() );
			}

            $this->afterAction();

			$this->response( array(
                'message'  => $this->data['message'],
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
	 * Change password process
	 *
	 * @return void
	 */
	private function change_password(){

		if( empty( $this->data['newpassword'] ) || empty( $this->data['repassword'] ) ){
			throw new \Exception( WP_Ulike_Pro_Options::getNoticeMessage( 'required_fields', esc_html__( 'Please enter required fields.', WP_ULIKE_PRO_DOMAIN ) ) );
		}

		if( $this->data['repassword'] !== $this->data['newpassword'] ){
			throw new \Exception( WP_Ulike_Pro_Options::getNoticeMessage( 'password_match', esc_html__( 'Oops! Password did not match! Try again.', WP_ULIKE_PRO_DOMAIN ) ) );
		}

		$rp_cookie = 'wp-resetpass-' . COOKIEHASH;

		$user = false;
		if ( isset( $_COOKIE[$rp_cookie] ) && 0 < strpos( $_COOKIE[$rp_cookie], ':' ) ) {
			list( $rp_login, $rp_key ) = explode( ':', wp_unslash( $_COOKIE[$rp_cookie] ), 2 );
			$user = check_password_reset_key( $rp_key, $rp_login );
			if ( ! empty( $this->data['newpassword'] ) && ! hash_equals( $rp_key, $this->data['rp_key'] ) ) {
				$user = false;
			}
		}

		if ( ! $user  || is_wp_error( $user ) ) {
			wp_ulike_pro_setcookie( $rp_cookie, false );
			throw new \Exception( WP_Ulike_Pro_Options::getNoticeMessage( 'permission_denied', esc_html__( 'Something went wrong. Please try again or contact the admin.', WP_ULIKE_PRO_DOMAIN ) ));
		}

		wp_set_password( $this->data['newpassword'], $user->ID );
		update_user_option( $user->ID, 'default_password_nag', false, true );

		wp_ulike_pro_setcookie( $rp_cookie, false );

		// Add user id param for use in after action hook
		$this->data['user_id'] = $user->ID;

		$mail = new WP_Ulike_Pro_Mail();
		$mail->send( $user->user_email, 'change-password', array( 'user_id' => $user->ID ) );
	}

	/**
	 * retrieve password & send email
	 *
	 * @return void
	 */
	private function retrieve_password(){

		if ( empty( $this->data['username'] ) || ! is_string( $this->data['username'] ) ) {
			throw new \Exception( WP_Ulike_Pro_Options::getNoticeMessage( 'empty_username', esc_html__( 'Enter a username or email address.', WP_ULIKE_PRO_DOMAIN ) ) );
		} elseif ( strpos( $this->data['username'], '@' ) ) {
			$user_data = get_user_by( 'email', trim( wp_unslash( $this->data['username'] ) ) );
			if ( empty( $user_data ) ) {
				throw new \Exception( WP_Ulike_Pro_Options::getNoticeMessage( 'invalidcombo', esc_html__( 'There is no account with that username or email address.', WP_ULIKE_PRO_DOMAIN ) ) );
			}
		} else {
			$login     = trim( $this->data['username'] );
			$user_data = get_user_by( 'login', $login );
		}

		if ( ! $user_data ) {
			throw new \Exception( WP_Ulike_Pro_Options::getNoticeMessage( 'invalidcombo', esc_html__( 'There is no account with that username or email address.', WP_ULIKE_PRO_DOMAIN ) ) );
		}

		// Add user id param for use in after action hook
		$this->data['user_id'] = $user_data->ID;

		$mail = new WP_Ulike_Pro_Mail();
		if ( ! $mail->send( $user_data->user_email, 'reset-password', array( 'user_id' => $user_data->ID ) ) ){
			throw new \Exception( WP_Ulike_Pro_Options::getNoticeMessage( 'email_error', esc_html__( 'The email could not be sent.', WP_ULIKE_PRO_DOMAIN ) ) );
		}

	}

	/**
	* Before Action
	* Provides hook for performing actions before a process
	*/
	private function beforeAction(){
		do_action_ref_array('wp_ulike_pro_before_reset_password_process', array( &$this ) );
	}

	/**
	* After Action
	* Provides hook for performing actions after a process
	*/
	private function afterAction(){
		do_action_ref_array( 'wp_ulike_pro_after_reset_password_process', array( &$this ) );
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
		if( WP_Ulike_Pro_Options::getResetPasswordPage() == '' || ( ! wp_verify_nonce( $this->data['security'], 'wp-ulike-pro-forms-nonce') && ! wp_ulike_is_cache_exist() ) ){
            throw new \Exception( WP_Ulike_Pro_Options::getNoticeMessage( 'permission_denied', esc_html__( 'Something went wrong. Please try again or contact the admin.', WP_ULIKE_PRO_DOMAIN ) ) );
        }

	}
}