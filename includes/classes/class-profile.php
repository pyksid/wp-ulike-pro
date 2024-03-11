<?php

final class WP_Ulike_Pro_Profile extends wp_ulike_ajax_listener_base {

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
		$this->data['security']    = isset( $_POST['security'] ) ? sanitize_text_field( $_POST['security'] ) : NULL;
		$this->data['first_name']  = isset( $_POST['firstname'] ) ? sanitize_user( $_POST['firstname'] ) : NULL;
		$this->data['last_name']   = isset( $_POST['lastname'] ) ? sanitize_user( $_POST['lastname'] ) : NULL;
		$this->data['user_email']  = isset( $_POST['email'] ) ? sanitize_text_field( $_POST['email'] ) : NULL;
		$this->data['user_url']    = isset( $_POST['website'] ) ? sanitize_text_field( $_POST['website'] ) : NULL;
		$this->data['description'] = isset( $_POST['bio'] ) ? sanitize_text_field( $_POST['bio'] ) : NULL;
		// Set form ID for action usage
		$this->data['_form_id']    = isset( $_POST['_form_id'] ) ? sanitize_text_field ( $_POST['_form_id'] ) : 1;
	}

	/**
	 * Process request
	 *
	 * @return void
	 */
	public function process(){
		try {
			$this->beforeAction();

			$this->validates();

			$user_args = array(
				'ID' => $this->user
			);

			foreach ( $this->data as $key => $value) {
				if( in_array( $key, array( 'security', '_form_id' ) ) || $value === NULL ){
					continue;
				}
				// Generate user args data
				$user_args[$key] = $value;
			}

			$user_id = wp_update_user( $user_args );

			if ( is_wp_error( $user_id ) ) {
				throw new \Exception( $user_id->get_error_message() );
			}

            $this->afterAction();

			$this->response( array(
                'message'  => WP_Ulike_Pro_Options::getNoticeMessage( 'profile_success', esc_html__( 'Profile data updated.', WP_ULIKE_PRO_DOMAIN ) ),
                'status'   => 'success'
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
		do_action_ref_array('wp_ulike_pro_before_profile_process', array( &$this ) );
	}

	/**
	* After Action
	* Provides hook for performing actions after a process
	*/
	private function afterAction(){
		do_action_ref_array( 'wp_ulike_pro_after_profile_process', array( &$this ) );
    }

	/**
	* Validate the Favorite
	*/
	private function validates(){
		// Return false in preview mode
		if( WP_Ulike_Pro::is_preview_mode() ){
			throw new \Exception( esc_html__( 'It is not possible to perform this process in preview mode!', WP_ULIKE_PRO_DOMAIN ) );
		}

		// Validate email address
		if( ! is_email( $this->data['user_email'] ) ){
            throw new \Exception( WP_Ulike_Pro_Options::getNoticeMessage( 'invalid_email', esc_html__( 'Email address is not valid!', WP_ULIKE_PRO_DOMAIN ) ) );
		}

		// Return false when nonce invalid
		if( ! $this->user || ( ! wp_verify_nonce( $this->data['security'], 'wp-ulike-pro-forms-nonce') && ! wp_ulike_is_cache_exist() ) ){
            throw new \Exception( WP_Ulike_Pro_Options::getNoticeMessage( 'permission_denied', esc_html__( 'Something went wrong. Please try again or contact the admin.', WP_ULIKE_PRO_DOMAIN ) ) );
        }

	}
}