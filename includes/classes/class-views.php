<?php
/**
 * Views Tracking Class
 *
 * Tracks button visits for conversion rate calculations
 * 
 * @package    wp-ulike-pro
 * @author     TechnoWich 2025
 * @link       https://wpulike.com
 */

// no direct access allowed
if ( ! defined('ABSPATH') ) {
    die();
}

if ( ! class_exists( 'WP_Ulike_Pro_Views' ) ) {

    class WP_Ulike_Pro_Views {

        /**
         * Instance of this class.
         *
         * @var      object
         */
        protected static $instance = null;

        /**
         * Database table name
         *
         * @var string
         */
        private $table_name;

        /**
         * Constructor
         */
        public function __construct() {
            global $wpdb;
            $this->table_name = $wpdb->prefix . 'ulike_views';
        }

        /**
         * Check if request is from a bot/crawler
         *
         * @return bool True if bot, false if real user
         */
        private function is_bot() {
            // Use parent plugin's bot detection if available (uses DeviceDetector library)
            if ( function_exists( 'wp_ulike_is_bot_request' ) ) {
                return wp_ulike_is_bot_request();
            }

            // Fallback: basic check if parent plugin function doesn't exist
            $user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : '';
            if ( empty( $user_agent ) ) {
                return true; // No user agent = likely bot
            }

            return false;
        }

        /**
         * Track a button view (item-based, increments count per day)
         *
         * @param int    $item_id     The item ID (post_id, comment_id, etc.)
         * @param string $type         The button type (post, comment, activity, topic)
         * @return bool|int            View ID on success, false on failure
         */
        public function track_view( $item_id, $type ) {
            global $wpdb;

            // Validate inputs first
            $item_id = absint( $item_id );
            $type = sanitize_text_field( $type );

            // Validate type
            $allowed_types = array( 'post', 'comment', 'activity', 'topic' );
            if ( ! in_array( $type, $allowed_types, true ) ) {
                return false;
            }

            // Don't track in admin (but allow AJAX requests - admin-ajax.php is in admin but serves frontend)
            if ( is_admin() && ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
                return false;
            }

            // Don't track REST API requests
            if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
                return false;
            }

            // Bot protection - don't track bots/crawlers (skip for AJAX as they come from real users)
            if ( ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) && $this->is_bot() ) {
                return false;
            }

            // Get current date
            $view_date = current_time( 'Y-m-d' );
            $date_time = current_time( 'mysql' );

            // Check if view already exists for today
            $existing = $wpdb->get_row( $wpdb->prepare(
                "SELECT view_id, view_count FROM {$this->table_name}
                WHERE item_id = %d
                AND type = %s
                AND view_date = %s",
                $item_id,
                $type,
                $view_date
            ) );

            if ( $existing ) {
                // Increment view count for existing record
                $result = $wpdb->update(
                    $this->table_name,
                    array(
                        'view_count' => $existing->view_count + 1,
                        'date_time'  => $date_time
                    ),
                    array( 'view_id' => $existing->view_id ),
                    array( '%d', '%s' ),
                    array( '%d' )
                );

                // Update cached total views meta (increment by 1)
                if ( $result !== false ) {
                    $meta_key = 'views';
                    $current_total = wp_ulike_get_meta_data( $item_id, $type, $meta_key, true );
                    $new_total = ( ! empty( $current_total ) || is_numeric( $current_total ) ) ? absint( $current_total ) + 1 : 1;
                    wp_ulike_update_meta_data( $item_id, $type, $meta_key, $new_total );
                }

                return $result !== false ? $existing->view_id : false;
            } else {
                // Insert new view record
                $result = $wpdb->insert(
                    $this->table_name,
                    array(
                        'item_id'     => $item_id,
                        'type'        => $type,
                        'view_date'   => $view_date,
                        'view_count'  => 1,
                        'date_time'   => $date_time
                    ),
                    array( '%d', '%s', '%s', '%d', '%s' )
                );

                // Update cached total views meta (increment by 1)
                if ( $result !== false ) {
                    $meta_key = 'views';
                    $current_total = wp_ulike_get_meta_data( $item_id, $type, $meta_key, true );
                    $new_total = ( ! empty( $current_total ) || is_numeric( $current_total ) ) ? absint( $current_total ) + 1 : 1;
                    wp_ulike_update_meta_data( $item_id, $type, $meta_key, $new_total );
                }

                return $result !== false ? $wpdb->insert_id : false;
            }
        }

        /**
         * Get total views for an item
         *
         * @param int    $item_id The item ID
         * @param string $type     The button type
         * @param string $date     Optional date range (Y-m-d format) or 'all'
         * @return int             Total view count
         */
        public function get_total_views( $item_id, $type, $date = 'all' ) {
            global $wpdb;

            $item_id = absint( $item_id );
            $type = sanitize_text_field( $type );

            // For 'all' period, use cached meta value for better performance
            if ( $date === 'all' ) {
                $meta_key = 'views';
                $cached_views = wp_ulike_get_meta_data( $item_id, $type, $meta_key, true );

                // If cached value exists (even if 0), return it
                if ( $cached_views !== false && ( ! empty( $cached_views ) || is_numeric( $cached_views ) ) ) {
                    return absint( $cached_views );
                }

                // Cache miss - calculate from database
                $query = $wpdb->prepare(
                    "SELECT SUM(view_count) as total FROM {$this->table_name}
                    WHERE item_id = %d AND type = %s",
                    $item_id,
                    $type
                );

                $result = $wpdb->get_var( $query );
                $total_views = absint( $result );

                // Cache the result
                wp_ulike_update_meta_data( $item_id, $type, $meta_key, $total_views );

                return $total_views;
            }

            // For specific dates, query directly without caching
            $query = $wpdb->prepare(
                "SELECT SUM(view_count) as total FROM {$this->table_name}
                WHERE item_id = %d AND type = %s",
                $item_id,
                $type
            );

            if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
                $query .= $wpdb->prepare( " AND view_date = %s", $date );
            }

            $result = $wpdb->get_var( $query );
            return absint( $result );
        }

        /**
         * Get the first view date for an item (when view tracking started for this item)
         *
         * @param int    $item_id The item ID
         * @param string $type     The button type
         * @return string|null    First view date (Y-m-d format) or null if no views exist
         */
        public function get_first_view_date( $item_id, $type ) {
            global $wpdb;

            $item_id = absint( $item_id );
            $type = sanitize_text_field( $type );

            $first_date = $wpdb->get_var( $wpdb->prepare(
                "SELECT MIN(view_date) FROM {$this->table_name}
                WHERE item_id = %d AND type = %s AND view_count > 0",
                $item_id,
                $type
            ) );

            return $first_date ? $first_date : null;
        }

        /**
         * Get views for a date range
         *
         * @param string $type       The button type
         * @param string $start_date Start date (Y-m-d format)
         * @param string $end_date    End date (Y-m-d format)
         * @param int    $item_id    Optional item ID to filter by specific item
         * @return array             Array of date => view_count
         */
        public function get_views_by_date_range( $type, $start_date, $end_date, $item_id = null ) {
            global $wpdb;

            $type = sanitize_text_field( $type );
            $start_date = sanitize_text_field( $start_date );
            $end_date = sanitize_text_field( $end_date );

            // Validate date format
            if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $start_date ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $end_date ) ) {
                return array();
            }

            $where_clause = "type = %s AND view_date BETWEEN %s AND %s";
            $params = array( $type, $start_date, $end_date );

            if ( $item_id !== null ) {
                $item_id = absint( $item_id );
                $where_clause .= " AND item_id = %d";
                $params[] = $item_id;
            }

            $query = "SELECT view_date, SUM(view_count) as total_views
                FROM {$this->table_name}
                WHERE {$where_clause}
                GROUP BY view_date
                ORDER BY view_date ASC";

            $results = $wpdb->get_results( $wpdb->prepare( $query, $params ), ARRAY_A );

            $views = array();
            foreach ( $results as $row ) {
                $views[ $row['view_date'] ] = absint( $row['total_views'] );
            }

            return $views;
        }

        /**
         * Get views count for period filtering
         *
         * @param string $type   The button type
         * @param string $period Period (today, week, month, year, all)
         * @return int           Total view count
         */
        public function get_views_count( $type, $period = 'all' ) {
            global $wpdb;

            $type = sanitize_text_field( $type );

            $query = "SELECT SUM(view_count) as total FROM {$this->table_name} WHERE type = %s";
            $params = array( $type );

            switch ( $period ) {
                case 'today':
                    $query .= " AND view_date = %s";
                    $params[] = current_time( 'Y-m-d' );
                    break;
                case 'week':
                    $query .= " AND view_date >= %s";
                    $params[] = date( 'Y-m-d', strtotime( '-7 days' ) );
                    break;
                case 'month':
                    $query .= " AND view_date >= %s";
                    $params[] = date( 'Y-m-d', strtotime( '-30 days' ) );
                    break;
                case 'year':
                    $query .= " AND view_date >= %s";
                    $params[] = date( 'Y-m-d', strtotime( '-365 days' ) );
                    break;
            }

            $result = $wpdb->get_var( $wpdb->prepare( $query, $params ) );
            return absint( $result );
        }

        /**
         * Check if view tracking is enabled for a content type
         *
         * @param string $type Content type (post, comment, activity, topic)
         * @return bool True if enabled, false otherwise
         */
        public function is_tracking_enabled( $type ) {
            $enabled_types = wp_ulike_get_option( 'view_tracking_enabled_types', array( 'post' ) );

            // Default to all enabled if option not set
            if ( empty( $enabled_types ) || ! is_array( $enabled_types ) ) {
                return true;
            }

            return in_array( $type, $enabled_types, true );
        }

        /**
         * Handle AJAX request to track button view
         *
         * @return void
         */
        public function ajax_track_view() {
            // Verify nonce
            if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], WP_ULIKE_PRO_DOMAIN ) ) {
                wp_send_json_error();
            }

            // Get and sanitize parameters
            $item_id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
            $type = isset( $_POST['type'] ) ? sanitize_text_field( $_POST['type'] ) : '';

            // Validate required fields
            if ( empty( $item_id ) || empty( $type ) ) {
                wp_send_json_error();
            }

            // Check if tracking is enabled for this content type
            if ( ! $this->is_tracking_enabled( $type ) ) {
                wp_send_json_error();
            }

            // Track the view (item-based, no user tracking)
            $result = $this->track_view( $item_id, $type );

            if ( $result !== false ) {
                wp_send_json_success( array(
                    'view_id' => $result
                ) );
            } else {
                wp_send_json_error();
            }
        }

        /**
         * Handle AJAX request to track multiple views in batch (for performance)
         *
         * @return void
         */
        public function ajax_track_view_batch() {
            // Verify nonce
            if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], WP_ULIKE_PRO_DOMAIN ) ) {
                wp_send_json_error();
            }

            // Get and sanitize parameters
            $items_json = isset( $_POST['items'] ) ? wp_unslash( $_POST['items'] ) : '';
            $items = json_decode( $items_json, true );

            // Validate required fields
            if ( empty( $items ) || ! is_array( $items ) ) {
                wp_send_json_error();
            }

            // Limit batch size for security and performance
            $items = array_slice( $items, 0, 50 );

            $tracked = 0;
            $errors = 0;

            // Track each view
            foreach ( $items as $item ) {
                $item_id = isset( $item['id'] ) ? absint( $item['id'] ) : 0;
                $type = isset( $item['type'] ) ? sanitize_text_field( $item['type'] ) : '';

                if ( empty( $item_id ) || empty( $type ) ) {
                    $errors++;
                    continue;
                }

                // Check if tracking is enabled for this content type
                if ( ! $this->is_tracking_enabled( $type ) ) {
                    continue; // Skip disabled types silently
                }

                $result = $this->track_view( $item_id, $type );
                if ( $result !== false ) {
                    $tracked++;
                } else {
                    $errors++;
                }
            }

            wp_send_json_success( array(
                'tracked' => $tracked,
                'errors'  => $errors
            ) );
        }

        /**
         * Delete all views for a specific content type
         *
         * @param string $type Content type (post, comment, activity, topic)
         * @return array Result with success status and count
         */
        public function delete_views_by_type( $type ) {
            global $wpdb;

            // Validate type
            $allowed_types = array( 'post', 'comment', 'activity', 'topic' );
            if ( ! in_array( $type, $allowed_types, true ) ) {
                return false;
            }

            // Get count before deletion
            $count = $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table_name} WHERE type = %s",
                $type
            ) );

            // Delete all views for this type
            $result = $wpdb->delete(
                $this->table_name,
                array( 'type' => $type ),
                array( '%s' )
            );

            if ( $result !== false ) {
                return array(
                    'success' => true,
                    'rows_affected' => (int) $count,
                    'message' => sprintf( esc_html__( 'Successfully deleted %d view records for %s.', WP_ULIKE_PRO_DOMAIN ), $count, $type )
                );
            }

            return false;
        }

        /**
         * Return an instance of this class.
         *
         * @return    object    A single instance of this class.
         */
        public static function get_instance() {
            // If the single instance hasn't been set, set it now.
            if ( null == self::$instance ) {
                self::$instance = new self;
            }

            return self::$instance;
        }
    }
}
