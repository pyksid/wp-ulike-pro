<?php

// no direct access allowed
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Class WP_Ulike_Pro_Update_Prepare
 *
 * Handles plugin update preparation and version checking.
 *
 * @package WP_Ulike_Pro
 */
class WP_Ulike_Pro_Update_Prepare {

	/**
	 * The plugin current version
	 *
	 * @var string
	 */
	public $current_version;

	/**
	 * Plugin Slug (plugin_directory/plugin_file.php)
	 *
	 * @var string
	 */
	public $plugin_slug;

	/**
	 * Plugin name (plugin_file)
	 *
	 * @var string
	 */
	public $plugin_name;

	/**
	 * Private transient key
	 *
	 * @var string
	 */
	private $response_transient_key;

	/**
	 * Constructor
	 */
	public function __construct() {
		// Set plugin current version
		$this->current_version = WP_ULIKE_PRO_VERSION;
		// Set the Plugin Slug
		$this->plugin_slug = basename( WP_ULIKE_PRO__FILE__, '.php' );
		$this->plugin_name = WP_ULIKE_PRO_BASENAME;
		// Set our global transient key
		$this->response_transient_key = md5( sanitize_key( $this->plugin_name ) . 'response_transient' );

		$this->setup_hooks();
		$this->maybe_delete_transients();
	}

	/**
	 * Setup WordPress hooks
	 *
	 * @return void
	 */
	private function setup_hooks() {
		// Define the alternative API for updating checking
		add_filter( 'pre_set_site_transient_update_plugins', [ $this, 'check_update' ], 50 );
		add_action( 'delete_site_transient_update_plugins', [ $this, 'delete_transients' ] );
		// Define the alternative response for information checking
		add_filter( 'plugins_api', [ $this, 'plugins_api_filter' ], 10, 3 );

		remove_action( 'after_plugin_row_' . $this->plugin_name, 'wp_plugin_update_row' );
		add_action( 'after_plugin_row_' . $this->plugin_name, [ $this, 'show_update_notification' ], 10, 2 );

		// Clear cache when language or upgrade changes
		add_action( 'update_option_WPLANG', [ $this, 'clean_get_version_cache' ] );
		add_action( 'upgrader_process_complete', [ $this, 'clean_get_version_cache' ] );
	}

	public function delete_transients() {
		$this->delete_transient( $this->response_transient_key );
	}

	/**
	 * Remove response transient cache
	 *
	 * @return void
	 */
	private function maybe_delete_transients() {
		global $pagenow;

		// Only allow force-check on update-core.php page with proper capability
		// Note: WordPress core doesn't use nonce verification for force-check as it's protected by capability checks
		if ( 'update-core.php' === $pagenow && current_user_can( 'update_plugins' ) && isset( $_GET['force-check'] ) ) {
			// Sanitize the force-check parameter
			$force_check = sanitize_text_field( wp_unslash( $_GET['force-check'] ) );
			if ( '1' === $force_check ) {
				$this->delete_transients();
			}
		}
	}

	/**
	 * Check transient info with server
	 *
	 * @param object|false $_transient_data Transient data object or false.
	 * @return object Transient data object with update information.
	 */
	public function check_transient_data( $_transient_data ) {
		if ( ! is_object( $_transient_data ) ) {
			$_transient_data = new \stdClass();
		}

		$version_info = WP_Ulike_Pro_API::get_version( false ); // Use Cache

		if ( is_wp_error( $version_info ) ) {
			return $_transient_data;
		}

		// Include an unmodified $wp_version
		require ABSPATH . WPINC . '/version.php';

		if ( version_compare( $wp_version, $version_info['requires'], '<' ) ) {
			return $_transient_data;
		}

		$plugin_info = (object) $version_info;
		unset( $plugin_info->sections );

		$plugin_info->plugin = $this->plugin_name;

		if ( ! empty( $plugin_info->banners ) ) {
			$plugin_info->banners = maybe_unserialize( $plugin_info->banners );
		}

		if ( ! empty( $plugin_info->icons ) ) {
			$plugin_info->icons = maybe_unserialize( $plugin_info->icons );
		}

		if ( version_compare( $this->current_version, $version_info['new_version'], '<' ) ) {
			$_transient_data->response[ $this->plugin_name ] = $plugin_info;
			$_transient_data->checked[ $this->plugin_name ]  = $version_info['new_version'];
		} else {
			$_transient_data->no_update[ $this->plugin_name ] = $plugin_info;
			$_transient_data->checked[ $this->plugin_name ]   = $this->current_version;
		}

		$_transient_data->last_checked = time();

		return $_transient_data;
	}

	/**
	 * Add our self-hosted autoupdate plugin to the filter transient
	 *
	 * @param object|false $_transient_data Transient data object or false.
	 * @return object Transient data object with update information.
	 */
	public function check_update( $_transient_data ) {
		if ( ! is_object( $_transient_data ) ) {
			$_transient_data = new \stdClass();
		}

		return $this->check_transient_data( $_transient_data );
	}

	/**
	 * Filter plugin API response
	 *
	 * @param false|object|array $_data Plugin API response data.
	 * @param string              $_action Action being performed.
	 * @param object              $_args Arguments for the API request.
	 * @return false|object|array Modified plugin API response data.
	 */
	public function plugins_api_filter( $_data, $_action = '', $_args = null ) {
		if ( 'plugin_information' !== $_action ) {
			return $_data;
		}

		if ( ! isset( $_args->slug ) || ( $_args->slug !== $this->plugin_slug ) ) {
			return $_data;
		}

		// Optimized cache key generation (no need for serialize on simple string)
		$cache_key = 'wp_ulike_pro_api_request_' . substr( md5( $this->plugin_slug ), 0, 15 );

		$api_request_transient = get_site_transient( $cache_key );

		if ( empty( $api_request_transient ) ) {
			$api_response = WP_Ulike_Pro_API::get_version();

			if ( is_wp_error( $api_response ) ) {
				return $_data;
			}

			$api_request_transient = new \stdClass();

			$api_request_transient->name          = esc_html__( 'WP ULike Pro', WP_ULIKE_PRO_DOMAIN );
			$api_request_transient->slug          = $this->plugin_slug;
			$api_request_transient->author        = '<a href="https://wpulike.com/?utm_source=wp-dash&utm_medium=plugin-uri&utm_campaign=api">wpulike.com</a>';
			$api_request_transient->homepage      = 'https://wpulike.com/?utm_source=wp-dash&utm_medium=plugin-uri&utm_campaign=api';
			$api_request_transient->requires      = $api_response['requires'];
			$api_request_transient->tested        = $api_response['tested'];

			$api_request_transient->version       = $api_response['new_version'];
			$api_request_transient->last_updated  = $api_response['last_updated'];
			$api_request_transient->download_link = $api_response['download_link'];
			$api_request_transient->banners       = maybe_unserialize( $api_response['banners'] );
			$api_request_transient->sections      = maybe_unserialize( $api_response['sections'] );
			$api_request_transient->autoupdate    = true;

			// Expires in 1 day
			set_site_transient( $cache_key, $api_request_transient, DAY_IN_SECONDS );
		}

		$_data = $api_request_transient;

		return $_data;
	}

	/**
	 * Show update notices
	 *
	 * @param string $file Plugin file path.
	 * @param array  $plugin Plugin data array.
	 * @return void
	 */
	public function show_update_notification( $file, $plugin ) {
		if ( is_network_admin() ) {
			return;
		}

		if ( ! current_user_can( 'update_plugins' ) ) {
			return;
		}

		if ( ! is_multisite() ) {
			return;
		}

		if ( $this->plugin_name !== $file ) {
			return;
		}

		// Remove our filter on the site transient
		remove_filter( 'pre_set_site_transient_update_plugins', [ $this, 'check_update' ] );

		$update_cache = get_site_transient( 'update_plugins' );
		$update_cache = $this->check_transient_data( $update_cache );
		set_site_transient( 'update_plugins', $update_cache );

		// Restore our filter
		add_filter( 'pre_set_site_transient_update_plugins', [ $this, 'check_update' ] );
	}

	/**
	 * Delete transient value
	 *
	 * Note: This uses options instead of WordPress transients for persistence.
	 *
	 * @param string $cache_key Cache key.
	 * @return void
	 */
	protected function delete_transient( $cache_key ) {
		delete_option( $cache_key );
	}

	/**
	 * Clean version cache when language or upgrade completes
	 *
	 * @return void
	 */
	public function clean_get_version_cache() {
		delete_option( 'wp_ulike_pro_remote_info_api_data_' . WP_ULIKE_PRO_VERSION );
	}

}