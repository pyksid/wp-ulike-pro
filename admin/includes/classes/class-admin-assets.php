<?php
/**
 * Admin Scripts Class.
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
 *  Class to load and print panel scripts
 */
class WP_Ulike_Pro_Admin_Assets {

    /**
     * __construct
     */
    function __construct() {
        // general assets
        add_action( 'admin_enqueue_scripts', array( $this, 'load_scripts' ) );
    }
    /**
     * Scripts for admin
     *
     * @return void
     */
    public function load_scripts( $hook ) {

        // Add local avatars uploader in profile page
        if( WP_Ulike_Pro_Options::isLocalAvatars()  && $hook == 'profile.php' ){
            wp_enqueue_script( 'ulp-uploader',
                WP_ULIKE_PRO_PUBLIC_URL . '/assets/js/solo/uploader.min.js',
                array( 'jquery' ),
                WP_ULIKE_PRO_VERSION,
                true
            );
            wp_enqueue_style(
                'ulp-uploader',
                WP_ULIKE_PRO_PUBLIC_URL . '/assets/css/uploader.min.css',
                array(),
                WP_ULIKE_PRO_VERSION
            );
            // Get lang code
            $language = get_locale();
            if ( strlen( $language ) > 0 ) {
                $language = explode( '_', $language )[0];
            }
            //localize script
            wp_localize_script( 'ulp-uploader', 'fileUploaderCommonConfig', array(
                'AjaxUrl' => admin_url( 'admin-ajax.php' ),
                'Nonce'   => wp_create_nonce( WP_ULIKE_PRO_DOMAIN ),
                'avatar'  => WP_Ulike_Pro_Options::getAvatarConfigs( array(
                    'url'      => array(),
                    'icons'    => array(
                        'menu'     => 'dashicons dashicons-admin-generic',
                        'upload'   => 'dashicons dashicons-cloud-upload',
                        'edit'     => 'dashicons dashicons-image-crop',
                        'remove'   => 'dashicons dashicons-trash',
                        'complete' => 'dashicons dashicons-yes',
                        'retry'    => 'dashicons dashicons-no',
                    )
                ) ),
                'Locale'  => $language
            ) );
        }

        // Scripts is only can be load on ulike pages.
        if ( strpos( $hook, WP_ULIKE_SLUG ) === false ) {
            return;
        }

        // Enqueue third-party styles
        wp_enqueue_style(
            'wp-ulike-pro-admin-plugins',
            WP_ULIKE_PRO_ADMIN_URL . '/assets/css/plugins.css',
            array(),
            WP_ULIKE_PRO_VERSION
        );

        // Enqueue third-party styles
        wp_enqueue_style(
            'wp-ulike-pro-admin-styles',
            WP_ULIKE_PRO_ADMIN_URL . '/assets/css/admin.css',
            array(),
            WP_ULIKE_PRO_VERSION
        );

        // Enqueue admin scripts
        wp_enqueue_script(
            'wp_ulike_pro_admin_plugins',
            WP_ULIKE_PRO_ADMIN_URL . '/assets/js/plugins.js',
            array( 'wp_ulike_admin_plugins' ),
            WP_ULIKE_PRO_VERSION,
            true
        );

        // Enqueue admin scripts
        wp_enqueue_script(
            'wp_ulike_pro_admin_scripts',
            WP_ULIKE_PRO_ADMIN_URL . '/assets/js/scripts.js',
            array( 'wp_ulike_pro_admin_plugins' ),
            WP_ULIKE_PRO_VERSION,
            true
        );

        //localize script
        wp_localize_script( 'wp_ulike_pro_admin_scripts', 'UlikeProAdminCommonConfig', array(
            'AjaxUrl' => admin_url( 'admin-ajax.php' ),
            'Nonce'   => wp_create_nonce( WP_ULIKE_PRO_DOMAIN )
        ) );

        // HelpScout Support Service
        if ( strpos( $hook, WP_ULIKE_SLUG . '-statistics' ) === false ) {
            wp_add_inline_script( 'wp_ulike_pro_admin_scripts', '!function(e,t,n){function a(){var e=t.getElementsByTagName("script")[0],n=t.createElement("script");n.type="text/javascript",n.async=!0,n.src="https://beacon-v2.helpscout.net",e.parentNode.insertBefore(n,e)}if(e.Beacon=n=function(t,n,a){e.Beacon.readyQueue.push({method:t,options:n,data:a})},n.readyQueue=[],"complete"===t.readyState)return a();e.attachEvent?e.attachEvent("onload",a):e.addEventListener("load",a,!1)}(window,document,window.Beacon||function(){});window.Beacon(\'init\', \'5d31578b-4133-429b-8ae3-70ff8fd243b8\')' );
        }

    }

}