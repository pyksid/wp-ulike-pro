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
	 * @var int $_user_id User ID.
	 */
	protected $_user_id;

	/**
	 * Session Data.
	 *
	 * @var array $_data Data array.
	 */
	protected $_data = array();

	/**
	 * Dirty when the session needs saving.
	 *
	 * @var bool $_dirty When something changes
	 */
	protected $_dirty = false;

	/**
	 * Init hooks and session data. Extended by child classes.
	 */
	public function init() {}

	/**
	 * Cleanup session data. Extended by child classes.
	 */
	public function cleanup_sessions() {}

	/**
	 * Magic get method.
	 *
	 * @param mixed $key Key to get.
	 * @return mixed
	 */
	public function __get( $key ) {
		return $this->get( $key );
	}

	/**
	 * Magic set method.
	 *
	 * @param mixed $key Key to set.
	 * @param mixed $value Value to set.
	 */
	public function __set( $key, $value ) {
		$this->set( $key, $value );
	}

	/**
	 * Magic isset method.
	 *
	 * @param mixed $key Key to check.
	 * @return bool
	 */
	public function __isset( $key ) {
		return isset( $this->_data[ sanitize_title( $key ) ] );
	}

	/**
	 * Magic unset method.
	 *
	 * @param mixed $key Key to unset.
	 */
	public function __unset( $key ) {
		if ( isset( $this->_data[ $key ] ) ) {
			unset( $this->_data[ $key ] );
			$this->_dirty = true;
		}
	}

	/**
	 * Get a session variable.
	 *
	 * @param string $key Key to get.
	 * @param mixed  $default used if the session variable isn't set.
	 * @return array|string value of session variable
	 */
	public function get( $key, $default = null ) {
		$key = sanitize_key( $key );
		return isset( $this->_data[ $key ] ) ? maybe_unserialize( $this->_data[ $key ] ) : $default;
	}

	/**
	 * Set a session variable.
	 *
	 * @param string $key Key to set.
	 * @param mixed  $value Value to set.
	 */
	public function set( $key, $value ) {
		if ( $value !== $this->get( $key ) ) {
			$this->_data[ sanitize_key( $key ) ] = maybe_serialize( $value );
			$this->_dirty                        = true;
		}
	}

	/**
	 * Get User ID.
	 *
	 * @return int
	 */
	public function get_user_id() {
		return $this->_user_id;
	}
}
