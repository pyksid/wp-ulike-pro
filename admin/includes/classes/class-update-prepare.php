<?php

// no direct access allowed
if ( ! defined('ABSPATH') ) {
    die();
}

class WP_Ulike_Pro_Update_Prepare {

	/**
	 * The plugin current version
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
     * @var st
     */
	private $response_transient_key;

    function __construct(){
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

	private function setup_hooks() {
		// define the alternative API for updating checking
		add_filter( 'pre_set_site_transient_update_plugins', [ $this, 'check_update' ], 50 );
		add_action( 'delete_site_transient_update_plugins', [ $this, 'delete_transients' ] );
		// Define the alternative response for information checking
		add_filter( 'plugins_api', [ $this, 'plugins_api_filter' ], 10, 3 );

		remove_action( 'after_plugin_row_' . $this->plugin_name, 'wp_plugin_update_row' );
		add_action( 'after_plugin_row_' . $this->plugin_name, [ $this, 'show_update_notification' ], 10, 2 );

		add_action( 'update_option_WPLANG', function () {
			$this->clean_get_version_cache();
		} );

		add_action( 'upgrader_process_complete', function () {
			$this->clean_get_version_cache();
		} );
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

		if ( 'update-core.php' === $pagenow && isset( $_GET['force-check'] ) ) {
			$this->delete_transients();
		}
	}

	/**
	 * Check transient info with server
	 *
	 * @param object $_transient_data
	 * @return object
	 */
	public function check_transient_data( $_transient_data ){
		if ( ! is_object( $_transient_data ) ) {
			$_transient_data = new \stdClass();
		}

		if ( empty( $_transient_data->checked ) ) {
			return $_transient_data;
		}

		$version_info = WP_Ulike_Pro_API::get_version( false /* Use Cache */ );

		if ( is_wp_error( $version_info ) ) {
			return $_transient_data;
		}

		// include an unmodified $wp_version
		include( ABSPATH . WPINC . '/version.php' );

		if ( version_compare( $wp_version, $version_info['requires'], '<' ) ) {
			return $_transient_data;
		}

		if ( version_compare( $this->current_version, $version_info['new_version'], '<' ) ) {
			$plugin_info = (object) $version_info;
			unset( $plugin_info->sections );

			if( ! empty( $plugin_info->banners ) ){
				$plugin_info->banners = maybe_unserialize( $plugin_info->banners );
			}

			if( ! empty( $plugin_info->icons ) ){
				$plugin_info->icons = maybe_unserialize( $plugin_info->icons );
			}

			$_transient_data->response[ $this->plugin_name ] = $plugin_info;
		}

		$_transient_data->last_checked = current_time( 'timestamp' );
		$_transient_data->checked[ $this->plugin_name ] = $this->current_version;

		return $_transient_data;
	}

    /**
	 * Add our self-hosted autoupdate plugin to the filter transient
	 *
	 * @param $transient
	 * @return object $ transient
	 */
	public function check_update( $_transient_data ) {
		global $pagenow;

		if ( ! is_object( $_transient_data ) ) {
			$_transient_data = new \stdClass();
		}

		if ( 'plugins.php' === $pagenow && is_multisite() ) {
			return $_transient_data;
		}

		return $this->check_transient_data( $_transient_data );
	}

	public function plugins_api_filter( $_data, $_action = '', $_args = null  ){
		if ( 'plugin_information' !== $_action ) {
			return $_data;
		}

		if ( ! isset( $_args->slug ) || ( $_args->slug !== $this->plugin_slug ) ) {
			return $_data;
		}

		$cache_key = 'wp_ulike_pro_api_request_' . substr( md5( serialize( $this->plugin_slug ) ), 0, 15 );

		$api_request_transient = get_site_transient( $cache_key );

		if ( empty( $api_request_transient ) ) {
			$api_response = WP_Ulike_Pro_API::get_version();

			if ( is_wp_error( $api_response ) ) {
				return $_data;
			}

			$api_request_transient = new \stdClass();

			$api_request_transient->name          = WP_ULIKE_PRO_NAME;
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

			// Expires in 1 day
			set_site_transient( $cache_key, $api_request_transient, DAY_IN_SECONDS );
		}

		$_data = $api_request_transient;

		return $_data;
    }

	/**
	 * Show update notices
	 *
	 * @param string $file
	 * @param string $plugin
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

	protected function get_transient( $cache_key ) {
		$cache_data = get_option( $cache_key );

		if ( empty( $cache_data['timeout'] ) || current_time( 'timestamp' ) > $cache_data['timeout'] ) {
			// Cache is expired.
			return false;
		}

		return $cache_data['value'];
	}

	protected function set_transient( $cache_key, $value, $expiration = 0 ) {
		if ( empty( $expiration ) ) {
			$expiration = strtotime( '+12 hours', current_time( 'timestamp' ) );
		}

		$data = [
			'timeout' => $expiration,
			'value' => $value,
		];

		update_option( $cache_key, $data, 'no' );
	}

	protected function delete_transient( $cache_key ) {
		delete_option( $cache_key );
	}

	private function clean_get_version_cache() {
		delete_option( 'wp_ulike_pro_remote_info_api_data_' . WP_ULIKE_PRO_VERSION );
	}

}