<?php
/**
 * All wp-ulike-pro functionalities starting from here...
 *
 *
 * @package    wp-ulike-pro
 * @author     TechnoWich 2026
 * @link       https://wpulike.com
 *
 * Plugin Name:       WP ULike Pro
 * Plugin URI:        https://wpulike.com/?utm_source=wp-plugins&utm_campaign=plugin-uri&utm_medium=wp-dash
 * Description:       WP ULike PRO boosts engagement with voting, user profiles, schema, and analytics—optimizing your site's performance effortlessly.
 * Version:           2.3.1
 * Author:            TechnoWich
 * Author URI:        https://technowich.com/?utm_source=wp-plugins&utm_campaign=author-uri&utm_medium=wp-dash
 * Requires PHP:      7.3.0
 * Requires at least: 6.0
 * Requires Plugins:  wp-ulike
 * Update URI:        https://wpulike.com
 * WP ULike tested up to: 5.0.6
 * Text Domain:       wp-ulike-pro
 * Domain Path:       /languages
 * Tested up to:      7.0
*/

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'WP_ULIKE_PRO_VERSION'                  , '2.3.1' );
define( 'WP_ULIKE_PRO_DB_VERSION'               , '2.0.0' );
define( 'WP_ULIKE_PRO_REQUIRED_CORE_VERSION'    , '5.2.1' );
define( 'WP_ULIKE_PRO_RECOMMENDED_CORE_VERSION' , '5.2.1' );
define( 'WP_ULIKE_PRO__FILE__'                  , __FILE__ );
define( 'WP_ULIKE_PRO_DOMAIN'                   , 'wp-ulike-pro' );
define( 'WP_ULIKE_FREE_BASENAME'                , 'wp-ulike/wp-ulike.php' );

define( 'WP_ULIKE_PRO_BASENAME'     , plugin_basename( WP_ULIKE_PRO__FILE__ ) );
define( 'WP_ULIKE_PRO_DIR'          , plugin_dir_path( WP_ULIKE_PRO__FILE__ ) );
define( 'WP_ULIKE_PRO_URL'          , plugin_dir_url( WP_ULIKE_PRO__FILE__ ) );
define( 'WP_ULIKE_PRO_NAME'         , 'WP ULike Pro' );

define( 'WP_ULIKE_PRO_ADMIN_DIR'    , WP_ULIKE_PRO_DIR . '/admin' );
define( 'WP_ULIKE_PRO_ADMIN_URL'    , WP_ULIKE_PRO_URL . 'admin' );

define( 'WP_ULIKE_PRO_INC_DIR'      , WP_ULIKE_PRO_DIR . '/includes' );
define( 'WP_ULIKE_PRO_INC_URL'      , WP_ULIKE_PRO_URL . 'includes' );

define( 'WP_ULIKE_PRO_PUBLIC_DIR'   , WP_ULIKE_PRO_DIR . '/public' );
define( 'WP_ULIKE_PRO_PUBLIC_URL'   , WP_ULIKE_PRO_URL . 'public' );

require WP_ULIKE_PRO_DIR . 'public/class-register-hook.php';

register_activation_hook( __FILE__, array( 'WP_Ulike_Pro_Register_Hook', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'WP_Ulike_Pro_Register_Hook', 'deactivate' ) );

add_action( 'plugins_loaded', 'wp_ulike_pro_load_plugin' );

/**
 * Load WP ULike Pro after the free plugin is ready.
 *
 * @return void
 */
function wp_ulike_pro_load_plugin() {
	if ( ! did_action( 'wp_ulike_loaded' ) ) {
		add_action( 'admin_notices', 'wp_ulike_pro_fail_load' );
		return;
	}

	if ( ! version_compare( WP_ULIKE_VERSION, WP_ULIKE_PRO_REQUIRED_CORE_VERSION, '>=' ) ) {
		add_action( 'admin_notices', 'wp_ulike_pro_fail_load_out_of_date' );
		return;
	}

	if ( ! version_compare( WP_ULIKE_VERSION, WP_ULIKE_PRO_RECOMMENDED_CORE_VERSION, '>=' ) ) {
		add_action( 'admin_notices', 'wp_ulike_pro_admin_notice_upgrade_recommendation' );
	}

	require WP_ULIKE_PRO_DIR . 'public/class-init.php';
}

function wp_ulike_pro_print_error( $message ) {
	if ( ! $message ) {
		return;
	}
	// PHPCS - $message should not be escaped
	echo '<div class="error">' . $message . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Show in WP Dashboard notice about the plugin is not activated.
 *
 * @return void
 */
function wp_ulike_pro_fail_load() {
	$screen = get_current_screen();
	if ( isset( $screen->parent_file ) && 'plugins.php' === $screen->parent_file && 'update' === $screen->id ) {
		return;
	}

	if ( _is_wp_ulike_installed() ) {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		$activation_url = wp_nonce_url(
			'plugins.php?action=activate&amp;plugin=' . WP_ULIKE_FREE_BASENAME . '&amp;plugin_status=all&amp;paged=1&amp;s',
			'activate-plugin_' . WP_ULIKE_FREE_BASENAME
		);

		$message  = '<h3>' . esc_html__( 'You\'re not using WP ULike Pro yet!', WP_ULIKE_PRO_DOMAIN ) . '</h3>';
		$message .= '<p>' . esc_html__( 'Activate the WP ULike plugin to start using all of WP ULike Pro plugin\'s features.', WP_ULIKE_PRO_DOMAIN ) . '</p>';
		$message .= '<p>' . sprintf( '<a href="%s" class="button-primary">%s</a>', $activation_url, esc_html__( 'Activate Now', WP_ULIKE_PRO_DOMAIN ) ) . '</p>';
	} else {
		if ( ! current_user_can( 'install_plugins' ) ) {
			return;
		}

		$install_url = wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=wp-ulike' ), 'install-plugin_wp-ulike' );

		$message  = '<h3>' . esc_html__( 'WP ULike Pro plugin requires installing the WP ULike plugin', WP_ULIKE_PRO_DOMAIN ) . '</h3>';
		$message .= '<p>' . esc_html__( 'Install and activate the WP ULike plugin to access all the Pro features.', WP_ULIKE_PRO_DOMAIN ) . '</p>';
		$message .= '<p>' . sprintf( '<a href="%s" class="button-primary">%s</a>', $install_url, esc_html__( 'Install Now', WP_ULIKE_PRO_DOMAIN ) ) . '</p>';
	}

	wp_ulike_pro_print_error( $message );
}

function wp_ulike_pro_fail_load_out_of_date() {
	if ( ! current_user_can( 'update_plugins' ) ) {
		return;
	}

	$upgrade_link = wp_nonce_url(
		self_admin_url( 'update.php?action=upgrade-plugin&plugin=' ) . WP_ULIKE_FREE_BASENAME,
		'upgrade-plugin_' . WP_ULIKE_FREE_BASENAME
	);
	$message = sprintf(
		/* translators: 1: Title opening tag, 2: Title closing tag */
		esc_html__( '%1$sWP ULike Pro requires newer version of the WP ULike plugin%2$s Update the WP ULike plugin to reactivate the WP ULike Pro plugin.', WP_ULIKE_PRO_DOMAIN ),
		'<h3>',
		'</h3>'
	);
	$message .= '<p>' . sprintf( '<a href="%s" class="button-primary">%s</a>', $upgrade_link, esc_html__( 'Update Now', WP_ULIKE_PRO_DOMAIN ) ) . '</p>';

	wp_ulike_pro_print_error( $message );
}

function wp_ulike_pro_admin_notice_upgrade_recommendation() {
	if ( ! current_user_can( 'update_plugins' ) ) {
		return;
	}

	$upgrade_link = wp_nonce_url(
		self_admin_url( 'update.php?action=upgrade-plugin&plugin=' ) . WP_ULIKE_FREE_BASENAME,
		'upgrade-plugin_' . WP_ULIKE_FREE_BASENAME
	);
	$message = sprintf(
		/* translators: 1: Title opening tag, 2: Title closing tag */
		esc_html__( '%1$sDon’t miss out on the new version of WP ULike%2$s Update to the latest version of WP ULike to enjoy new features, better performance and compatibility.', WP_ULIKE_PRO_DOMAIN ),
		'<h3>',
		'</h3>'
	);
	$message .= '<p>' . sprintf( '<a href="%s" class="button-primary">%s</a>', $upgrade_link, esc_html__( 'Update Now', WP_ULIKE_PRO_DOMAIN ) ) . '</p>';

	wp_ulike_pro_print_error( $message );
}

if ( ! function_exists( '_is_wp_ulike_installed' ) ) {
	/**
	 * @return bool
	 */
	function _is_wp_ulike_installed() {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return isset( get_plugins()[ WP_ULIKE_FREE_BASENAME ] );
	}
}

