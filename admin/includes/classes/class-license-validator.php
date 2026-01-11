<?php

// no direct access allowed
if ( ! defined('ABSPATH') ) {
    die();
}

/**
 * Simple License Validator
 *
 * Basic integrity checks to detect if license key or data was manually modified.
 * Uses cached data only - no remote requests.
 *
 * @since 1.0.0
 */
class WP_Ulike_Pro_License_Validator {

	/**
	 * Validation cache to avoid repeated checks
	 */
	private static $validation_cache = [];


	/**
	 * Simple license validation - just checks integrity
	 * Uses cached data only - no remote requests
	 *
	 * @param bool $force_remote Not used - kept for compatibility
	 * @return bool
	 */
	public static function validate( $force_remote = false ) {
		// Check cache first
		if ( isset( self::$validation_cache['simple'] ) ) {
			return self::$validation_cache['simple'];
		}

		// Simple check 1: License key exists
		$license_key = WP_Ulike_Pro_License::get_license_key();
		if ( empty( $license_key ) ) {
			self::$validation_cache['simple'] = false;
			return false;
		}

		// Simple check 2: Verify checksum (detects if key was manually changed)
		$stored_checksum = get_option( 'wp_ulike_pro_license_checksum', '' );
		if ( ! empty( $stored_checksum ) ) {
			$calculated_checksum = self::calculate_license_checksum( $license_key );
			if ( $stored_checksum !== $calculated_checksum ) {
				// License key was modified - likely nulled
				self::$validation_cache['simple'] = false;
				return false;
			}
		}

		// Simple check 3: Get cached license data (no remote request)
		$license_data = WP_Ulike_Pro_API::get_license_data( false );

		if ( ! is_array( $license_data ) || empty( $license_data['license'] ) ) {
			self::$validation_cache['simple'] = false;
			return false;
		}

		// Simple check 4: License status
		$is_valid = WP_Ulike_Pro_API::STATUS_VALID === $license_data['license'];

		// Simple check 5: Verify signature (detects if data was manually set to 'valid')
		if ( $is_valid ) {
			$stored_signature = get_option( 'wp_ulike_pro_license_signature', '' );
			if ( ! empty( $stored_signature ) ) {
				$calculated_signature = self::calculate_data_signature( $license_data );
				if ( $stored_signature !== $calculated_signature ) {
					// Data was tampered with
					$is_valid = false;
				}
			}
		}

		self::$validation_cache['simple'] = $is_valid;

		return $is_valid;
	}


	/**
	 * Calculate checksum for license key
	 *
	 * @param string $license_key
	 * @return string
	 */
	private static function calculate_license_checksum( $license_key ) {
		$salt = wp_ulike_pro_get_audit_token();
		return hash( 'sha256', $license_key . $salt . home_url() );
	}


	/**
	 * Calculate signature for license data
	 *
	 * @param array $license_data
	 * @return string
	 */
	private static function calculate_data_signature( $license_data ) {
		$key_fields = [
			$license_data['license'] ?? '',
			$license_data['payment_id'] ?? '',
			$license_data['expires'] ?? '',
			home_url(),
			wp_ulike_pro_get_audit_token(),
		];

		return hash( 'sha256', implode( '|', $key_fields ) );
	}

}

