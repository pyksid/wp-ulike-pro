<?php
/**
 * Handle data for the current user session
 *
 * 
 * @package    wp-ulike-pro
 * @author     TechnoWich 2025
 * @link       https://wpulike.com
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WP_Ulike_Pro_Session
 */
abstract class  WP_Ulike_Pro_Session {

	/**
	 * User ID.
	 *
	 * @var ?string $_user_id User ID.
	 */
	protected $_user_id; // phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore

	/**
	 * Session Data.
	 *
	 * @var array $_data Data array.
	 */
	protected $_data = array(); // phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore

	/**
	 * Dirty when the session needs saving.
	 *
	 * @var bool $_dirty When something changes
	 */
	protected $_dirty = false; // phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore

	/**
	 * Init hooks and session data. Extended by child classes.
	 *
	 */
	public function init() {}

	/**
	 * Cleanup session data. Extended by child classes.
	 */
	public function cleanup_sessions() {}

	/**
	 * Magic get method.
	 *
	 * @param string $key Key to get.
	 * @return mixed
	 */
	public function __get( $key ) {
		return $this->get( $key );
	}

	/**
	 * Magic set method.
	 *
	 * @param string $key Key to set.
	 * @param mixed  $value Value to set.
	 */
	public function __set( $key, $value ) {
		$this->set( $key, $value );
	}

	/**
	 * Magic isset method.
	 *
	 * @param string $key Key to check.
	 * @return bool
	 */
	public function __isset( $key ) {
		return isset( $this->_data[ sanitize_key( $key ) ] );
	}

	/**
	 * Magic unset method.
	 *
	 * @param string $key Key to unset.
	 */
	public function __unset( $key ) {
		$key = sanitize_key( $key );
		if ( isset( $this->_data[ $key ] ) ) {
			unset( $this->_data[ $key ] );
			$this->_dirty = true;
		}
	}

	/**
	 * Get a session variable.
	 *
	 * @param string $key Key to get.
	 * @param mixed  $default_value used if the session variable isn't set.
	 * @return mixed value of session variable
	 */
	public function get( $key, $default_value = null ) {
		$key = sanitize_key( $key );
		return isset( $this->_data[ $key ] ) ? maybe_unserialize( $this->_data[ $key ] ) : $default_value;
	}

	/**
	 * Set a session variable.
	 *
	 * @param string $key Key to set.
	 * @param mixed  $value Value to set.
	 */
	public function set( $key, $value ) {
		if ( null === $value ) {
			$this->__unset( $key );

			return;
		}

		$key                       = sanitize_key( $key );
		$serialized_original_value = $this->_data[ $key ] ?? null;
		$serialized_value          = maybe_serialize( $value );

		if ( $serialized_original_value === $serialized_value || maybe_unserialize( $serialized_original_value ) === $value ) {
			return;
		}

		$this->_dirty        = true;
		$this->_data[ $key ] = $serialized_value;
	}

	/**
	 * Get user ID. If the session is not initialized, returns an empty string.
	 *
	 * @return string
	 */
	public function get_user_id() {
		return $this->_user_id ?? '';
	}
}
