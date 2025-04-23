<?php

/**
 * Fired during plugin activation
 *
 */

 class WP_Ulike_Pro_Activator {

    public static function activate() {
        global $wpdb;

        // Install tables
        self::install_tables();
    }

    public static function install_tables() {
        global $wpdb;

		if( ! function_exists( 'maybe_create_table' ) ){
			// Add one library admin function for next function
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}

        $collate = '';
        if ( $wpdb->has_cap( 'collation' ) ) {
            $collate = $wpdb->get_charset_collate();
        }

        // Create sessions table
        maybe_create_table( $wpdb->prefix . 'ulike_sessions', "
            CREATE TABLE `{$wpdb->prefix}ulike_sessions` (
                session_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                session_key char(32) NOT NULL,
                session_value longtext NOT NULL,
                session_expiry bigint(20) unsigned NOT NULL,
                PRIMARY KEY  (session_id),
                UNIQUE KEY session_key (session_key)
            ) $collate
        " );

        // Update DB version
        update_option( 'wp_ulike_pro_database_version', '1.0.1' );
    }

    public static function upgrade_0() {
		global $wpdb;

		// Define tables to be altered
		$tables = [
			"{$wpdb->prefix}ulike",
			"{$wpdb->prefix}ulike_comments",
			"{$wpdb->prefix}ulike_activities",
			"{$wpdb->prefix}ulike_forums"
		];

		// Columns to check and add
		$columns_to_check = [
			'country_code' => "ADD COLUMN `country_code` CHAR(2) DEFAULT NULL",
			'device'       => "ADD COLUMN `device` VARCHAR(50) DEFAULT NULL",
			'os'           => "ADD COLUMN `os` VARCHAR(50) DEFAULT NULL",
			'browser'      => "ADD COLUMN `browser` VARCHAR(50) DEFAULT NULL"
		];

		// Loop through tables and update if necessary
		foreach ($tables as $table) {
			$existing_columns = $wpdb->get_col("SHOW COLUMNS FROM `$table`");

			$alter_queries = [];
			foreach ($columns_to_check as $column => $alter_query) {
				if (!in_array($column, $existing_columns)) {
					$alter_queries[] = $alter_query;
				}
			}

			if (!empty($alter_queries)) {
				$query = "ALTER TABLE `$table` " . implode(', ', $alter_queries) . ";";
				$wpdb->query($query);
			}
		}

		// Update DB version
		update_option('wp_ulike_pro_database_version', '1.0.2');
    }
}
