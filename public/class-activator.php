<?php

/**
 * Fired during plugin activation
 *
 */

 class WP_Ulike_Pro_Activator {

    public static function activate() {
        // Install tables
        self::install_tables();
    }

    public static function install_tables() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'ulike_sessions';

        $result = true; // Default to success if table already exists

        // Create sessions table
        if ( ! self::table_exists( $table_name ) ) {
            if( ! function_exists( 'maybe_create_table' ) ){
                // Add one library admin function for next function
                require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            }

            $collate = '';
            if ( $wpdb->has_cap( 'collation' ) ) {
                $collate = $wpdb->get_charset_collate();
            }

            $result = maybe_create_table( $table_name, "
                CREATE TABLE `{$wpdb->prefix}ulike_sessions` (
                    session_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                    session_key char(32) NOT NULL,
                    session_value longtext NOT NULL,
                    session_expiry bigint(20) unsigned NOT NULL,
                    PRIMARY KEY  (session_id),
                    UNIQUE KEY session_key (session_key),
                    KEY session_expiry (session_expiry)
                ) $collate
            " );
        }

        update_option( 'wp_ulike_pro_database_version', '1.0.1' );
        return $result;
    }

    /**
     * Check if a table exists in the database
     *
     * @param string $table_name
     * @return bool
     */
    protected static function table_exists( $table_name ) {
        global $wpdb;
        $result = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) );
        return $result === $table_name;
    }

    /**
     * Check if a column exists on a table
     *
     * @param string $table_name
     * @param string $column_name
     * @return bool
     */
    protected static function column_exists( $table_name, $column_name ) {
        global $wpdb;
        $result = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s",
            DB_NAME,
            $table_name,
            $column_name
        ) );
        return (int) $result > 0;
    }

    /**
     * Execute a database query with error handling
     *
     * @param string $query
     * @return bool
     */
    protected static function execute_query( $query ) {
        global $wpdb;
        $result = $wpdb->query( $query );
        if ( false === $result ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                error_log( 'WP ULike Pro: Database query failed - ' . $wpdb->last_error . ' | Query: ' . $query );
            }
            return false;
        }
        return true;
    }

    /**
     * Upgrade database to version 1.0.2
     *
     * @return bool
     */
    public static function upgrade_0() {
		global $wpdb;

		// Define tables to be altered
		$tables = [
			$wpdb->prefix . 'ulike',
			$wpdb->prefix . 'ulike_comments',
			$wpdb->prefix . 'ulike_activities',
			$wpdb->prefix . 'ulike_forums'
		];

		// Columns to add
		$columns_to_add = [
			'country_code' => "ADD COLUMN `country_code` CHAR(2) DEFAULT NULL",
			'device'       => "ADD COLUMN `device` VARCHAR(50) DEFAULT NULL",
			'os'           => "ADD COLUMN `os` VARCHAR(50) DEFAULT NULL",
			'browser'      => "ADD COLUMN `browser` VARCHAR(50) DEFAULT NULL"
		];

		$result = true;

		// Loop through tables and update if necessary
		foreach ( $tables as $table ) {
			// Check if table exists first
			if ( ! self::table_exists( $table ) ) {
				continue; // Skip if table doesn't exist
			}

			$table_escaped = esc_sql( $table );
			$alter_queries = [];

			// Check each column and add if it doesn't exist
			foreach ( $columns_to_add as $column => $alter_query ) {
				if ( ! self::column_exists( $table, $column ) ) {
					$alter_queries[] = $alter_query;
				}
			}

			// Execute ALTER TABLE query if there are columns to add
			if ( ! empty( $alter_queries ) ) {
				$query = "ALTER TABLE `{$table_escaped}` " . implode( ', ', $alter_queries );
				if ( ! self::execute_query( $query ) ) {
					$result = false;
				}
			}
		}

		update_option( 'wp_ulike_pro_database_version', '1.0.2' );
		return $result;
    }

    /**
     * Upgrade database to version 1.0.3
     *
     * @return bool
     */
    public static function upgrade_1() {
		global $wpdb;

        $table_name = $wpdb->prefix . 'ulike_views';

        $result = true; // Default to success if table already exists

        if ( ! self::table_exists( $table_name ) ) {
            if( ! function_exists( 'maybe_create_table' ) ){
                require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            }

            $collate = '';
            if ( $wpdb->has_cap( 'collation' ) ) {
                $collate = $wpdb->get_charset_collate();
            }

            $result = maybe_create_table( $table_name, "
                CREATE TABLE `{$table_name}` (
                    view_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                    item_id bigint(20) unsigned NOT NULL,
                    type varchar(20) NOT NULL,
                    view_date date NOT NULL,
                    view_count int(11) unsigned DEFAULT 0,
                    date_time datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY  (view_id),
                    UNIQUE KEY unique_view (item_id, type, view_date),
                    KEY idx_item_type (item_id, type),
                    KEY idx_view_date (view_date),
                    KEY idx_type_date (type, view_date),
                    KEY idx_item_date (item_id, view_date)
                ) $collate
            " );
        }

		update_option( 'wp_ulike_pro_database_version', '1.0.3' );
        return $result;
    }
}
