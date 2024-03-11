<?php

final class WP_Ulike_Pro_Logout {

	public $data;

	public function __construct(){
		$this->setFormData();
	}

	/**
	 * Set Form Data
	 *
	 * @return void
	 */
	private function setFormData(){
		$this->data['action']   = isset( $_GET['action'] ) && $_GET['action'] === 'logout' ? true : false;
		$this->data['security'] = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( $_GET['_wpnonce'] ) : NULL;
		$this->data['redirect'] = ! empty( $_GET['redirect_to'] ) ? esc_url( $_GET['redirect_to'] ) : NULL;
	}

	/**
	 * @param $redirect_url
	 * @param $status
	 *
	 * @return false|string
	 */
	function safe_redirect_default( $redirect_url, $status ) {
		$login_url = WP_Ulike_Pro_Permalinks::get_login_url();
		return $login_url ? $login_url : $redirect_url;
	}

	/**
	 * Process request
	 *
	 * @return void
	 */
	public function maybeLogout(){
		try {
			$this->validates();

			do_action_ref_array( 'wp_ulike_pro_before_logout_process', array( &$this ) );

			add_filter( 'wp_safe_redirect_fallback', array( &$this, 'safe_redirect_default' ), 10, 2 );

			wp_destroy_current_session();
			wp_logout();
			session_unset();

			if ( empty( $this->data['redirect'] ) ) {
				$this->data['redirect'] = WP_Ulike_Pro_Permalinks::get_login_url();
				// Redirect to homepage if empty
				if( empty( $this->data['redirect'] ) ){
					$this->data['redirect'] = home_url();
				}
			}

			do_action_ref_array( 'wp_ulike_pro_after_logout_process', array( &$this ) );
			exit( wp_safe_redirect(  $this->data['redirect'] ) );

		} catch ( \Exception $e ){
			add_filter( 'wp_safe_redirect_fallback', array( &$this, 'safe_redirect_default' ), 10, 2 );
			exit( wp_safe_redirect( home_url() ) );
		}
	}

	/**
	* Validate the Favorite
	*/
	private function validates(){

		// Return false when nonce invalid
		if( ! wp_verify_nonce( $this->data['security'], 'log-out' ) || ! is_user_logged_in() || ! $this->data['action'] ){
            throw new \Exception( WP_Ulike_Pro_Options::getNoticeMessage( 'permission_denied', esc_html__( 'Something went wrong. Please try again or contact the admin.', WP_ULIKE_PRO_DOMAIN ) ));
        }

	}
}