<?php

/**
 * Install default core pages
 *
 */

class WP_Ulike_Pro_Core_Pages {

	protected static $core_pages;

	public static function install() {

		self::$core_pages = array(
			'user-profile' => array(
				'title'       => esc_html__( 'User Profile', WP_ULIKE_PRO_DOMAIN ),
				'content'     => '[wp_ulike_pro_completeness_profile]',
				'option_name' => 'user_profiles_core_page'
			),
			'login' => array(
				'title'       => esc_html__( 'Login', WP_ULIKE_PRO_DOMAIN ),
				'content'     => '[wp_ulike_pro_login_form]',
				'option_name' => 'login_core_page'
			),
			'signup' => array(
				'title'       => esc_html__( 'Sign Up', WP_ULIKE_PRO_DOMAIN ),
				'content'     => '[wp_ulike_pro_signup_form]',
				'option_name' => 'signup_core_page'
			),
			'password-reset' => array(
				'title'       => esc_html__( 'Password Reset', WP_ULIKE_PRO_DOMAIN ),
				'content'     => '[wp_ulike_pro_reset_password_form]',
				'option_name' => 'reset_password_core_page'
			),
			'edit-account' => array(
				'title'       => esc_html__( 'Edit Account', WP_ULIKE_PRO_DOMAIN ),
				'content'     => '[wp_ulike_pro_account_form]',
				'option_name' => 'edit_account_core_page'
			)
		);

		return self::install_default_pages();
	}

	/**
	 * Get posts with specific meta key/value
	 *
	 * @param $post_type
	 * @param $key
	 * @param $value
	 *
	 * @return bool
	 */
	public static function find_post_id( $post_type, $key, $value ) {
		$posts = get_posts( array( 'post_type' => $post_type, 'meta_key' => $key, 'meta_value' => $value ) );
		if ( isset( $posts[0] ) && ! empty( $posts ) ){
			return $posts[0]->ID;
		}

		return false;
	}

	/**
	 * Install Pre-defined pages with shortcodes
	 *
	 * @return void
	 */
	public static function install_default_pages() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		//Install Core Pages
		$core_pages = array();
		foreach ( self::$core_pages as $slug => $args ) {

			$page_exists = self::find_post_id( 'page', '_wp_ulike_pro_core', $slug );
			if ( $page_exists ) {
				continue;
			}

			$post_arr = array(
				'post_title'     => $args['title'],
				'post_content'   => $args['content'],
				'post_name'      => $slug,
				'post_type'      => 'page',
				'post_status'    => 'publish',
				'post_author'    => get_current_user_id(),
				'comment_status' => 'closed'
			);

			$post_id = wp_insert_post( $post_arr );

			if( ! is_wp_error( $post_id ) ){
				update_post_meta( $post_id, '_wp_ulike_pro_core', $slug );
				$core_pages[ $slug ] =  array(
					'id'  => $post_id,
					'key' => $args['option_name']
				);
			}

		}

		if( ! empty( $core_pages ) ){
			$options =  get_option( 'wp_ulike_settings', array() );

			foreach ( $core_pages as $slug => $args ) {
				$options[ $args['key'] ] = $args['id'];
			}

			update_option( 'wp_ulike_settings', $options );
		}

		return true;
	}

}