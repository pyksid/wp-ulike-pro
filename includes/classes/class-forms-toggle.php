<?php

use RobThree\Auth\TwoFactorAuth;

final class WP_Ulike_Pro_Forms_Toggle extends wp_ulike_ajax_listener_base {

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
		$this->data['request'] = ! empty( $_REQUEST['request'] )  ? $_REQUEST['request'] : NULL;
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

			$content = '';

			switch ($this->data['request']) {
				case 'signup':
					$content = do_shortcode('[wp_ulike_pro_signup_form ajax_toggle=1 redirect_to="current_page"]');
					break;

				case 'login':
					$content = do_shortcode('[wp_ulike_pro_login_form ajax_toggle=1 redirect_to="current_page"]');
					break;

				case 'reset-password':
					$content = do_shortcode('[wp_ulike_pro_reset_password_form ajax_toggle=1]');
					break;
			}

            $this->afterAction();

			$this->response( array(
				'content' => apply_filters( 'wp_ulike_pro_toggle_form_content', $content, $this->data['request'] ),
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
		do_action_ref_array('wp_ulike_pro_before_forms_toggle_validation', array( &$this ) );
	}

	/**
	* After Action
	* Provides hook for performing actions after a process
	*/
	private function afterAction(){
		do_action_ref_array( 'wp_ulike_pro_after_forms_toggle_validation', array( &$this ) );
    }

	/**
	* Validate the Favorite
	*/
	private function validates(){
		// Return false when ID not exist
		if( empty( $this->data['request'] ) ) return false;

		return true;
	}
}