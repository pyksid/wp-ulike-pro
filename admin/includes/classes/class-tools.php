<?php
/**
 * WP ULike Pro Tools Class
 *
 *
 * @package    wp-ulike-pro
 * @author     TechnoWich 2026
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

        /** Max lines from debug.log included in the system report. */
        const DEBUG_ERROR_LOG_MAX_LINES = 200;

        /**
         * __construct
         */
        function __construct() {
            add_filter( 'wp_ulike_admin_pages', array( $this, 'register_page' ), 5, 1 );
            add_action( 'admin_init', array( $this, 'handle_rest_api_settings_save' ) );
            add_action( 'admin_init', array( $this, 'handle_display_automation_save' ) );
        }

        /**
         * Handle REST API settings form submission
         */
        public function handle_rest_api_settings_save() {
            if ( ! isset( $_POST['wp_ulike_rest_api_settings_save'] ) || ! wp_verify_nonce( $_POST['wp_ulike_rest_api_settings_nonce'], 'wp_ulike_rest_api_settings' ) ) {
                return;
            }

            if ( ! current_user_can( 'manage_options' ) ) {
                return;
            }

            $settings = array(
                'enable_rest_api'                        => isset( $_POST['enable_rest_api'] ) ? 'true' : 'false',
                'authentication_type'                   => isset( $_POST['authentication_type'] ) ? sanitize_text_field( $_POST['authentication_type'] ) : 'login',
                'rest_api_permission_for_readable_routes' => isset( $_POST['rest_api_permission_for_readable_routes'] ) && is_array( $_POST['rest_api_permission_for_readable_routes'] ) ? array_map( 'sanitize_text_field', $_POST['rest_api_permission_for_readable_routes'] ) : array( 'administrator' ),
                'rest_api_permission_for_writable_routes' => isset( $_POST['rest_api_permission_for_writable_routes'] ) && is_array( $_POST['rest_api_permission_for_writable_routes'] ) ? array_map( 'sanitize_text_field', $_POST['rest_api_permission_for_writable_routes'] ) : array( 'administrator' ),
                'enable_auto_user_id'                   => isset( $_POST['enable_auto_user_id'] ) ? 'true' : 'false',
            );

            self::save_rest_api_settings( $settings );

            // Redirect to prevent resubmission
            wp_safe_redirect( add_query_arg( array( 'page' => 'wp-ulike-pro-tools', 'tab' => 'rest-api', 'settings-updated' => 'true' ), admin_url( 'admin.php' ) ) );
            exit;
        }

        /**
         * Handle Display Automation form submission.
         *
         * @return void
         */
        public function handle_display_automation_save() {
            if ( ! isset( $_POST['wp_ulike_display_automation_save'] ) || ! wp_verify_nonce( $_POST['wp_ulike_display_automation_nonce'], 'wp_ulike_display_automation_settings' ) ) {
                return;
            }

            if ( ! current_user_can( 'manage_options' ) ) {
                return;
            }

            if ( ! WP_Ulike_Pro_API::has_permission() ) {
                wp_die( esc_html__( 'You need an active license to save display automation rules.', WP_ULIKE_PRO_DOMAIN ) );
            }

            $rules = isset( $_POST['display_rules'] ) && is_array( $_POST['display_rules'] )
                ? wp_unslash( $_POST['display_rules'] )
                : array();

            WP_Ulike_Pro_Display_Automation::save_rules( $rules );

            wp_safe_redirect(
                add_query_arg(
                    array(
                        'page'             => 'wp-ulike-pro-tools',
                        'tab'              => 'display-automation',
                        'settings-updated' => 'true',
                    ),
                    admin_url( 'admin.php' )
                )
            );
            exit;
        }


        /**
         * Get REST API settings for options panel (used by filter)
         *
         * @param array $options
         * @return array
         */
        public function get_rest_api_settings_for_panel( $options ) {
            return self::get_rest_api_settings();
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
                'title'       => esc_html__( 'Tools', WP_ULIKE_PRO_DOMAIN ),
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
         * View data for the Tools admin screen (wp-ulike-about layout).
         *
         * @return array<string, mixed>
         */
        public static function get_tools_view_data() {
            $current_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'maintenance';

            $tabs = array(
                'maintenance'        => array(
                    'label'       => esc_html__( 'Maintenance', WP_ULIKE_PRO_DOMAIN ),
                    'description' => esc_html__( 'Fix like counts, clean up old data, and keep things running smoothly.', WP_ULIKE_PRO_DOMAIN ),
                    'icon'        => 'admin-tools',
                ),
                'display-automation' => array(
                    'label'       => esc_html__( 'Display Automation', WP_ULIKE_PRO_DOMAIN ),
                    'description' => esc_html__( 'Choose where like buttons appear with rules and filters.', WP_ULIKE_PRO_DOMAIN ),
                    'icon'        => 'visibility',
                ),
                'schema-generator'   => array(
                    'label'       => esc_html__( 'Schema Generator', WP_ULIKE_PRO_DOMAIN ),
                    'description' => esc_html__( 'Configure Schema.org markup and FAQ structured data for your posts.', WP_ULIKE_PRO_DOMAIN ),
                    'icon'        => 'media-code',
                ),
                'bulk-actions'       => array(
                    'label'       => esc_html__( 'Bulk Actions', WP_ULIKE_PRO_DOMAIN ),
                    'description' => esc_html__( 'Search content and adjust vote, emoji, or star counters in bulk.', WP_ULIKE_PRO_DOMAIN ),
                    'icon'        => 'chart-bar',
                ),
                'gdpr'               => array(
                    'label'       => esc_html__( 'GDPR', WP_ULIKE_PRO_DOMAIN ),
                    'description' => esc_html__( 'Remove all like logs for selected users (cannot be undone).', WP_ULIKE_PRO_DOMAIN ),
                    'icon'        => 'privacy',
                ),
                'rest-api'           => array(
                    'label'       => esc_html__( 'REST API', WP_ULIKE_PRO_DOMAIN ),
                    'description' => esc_html__( 'Enable the REST API, permissions, and API keys.', WP_ULIKE_PRO_DOMAIN ),
                    'icon'        => 'rest-api',
                ),
                'debug'              => array(
                    'label'       => esc_html__( 'Debug Info', WP_ULIKE_PRO_DOMAIN ),
                    'description' => esc_html__( 'Copy system details for support tickets (no passwords or keys).', WP_ULIKE_PRO_DOMAIN ),
                    'icon'        => 'editor-code',
                ),
            );

            if ( ! isset( $tabs[ $current_tab ] ) ) {
                $current_tab = 'maintenance';
            }

            $tools_base = admin_url( 'admin.php?page=wp-ulike-pro-tools' );

            return array(
                'tabs'           => $tabs,
                'current_tab'    => $current_tab,
                'tab_lead'       => $tabs[ $current_tab ]['description'],
                'tools_base'     => $tools_base,
                'pro_version'    => defined( 'WP_ULIKE_PRO_VERSION' ) ? WP_ULIKE_PRO_VERSION : '',
                'settings_saved' => isset( $_GET['settings-updated'] ) && 'true' === sanitize_key( wp_unslash( $_GET['settings-updated'] ) ),
            );
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
                    'tools' => self::get_content_maintenance_tools( 'post' ),
                ),
                'comments' => array(
                    'title' => esc_html__( 'Comments', WP_ULIKE_PRO_DOMAIN ),
                    'tools' => self::get_content_maintenance_tools( 'comment' ),
                )
            );

            // Only add BuddyPress section if plugin is active
            if( $is_buddypress_active ){
                $tools['activity'] = array(
                    'title' => esc_html__( 'BuddyPress Activities', WP_ULIKE_PRO_DOMAIN ),
                    'tools' => self::get_content_maintenance_tools( 'activity' ),
                );
            }

            // Only add bbPress section if plugin is active
            if( $is_bbpress_active ){
                $tools['topic'] = array(
                    'title' => esc_html__( 'bbPress Topics', WP_ULIKE_PRO_DOMAIN ),
                    'tools' => self::get_content_maintenance_tools( 'topic' ),
                );
            }

            $tools['general'] = array(
                'title' => esc_html__( 'Site', WP_ULIKE_PRO_DOMAIN ),
                'tools' => array(
                    array(
                        'title'   => esc_html__( 'Delete All User Vote Status', WP_ULIKE_PRO_DOMAIN ),
                        'label'   => esc_html__( 'Delete Status', WP_ULIKE_PRO_DOMAIN ),
                        'desc'    => esc_html__( 'Remove all stored information about which users have liked or disliked content. This will not delete vote records, only the user status tracking. This action cannot be undone.', WP_ULIKE_PRO_DOMAIN ),
                        'summary' => esc_html__( 'Clears who-voted tracking only — your like and dislike records stay intact.', WP_ULIKE_PRO_DOMAIN ),
                        'type'    => 'user',
                        'action'  => 'delete_meta_group',
                    ),
                    array(
                        'title'   => esc_html__( 'Remove Empty Post Settings', WP_ULIKE_PRO_DOMAIN ),
                        'label'   => esc_html__( 'Clean Empty', WP_ULIKE_PRO_DOMAIN ),
                        'desc'    => esc_html__( 'Delete empty post settings that are no longer needed. This helps reduce database size without affecting your content or votes.', WP_ULIKE_PRO_DOMAIN ),
                        'summary' => esc_html__( 'Removes unused empty settings rows to free up database space.', WP_ULIKE_PRO_DOMAIN ),
                        'type'    => 'optimize',
                        'action'  => 'optimize_post_meta',
                    ),
                    array(
                        'title'   => esc_html__( 'Create Default Pages', WP_ULIKE_PRO_DOMAIN ),
                        'label'   => esc_html__( 'Create Pages', WP_ULIKE_PRO_DOMAIN ),
                        'desc'    => esc_html__( 'Automatically create the required pages for user profiles, login, registration, and account management. Existing pages will not be replaced.', WP_ULIKE_PRO_DOMAIN ),
                        'summary' => esc_html__( 'Creates login, profile, and account pages if they are missing.', WP_ULIKE_PRO_DOMAIN ),
                        'type'    => 'create',
                        'action'  => 'manage_default_pages',
                    ),
                    array(
                        'title'   => esc_html__( 'Delete Default Pages', WP_ULIKE_PRO_DOMAIN ),
                        'label'   => esc_html__( 'Delete Pages', WP_ULIKE_PRO_DOMAIN ),
                        'desc'    => esc_html__( 'Remove the plugin-created pages (login, profile, registration, etc.). You can recreate them anytime using Create Default Pages.', WP_ULIKE_PRO_DOMAIN ),
                        'summary' => esc_html__( 'Removes plugin pages only. Use Create Default Pages to set them up again.', WP_ULIKE_PRO_DOMAIN ),
                        'type'    => 'delete',
                        'action'  => 'manage_default_pages',
                        'risk'    => 'caution',
                        'confirm' => esc_html__( 'Remove the plugin-created pages? You can recreate them anytime with Create Default Pages.', WP_ULIKE_PRO_DOMAIN ),
                    )
                )
            );

            $tools['repair'] = array(
                'title' => esc_html__( 'Database', WP_ULIKE_PRO_DOMAIN ),
                'tools' => array(
                    array(
                        'title'  => esc_html__( 'Repair Tables', WP_ULIKE_PRO_DOMAIN ),
                        'label'  => esc_html__( 'Repair Tables', WP_ULIKE_PRO_DOMAIN ),
                        'desc'   => esc_html__( 'Repair plugin tables (meta cache, sessions, views).', WP_ULIKE_PRO_DOMAIN ),
                        'type'   => 'repair',
                        'action' => 'repair_tables',
                        'risk'   => 'safe',
                    ),
                    array(
                        'title'  => esc_html__( 'Analyze Tables', WP_ULIKE_PRO_DOMAIN ),
                        'label'  => esc_html__( 'Analyze Tables', WP_ULIKE_PRO_DOMAIN ),
                        'desc'   => esc_html__( 'Update MySQL statistics for plugin tables to improve query performance.', WP_ULIKE_PRO_DOMAIN ),
                        'type'   => 'analyze',
                        'action' => 'analyze_tables',
                        'risk'   => 'safe',
                    ),
                    array(
                        'title'  => esc_html__( 'Sync Indexes', WP_ULIKE_PRO_DOMAIN ),
                        'label'  => esc_html__( 'Sync Indexes', WP_ULIKE_PRO_DOMAIN ),
                        'desc'   => esc_html__( 'Add missing indexes on plugin tables. Safe to run multiple times.', WP_ULIKE_PRO_DOMAIN ),
                        'type'   => 'sync',
                        'action' => 'sync_indexes',
                        'risk'   => 'safe',
                    ),
                ),
            );

            $tools['cache'] = array(
                'title' => esc_html__( 'Cache', WP_ULIKE_PRO_DOMAIN ),
                'tools' => array(
                    array(
                        'title'   => esc_html__( 'Clear All Cache', WP_ULIKE_PRO_DOMAIN ),
                        'label'   => esc_html__( 'Clear All Cache', WP_ULIKE_PRO_DOMAIN ),
                        'desc'    => esc_html__( 'Clear plugin query caches and popular page-cache plugins. Use this first when counts look wrong on the front end.', WP_ULIKE_PRO_DOMAIN ),
                        'summary' => esc_html__( 'Refreshes vote and counter caches site-wide.', WP_ULIKE_PRO_DOMAIN ),
                        'type'    => 'cache',
                        'action'  => 'clear_all_cache',
                        'risk'    => 'safe',
                    ),
                    array(
                        'title'   => esc_html__( 'Clear Statistics Cache', WP_ULIKE_PRO_DOMAIN ),
                        'label'   => esc_html__( 'Clear Statistics', WP_ULIKE_PRO_DOMAIN ),
                        'desc'    => esc_html__( 'Refresh the statistics dashboard cache. Vote records are not deleted.', WP_ULIKE_PRO_DOMAIN ),
                        'summary' => esc_html__( 'Statistics dashboard only — your vote data stays intact.', WP_ULIKE_PRO_DOMAIN ),
                        'type'    => 'statistics',
                        'action'  => 'delete_meta_group',
                        'risk'    => 'safe',
                    ),
                    array(
                        'title'  => esc_html__( 'Clear Transient Cache', WP_ULIKE_PRO_DOMAIN ),
                        'label'  => esc_html__( 'Clear Transients', WP_ULIKE_PRO_DOMAIN ),
                        'desc'   => esc_html__( 'Delete temporary plugin transients to free database space.', WP_ULIKE_PRO_DOMAIN ),
                        'type'   => 'transients',
                        'action' => 'clear_transients',
                        'risk'   => 'safe',
                    ),
                    array(
                        'title'  => esc_html__( 'Clean Up Expired Sessions', WP_ULIKE_PRO_DOMAIN ),
                        'label'  => esc_html__( 'Clean Sessions', WP_ULIKE_PRO_DOMAIN ),
                        'desc'   => esc_html__( 'Remove expired session rows from the sessions table.', WP_ULIKE_PRO_DOMAIN ),
                        'type'   => 'sessions',
                        'action' => 'cleanup_sessions',
                        'risk'   => 'safe',
                    )
                )
            );

            return self::finalize_maintenance_tool_groups( $tools );
        }

        /**
         * Minimal maintenance tools for one content type.
         *
         * @param string $type post|comment|activity|topic.
         * @return array<int, array<string, mixed>>
         */
        private static function get_content_maintenance_tools( $type ) {
            $label = ucfirst( $type );

            $tools = array(
                array(
                    'title'   => sprintf( esc_html__( 'Sync Counters (%s)', WP_ULIKE_PRO_DOMAIN ), $label ),
                    'label'   => esc_html__( 'Sync Counters', WP_ULIKE_PRO_DOMAIN ),
                    'desc'    => esc_html__( 'Rebuild stored counters from the database. Safe to run anytime counts look wrong.', WP_ULIKE_PRO_DOMAIN ),
                    'summary' => esc_html__( 'Recommended first step when numbers disagree with the live data.', WP_ULIKE_PRO_DOMAIN ),
                    'type'    => $type,
                    'action'  => 'sync_counters',
                    'risk'    => 'safe',
                ),
            );

            if ( 'none' !== wp_ulike_pro_get_engagement_mode_for_type( $type ) ) {
                $tools[] = array(
                    'title'   => sprintf( esc_html__( 'Repair Records (%s)', WP_ULIKE_PRO_DOMAIN ), $label ),
                    'label'   => esc_html__( 'Repair Records', WP_ULIKE_PRO_DOMAIN ),
                    'desc'    => esc_html__( 'Remove invalid and duplicate engagement records for this content type.', WP_ULIKE_PRO_DOMAIN ),
                    'summary' => esc_html__( 'Fixes broken or duplicate records without deleting valid votes.', WP_ULIKE_PRO_DOMAIN ),
                    'type'    => $type,
                    'action'  => 'repair_records',
                    'risk'    => 'caution',
                );
            }

            $tools[] = array(
                'title'   => sprintf( esc_html__( 'Clear View History (%s)', WP_ULIKE_PRO_DOMAIN ), $label ),
                'label'   => esc_html__( 'Clear Views', WP_ULIKE_PRO_DOMAIN ),
                'desc'    => esc_html__( 'Permanently delete view tracking records for this content type.', WP_ULIKE_PRO_DOMAIN ),
                'summary' => esc_html__( 'Removes view history used for engagement rate metrics.', WP_ULIKE_PRO_DOMAIN ),
                'type'    => $type,
                'action'  => 'delete_views',
                'risk'    => 'destructive',
            );

            $kind_options = array(
                array(
                    'value' => 'vote',
                    'label' => esc_html__( 'Votes only', WP_ULIKE_PRO_DOMAIN ),
                ),
            );

            if ( 'none' !== wp_ulike_pro_get_engagement_mode_for_type( $type ) ) {
                $kind_options[] = array(
                    'value' => 'engagement',
                    'label' => esc_html__( 'Emoji & star only', WP_ULIKE_PRO_DOMAIN ),
                );
                $kind_options[] = array(
                    'value' => 'all',
                    'label' => esc_html__( 'Votes + emoji & star', WP_ULIKE_PRO_DOMAIN ),
                );
            }

            $tools[] = array(
                'title'   => sprintf( esc_html__( 'Purge Pulse Logs (%s)', WP_ULIKE_PRO_DOMAIN ), $label ),
                'label'   => esc_html__( 'Purge Logs', WP_ULIKE_PRO_DOMAIN ),
                'desc'    => esc_html__( 'Permanently delete matching Pulse log rows for this content type. Use the filters to limit by kind and age. Counters are rebuilt for affected items.', WP_ULIKE_PRO_DOMAIN ),
                'summary' => esc_html__( 'Delete matching vote or engagement logs. Prefer an age filter when you only need to clear old data.', WP_ULIKE_PRO_DOMAIN ),
                'type'    => $type,
                'action'  => 'purge_pulse_logs',
                'risk'    => 'destructive',
                'ui'      => 'purge',
                'confirm' => esc_html__( 'This permanently deletes matching Pulse log rows and cannot be undone. Continue?', WP_ULIKE_PRO_DOMAIN ),
                'filters' => array(
                    'kind'       => array(
                        'label'   => esc_html__( 'What to remove', WP_ULIKE_PRO_DOMAIN ),
                        'default' => 'vote',
                        'options' => $kind_options,
                    ),
                    'older_than' => array(
                        'label'   => esc_html__( 'Age filter', WP_ULIKE_PRO_DOMAIN ),
                        'default' => '90',
                        'options' => array(
                            array(
                                'value' => '30',
                                'label' => esc_html__( 'Older than 30 days', WP_ULIKE_PRO_DOMAIN ),
                            ),
                            array(
                                'value' => '90',
                                'label' => esc_html__( 'Older than 90 days', WP_ULIKE_PRO_DOMAIN ),
                            ),
                            array(
                                'value' => '365',
                                'label' => esc_html__( 'Older than 1 year', WP_ULIKE_PRO_DOMAIN ),
                            ),
                            array(
                                'value' => '0',
                                'label' => esc_html__( 'All time', WP_ULIKE_PRO_DOMAIN ),
                            ),
                        ),
                    ),
                ),
            );

            return $tools;
        }

        /**
         * Maintenance sections for the switcher UI (one panel visible at a time).
         *
         * @return array<string, array<string, mixed>>
         */
        public static function get_maintenance_sections() {
            $groups = self::get_optimization_tools();

            if ( empty( $groups ) ) {
                return array();
            }

            $definitions = array(
                'posts'    => array(
                    'label'       => esc_html__( 'Posts', WP_ULIKE_PRO_DOMAIN ),
                    'description' => esc_html__( 'Sync counters, repair records, purge Pulse logs, and manage view history for posts.', WP_ULIKE_PRO_DOMAIN ),
                ),
                'comments' => array(
                    'label'       => esc_html__( 'Comments', WP_ULIKE_PRO_DOMAIN ),
                    'description' => esc_html__( 'Sync counters, repair records, purge Pulse logs, and manage view history for comments.', WP_ULIKE_PRO_DOMAIN ),
                ),
                'activity' => array(
                    'label'       => esc_html__( 'Activities', WP_ULIKE_PRO_DOMAIN ),
                    'description' => esc_html__( 'Sync counters, repair records, purge Pulse logs, and manage view history for BuddyPress activities.', WP_ULIKE_PRO_DOMAIN ),
                ),
                'topic'    => array(
                    'label'       => esc_html__( 'Topics', WP_ULIKE_PRO_DOMAIN ),
                    'description' => esc_html__( 'Sync counters, repair records, purge Pulse logs, and manage view history for bbPress topics.', WP_ULIKE_PRO_DOMAIN ),
                ),
                'general'  => array(
                    'label'       => esc_html__( 'Site', WP_ULIKE_PRO_DOMAIN ),
                    'description' => esc_html__( 'Plugin pages and user vote-status cleanup.', WP_ULIKE_PRO_DOMAIN ),
                ),
                'cache'    => array(
                    'label'       => esc_html__( 'Cache', WP_ULIKE_PRO_DOMAIN ),
                    'description' => esc_html__( 'Clear query caches, statistics, transients, and expired sessions.', WP_ULIKE_PRO_DOMAIN ),
                ),
                'repair'   => array(
                    'label'       => esc_html__( 'Database', WP_ULIKE_PRO_DOMAIN ),
                    'description' => esc_html__( 'Maintain plugin database tables. Dropping old legacy log tables is in WP ULike → Pulse.', WP_ULIKE_PRO_DOMAIN ),
                ),
            );

            $sections = array();

            foreach ( $definitions as $key => $meta ) {
                if ( ! isset( $groups[ $key ] ) ) {
                    continue;
                }

                $risk_groups = self::group_maintenance_tools_by_risk( $groups[ $key ]['tools'] );

                if ( empty( $risk_groups ) ) {
                    continue;
                }

                $sections[ $key ] = array_merge(
                    $meta,
                    array(
                        'risk_groups' => $risk_groups,
                    )
                );
            }

            return $sections;
        }

        /**
         * Group maintenance tools by risk level for display.
         *
         * @param array<int, array<string, mixed>> $tools Tool definitions.
         * @return array<string, array<string, mixed>>
         */
        private static function group_maintenance_tools_by_risk( $tools ) {
            $order  = array( 'safe', 'caution', 'destructive' );
            $labels = array(
                'safe'        => esc_html__( 'Recommended', WP_ULIKE_PRO_DOMAIN ),
                'caution'     => esc_html__( 'Review first', WP_ULIKE_PRO_DOMAIN ),
                'destructive' => esc_html__( 'Permanent removal', WP_ULIKE_PRO_DOMAIN ),
            );

            $grouped = array(
                'safe'        => array(),
                'caution'     => array(),
                'destructive' => array(),
            );

            foreach ( $tools as $tool ) {
                $risk = isset( $tool['risk'] ) ? $tool['risk'] : 'caution';

                if ( ! isset( $grouped[ $risk ] ) ) {
                    $risk = 'caution';
                }

                $grouped[ $risk ][] = $tool;
            }

            $result = array();

            foreach ( $order as $risk ) {
                if ( empty( $grouped[ $risk ] ) ) {
                    continue;
                }

                $result[ $risk ] = array(
                    'label' => $labels[ $risk ],
                    'tools' => $grouped[ $risk ],
                );
            }

            return $result;
        }

        /**
         * Attach risk level and concise summary to each maintenance tool.
         *
         * @param array<string, array<string, mixed>> $groups Tool groups.
         * @return array<string, array<string, mixed>>
         */
        private static function finalize_maintenance_tool_groups( $groups ) {
            foreach ( $groups as $group_key => $group_data ) {
                if ( empty( $group_data['tools'] ) || ! is_array( $group_data['tools'] ) ) {
                    continue;
                }

                foreach ( $group_data['tools'] as $index => $tool ) {
                    $groups[ $group_key ]['tools'][ $index ] = self::finalize_maintenance_tool( $tool );
                }
            }

            return $groups;
        }

        /**
         * @param array<string, mixed> $tool Tool definition.
         * @return array<string, mixed>
         */
        private static function finalize_maintenance_tool( $tool ) {
            $action = isset( $tool['action'] ) ? $tool['action'] : '';
            $type   = isset( $tool['type'] ) ? $tool['type'] : '';

            if ( empty( $tool['risk'] ) ) {
                $tool['risk'] = self::resolve_maintenance_tool_risk( $action, $type );
            }

            if ( empty( $tool['summary'] ) && ! empty( $tool['desc'] ) ) {
                $tool['summary'] = self::summarize_maintenance_tool_description( $tool['desc'] );
            }

            return $tool;
        }

        /**
         * @param string $action Tool action key.
         * @param string $type   Tool type key.
         * @return string safe|caution|destructive
         */
        private static function resolve_maintenance_tool_risk( $action, $type ) {
            if ( 'manage_default_pages' === $action ) {
                return 'delete' === $type ? 'caution' : 'safe';
            }

            if ( 'delete_all' === $type ) {
                return 'destructive';
            }

            if ( 'delete_meta_group' === $action ) {
                if ( in_array( $type, array( 'post', 'comment', 'activity', 'topic' ), true ) ) {
                    return 'caution';
                }

                if ( 'user' === $type ) {
                    return 'caution';
                }

                if ( 'statistics' === $type ) {
                    return 'safe';
                }

                return 'caution';
            }

            $destructive = array( 'delete_views', 'purge_pulse_logs' );
            $safe        = array(
                'sync_counters',
                'clear_all_cache',
                'clear_transients',
                'cleanup_sessions',
                'analyze_tables',
                'sync_indexes',
                'repair_tables',
                'count_pulse_logs',
            );
            $caution     = array(
                'repair_records',
                'optimize_post_meta',
            );

            if ( in_array( $action, $destructive, true ) ) {
                return 'destructive';
            }

            if ( in_array( $action, $safe, true ) ) {
                return 'safe';
            }

            if ( in_array( $action, $caution, true ) ) {
                return 'caution';
            }

            return 'caution';
        }

        /**
         * @param string $description Full tool description.
         * @return string
         */
        private static function summarize_maintenance_tool_description( $description ) {
            $plain = wp_strip_all_tags( $description );
            $parts = preg_split( '/(?<=[.!?])\s+/', $plain, 2 );

            if ( ! empty( $parts[0] ) ) {
                return trim( $parts[0] );
            }

            return $plain;
        }

        /**
         * Human-readable risk labels for maintenance tools.
         *
         * @return array<string, string>
         */
        public static function get_maintenance_risk_labels() {
            return array(
                'safe'        => esc_html__( 'Safe', WP_ULIKE_PRO_DOMAIN ),
                'caution'     => esc_html__( 'Review first', WP_ULIKE_PRO_DOMAIN ),
                'destructive' => esc_html__( 'Cannot undo', WP_ULIKE_PRO_DOMAIN ),
            );
        }

        /**
         * Contextual notices for the Maintenance screen.
         *
         * @return array<int, array<string, string>>
         */
        public static function get_maintenance_admin_notices() {
            $notices = array();

            $logging_labels = array(
                'do_not_log'        => esc_html__( 'Do Not Log', WP_ULIKE_PRO_DOMAIN ),
                'by_cookie'         => esc_html__( 'By Cookie', WP_ULIKE_PRO_DOMAIN ),
                'by_username'       => esc_html__( 'By Username', WP_ULIKE_PRO_DOMAIN ),
                'by_user_ip_cookie' => esc_html__( 'By User/IP + Cookie', WP_ULIKE_PRO_DOMAIN ),
            );

            $content_groups = array(
                'posts_group'       => esc_html__( 'Posts', WP_ULIKE_PRO_DOMAIN ),
                'comments_group'    => esc_html__( 'Comments', WP_ULIKE_PRO_DOMAIN ),
                'buddypress_group'  => esc_html__( 'BuddyPress Activities', WP_ULIKE_PRO_DOMAIN ),
                'bbpress_group'     => esc_html__( 'bbPress Topics', WP_ULIKE_PRO_DOMAIN ),
            );

            $limited_logging = array();

            foreach ( $content_groups as $group_key => $group_label ) {
                $method = wp_ulike_get_option( $group_key . '|logging_method', 'by_username' );

                if ( in_array( $method, array( 'do_not_log', 'by_cookie' ), true ) ) {
                    $method_label       = isset( $logging_labels[ $method ] ) ? $logging_labels[ $method ] : $method;
                    $limited_logging[] = sprintf(
                        /* translators: 1: content type label, 2: logging method label */
                        esc_html__( '%1$s uses "%2$s"', WP_ULIKE_PRO_DOMAIN ),
                        $group_label,
                        $method_label
                    );
                }
            }

            if ( ! empty( $limited_logging ) ) {
                $notices[] = array(
                    'type'    => 'info',
                    'message' => esc_html__( 'Some content types store few or no vote logs. Cleanup tools that rely on log rows may have limited effect:', WP_ULIKE_PRO_DOMAIN ) . ' ' . implode( '; ', $limited_logging ) . '.',
                );
            }

            return $notices;
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
            $debug_info[] = '=== WordPress Information ===';
            $debug_info[] = 'WordPress Version: ' . get_bloginfo( 'version' );
            $debug_info[] = 'Site URL: ' . site_url();
            $debug_info[] = 'Home URL: ' . home_url();
            $debug_info[] = 'Multisite: ' . ( is_multisite() ? 'Yes' : 'No' );
            $debug_info[] = 'Language: ' . get_locale();
            $debug_info[] = 'Memory limit: ' . ( defined( 'WP_MEMORY_LIMIT' ) ? WP_MEMORY_LIMIT : 'N/A' );
            $debug_info[] = '';

            // Server Info
            $debug_info[] = '=== Server Information ===';
            $debug_info[] = 'PHP Version: ' . phpversion();
            $debug_info[] = 'MySQL Version: ' . $wpdb->db_version();
            $debug_info[] = 'Server Software: ' . ( isset( $_SERVER['SERVER_SOFTWARE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) : 'Unknown' );
            $debug_info[] = 'PHP Memory Limit: ' . ini_get( 'memory_limit' );
            $debug_info[] = "PHP Max Execution Time: " . ini_get( 'max_execution_time' );
            $debug_info[] = "PHP Upload Max Filesize: " . ini_get( 'upload_max_filesize' );
            $debug_info[] = "PHP Post Max Size: " . ini_get( 'post_max_size' );
            $debug_info[] = "allow_url_fopen: " . ( ini_get( 'allow_url_fopen' ) ? 'Enabled' : 'Disabled' );
            $debug_info[] = "cURL Version: " . ( function_exists( 'curl_version' ) ? curl_version()['version'] : 'Not Available' );
            $curl_ssl = function_exists( 'curl_version' ) && isset( curl_version()['ssl_version'] ) ? curl_version()['ssl_version'] : 'N/A';
            $debug_info[] = "cURL SSL Version: " . $curl_ssl;
            $debug_info[] = "OpenSSL Version: " . ( defined( 'OPENSSL_VERSION_TEXT' ) ? OPENSSL_VERSION_TEXT : ( function_exists( 'openssl_version_text' ) ? openssl_version_text() : 'Not Available' ) );
            $debug_info[] = "HTTPS: " . ( is_ssl() ? 'Yes' : 'No' );
            $debug_info[] = "cURL: " . ( function_exists( 'curl_init' ) ? 'Supported' : 'Not supported' );
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
                $debug_info[] = $plugin_data['Name'] . " - " . $plugin_data['Version'];
            }
            $debug_info[] = "";

            // Theme
            $theme = wp_get_theme();
            $debug_info[] = "=== Theme ===";
            $debug_info[] = 'Theme Name: ' . $theme->get( 'Name' );
            $debug_info[] = 'Theme Version: ' . $theme->get( 'Version' );
            $debug_info[] = 'Parent Theme: ' . ( $theme->parent() ? $theme->parent()->get( 'Name' ) : 'None' );
            $debug_info[] = "";

            // WP ULike Info
            $debug_info[] = "=== WP ULike Information ===";
            $debug_info[] = "WP ULike Free Version: " . ( defined( 'WP_ULIKE_VERSION' ) ? WP_ULIKE_VERSION : 'Not Installed' );
            $debug_info[] = "WP ULike Pro Version: " . ( defined( 'WP_ULIKE_PRO_VERSION' ) ? WP_ULIKE_PRO_VERSION : 'Unknown' );
            $debug_info[] = "License Status: " . ( WP_Ulike_Pro_API::has_permission() ? 'Active' : 'Inactive' );
            $debug_info[] = "";

            // Database Tables
            $debug_info[] = "=== Database Tables ===";
            $tables = array( 'ulike_pulse', 'ulike_meta', 'ulike_sessions', 'ulike_views' );
            foreach ( $tables as $table ) {
                $table_name = $wpdb->prefix . $table;
                $exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) ) == $table_name;
                $debug_info[] = $table . ': ' . ( $exists ? 'Exists' : 'Not exists' );
            }
            $debug_info[] = "";

            // WP ULike Settings Summary
            $debug_info[] = "=== WP ULike Settings Summary ===";
            $settings = get_option( 'wp_ulike_settings', array() );
            $debug_info[] = "Enable Serialize Storage: " . ( isset( $settings['enable_serialize'] ) && $settings['enable_serialize'] ? 'Yes' : 'No' );
            $debug_info[] = "";

            // Error Log (last lines only — see get_error_log_content).
            $debug_info[] = '=== Error Log (recent lines) ===';
            $error_log_result = self::get_error_log_content();
            if ( $error_log_result['success'] ) {
                if ( ! empty( $error_log_result['notice'] ) ) {
                    $debug_info[] = $error_log_result['notice'];
                }
                $debug_info[] = $error_log_result['content'];
            } else {
                $debug_info[] = ! empty( $error_log_result['message'] )
                    ? $error_log_result['message']
                    : 'No readable error log found.';
            }
            $debug_info[] = '';

            // Timestamp
            $debug_info[] = '=== Generated ===';
            $debug_info[] = 'Date: ' . current_time( 'mysql' ) . ' (' . wp_timezone_string() . ')';

            return implode( "\n", $debug_info );
        }

        /**
         * Get error log file path (WordPress debug.log or PHP error_log).
         *
         * @return string
         */
        private static function get_error_log_path() {
            if ( defined( 'WP_DEBUG_LOG' ) && is_string( WP_DEBUG_LOG ) ) {
                return WP_DEBUG_LOG;
            }
            $wp_log = defined( 'WP_CONTENT_DIR' ) ? WP_CONTENT_DIR . '/debug.log' : '';
            if ( $wp_log && file_exists( $wp_log ) ) {
                return $wp_log;
            }
            $ini_log = ini_get( 'error_log' );
            if ( ! empty( $ini_log ) && file_exists( $ini_log ) ) {
                return $ini_log;
            }
            return $wp_log ? $wp_log : (string) $ini_log;
        }

        /**
         * Read the last N lines from a log file (reads only a tail chunk for large files).
         *
         * @param string $file_path Log file path.
         * @param int    $max_lines Maximum lines to return.
         * @return string
         */
        private static function read_log_file_tail( $file_path, $max_lines ) {
            $max_lines = max( 1, (int) $max_lines );
            $size      = @filesize( $file_path );

            if ( false === $size || 0 === $size ) {
                return '';
            }

            // Read up to 512 KB from the end so huge logs stay usable.
            $read_bytes = (int) min( $size, 512 * 1024 );
            $offset     = max( 0, $size - $read_bytes );

            $handle = @fopen( $file_path, 'rb' );
            if ( ! $handle ) {
                return '';
            }

            if ( $offset > 0 ) {
                fseek( $handle, $offset );
            }

            $chunk = stream_get_contents( $handle );
            fclose( $handle );

            if ( false === $chunk || '' === $chunk ) {
                return '';
            }

            if ( $offset > 0 ) {
                $chunk = preg_replace( '/^[^\r\n]*[\r\n]+/', '', $chunk, 1 );
            }

            $lines = preg_split( "/\r\n|\n|\r/", $chunk );
            $lines = array_filter( $lines, 'strlen' );
            $lines = array_slice( $lines, -$max_lines );

            return implode( "\n", $lines );
        }

        /**
         * Get recent error log lines for the debug report.
         *
         * @return array{success: bool, message?: string, notice?: string, path?: string, content?: string}
         */
        private static function get_error_log_content() {
            $max_lines = self::DEBUG_ERROR_LOG_MAX_LINES;
            $file_path = self::get_error_log_path();

            if ( empty( $file_path ) || ! file_exists( $file_path ) || ! is_readable( $file_path ) ) {
                return array(
                    'success' => false,
                    'message' => 'No readable error log found.',
                );
            }

            $content = self::read_log_file_tail( $file_path, $max_lines );
            if ( '' === $content ) {
                return array(
                    'success' => false,
                    'message' => 'Error log is empty or could not be read.',
                );
            }

            $size    = @filesize( $file_path );
            $notice  = sprintf(
                '(Last %d lines from %s%s)',
                $max_lines,
                basename( $file_path ),
                ( false !== $size && $size > 512 * 1024 ) ? '; larger file truncated to recent entries' : ''
            );

            return array(
                'success' => true,
                'path'    => $file_path,
                'notice'  => $notice,
                'content' => $content,
            );
        }

        /**
         * Migrate old REST API settings to new structure
         *
         * @return array
         */
        public static function migrate_rest_api_settings() {
            // Check if migration already done (persistent check)
            $migrated = get_option( 'wp_ulike_rest_api_settings_migrated', false );
            if ( $migrated ) {
                return get_option( 'wp_ulike_rest_api_settings', array() );
            }

            // Get old settings from options panel structure
            // wp_ulike_get_option retrieves from wp_ulike_settings option
            $old_settings = wp_ulike_get_option( 'enable_rest_api', false );
            $old_auth_type = wp_ulike_get_option( 'authentication_type', 'login' );
            $old_read_perms = wp_ulike_get_option( 'rest_api_permission_for_readable_routes', array( 'administrator' ) );
            $old_write_perms = wp_ulike_get_option( 'rest_api_permission_for_writable_routes', array( 'administrator' ) );
            $old_auto_user_id = wp_ulike_get_option( 'enable_auto_user_id', false );

            // Check if any old settings exist (if all are defaults, might be new install)
            $has_old_settings = (
                $old_settings !== false ||
                $old_auth_type !== 'login' ||
                ! empty( $old_read_perms ) ||
                ! empty( $old_write_perms ) ||
                $old_auto_user_id !== false
            );

            // Create new settings structure with proper type conversion
            $new_settings = array(
                'enable_rest_api'                        => wp_ulike_is_true( $old_settings ),
                'authentication_type'                   => sanitize_text_field( $old_auth_type ? $old_auth_type : 'login' ),
                'rest_api_permission_for_readable_routes' => is_array( $old_read_perms ) && ! empty( $old_read_perms ) ? array_map( 'sanitize_text_field', $old_read_perms ) : array( 'administrator' ),
                'rest_api_permission_for_writable_routes' => is_array( $old_write_perms ) && ! empty( $old_write_perms ) ? array_map( 'sanitize_text_field', $old_write_perms ) : array( 'administrator' ),
                'enable_auto_user_id'                   => wp_ulike_is_true( $old_auto_user_id ),
            );

            // Save new settings (even if no old settings, to mark migration as done)
            update_option( 'wp_ulike_rest_api_settings', $new_settings );
            update_option( 'wp_ulike_rest_api_settings_migrated', true );

            return $new_settings;
        }

        /**
         * Get REST API settings data
         *
         * @param string $key Optional key to get specific setting
         * @param mixed $default Default value if key not found
         * @return mixed
         */
        public static function get_rest_api_settings_data( $key = null, $default = null ) {
            // Migrate if needed (only once)
            static $migration_done = false;
            if ( ! $migration_done ) {
                self::migrate_rest_api_settings();
                $migration_done = true;
            }

            $settings = get_option( 'wp_ulike_rest_api_settings', array() );

            // Set defaults if empty
            if ( empty( $settings ) ) {
                $settings = array(
                    'enable_rest_api'                        => false,
                    'authentication_type'                   => 'login',
                    'rest_api_permission_for_readable_routes' => array( 'administrator' ),
                    'rest_api_permission_for_writable_routes' => array( 'administrator' ),
                    'enable_auto_user_id'                   => false,
                );
            }

            if ( $key !== null ) {
                return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
            }

            return $settings;
        }

        /**
         * Save REST API settings
         *
         * @param array $settings
         * @return bool
         */
        public static function save_rest_api_settings( $settings ) {
            // Sanitize settings
            $sanitized = array(
                'enable_rest_api'                        => isset( $settings['enable_rest_api'] ) ? wp_ulike_is_true( $settings['enable_rest_api'] ) : false,
                'authentication_type'                   => isset( $settings['authentication_type'] ) ? sanitize_text_field( $settings['authentication_type'] ) : 'login',
                'rest_api_permission_for_readable_routes' => isset( $settings['rest_api_permission_for_readable_routes'] ) && is_array( $settings['rest_api_permission_for_readable_routes'] ) ? array_map( 'sanitize_text_field', $settings['rest_api_permission_for_readable_routes'] ) : array( 'administrator' ),
                'rest_api_permission_for_writable_routes' => isset( $settings['rest_api_permission_for_writable_routes'] ) && is_array( $settings['rest_api_permission_for_writable_routes'] ) ? array_map( 'sanitize_text_field', $settings['rest_api_permission_for_writable_routes'] ) : array( 'administrator' ),
                'enable_auto_user_id'                   => isset( $settings['enable_auto_user_id'] ) ? wp_ulike_is_true( $settings['enable_auto_user_id'] ) : false,
            );

            return update_option( 'wp_ulike_rest_api_settings', $sanitized );
        }

        /**
         * Render API keys management section
         *
         * @return void
         */
        public static function render_api_keys_section() {
            $get_keys = get_option( 'wp_ulike_rest_api_keys', array() );
            ?>
            <p style="margin-top: 0;"><?php esc_html_e( 'These API keys allow you to use the REST API to retrieve store data in JSON for external applications or devices.', WP_ULIKE_PRO_DOMAIN ); ?></p>

            <div class="wp-ulike-pro-api-keys-actions">
                <input type="button" id="wp-ulike-pro-generate-api-key" class="button button-primary" value="<?php esc_attr_e( 'Generate New API Key', WP_ULIKE_PRO_DOMAIN ); ?>">
                <?php wp_nonce_field( 'wp_ulike_generate_api_keys', 'wp-ulike-pro-api-keys-nonce-field' ); ?>
            </div>

            <?php
            // Filter and validate keys
            $valid_keys = array();
            if ( ! empty( $get_keys ) && is_array( $get_keys ) ) {
                foreach ( $get_keys as $key => $value ) {
                    if ( is_array( $value ) && isset( $value['token'] ) && ! empty( $value['token'] ) ) {
                        $valid_keys[] = $value;
                    }
                }
            }
            ?>
            <?php if ( ! empty( $valid_keys ) ) : ?>
                <div class="wp-ulike-pro-api-keys-list">
                    <h3><?php esc_html_e( 'Generated API Keys', WP_ULIKE_PRO_DOMAIN ); ?></h3>
                    <div class="wp-ulike-pro-api-keys-items">
                        <?php
                        // Reverse array to show newest first (without preserving keys)
                        $display_keys = array_reverse( $valid_keys );
                        foreach ( $display_keys as $value ) :
                            // Use exact token as stored (wp_generate_password doesn't include whitespace)
                            $token = (string) $value['token'];
                            if ( empty( $token ) ) continue;
                        ?>
                            <div class="wp-ulike-pro-api-key-item" data-token="<?php echo esc_attr( $token ); ?>">
                                <div class="wp-ulike-pro-api-key-header">
                                    <div class="wp-ulike-pro-api-key-info">
                                        <div class="wp-ulike-pro-api-key-label-main"><?php esc_html_e( 'Secret Token', WP_ULIKE_PRO_DOMAIN ); ?></div>
                                        <div class="wp-ulike-pro-api-key-date-info">
                                            <span class="wp-ulike-pro-api-key-label-small"><?php esc_html_e( 'Created:', WP_ULIKE_PRO_DOMAIN ); ?></span>
                                            <span class="wp-ulike-pro-api-key-date-value"><?php echo esc_html( isset( $value['date'] ) ? $value['date'] : '' ); ?></span>
                                        </div>
                                    </div>
                                    <button type="button" class="wp-ulike-pro-delete-api-key button button-link-delete" data-token="<?php echo esc_attr( $token ); ?>" title="<?php esc_attr_e( 'Delete this API key', WP_ULIKE_PRO_DOMAIN ); ?>">
                                        <span class="dashicons dashicons-trash"></span>
                                        <span class="screen-reader-text"><?php esc_html_e( 'Delete', WP_ULIKE_PRO_DOMAIN ); ?></span>
                                    </button>
                                </div>
                                <div class="wp-ulike-pro-api-key-token-wrapper">
                                    <code class="wp-ulike-pro-api-key-token-code"><?php echo esc_html( $token ); ?></code>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else : ?>
                <div class="wp-ulike-pro-api-keys-empty">
                    <div class="wp-ulike-pro-api-keys-empty-icon">
                        <span class="dashicons dashicons-admin-network"></span>
                    </div>
                    <h3><?php esc_html_e( 'No API Keys Yet', WP_ULIKE_PRO_DOMAIN ); ?></h3>
                    <p><?php esc_html_e( 'Generate your first API key to start using the REST API. API keys allow external applications to securely access your WP ULike data.', WP_ULIKE_PRO_DOMAIN ); ?></p>
                </div>
            <?php endif; ?>
            <?php
        }
    }
}

