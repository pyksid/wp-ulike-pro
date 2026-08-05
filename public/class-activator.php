<?php

/**
 * Fired during plugin activation
 *
 * @package WP_Ulike_Pro
 */

class WP_Ulike_Pro_Activator {

	const DB_VERSION = '2.0.0';

	public static function activate() {
		self::install_tables();
	}

	/**
	 * Pro tables only — vote data lives in WP ULike (free) ulike_pulse.
	 */
	public static function install_tables() {
		global $wpdb;

		if ( ! function_exists( 'maybe_create_table' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}

		$collate = $wpdb->has_cap( 'collation' ) ? $wpdb->get_charset_collate() : '';
		$result  = true;

		$sessions = $wpdb->prefix . 'ulike_sessions';
		if ( ! self::table_exists( $sessions ) ) {
			$result = maybe_create_table(
				$sessions,
				"CREATE TABLE `{$sessions}` (
                    session_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                    session_key char(32) NOT NULL,
                    session_value longtext NOT NULL,
                    session_expiry bigint(20) unsigned NOT NULL,
                    PRIMARY KEY  (session_id),
                    UNIQUE KEY session_key (session_key),
                    KEY session_expiry (session_expiry)
                ) $collate"
			) && $result;
		}

		$views = $wpdb->prefix . 'ulike_views';
		if ( ! self::table_exists( $views ) ) {
			$result = maybe_create_table(
				$views,
				"CREATE TABLE `{$views}` (
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
                ) $collate"
			) && $result;
		}

		update_option( 'wp_ulike_pro_database_version', self::DB_VERSION );
		return $result;
	}

	protected static function table_exists( $table_name ) {
		global $wpdb;
		$result = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) );
		return $result === $table_name;
	}
}

