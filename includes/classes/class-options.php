<?php
/**
 * Options manager
 *
 * 
 * @package    wp-ulike-pro
 * @author     TechnoWich 2024
 * @link       https://wpulike.com
*/

// no direct access allowed
if ( ! defined('ABSPATH') ) {
    die();
}

class WP_Ulike_Pro_Options extends wp_ulike_setting_repo {

	/**
	 * Check email html support
	 *
	 * @return boolean
	 */
	public static function supportHtmlEmail(){
		return self::getOption( 'enable_html_email', true );
    }

	/**
	 * Get email template and subject
	 *
	 * @param string $template_name
	 * @param string $key
	 * @return array
	 */
	public static function getEmailTemplate( $template_name, $key = 'body' ){
        $option = self::getOption( str_replace( '-', '_', $template_name ) . '_email', '' );
		return ! empty( $option[ $key ] ) ? $option[ $key ] : '';
    }

	/**
	 * Get admin email
	 *
	 * @return string
	 */
	public static function getAdminEmail(){
		return self::getOption( 'admin_email', get_bloginfo('admin_email') );
	}

	/**
	 * Get appears from name
	 *
	 * @return string
	 */
	public static function getAppearsFrom(){
		return self::getOption( 'appears_from', get_bloginfo( 'name' ) );
    }

	/**
	 * Get appears from email address
	 *
	 * @return string
	 */
	public static function getAppearsEmail(){
		return self::getOption( 'appears_email', get_bloginfo('admin_email') );
    }

	/**
	 * Check profile visibility
	 *
	 * @return boolean
	 */
	public static function isProfileVisible(){
		return self::getOption( 'enable_user_profiles', false );
	}

	/**
	 * Get profile page id
	 *
	 * @return integer
	 */
	public static function getProfilePage(){
		return self::getOption( 'user_profiles_core_page', '' );
	}

	/**
	 * Get profile page url
	 *
	 * @return string
	 */
	public static function getProfilePageUrl(){
		return get_permalink( self::getProfilePage() );
    }

	/**
	 * Check profile access level
	 *
	 * @return string
	 */
	public static function checkProfilesAccess(){
		return self::getOption( 'user_profiles_access', 'everyone' );
	}

	/**
	 * Get redirect URL when the profile has been restricted
	 *
	 * @return string
	 */
	public static function getCustomRedirect(){
		return self::getOption( 'user_custom_redirect', home_url() );
    }

	/**
	 * Show only for profile owner.
	 *
	 * @return boolean
	 */
	public static function restrictProfileOwner(){
		return self::getOption( 'user_restrict_profile_owner', false );
    }

	/**
	 * Get exclusive user roles
	 *
	 * @return array
	 */
    public static function getExclusiveRoles(){
		return self::getOption( 'user_restrict_exclusive_roles', array() );
    }

	/**
	 * Check author page redirect activation
	 *
	 * @return boolean
	 */
	public static function authorRedirect(){
		return self::getOption( 'enable_author_redirect', false );
    }

	/**
	 * Get profile permalink base structure
	 *
	 * @return boolean
	 */
	public static function getProfilePermalinkBase(){
		return self::getOption( 'user_profiles_permalink_base', 'user_login' );
    }

	/**
	 * Get login page ID
	 *
	 * @return string
	 */
	public static function getLoginPage(){
		return self::getOption( 'login_core_page', '' );
    }

	/**
	 * Get login page URL
	 *
	 * @return string
	 */
	public static function getLoginPageUrl(){
		return get_permalink( self::getLoginPage() );
    }

	/**
	 * Get login success redirect URL
	 *
	 * @return string
	 */
	public static function getLoginRedirectUrl(){
		return self::getOption( 'login_custom_redirect', '' );
	}

	/**
	 * Get login success redirect URL
	 *
	 * @return string
	 */
	public static function getLogoutRedirectUrl(){
		return self::getOption( 'logout_custom_redirect', '' );
	}

	/**
	 * Get signup page URL
	 *
	 * @return string
	 */
	public static function getSignUpPage(){
		return self::getOption( 'signup_core_page', '' );
	}

	/**
	 * Get signup page ID
	 *
	 * @return string
	 */
	public static function getSignUpPageUrl(){
		return get_permalink( self::getSignUpPage() );
	}

	/**
	 * Get signup success redirect URL
	 *
	 * @return string
	 */
	public static function getSignUpRedirectUrl(){
		return self::getOption( 'signup_custom_redirect', '' );
    }

	/**
	 * Get reset password page ID
	 *
	 * @return string
	 */
	public static function getResetPasswordPage(){
		return self::getOption( 'reset_password_core_page', '' );
    }

	/**
	 * Get reset password page URL
	 *
	 * @return string
	 */
	public static function getResetPasswordPageUrl(){
		// get reset password page
		$reset_password_page = self::getResetPasswordPage();
		$reset_password_url  = ! empty( $reset_password_page ) ? get_permalink( $reset_password_page  ) : false;

		return ! empty( $reset_password_url ) ? $reset_password_url : add_query_arg( array(
			'action' => 'lostpassword'
		), WP_Ulike_Pro_Permalinks::get_login_url() );
    }

	/**
	 * Get edit account page ID
	 *
	 * @return string
	 */
	public static function getEditAccountPage(){
		return self::getOption( 'edit_account_core_page', '' );
	}

	/**
	 * Get reset password page URL
	 *
	 * @return string
	 */
	public static function getCorePages( $selected_core_pages = array() ){
		$pages = array(
			'profile'        => self::getProfilePage(),
			'login'          => self::getLoginPage(),
			'signup'         => self::getSignUpPage(),
			'reset_password' => self::getResetPasswordPage(),
			'edit_account'   => self::getEditAccountPage()
		);

		// filter specific pages
		if( ! empty( $selected_core_pages ) ){
			$pages = array_intersect_key( $pages, array_flip( $selected_core_pages ) );
		}

		return apply_filters( 'wp_ulike_pro_core_pages', array_values( $pages ) );
    }

	/**
	 * Get reset password page URL
	 *
	 * @return string
	 */
	public static function isCorePage( $page_id = '', $type = array() ){
		if( empty( $page_id ) ){
			$page_id = get_queried_object_id();
		}
		return is_page( $page_id ) && in_array( $page_id, self::getCorePages( $type ) );
    }

	/**
	 * Display message when user logged in.
	 *
	 * @return string
	 */
	public static function getLoggedInMessage(){
        $current_user = wp_get_current_user();
        $messsage = self::getOption( 'logged_in_message', '' );

        if( empty( $messsage ) ){
            $messsage =  '<div class="ulp-avatar"><img src="{avatar_url}"></div> <span>Logged in as {display_name}. (<a href="{profile_url}">Profile</a>) (<a href="{logout_url}">Logout</a>)</span>';
        }

		$messsage = wp_ulike_kses( $messsage );

		$tags = new WP_Ulike_Pro_Convert_Tags( array( 'user_id' => $current_user->ID ) );

        return sprintf( '<div class="ulp-flex ulp-flex-center-xs"><div class="ulp-logged-in-message">%s</div></div>', $tags->convert( $messsage ) );
	}


	/**
	 * Get percentage values
	 *
	 * @return array
	 */
	public static function maybePercentageValue( $typeName, $data ){
		$is_percentage = self::getOption( self::getSettingKey( $typeName ) . '|enable_percentage_values', false );

		if( ! $is_percentage || ! is_array( $data ) ){
			return $data;
		}

		// Create base sum value
		$base = (int) $data['up'] + (int) $data['down'];
		// Fix zero division issue
		if( ! $base ){
			$base = 1;
		}
		// Calculate + update values
		$data['up']   = intval( round( ( (int) $data['up'] * 100 ) / $base ) ). '%';
		$data['down'] = intval( round( ( (int) $data['down'] * 100 ) / $base ) ) . '%';
		$data['sub']  = 0;

		// Check zero visible
		if( self::isCounterZeroHidden( $typeName ) ){
			$data['up']   = empty( $data['up'] ) || $data['up'] == '0%' ? '' : $data['up'];
			$data['down'] = empty( $data['down'] ) || $data['down'] == '0%' ? '' : $data['down'];
		}

        return $data;
	}

	/**
	 * Check postmeta option is enabled
	 *
	 * @param string $typeName
	 * @return boolean
	 */
	public static function isPostMetaEnabled( $typeName ){
		return self::getOption( self::getSettingKey( $typeName ) . '|enable_metadata', true );
	}

	/**
	 * Update meta data table
	 *
	 * @param string $typeName
	 * @param integer $id
	 * @param array $data
	 * @return void
	 */
	public static function maybeUpdateMetaData( $typeName, $id, $data ){
		if( ! self::isPostMetaEnabled( $typeName ) ){
			return;
		}

		foreach ( $data as $meta_key => $meta_value ) {
			update_metadata( $typeName, $id, $meta_key, $meta_value );
		}
	}

	/**
	 * Get require login template
	 *
	 * @return boolean
	 */
	public static function getRequireModalTemplate( $typeName ){
		$template = self::getOption( self::getSettingKey( $typeName ) . '|modal_template', '' );
		return do_shortcode( wp_ulike_kses( $template ) );
	}

	/**
	 * Get  template
	 *
	 * @return boolean
	 */
	public static function getLikersModalTemplate( $typeName ){
		$template = self::getOption( self::getSettingKey( $typeName ) . '|likers_modal_template', '' );
		return do_shortcode( wp_ulike_kses( $template ) );
	}
	/**
	 * Get  template
	 *
	 * @return boolean
	 */
	public static function getLikersModalTitle( $typeName ){
		return self::getOption( self::getSettingKey( $typeName ) . '|likers_modal_title', esc_html__('Likers', WP_ULIKE_PRO_DOMAIN) );
	}

	/**
	 * Get notice message text
	 *
	 * @param string $name
	 * @param string $default
	 * @return string
	 */
	public static function getNoticeMessage( $name, $default = '' ){
		return self::getOption( $name . '_notice' , $default );
	}

	/**
	 * Get notice message text
	 *
	 * @param string $name
	 * @param string $default
	 * @return string
	 */
	public static function getFormLabel( $prefix, $name, $default = '' ){
		return self::getOption( $prefix . '_' . $name, $default );
	}

	/**
	 * Check auto login after registration
	 *
	 * @return boolean
	 */
	public static function checkAutoLogin(){
		return self::getOption( 'signup_enable_auto_login', false );
    }

	/**
	 * Check enable local avatars
	 *
	 * @return boolean
	 */
	public static function isLocalAvatars(){
		return self::getOption( 'enable_local_avatars', false );
    }

	/**
	 * Check recaptcha status
	 *
	 * @return boolean
	 */
	public static function isRecaptchaEnabled(){
		return self::getOption( 'enable_recaptcha', false  );
    }

	/**
	 * Check recaptcha status
	 *
	 * @return boolean
	 */
	public static function isGlobalRecaptchaEnabled(){
		return self::getOption( 'global_recaptcha', false  );
    }

	/**
	 * Check 2-factor status
	 *
	 * @return boolean
	 */
	public static function is2FactorAuthEnabled(){
		return self::getOption( 'enable_2fa', false  );
    }

	/**
	 * Check default login redirect enabled
	 *
	 * @return boolean
	 */
	public static function isWpLoginRedirectEnabled(){
		return self::getOption( 'enable_wp_login_redirect', false  );
    }

	/**
	 * Get custom avatar configs
	 *
	 * @return boolean
	 */
	public static function getAvatarConfigs( $args = array() ){
		//Main data
		$defaults = array(
			'maxSize'  => self::getOption( 'max_avatar_size', 2 ),
			'maxWidth' => self::getOption( 'max_avatar_width', 512 ),
			'quality'  => self::getOption( 'image_quality', 60 ),
			'url'      => array(
				'logout' => WP_Ulike_Pro_Permalinks::get_logout_url()
			),
			'captions' => array(
				'upload' => self::getOption( 'avatar_upload_text', esc_html__( 'Upload', WP_ULIKE_PRO_DOMAIN ) ),
				'edit'   => self::getOption( 'avatar_edit_text', esc_html__( 'Edit', WP_ULIKE_PRO_DOMAIN ) ),
				'remove' => self::getOption( 'avatar_delete_text', esc_html__( 'Delete', WP_ULIKE_PRO_DOMAIN ) ),
				'logout' => self::getOption( 'avatar_logout_text', esc_html__( 'Log Out', WP_ULIKE_PRO_DOMAIN ) )
			),
			'icons'    => array(
				'menu'     => 'ulp-icon-settings',
				'upload'   => 'ulp-icon-upload',
				'edit'     => 'ulp-icon-crop',
				'remove'   => 'ulp-icon-trash',
				'complete' => 'ulp-icon-accept',
				'retry'    => 'ulp-icon-cancel',
				'logout'   => 'ulp-icon-logout'
			)
		);

		return apply_filters( 'wp_ulike_pro_avatar_configs', wp_parse_args( $args, $defaults ) );
    }

	/**
	 * Get availabe social login providers data
	 *
	 * @return array
	 */
	public static function getAvailabeSocialLogins(){
		$availabe_providers = array();

		if( ! self::getOption( 'enable_social_login', false  ) ){
			return $availabe_providers;
		}

		$social_logins = self::getOption( 'social_logins', array()  );

		if( empty( $social_logins ) ){
			return $availabe_providers;
		}

		foreach ( $social_logins as $key => $value ) {
			// if key and secret was empty
			if( empty( $value['network'])  || empty( $value['key'] ) || empty( $value['secret'] ) || $value['disable'] ){
				continue;
			}

			$availabe_providers[$key] = $value;
		}

		return $availabe_providers;
	}

	/**
	 * Check attachment display filter
	 *
	 * @return boolean
	 */
	public static function isAttachmentVisible( $attachment_id, $size, $attr ){
		// If option not enabled
		if( ! self::getOption( self::getSettingKey( 'post' ) . '|enable_attachments', false ) ){
			return false;
		}

        $filter_ids   = self::getOption( self::getSettingKey( 'post' ) . '|filter_attachment_ids', array() );
        $filter_size  = self::getOption( self::getSettingKey( 'post' ) . '|filter_attachment_size', array() );
        $filter_class = self::getOption( self::getSettingKey( 'post' ) . '|filter_attachment_class', array() );

		// If has no filter return true
        if( empty( $filter_ids ) && empty( $filter_size ) && empty( $filter_class ) ){
            return true;
        } else {
            // Filter by ids
            if( ! empty( $filter_ids ) && in_array( $attachment_id, $filter_ids ) ){
            	return true;
            }
            // Filter by class name
            if( ! empty( $attr['class'] ) && ! empty( $filter_class ) ){
                foreach ($filter_class as $class) {
                    if ( ! empty( $class['name'] ) && strpos( $attr['class'], $class['name'] ) !== FALSE ) {
                        return true;
                    }
                }
            }
            // filter by attachment size
			if( ! empty( $filter_size ) && in_array( $size, array_column( $filter_size, 'name' ) ) ){
				return true;
			}
        }

		return false;
	}

	/**
	 * Check email verify status
	 *
	 * @return boolean
	 */
	public static function isEmailVerifyEnabled(){
		return self::getOption( 'signup_status', 'approved' ) == 'checkmail';
	}

}