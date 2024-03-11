<?php

/**
 * Fired during plugin activation
 *
 */

class WP_Ulike_Pro_Activator {

	protected static $tables, $database;

	public static function activate() {
		global $wpdb;

		self::$database = $wpdb;
		self::$tables   = array(
			'sessions' => self::$database->prefix . 'ulike_sessions'
		);

		self::install_tables();
	}

	public static function install_tables(){

		if( ! function_exists( 'maybe_create_table' ) ){
			// Add one library admin function for next function
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}

		$collate = '';

		if ( self::$database->has_cap( 'collation' ) ) {
			$collate = self::$database->get_charset_collate();
		}

		extract(self::$tables);

		// Posts table
		maybe_create_table( $sessions, "CREATE TABLE `{$sessions}` (
			session_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			session_key char(32) NOT NULL,
			session_value longtext NOT NULL,
			session_expiry bigint(20) unsigned NOT NULL,
			PRIMARY KEY  (session_id),
			UNIQUE KEY session_key (session_key)
		) $collate" );


		// Update db version
		if( get_option( 'wp_ulike_pro_database_version' ) === false ){
			update_option( 'wp_ulike_pro_database_version', '1.0.1' );
		}
	}
}