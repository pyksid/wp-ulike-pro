<?php
/**
 * Class for statistics v2 process
 * 
 * @package    wp-ulike-pro
 * @author     TechnoWich 2025
 * @link       https://wpulike.com
 */

// no direct access allowed
if ( ! defined('ABSPATH') ) {
    die();
}

if ( ! class_exists( 'WP_Ulike_Pro_Stats_V2' ) ) {

	class WP_Ulike_Pro_Stats_V2{

		// Private variables
		private $wpdb, $tables, $dateRange, $selectedStatus, $button_views;

		/**
		 * Instance of this class.
		 *
		 * @var      object
		 */
		protected static $instance  = null;

		/**
		 * Constructor
		 */
		function __construct(){
			global $wpdb;
			$this->wpdb   = $wpdb;
			$this->tables = array(
				'posts'      => 'ulike',
				'comments'   => 'ulike_comments',
				'activities' => 'ulike_activities',
				'topics'     => 'ulike_forums',
			);
			// Initialize views tracker
			if ( class_exists( 'WP_Ulike_Pro_Views' ) ) {
				$this->button_views = WP_Ulike_Pro_Views::get_instance();
			}
		}

		/**
		 * Return tables which has any data inside
		 *
		 * @return			Array
		 */
		public function get_tables(){
			// Tables buffer
			$get_tables = $this->tables;

			foreach ( $get_tables as $type => $table) {
				// If this table has no data, then unset it and continue...
				if( ! $this->count_logs( array ( "table" => $table ) ) ) {
					unset( $get_tables[ $type ] );
					continue;
				}

			}

			return $get_tables;
		}


		public function get_api_data() {
			// Fetch basic statistics
			$overview = $this->get_overview_data();

			// Fetch reports data
			$reports = $this->get_reports();

			// Fetch datasets for each table
			$datasets = $this->get_all_datasets();

			// Fetch count logs for each table with different time ranges
			$count_logs = $this->get_count_logs();

			// Combine all data into a structured output array
			$output = array(
				'overview' => $overview,
				'reports'  => $reports,
				'charts'   => $datasets,
				'metrics'  => $count_logs
			);

			return $output;
		}

		// Get basic statistics
		private function get_overview_data() {
			return array(
				'total'                => $this->count_all_logs('all'),
				'today'                => $this->count_all_logs('today')
			);
		}

		// Get reports data
		private function get_reports() {
			return array(
				'monthly_data'  => $this->get_aggregated_data_by_month(),
				'daily_data'    => $this->get_aggregated_data_by_date(),
				'device_types'  => $this->count_device_types(),
				'country_codes' => $this->count_country_codes(),
			);
		}

		private function get_all_datasets() {
			$tables = $this->get_tables();
			$datasets = array();

			foreach ($tables as $type => $table) {
				// check bbpress installation status
				if( ! function_exists( 'is_bbpress' ) && $type === 'topics' ) {
					continue;
				}

				// check buddpress installation status
				if( ! defined( 'BP_VERSION' ) && $type === 'activities' ) {
					continue;
				}

				$datasets[$type] = $this->get_dataset( $table );
			}

			return $datasets;
		}


		/**
		 * Get posts dataset
		 *
		 * @param string $table
		 * @return array
		 */
		public function get_dataset( $table ){
			$output  = array();
			// Get data
			$results = $this->select_data( $table );

			// Create chart dataset
			foreach( $results as $result ){
				if( isset( $result->labels ) & isset( $result->counts ) ){
					$output[]= [
						'date'  => wp_date( "Y-m-d", strtotime( $result->labels ) ),
						'total' => (int) $result->counts
					];
				}
			}

			return $output;
		}

		/**
		 * Get custom dataset for each type
		 *
		 * @param string $type
		 * @param string $start_date
		 * @param string $end_date
		 * @param array $selected_status
		 * @return void
		 */
		public function get_custom_dataset( $type, $start_date, $end_date, $selected_status ){
			$output  = array();

			$tables = $this->get_tables();
			if( isset( $tables[$type] ) ) {
				if( $start_date && $end_date ){
					$this->setDateRange( [
						'start' => $start_date,
						'end'   => $end_date
					]);
				}
				if( $selected_status ){
					$this->selectedStatus = $selected_status;
				}

				return $this->select_charts_data( $tables[$type] );
			}

		}

		/**
		 * Select charts data.
		 *
		 * @param string $table
		 * @return array
		 */
		public function select_charts_data( $table ) {
			$output = array();
			// Whitelist allowed table names to prevent SQL injection
			$allowed_tables = array( 'ulike', 'ulike_comments', 'ulike_activities', 'ulike_forums' );
			if ( ! in_array( $table, $allowed_tables, true ) ) {
				$table = 'ulike'; // Default fallback
			}
			$table  = esc_sql( $this->wpdb->prefix . $table );

			// Generate a unique cache key based on table, status, and date range
			$cache_key = 'charts_data_' . md5( $table . serialize( $this->selectedStatus ) . serialize( $this->dateRange ) );
			$cached    = wp_cache_get( $cache_key, WP_ULIKE_PRO_DOMAIN );
			if ( false !== $cached ) {
				return $cached;
			}

			$range = $this->getMySqlDateRange( $table );

			if ( empty( $this->selectedStatus ) ) {
				$dataInfo = $this->wpdb->get_results( "
					SELECT DATE(`date_time`) AS labels,
						COUNT(`date_time`) AS counts
					FROM `$table`
					WHERE $range
					GROUP BY labels
					ORDER BY labels ASC
				" );

				if ( $dataInfo ) {
					foreach ( $dataInfo as $result ) {
						if ( isset( $result->labels ) && isset( $result->counts ) ) {
							$output[] = [
								'date'  => wp_date( "Y-m-d", strtotime( $result->labels ) ),
								'total' => (int) $result->counts
							];
						}
					}
				}
			} else {
				// Sanitize status values and build safe IN clause
				$status_placeholders = array();
				$status_values = array();
				foreach ( $this->selectedStatus as $status ) {
					$status_sanitized = sanitize_text_field( $status );
					// Whitelist allowed status values
					$allowed_statuses = array( 'like', 'dislike', 'unlike', 'undislike' );
					if ( in_array( $status_sanitized, $allowed_statuses, true ) ) {
						$status_placeholders[] = '%s';
						$status_values[] = $status_sanitized;
					}
				}

				if ( ! empty( $status_placeholders ) ) {
					$placeholders_string = implode( ',', $status_placeholders );
					$query = "
						SELECT DATE(`date_time`) AS labels,
							status,
							COUNT(`date_time`) AS counts
						FROM `{$table}`
						WHERE {$range}
						AND `status` IN ({$placeholders_string})
						GROUP BY labels, status
						ORDER BY labels, status ASC";

					$dataInfo = $this->wpdb->get_results( $this->wpdb->prepare( $query, $status_values ) );
				} else {
					$dataInfo = array();
				}

				foreach ( $dataInfo as $row ) {
					$date   = $row->labels;
					$status = $row->status;
					$count  = $row->counts;

					if ( ! isset( $output[ $date ] ) ) {
						$output[ $date ] = [ 'date' => $date ];
					}
					$output[ $date ][ $status ] = (int) $count;
				}

				if ( ! empty( $output ) ) {
					$output = array_values( $output );
					if ( ! empty( $output ) ) {
						foreach ( $output as $key => $args ) {
							foreach ( $this->selectedStatus as $sv ) {
								if ( ! isset( $args[ $sv ] ) ) {
									$output[ $key ][ $sv ] = 0;
								}
							}
						}
					}
				}
			}

			// Cache the result for 10 seconds to keep data nearly real-time
			wp_cache_set( $cache_key, $output, WP_ULIKE_PRO_DOMAIN, 10 );
			return $output;
		}

	/**
	 * Get MySQL date range format.
	 *
	 * @param string $table
	 * @return string
	 */
	private function getMySqlDateRange( $table ) {
		$table = esc_sql( $table );

		// Return early if date range is set
		if ( ! empty( $this->dateRange ) ) {
			// Sanitize date values - ensure they are valid date format
			$start = isset( $this->dateRange['start'] ) ? sanitize_text_field( $this->dateRange['start'] ) : '';
			$end = isset( $this->dateRange['end'] ) ? sanitize_text_field( $this->dateRange['end'] ) : '';

			// Validate date format (YYYY-MM-DD)
			if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $start ) ) {
				$start = '';
			}
			if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $end ) ) {
				$end = '';
			}

			if ( empty( $start ) || empty( $end ) ) {
				return '1=1'; // Return neutral condition if dates are invalid
			}

			// Escape date values for safe use in SQL (since this string is inserted into other queries)
			$start_escaped = esc_sql( $start );
			$end_escaped = esc_sql( $end );

			if ( $start === $end ) {
				return sprintf( "DATE(`date_time`) = '%s'", $start_escaped );
			} else {
				return sprintf( "DATE(`date_time`) BETWEEN '%s' AND '%s'", $start_escaped, $end_escaped );
			}
		}

			// Cache for the latest date query
			$cache_key = 'latest_date_' . $table;
			$latest_date = wp_cache_get( $cache_key, WP_ULIKE_PRO_DOMAIN );

			if ( false === $latest_date ) {
				$latest_date = $this->wpdb->get_var( "SELECT MAX(date_time) FROM `$table`" );
				wp_cache_set( $cache_key, $latest_date, WP_ULIKE_PRO_DOMAIN, 10 );
			}

			if( empty( $latest_date ) ){
				$latest_date = current_time( 'mysql' );
			}

		// Calculate the 30 days range
		$latest_date_timestamp = strtotime( $latest_date );
		if ( false === $latest_date_timestamp ) {
			$latest_date_timestamp = current_time( 'timestamp' );
		}
		$date_30_days_before   = date( 'Y-m-d', $latest_date_timestamp - DAY_IN_SECONDS * 30 );
		$latest_date_formatted = date( 'Y-m-d', $latest_date_timestamp );

		// Escape date values for safe use in SQL (since this string is inserted into other queries)
		$date_30_days_before_escaped = esc_sql( $date_30_days_before );
		$latest_date_formatted_escaped = esc_sql( $latest_date_formatted );

		return sprintf( "DATE(`date_time`) BETWEEN '%s' AND '%s'", $date_30_days_before_escaped, $latest_date_formatted_escaped );
		}


	/**
	 * Set date range with our format
	 *
	 * @param array $rawDate
	 * @return void
	 */
	private function setDateRange( $rawDate ){
		if ( empty( $rawDate['start'] ) ) {
			$this->dateRange = [];
			return;
		}

		// Sanitize and validate start date
		$start_raw = sanitize_text_field( $rawDate['start'] );
		$start_timestamp = strtotime( $start_raw );
		if ( false === $start_timestamp ) {
			$this->dateRange = [];
			return;
		}
		$this->dateRange['start'] = date( "Y-m-d", $start_timestamp );

		// Sanitize and validate end date
		if ( ! empty( $rawDate['end'] ) ) {
			$end_raw = sanitize_text_field( $rawDate['end'] );
			$end_timestamp = strtotime( $end_raw );
			if ( false !== $end_timestamp ) {
				$this->dateRange['end'] = date( "Y-m-d", $end_timestamp );
			} else {
				$this->dateRange['end'] = $this->dateRange['start'];
			}
		} else {
			$this->dateRange['end'] = $this->dateRange['start'];
		}
	}


		/**
		 * Get The Logs Data From Tables
		 *
		 * @return Object
		 */
		public function select_data( $table ){
			// Whitelist allowed table names to prevent SQL injection
			$allowed_tables = array( 'ulike', 'ulike_comments', 'ulike_activities', 'ulike_forums' );
			if ( ! in_array( $table, $allowed_tables, true ) ) {
				$table = 'ulike'; // Default fallback
			}

			$table_name = esc_sql( $this->wpdb->prefix . $table );
			$data_limit = absint( apply_filters( 'wp_ulike_stats_data_limit', 30 ) );
			$date_range = $this->getMySqlDateRange( $table_name );

			// Build query with properly escaped values
			$query  = "
				SELECT DATE(date_time) AS labels,
				count(date_time) AS counts
				FROM `{$table_name}`
				WHERE {$date_range}
				GROUP BY labels
				ORDER BY labels ASC
				LIMIT {$data_limit}";

			$result = $this->wpdb->get_results( $query );

			if( empty( $result ) ) {
				$result = new stdClass();
				$result->labels = $result->counts = NULL;
			}

			return $result;
		}

		/**
		 * Count all logs from the tables
		 *
		 * @since 3.5
		 * @param string $date
		 * @return integer
		 */
		public function count_all_logs( $date = 'all' ){
			return wp_ulike_count_all_logs( $date );
		}

		/**
		 * Count all logs
		 *
		 * @return array
		 */
		private function get_count_logs() {
			$tables = $this->get_tables();
			$count_logs = array();

			foreach ($tables as $type => $table) {

				// check bbpress installation status
				if( ! function_exists( 'is_bbpress' ) && $type === 'topics' ) {
					continue;
				}

				// check buddpress installation status
				if( ! defined( 'BP_VERSION' ) && $type === 'activities' ) {
					continue;
				}

				$count_logs[$type] = array(
					'week'       => $this->count_logs(array("table" => $table, "date" => 'week')),
					'last_week'  => $this->count_logs(array("table" => $table, "date" => 'last_week')),
					'month'      => $this->count_logs(array("table" => $table, "date" => 'month')),
					'last_month' => $this->count_logs(array("table" => $table, "date" => 'last_month')),
					'year'       => $this->count_logs(array("table" => $table, "date" => 'year')),
					'last_year'  => $this->count_logs(array("table" => $table, "date" => 'last_year')),
					'all'        => $this->count_logs(array("table" => $table, "date" => 'all'))
				);
			}

			return $count_logs;
		}

		/**
		 * Count logs by table
		 *
		 * @param array $args
		 * @return void
		 */
		public function count_logs( $args = array() ){
			//Main Data
			$defaults  = array(
				"table" => 'ulike',
				"date"  => 'all'
			);

			$parsed_args = wp_parse_args( $args, $defaults );

			// Extract variables safely instead of using extract()
			$table = isset( $parsed_args['table'] ) ? $parsed_args['table'] : 'ulike';
			$date = isset( $parsed_args['date'] ) ? $parsed_args['date'] : 'all';

			$cache_key = sanitize_key( sprintf( 'count_logs_for_%s_table_in_%s_daterange', $table, is_array($date) ? implode('_', $date) : $date ) );

			if( $date === 'all' ){
				$count_all_logs = wp_ulike_get_meta_data( 1, 'statistics', $cache_key, true );
				if( ! empty( $count_all_logs ) || is_numeric( $count_all_logs ) ){
					return absint( $count_all_logs );
				}
			}

			$counter_value = wp_cache_get( $cache_key, WP_ULIKE_PRO_DOMAIN );

			// Make a cachable query to get new like count from all tables
			if( false === $counter_value ){
				$query = sprintf( "SELECT COUNT(*) FROM %s WHERE 1=1", $this->wpdb->prefix . $table );
				$query .= wp_ulike_get_period_limit_sql( $date );

				$counter_value = $this->wpdb->get_var( $query );
				wp_cache_set( $cache_key, $counter_value, WP_ULIKE_PRO_DOMAIN, 10 );
			}

			if( $date === 'all' ){
				wp_ulike_update_meta_data( 1, 'statistics', $cache_key, $counter_value );
			}

	        return  empty( $counter_value ) ? 0 : absint( $counter_value );
		}

		/**
		 * Count engaged users by table
		 *
		 * @param array $args
		 * @return void
		 */
		public function count_total_interactions( $args = array() ){
			//Main Data
			$defaults  = array(
				"table" => 'ulike',
				"date"  => 'all'
			);

			$parsed_args = wp_parse_args( $args, $defaults );

			// Extract variables safely instead of using extract()
			$table = isset( $parsed_args['table'] ) ? $parsed_args['table'] : 'ulike';
			$date = isset( $parsed_args['date'] ) ? $parsed_args['date'] : 'all';

			$cache_key = sanitize_key( sprintf( 'count_total_interactions_for_%s_table_in_%s_daterange', $table, is_array($date) ? implode('_', $date) : $date ) );

			$engaged_users = wp_cache_get( $cache_key, WP_ULIKE_PRO_DOMAIN );

			// Make a cachable query to get new like count from all tables
			if( false === $engaged_users ){
				$query = sprintf( "SELECT COUNT(DISTINCT user_id) FROM %s WHERE 1=1", $this->wpdb->prefix . $table );
				$query .= wp_ulike_get_period_limit_sql( $date );

				$engaged_users = $this->wpdb->get_var( $query );
				wp_cache_set( $cache_key, $engaged_users, WP_ULIKE_PRO_DOMAIN, 10 );
			}

	        return  empty( $engaged_users ) ? 0 : absint( $engaged_users );
		}

		/**
		 * Get top items of each type
		 *
		 * @param string $type
		 * @param array|string $date_range
		 * @param array $args
		 * @return array
		 */
		public function get_top( $args, $date_range = NULL ){

			if( ! empty( $date_range ) ){
				$this->setDateRange( $date_range );
				$args['period'] = $this->dateRange;
			} else {
				$args['period'] = NULL;
			}

			if( empty( $args['type'] ) ){
				return;
			}

			switch( $args['type']  ){
				case 'post':
					return [
						'items' => $this->top_posts( $args ),
						'types' => wp_ulike_pro_get_public_post_types(),
						'total' => $this->get_top_counts( $args, 'posts' ),
					];
					break;
				case 'comment':
					return [
						'items' => $this->top_comments( $args ),
						'total' => $this->get_top_counts( $args, 'comments' ),
					];
				break;
				case 'activity':
					return [
						'items' => $this->top_activities( $args ),
						'total' => $this->get_top_counts( $args, 'activities' ),
					];
				break;
				case 'topic':
					return [
						'items' => $this->top_topics( $args ),
						'total' => $this->get_top_counts( $args, 'topics' ),
					];
				break;
				case 'engagers':
					return [
						'items' => $this->top_engagers( $args ),
						'total' => $this->get_top_counts( $args, 'engagers' ),
					];
				break;
				default:
					return;
			}
		}

		/**
		 * Get top items count
		 *
		 * @param array $args
		 * @param string $data_type
		 * @return void
		 */
		public function get_top_counts( $args, $data_type ){
			if( ! defined( 'BP_VERSION' ) && $data_type === 'activities' ) {
				return 0;
			}

			if( $data_type === 'engagers' ){
				return wp_ulike_get_top_enagers_total_number( $args['period'], $args['status']  );
			}

            return wp_ulike_get_popular_items_total_number( $args  );
		}

		/**
		 * Get top posts
		 *
		 * @param array $args
		 * @return void
		 */
		public function top_posts( $args = array() ) {

			$defaults = array(
				"type"       => 'post',
				"rel_type"   => '',
				"is_popular" => true,
				"status"     => array( 'like', 'dislike' ),
				"user_id"    => '',
				"order"      => 'DESC',
				"period"     => 'all',
				"offset"     => 1,
				"limit"      => 10
			);
			// Parse args
			$settings = wp_parse_args( $args, $defaults );

			$posts = wp_ulike_pro_get_posts_query( $settings );

			$result = [];

			if($posts && $posts->have_posts()) {
				$is_distinct        = wp_ulike_setting_repo::isDistinct('post');

				while($posts->have_posts()) {
					$posts->the_post();

					$post_id = wp_ulike_get_the_id();

					$like_count    = wp_ulike_get_counter_value( $post_id, 'post', 'like', $is_distinct, $settings['period'] ?? NULL );
					$dislike_count = wp_ulike_get_counter_value( $post_id, 'post', 'dislike', $is_distinct, $settings['period'] ?? NULL );
					$thumbnail     = get_the_post_thumbnail_url( $post_id, 'thumbnail');
					$engaged_users = wp_ulike_get_likers_list_per_post( 'ulike', 'post_id', $post_id, NULL );

					$engaged_users_info = [];
					foreach ( $engaged_users as $user ) {
						$user_info	= get_user_by( 'id', $user );
						// Check user existence
						if( ! $user_info ){
							continue;
						}

						$engaged_users_info[] = [
							'name'     => esc_attr( $user_info->display_name ),
							'avatar'   => get_avatar_url( $user_info->user_email, [ 'size' => 48 ] ),
							'role'     => $this->get_i18n_role_name( $user_info->roles[0] ?? esc_html__('Guest User', WP_ULIKE_PRO_DOMAIN) ),
							'activity' => wp_ulike_pro_get_user_latest_activity( $post_id, $user, 'post' )
						];
					}


					if( empty( $thumbnail ) ){
						$thumbnail = WP_ULIKE_PRO_ADMIN_URL . '/assets/img/no-image.svg';
					}

					$comment_number = get_comments_number($post_id);

					// Calculate engagement rate and views
					$period = $settings['period'] ?? 'all';
					$engagement_data = $this->calculate_engagement_rate( $post_id, 'post', $like_count, $dislike_count, $period );
					$total_views = $engagement_data['total_views'];
					$engagement_rate = $engagement_data['engagement_rate'];

					// Calculate engagement growth (period-over-period)
					$engagement_growth = $this->calculate_engagement_growth( $post_id, 'post', $like_count, $dislike_count, $period, $engagement_rate );

					// Build meta_data array conditionally
					$meta_data = [
						'Published'  => get_the_date( '', $post_id ),
						'Comments'   => $comment_number,
					];

					// Only include Views if it has a value
					if ( $total_views > 0 ) {
						$meta_data['Views'] = $total_views;
					}

					// Build engagement array (separate from meta_data for React app)
					$engagement = array();
					if ( $engagement_rate > 0 ) {
						$engagement['rate'] = round( $engagement_rate, 2 );
						$engagement['growth'] = $engagement_growth;
					}

					$result[] = [
						'id'             => $post_id,
						'title'          => get_the_title(),
						'image'          => $thumbnail,
						'permalink'      => get_permalink(),
						'likes_count'    => $like_count,
						'dislikes_count' => $dislike_count,
						'engaged_users'  => $engaged_users_info,
						'meta_data'      => $meta_data,
						'engagement'     => $engagement,
					];
				}
				wp_reset_postdata(); // VERY VERY IMPORTANT
			}

			return $result;
		}

		/**
		 * Get top comments
		 *
		 * @param array $args
		 * @return void
		 */
		public function top_comments( $args = array() ) {

			$defaults = array(
				"type"       => 'comment',
				"rel_type"   => '',
				"is_popular" => true,
				"status"     => array( 'like', 'dislike' ),
				"user_id"    => '',
				"order"      => 'DESC',
				"period"     => 'all',
				"offset"     => 1,
				"limit"      => 10
			);
			// Parse args
			$settings = wp_parse_args( $args, $defaults );

			$comments = wp_ulike_pro_get_comments_query( $settings );

			$result = [];

			if( $comments ) {
				$is_distinct        = wp_ulike_setting_repo::isDistinct('comment');

				foreach ( $comments as $comment ) {

					$like_count    = wp_ulike_get_counter_value( $comment->comment_ID, 'comment', 'like', $is_distinct, $settings['period'] ?? NULL );
					$dislike_count = wp_ulike_get_counter_value( $comment->comment_ID, 'comment', 'dislike', $is_distinct, $settings['period'] ?? NULL );

					$engaged_users = wp_ulike_get_likers_list_per_post( 'ulike_comments', 'comment_id', $comment->comment_ID, NULL );

					$engaged_users_info = [];
					foreach ( $engaged_users as $user ) {
						$user_info	= get_user_by( 'id', $user );
						// Check user existence
						if( ! $user_info ){
							continue;
						}

						$engaged_users_info[] = [
							'name'     => esc_attr( $user_info->display_name ),
							'avatar'   => get_avatar_url( $user_info->user_email, [ 'size' => 100 ] ),
							'role'     => $this->get_i18n_role_name( $user_info->roles[0] ?? esc_html__('Guest User', WP_ULIKE_PRO_DOMAIN) ),
							'activity' => wp_ulike_pro_get_user_latest_activity( $comment->comment_ID, $user, 'comment' )
						];
					}

					$comment_number = get_comments_number( $comment->comment_post_ID );

					// Calculate engagement rate and views
					$period = $settings['period'] ?? 'all';
					$engagement_data = $this->calculate_engagement_rate( $comment->comment_ID, 'comment', $like_count, $dislike_count, $period );
					$total_views = $engagement_data['total_views'];
					$engagement_rate = $engagement_data['engagement_rate'];

					// Calculate engagement growth (period-over-period)
					$engagement_growth = $this->calculate_engagement_growth( $comment->comment_ID, 'comment', $like_count, $dislike_count, $period, $engagement_rate );

					// Build meta_data array conditionally
					$meta_data = [
						'Published'  => get_comment_date( '', $comment->comment_ID ),
						'By'         => esc_attr( $comment->comment_author ),
					];

					// Only include Views if it has a value
					if ( $total_views > 0 ) {
						$meta_data['Views'] = $total_views;
					}

					// Build engagement array (separate from meta_data for React app)
					$engagement = array();
					if ( $engagement_rate > 0 ) {
						$engagement['rate'] = round( $engagement_rate, 2 );
						$engagement['growth'] = $engagement_growth;
					}

					$result[] = [
						'id'             => $comment->comment_ID,
						'title'          => get_the_title($comment->comment_post_ID),
						'image'          => get_avatar_url( $comment->comment_author_email, [ 'size' => 100 ] ),
						'permalink'      => get_comment_link($comment->comment_ID),
						'likes_count'    => $like_count,
						'dislikes_count' => $dislike_count,
						'engaged_users'  => $engaged_users_info,
						'meta_data'      => $meta_data,
						'engagement'     => $engagement,
					];
				}
			}

			return $result;
		}

		/**
		 * Get top topics
		 *
		 * @param array $args
		 * @return void
		 */
		public function top_topics( $args = array() ) {

			if( ! function_exists( 'is_bbpress' ) ) {
				return [];
			}

			$defaults = array(
				"type"       => 'topic',
				"rel_type"   => '',
				"is_popular" => true,
				"status"     => array( 'like', 'dislike' ),
				"user_id"    => '',
				"order"      => 'DESC',
				"period"     => 'all',
				"offset"     => 1,
				"limit"      => 10
			);
			// Parse args
			$settings = wp_parse_args( $args, $defaults );

			$topics = wp_ulike_pro_get_posts_query( $settings );

			$result = [];

			if($topics && $topics->have_posts()) {
				$is_distinct = wp_ulike_setting_repo::isDistinct('topic');

				while($topics->have_posts()) {
					$topics->the_post();

					$topic_id      = get_the_ID();
					$like_count    = wp_ulike_get_counter_value( $topic_id, 'topic', 'like', $is_distinct, $settings['period'] ?? NULL );
					$dislike_count = wp_ulike_get_counter_value( $topic_id, 'topic', 'dislike', $is_distinct, $settings['period'] ?? NULL );

					$engaged_users = wp_ulike_get_likers_list_per_post( 'ulike_forums', 'topic_id', $topic_id, NULL );

					$engaged_users_info = [];
					foreach ( $engaged_users as $user ) {
						$user_info	= get_user_by( 'id', $user );
						// Check user existence
						if( ! $user_info ){
							continue;
						}

						$engaged_users_info[] = [
							'name'     => esc_attr( $user_info->display_name ),
							'avatar'   => get_avatar_url( $user_info->user_email, [ 'size' => 100 ] ),
							'role'     => $this->get_i18n_role_name( $user_info->roles[0] ?? esc_html__('Guest User', WP_ULIKE_PRO_DOMAIN) ),
							'activity' => wp_ulike_pro_get_user_latest_activity( $topic_id, $user, 'topic' )
						];
					}

					// Calculate engagement rate and views
					$period = $settings['period'] ?? 'all';
					$engagement_data = $this->calculate_engagement_rate( $topic_id, 'topic', $like_count, $dislike_count, $period );
					$total_views = $engagement_data['total_views'];
					$engagement_rate = $engagement_data['engagement_rate'];

					// Calculate engagement growth (period-over-period)
					$engagement_growth = $this->calculate_engagement_growth( $topic_id, 'topic', $like_count, $dislike_count, $period, $engagement_rate );

					$author_avatar = NULL;
					if ( ! bbp_is_topic_anonymous( $topic_id ) ) {
						$author_avatar = get_avatar_url( bbp_get_topic_author_id( $topic_id ), 100 );
					} else {
						$author_avatar = get_avatar_url( get_post_meta( $topic_id, '_bbp_anonymous_email', true ), 100 );
					}

					// Build meta_data array conditionally
					$meta_data = [
						'Published'  => bbp_get_topic_post_date( $topic_id ),
						'By'         => bbp_get_topic_author_display_name( $topic_id ),
					];

					// Only include Views if it has a value
					if ( $total_views > 0 ) {
						$meta_data['Views'] = $total_views;
					}

					// Build engagement array (separate from meta_data for React app)
					$engagement = array();
					if ( $engagement_rate > 0 ) {
						$engagement['rate'] = round( $engagement_rate, 2 );
						$engagement['growth'] = $engagement_growth;
					}

					$result[] = [
						'id'             => $topic_id,
						'title'          => bbp_get_forum_title( $topic_id ),
						'image'          => $author_avatar,
						'permalink'      => 'topic' === get_post_type( $topic_id ) ? bbp_get_topic_permalink( $topic_id ) : bbp_get_reply_url( $topic_id ),
						'likes_count'    => $like_count,
						'dislikes_count' => $dislike_count,
						'engaged_users'  => $engaged_users_info,
						'meta_data'      => $meta_data,
						'engagement'     => $engagement,
					];
				}
				wp_reset_postdata(); // VERY VERY IMPORTANT
			}

			return $result;
		}

		/**
		 * Get top activities
		 *
		 * @param array $args
		 * @return void
		 */
		public function top_activities( $args = array() ) {

			if( ! defined( 'BP_VERSION' ) ) {
				return [];
			}

			$defaults = array(
				"type"       => 'activity',
				"rel_type"   => '',
				"is_popular" => true,
				"status"     => array( 'like', 'dislike' ),
				"user_id"    => '',
				"order"      => 'DESC',
				"period"     => 'all',
				"offset"     => 1,
				"limit"      => 10
			);
			// Parse args
			$settings = wp_parse_args( $args, $defaults );

			$activities = wp_ulike_pro_get_activity_query( $settings );

			$result = [];

			if( $activities ) {
				$is_distinct = wp_ulike_setting_repo::isDistinct('activity');

				foreach ( $activities as $activity ) {

					$like_count    = wp_ulike_get_counter_value( $activity->id, 'activity', 'like', $is_distinct, $settings['period'] ?? NULL );
					$dislike_count = wp_ulike_get_counter_value( $activity->id, 'activity', 'dislike', $is_distinct, $settings['period'] ?? NULL );

					$engaged_users = wp_ulike_get_likers_list_per_post( 'ulike_activities', 'activity_id', $activity->id, NULL );

					$engaged_users_info = [];
					foreach ( $engaged_users as $user ) {
						$user_info	= get_user_by( 'id', $user );
						// Check user existence
						if( ! $user_info ){
							continue;
						}

						$engaged_users_info[] = [
							'name'     => esc_attr( $user_info->display_name ),
							'avatar'   => get_avatar_url( $user_info->user_email, [ 'size' => 100 ] ),
							'role'     => $this->get_i18n_role_name( $user_info->roles[0] ?? esc_html__('Guest User', WP_ULIKE_PRO_DOMAIN) ),
							'activity' => wp_ulike_pro_get_user_latest_activity( $activity->id, $user, 'activity' )
						];
					}

					$author = get_user_by( 'id', $activity->user_id );

					// Calculate engagement rate and views
					$period = $settings['period'] ?? 'all';
					$engagement_data = $this->calculate_engagement_rate( $activity->id, 'activity', $like_count, $dislike_count, $period );
					$total_views = $engagement_data['total_views'];
					$engagement_rate = $engagement_data['engagement_rate'];

					// Calculate engagement growth (period-over-period)
					$engagement_growth = $this->calculate_engagement_growth( $activity->id, 'activity', $like_count, $dislike_count, $period, $engagement_rate );

					// Build meta_data array conditionally
					$meta_data = [
						'Published'  => wp_ulike_date_i18n( $activity->date_recorded ),
						'By'         => esc_attr( $author->display_name ),
					];

					// Only include Views if it has a value
					if ( $total_views > 0 ) {
						$meta_data['Views'] = $total_views;
					}

					// Build engagement array (separate from meta_data for React app)
					$engagement = array();
					if ( $engagement_rate > 0 ) {
						$engagement['rate'] = round( $engagement_rate, 2 );
						$engagement['growth'] = $engagement_growth;
					}

					$result[] = [
						'id'             => $activity->id,
						'title'          => ! empty( $activity->content ) ? wp_strip_all_tags( $activity->content ) : wp_strip_all_tags( $activity->action ),
						'image'          => get_avatar_url( $author->user_email, [ 'size' => 100 ] ),
						'permalink'      => function_exists('bp_activity_get_permalink') ? bp_activity_get_permalink( $activity->id ) : '',
						'likes_count'    => $like_count,
						'dislikes_count' => $dislike_count,
						'engaged_users'  => $engaged_users_info,
						'meta_data'      => $meta_data,
						'engagement'     => $engagement,
					];
				}
			}

			return $result;
		}

		/**
		 * Get top engagers list
		 *
		 * @param array $args
		 * @return array
		 */
		public function top_engagers( $args ){

			$limit  = $args['limit'] ?? 10;
			$period = $args['period'] ?? 'all';
			$offset = $args['offset'] ?? 1;
			$status = $args['status'] ?? ['like','dislike'];

			$top_likers = wp_ulike_get_best_likers_info(  $limit, $period, $offset, $status );
			$result     = [];

			if( ! empty( $top_likers ) ){
				foreach ( $top_likers as $user ) {
					$user_ID         = stripslashes( $user->user_id );
					$userdata        = get_userdata( $user_ID );
					$username        = empty( $userdata ) ? esc_html__('Guest User', WP_ULIKE_PRO_DOMAIN) : esc_attr( $userdata->display_name );
					$latest_activity = wp_ulike_pro_get_latest_user_activity_date( $user_ID );

					$result[] = [
						'id'               => $user_ID,
						'image'            => get_avatar_url( $user_ID, ['size' => 256] ),
						'title'            => $username,
						'permalink'        => get_edit_profile_url( $user_ID ),
						'last_activity'    => $latest_activity,
						'likes_count'      => absint( $user->likeCount ?? 0 ),
						'dislikes_count'   => absint( $user->dislikeCount ?? 0 ),
						'unlikes_count'    => absint( $user->unlikeCount ?? 0 ),
						'undislikes_count' => absint( $user->undislikeCount ?? 0 ),
					];
				}
			}

			return $result;
		}

		/**
		 * Get aggregated data from multiple tables using object caching.
		 *
		 * @param string   $cache_key         Cache key for the result.
		 * @param string   $interval          Time interval, e.g., '5 MONTH'.
		 * @param string   $selectExpression  SQL expression to format the date (group key).
		 * @param string   $orderByExpression SQL expression for ordering the group key.
		 * @param callable $formatter         Callback to format each row.
		 * @return array
		 */
		private function get_aggregated_data($cache_key, $interval, $selectExpression, $orderByExpression, callable $formatter) {
			// Try to fetch a cached result.
			$cached = wp_cache_get($cache_key, WP_ULIKE_PRO_DOMAIN);
			if ( false !== $cached ) {
				return $cached;
			}

			// Validate interval format to avoid potential SQL injection.
			if ( ! preg_match('/^\d+\s+(DAY|MONTH|YEAR)$/i', $interval) ) {
				// Fallback to a default interval if invalid.
				$interval = '5 MONTH';
			}

			// Build the union query to aggregate date_time from all relevant tables.
			$unionQuery = "
				SELECT DATE(date_time) AS date_time FROM {$this->wpdb->prefix}{$this->tables['posts']}
					WHERE date_time >= NOW() - INTERVAL {$interval}
				UNION ALL
				SELECT DATE(date_time) AS date_time FROM {$this->wpdb->prefix}{$this->tables['activities']}
					WHERE date_time >= NOW() - INTERVAL {$interval}
				UNION ALL
				SELECT DATE(date_time) AS date_time FROM {$this->wpdb->prefix}{$this->tables['comments']}
					WHERE date_time >= NOW() - INTERVAL {$interval}
				UNION ALL
				SELECT DATE(date_time) AS date_time FROM {$this->wpdb->prefix}{$this->tables['topics']}
					WHERE date_time >= NOW() - INTERVAL {$interval}
			";

			// Build the full query using the provided SELECT expression and ORDER BY clause.
			$query = "
				SELECT
					{$selectExpression} AS period,
					COUNT(*) AS total_count
				FROM (
					{$unionQuery}
				) AS combined
				GROUP BY period
				ORDER BY {$orderByExpression} ASC
			";

			$results = $this->wpdb->get_results($query);

			$data = [];
			if ( ! empty($results) ) {
				foreach ( $results as $result ) {
					$data[] = $formatter($result);
				}
			}

			// Cache the result for a short duration (10 seconds) to ensure near real-time data.
			wp_cache_set($cache_key, $data, WP_ULIKE_PRO_DOMAIN, 10);

			return $data;
		}

		/**
		 * Get aggregated data by past months.
		 *
		 * @param string $interval Time interval (e.g., '5 MONTH').
		 * @return array
		 */
		public function get_aggregated_data_by_month($interval = '5 MONTH') {
			$cache_key = 'aggregated_data_by_month_' . md5($interval);
			return $this->get_aggregated_data(
				$cache_key,
				$interval,
				"DATE_FORMAT(date_time, '%Y-%m')",  // Group by year-month.
				"DATE_FORMAT(date_time, '%Y-%m')",  // Order by year-month.
				function( $result ) {
					// Convert the period (year-month) to a timestamp and format it.
					$date = DateTime::createFromFormat('Y-m', $result->period);
					return [
						'total_count' => absint($result->total_count),
						'month_name'  => wp_date("F Y", $date->getTimestamp())
					];
				}
			);
		}

		/**
		 * Get aggregated data by past days.
		 *
		 * @param string $interval Time interval (e.g., '6 DAY').
		 * @return array
		 */
		public function get_aggregated_data_by_date($interval = '6 DAY') {
			$cache_key = 'aggregated_data_by_date_' . md5($interval);
			return $this->get_aggregated_data(
				$cache_key,
				$interval,
				"DATE(date_time)",   // Group by date.
				"DATE(date_time)",   // Order by date.
				function( $result ) {
					return [
						'total_count' => absint($result->total_count),
						'vote_date'   => wp_date("Y-m-d", strtotime($result->period))
					];
				}
			);
		}

		// Method to count device types across all relevant tables
		public function count_device_types( $dateRange = [], $type = 'device' ) {
			// Set the date range if provided
			if (!empty($dateRange)) {
				$this->setDateRange($dateRange);
			}

 			// Allowed columns - whitelist to prevent SQL injection
			$allowed_types = ['device', 'os', 'browser'];
			if (!in_array($type, $allowed_types, true)) {
				$type = 'device'; // fallback
			}

			// Sanitize column name for SQL
			$type = esc_sql( $type );

			// Check cache first
			$cache_key = "{$type}_types_" . md5(json_encode($this->dateRange));
			$counts = wp_cache_get($cache_key, WP_ULIKE_PRO_DOMAIN);
			if ($counts !== false) {
				return $counts;
			}

			// Initialize result array
			$counts = [];

			// Query each table
			foreach ($this->tables as $content_type => $table_name) {
				$table = esc_sql( $this->wpdb->prefix . $table_name );
				$date_condition = $this->getMySqlDateRange($table);

				// Build query with properly escaped column name
				$query = "
					SELECT
						TRIM(SUBSTRING_INDEX(`{$type}`, ' ', GREATEST(CHAR_LENGTH(`{$type}`) - CHAR_LENGTH(REPLACE(`{$type}`, ' ', '')), 1))) AS device_group,
						COUNT(DISTINCT user_id) AS device_count
					FROM `{$table}`
					WHERE {$date_condition}
						AND `{$type}` != ''
						AND `{$type}` IS NOT NULL
					GROUP BY device_group
					ORDER BY device_count DESC
				";

				$results = $this->wpdb->get_results($query, ARRAY_A);

				foreach ($results as $row) {
					$value = $row['device_group'];
					$count = (int) $row['device_count'];

					if (isset($counts[$value])) {
						$counts[$value] += $count;
					} else {
						$counts[$value] = $count;
					}
				}
			}

			// Cache for 10 seconds
			wp_cache_set($cache_key, $counts, WP_ULIKE_PRO_DOMAIN, 10);

			return $counts;
		}

		public function count_country_codes( $dateRange = [], $selected_status = [], $types = [] ) {
			// Set the date range if provided
			if (!empty($dateRange)) {
				$this->setDateRange($dateRange);
			}

			// Generate a unique cache key based on the date range
			$cache_key = 'country_counts_' . md5(json_encode([
				'dateRange' => $this->dateRange,
				'status'    => $selected_status,
				'types'     => $types,
			]));
			$country_counts = wp_cache_get($cache_key, WP_ULIKE_PRO_DOMAIN);
			if (false !== $country_counts) {
				$decoded = json_decode($country_counts, true);
				// Calculate growth if not already included
				if (!empty($decoded)) {
					$first_key = key($decoded);
					if ($first_key && !isset($decoded[$first_key]['growth'])) {
						$decoded = $this->add_country_growth($decoded, $dateRange, $selected_status, $types);
					}
				}
				return $decoded;
			}

			// Initialize result array
			$country_counts = [];

			// Loop through each table and fetch country codes
			foreach ($this->tables as $content_type => $table_name) {
				// check type filter
				if( ! empty( $types ) && ! in_array( $content_type, $types ) ){
					continue;
				}

				$table = "{$this->wpdb->prefix}{$table_name}";
				$date_condition = $this->getMySqlDateRange($table);

				// Prepare query based on the selected status
				$status_condition = '';
				if (!empty($selected_status)) {
					// Map selected statuses to prepared query format
					$selectedStatus = array_map(function($status) {
						return $this->wpdb->prepare('%s', $status);
					}, $selected_status);

					// Add status filter to the query
					$status_condition = "AND `status` IN (" . implode(',', $selectedStatus) . ")";
				}

				// Prepare the query with the additional status condition if applicable
				$query = "
					SELECT country_code, COUNT(DISTINCT user_id) AS count
				";

				// Add status to the query if statuses are provided
				if (!empty($selected_status)) {
					$query .= ", `status`";
				}

				$query .= "
					FROM `$table`
					WHERE $date_condition
					AND country_code IS NOT NULL
					AND country_code != ''
					$status_condition
					GROUP BY country_code";

				// Add status to GROUP BY if statuses are provided
				if (!empty($selected_status)) {
					$query .= ", `status`";
				}

				// Fetch results
				$results = $this->wpdb->get_results($query, ARRAY_A);

				// Sum up the counts across all content types
				foreach ($results as $row) {
					$country_code = $row['country_code'];
					$count = (int) $row['count'];

					if (empty($selected_status)) {
						// If no selected status, group by total
						if (!isset($country_counts[$country_code])) {
							$country_counts[$country_code] = [];
						}
						if (isset($country_counts[$country_code]['total'])) {
							$country_counts[$country_code]['total'] += $count;
						} else {
							$country_counts[$country_code]['total'] = $count;
						}
					} else {
						// If selected statuses exist, group by both country_code and status
						$status = $row['status'];
						if (!isset($country_counts[$country_code])) {
							$country_counts[$country_code] = [];
						}
						if (isset($country_counts[$country_code][$status])) {
							$country_counts[$country_code][$status] += $count;
						} else {
							$country_counts[$country_code][$status] = $count;
						}
					}
				}
			}

			// Calculate and add growth for each country
			// Use the actual dateRange that was used (either provided or from class property)
			$actual_dateRange = ! empty( $dateRange ) ? $dateRange : ( ! empty( $this->dateRange ) ? $this->dateRange : null );
			$country_counts = $this->add_country_growth($country_counts, $actual_dateRange, $selected_status, $types);

			// Cache the result for 10 seconds
			wp_cache_set($cache_key, json_encode($country_counts), WP_ULIKE_PRO_DOMAIN, 10);

			return $country_counts;
		}

		/**
		 * Calculate growth percentage for each country (period-over-period comparison)
		 * Similar to Google Analytics active users map growth calculation
		 *
		 * @param array $current_counts Current period country counts
		 * @param array $dateRange      Current date range
		 * @param array $selected_status Selected status filter
		 * @param array $types          Selected content types
		 * @return array                Country counts with growth added
		 */
		private function add_country_growth( $current_counts, $dateRange = [], $selected_status = [], $types = [] ) {
			// Determine current period - use provided dateRange or current class property
			$current_period = ! empty( $dateRange ) ? $dateRange : $this->dateRange;

			// If no date range at all, use default: last 30 days
			if ( empty( $current_period ) ) {
				$end = current_time( 'Y-m-d' );
				$start = date( 'Y-m-d', strtotime( '-30 days' ) );
				$current_period = array( 'start' => $start, 'end' => $end );
			}

			// Calculate previous period of same length
			$previous_dateRange = $this->calculate_previous_period( $current_period );

			// Get previous period country counts (cached separately for performance)
			$previous_counts = $this->get_country_counts_for_period( $previous_dateRange, $selected_status, $types );

			// Calculate growth for each country
			foreach ( $current_counts as $country_code => $data ) {
				$current_total = 0;
				$previous_total = 0;

				// Get current period total (handle both 'total' key and status-based structure)
				if ( isset( $data['total'] ) ) {
					$current_total = (int) $data['total'];
				} else {
					// Sum all status counts if no 'total' key
					$current_total = array_sum( array_map( 'intval', $data ) );
				}

				// Get previous period total
				if ( isset( $previous_counts[$country_code] ) ) {
					if ( isset( $previous_counts[$country_code]['total'] ) ) {
						$previous_total = (int) $previous_counts[$country_code]['total'];
					} else {
						// Sum all status counts if no 'total' key
						$previous_total = array_sum( array_map( 'intval', $previous_counts[$country_code] ) );
					}
				}

				// Calculate growth percentage: ((current - previous) / previous) * 100
				$growth = 0;
				if ( $previous_total > 0 ) {
					$growth = ( ( $current_total - $previous_total ) / $previous_total ) * 100;
				} elseif ( $current_total > 0 && $previous_total == 0 ) {
					// If previous period had no data but current does, it's 100% growth
					$growth = 100;
				}

				// Add growth to the country data
				$current_counts[$country_code]['growth'] = round( $growth, 2 );
			}

			return $current_counts;
		}

		/**
		 * Get country counts for a specific date range (used for previous period calculation)
		 * Optimized with caching to avoid duplicate queries
		 *
		 * @param array $dateRange      Date range array with 'start' and 'end'
		 * @param array $selected_status Selected status filter
		 * @param array $types          Selected content types
		 * @return array                Country counts for the period
		 */
		private function get_country_counts_for_period( $dateRange, $selected_status = [], $types = [] ) {
			// Cache key for previous period
			$cache_key = 'country_counts_prev_' . md5( json_encode( [
				'dateRange' => $dateRange,
				'status'    => $selected_status,
				'types'     => $types,
			] ) );

			$cached = wp_cache_get( $cache_key, WP_ULIKE_PRO_DOMAIN );
			if ( false !== $cached ) {
				return json_decode( $cached, true );
			}

			// Store current date range to restore later
			$original_dateRange = $this->dateRange;

			// Set the previous period date range
			$this->setDateRange( $dateRange );

			// Initialize result array
			$country_counts = [];

			// Loop through each table and fetch country codes
			foreach ( $this->tables as $content_type => $table_name ) {
				// check type filter
				if ( ! empty( $types ) && ! in_array( $content_type, $types ) ) {
					continue;
				}

				$table = "{$this->wpdb->prefix}{$table_name}";
				$date_condition = $this->getMySqlDateRange( $table );

				// Prepare query based on the selected status
				$status_condition = '';
				if ( ! empty( $selected_status ) ) {
					// Map selected statuses to prepared query format
					$selectedStatus = array_map( function( $status ) {
						return $this->wpdb->prepare( '%s', $status );
					}, $selected_status );

					// Add status filter to the query
					$status_condition = "AND `status` IN (" . implode( ',', $selectedStatus ) . ")";
				}

				// Prepare the query
				$query = "
					SELECT country_code, COUNT(DISTINCT user_id) AS count
				";

				// Add status to the query if statuses are provided
				if ( ! empty( $selected_status ) ) {
					$query .= ", `status`";
				}

				$query .= "
					FROM `$table`
					WHERE $date_condition
					AND country_code IS NOT NULL
					AND country_code != ''
					$status_condition
					GROUP BY country_code";

				// Add status to GROUP BY if statuses are provided
				if ( ! empty( $selected_status ) ) {
					$query .= ", `status`";
				}

				// Fetch results
				$results = $this->wpdb->get_results( $query, ARRAY_A );

				// Sum up the counts across all content types
				foreach ( $results as $row ) {
					$country_code = $row['country_code'];
					$count = (int) $row['count'];

					if ( empty( $selected_status ) ) {
						// If no selected status, group by total
						if ( ! isset( $country_counts[$country_code] ) ) {
							$country_counts[$country_code] = [];
						}
						if ( isset( $country_counts[$country_code]['total'] ) ) {
							$country_counts[$country_code]['total'] += $count;
						} else {
							$country_counts[$country_code]['total'] = $count;
						}
					} else {
						// If selected statuses exist, group by both country_code and status
						$status = $row['status'];
						if ( ! isset( $country_counts[$country_code] ) ) {
							$country_counts[$country_code] = [];
						}
						if ( isset( $country_counts[$country_code][$status] ) ) {
							$country_counts[$country_code][$status] += $count;
						} else {
							$country_counts[$country_code][$status] = $count;
						}
					}
				}
			}

			// Restore original date range
			$this->dateRange = $original_dateRange;

			// Cache the result
			wp_cache_set( $cache_key, json_encode( $country_counts ), WP_ULIKE_PRO_DOMAIN, 10 );

			return $country_counts;
		}

		/**
		 * Calculate previous period date range based on current date range
		 * Simple and optimized: calculates previous period of same length
		 *
		 * @param array $current_period Current date range array with 'start' and 'end'
		 * @return array                Previous period date range
		 */
		private function calculate_previous_period( $current_period ) {
			if ( ! isset( $current_period['start'] ) || ! isset( $current_period['end'] ) ) {
				// Default: last 30 days vs previous 30 days
				$end = current_time( 'Y-m-d' );
				$start = date( 'Y-m-d', strtotime( '-30 days' ) );
				$current_period = array( 'start' => $start, 'end' => $end );
			}

			$start = strtotime( $current_period['start'] );
			$end = strtotime( $current_period['end'] );

			// Calculate number of days in current period
			$days_diff = ( $end - $start ) / DAY_IN_SECONDS;

			// Previous period ends 1 day before current period starts
			$prev_end = date( 'Y-m-d', strtotime( $current_period['start'] . ' -1 day' ) );
			// Previous period starts (days_diff) days before prev_end
			$prev_start = date( 'Y-m-d', strtotime( $prev_end . ' -' . $days_diff . ' days' ) );

			return array(
				'start' => $prev_start,
				'end'   => $prev_end
			);
		}


		/**
		 * Calculate engagement rate and views for an item
		 * Smart calculation: Only counts likes/dislikes from the period where views exist
		 * This ensures accurate, logical engagement rates for marketing purposes
		 *
		 * @param int    $item_id      Item ID
		 * @param string $type          Content type (post, comment, activity, topic)
		 * @param int    $like_count    Like count (all-time, will be filtered if needed)
		 * @param int    $dislike_count Dislike count (all-time, will be filtered if needed)
		 * @param mixed  $period        Period setting (array with start/end or string)
		 * @return array                Array with 'total_views' and 'engagement_rate'
		 */
		private function calculate_engagement_rate( $item_id, $type, $like_count, $dislike_count, $period = 'all' ) {
			$total_views = 0;
			$engagement_rate = 0;

			if ( ! $this->button_views || ! $this->button_views->is_tracking_enabled( $type ) ) {
				return array(
					'total_views'     => $total_views,
					'engagement_rate' => $engagement_rate
				);
			}

			// Get views and determine the period where views exist
			$view_period_start = null;
			if ( is_array( $period ) && isset( $period['start'] ) && isset( $period['end'] ) ) {
				// Date range: use the provided range
				$views_data = $this->button_views->get_views_by_date_range( $type, $period['start'], $period['end'], $item_id );
				$total_views = array_sum( $views_data );
				$view_period_start = $period['start'];
			} else {
				// Period string or 'all'
				$period_string = $period === 'all' ? 'all' : current_time( 'Y-m-d' );
				$total_views = $this->button_views->get_total_views( $item_id, $type, $period_string );

				// Smart period matching: For 'all' period, find when view tracking started
				// This ensures we only compare likes and views from the same time period
				if ( $period === 'all' && $total_views > 0 ) {
					$first_view_date = $this->button_views->get_first_view_date( $item_id, $type );
					if ( $first_view_date ) {
						$view_period_start = $first_view_date;
					}
				} elseif ( $period !== 'all' ) {
					// For specific periods, get the period start date
					$view_period_start = $this->get_period_start_date( $period );
				}
			}

			// Calculate engagement rate only if we have views
			if ( $total_views > 0 ) {
				// Smart filtering: Only count likes/dislikes from the period where views exist
				// This prevents inflated percentages from historical likes vs new view tracking
				$filtered_like_count = $like_count;
				$filtered_dislike_count = $dislike_count;

				if ( $view_period_start ) {
					$effective_start_date = $view_period_start;
					
					// Performance optimization: Only apply max_lookback_days limit for 'all' period
					// For user-provided date ranges or period strings, respect the exact dates requested
					if ( $period === 'all' ) {
						// Maximum lookback: 90 days (3 months) - optimal for top items dashboard
						// This provides recent engagement data while maintaining fast query performance
						// 90 days captures meaningful trends without the overhead of year-long queries
						// 
						// Filter to allow customization: 'wp_ulike_pro_engagement_max_lookback_days'
						// Default: 90 days. Recommended range: 30-365 days
						$max_lookback_days = apply_filters( 'wp_ulike_pro_engagement_max_lookback_days', 90, $item_id, $type );
						$max_lookback_days = absint( $max_lookback_days ); // Ensure positive integer
						$max_lookback_days = max( 1, min( 365, $max_lookback_days ) ); // Clamp between 1-365 days
						
						$max_lookback_date = date( 'Y-m-d', strtotime( '-' . $max_lookback_days . ' days' ) );
						
						// Use the later date: either view_period_start or max_lookback_date
						// This ensures we don't query too far back, even if view tracking started years ago
						// Note: For 'all' period, this creates a "recent engagement rate" (all-time views vs recent likes)
						// which is more actionable for marketers than pure all-time engagement
						$effective_start_date = $view_period_start < $max_lookback_date ? $max_lookback_date : $view_period_start;
					}
					// For date ranges and period strings, use the exact dates - no limit applied
					
					// Determine end date: use period end date if provided, otherwise use today
					$effective_end_date = current_time( 'Y-m-d' );
					if ( is_array( $period ) && isset( $period['end'] ) ) {
						// For user-provided date ranges, respect the exact end date
						$effective_end_date = $period['end'];
					}
					
					// Get likes/dislikes count from the effective start date to effective end date
					$is_distinct = wp_ulike_setting_repo::isDistinct( $type );
					$period_array = array( 'start' => $effective_start_date, 'end' => $effective_end_date );

					$filtered_like_count = wp_ulike_get_counter_value( $item_id, $type, 'like', $is_distinct, $period_array );
					$filtered_dislike_count = wp_ulike_get_counter_value( $item_id, $type, 'dislike', $is_distinct, $period_array );
				}

				// Calculate engagement rate: (Likes + Dislikes) / Views * 100
				// Note: Engagement rate is a standard metric and can exceed 100% for viral content
				// where multiple users engage with the same content (e.g., 150% = 1.5 engagements per view)
				$total_engagements = $filtered_like_count + $filtered_dislike_count;
				$engagement_rate = ( $total_engagements / $total_views ) * 100;
			}

			return array(
				'total_views'     => $total_views,
				'engagement_rate' => $engagement_rate
			);
		}

		/**
		 * Calculate engagement growth (period-over-period comparison)
		 * Compares current period engagement rate with previous period
		 *
		 * @param int    $item_id      Item ID
		 * @param string $type          Content type (post, comment, activity, topic)
		 * @param int    $like_count    Like count
		 * @param int    $dislike_count Dislike count
		 * @param mixed  $period        Period setting (array with start/end or string)
		 * @param float  $current_rate  Current period engagement rate
		 * @return float                Growth percentage (positive = growth, negative = decline)
		 */
		private function calculate_engagement_growth( $item_id, $type, $like_count, $dislike_count, $period, $current_rate ) {
			if ( ! $this->button_views || ! $this->button_views->is_tracking_enabled( $type ) ) {
				return 0;
			}

			// For 'all' period, calculate growth based on last 7 days vs previous 7 days
			// This provides meaningful trend analysis for top items while engagement rate shows all-time data
			// 7 days is ideal for showing recent momentum and trending content
			if ( $period === 'all' ) {
				// Calculate current period (last 7 days) engagement rate
				$current_period = array(
					'start' => date( 'Y-m-d', strtotime( '-7 days' ) ),
					'end' => current_time( 'Y-m-d' )
				);
				$current_engagement = $this->calculate_engagement_rate( $item_id, $type, $like_count, $dislike_count, $current_period );
				$current_rate_7days = $current_engagement['engagement_rate'];

				// Calculate previous period (8-14 days ago) engagement rate
				$previous_period = array(
					'start' => date( 'Y-m-d', strtotime( '-14 days' ) ),
					'end' => date( 'Y-m-d', strtotime( '-8 days' ) )
				);
				$previous_engagement = $this->calculate_engagement_rate( $item_id, $type, $like_count, $dislike_count, $previous_period );
				$previous_rate = $previous_engagement['engagement_rate'];

				// Calculate growth percentage: ((current - previous) / previous) * 100
				if ( $previous_rate > 0 ) {
					$growth = ( ( $current_rate_7days - $previous_rate ) / $previous_rate ) * 100;
					return round( $growth, 2 );
				}

				// If previous period had no engagement, but current does, it's 100% growth
				if ( $current_rate_7days > 0 && $previous_rate == 0 ) {
					return 100;
				}

				return 0;
			}

			// For non-'all' periods, use the passed $current_rate parameter
			if ( $current_rate <= 0 ) {
				return 0;
			}

			// Determine previous period based on current period
			$previous_period = null;

			if ( is_array( $period ) && isset( $period['start'] ) && isset( $period['end'] ) ) {
				// For date ranges, calculate previous period of same length
				$start = strtotime( $period['start'] );
				$end = strtotime( $period['end'] );
				$days_diff = ( $end - $start ) / DAY_IN_SECONDS;

				$prev_end = date( 'Y-m-d', strtotime( $period['start'] . ' -1 day' ) );
				$prev_start = date( 'Y-m-d', strtotime( $prev_end . ' -' . $days_diff . ' days' ) );

				$previous_period = array( 'start' => $prev_start, 'end' => $prev_end );
			} else {
				// For period strings, get previous equivalent period
				switch ( $period ) {
					case 'today':
						$previous_period = date( 'Y-m-d', strtotime( '-1 day' ) );
						break;
					case 'week':
						$previous_period = array(
							'start' => date( 'Y-m-d', strtotime( '-14 days' ) ),
							'end' => date( 'Y-m-d', strtotime( '-8 days' ) )
						);
						break;
					case 'month':
						$previous_period = array(
							'start' => date( 'Y-m-d', strtotime( '-60 days' ) ),
							'end' => date( 'Y-m-d', strtotime( '-31 days' ) )
						);
						break;
					case 'year':
						$previous_period = array(
							'start' => date( 'Y-m-d', strtotime( '-730 days' ) ),
							'end' => date( 'Y-m-d', strtotime( '-366 days' ) )
						);
						break;
					default:
						return 0;
				}
			}

			// Calculate previous period engagement rate
			$previous_engagement = $this->calculate_engagement_rate( $item_id, $type, $like_count, $dislike_count, $previous_period );
			$previous_rate = $previous_engagement['engagement_rate'];

			// Calculate growth percentage: ((current - previous) / previous) * 100
			if ( $previous_rate > 0 ) {
				$growth = ( ( $current_rate - $previous_rate ) / $previous_rate ) * 100;
				return round( $growth, 2 );
			}

			// If previous period had no engagement, but current does, it's 100% growth
			if ( $current_rate > 0 && $previous_rate == 0 ) {
				return 100;
			}

			return 0;
		}

		/**
		 * Get period start date for period strings
		 *
		 * @param string $period Period string (today, week, month, year)
		 * @return string|null Start date (Y-m-d format) or null
		 */
		private function get_period_start_date( $period ) {
			switch ( $period ) {
				case 'today':
					return current_time( 'Y-m-d' );
				case 'week':
					return date( 'Y-m-d', strtotime( '-7 days' ) );
				case 'month':
					return date( 'Y-m-d', strtotime( '-30 days' ) );
				case 'year':
					return date( 'Y-m-d', strtotime( '-365 days' ) );
				default:
					return null;
			}
		}

		/**
		 * Get translated role name
		 *
		 * @param string $role
		 * @return string
		 */
		public function get_i18n_role_name( $role ){
			$editable_roles = wp_roles()->roles;

			if( isset( $editable_roles[$role] ) ){
				return translate_user_role( $editable_roles[$role]['name'] );
			}

			return $role;
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