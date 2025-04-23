<?php

final class WP_Ulike_Pro_Avatar_Controller extends wp_ulike_ajax_listener_base {

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
		$this->data['security'] = isset( $_POST['security'] ) ? $_POST['security'] : NULL;
		$this->data['name']     = isset( $_POST['name'] ) ?  $_POST['name'] : NULL;
		$this->data['method']   = isset( $_POST['method'] ) ?  $_POST['method'] : NULL;

		// Delete Args
		$this->data['file'] = isset( $_POST['file'] ) ?  $_POST['file'] : NULL;
		$this->data['remove_meta'] = isset( $_POST['removeMeta'] ) ?  $_POST['removeMeta'] : false;

		$configurations = WP_Ulike_Pro_Options::getAvatarConfigs();

		// Configs
		$this->data['configs']  = [
			'limit'       => 1,
			'fileMaxSize' => $configurations['maxSize'],
			'extensions'  => ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'],
			'title'       => 'auto',
			'uploadDir'   => WP_ULIKE_CUSTOM_DIR . '/avatars/',
			'replace'     => false,
			'editor'      => [
				'maxWidth'  => $configurations['maxWidth'],
				'maxHeight' => $configurations['maxWidth'],
				'crop'      => false,
				'quality'   => $configurations['quality']
			]
		];
	}

	/**
	 * Process request
	 *
	 * @return void
	 */
	public function process(){
		// Output args
        $data = array(
            "hasWarnings" => false,
            "isSuccess"   => false,
            "warnings"    => array(),
            "files"       => array()
        );

		try {
			$this->beforeAction();

			$this->validates();

			if ( in_array( $this->data['method'], array( 'upload','edit' ) ) ) {

				// make sure upload dir exists
				if ( ! is_dir( $this->data['configs']['uploadDir'] ) ) {
					wp_mkdir_p( $this->data['configs']['uploadDir'] );
				}

				// Set name
				if( ! empty( $this->data['name'] ) && $this->data['method'] == 'edit' ){
					$name = str_replace (array('/', '\\'), '', $this->data['name'] );

					if ( is_file( $this->data['configs']['uploadDir'] . $name ) ) {
						$this->data['configs']['title']   = $name;
						$this->data['configs']['replace'] = true;
					}
				}

				// initialize FileUploader
				$file = new WP_Ulike_Pro_File_Uploader( 'files', $this->data['configs'] );
				// call to upload the files
				$data = $file->upload();

				// change file's public data
				if ( ! empty( $data['files'] ) ) {
					$item = $data['files'][0];

					$uploads   = wp_get_upload_dir();
					$image_url = $uploads['baseurl'] . '/' . WP_ULIKE_SLUG . '/avatars/' . $item['name'];

					// Set image url to file
					$item['file'] = $image_url;

					// Update avatar meta
					update_user_meta( $this->user, 'ulp_avatar_data', $item );

					// Update avatar url
					update_user_meta( $this->user, 'ulp_avatar', array(
						'url' => $image_url
					) );

					$data['files'][0] = array(
						'title' => $item['title'],
						'name' => $item['name'],
						'size' => $item['size'],
						'size2' => $item['size2']
					);
				}
			} elseif( $this->data['method'] === 'delete' && ! empty( $this->data['file'] ) ) {
				$file = $this->data['configs']['uploadDir'] . str_replace( array('/', '\\'), '', $this->data['file'] );

				if( is_file( $file ) ){
					// Remove file
					unlink( $file );
					// Delete meta
					if( $this->data['remove_meta'] ){
						delete_user_meta( $this->user, 'ulp_avatar_data' );
						delete_user_meta( $this->user, 'ulp_avatar' );
					}
					// Change message
					$data['isSuccess'] = true;
				}
			}

            $this->afterAction();

		} catch ( \Exception $e ){
			$data['hasWarnings'] = true;
			$data['warnings'] = array(
				$e->getMessage()
			);
		}

		wp_send_json( $data );
	}

	/**
	* Before Action
	* Provides hook for performing actions before a process
	*/
	private function beforeAction(){
		do_action_ref_array('wp_ulike_pro_before_avatar_process', array( &$this ) );
	}

	/**
	* After Action
	* Provides hook for performing actions after a process
	*/
	private function afterAction(){
		do_action_ref_array( 'wp_ulike_pro_after_avatar_process', array( &$this ) );
    }

	/**
	* Validate the Favorite
	*/
	private function validates(){
		// Return false when nonce invalid
		if( ! wp_verify_nonce( $this->data['security'], WP_ULIKE_PRO_DOMAIN ) || ! $this->user ){
            throw new \Exception( WP_Ulike_Pro_Options::getNoticeMessage( 'permission_denied', esc_html__( 'Something went wrong. Please try again or contact the admin.', WP_ULIKE_PRO_DOMAIN ) ) );
        }
		// Check methods
		if( empty( $this->data['method'] ) || ! in_array( $this->data['method'], array( 'upload','edit','delete' ) ) ){
			throw new \Exception( esc_html__( 'Method Not found!', WP_ULIKE_PRO_DOMAIN ) );
		}
	}
}