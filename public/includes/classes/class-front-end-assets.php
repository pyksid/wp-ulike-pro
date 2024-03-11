<?php
/**
 * Front-End Scripts Class.
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

/**
 *  Class to load and print front-end scripts
 */
class WP_Ulike_Pro_Front_End_Assets {

    /**
     * __construct
     */
    function __construct() {
        // general assets
        add_action( 'wp_enqueue_scripts', array( $this, 'load_scripts' ) );
    }
    /**
     * Scripts for admin
     *
     * @return void
     */
    public function load_scripts( $hook ) {
        // If user has been disabled this page in options, then return.
        if( ! is_wp_ulike( wp_ulike_get_option( 'disable_plugin_files' ), array(), true ) ) {
            return;
        }

        if( WP_Ulike_Pro_Options::isGlobalRecaptchaEnabled() ){
            WP_Ulike_Pro_reCAPTCHA_Enqueue::wp_enqueue_scripts();
        }

        $localize_args = array(
            'AjaxUrl' => add_query_arg( WP_Ulike_Pro::is_preview_mode() ? array( 'preview' => true ) : array(), admin_url( 'admin-ajax.php' ) ),
            'Nonce'   => wp_create_nonce( WP_ULIKE_PRO_DOMAIN ),
            'TabSide' => wp_ulike_get_option( 'user_profiles_appearance|tabs_side', 'top' )
        );
        $script_dependencies = array( 'jquery' );

        if( ! WP_Ulike_Pro::is_preview_mode() ){
            // Add social share buttons script
            $social_items = wp_ulike_get_option( 'social_share', array() );
            if( ! empty( $social_items ) ){
                wp_enqueue_script( 'ulp-share-buttons', WP_ULIKE_PRO_PUBLIC_URL . '/assets/js/solo/share.min.js', array(), WP_ULIKE_PRO_VERSION, true );
            }

            // Avatar uploader scripts
            if( WP_Ulike_Pro_Options::isLocalAvatars() ){
                wp_register_script( 'ulp-uploader', WP_ULIKE_PRO_PUBLIC_URL . '/assets/js/solo/uploader.min.js', array( 'jquery' ), WP_ULIKE_PRO_VERSION, true );
                wp_register_style( 'ulp-uploader', WP_ULIKE_PRO_PUBLIC_URL . '/assets/css/uploader.min.css', array(), WP_ULIKE_PRO_VERSION );
                // Get lang code
                $language = get_locale();
                if ( strlen( $language ) > 0 ) {
                    $language = explode( '_', $language )[0];
                }
                // Set localize args
                $localize_args['avatar'] = WP_Ulike_Pro_Options::getAvatarConfigs();
                $localize_args['Locale'] = $language;
                //localize script
                wp_localize_script( 'ulp-uploader', 'fileUploaderCommonConfig', $localize_args );
                // Unset custom args
                unset( $localize_args['avatar'] );
                unset( $localize_args['Locale'] );
            }
        }

        wp_enqueue_style( WP_ULIKE_PRO_DOMAIN, WP_ULIKE_PRO_PUBLIC_URL . '/assets/css/wp-ulike-pro.min.css', array( WP_ULIKE_SLUG ), WP_ULIKE_PRO_VERSION );

        //Add wp_ulike script file with special functions.
        wp_enqueue_script( WP_ULIKE_PRO_DOMAIN, WP_ULIKE_PRO_PUBLIC_URL . '/assets/js/wp-ulike-pro.min.js', $script_dependencies, WP_ULIKE_PRO_VERSION, true );


        //localize script
        wp_localize_script( WP_ULIKE_PRO_DOMAIN, 'UlikeProCommonConfig', $localize_args );
    }

}