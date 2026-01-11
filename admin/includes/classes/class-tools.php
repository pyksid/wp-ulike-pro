<?php
/**
 * WP ULike Pro Tools Class
 *
 * 
 * @package    wp-ulike-pro
 * @author     TechnoWich 2025
 * @link       https://wpulike.com
 */

// no direct access allowed
if ( ! defined('ABSPATH') ) {
    die();
}

if ( ! class_exists( 'WP_Ulike_Pro_Tools' ) ) {
    /**
     * Class to handle Tools menu page
     */
    class WP_Ulike_Pro_Tools {

        /**
         * __construct
         */
        function __construct() {
            add_filter( 'wp_ulike_admin_pages', array( $this, 'register_page' ), 5, 1 );
        }

        /**
         * Register Tools page in admin menu
         *
         * @param array $submenus
         * @return array
         */
        public function register_page( $submenus ) {
            // Find the position of 'about' menu
            $about_position = false;
            if ( is_array( $submenus ) ) {
                $keys = array_keys( $submenus );
                $about_position = array_search( 'about', $keys );
            }

            $tools_submenu_page = array( 'tools' => array(
                'title'       => sprintf( '<span class="wp-ulike-menu-icon"><span class="dashicons dashicons-admin-tools"></span> %s</span>', esc_html__( 'Tools', WP_ULIKE_PRO_DOMAIN ) ),
                'parent_slug' => 'wp-ulike-settings',
                'capability'  => 'manage_options',
                'path'        => WP_ULIKE_PRO_ADMIN_DIR . '/includes/templates/tools.php',
                'menu_slug'   => 'wp-ulike-pro-tools',
                'load_screen' => false
            ) );

            // Insert before 'about' if it exists, otherwise append
            if ( $about_position !== false ) {
                array_splice( $submenus, $about_position, 0, $tools_submenu_page );
            } else {
                $submenus = array_merge( $submenus, $tools_submenu_page );
            }

            return $submenus;
        }

        /**
         * Get optimization tools data
         *
         * @return array
         */
        public static function get_optimization_tools() {
            // Check license permission
            if( ! WP_Ulike_Pro_API::has_permission() ){
                return array();
            }

            // Check if BuddyPress is active
            $is_buddypress_active = defined( 'BP_VERSION' );

            // Check if bbPress is active
            $is_bbpress_active = function_exists( 'is_bbpress' );

            $tools = array(
                'posts' => array(
                    'title' => esc_html__( 'Posts', WP_ULIKE_PRO_DOMAIN ),
                    'tools' => array(
                        array(
                            'title'  => esc_html__( 'Delete All Records', WP_ULIKE_PRO_DOMAIN ),
                            'label'  => esc_html__( 'Delete All', WP_ULIKE_PRO_DOMAIN ),
                            'desc'   => esc_html__( 'Permanently delete all like and dislike records for posts. This will remove all like/dislike history and cannot be undone. Your post content will not be affected.', WP_ULIKE_PRO_DOMAIN ),
                            'type'   => 'post',
                            'action' => 'truncate_table'
                        ),
                        array(
                            'title'  => esc_html__( 'Remove Invalid Records', WP_ULIKE_PRO_DOMAIN ),
                            'label'  => esc_html__( 'Clean Invalid', WP_ULIKE_PRO_DOMAIN ),
                            'desc'   => esc_html__( 'Remove like/dislike records for posts that no longer exist in your WordPress database. This helps clean up orphaned data. Do not use this if you are using custom post IDs or external post references.', WP_ULIKE_PRO_DOMAIN ),
                            'type'   => 'post',
                            'action' => 'delete_orphaned_rows'
                        ),
                        array(
                            'title'  => esc_html__( 'Remove Duplicate Records', WP_ULIKE_PRO_DOMAIN ),
                            'label'  => esc_html__( 'Remove Duplicates', WP_ULIKE_PRO_DOMAIN ),
                            'desc'   => esc_html__( 'Delete duplicate like/dislike records that may have been created by spam, system errors, or data migration issues. This keeps only the most recent record from each user per post.', WP_ULIKE_PRO_DOMAIN ),
                            'type'   => 'post',
                            'action' => 'delete_duplicate_rows'
                        ),
                        array(
                            'title'  => esc_html__( 'Delete Stored Counter Values', WP_ULIKE_PRO_DOMAIN ),
                            'label'  => esc_html__( 'Delete Counters', WP_ULIKE_PRO_DOMAIN ),
                            'desc'   => esc_html__( 'Remove all stored like/dislike counter values from post meta. The counters will be automatically recalculated from records when needed. This action cannot be undone.', WP_ULIKE_PRO_DOMAIN ),
                            'type'   => 'post',
                            'action' => 'delete_meta_group'
                        ),
                        array(
                            'title'  => esc_html__( 'Sync Counter Values', WP_ULIKE_PRO_DOMAIN ),
                            'label'  => esc_html__( 'Sync Counters', WP_ULIKE_PRO_DOMAIN ),
                            'desc'   => esc_html__( 'Recalculate and update all counter values from actual like/dislike records. Use this after deleting records, if counters show incorrect numbers, or to ensure meta counters match the actual record data.', WP_ULIKE_PRO_DOMAIN ),
                            'type'   => 'post',
                            'action' => 'recalculate_counters'
                        ),
                        array(
                            'title'  => esc_html__( 'Optimize Database Table', WP_ULIKE_PRO_DOMAIN ),
                            'label'  => esc_html__( 'Optimize Table', WP_ULIKE_PRO_DOMAIN ),
                            'desc'   => esc_html__( 'Clean up unused space in the database table to improve performance and reduce file size. This is safe and recommended for regular maintenance on large databases.', WP_ULIKE_PRO_DOMAIN ),
                            'type'   => 'post',
                            'action' => 'optimize_table'
                        ),
                        array(
                            'title'  => esc_html__( 'Delete All View Tracking Records', WP_ULIKE_PRO_DOMAIN ),
                            'label'  => esc_html__( 'Delete Views', WP_ULIKE_PRO_DOMAIN ),
                            'desc'   => esc_html__( 'Permanently delete all view tracking records for posts. This will remove all view history used for engagement rate calculations and cannot be undone.', WP_ULIKE_PRO_DOMAIN ),
                            'type'   => 'post',
                            'action' => 'delete_views'
                        )
                    )
                ),
                'comments' => array(
                    'title' => esc_html__( 'Comments', WP_ULIKE_PRO_DOMAIN ),
                    'tools' => array(
                        array(
                            'title'  => esc_html__( 'Delete All Records', WP_ULIKE_PRO_DOMAIN ),
                            'label'  => esc_html__( 'Delete All', WP_ULIKE_PRO_DOMAIN ),
                            'desc'   => esc_html__( 'Permanently delete all like and dislike records for comments. This will remove all like/dislike history and cannot be undone. Your comments will not be affected.', WP_ULIKE_PRO_DOMAIN ),
                            'type'   => 'comment',
                            'action' => 'truncate_table'
                        ),
                        array(
                            'title'  => esc_html__( 'Remove Invalid Records', WP_ULIKE_PRO_DOMAIN ),
                            'label'  => esc_html__( 'Clean Invalid', WP_ULIKE_PRO_DOMAIN ),
                            'desc'   => esc_html__( 'Remove like/dislike records for comments that no longer exist in your WordPress database. This helps clean up orphaned data. Do not use this if you are using custom comment IDs or external comment references.', WP_ULIKE_PRO_DOMAIN ),
                            'type'   => 'comment',
                            'action' => 'delete_orphaned_rows'
                        ),
                        array(
                            'title'  => esc_html__( 'Remove Duplicate Records', WP_ULIKE_PRO_DOMAIN ),
                            'label'  => esc_html__( 'Remove Duplicates', WP_ULIKE_PRO_DOMAIN ),
                            'desc'   => esc_html__( 'Delete duplicate like/dislike records that may have been created by spam, system errors, or data migration issues. This keeps only the most recent record from each user per comment.', WP_ULIKE_PRO_DOMAIN ),
                            'type'   => 'comment',
                            'action' => 'delete_duplicate_rows'
                        ),
                        array(
                            'title'  => esc_html__( 'Delete Stored Counter Values', WP_ULIKE_PRO_DOMAIN ),
                            'label'  => esc_html__( 'Delete Counters', WP_ULIKE_PRO_DOMAIN ),
                            'desc'   => esc_html__( 'Remove all stored like/dislike counter values from comment meta. The counters will be automatically recalculated from records when needed. This action cannot be undone.', WP_ULIKE_PRO_DOMAIN ),
                            'type'   => 'comment',
                            'action' => 'delete_meta_group'
                        ),
                        array(
                            'title'  => esc_html__( 'Sync Counter Values', WP_ULIKE_PRO_DOMAIN ),
                            'label'  => esc_html__( 'Sync Counters', WP_ULIKE_PRO_DOMAIN ),
                            'desc'   => esc_html__( 'Recalculate and update all counter values from actual like/dislike records. Use this after deleting records, if counters show incorrect numbers, or to ensure meta counters match the actual record data.', WP_ULIKE_PRO_DOMAIN ),
                            'type'   => 'comment',
                            'action' => 'recalculate_counters'
                        ),
                        array(
                            'title'  => esc_html__( 'Optimize Database Table', WP_ULIKE_PRO_DOMAIN ),
                            'label'  => esc_html__( 'Optimize Table', WP_ULIKE_PRO_DOMAIN ),
                            'desc'   => esc_html__( 'Clean up unused space in the database table to improve performance and reduce file size. This is safe and recommended for regular maintenance on large databases.', WP_ULIKE_PRO_DOMAIN ),
                            'type'   => 'comment',
                            'action' => 'optimize_table'
                        ),
                        array(
                            'title'  => esc_html__( 'Delete All View Tracking Records', WP_ULIKE_PRO_DOMAIN ),
                            'label'  => esc_html__( 'Delete Views', WP_ULIKE_PRO_DOMAIN ),
                            'desc'   => esc_html__( 'Permanently delete all view tracking records for comments. This will remove all view history used for engagement rate calculations and cannot be undone.', WP_ULIKE_PRO_DOMAIN ),
                            'type'   => 'comment',
                            'action' => 'delete_views'
                        )
                    )
                )
            );

            // Only add BuddyPress section if plugin is active
            if( $is_buddypress_active ){
                $tools['activity'] = array(
                    'title' => esc_html__( 'BuddyPress Activities', WP_ULIKE_PRO_DOMAIN ),
                    'tools' => array(
                        array(
                            'title'  => esc_html__( 'Delete All Records', WP_ULIKE_PRO_DOMAIN ),
                            'label'  => esc_html__( 'Delete All', WP_ULIKE_PRO_DOMAIN ),
                            'desc'   => esc_html__( 'Permanently delete all like and dislike records for BuddyPress activities. This will remove all like/dislike history and cannot be undone. Your activities will not be affected.', WP_ULIKE_PRO_DOMAIN ),
                            'type'   => 'activity',
                            'action' => 'truncate_table'
                        ),
                        array(
                            'title'  => esc_html__( 'Remove Invalid Records', WP_ULIKE_PRO_DOMAIN ),
                            'label'  => esc_html__( 'Clean Invalid', WP_ULIKE_PRO_DOMAIN ),
                            'desc'   => esc_html__( 'Remove like/dislike records for activities that no longer exist in your BuddyPress database. This helps clean up orphaned data. Do not use this if you are using custom activity IDs or external activity references.', WP_ULIKE_PRO_DOMAIN ),
                            'type'   => 'activity',
                            'action' => 'delete_orphaned_rows'
                        ),
                        array(
                            'title'  => esc_html__( 'Remove Duplicate Records', WP_ULIKE_PRO_DOMAIN ),
                            'label'  => esc_html__( 'Remove Duplicates', WP_ULIKE_PRO_DOMAIN ),
                            'desc'   => esc_html__( 'Delete duplicate like/dislike records that may have been created by spam, system errors, or data migration issues. This keeps only the most recent record from each user per activity.', WP_ULIKE_PRO_DOMAIN ),
                            'type'   => 'activity',
                            'action' => 'delete_duplicate_rows'
                        ),
                        array(
                            'title'  => esc_html__( 'Delete Stored Counter Values', WP_ULIKE_PRO_DOMAIN ),
                            'label'  => esc_html__( 'Delete Counters', WP_ULIKE_PRO_DOMAIN ),
                            'desc'   => esc_html__( 'Remove all stored like/dislike counter values from activity meta. The counters will be automatically recalculated from records when needed. This action cannot be undone.', WP_ULIKE_PRO_DOMAIN ),
                            'type'   => 'activity',
                            'action' => 'delete_meta_group'
                        ),
                        array(
                            'title'  => esc_html__( 'Sync Counter Values', WP_ULIKE_PRO_DOMAIN ),
                            'label'  => esc_html__( 'Sync Counters', WP_ULIKE_PRO_DOMAIN ),
                            'desc'   => esc_html__( 'Recalculate and update all counter values from actual like/dislike records. Use this after deleting records, if counters show incorrect numbers, or to ensure meta counters match the actual record data.', WP_ULIKE_PRO_DOMAIN ),
                            'type'   => 'activity',
                            'action' => 'recalculate_counters'
                        ),
                        array(
                            'title'  => esc_html__( 'Optimize Database Table', WP_ULIKE_PRO_DOMAIN ),
                            'label'  => esc_html__( 'Optimize Table', WP_ULIKE_PRO_DOMAIN ),
                            'desc'   => esc_html__( 'Clean up unused space in the database table to improve performance and reduce file size. This is safe and recommended for regular maintenance on large databases.', WP_ULIKE_PRO_DOMAIN ),
                            'type'   => 'activity',
                            'action' => 'optimize_table'
                        ),
                        array(
                            'title'  => esc_html__( 'Delete All View Tracking Records', WP_ULIKE_PRO_DOMAIN ),
                            'label'  => esc_html__( 'Delete Views', WP_ULIKE_PRO_DOMAIN ),
                            'desc'   => esc_html__( 'Permanently delete all view tracking records for BuddyPress activities. This will remove all view history used for engagement rate calculations and cannot be undone.', WP_ULIKE_PRO_DOMAIN ),
                            'type'   => 'activity',
                            'action' => 'delete_views'
                        )
                    )
                );
            }

            // Only add bbPress section if plugin is active
            if( $is_bbpress_active ){
                $tools['topic'] = array(
                    'title' => esc_html__( 'bbPress Topics', WP_ULIKE_PRO_DOMAIN ),
                    'tools' => array(
                        array(
                            'title'  => esc_html__( 'Delete All Records', WP_ULIKE_PRO_DOMAIN ),
                            'label'  => esc_html__( 'Delete All', WP_ULIKE_PRO_DOMAIN ),
                            'desc'   => esc_html__( 'Permanently delete all like and dislike records for bbPress topics. This will remove all like/dislike history and cannot be undone. Your topics will not be affected.', WP_ULIKE_PRO_DOMAIN ),
                            'type'   => 'topic',
                            'action' => 'truncate_table'
                        ),
                        array(
                            'title'  => esc_html__( 'Remove Invalid Records', WP_ULIKE_PRO_DOMAIN ),
                            'label'  => esc_html__( 'Clean Invalid', WP_ULIKE_PRO_DOMAIN ),
                            'desc'   => esc_html__( 'Remove like/dislike records for topics that no longer exist in your bbPress database. This helps clean up orphaned data. Do not use this if you are using custom topic IDs or external topic references.', WP_ULIKE_PRO_DOMAIN ),
                            'type'   => 'topic',
                            'action' => 'delete_orphaned_rows'
                        ),
                        array(
                            'title'  => esc_html__( 'Remove Duplicate Records', WP_ULIKE_PRO_DOMAIN ),
                            'label'  => esc_html__( 'Remove Duplicates', WP_ULIKE_PRO_DOMAIN ),
                            'desc'   => esc_html__( 'Delete duplicate like/dislike records that may have been created by spam, system errors, or data migration issues. This keeps only the most recent record from each user per topic.', WP_ULIKE_PRO_DOMAIN ),
                            'type'   => 'topic',
                            'action' => 'delete_duplicate_rows'
                        ),
                        array(
                            'title'  => esc_html__( 'Delete Stored Counter Values', WP_ULIKE_PRO_DOMAIN ),
                            'label'  => esc_html__( 'Delete Counters', WP_ULIKE_PRO_DOMAIN ),
                            'desc'   => esc_html__( 'Remove all stored like/dislike counter values from topic meta. The counters will be automatically recalculated from records when needed. This action cannot be undone.', WP_ULIKE_PRO_DOMAIN ),
                            'type'   => 'topic',
                            'action' => 'delete_meta_group'
                        ),
                        array(
                            'title'  => esc_html__( 'Sync Counter Values', WP_ULIKE_PRO_DOMAIN ),
                            'label'  => esc_html__( 'Sync Counters', WP_ULIKE_PRO_DOMAIN ),
                            'desc'   => esc_html__( 'Recalculate and update all counter values from actual like/dislike records. Use this after deleting records, if counters show incorrect numbers, or to ensure meta counters match the actual record data.', WP_ULIKE_PRO_DOMAIN ),
                            'type'   => 'topic',
                            'action' => 'recalculate_counters'
                        ),
                        array(
                            'title'  => esc_html__( 'Optimize Database Table', WP_ULIKE_PRO_DOMAIN ),
                            'label'  => esc_html__( 'Optimize Table', WP_ULIKE_PRO_DOMAIN ),
                            'desc'   => esc_html__( 'Clean up unused space in the database table to improve performance and reduce file size. This is safe and recommended for regular maintenance on large databases.', WP_ULIKE_PRO_DOMAIN ),
                            'type'   => 'topic',
                            'action' => 'optimize_table'
                        ),
                        array(
                            'title'  => esc_html__( 'Delete All View Tracking Records', WP_ULIKE_PRO_DOMAIN ),
                            'label'  => esc_html__( 'Delete Views', WP_ULIKE_PRO_DOMAIN ),
                            'desc'   => esc_html__( 'Permanently delete all view tracking records for bbPress topics. This will remove all view history used for engagement rate calculations and cannot be undone.', WP_ULIKE_PRO_DOMAIN ),
                            'type'   => 'topic',
                            'action' => 'delete_views'
                        )
                    )
                );
            }

            $tools['general'] = array(
                'title' => esc_html__( 'Other Tools', WP_ULIKE_PRO_DOMAIN ),
                'tools' => array(
                    array(
                        'title'  => esc_html__( 'Delete All User Vote Status', WP_ULIKE_PRO_DOMAIN ),
                        'label'  => esc_html__( 'Delete Status', WP_ULIKE_PRO_DOMAIN ),
                        'desc'   => esc_html__( 'Remove all stored information about which users have liked or disliked content. This will not delete vote records, only the user status tracking. This action cannot be undone.', WP_ULIKE_PRO_DOMAIN ),
                        'type'   => 'user',
                        'action' => 'delete_meta_group'
                    ),
                    array(
                        'title'  => esc_html__( 'Delete All Statistics Cache', WP_ULIKE_PRO_DOMAIN ),
                        'label'  => esc_html__( 'Delete Cache', WP_ULIKE_PRO_DOMAIN ),
                        'desc'   => esc_html__( 'Clear all cached statistics data. Statistics will be recalculated when needed. This is safe and will not delete actual vote records.', WP_ULIKE_PRO_DOMAIN ),
                        'type'   => 'statistics',
                        'action' => 'delete_meta_group'
                    ),
                    array(
                        'title'  => esc_html__( 'Remove Empty Post Settings', WP_ULIKE_PRO_DOMAIN ),
                        'label'  => esc_html__( 'Clean Empty', WP_ULIKE_PRO_DOMAIN ),
                        'desc'   => esc_html__( 'Delete empty post settings that are no longer needed. This helps reduce database size without affecting your content or votes.', WP_ULIKE_PRO_DOMAIN ),
                        'type'   => 'optimize',
                        'action' => 'optimize_post_meta'
                    ),
                    array(
                        'title'  => esc_html__( 'Create Default Pages', WP_ULIKE_PRO_DOMAIN ),
                        'label'  => esc_html__( 'Create Pages', WP_ULIKE_PRO_DOMAIN ),
                        'desc'   => esc_html__( 'Automatically create the required pages for user profiles, login, registration, and account management. Existing pages will not be replaced.', WP_ULIKE_PRO_DOMAIN ),
                        'type'   => 'create',
                        'action' => 'manage_default_pages'
                    ),
                    array(
                        'title'  => esc_html__( 'Delete Default Pages', WP_ULIKE_PRO_DOMAIN ),
                        'label'  => esc_html__( 'Delete Pages', WP_ULIKE_PRO_DOMAIN ),
                        'desc'   => esc_html__( 'Permanently delete all default pages created by the plugin (user profiles, login, registration, etc.). This action cannot be undone.', WP_ULIKE_PRO_DOMAIN ),
                        'type'   => 'delete',
                        'action' => 'manage_default_pages'
                    )
                )
            );

            $tools['conversions'] = array(
                'title' => esc_html__( 'Data Conversions & Migration', WP_ULIKE_PRO_DOMAIN ),
                'tools' => array(
                    array(
                        'title'  => esc_html__( 'Move Post Counters to WordPress Meta', WP_ULIKE_PRO_DOMAIN ),
                        'label'  => esc_html__( 'Move Post Counters', WP_ULIKE_PRO_DOMAIN ),
                        'desc'   => esc_html__( 'Transfer counter values from the plugin table to WordPress standard meta tables. Only use this if you are upgrading from very old plugin versions (pre-3.0). Most users do not need this tool.', WP_ULIKE_PRO_DOMAIN ),
                        'type'   => 'post',
                        'action' => 'migrate_metadata'
                    ),
                    array(
                        'title'  => esc_html__( 'Move Comment Counters to WordPress Meta', WP_ULIKE_PRO_DOMAIN ),
                        'label'  => esc_html__( 'Move Comment Counters', WP_ULIKE_PRO_DOMAIN ),
                        'desc'   => esc_html__( 'Transfer counter values from the plugin table to WordPress standard meta tables. Only use this if you are upgrading from very old plugin versions (pre-3.0). Most users do not need this tool.', WP_ULIKE_PRO_DOMAIN ),
                        'type'   => 'comment',
                        'action' => 'migrate_metadata'
                    ),
                    array(
                        'title'  => esc_html__( 'Convert to Serialized Format', WP_ULIKE_PRO_DOMAIN ),
                        'label'  => esc_html__( 'Convert Format', WP_ULIKE_PRO_DOMAIN ),
                        'desc'   => esc_html__( 'Convert post meta from the old format (each setting stored in separate rows) to the new serialized format (all settings in one row). This improves database performance and reduces storage space. Use this if you upgraded from older plugin versions that used the old structure.', WP_ULIKE_PRO_DOMAIN ),
                        'type'   => 'post',
                        'action' => 'upgrade_unserialize_post_meta'
                    ),
                    array(
                        'title'  => esc_html__( 'Remove Old Format Meta Records', WP_ULIKE_PRO_DOMAIN ),
                        'label'  => esc_html__( 'Remove Old Records', WP_ULIKE_PRO_DOMAIN ),
                        'desc'   => esc_html__( 'Remove old format post meta records (individual rows) after successfully converting to serialized format. Only use this after running the conversion tool above. This action cannot be undone.', WP_ULIKE_PRO_DOMAIN ),
                        'type'   => 'delete_all',
                        'action' => 'optimize_post_meta'
                    )
                )
            );

            $tools['cache'] = array(
                'title' => esc_html__( 'Cache Management', WP_ULIKE_PRO_DOMAIN ),
                'tools' => array(
                    array(
                        'title'  => esc_html__( 'Clear All Cache', WP_ULIKE_PRO_DOMAIN ),
                        'label'  => esc_html__( 'Clear All Cache', WP_ULIKE_PRO_DOMAIN ),
                        'desc'   => esc_html__( 'Clear all cached data including WordPress object cache and popular cache plugins (WP Rocket, W3 Total Cache, LiteSpeed Cache, WP Super Cache, etc.). Use this if you notice outdated like counts or cached data.', WP_ULIKE_PRO_DOMAIN ),
                        'type'   => 'cache',
                        'action' => 'clear_all_cache'
                    ),
                    array(
                        'title'  => esc_html__( 'Clear Statistics Cache', WP_ULIKE_PRO_DOMAIN ),
                        'label'  => esc_html__( 'Clear Statistics', WP_ULIKE_PRO_DOMAIN ),
                        'desc'   => esc_html__( 'Delete all cached statistics and temporary data. Statistics will be recalculated fresh when viewed. This is safe and does not delete any vote records or actual data.', WP_ULIKE_PRO_DOMAIN ),
                        'type'   => 'statistics',
                        'action' => 'delete_meta_group'
                    ),
                    array(
                        'title'  => esc_html__( 'Clear Transient Cache', WP_ULIKE_PRO_DOMAIN ),
                        'label'  => esc_html__( 'Clear Transients', WP_ULIKE_PRO_DOMAIN ),
                        'desc'   => esc_html__( 'Delete all temporary cached data (transients) stored by the plugin. This helps free up database space. Data will be regenerated when needed. This is safe and does not delete vote records.', WP_ULIKE_PRO_DOMAIN ),
                        'type'   => 'transients',
                        'action' => 'clear_transients'
                    ),
                    array(
                        'title'  => esc_html__( 'Clean Up Expired Sessions', WP_ULIKE_PRO_DOMAIN ),
                        'label'  => esc_html__( 'Clean Sessions', WP_ULIKE_PRO_DOMAIN ),
                        'desc'   => esc_html__( 'Remove expired user session data from the database. This helps free up space and improve performance. Active sessions will not be affected.', WP_ULIKE_PRO_DOMAIN ),
                        'type'   => 'sessions',
                        'action' => 'cleanup_sessions'
                    )
                )
            );

            $tools['repair'] = array(
                'title' => esc_html__( 'Repair & Maintenance', WP_ULIKE_PRO_DOMAIN ),
                'tools' => array(
                    array(
                        'title'  => esc_html__( 'Repair Database Tables', WP_ULIKE_PRO_DOMAIN ),
                        'label'  => esc_html__( 'Repair Tables', WP_ULIKE_PRO_DOMAIN ),
                        'desc'   => esc_html__( 'Fix corrupted or damaged database tables. Use this if you experience database errors or missing data. This may take several minutes on large databases.', WP_ULIKE_PRO_DOMAIN ),
                        'type'   => 'repair',
                        'action' => 'repair_tables'
                    ),
                    array(
                        'title'  => esc_html__( 'Analyze Database Tables', WP_ULIKE_PRO_DOMAIN ),
                        'label'  => esc_html__( 'Analyze Tables', WP_ULIKE_PRO_DOMAIN ),
                        'desc'   => esc_html__( 'Update database table statistics to help MySQL optimize queries. This improves query performance and is recommended for regular maintenance.', WP_ULIKE_PRO_DOMAIN ),
                        'type'   => 'analyze',
                        'action' => 'analyze_tables'
                    ),
                    array(
                        'title'  => esc_html__( 'Sync Database Indexes', WP_ULIKE_PRO_DOMAIN ),
                        'label'  => esc_html__( 'Sync Indexes', WP_ULIKE_PRO_DOMAIN ),
                        'desc'   => esc_html__( 'Add missing database indexes to improve query performance. This ensures all tables have the correct indexes as defined in the latest plugin version. Safe to run multiple times.', WP_ULIKE_PRO_DOMAIN ),
                        'type'   => 'sync',
                        'action' => 'sync_indexes'
                    )
                )
            );

            return $tools;
        }

        /**
         * Get debug information for support
         *
         * @return string
         */
        public static function get_debug_info() {
            global $wpdb;

            $debug_info = array();

            // WordPress Info
            $debug_info[] = "=== WordPress Information ===";
            $debug_info[] = "WordPress Version: " . get_bloginfo( 'version' );
            $debug_info[] = "Site URL: " . site_url();
            $debug_info[] = "Home URL: " . home_url();
            $debug_info[] = "Multisite: " . ( is_multisite() ? 'Yes' : 'No' );
            $debug_info[] = "Language: " . get_locale();
            $debug_info[] = "User Count: " . count_users()['total_users'];
            $debug_info[] = "";

            // Server Info
            $debug_info[] = "=== Server Information ===";
            $debug_info[] = "PHP Version: " . phpversion();
            $debug_info[] = "MySQL Version: " . $wpdb->db_version();
            $debug_info[] = "Server Software: " . ( isset( $_SERVER['SERVER_SOFTWARE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) : 'Unknown' );
            $debug_info[] = "Server Protocol: " . ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_PROTOCOL'] ) ) : 'Unknown' );
            $debug_info[] = "Server IP (Internal): " . ( isset( $_SERVER['SERVER_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_ADDR'] ) ) : 'Unknown' );

            // Get Remote IP (safely, without exposing full details)
            $remote_ip = 'Not available';
            if ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
                $ips = explode( ',', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) );
                $remote_ip = trim( $ips[0] );
            } elseif ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
                $remote_ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
            }
            $debug_info[] = "Remote IP: " . $remote_ip;

            // Get Public IP (using cached method from API class)
            $public_ip = 'Not available';
            if ( class_exists( 'WP_Ulike_Pro_API' ) && method_exists( 'WP_Ulike_Pro_API', 'get_public_server_ip' ) ) {
                try {
                    $public_ip = WP_Ulike_Pro_API::get_public_server_ip();
                } catch ( Exception $e ) {
                    // Silently fail - public IP is not critical
                }
            }
            $debug_info[] = "Public IP: " . $public_ip;

            $debug_info[] = "PHP Memory Limit: " . ini_get( 'memory_limit' );
            $debug_info[] = "PHP Max Execution Time: " . ini_get( 'max_execution_time' );
            $debug_info[] = "PHP Upload Max Filesize: " . ini_get( 'upload_max_filesize' );
            $debug_info[] = "PHP Post Max Size: " . ini_get( 'post_max_size' );
            $debug_info[] = "allow_url_fopen: " . ( ini_get( 'allow_url_fopen' ) ? 'Enabled' : 'Disabled' );
            $debug_info[] = "cURL Version: " . ( function_exists( 'curl_version' ) ? curl_version()['version'] : 'Not Available' );
            $curl_ssl = function_exists( 'curl_version' ) && isset( curl_version()['ssl_version'] ) ? curl_version()['ssl_version'] : 'N/A';
            $debug_info[] = "cURL SSL Version: " . $curl_ssl;
            $debug_info[] = "OpenSSL Version: " . ( defined( 'OPENSSL_VERSION_TEXT' ) ? OPENSSL_VERSION_TEXT : ( function_exists( 'openssl_version_text' ) ? openssl_version_text() : 'Not Available' ) );
            $debug_info[] = "Is SSL: " . ( is_ssl() ? 'Yes' : 'No' );
            $debug_info[] = "";

            // WordPress Debug
            $debug_info[] = "=== WordPress Debug ===";
            $debug_info[] = "WP_DEBUG: " . ( defined( 'WP_DEBUG' ) && WP_DEBUG ? 'Enabled' : 'Disabled' );
            $debug_info[] = "WP_DEBUG_LOG: " . ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ? 'Enabled' : 'Disabled' );
            $debug_info[] = "WP_DEBUG_DISPLAY: " . ( defined( 'WP_DEBUG_DISPLAY' ) && WP_DEBUG_DISPLAY ? 'Enabled' : 'Disabled' );
            $debug_info[] = "SCRIPT_DEBUG: " . ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? 'Enabled' : 'Disabled' );
            $debug_info[] = "";

            // Active Plugins
            $active_plugins = get_option( 'active_plugins', array() );
            if ( is_multisite() ) {
                $network_plugins = get_site_option( 'active_sitewide_plugins', array() );
                $active_plugins = array_merge( $active_plugins, array_keys( $network_plugins ) );
            }

            $debug_info[] = "=== Active Plugins (" . count( $active_plugins ) . ") ===";
            foreach ( $active_plugins as $plugin ) {
                $plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );
                $debug_info[] = $plugin_data['Name'] . " - " . $plugin_data['Version'] . " (" . $plugin . ")";
            }
            $debug_info[] = "";

            // Theme
            $theme = wp_get_theme();
            $debug_info[] = "=== Theme ===";
            $debug_info[] = "Theme Name: " . $theme->get( 'Name' );
            $debug_info[] = "Theme Version: " . $theme->get( 'Version' );
            $debug_info[] = "Theme Author: " . $theme->get( 'Author' );
            $debug_info[] = "Theme URI: " . $theme->get( 'ThemeURI' );
            $debug_info[] = "Parent Theme: " . ( $theme->parent() ? $theme->parent()->get( 'Name' ) : 'None' );
            $debug_info[] = "";

            // WP ULike Info
            $debug_info[] = "=== WP ULike Information ===";
            $debug_info[] = "WP ULike Free Version: " . ( defined( 'WP_ULIKE_VERSION' ) ? WP_ULIKE_VERSION : 'Not Installed' );
            $debug_info[] = "WP ULike Pro Version: " . ( defined( 'WP_ULIKE_PRO_VERSION' ) ? WP_ULIKE_PRO_VERSION : 'Unknown' );
            $debug_info[] = "License Status: " . ( WP_Ulike_Pro_API::has_permission() ? 'Active' : 'Inactive' );
            $debug_info[] = "";

            // Database Tables
            $debug_info[] = "=== Database Tables ===";
            $tables = array( 'ulike', 'ulike_meta', 'ulike_activities', 'ulike_comments', 'ulike_forums', 'ulike_sessions', 'ulike_views' );
            foreach ( $tables as $table ) {
                $table_name = $wpdb->prefix . $table;
                $exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) ) == $table_name;
                if ( $exists ) {
                    // Use esc_sql for table name in query
                    $count = $wpdb->get_var( sprintf( "SELECT COUNT(*) FROM `%s`", esc_sql( $table_name ) ) );
                    $debug_info[] = $table_name . ": Exists ($count rows)";
                } else {
                    $debug_info[] = $table_name . ": Not exists";
                }
            }
            $debug_info[] = "";

            // WP ULike Settings Summary
            $debug_info[] = "=== WP ULike Settings Summary ===";
            $settings = get_option( 'wp_ulike_settings', array() );
            $debug_info[] = "Enable Serialize Storage: " . ( isset( $settings['enable_serialize'] ) && $settings['enable_serialize'] ? 'Yes' : 'No' );
            $debug_info[] = "";

            // Network & Connectivity
            $debug_info[] = "=== Network & Connectivity ===";
            $debug_info[] = "WP HTTP API Available: " . ( function_exists( 'wp_remote_get' ) ? 'Yes' : 'No' );
            $debug_info[] = "DNS Lookup: " . ( function_exists( 'gethostbyname' ) ? 'Available' : 'Not Available' );
            $debug_info[] = "";

            // Timestamp
            $debug_info[] = "=== Generated ===";
            $debug_info[] = "Date: " . current_time( 'mysql' );
            $debug_info[] = "Timezone: " . wp_timezone_string();
            $debug_info[] = "Server Time: " . date( 'Y-m-d H:i:s' );

            return implode( "\n", $debug_info );
        }
    }
}
