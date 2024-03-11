<?php
/**
 * Front-End reCAPTCHA Enqueue Class.
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
 *  Class to load reCAPTCHA scripts
 */
class WP_Ulike_Pro_reCAPTCHA_Enqueue {

    /**
     * __construct
     */
    function __construct() {}

    /**
     * Scripts for admin
     *
     * @return void
     */
    public static function wp_enqueue_scripts() {
        $version  = wp_ulike_get_option( 'recaptcha_version' );
        $site_key = '';
        if( $version === 'v3' ) {
            $site_key = wp_ulike_get_option( 'v3_recaptcha_sitekey' );
            wp_register_script( 'ulp-google-recapthca-api', "https://www.google.com/recaptcha/api.js?render=$site_key" );
        } else {
            $language_code = wp_ulike_get_option( 'v2_recaptcha_language_code' );
            $site_key = wp_ulike_get_option( 'v2_recaptcha_sitekey' );

            wp_register_script( 'ulp-google-recapthca-api', "https://www.google.com/recaptcha/api.js?onload=ulpOnloadCallback&render=explicit&hl=$language_code" );
            wp_add_inline_script( 'ulp-google-recapthca-api', "var ulpOnloadCallback=function(){try{jQuery('.ulp-google-recaptcha').each(function(i){grecaptcha.render(jQuery(this).attr('id'),{'sitekey':jQuery(this).attr('data-sitekey'),'theme':jQuery(this).attr('data-theme')})})}catch(error){console.log(error)}};function ulp_recaptcha_refresh(){jQuery('.ulp-google-recaptcha').html('');grecaptcha.reset()}");
        }

        wp_enqueue_script( 'ulp-recaptcha', WP_ULIKE_PRO_PUBLIC_URL . '/assets/js/solo/recaptcha.js', array( 'jquery', 'ulp-google-recapthca-api' ), WP_ULIKE_PRO_VERSION, true );
        wp_localize_script( 'ulp-recaptcha', 'UlikeProRecaptchaData', array(
            'recaptchaVersion'   => $version,
            'recaptchaSiteKey'  => $site_key,
        ) );
    }

}