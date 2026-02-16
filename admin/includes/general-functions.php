<?php
/**
 * Admin Function
 *
 * 
 * @package    wp-ulike-pro
 * @author     TechnoWich 2026
 * @link       https://wpulike.com
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die('No Naughty Business Please !');
}

/**
 * Check license activation status
 *
 * @return void
 */
function wp_ulike_pro_is_activated(){
    return WP_Ulike_Pro_API::is_license_active();
}

/**
 * Get license info from options table
 *
 * @return array|null
 */
function wp_ulike_pro_get_license_info(){
    return WP_Ulike_Pro_License::get_license_key();
}

/**
 * Helper function to check if operation should continue (time/memory limits)
 *
 * @param int $start_time Operation start time
 * @param int $max_execution_time Maximum execution time in seconds (default 25)
 * @return bool
 */
function wp_ulike_pro_can_continue_operation( $start_time, $max_execution_time = 25 ) {
	// Check execution time (leave 5 seconds buffer for response)
	if( ( time() - $start_time ) >= $max_execution_time ){
		return false;
	}

	// Check memory usage (leave 10MB buffer)
	$memory_limit = wp_ulike_pro_get_memory_limit();
	$current_memory = memory_get_usage( true );
	if( $memory_limit > 0 && ( $current_memory / $memory_limit ) > 0.9 ){
		return false;
	}

	return true;
}

/**
 * Get PHP memory limit in bytes
 *
 * @return int Memory limit in bytes, 0 if unlimited
 */
function wp_ulike_pro_get_memory_limit() {
	$limit = ini_get( 'memory_limit' );
	if( $limit === '-1' ){
		return 0; // Unlimited
	}

	$limit = trim( $limit );
	$last = strtolower( $limit[ strlen( $limit ) - 1 ] );
	$value = (int) $limit;

	switch( $last ){
		case 'g':
			$value *= 1024;
		case 'm':
			$value *= 1024;
		case 'k':
			$value *= 1024;
	}

	return $value;
}

/**
 * Delete all logs
 *
 * @param string $type
 * @return boolean
 */
function wp_ulike_pro_truncate_table( $type ) {
    global $wpdb;

	if( ! current_user_can( 'manage_options' ) ){
		return false;
	}

	// Validate type parameter - whitelist allowed types
	$allowed_types = array( 'post', 'comment', 'activity', 'topic' );
	if( ! in_array( $type, $allowed_types, true ) ){
		return false;
	}

    $info_args = wp_ulike_get_table_info( $type );
    if( empty( $info_args ) || ! isset( $info_args['table'] ) ){
        return false;
    }

	// Validate table name - ensure it's a known WP ULike table
	$allowed_tables = array( 'ulike', 'ulike_meta', 'ulike_activities', 'ulike_comments', 'ulike_forums' );
	if( ! in_array( $info_args['table'], $allowed_tables, true ) ){
		return false;
	}

    $table_name = $wpdb->prefix . $info_args['table'];

    // Get count before deletion - table name is validated above
    $count = (int) $wpdb->get_var( sprintf( "SELECT COUNT(*) FROM `%s`", esc_sql( $table_name ) ) );

    // TRUNCATE TABLE - table name is validated above
    if ( $wpdb->query( sprintf( "TRUNCATE TABLE `%s`", esc_sql( $table_name ) ) ) === FALSE ) {
        return false;
    }

    $type_label = ucfirst( $type );
    return array(
        'success' => true,
        'rows_affected' => $count,
        'message' => sprintf( esc_html__( 'Successfully deleted %d vote records for %s.', WP_ULIKE_PRO_DOMAIN ), $count, $type_label )
    );
}

/**
 * Delete singular logs|meta from meta box panel
 *
 * @param string|array $args JSON string or array with method and id
 * @return array|false
 */
function wp_ulike_pro_post_metabox_truncate( $args ) {
    global $wpdb;

	if( ! current_user_can( 'manage_options' ) ){
		return false;
    }

	// Decode JSON string if needed
	if( is_string( $args ) ){
		$args = json_decode( $args, true );
	}

	if( ! is_array( $args ) || empty( $args['method'] ) || empty( $args['id'] ) ){
		return false;
    }

	// Sanitize method
	$method = sanitize_text_field( $args['method'] );
	if( ! in_array( $method, array( 'meta', 'logs' ), true ) ){
		return false;
	}

	// Sanitize ID
	$post_id = absint( $args['id'] );
	if( $post_id <= 0 ){
		return false;
	}

    $table = $method === 'meta' ? 'ulike_meta' : 'ulike';
    $where = $method === 'meta' ?  array( 'item_id' => $post_id,  'meta_group' => 'post' ) : array( 'post_id' => $post_id );

	// Get count before deletion
	if( $method === 'meta' ){
		$count = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}{$table} WHERE item_id = %d AND meta_group = %s",
			$post_id,
			'post'
		) );
	} else {
		$count = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}{$table} WHERE post_id = %d",
			$post_id
		) );
	}

    // Delete records
    $deleted = $wpdb->delete( $wpdb->prefix . $table, $where );

    if ( $deleted === false ) {
		return false;
    }

	$method_label = $method === 'meta' ? esc_html__( 'Meta Counter Data', WP_ULIKE_PRO_DOMAIN ) : esc_html__( 'Likes Logs', WP_ULIKE_PRO_DOMAIN );

    return array(
		'success' => true,
		'rows_affected' => $deleted,
		'message' => sprintf( esc_html__( 'Successfully removed %d %s record(s) for post ID %d.', WP_ULIKE_PRO_DOMAIN ), $deleted, $method_label, $post_id )
    );
}

/**
 * Delete all orphaned rows.
 *
 * @param string $type
 * @return boolean
 */
function wp_ulike_pro_delete_orphaned_rows( $type ) {
    global $wpdb;

	if( ! current_user_can( 'manage_options' ) ){
		return false;
	}

	// Validate type parameter - whitelist allowed types
	$allowed_types = array( 'post', 'comment', 'activity', 'topic' );
	if( ! in_array( $type, $allowed_types, true ) ){
		return false;
	}

    $info_args = wp_ulike_get_table_info( $type );
    if( empty( $info_args ) || ! isset( $info_args['table'] ) || ! isset( $info_args['column'] ) || ! isset( $info_args['related_column'] ) || ! isset( $info_args['related_table_prefix'] ) ){
        return false;
    }

	// Validate table name
	$allowed_tables = array( 'ulike', 'ulike_meta', 'ulike_activities', 'ulike_comments', 'ulike_forums' );
	if( ! in_array( $info_args['table'], $allowed_tables, true ) ){
		return false;
	}

    $table_name = $wpdb->prefix . $info_args['table'];

    // Sanitize column names
    $column = esc_sql( $info_args['column'] );
    $related_column = esc_sql( $info_args['related_column'] );
    $related_table = esc_sql( $info_args['related_table_prefix'] );

    // Get count before deletion - use LEFT JOIN for better performance on large tables
    $count_query = sprintf( "
        SELECT COUNT(*) FROM `%s` t
        LEFT JOIN `%s` dt ON t.`%s` = dt.`%s`
        WHERE dt.`%s` IS NULL",
        esc_sql( $table_name ),
        $related_table,
        $column,
        $related_column,
        $related_column
    );
    $count = (int) $wpdb->get_var( $count_query );

    if( $count > 0 ){
        // Delete orphaned rows using subquery (MySQL doesn't support LIMIT in DELETE with JOIN)
        $delete_query = sprintf( "
            DELETE FROM `%s`
            WHERE `id` IN (
                SELECT `id` FROM (
                    SELECT t.`id` FROM `%s` t
                    LEFT JOIN `%s` dt ON t.`%s` = dt.`%s`
                    WHERE dt.`%s` IS NULL
                ) AS temp
            )",
            esc_sql( $table_name ),
            esc_sql( $table_name ),
            $related_table,
            $column,
            $related_column,
            $related_column
        );

        $wpdb->query( $delete_query );
    }

    $deleted_count = $count;

    $type_label = ucfirst( $type );
    return array(
        'success' => true,
        'rows_affected' => $deleted_count,
        'message' => sprintf( esc_html__( 'Successfully removed %d invalid vote records for %s.', WP_ULIKE_PRO_DOMAIN ), $deleted_count, $type_label )
    );
}

/**
 * Optimize tables
 *
 * @param string $type
 * @return boolean
 */
function wp_ulike_pro_optimize_table( $type ) {
    global $wpdb;

	if( ! current_user_can( 'manage_options' ) ){
		return false;
	}

	// Validate type parameter
	$allowed_types = array( 'post', 'comment', 'activity', 'topic' );
	if( ! in_array( $type, $allowed_types, true ) ){
		return false;
	}

    $info_args = wp_ulike_get_table_info( $type );
    if( empty( $info_args ) || ! isset( $info_args['table'] ) ){
        return false;
    }

	// Validate table name
	$allowed_tables = array( 'ulike', 'ulike_meta', 'ulike_activities', 'ulike_comments', 'ulike_forums', 'ulike_sessions', 'ulike_views' );
	if( ! in_array( $info_args['table'], $allowed_tables, true ) ){
		return false;
	}

    $table_name = $wpdb->prefix . $info_args['table'];

    // Create query string - table name is validated above
    $query  = sprintf( "OPTIMIZE TABLE `%s`", esc_sql( $table_name ) );

    if ( $wpdb->query( $query ) === FALSE ) {
        return false;
    }

    $type_label = ucfirst( $type );
    return array(
        'success' => true,
        'rows_affected' => 0,
        'message' => sprintf( esc_html__( 'Successfully optimized database table for %s.', WP_ULIKE_PRO_DOMAIN ), $type_label )
    );
}

/**
 * Migrate counter meta values
 *
 * @param string $type
 * @return boolean
 */
function wp_ulike_pro_migrate_metadata( $type ) {
    global $wpdb;

	if( ! current_user_can( 'manage_options' ) || ! in_array( $type, array( 'post','comment' ), true ) ){
		return false;
    }

    $meta_key = ! wp_ulike_setting_repo::isDistinct( $type ) ? 'count_total_' : 'count_distinct_';
    $table_name = $wpdb->prefix . 'ulike_meta';

    // Create query string - table name is validated, use sprintf for table name and prepare for values
    $query = $wpdb->prepare(
        "SELECT * FROM `" . esc_sql( $table_name ) . "` WHERE `meta_group` = %s AND `meta_key` LIKE %s",
        $type,
        '%' . $wpdb->esc_like( $meta_key ) . '%'
    );

    // get results
    $result = $wpdb->get_results( $query );

    // return false if meta not exist
    if( empty( $result ) ){
        return false;
    }

    $migrated_count = 0;
    // Update metadata
    $net_votes = [];
    foreach ( $result as $key => $value ) {
        if( get_post_type( $value->item_id ) ){
            $status     = str_replace( $meta_key, '', $value->meta_key);
            $quantity   = wp_ulike_pro_get_counter_quantity( $value->item_id, $status, $type );
            $meta_value = (int) $value->meta_value + (int) $quantity;
            update_metadata( $type, $value->item_id, $status . '_amount' , $meta_value );
            // save net_votes for another migrate
            $net_votes[ $value->item_id ][$status] = $meta_value;
            $migrated_count++;
        }
    }

    if( ! empty( $net_votes ) ){
        foreach ($net_votes as $item_id => $args) {
            $net_votes_val = ! empty( $args['dislike'] ) ? ( $args['like'] - $args['dislike'] ) : $args['like'];
            update_metadata( $type, $item_id, 'net_votes' , $net_votes_val );
        }
    }

    $type_label = ucfirst( $type );
    return array(
        'success' => true,
        'rows_affected' => $migrated_count,
        'message' => sprintf( esc_html__( 'Successfully migrated %d counter values to WordPress meta for %s.', WP_ULIKE_PRO_DOMAIN ), $migrated_count, $type_label )
    );
}

/**
 * Delete meta data by group name
 *
 * @param string $group_name
 * @return boolean
 */
function wp_ulike_pro_delete_meta_group( $group_name ){
    global $wpdb;

	if( ! current_user_can( 'manage_options' ) ){
		return false;
	}

	// Validate group_name parameter - whitelist allowed groups
	$allowed_groups = array( 'post', 'comment', 'activity', 'topic', 'user', 'statistics' );
	if( ! in_array( $group_name, $allowed_groups, true ) ){
		return false;
	}

    $table_name = $wpdb->prefix . 'ulike_meta';

    // Create ulike meta logs - table name is validated, use sprintf for table name and prepare for values
    $query = $wpdb->prepare(
        "SELECT * FROM `" . esc_sql( $table_name ) . "` WHERE `meta_group` = %s",
        $group_name
    );
    $data  = $wpdb->get_results( $query );

    $count = 0;
    if( ! empty( $data ) ){
        $count = count( $data );
        foreach ( $data as $m_key => $m_value ) {
            wp_ulike_delete_meta_data( $group_name, $m_value->item_id, $m_value->meta_key );
        }
    }

    $post_meta_count = 0;
    // Delete post meta logs
    if( in_array( $group_name, array( 'post','comment' ) ) ){

        // Create query string - WordPress core tables are safe, but use esc_sql for consistency
        $meta_table = $group_name === 'post' ? $wpdb->postmeta : $wpdb->commentmeta;
        $meta_query = sprintf( "
            SELECT * FROM `%s` WHERE `meta_key` IN ('like_amount','dislike_amount', 'net_votes', 'likes_counter_quantity','dislikes_counter_quantity') ",
            esc_sql( $meta_table )
        );
        $meta_data = $wpdb->get_results( $meta_query );

        if( ! empty( $meta_data ) ){
            $post_meta_count = count( $meta_data );
            foreach ( $meta_data as $key => $value ) {
                delete_metadata( $group_name, $group_name === 'post' ? $value->post_id : $value->comment_id, $value->meta_key );
            }
        }
    }

    $total_count = $count + $post_meta_count;
    $group_label = ucfirst( $group_name );
    if( $group_name === 'user' ){
        $group_label = esc_html__( 'User Status', WP_ULIKE_PRO_DOMAIN );
    } elseif( $group_name === 'statistics' ){
        $group_label = esc_html__( 'Statistics', WP_ULIKE_PRO_DOMAIN );
    }

    return array(
        'success' => true,
        'rows_affected' => $total_count,
        'message' => sprintf( esc_html__( 'Successfully deleted %d meta entries for %s.', WP_ULIKE_PRO_DOMAIN ), $total_count, $group_label )
    );
}

/**
 * Delete meta data by group name
 *
 * @param string $group_name
 * @return boolean
 */
function wp_ulike_pro_delete_duplicate_rows( $type ){
    global $wpdb;

	if( ! current_user_can( 'manage_options' ) ){
		return false;
    }

	// Validate type parameter
	$allowed_types = array( 'post', 'comment', 'activity', 'topic' );
	if( ! in_array( $type, $allowed_types, true ) ){
		return false;
	}

    $info_args = wp_ulike_get_table_info( $type );
    if( empty( $info_args ) || ! isset( $info_args['table'] ) || ! isset( $info_args['column'] ) ){
        return false;
    }

	// Validate table name
	$allowed_tables = array( 'ulike', 'ulike_meta', 'ulike_activities', 'ulike_comments', 'ulike_forums' );
	if( ! in_array( $info_args['table'], $allowed_tables, true ) ){
		return false;
	}

    $table_name = $wpdb->prefix . $info_args['table'];
    $column = esc_sql( $info_args['column'] );

    // Get total count before deletion
    $total_count = (int) $wpdb->get_var( sprintf( "SELECT COUNT(*) FROM `%s`", esc_sql( $table_name ) ) );

    // Get count of unique rows (what will remain)
    $unique_query = sprintf( "
        SELECT COUNT(*) FROM (
            SELECT MAX(`id`) as max_id FROM `%s` GROUP BY `user_id`, `%s`
        ) as unique_rows",
        esc_sql( $table_name ),
        $column
    );
    $unique_count = (int) $wpdb->get_var( $unique_query );

    $duplicate_count = $total_count - $unique_count;

    if( $duplicate_count > 0 ){
        // Delete duplicate rows using subquery (MySQL doesn't support LIMIT in DELETE with JOIN)
        $delete_query = sprintf( '
            DELETE FROM `%1$s`
            WHERE `id` IN (
                SELECT `id` FROM (
                    SELECT t1.`id` FROM `%1$s` t1
                    INNER JOIN (
                        SELECT `user_id`, `%2$s`, MAX(`id`) as max_id
                        FROM `%1$s`
                        GROUP BY `user_id`, `%2$s`
                    ) t2 ON t1.`user_id` = t2.`user_id` AND t1.`%2$s` = t2.`%2$s` AND t1.`id` < t2.max_id
                ) AS temp
            )',
            esc_sql( $table_name ),
            $column
        );

        $wpdb->query( $delete_query );
    }

    $deleted_count = $duplicate_count;

    $type_label = ucfirst( $type );
    return array(
        'success' => true,
        'rows_affected' => $deleted_count,
        'message' => sprintf( esc_html__( 'Successfully removed %d duplicate vote records for %s.', WP_ULIKE_PRO_DOMAIN ), $deleted_count, $type_label )
    );
}

/**
 * Delete empty post meta rows
 *
 * @param string $group_name
 * @return boolean
 */
function wp_ulike_pro_optimize_post_meta( $group_name ){
    global $wpdb;

	if( ! current_user_can( 'manage_options' ) ){
		return false;
    }

	// Validate group_name parameter
	$allowed_groups = array( 'optimize', 'delete_all' );
	if( ! in_array( $group_name, $allowed_groups, true ) ){
		return false;
	}

    // Table name is safe (WordPress core table), use sprintf for table name and prepare for values
    $postmeta_table = esc_sql( $wpdb->postmeta );
    $like_pattern = $wpdb->esc_like( 'wp_ulike_pro' ) . '%';

    if( $group_name === 'delete_all' ){
        $count_query = $wpdb->prepare(
            "SELECT COUNT(*) FROM `" . $postmeta_table . "` WHERE `meta_key` LIKE %s AND `meta_key` != %s",
            $like_pattern,
            'wp_ulike_pro_meta_box'
        );
        $query = $wpdb->prepare(
            "DELETE FROM `" . $postmeta_table . "` WHERE `meta_key` LIKE %s AND `meta_key` != %s",
            $like_pattern,
            'wp_ulike_pro_meta_box'
        );
    } else {
        $count_query = $wpdb->prepare(
            "SELECT COUNT(*) FROM `" . $postmeta_table . "` WHERE `meta_key` LIKE %s AND `meta_value` = ''",
            $like_pattern
        );
        $query = $wpdb->prepare(
            "DELETE FROM `" . $postmeta_table . "` WHERE `meta_key` LIKE %s AND `meta_value` = ''",
            $like_pattern
        );
    }

    $count = (int) $wpdb->get_var( $count_query );

    if ( $wpdb->query( $query ) === FALSE ) {
        return false;
    }

    $message = $group_name !== 'delete_all'
        ? sprintf( esc_html__( 'Successfully deleted %d empty post meta rows.', WP_ULIKE_PRO_DOMAIN ), $count )
        : sprintf( esc_html__( 'Successfully deleted %d old format post meta rows.', WP_ULIKE_PRO_DOMAIN ), $count );

    return array(
        'success' => true,
        'rows_affected' => $count,
        'message' => $message
    );
}

/**
 * Convert the old meta boxes to the new serialize structure.
 *
 * @param string $group_name
 * @return boolean
 */
function wp_ulike_pro_upgrade_unserialize_post_meta( $group_name ){
    global $wpdb;

	if( ! current_user_can( 'manage_options' ) ){
		return false;
    }

    // Create query string - postmeta is WordPress core table, safe to use directly
    $postmeta_table = esc_sql( $wpdb->postmeta );
    $like_pattern = $wpdb->esc_like( 'wp_ulike_pro' ) . '%';

    // Use prepare for proper quoting of string values
    $posts_query = $wpdb->prepare(
        "SELECT DISTINCT `post_id` FROM `" . $postmeta_table . "` WHERE `meta_key` LIKE %s AND `meta_key` != %s",
        $like_pattern,
        'wp_ulike_pro_meta_box'
    );
    $deprecated_options = $wpdb->get_results( $posts_query );

    $upgraded_count = 0;
    foreach ( $deprecated_options as $post ) {
        $post_id = absint( $post->post_id );
        $options_val = array();
        // Use prepare for values, table name already escaped
        $meta_query = $wpdb->prepare(
            "SELECT `meta_key`, `meta_value` FROM `" . $postmeta_table . "` WHERE `meta_key` LIKE %s AND `meta_key` != %s AND `post_id` = %d",
            $like_pattern,
            'wp_ulike_pro_meta_box',
            $post_id
        );
        $meta_data = $wpdb->get_results( $meta_query );
        if( ! empty( $meta_data ) ){
            foreach ( $meta_data as $meta ) {
                $option_key = str_replace( 'wp_ulike_pro_', '', $meta->meta_key );
                $options_val[ $option_key ] = $meta->meta_value;
            }
        }

        if( ! empty( $options_val ) ){
            update_post_meta( $post->post_id, 'wp_ulike_pro_meta_box', $options_val );
            $upgraded_count++;
        }
    }

    return array(
        'success' => true,
        'rows_affected' => $upgraded_count,
        'message' => sprintf( esc_html__( 'Successfully upgraded %d posts to the new format.', WP_ULIKE_PRO_DOMAIN ), $upgraded_count )
    );
}

/**
 * Manage default pages
 *
 * @param string $action
 * @return boolean
 */
function wp_ulike_pro_manage_default_pages( $action ){
    global $wpdb;

	if( ! current_user_can( 'manage_options' ) ){
		return false;
    }

    if( $action == 'create' ){
        // install pages
        if( ! class_exists( 'WP_Ulike_Pro_Activator' ) ){
            require_once WP_ULIKE_PRO_DIR . 'public/class-activator.php';
        }
        $result = WP_Ulike_Pro_Core_Pages::install();
        $count = is_numeric( $result ) ? (int) $result : 0;
        return array(
            'success' => true,
            'rows_affected' => $count,
            'message' => sprintf( esc_html__( 'Successfully created %d default pages.', WP_ULIKE_PRO_DOMAIN ), $count )
        );
    } else {
        // delete pages
        $pages = get_posts( array(
            'post_type'      => 'page',
            'posts_per_page' => -1,
            'meta_query'     => array(
                array(
                    'key'     => '_wp_ulike_pro_core',
                    'compare' => 'EXISTS',
                )
            )
        ) );
        $count = 0;
        if( ! empty( $pages ) ) {
            $count = count( $pages );
            foreach ( $pages as $page ) {
                // Permanently delete the page
                wp_delete_post( $page->ID, true );
            }
        }
        return array(
            'success' => true,
            'rows_affected' => $count,
            'message' => sprintf( esc_html__( 'Successfully deleted %d default pages.', WP_ULIKE_PRO_DOMAIN ), $count )
        );
    }
}

/**
 * Update "flush" option for reset rules on wp_loaded hook
 *
 * @return void
 */
function wp_ulike_pro_reset_rules() {
    update_option( 'wp_ulike_pro_flush_rewrite_rules', 1 );
}

/**
 * Clear all cache
 *
 * @param string $type Optional - not used, kept for compatibility with AJAX handler
 * @return boolean
 */
function wp_ulike_pro_clear_all_cache( $type = '' ) {
	if( ! current_user_can( 'manage_options' ) ){
		return false;
	}

	$cache_plugins = array();

	// Clear WordPress object cache for WP ULike
	if( function_exists( 'wp_cache_flush_group' ) ){
		wp_cache_flush_group( WP_ULIKE_SLUG );
		wp_cache_flush_group( WP_ULIKE_PRO_DOMAIN );
		wp_cache_flush_group( 'ulike_session_id' );
	} else {
		// Fallback: clear specific cache keys
		wp_cache_delete( 'calculate_new_votes', WP_ULIKE_SLUG );
		wp_cache_delete( 'count_logs_period_all', WP_ULIKE_SLUG );
		wp_cache_delete( 1, 'wp_ulike_statistics_meta' );
	}

	// Clear third-party cache plugins
	if( class_exists( 'wp_ulike_purge_cache' ) ){
		$purge_cache = new wp_ulike_purge_cache();
		$purge_cache->purgeAll();
		// Detect which cache plugins are active
		$detected_plugins = array();
		if( class_exists( 'WP_Rocket' ) ) $detected_plugins[] = 'WP Rocket';
		if( class_exists( 'W3_TotalCache' ) ) $detected_plugins[] = 'W3 Total Cache';
		if( defined( 'LSCWP_V' ) ) $detected_plugins[] = 'LiteSpeed Cache';
		if( class_exists( 'WP_Super_Cache' ) ) $detected_plugins[] = 'WP Super Cache';
		if( class_exists( 'WPO_Page_Cache' ) ) $detected_plugins[] = 'WP Optimize';
		if( class_exists( 'WpFastestCache' ) ) $detected_plugins[] = 'WP Fastest Cache';
		$cache_plugins = $detected_plugins;
	}

	$plugin_list = ! empty( $cache_plugins ) ? ' (' . implode( ', ', $cache_plugins ) . ')' : '';
	return array(
        'success' => true,
        'rows_affected' => 0,
        'message' => esc_html__( 'Successfully cleared all cache', WP_ULIKE_PRO_DOMAIN ) . $plugin_list . '.'
    );
}

/**
 * Clear all transients
 *
 * @param string $type Optional - not used, kept for compatibility with AJAX handler
 * @return boolean
 */
function wp_ulike_pro_clear_transients( $type = '' ) {
	global $wpdb;

	if( ! current_user_can( 'manage_options' ) ){
		return false;
	}

	// Get count before deletion - $wpdb->options is WordPress core table, safe but use esc_sql for consistency
	$options_table = esc_sql( $wpdb->options );
	$patterns = array(
		'_transient_wp_ulike%',
		'_transient_timeout_wp_ulike%',
		'_transient_ulp_rate_limit_%',
		'_transient_timeout_ulp_rate_limit_%',
		'_transient_wp-ulike-%',
		'_transient_timeout_wp-ulike-%'
	);

	// Build WHERE clause with OR conditions
	$where_conditions = array();
	$prepared_values = array();
	foreach( $patterns as $pattern ){
		$where_conditions[] = "option_name LIKE %s";
		$prepared_values[] = $pattern;
	}
	$where_clause = implode( ' OR ', $where_conditions );

	$count_query = $wpdb->prepare(
		"SELECT COUNT(*) FROM `" . $options_table . "` WHERE " . $where_clause,
		$prepared_values
	);
	$count = (int) $wpdb->get_var( $count_query );

	// Delete all WP ULike transients - use same pattern for each
	foreach( $patterns as $pattern ){
		$wpdb->query( $wpdb->prepare( "DELETE FROM `" . $options_table . "` WHERE option_name LIKE %s", $pattern ) );
	}

	// Delete specific options (not transients)
	$options_to_delete = array( 'public_server_ip' );
	foreach( $options_to_delete as $option_name ){
		delete_option( $option_name );
		$count++;
	}

	return array(
        'success' => true,
        'rows_affected' => $count,
        'message' => sprintf( esc_html__( 'Successfully cleared %d cached statistics entries.', WP_ULIKE_PRO_DOMAIN ), $count )
    );
}

/**
 * Delete view tracking records by content type
 *
 * @param string $type Content type (post, comment, activity, topic)
 * @return array Result with success status and count
 */
function wp_ulike_pro_delete_views( $type ) {
	if( ! current_user_can( 'manage_options' ) ){
		return false;
	}

	$views = WP_Ulike_Pro_Views::get_instance();
	return $views->delete_views_by_type( $type );
}

/**
 * Cleanup expired sessions
 *
 * @param string $type Optional - not used, kept for compatibility with AJAX handler
 * @return boolean
 */
function wp_ulike_pro_cleanup_sessions( $type = '' ) {
	global $wpdb;

	if( ! current_user_can( 'manage_options' ) ){
		return false;
	}

	if( class_exists( 'WP_Ulike_Pro_Session_Handler' ) ){
		// Get count before cleanup
		$table_name = $wpdb->prefix . 'ulike_sessions';
		$count_query = sprintf( "
			SELECT COUNT(*) FROM `%s` WHERE session_expiry < %d",
			esc_sql( $table_name ),
			time()
		);
		$count = (int) $wpdb->get_var( $count_query );

		$session_handler = new WP_Ulike_Pro_Session_Handler();
		$session_handler->cleanup_sessions();

		return array(
			'success' => true,
			'rows_affected' => $count,
			'message' => sprintf( esc_html__( 'Successfully cleaned up %d expired sessions.', WP_ULIKE_PRO_DOMAIN ), $count )
		);
	}

	return false;
}

/**
 * Recalculate all counters from logs
 *
 * @param string $type
 * @return array|boolean
 */
function wp_ulike_pro_recalculate_counters( $type ) {
	global $wpdb;

	if( ! current_user_can( 'manage_options' ) ){
		return false;
	}

	// If type is 'all', recalculate for all types
	if( $type === 'all' ){
		$types = array( 'post', 'comment', 'activity', 'topic' );
		$total_count = 0;
		$type_messages = array();
		foreach ( $types as $t ) {
			$result = wp_ulike_pro_recalculate_counters( $t );
			if( is_array( $result ) && $result['success'] ){
				$total_count += $result['rows_affected'];
				$type_messages[] = $result['message'];
			}
		}
		return array(
			'success' => true,
			'rows_affected' => $total_count,
			'message' => sprintf( esc_html__( 'Successfully recalculated counters for %d items across all content types.', WP_ULIKE_PRO_DOMAIN ), $total_count )
		);
	}

	// Validate type parameter
	$allowed_types = array( 'post', 'comment', 'activity', 'topic' );
	if( ! in_array( $type, $allowed_types, true ) ){
		return false;
	}

	$info_args = wp_ulike_get_table_info( $type );
	if( empty( $info_args ) || ! isset( $info_args['table'] ) || ! isset( $info_args['column'] ) ){
		return false;
	}

	// Validate table name
	$allowed_tables = array( 'ulike', 'ulike_meta', 'ulike_activities', 'ulike_comments', 'ulike_forums' );
	if( ! in_array( $info_args['table'], $allowed_tables, true ) ){
		return false;
	}

	$table_name = $wpdb->prefix . $info_args['table'];
	$is_distinct = wp_ulike_setting_repo::isDistinct( $type );
	$column = esc_sql( $info_args['column'] );
	$meta_table = $wpdb->prefix . 'ulike_meta';

	// Get all unique items from vote logs - table and column are validated
	$items_from_logs = $wpdb->get_col( sprintf( "SELECT DISTINCT `%s` FROM `%s`", $column, esc_sql( $table_name ) ) );

	// Also get items that have meta data but might not have votes (to sync them to 0)
	$items_from_meta = array();
	$meta_exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $meta_table ) ) == $meta_table;
	if( $meta_exists ){
		// Query to find items with counter or likers_list meta
		// Escape all parts properly - table name, column names, and values
		$meta_table_escaped = esc_sql( $meta_table );
		$type_escaped = esc_sql( $type );
		$count_pattern = '%' . $wpdb->esc_like( 'count_' ) . '%';
		$likers_list_value = 'likers_list';

		// Use prepare for values, escape table/column names
		$meta_query = $wpdb->prepare(
			"SELECT DISTINCT `item_id` FROM `{$meta_table_escaped}` WHERE `meta_group` = %s AND ( `meta_key` LIKE %s OR `meta_key` = %s )",
			$type_escaped,
			$count_pattern,
			$likers_list_value
		);
		$meta_items = $wpdb->get_col( $meta_query );
		if( ! empty( $meta_items ) ){
			$items_from_meta = array_map( 'absint', $meta_items );
		}
	}

	// Merge both lists and remove duplicates
	$items = array_unique( array_merge( $items_from_logs, $items_from_meta ) );
	$items = array_map( 'absint', $items );
	$items = array_filter( $items, function( $id ) { return $id > 0; } );

	if( empty( $items ) ){
		$type_label = ucfirst( $type );
		return array(
			'success' => true,
			'rows_affected' => 0,
			'message' => sprintf( esc_html__( 'No items found to recalculate for %s.', WP_ULIKE_PRO_DOMAIN ), $type_label )
		);
	}

	$start_time = time();
	$recalculated_count = 0;
	$batch_size = 100; // Process 100 items at a time

	// Process in batches to avoid timeouts
	$total_items = count( $items );
	$processed = 0;

	foreach ( $items as $item_id ) {
		// Check if we should continue
		if( ! wp_ulike_pro_can_continue_operation( $start_time ) ){
			break;
		}

		$item_id = absint( $item_id );

		// Determine which meta key to use based on distinct setting
		$meta_key_prefix = $is_distinct ? 'count_distinct_' : 'count_total_';

		// Count likes - use appropriate count based on distinct setting
		if( $is_distinct ){
			$like_count = (int) $wpdb->get_var( sprintf(
				"SELECT COUNT(DISTINCT `user_id`) FROM `%s` WHERE `%s` = %d AND `status` = 'like'",
				esc_sql( $table_name ),
				$column,
				$item_id
			) );
		} else {
			$like_count = (int) $wpdb->get_var( sprintf(
				"SELECT COUNT(*) FROM `%s` WHERE `%s` = %d AND `status` = 'like'",
				esc_sql( $table_name ),
				$column,
				$item_id
			) );
		}

		// Count dislikes - use appropriate count based on distinct setting
		if( $is_distinct ){
			$dislike_count = (int) $wpdb->get_var( sprintf(
				"SELECT COUNT(DISTINCT `user_id`) FROM `%s` WHERE `%s` = %d AND `status` = 'dislike'",
				esc_sql( $table_name ),
				$column,
				$item_id
			) );
		} else {
			$dislike_count = (int) $wpdb->get_var( sprintf(
				"SELECT COUNT(*) FROM `%s` WHERE `%s` = %d AND `status` = 'dislike'",
				esc_sql( $table_name ),
				$column,
				$item_id
			) );
		}

		// Get actual likers list from table
		$likers = $wpdb->get_col( sprintf(
			"SELECT DISTINCT `user_id` FROM `%s` WHERE `%s` = %d AND `status` = 'like'",
			esc_sql( $table_name ),
			$column,
			$item_id
		) );
		$likers_list = ! empty( $likers ) ? array_map( 'absint', $likers ) : array();

		// Update meta counters based on distinct setting
		wp_ulike_update_meta_data( $item_id, $type, $meta_key_prefix . 'like', $like_count );
		wp_ulike_update_meta_data( $item_id, $type, $meta_key_prefix . 'dislike', $dislike_count );

		// Update likers_list
		wp_ulike_update_meta_data( $item_id, $type, 'likers_list', $likers_list );

		// Update post/comment meta if applicable
		if( in_array( $type, array( 'post', 'comment' ) ) ){
			update_metadata( $type, $item_id, 'like_amount', $like_count );
			update_metadata( $type, $item_id, 'dislike_amount', $dislike_count );
			update_metadata( $type, $item_id, 'net_votes', ( $like_count - $dislike_count ) );
		}

		$recalculated_count++;
		$processed++;

		// Clear object cache periodically to prevent memory issues
		if( $processed % $batch_size === 0 ){
			wp_cache_flush_group( WP_ULIKE_SLUG );
		}
	}

	// If operation was interrupted, return partial success
	if( $processed < $total_items && ! wp_ulike_pro_can_continue_operation( $start_time ) ){
		$type_label = ucfirst( $type );
		return array(
			'success' => true,
			'rows_affected' => $recalculated_count,
			'message' => sprintf( esc_html__( 'Partially completed: recalculated %d of %d items for %s. Please run again to continue.', WP_ULIKE_PRO_DOMAIN ), $recalculated_count, $total_items, $type_label )
		);
	}

	$type_label = ucfirst( $type );
	return array(
		'success' => true,
		'rows_affected' => $recalculated_count,
		'message' => sprintf( esc_html__( 'Successfully recalculated counters for %d %s items.', WP_ULIKE_PRO_DOMAIN ), $recalculated_count, $type_label )
	);
}

/**
 * Repair all tables
 *
 * @param string $type Optional - not used, kept for compatibility with AJAX handler
 * @return boolean
 */
function wp_ulike_pro_repair_tables( $type = '' ) {
	global $wpdb;

	if( ! current_user_can( 'manage_options' ) ){
		return false;
	}

	// Whitelist of allowed tables
	$tables = array( 'ulike', 'ulike_meta', 'ulike_activities', 'ulike_comments', 'ulike_forums', 'ulike_sessions', 'ulike_views' );
	$repaired_count = 0;

	foreach ( $tables as $table ) {
		// Validate table name is in whitelist
		if( ! in_array( $table, $tables, true ) ){
			continue;
		}

		$table_name = $wpdb->prefix . $table;
		$exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) ) == $table_name;
		if( $exists ){
			// Table name is validated above
			$wpdb->query( sprintf( "REPAIR TABLE `%s`", esc_sql( $table_name ) ) );
			$repaired_count++;
		}
	}

	return array(
		'success' => true,
		'rows_affected' => $repaired_count,
		'message' => sprintf( esc_html__( 'Successfully repaired %d database tables.', WP_ULIKE_PRO_DOMAIN ), $repaired_count )
	);
}

/**
 * Analyze all tables
 *
 * @param string $type Optional - not used, kept for compatibility with AJAX handler
 * @return boolean
 */
function wp_ulike_pro_analyze_tables( $type = '' ) {
	global $wpdb;

	if( ! current_user_can( 'manage_options' ) ){
		return false;
	}

	// Whitelist of allowed tables
	$tables = array( 'ulike', 'ulike_meta', 'ulike_activities', 'ulike_comments', 'ulike_forums', 'ulike_sessions', 'ulike_views' );
	$analyzed_count = 0;

	foreach ( $tables as $table ) {
		// Validate table name is in whitelist
		if( ! in_array( $table, $tables, true ) ){
			continue;
		}

		$table_name = $wpdb->prefix . $table;
		$exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) ) == $table_name;
		if( $exists ){
			// Table name is validated above
			$wpdb->query( sprintf( "ANALYZE TABLE `%s`", esc_sql( $table_name ) ) );
			$analyzed_count++;
		}
	}

	return array(
		'success' => true,
		'rows_affected' => $analyzed_count,
		'message' => sprintf( esc_html__( 'Successfully analyzed %d database tables.', WP_ULIKE_PRO_DOMAIN ), $analyzed_count )
	);
}

/**
 * Sync database table indexes
 *
 * @param string $type Optional - not used, kept for compatibility with AJAX handler
 * @return array|false
 */
function wp_ulike_pro_sync_indexes( $type = '' ) {
	global $wpdb;

	if( ! current_user_can( 'manage_options' ) ){
        return false;
	}

	// Initialize variables
	$max_index_length = 191;
	$synced_count = 0;
	$indexes_added = 0;
	$errors = array();

	// Define expected indexes for each table based on activator
	$expected_indexes = array(
		'ulike' => array(
			'post_id' => 'KEY `post_id` (`post_id`)',
			'user_id' => 'KEY `user_id` (`user_id`)',
			'date_time' => 'KEY `date_time` (`date_time`)',
			'status' => 'KEY `status` (`status`)',
			'fingerprint' => 'KEY `fingerprint` (`fingerprint`)',
			'post_id_user_id' => 'KEY `post_id_user_id` (`post_id`, `user_id`)',
			'post_id_status' => 'KEY `post_id_status` (`post_id`, `status`)',
			'post_id_fingerprint' => 'KEY `post_id_fingerprint` (`post_id`, `fingerprint`)',
			'user_id_status' => 'KEY `user_id_status` (`user_id`, `status`)',
			'user_id_status_date_time' => 'KEY `user_id_status_date_time` (`user_id`, `status`, `date_time`)',
			'status_date_time' => 'KEY `status_date_time` (`status`, `date_time`)',
			'post_id_date_time_user_id_status' => 'KEY `post_id_date_time_user_id_status` (`post_id`, `date_time`, `user_id`, `status`)'
		),
		'ulike_comments' => array(
			'comment_id' => 'KEY `comment_id` (`comment_id`)',
			'user_id' => 'KEY `user_id` (`user_id`)',
			'date_time' => 'KEY `date_time` (`date_time`)',
			'status' => 'KEY `status` (`status`)',
			'fingerprint' => 'KEY `fingerprint` (`fingerprint`)',
			'comment_id_user_id' => 'KEY `comment_id_user_id` (`comment_id`, `user_id`)',
			'comment_id_status' => 'KEY `comment_id_status` (`comment_id`, `status`)',
			'comment_id_fingerprint' => 'KEY `comment_id_fingerprint` (`comment_id`, `fingerprint`)',
			'user_id_status' => 'KEY `user_id_status` (`user_id`, `status`)',
			'user_id_status_date_time' => 'KEY `user_id_status_date_time` (`user_id`, `status`, `date_time`)',
			'status_date_time' => 'KEY `status_date_time` (`status`, `date_time`)',
			'comment_id_date_time_user_id_status' => 'KEY `comment_id_date_time_user_id_status` (`comment_id`, `date_time`, `user_id`, `status`)'
		),
		'ulike_activities' => array(
			'activity_id' => 'KEY `activity_id` (`activity_id`)',
			'user_id' => 'KEY `user_id` (`user_id`)',
			'date_time' => 'KEY `date_time` (`date_time`)',
			'status' => 'KEY `status` (`status`)',
			'fingerprint' => 'KEY `fingerprint` (`fingerprint`)',
			'activity_id_user_id' => 'KEY `activity_id_user_id` (`activity_id`, `user_id`)',
			'activity_id_status' => 'KEY `activity_id_status` (`activity_id`, `status`)',
			'activity_id_fingerprint' => 'KEY `activity_id_fingerprint` (`activity_id`, `fingerprint`)',
			'user_id_status' => 'KEY `user_id_status` (`user_id`, `status`)',
			'user_id_status_date_time' => 'KEY `user_id_status_date_time` (`user_id`, `status`, `date_time`)',
			'status_date_time' => 'KEY `status_date_time` (`status`, `date_time`)',
			'activity_id_date_time_user_id_status' => 'KEY `activity_id_date_time_user_id_status` (`activity_id`, `date_time`, `user_id`, `status`)'
		),
		'ulike_forums' => array(
			'topic_id' => 'KEY `topic_id` (`topic_id`)',
			'user_id' => 'KEY `user_id` (`user_id`)',
			'date_time' => 'KEY `date_time` (`date_time`)',
			'status' => 'KEY `status` (`status`)',
			'fingerprint' => 'KEY `fingerprint` (`fingerprint`)',
			'topic_id_user_id' => 'KEY `topic_id_user_id` (`topic_id`, `user_id`)',
			'topic_id_status' => 'KEY `topic_id_status` (`topic_id`, `status`)',
			'topic_id_fingerprint' => 'KEY `topic_id_fingerprint` (`topic_id`, `fingerprint`)',
			'user_id_status' => 'KEY `user_id_status` (`user_id`, `status`)',
			'user_id_status_date_time' => 'KEY `user_id_status_date_time` (`user_id`, `status`, `date_time`)',
			'status_date_time' => 'KEY `status_date_time` (`status`, `date_time`)',
			'topic_id_date_time_user_id_status' => 'KEY `topic_id_date_time_user_id_status` (`topic_id`, `date_time`, `user_id`, `status`)'
		),
		'ulike_meta' => array(
			'item_id' => 'KEY `item_id` (`item_id`)',
			'meta_key' => 'KEY `meta_key` (`meta_key`(' . $max_index_length . '))',
			'item_id_meta_group' => 'KEY `item_id_meta_group` (`item_id`, `meta_group`)',
			'meta_group_meta_key_item_id' => 'KEY `meta_group_meta_key_item_id` (`meta_group`, `meta_key`, `item_id`)'
		),
		'ulike_sessions' => array(
			'session_key' => 'UNIQUE KEY `session_key` (`session_key`)',
			'session_expiry' => 'KEY `session_expiry` (`session_expiry`)'
		),
		'ulike_views' => array(
			'unique_view' => 'UNIQUE KEY `unique_view` (`item_id`, `type`, `view_date`)',
			'idx_item_type' => 'KEY `idx_item_type` (`item_id`, `type`)',
			'idx_view_date' => 'KEY `idx_view_date` (`view_date`)',
			'idx_type_date' => 'KEY `idx_type_date` (`type`, `view_date`)',
			'idx_item_date' => 'KEY `idx_item_date` (`item_id`, `view_date`)'
		)
	);

	// Whitelist of allowed tables
	$tables = array( 'ulike', 'ulike_meta', 'ulike_activities', 'ulike_comments', 'ulike_forums', 'ulike_sessions', 'ulike_views' );

	foreach ( $tables as $table ) {
		// Validate table name is in whitelist
		if( ! in_array( $table, $tables, true ) ){
			continue;
		}

		$table_name = $wpdb->prefix . $table;
		$exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) ) == $table_name;

		if( ! $exists ){
			continue;
		}

		// Get existing columns to check if required columns exist
		$existing_columns = array();
		$wpdb->last_error = '';
		$column_results = $wpdb->get_results( sprintf( "SHOW COLUMNS FROM `%s`", esc_sql( $table_name ) ), ARRAY_A );
		if( ! empty( $wpdb->last_error ) ){
			$errors[] = sprintf( esc_html__( 'Failed to get columns for table %s: %s', WP_ULIKE_PRO_DOMAIN ), $table, $wpdb->last_error );
			continue;
		}
		if( $column_results ){
			foreach( $column_results as $column ){
				$field_name = isset( $column['Field'] ) ? $column['Field'] : '';
				if( ! empty( $field_name ) ){
					$existing_columns[ $field_name ] = true;
				}
			}
		}

		// Get existing indexes
		$existing_indexes = array();
		// Clear any previous errors
		$wpdb->last_error = '';
		$index_query = sprintf( "SHOW INDEX FROM `%s`", esc_sql( $table_name ) );
		$index_results = $wpdb->get_results( $index_query, ARRAY_A );

		// Check for query errors
		if( ! empty( $wpdb->last_error ) ){
			$errors[] = sprintf( esc_html__( 'Failed to get indexes for table %s: %s', WP_ULIKE_PRO_DOMAIN ), $table, $wpdb->last_error );
			continue;
		}

		if( $index_results ){
			foreach( $index_results as $index ){
				$key_name = isset( $index['Key_name'] ) ? $index['Key_name'] : '';
				// Skip PRIMARY key
				if( $key_name !== 'PRIMARY' && ! empty( $key_name ) ){
					$existing_indexes[ $key_name ] = true;
				}
			}
		}

		// Check if table has expected indexes
		if( ! isset( $expected_indexes[ $table ] ) ){
			continue;
		}

		$table_indexes = $expected_indexes[ $table ];
		$table_synced = false;

		// Add missing indexes
		foreach( $table_indexes as $index_name => $index_definition ){
			// Check if index already exists
			if( isset( $existing_indexes[ $index_name ] ) ){
				continue; // Index already exists, skip
			}

			// Extract column names from index definition to check if columns exist
			preg_match( '/\(([^)]+)\)/', $index_definition, $matches );
			if( ! empty( $matches[1] ) ){
				$index_columns = array_map( 'trim', explode( ',', $matches[1] ) );
				$columns_exist = true;
				foreach( $index_columns as $col ){
					// Remove backticks, spaces, and length specifiers (e.g., `meta_key`(191) or `post_id`)
					// First remove backticks, then remove length specifiers like (191)
					$col_name = trim( $col, '`' ); // Remove backticks from start and end
					$col_name = preg_replace( '/\s*\([^)]*\)\s*$/', '', $col_name ); // Remove length specifiers like (191)
					$col_name = trim( $col_name );

					if( empty( $col_name ) || ! isset( $existing_columns[ $col_name ] ) ){
						$columns_exist = false;
						break;
					}
				}
				if( ! $columns_exist ){
					// Skip this index if required columns don't exist - but add debug info
					$missing_cols = array();
					foreach( $index_columns as $col ){
						$col_name = trim( $col, '`' );
						$col_name = preg_replace( '/\s*\([^)]*\)\s*$/', '', $col_name );
						$col_name = trim( $col_name );
						if( empty( $col_name ) || ! isset( $existing_columns[ $col_name ] ) ){
							$missing_cols[] = $col_name;
						}
					}
					$errors[] = sprintf( esc_html__( 'Skipped index %s for table %s: Required columns do not exist (%s). Available columns: %s', WP_ULIKE_PRO_DOMAIN ), $index_name, $table, implode( ', ', $missing_cols ), implode( ', ', array_keys( $existing_columns ) ) );
					continue;
				}
			}

			// Build ALTER TABLE query - index_definition already includes KEY or UNIQUE KEY
			$query = sprintf( "ALTER TABLE `%s` ADD %s", esc_sql( $table_name ), $index_definition );

			// Execute query
			$wpdb->last_error = '';
			$result = $wpdb->query( $query );
			$error_msg = $wpdb->last_error;

			// Check result - ALTER TABLE returns false on error, or 0/1 on success
			if( $result !== false && empty( $error_msg ) ){
				// Query succeeded - verify index was actually added by re-checking all indexes
				$wpdb->last_error = '';
				$verify_query = sprintf( "SHOW INDEX FROM `%s`", esc_sql( $table_name ) );
				$verify_results = $wpdb->get_results( $verify_query, ARRAY_A );
				$verify_error = $wpdb->last_error;

				// Check if our index name exists in the results
				$index_found = false;
				if( ! empty( $verify_results ) && is_array( $verify_results ) && empty( $verify_error ) ){
					foreach( $verify_results as $verify_index ){
						if( isset( $verify_index['Key_name'] ) && $verify_index['Key_name'] === $index_name ){
							$index_found = true;
							break;
						}
					}
				}

				if( $index_found ){
					// Index was successfully added
					$indexes_added++;
					$table_synced = true;
					$existing_indexes[ $index_name ] = true; // Update for this run
				} else {
					// Query succeeded but index not found - report error
					$errors[] = sprintf( esc_html__( 'Index %s query succeeded for table %s but index was not found after creation. Verify error: %s', WP_ULIKE_PRO_DOMAIN ), $index_name, $table, $verify_error ? $verify_error : 'None' );
				}
			} elseif( ! empty( $error_msg ) ){
				// Query failed - check if it's a duplicate error (index already exists)
				$error_lower = strtolower( $error_msg );
				$is_duplicate = (
					strpos( $error_lower, 'duplicate key name' ) !== false ||
					strpos( $error_lower, 'already exists' ) !== false ||
					strpos( $error_lower, 'duplicate entry' ) !== false ||
					strpos( $error_lower, 'duplicate' ) !== false
				);

				if( ! $is_duplicate ){
					// Real error - report it with query for debugging
					$errors[] = sprintf( esc_html__( 'Failed to add index %s to table %s: %s. Query: %s', WP_ULIKE_PRO_DOMAIN ), $index_name, $table, $error_msg, $query );
				}
				// If duplicate, silently skip (index already exists)
			} else {
				// Query returned false with no error - unusual, include query for debugging
				$errors[] = sprintf( esc_html__( 'Failed to add index %s to table %s: Query returned false with no error message. Query: %s', WP_ULIKE_PRO_DOMAIN ), $index_name, $table, $query );
			}
		}

		if( $table_synced ){
			$synced_count++;
		}
	}

	// Build message
	$message = '';
	if( $indexes_added > 0 ){
		$message = sprintf( esc_html__( 'Successfully synced indexes for %d tables. Added %d new indexes.', WP_ULIKE_PRO_DOMAIN ), $synced_count, $indexes_added );
	} else {
		$message = esc_html__( 'All indexes are already in sync. No changes were needed.', WP_ULIKE_PRO_DOMAIN );
	}

	if( ! empty( $errors ) ){
		$error_text = esc_html__( 'Index sync completed with errors:', WP_ULIKE_PRO_DOMAIN ) . ' ' . implode( '; ', $errors );
		if( $indexes_added > 0 ){
			$error_text .= ' ' . sprintf( esc_html__( 'However, %d indexes were successfully added.', WP_ULIKE_PRO_DOMAIN ), $indexes_added );
		}
		$message = $error_text;
	}

	// Always return array format - never return false
	// If there are errors, return success=false even if some indexes were added
	return array(
		'success' => empty( $errors ) && $indexes_added >= 0,
		'rows_affected' => $indexes_added,
		'message' => $message,
		'errors' => $errors
	);
}

/**
 * Remove votes by user ID(s) - GDPR compliance
 *
 * @param string|array $user_ids User ID(s) - can be comma-separated string or array
 * @return array|false
 */
function wp_ulike_pro_remove_user_votes( $user_ids ) {
	global $wpdb;

	if( ! current_user_can( 'manage_options' ) ){
		return false;
	}

	// Convert to array if string
	if( is_string( $user_ids ) ){
		$user_ids = array_map( 'trim', explode( ',', $user_ids ) );
	}

	if( ! is_array( $user_ids ) || empty( $user_ids ) ){
		return false;
	}

	// Sanitize and validate user IDs
	$user_ids = array_map( 'absint', $user_ids );
	$user_ids = array_filter( $user_ids, function( $id ) {
		return $id > 0;
	});

	if( empty( $user_ids ) ){
		return false;
	}

	$start_time = time();
	$total_deleted = 0;
	$tables_processed = array();
	$affected_items = array(); // Track items that need counter sync

	// Types to process - use wp_ulike_get_table_info for each
	$types = array( 'post', 'comment', 'activity', 'topic' );

	// Process each content type
	foreach( $types as $type ){
		if( ! wp_ulike_pro_can_continue_operation( $start_time ) ){
			break;
		}

		// Get table info using existing function
		$info_args = wp_ulike_get_table_info( $type );
		if( empty( $info_args ) || ! isset( $info_args['table'] ) || ! isset( $info_args['column'] ) ){
			continue;
		}

		// Validate table name
		$allowed_tables = array( 'ulike', 'ulike_meta', 'ulike_activities', 'ulike_comments', 'ulike_forums' );
		if( ! in_array( $info_args['table'], $allowed_tables, true ) ){
			continue;
		}

		$table_name = $wpdb->prefix . $info_args['table'];

		// Check if table exists
		$exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) ) == $table_name;
		if( ! $exists ){
			continue;
		}

		// Get count before deletion and track affected items
		$column = esc_sql( $info_args['column'] );
		$placeholders = implode( ',', array_fill( 0, count( $user_ids ), '%d' ) );
		$count_query = $wpdb->prepare(
			"SELECT COUNT(*) FROM `" . esc_sql( $table_name ) . "` WHERE `user_id` IN ($placeholders)",
			$user_ids
		);
		$count = (int) $wpdb->get_var( $count_query );

		// Track affected items BEFORE deletion for counter sync
		if( $count > 0 ){
			$affected_items_query = $wpdb->prepare(
				"SELECT DISTINCT `" . $column . "` FROM `" . esc_sql( $table_name ) . "` WHERE `user_id` IN ($placeholders)",
				$user_ids
			);
			$items = $wpdb->get_col( $affected_items_query );
			if( ! empty( $items ) ){
				if( ! isset( $affected_items[ $type ] ) ){
					$affected_items[ $type ] = array();
				}
				$affected_items[ $type ] = array_merge( $affected_items[ $type ], array_map( 'absint', $items ) );
				$affected_items[ $type ] = array_unique( $affected_items[ $type ] );
			}
		}

		if( $count > 0 ){
			// Delete in batches
			$batch_size = 1000;
			$deleted = 0;

			while( $deleted < $count && wp_ulike_pro_can_continue_operation( $start_time ) ){
				$delete_query = $wpdb->prepare(
					"DELETE FROM `" . esc_sql( $table_name ) . "` WHERE `user_id` IN ($placeholders) LIMIT %d",
					array_merge( $user_ids, array( $batch_size ) )
				);

				$result = $wpdb->query( $delete_query );
				if( $result === false ){
					break;
				}

				$deleted += $result;
				if( $result === 0 ){
					break;
				}
			}

			$total_deleted += $deleted;
			$tables_processed[ $info_args['table'] ] = $deleted;
		}
	}

	// Also delete from ulike_meta if user_id is stored there
	$meta_table = $wpdb->prefix . 'ulike_meta';
	$meta_exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $meta_table ) ) == $meta_table;
	if( $meta_exists && wp_ulike_pro_can_continue_operation( $start_time ) ){
		// Get all user meta entries first
		$placeholders = implode( ',', array_fill( 0, count( $user_ids ), '%d' ) );
		$meta_query = $wpdb->prepare(
			"SELECT * FROM `" . esc_sql( $meta_table ) . "` WHERE `meta_group` = 'user' AND `item_id` IN ($placeholders)",
			$user_ids
		);
		$meta_entries = $wpdb->get_results( $meta_query );

		$meta_count = 0;
		if( ! empty( $meta_entries ) ){
			// Delete each meta entry using the correct function signature
			foreach( $meta_entries as $meta_entry ){
				wp_ulike_delete_meta_data( 'user', $meta_entry->item_id, $meta_entry->meta_key );
				$meta_count++;
			}
		}

		if( $meta_count > 0 ){
			$tables_processed['ulike_meta'] = $meta_count;
		}
	}

	// Update likers_list only - remove deleted user IDs from likers_list entries using SQL
	// Fast approach: Use SQL LIKE to find entries containing user IDs, then update only those
	if( $total_deleted > 0 ){
		$meta_table = $wpdb->prefix . 'ulike_meta';
		$meta_exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $meta_table ) ) == $meta_table;

		if( $meta_exists ){
			$types = array( 'post', 'comment', 'activity', 'topic' );
			$updated_count = 0;

			foreach( $types as $type ){
				if( ! wp_ulike_pro_can_continue_operation( $start_time ) ){
					break;
				}

				// Build SQL LIKE patterns for each user ID in serialized format
				// Serialized arrays contain: i:USER_ID; (for integers)
				$like_patterns = array();
				$like_values = array();
				foreach( $user_ids as $user_id ){
					// Match serialized integer: i:USER_ID;
					$pattern = '%' . $wpdb->esc_like( 'i:' . $user_id . ';' ) . '%';
					$like_patterns[] = "`meta_value` LIKE %s";
					$like_values[] = $pattern;
				}

				if( empty( $like_patterns ) ){
					continue;
				}

				// Build WHERE clause with OR conditions for each user ID pattern
				$where_clause = implode( ' OR ', $like_patterns );

				// Get only likers_list entries that contain any of the deleted user IDs
				// Prepare query with type and all LIKE patterns
				$prepared_values = array_merge( array( $type ), $like_values );
				$likers_list_query = $wpdb->prepare(
					"SELECT `item_id`, `meta_value` FROM `" . esc_sql( $meta_table ) . "` WHERE `meta_group` = %s AND `meta_key` = 'likers_list' AND (" . $where_clause . ")",
					$prepared_values
				);
				$likers_entries = $wpdb->get_results( $likers_list_query );

				if( empty( $likers_entries ) ){
					continue;
				}

				// Process each affected likers_list entry
				foreach( $likers_entries as $entry ){
					if( ! wp_ulike_pro_can_continue_operation( $start_time ) ){
						break 2;
					}

					$item_id = absint( $entry->item_id );
					$likers_list = maybe_unserialize( $entry->meta_value );

					// Skip if not an array or doesn't contain any deleted user IDs
					if( ! is_array( $likers_list ) || empty( array_intersect( $user_ids, $likers_list ) ) ){
						continue;
					}

					// Remove deleted user IDs from likers_list
					$likers_list = array_diff( $likers_list, $user_ids );
					$likers_list = array_values( array_map( 'absint', $likers_list ) ); // Re-index and sanitize

					// Update only likers_list - no counter recalculation
					wp_ulike_update_meta_data( $item_id, $type, 'likers_list', $likers_list );

					$updated_count++;
				}
			}
		}
	}

	// Get user display names for message
	$user_names = array();
	foreach( $user_ids as $user_id ){
		$user = get_user_by( 'id', $user_id );
		if( $user ){
			$user_names[] = $user->display_name . ' (ID: ' . $user_id . ')';
		} else {
			$user_names[] = 'ID: ' . $user_id;
		}
	}

	$message = sprintf(
		esc_html__( 'Successfully removed %d votes for %d user(s): %s.', WP_ULIKE_PRO_DOMAIN ),
		$total_deleted,
		count( $user_ids ),
		implode( ', ', $user_names )
	);

	if( ! wp_ulike_pro_can_continue_operation( $start_time ) && $total_deleted > 0 ){
		$message .= ' ' . esc_html__( 'Operation was partially completed due to time limits. Please run "Recalculate All Counters" from Repair & Maintenance if needed.', WP_ULIKE_PRO_DOMAIN );
	}

	return array(
		'success' => true,
		'rows_affected' => $total_deleted,
		'message' => $message,
		'users_processed' => count( $user_ids ),
		'tables' => $tables_processed
	);
}

/**
 * Bulk update likes/dislikes for posts
 *
 * @param array $posts_data Array of post data with id, likes, dislikes
 * @return array|false
 */
function wp_ulike_pro_bulk_update_likes( $posts_data ) {
	global $wpdb;

	if ( ! current_user_can( 'manage_options' ) ) {
		return false;
	}

	// Sanitize and validate posts data
	if ( ! is_array( $posts_data ) || empty( $posts_data ) ) {
		return array(
			'success' => false,
			'message' => esc_html__( 'Invalid posts data provided.', WP_ULIKE_PRO_DOMAIN )
		);
	}

	$processed = 0;
	$errors = array();

	foreach ( $posts_data as $post_data ) {
		if ( ! isset( $post_data['id'] ) ) {
			continue;
		}

		$post_id = absint( $post_data['id'] );
		$new_likes = isset( $post_data['likes'] ) ? absint( $post_data['likes'] ) : 0;
		$new_dislikes = isset( $post_data['dislikes'] ) ? absint( $post_data['dislikes'] ) : 0;
		$type = isset( $post_data['type'] ) ? sanitize_text_field( $post_data['type'] ) : 'post';

		// Validate type
		$allowed_types = array( 'post', 'comment', 'activity', 'topic' );
		if ( ! in_array( $type, $allowed_types, true ) ) {
			$errors[] = sprintf( esc_html__( 'Invalid type for item ID %d.', WP_ULIKE_PRO_DOMAIN ), $post_id );
			continue;
		}

		// For post and comment types, verify they exist (but allow custom IDs)
		if ( $type === 'post' ) {
			$post_exists = get_post( $post_id );
			if ( ! $post_exists ) {
				// Check if it exists in ulike_meta (custom ID)
				global $wpdb;
				$meta_table = $wpdb->prefix . 'ulike_meta';
				$meta_exists = $wpdb->get_var( $wpdb->prepare(
					"SELECT COUNT(*) FROM `" . esc_sql( $meta_table ) . "` WHERE `item_id` = %d AND `meta_group` = %s",
					$post_id,
					$type
				) );
				if ( ! $meta_exists ) {
					$errors[] = sprintf( esc_html__( 'Item ID %d (type: %s) does not exist.', WP_ULIKE_PRO_DOMAIN ), $post_id, $type );
					continue;
				}
			}
		} elseif ( $type === 'comment' ) {
			$comment_exists = get_comment( $post_id );
			if ( ! $comment_exists ) {
				// Check if it exists in ulike_meta (custom ID)
				global $wpdb;
				$meta_table = $wpdb->prefix . 'ulike_meta';
				$meta_exists = $wpdb->get_var( $wpdb->prepare(
					"SELECT COUNT(*) FROM `" . esc_sql( $meta_table ) . "` WHERE `item_id` = %d AND `meta_group` = %s",
					$post_id,
					$type
				) );
				if ( ! $meta_exists ) {
					$errors[] = sprintf( esc_html__( 'Item ID %d (type: %s) does not exist.', WP_ULIKE_PRO_DOMAIN ), $post_id, $type );
					continue;
				}
			}
		}

		// Check if distinct mode is enabled for this type
		$is_distinct = wp_ulike_setting_repo::isDistinct( $type );
		$meta_key_prefix = $is_distinct ? 'count_distinct_' : 'count_total_';

		// Update meta counters
		wp_ulike_update_meta_data( $post_id, $type, $meta_key_prefix . 'like', $new_likes );
		wp_ulike_update_meta_data( $post_id, $type, $meta_key_prefix . 'dislike', $new_dislikes );

		// Update post/comment meta if applicable and item exists
		if ( in_array( $type, array( 'post', 'comment' ) ) ) {
			if ( $type === 'post' && get_post( $post_id ) ) {
				update_metadata( $type, $post_id, 'like_amount', $new_likes );
				update_metadata( $type, $post_id, 'dislike_amount', $new_dislikes );
				update_metadata( $type, $post_id, 'net_votes', ( $new_likes - $new_dislikes ) );
			} elseif ( $type === 'comment' && get_comment( $post_id ) ) {
				update_metadata( $type, $post_id, 'like_amount', $new_likes );
				update_metadata( $type, $post_id, 'dislike_amount', $new_dislikes );
				update_metadata( $type, $post_id, 'net_votes', ( $new_likes - $new_dislikes ) );
			}
			// For custom IDs that don't exist as posts/comments, skip WordPress meta update
		}

		// Update likers_list (keep existing or create empty)
		$current_likers = wp_ulike_get_meta_data( $post_id, $type, 'likers_list', true );
		$likers_list = is_array( $current_likers ) ? $current_likers : array();
		wp_ulike_update_meta_data( $post_id, $type, 'likers_list', $likers_list );

		$processed++;
	}

	$message = sprintf(
		esc_html__( 'Successfully updated %d item(s).', WP_ULIKE_PRO_DOMAIN ),
		$processed
	);

	if ( ! empty( $errors ) ) {
		$message .= ' ' . esc_html__( 'Errors:', WP_ULIKE_PRO_DOMAIN ) . ' ' . implode( ', ', $errors );
	}

	return array(
		'success' => true,
		'rows_affected' => $processed,
		'message' => $message,
		'errors' => $errors
	);
}

/**
 * Bulk add likes/dislikes to posts
 *
 * @param array $post_ids Array of post IDs
 * @param int $likes Number of likes to add
 * @param int $dislikes Number of dislikes to add
 * @return array|false
 */
function wp_ulike_pro_bulk_add_likes( $post_ids, $likes, $dislikes ) {
	global $wpdb;

	if ( ! current_user_can( 'manage_options' ) ) {
		return false;
	}

	// Sanitize and validate post IDs
	if ( ! is_array( $post_ids ) || empty( $post_ids ) ) {
		return array(
			'success' => false,
			'message' => esc_html__( 'Invalid post IDs provided.', WP_ULIKE_PRO_DOMAIN )
		);
	}

	$post_ids = array_map( 'absint', $post_ids );
	$post_ids = array_filter( $post_ids, function( $id ) {
		return $id > 0;
	});

	if ( empty( $post_ids ) ) {
		return array(
			'success' => false,
			'message' => esc_html__( 'No valid post IDs provided.', WP_ULIKE_PRO_DOMAIN )
		);
	}

	$likes = absint( $likes );
	$dislikes = absint( $dislikes );

	if ( $likes === 0 && $dislikes === 0 ) {
		return array(
			'success' => false,
			'message' => esc_html__( 'Please enter at least one like or dislike count.', WP_ULIKE_PRO_DOMAIN )
		);
	}

	$type = 'post';
	$table_name = $wpdb->prefix . 'ulike';
	$processed = 0;
	$errors = array();

	// Check if distinct mode is enabled
	$is_distinct = wp_ulike_setting_repo::isDistinct( $type );
	$meta_key_prefix = $is_distinct ? 'count_distinct_' : 'count_total_';

	// Get current time for date_time
	$current_time = current_time( 'mysql' );

	foreach ( $post_ids as $post_id ) {
		// Verify post exists
		if ( ! get_post( $post_id ) ) {
			$errors[] = sprintf( esc_html__( 'Post ID %d does not exist.', WP_ULIKE_PRO_DOMAIN ), $post_id );
			continue;
		}

		// Get current counts from meta
		$current_like_meta = wp_ulike_get_meta_data( $post_id, $type, $meta_key_prefix . 'like', true );
		$current_dislike_meta = wp_ulike_get_meta_data( $post_id, $type, $meta_key_prefix . 'dislike', true );

		$current_likes = ! empty( $current_like_meta ) ? (int) $current_like_meta : 0;
		$current_dislikes = ! empty( $current_dislike_meta ) ? (int) $current_dislike_meta : 0;

		// Calculate new totals
		$new_likes = $current_likes + $likes;
		$new_dislikes = $current_dislikes + $dislikes;

		// Update meta counters
		wp_ulike_update_meta_data( $post_id, $type, $meta_key_prefix . 'like', $new_likes );
		wp_ulike_update_meta_data( $post_id, $type, $meta_key_prefix . 'dislike', $new_dislikes );

		// Update post meta
		update_metadata( $type, $post_id, 'like_amount', $new_likes );
		update_metadata( $type, $post_id, 'dislike_amount', $new_dislikes );
		update_metadata( $type, $post_id, 'net_votes', ( $new_likes - $new_dislikes ) );

		// Update likers_list if needed (for likes only)
		if ( $likes > 0 ) {
			$current_likers = wp_ulike_get_meta_data( $post_id, $type, 'likers_list', true );
			$likers_list = is_array( $current_likers ) ? $current_likers : array();

			// Add placeholder user IDs (0) for bulk likes - or you could use a specific user ID
			// For now, we'll just update the count without adding actual user IDs to likers_list
			// since this is for initial setup
			wp_ulike_update_meta_data( $post_id, $type, 'likers_list', $likers_list );
		}

		$processed++;
	}

	$message = sprintf(
		esc_html__( 'Successfully added %d likes and %d dislikes to %d post(s).', WP_ULIKE_PRO_DOMAIN ),
		$likes,
		$dislikes,
		$processed
	);

	if ( ! empty( $errors ) ) {
		$message .= ' ' . esc_html__( 'Errors:', WP_ULIKE_PRO_DOMAIN ) . ' ' . implode( ', ', $errors );
	}

	return array(
		'success' => true,
		'rows_affected' => $processed,
		'message' => $message,
		'errors' => $errors
	);
}

/**
 * Get date range of tops
 *
 * @param string $type
 * @return string
 */
function wp_ulike_pro_get_daterange_of_tops( $type ){
	$period = wp_ulike_get_transient( 'wp_ulike_pro_daterange_of_top_' . $type );
	return is_array( $period ) ? implode( ' - ', $period ) : esc_html__( 'All Times', WP_ULIKE_PRO_DOMAIN );
}

/**
 * Convert array to csv
 *
 * @param array $data
 * @param string $filename
 * @return void
 */
function wp_ulike_pro_produce_csv( $results, $filename = "export.csv" ) {

    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header('Content-Description: File Transfer');
    header("Content-type: text/csv");
    header("Content-Disposition: attachment; filename={$filename}");
    header("Expires: 0");
    header("Pragma: public");

    $fh = @fopen( 'php://output', 'w' );

    $headerDisplayed = false;

    foreach ( $results as $data ) {
        // Add a header row if it hasn't been added yet
        if ( !$headerDisplayed ) {
            // Use the keys from $data as the titles
            fputcsv($fh, array_keys($data));
            $headerDisplayed = true;
        }

        // Put the data into the stream
        fputcsv($fh, $data);
    }
    // Close the file
    fclose($fh);
    // Make sure nothing else is sent, our file is done
    exit;

}

/**
 * Search for attachments
 *
 * @param string $term (default: '') Term to search for.
 * @param bool   $include_variations in search or not.
 */
function wp_ulike_pro_search_attachments( $term = '', $include_variations = false ) {

	if ( empty( $term ) && isset( $_REQUEST['term'] ) ) {
		$term = (string) wp_unslash( $_REQUEST['term'] );
	}

	if ( empty( $term ) ) {
		return array();
	}

    $query = array(
        's'           => $term,
        'post_type'   => 'attachment',
        'post_status' => 'inherit'
    );

    if ( current_user_can( get_post_type_object( 'attachment' )->cap->read_private_posts ) ) {
        $query['post_status'] .= ',private';
    }

    $query = new WP_Query( $query );
    $attachments = array();

    // Check that we have query results.
    if ( $query->have_posts() ) {
        // Start looping over the query results.
        while ( $query->have_posts() ) {
            $query->the_post();
            $attachments[ get_the_ID() ] = rawurldecode( wp_strip_all_tags( sprintf( '%s [ ID : %s ]', get_the_title(), get_the_ID() ) ) );
        }
        // Restore original post data.
        wp_reset_postdata();
    }

	return $attachments;
}

/**
 * Search for attachment title
 *
 * @param integer $id
 * @return string
 */
function wp_ulike_pro_search_attachments_title( $id ) {
    // Get attachment info
	$post = get_post( $id );
    // Return title
    return ! empty( $post ) ? rawurldecode( wp_strip_all_tags( sprintf( '%s [ ID : %s ]', $post->post_title, $post->ID ) ) ) : $id;
}

/**
 * Get admin avatar box html wrapper
 *
 * @return void
 */
function wp_ulike_pro_admin_avatar_box_callback(){
    echo sprintf( '
        <div class="ulf-title">
            <h4>%s</h4>
        </div>
        <div class="ulf-fieldset">
            <div class="ulf--wrap">%s</div>
            <div class="clear"></div>
        </div>',
        esc_html__('Upload Avatar', WP_ULIKE_PRO_DOMAIN),
        WP_Ulike_Pro_Avatar::get_avatar_uploader()
    );
}

/**
 * Get admin logs columns for vuejs
 *
 * @param string $type
 * @return array
 */
function wp_ulike_pro_get_admin_logs_columns( $type ){
    // Output
    $output = array();

    switch ($type) {
        case 'comment':
            $output = array(
                array(
                    'label' => 'ID',
                    'field' => 'id',
                    'type' => 'number'
                ),
                array(
                    'label' => 'Username',
                    'field' => 'user_id',
                    'html'  => true
                ),
                array(
                    'label' => 'Comment ID',
                    'field' => 'comment_id'
                ),
                array(
                    'label'    => 'Comment Author',
                    'field'    => 'comment_author',
                    'sortable' => false
                ),
                array(
                    'label'    => 'Comment Content',
                    'field'    => 'comment_content',
                    'html'     => true,
                    'sortable' => false
                ),
                array(
                    'label' => 'Status',
                    'field' => 'status'
                ),
                array(
                    'label' => 'Date/Time',
                    'field' => 'date_time'
                ),
                array(
                    'label' => 'IP',
                    'field' => 'ip',
                    'type' => 'number'
                )
            );
            break;

        case 'topic':
            $output = array(
                array(
                    'label' => 'ID',
                    'field' => 'id',
                    'type' => 'number'
                ),
                array(
                    'label' => 'Username',
                    'field' => 'user_id',
                    'html'  => true
                ),
                array(
                    'label' => 'Topic ID',
                    'field' => 'topic_id'
                ),
                array(
                    'label'    => 'Topic Title',
                    'field'    => 'topic_title',
                    'html'     => true,
                    'sortable' => false,
                ),
                array(
                    'label' => 'Status',
                    'field' => 'status'
                ),
                array(
                    'label' => 'Date/Time',
                    'field' => 'date_time'
                ),
                array(
                    'label' => 'IP',
                    'field' => 'ip',
                    'type' => 'number'
                )
            );
            break;

        case 'activity':
            $output = array(
                array(
                    'label' => 'ID',
                    'field' => 'id',
                    'type' => 'number'
                ),
                array(
                    'label' => 'Username',
                    'field' => 'user_id',
                    'html'  => true
                ),
                array(
                    'label' => 'Activity ID',
                    'field' => 'activity_id'
                ),
                array(
                    'label' => 'Activity Title',
                    'field' => 'activity_title',
                    'html'  => true
                ),
                array(
                    'label' => 'Status',
                    'field' => 'status'
                ),
                array(
                    'label' => 'Date/Time',
                    'field' => 'date_time'
                ),
                array(
                    'label' => 'IP',
                    'field' => 'ip',
                    'type' => 'number'
                )
            );
            break;

        default:
            $output = array(
                array(
                    'label' => 'ID',
                    'field' => 'id',
                    'type' => 'number'
                ),
                array(
                    'label' => 'Username',
                    'field' => 'user_id',
                    'html'  => true
                ),
                array(
                    'label' => 'Post ID/Title',
                    'field' => 'post_title',
                    'html'  => true
                ),
                array(
                    'label'    => 'Post Type',
                    'field'    => 'post_type',
                    'html'     => true,
                    'sortable' => false
                ),
                array(
                    'label'    => 'Category',
                    'field'    => 'category',
                    'html'     => true,
                    'sortable' => false
                ),
                array(
                    'label' => 'Status',
                    'field' => 'status'
                ),
                array(
                    'label' => 'Date/Time',
                    'field' => 'date_time'
                ),
                array(
                    'label' => 'IP',
                    'field' => 'ip',
                    'type' => 'number'
                )
            );
            break;
    }

    return apply_filters( 'wp_ulike_pro_admin_logs_columns', $output, $type );
}

/**
 * Get installed core pages list
 *
 * @return array
 */
function wp_ulike_pro_get_installed_core_pages(){
    $installed_pages = array();

    $core_pages = get_posts( array(
		'post_type'      => 'page',
		'posts_per_page' => -1,
		'meta_query'     => array(
			array(
				'key'     => '_wp_ulike_pro_core',
				'compare' => 'EXISTS',
			)
		)
	) );

	if ( $core_pages ) {
        foreach ( $core_pages as $core_page ) {
            $installed_pages[$core_page->ID] = $core_page->post_name;
        }
	}

    return $installed_pages;
}