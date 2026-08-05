<?php
/**
 * Extends free Help backup with Pro site-level configuration.
 *
 * @package WP_ULike_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_Ulike_Pro_Backup' ) ) {

	/**
	 * Adds display rules and REST API settings to Help backup export/import.
	 */
	class WP_Ulike_Pro_Backup {

		const EXTENSION_KEY = 'wp-ulike-pro';

		/**
		 * Constructor.
		 */
		public function __construct() {
			add_filter( 'wp_ulike_backup_export_payload', array( $this, 'export_payload' ) );
			add_filter( 'wp_ulike_backup_import_extensions', array( $this, 'import_extensions' ), 10, 2 );
			add_filter( 'wp_ulike_backup_intro', array( $this, 'filter_backup_intro' ) );
			add_filter( 'wp_ulike_backup_import_confirm', array( $this, 'filter_import_confirm' ) );
		}

		/**
		 * Append Pro configuration to the export payload.
		 *
		 * @param array $data Export payload.
		 * @return array
		 */
		public function export_payload( $data ) {
			if ( ! is_array( $data ) ) {
				$data = array();
			}

			if ( ! isset( $data['extensions'] ) || ! is_array( $data['extensions'] ) ) {
				$data['extensions'] = array();
			}

			$data['extensions'][ self::EXTENSION_KEY ] = array(
				'version'           => defined( 'WP_ULIKE_PRO_VERSION' ) ? WP_ULIKE_PRO_VERSION : '',
				'display_rules'     => $this->get_display_rules(),
				'rest_api_settings' => $this->get_rest_api_settings(),
			);

			return $data;
		}

		/**
		 * Restore Pro configuration from an import payload.
		 *
		 * @param true|WP_Error $result  Import result.
		 * @param array         $payload Full decoded import payload.
		 * @return true|WP_Error
		 */
		public function import_extensions( $result, $payload ) {
			if ( is_wp_error( $result ) ) {
				return $result;
			}

			if ( empty( $payload['extensions'][ self::EXTENSION_KEY ] ) || ! is_array( $payload['extensions'][ self::EXTENSION_KEY ] ) ) {
				return $result;
			}

			$extension = $payload['extensions'][ self::EXTENSION_KEY ];

			if ( array_key_exists( 'display_rules', $extension ) && is_array( $extension['display_rules'] ) && class_exists( 'WP_Ulike_Pro_Display_Automation' ) ) {
				WP_Ulike_Pro_Display_Automation::save_rules( $extension['display_rules'] );
			}

			if ( array_key_exists( 'rest_api_settings', $extension ) && is_array( $extension['rest_api_settings'] ) && class_exists( 'WP_Ulike_Pro_Tools' ) ) {
				WP_Ulike_Pro_Tools::save_rest_api_settings( $extension['rest_api_settings'] );
			}

			return $result;
		}

		/**
		 * Help backup card intro for Pro sites.
		 *
		 * @param string $intro Default intro.
		 * @return string
		 */
		public function filter_backup_intro( $intro ) {
			return __( 'Download your settings, customizer values, display rules, and REST API configuration as JSON. Per-post schema/FAQ data and REST API keys are not included.', WP_ULIKE_PRO_DOMAIN );
		}

		/**
		 * Import confirmation message for Pro sites.
		 *
		 * @param string $message Default confirmation message.
		 * @return string
		 */
		public function filter_import_confirm( $message ) {
			return __( 'Import will replace your current WP ULike settings, customizer values, display rules, and REST API configuration. Continue?', WP_ULIKE_PRO_DOMAIN );
		}

		/**
		 * Get display automation rules for export.
		 *
		 * @return array
		 */
		private function get_display_rules() {
			if ( ! class_exists( 'WP_Ulike_Pro_Display_Automation' ) ) {
				return array();
			}

			return WP_Ulike_Pro_Display_Automation::get_rules();
		}

		/**
		 * Get REST API settings for export.
		 *
		 * @return array
		 */
		private function get_rest_api_settings() {
			if ( ! class_exists( 'WP_Ulike_Pro_Tools' ) ) {
				return array();
			}

			$settings = WP_Ulike_Pro_Tools::get_rest_api_settings_data();

			return is_array( $settings ) ? $settings : array();
		}
	}

	new WP_Ulike_Pro_Backup();
}

