<?php

final class WP_Ulike_Pro_Likers {

	public $data;

	public function __construct(){
		$this->setFormData();
		$this->printList();
	}

	/**
	 * Set Form Data
	 *
	 * @return void
	 */
	private function setFormData(){
		$this->data['id']   = ! empty( $_REQUEST['id'] )  ? (int) $_REQUEST['id'] : NULL;
		$this->data['type'] = ! empty( $_REQUEST['type'] ) ? sanitize_text_field( $_REQUEST['type'] ) : NULL;
	}

	/**
	 * Process request
	 *
	 * @return void
	 */
	public function printList(){
		try {

			$this->beforeAction();

			$this->settings_type = new wp_ulike_setting_type( $this->data['type'] );

			if ( !$this->validates() ){
				throw new \Exception( WP_Ulike_Pro_Options::getNoticeMessage( 'permission_denied', esc_html__( 'Something went wrong. Please try again or contact the admin.', WP_ULIKE_PRO_DOMAIN ) ) );
			}

			if( empty( $this->settings_type->getType() ) ){
				throw new \Exception( esc_html__( 'Invalid item type.', WP_ULIKE_PRO_DOMAIN ) );
			}

			// Final template
			$template  = '';
			// Get all likers list
			$get_users = wp_ulike_get_likers_list_per_post( $this->settings_type->getTableName(), $this->settings_type->getColumnName(), $this->data['id'], NULL );

			if( empty( $get_users ) ){
				throw new \Exception( esc_html__( 'This item has not been liked by any user!', WP_ULIKE_PRO_DOMAIN ) );
			}

			// Generate users list
			$user_list  = '';
			$modal_temp = WP_Ulike_Pro_Options::getLikersModalTemplate( $this->data['type'] );

			foreach ( $get_users as $user ) {
				$user_info	= get_user_by( 'id', $user );
				// Check user existence
				if( ! $user_info ){
					continue;
				}
				$get_user_history = wp_ulike_get_user_item_history( array(
					"item_id"           => $this->data['id'],
					"item_type"         => $this->data['type'],
					"current_user"      => $user,
					"settings"          => new wp_ulike_setting_type( $this->data['type'] )
				) );

				$extra_vars = apply_filters( 'wp_ulike_pro_likers_list_extra_vars', array(
					'{user_status}' => ! empty( $get_user_history[$this->data['id']] ) ? $get_user_history[$this->data['id']] : ''
				), $user, $this->data['id'], $this->data['type'], $get_user_history );

				$tags = new WP_Ulike_Pro_Convert_Tags( array( 'user_id' => $user ), $extra_vars );
				$user_list .=  $tags->convert( $modal_temp );
			}

			// Generate template
			$template = sprintf( '<div class="ulpmodal-ajax-wrapper"><h3 class="ulpmodal-title">%s</h3><div class="ulp-modal-likers-list">%s</div></div>', WP_Ulike_Pro_Options::getLikersModalTitle( $this->data['type'] ), $user_list );

			$this->afterAction();

			// Print template
			echo apply_filters( 'wp_ulike_pro_likers_list_template', $template, array( &$this ) );

		} catch ( \Exception $e ){
			// Print error message
			echo sprintf( '<div class="ulpmodal-ajax-wrapper">%s</div>', $e->getMessage() );
		}

		die;
	}

	/**
	* Before Action
	* Provides hook for performing actions before a process
	*/
	private function beforeAction(){
		do_action_ref_array('wp_ulike_pro_before_likers_process', array( &$this ) );
	}

	/**
	* After Action
	* Provides hook for performing actions after a process
	*/
	private function afterAction(){
		do_action_ref_array( 'wp_ulike_pro_after_likers_process', array( &$this ) );
    }

	/**
	* Validate the Favorite
	*/
	private function validates(){
		// Return false when ID not exist
		if( empty( $this->data['id'] ) || empty( $this->data['type'] ) ) return false;
		// Return false when anonymous display is off
		if( wp_ulike_setting_repo::restrictLikersBox( $this->settings_type->getType() ) && ! is_user_logged_in() ) return false;

		return true;
	}
}