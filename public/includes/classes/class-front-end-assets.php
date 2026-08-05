<?php
/**
 * Front-End Scripts Class.
 *
 *
 * @package    wp-ulike-pro
 * @author     TechnoWich 2026
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

        // Get view tracking enabled types
        $view_tracking_enabled = wp_ulike_get_option( 'view_tracking_enabled_types', array( 'post') );
        if ( empty( $view_tracking_enabled ) || ! is_array( $view_tracking_enabled ) ) {
            $view_tracking_enabled = array( 'post' );
        }

        $localize_args = array(
            'AjaxUrl' => add_query_arg( WP_Ulike_Pro::is_preview_mode() ? array( 'preview' => true ) : array(), admin_url( 'admin-ajax.php' ) ),
            'Nonce'   => wp_create_nonce( WP_ULIKE_PRO_DOMAIN ),
            'TabSide' => wp_ulike_get_option( 'user_profiles_appearance|tabs_side', 'top' ),
            'ViewTracking' => array(
                'enabledTypes' => $view_tracking_enabled
            ),
            'notifications' => wp_ulike_get_option( 'enable_toast_notice', true ),
            'ajax_error'    => wp_ulike_setting_repo::getAjaxErrorNotice(),
            'modalClose'    => esc_html__( 'Close dialog', WP_ULIKE_PRO_DOMAIN ),
        );

        if( ! WP_Ulike_Pro::is_preview_mode() ){
            // Add social share buttons script
            $social_items = wp_ulike_get_option( 'social_share', array() );
            if( ! empty( $social_items ) ){
                wp_enqueue_script( 'ulp-share-buttons', WP_ULIKE_PRO_PUBLIC_URL . '/assets/js/solo/share.min.js', array(), WP_ULIKE_PRO_VERSION, true );
            }

            // Avatar uploader scripts (Standalone vanilla JS version)
            // Styles are in uploader.scss and compiled to uploader.css
            if( WP_Ulike_Pro_Options::isLocalAvatars() ){
                // Register avatar uploader with main bundle as dependency
                // The main bundle (WP_ULIKE_PRO_DOMAIN) includes _modal.js
                wp_register_script(
                    'ulp-uploader',
                    WP_ULIKE_PRO_PUBLIC_URL . '/assets/js/solo/uploader.min.js',
                    array( WP_ULIKE_PRO_DOMAIN ), // Main bundle includes modal
                    WP_ULIKE_PRO_VERSION,
                    true
                );

                // Register style (compiled from uploader.scss)
                wp_register_style( 'ulp-uploader', WP_ULIKE_PRO_PUBLIC_URL . '/assets/css/uploader.min.css', array(), WP_ULIKE_PRO_VERSION );

                // Get upload directory URL (WP_ULIKE_SLUG from parent plugin)
                $upload_dir = wp_upload_dir();
                $upload_slug = defined( 'WP_ULIKE_SLUG' ) ? WP_ULIKE_SLUG : 'wp-ulike';
                $upload_url = trailingslashit( $upload_dir['baseurl'] ) . $upload_slug . '/avatars/';

                // Get formatted avatar config for JavaScript
                $avatar_config_js = WP_Ulike_Pro_Options::getAvatarConfigForJs();

                // Pass config as inline JSON.
                wp_ulike_add_inline_script_data( 'ulp-uploader', 'fileUploaderCommonConfig', array(
                    'AjaxUrl' => add_query_arg( WP_Ulike_Pro::is_preview_mode() ? array( 'preview' => true ) : array(), admin_url( 'admin-ajax.php' ) ),
                    'Nonce' => wp_create_nonce( WP_ULIKE_PRO_DOMAIN ),
                    'uploadUrl' => trailingslashit( $upload_url ),
                    'avatarConfig' => $avatar_config_js
                ) );

                // Enqueue only on profile pages
                if ( function_exists( 'wp_ulike_pro_is_profile_page' ) && wp_ulike_pro_is_profile_page() ) {
                    wp_enqueue_script( 'ulp-uploader' );
                    wp_enqueue_style( 'ulp-uploader' );
                }
            }
        }

        wp_enqueue_style( WP_ULIKE_PRO_DOMAIN, WP_ULIKE_PRO_PUBLIC_URL . '/assets/css/wp-ulike-pro.min.css', array( WP_ULIKE_SLUG ), WP_ULIKE_PRO_VERSION );

        //Add wp_ulike script file with special functions.
        wp_ulike_enqueue_script_with_defer( WP_ULIKE_PRO_DOMAIN, WP_ULIKE_PRO_PUBLIC_URL . '/assets/js/wp-ulike-pro.min.js', array(), WP_ULIKE_PRO_VERSION );


        wp_ulike_add_inline_script_data( WP_ULIKE_PRO_DOMAIN, 'UlikeProCommonConfig', apply_filters( 'wp_ulike_pro_front_end_localize', $localize_args ) );
    }

}