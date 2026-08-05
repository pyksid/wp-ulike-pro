<?php
/**
 * Admin Scripts Class.
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

        // Scripts is only can be load on ulike pages.
        if ( strpos( $hook, WP_ULIKE_SLUG ) === false ) {
            return;
        }

        // License/schema tools only — not used on the stats React app.
        if ( strpos( $hook, 'statistics' ) === false ) {
            wp_enqueue_script(
                'wp_ulike_pro_admin_scripts',
                WP_ULIKE_PRO_ADMIN_URL . '/assets/js/scripts.js',
                array(),
                WP_ULIKE_PRO_VERSION,
                true
            );

            wp_ulike_add_inline_script_data( 'wp_ulike_pro_admin_scripts', 'UlikeProAdminCommonConfig', array(
                'AjaxUrl' => admin_url( 'admin-ajax.php' ),
                'Nonce'   => wp_create_nonce( WP_ULIKE_PRO_DOMAIN )
            ) );
        }

        if ( false !== strpos( $hook, 'wp-ulike-pro-license' ) ) {
            wp_ulike_add_inline_script_data(
                'wp_ulike_pro_admin_scripts',
                'UlikeProLicenseConfig',
                array(
                    'nonce'   => wp_create_nonce( 'wp-ulike-pro-license' ),
                    'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                )
            );
        }

        if ( strpos( $hook, 'statistics' ) !== false ) {
            // svg-painter can throw if it runs after jQuery ready (common with deferred CDN JS).
            // It only recolors admin menu icons, which the stats shell replaces anyway.
            wp_dequeue_script( 'svg-painter' );

            wp_enqueue_style(
                'wp_ulike_pro_admin_react',
                WP_ULIKE_PRO_ADMIN_URL . '/includes/statistics/stats.css',
                array(),
                WP_ULIKE_PRO_VERSION
            );

            wp_enqueue_script(
                'wp_ulike_pro_admin_react',
                WP_ULIKE_PRO_ADMIN_URL . '/includes/statistics/stats.js',
                array(),
                WP_ULIKE_PRO_VERSION,
                true
            );

            wp_ulike_add_inline_script_data( 'wp_ulike_pro_admin_react', 'StatsAppConfig', array(
                'nonce'         => wp_create_nonce( WP_ULIKE_PRO_DOMAIN ),
                'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
                'worldSvg'      => WP_ULIKE_PRO_ADMIN_URL . '/includes/statistics/world.svg',
                'logo'          => WP_ULIKE_ASSETS_URL . '/img/icon.svg',
                'title'         => esc_html__( 'Metrics Dashboard', 'wp-ulike-pro' ),
                'buildType'     => 'pro',
                'loaderSvg'     => $this->get_loader_svg(),
                'siteUrl'       => wp_ulike_pro_get_license_site_url(),
                'licenseKey'    => WP_Ulike_Pro_License::get_license_key(),
                'licenseItemId' => WP_Ulike_Pro_API::PRODUCT_ID,
                'userPrefs'     => class_exists( 'WP_Ulike_Stats_User_Prefs' )
                    ? WP_Ulike_Stats_User_Prefs::get_app_config()
                    : array(),
                'migrationNotice' => $this->get_migration_notice_config(),
            ) );
        }

        // Enqueue third-party styles
        wp_enqueue_style(
            'wp-ulike-pro-admin-styles',
            WP_ULIKE_PRO_ADMIN_URL . '/assets/css/admin.css',
            array( 'wp-ulike-admin-plugins' ),
            WP_ULIKE_PRO_VERSION
        );

    }

    /**
     * Build the in-app migration nudge config, or null when not applicable.
     *
     * Pro statistics are correct in every storage mode, so this is a soft
     * performance/cleanup nudge shown inside the React app (a server-side
     * notice above #root is invisible because the SPA occupies the viewport).
     *
     * @return array<string,mixed>|null
     */
    private function get_migration_notice_config() {
        $url = function_exists( 'wp_ulike_pro_get_pulse_migration_url' )
            ? wp_ulike_pro_get_pulse_migration_url()
            : admin_url( 'admin.php?page=wp-ulike-pulse' );

        // After full Pulse cutover, nudge cleanup when legacy tables remain.
        if (
            class_exists( 'WP_Ulike_Pulse_Config' )
            && WP_Ulike_Pulse_Config::MODE_PULSE === WP_Ulike_Pulse_Config::mode()
            && class_exists( 'WP_Ulike_Pulse_Legacy_Cleanup' )
            && WP_Ulike_Pulse_Legacy_Cleanup::legacy_tables_exist()
            && ( ! method_exists( 'WP_Ulike_Pulse_Config', 'is_admin_dismissed' ) || ! WP_Ulike_Pulse_Config::is_admin_dismissed() )
        ) {
            return array(
                'id'       => 'pro_pulse_cleanup',
                'title'    => esc_html__( 'Free up disk space.', WP_ULIKE_PRO_DOMAIN ),
                'message'  => esc_html__( 'Like records already use the faster storage. Remove the old log tables when you are ready to reclaim disk space.', WP_ULIKE_PRO_DOMAIN ),
                'ctaLabel' => esc_html__( 'Review cleanup', WP_ULIKE_PRO_DOMAIN ),
                'ctaUrl'   => esc_url( $url ),
            );
        }

        if ( ! function_exists( 'wp_ulike_pro_legacy_votes_pending' ) || ! wp_ulike_pro_legacy_votes_pending() ) {
            return null;
        }

        return array(
            'id'       => 'pro_pulse_migration',
            'title'    => esc_html__( 'Faster statistics with Pulse storage.', WP_ULIKE_PRO_DOMAIN ),
            'message'  => esc_html__( 'Your statistics are already complete — WP ULike reads both legacy and Pulse data automatically. Migrating fully to Pulse storage makes charts faster and lets you clean up the old tables.', WP_ULIKE_PRO_DOMAIN ),
            'ctaLabel' => esc_html__( 'Upgrade like storage', WP_ULIKE_PRO_DOMAIN ),
            'ctaUrl'   => esc_url( $url ),
        );
    }

    /**
     * WP ULike logo SVG used by the stats panel loader (matches Optiwich).
     *
     * @return string
     */
    private function get_loader_svg() {
        return '<svg width="386" height="204" viewBox="0 0 386 204" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M368.646 20.3446C345.509 -2.76261 308.086 -2.76261 285.149 20.3446L261.609 43.8543L282.735 64.9524L305.671 41.8451C311.104 36.4198 318.347 33.2045 325.993 32.8027C334.242 32.6018 342.289 35.817 347.923 41.8451C358.989 53.6998 358.587 71.985 346.917 83.2376L331.827 98.3075C324.785 105.34 313.518 105.34 306.476 98.3075C292.794 84.644 228.008 19.9428 228.008 19.9428C216.943 8.89117 202.054 2.66214 186.159 2.66214C170.465 2.66214 155.577 8.89117 144.31 19.9428C139.682 24.5645 135.658 29.9898 132.842 36.0179L131.634 38.6299L155.979 62.9432L157.589 55.5087C161.009 39.6345 176.501 29.588 192.396 33.0036C198.03 34.2091 203.06 36.8216 207.084 40.8399L297.623 131.261L256.177 172.654L195.414 112.172C194.207 110.967 189.177 105.742 163.021 79.4196L100.851 17.3309C77.713 -5.77696 40.2899 -5.77696 17.3535 17.3309C-5.78451 40.4381 -5.78451 77.8122 17.3535 100.719L110.106 193.35C115.136 198.374 121.977 201.187 129.019 201.187C136.262 201.187 142.901 198.374 148.133 193.35L186.763 154.771L165.637 133.673L129.22 170.041L38.6804 79.6205C27.4131 67.9667 27.8155 49.4806 39.4852 38.228C50.7524 27.3773 68.6593 27.3773 79.926 38.228L141.292 99.5131C142.298 100.719 143.505 102.126 144.712 103.331L237.264 195.761C242.294 200.986 249.135 204 256.579 204C256.78 204 256.981 204 256.981 204C264.224 204 270.864 201.187 276.095 196.164L368.646 103.733C391.785 80.8266 391.785 43.2515 368.646 20.3446Z" fill="#ee5e60"/></svg>';
    }

}