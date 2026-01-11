<?php

/**
 * Fired during plugin deactivation
 *
 */

class WP_Ulike_Pro_Deactivator {

	public static function deactivate() {
		// Clear scheduled session cleanup
		wp_clear_scheduled_hook( 'wp_ulike_pro_cleanup_expired_sessions' );
	}

}