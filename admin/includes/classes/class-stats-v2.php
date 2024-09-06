<?php
/**
 * Class for statistics v2 process
 * 
 * @package    wp-ulike-pro
 * @author     TechnoWich 2024
 * @link       https://wpulike.com
 */

// no direct access allowed
if ( ! defined('ABSPATH') ) {
    die();
}

if ( ! class_exists( 'WP_Ulike_Pro_Stats_V2' ) ) {

	class WP_Ulike_Pro_Stats_V2{

		// Private variables
		private $wpdb, $tables, $dateRange, $selectedStatus;

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
				'monthly_data'    => $this->get_aggregated_data_by_month(),
				'daily_data'      => $this->get_aggregated_data_by_date(),
				'user_statistics' => $this->get_voting_statistics_by_user_type(),
			);
		}

		private function get_all_datasets() {
			$tables = $this->get_tables();
			$datasets = array();

			foreach ($tables as $type => $table) {
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
		 * Select charts data
		 *
		 * @param string $table
		 * @return void
		 */
		public function select_charts_data( $table ){

			$output = array();
			$table  = $this->wpdb->prefix . $table;
			$range  = $this->getMySqlDateRange();

			if( empty( $this->selectedStatus ) ){
				$dataInfo = $this->wpdb->get_results( "
					SELECT DATE(`date_time`) AS labels,
					COUNT(`date_time`) AS counts
					FROM `$table`
					WHERE $range
					GROUP BY labels ORDER BY labels ASC",
				);

				if( $dataInfo ){
					foreach( $dataInfo as $result ){
						if( isset( $result->labels ) & isset( $result->counts ) ){
							$output[]= [
								'date'  => wp_date( "Y-m-d", strtotime( $result->labels ) ),
								'total' => (int) $result->counts
							];
						}
					}
				}

			} else {

				$db = $this->wpdb;
				$selectedStatus = array_map(function($status) use ($db) {
					return $db->prepare('%s', $status);
				}, $this->selectedStatus);

				$dataInfo = $this->wpdb->get_results( "
					SELECT DATE(`date_time`) AS labels,
					status,
					COUNT(`date_time`) AS counts
					FROM `$table`
					WHERE $range
					AND `status` IN (" . implode(',', $selectedStatus) . ")
					GROUP BY
						labels,
						status
					ORDER BY
						labels, status ASC;"
				);

				foreach( $dataInfo as $row ){
					$date   = $row->labels;
					$status = $row->status;
					$count  = $row->counts;

					if (!isset($output[$date])) {
						$output[$date] = ['date' => $date];
					}

					$output[$date][$status] = (int) $count;
				}

				if( ! empty( $output ) ){
					$output = array_values($output);

					if( ! empty( $output ) ){
						foreach ($output as $key => $args) {
							foreach ($this->selectedStatus as $sv) {
								if( ! isset( $args[$sv] ) ){
									$output[$key][$sv] = 0;
								}
							}
						}
					}
				}

			}

			return $output;
		}


		/**
		 * Get mysql date range format
		 *
		 * @return string
		 */
		private function getMySqlDateRange(){
			if( empty( $this->dateRange ) ){
				return '`date_time` >= NOW() - INTERVAL 30 DAY';
			}

			if( $this->dateRange['start'] === $this->dateRange['end'] ){
				return sprintf( 'DATE(`date_time`) = \'%s\'', $this->dateRange['start'] );
			}

			return sprintf( 'DATE(`date_time`) >= \'%s\' AND DATE(`date_time`) <= \'%s\'', $this->dateRange['start'], $this->dateRange['end'] );
		}

		/**
		 * Set date range with our format
		 *
		 * @param array $rawDate
		 * @return void
		 */
		private function setDateRange( $rawDate ){
			if( empty( $rawDate ) || empty( $rawDate['start'] ) ){
				$this->dateRange = array();
				return;
			}
			$this->dateRange['start'] = date( "Y-m-d", strtotime( $rawDate['start'] ) );
			$this->dateRange['end']   = isset( $rawDate['end'] ) ? date( "Y-m-d", strtotime( $rawDate['end'] ) ) : $this->dateRange['start'];
		}


		/**
		 * Get The Logs Data From Tables
		 *
		 * @return Object
		 */
		public function select_data( $table ){

			$data_limit = apply_filters( 'wp_ulike_stats_data_limit', 30 );

			// Fetch the most recent date_time from the table
			$latest_date = $this->wpdb->get_var( "
				SELECT MAX(date_time) FROM `{$this->wpdb->prefix}{$table}`
			");

			// Prepare the main query with the fetched latest date
			$query  = $this->wpdb->prepare( "
				SELECT DATE(date_time) AS labels,
				count(date_time) AS counts
				FROM `{$this->wpdb->prefix}{$table}`
				WHERE DATEDIFF(%s, date_time) <= 30
				GROUP BY labels
				ORDER BY labels ASC
				LIMIT %d",
				$latest_date,
				$data_limit
			);

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

			// Extract variables
			extract( $parsed_args );

			$cache_key = sanitize_key( sprintf( 'count_logs_for_%s_table_in_%s_daterange', $table, $date ) );

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
				wp_cache_set( $cache_key, $counter_value, WP_ULIKE_PRO_DOMAIN );
			}

			if( $date === 'all' ){
				wp_ulike_update_meta_data( 1, 'statistics', $cache_key, $counter_value );
			}

	        return  empty( $counter_value ) ? 0 : absint( $counter_value );
		}

		/**
		 * Get top items list
		 *
		 * @return void
		 */
		private function get_top_items() {
			$tables = $this->get_tables();
			$top_items = array();

			$top_items['post']      = $this->get_top( [
				"type" => 'post', "rel_type" => '', "status" => ['like','dislike'], 'period' => NULL
			], NULL );

			$top_items['comment']   = $this->get_top( [
				"type" => 'comment', "rel_type" => '', "status" => ['like','dislike'], 'period' => NULL
			], NULL );

			$top_items['activity'] = $this->get_top( [
				"type" => 'activity', "rel_type" => '', "status" => ['like','dislike'], 'period' => NULL
			], NULL );

			$top_items['topic']     = $this->get_top( [
				"type" => 'topic', "rel_type" => '', "status" => ['like','dislike'], 'period' => NULL
			], NULL );

			$top_items['engagers']   = $this->get_top( [
				"type" => 'engagers', "rel_type" => '', "status" => ['like','dislike'], 'period' => NULL
			], NULL );

			return $top_items;
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
				$is_distinct = wp_ulike_setting_repo::isDistinct('post');

				while($posts->have_posts()) {
					$posts->the_post();

					$like_count    = wp_ulike_get_counter_value( wp_ulike_get_the_id( get_the_ID() ), 'post', 'like', $is_distinct, $settings['period'] ?? NULL );
					$dislike_count = wp_ulike_get_counter_value( wp_ulike_get_the_id( get_the_ID() ), 'post', 'dislike', $is_distinct, $settings['period'] ?? NULL );

					$result[] = [
						'title'          => get_the_title(),
						'permalink'      => get_permalink(),
						'likes_count'    => $like_count,
						'dislikes_count' => $dislike_count
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
				$is_distinct = wp_ulike_setting_repo::isDistinct('comment');

				foreach ( $comments as $comment ) {

					$like_count    = wp_ulike_get_counter_value( $comment->comment_ID, 'comment', 'like', $is_distinct, $settings['period'] ?? NULL );
					$dislike_count = wp_ulike_get_counter_value( $comment->comment_ID, 'comment', 'dislike', $is_distinct, $settings['period'] ?? NULL );

					$result[] = [
						'author'         => $comment->comment_author,
						'title'          => get_the_title($comment->comment_post_ID),
						'permalink'      => get_comment_link($comment->comment_ID),
						'likes_count'    => $like_count,
						'dislikes_count' => $dislike_count
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

					$like_count    = wp_ulike_get_counter_value( get_the_ID(), 'topic', 'like', $is_distinct, $settings['period'] ?? NULL );
					$dislike_count = wp_ulike_get_counter_value( get_the_ID(), 'topic', 'dislike', $is_distinct, $settings['period'] ?? NULL );

					$result[] = [
						'title'          => function_exists('bbp_get_forum_title') ? bbp_get_forum_title( get_the_ID() ) : get_the_title(),
						'permalink'      => 'topic' === get_post_type( get_the_ID() ) ? bbp_get_topic_permalink( get_the_ID() ) : bbp_get_reply_url( get_the_ID() ),
						'likes_count'    => $like_count,
						'dislikes_count' => $dislike_count
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

					$result[] = [
						'title'          => ! empty( $activity->content ) ? wp_strip_all_tags( $activity->content ) : wp_strip_all_tags( $activity->action ),
						'permalink'      => function_exists('bp_activity_get_permalink') ? bp_activity_get_permalink( $activity->id ) : '',
						'likes_count'    => $like_count,
						'dislikes_count' => $dislike_count
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
					$user_ID  = stripslashes( $user->user_id );
					$userdata = get_userdata( $user_ID );
					$username = empty( $userdata ) ? esc_html__('Guest User', WP_ULIKE_PRO_DOMAIN) : esc_attr( $userdata->display_name );

					$result[] = [
						'permalink'        => get_edit_profile_url( $user_ID ),
						'title'            => $username,
						'likes_count'      => absint( $user->likeCount ?? 0 ),
						'dislikes_count'   => absint( $user->dislikeCount ?? 0 ),
						'unlikes_count'    => absint( $user->unlikeCount ?? 0 ),
						'undislikes_count' => absint( $user->undislikeCount ?? 0 )
					];
				}
			}

			return $result;
		}

		/**
		 * Get aggregated data by past months
		 *
		 * @param string $interval
		 * @return array
		 */
		public function get_aggregated_data_by_month($interval = '5 MONTH') {
			$query = "SELECT
				DATE_FORMAT(date_time, '%Y-%m') AS month,
				COUNT(*) AS total_count
			FROM (
				SELECT DATE(date_time) AS date_time FROM {$this->wpdb->prefix}{$this->tables['posts']} WHERE date_time >= NOW() - INTERVAL {$interval}
				UNION ALL
				SELECT DATE(date_time) AS date_time FROM {$this->wpdb->prefix}{$this->tables['activities']} WHERE date_time >= NOW() - INTERVAL {$interval}
				UNION ALL
				SELECT DATE(date_time) AS date_time FROM {$this->wpdb->prefix}{$this->tables['comments']} WHERE date_time >= NOW() - INTERVAL {$interval}
				UNION ALL
				SELECT DATE(date_time) AS date_time FROM {$this->wpdb->prefix}{$this->tables['topics']} WHERE date_time >= NOW() - INTERVAL {$interval}
			) AS combined
			GROUP BY month
			ORDER BY month ASC;";

			$results = $this->wpdb->get_results($query);

			$month_counts = array();

			foreach ($results as $result) {
				if( empty( $result->month ) ){
					continue;
				}

				$date = DateTime::createFromFormat('Y-m', $result->month);
				$month_counts[] = [
					'total_count' => absint($result->total_count),
					'month_name'  => wp_date( "F Y", $date->getTimestamp() )
				];
			}

			return $month_counts;
		}

		/**
		 * Get aggregated data by past days
		 *
		 * @param string $interval
		 * @return array
		 */
		public function get_aggregated_data_by_date($interval = '6 DAY') {
			$query = "SELECT
				DATE(date_time) AS vote_date,
				COUNT(*) AS total_count
			FROM (
				SELECT DATE(date_time) AS date_time FROM {$this->wpdb->prefix}{$this->tables['posts']} WHERE date_time >= NOW() - INTERVAL {$interval}
				UNION ALL
				SELECT DATE(date_time) AS date_time FROM {$this->wpdb->prefix}{$this->tables['activities']} WHERE date_time >= NOW() - INTERVAL {$interval}
				UNION ALL
				SELECT DATE(date_time) AS date_time FROM {$this->wpdb->prefix}{$this->tables['comments']} WHERE date_time >= NOW() - INTERVAL {$interval}
				UNION ALL
				SELECT DATE(date_time) AS date_time FROM {$this->wpdb->prefix}{$this->tables['topics']} WHERE date_time >= NOW() - INTERVAL {$interval}
			) AS combined
			GROUP BY vote_date
			ORDER BY vote_date ASC;";

			// Execute the query and get results
			$results = $this->wpdb->get_results($query);

			$date_counts = array();

			foreach ($results as $result) {
				$date_counts[] = [
					'total_count' => absint($result->total_count),
					'vote_date' => wp_date( "Y-m-d", strtotime( $result->vote_date ) )
				];
			}

			return $date_counts;
		}

		/**
		 * Get User Role Distribution in Engagements
		 *
		 * @param string $interval
		 * @return array
		 */
		public function get_voting_statistics_by_user_type($interval = '5 MONTH') {

			// Step 1: Query to get user IDs and serialized role data
			$query = "
				SELECT
					u.ID AS user_id,
					roles.meta_value AS role_data
				FROM (
					SELECT user_id FROM {$this->wpdb->prefix}{$this->tables['posts']} WHERE date_time >= NOW() - INTERVAL {$interval}
					UNION ALL
					SELECT user_id FROM {$this->wpdb->prefix}{$this->tables['activities']} WHERE date_time >= NOW() - INTERVAL {$interval}
					UNION ALL
					SELECT user_id FROM {$this->wpdb->prefix}{$this->tables['comments']} WHERE date_time >= NOW() - INTERVAL {$interval}
					UNION ALL
					SELECT user_id FROM {$this->wpdb->prefix}{$this->tables['topics']} WHERE date_time >= NOW() - INTERVAL {$interval}
				) AS combined
				LEFT JOIN {$this->wpdb->users} u ON combined.user_id = u.ID
				LEFT JOIN {$this->wpdb->usermeta} roles ON u.ID = roles.user_id AND roles.meta_key = '{$this->wpdb->prefix}capabilities'
				GROUP BY u.ID;
			";

			// Execute the query and get results
			$results = $this->wpdb->get_results($query);

			// Process the results to count users by their main role
			$role_counts = array();

			$editable_roles = wp_roles()->roles;

			$guest = esc_html__('Guest User', WP_ULIKE_PRO_DOMAIN);

			foreach ($results as $result) {
				$roles = maybe_unserialize($result->role_data);

				if (is_array($roles)) {
					// Get the main role (first role found)
					$main_role = key($roles);

					if( isset( $editable_roles[$main_role] ) ){
						$main_role = translate_user_role( $editable_roles[$main_role]['name'] );
					}

					// Count the role occurrences
					if (!isset($role_counts[$main_role])) {
						$role_counts[$main_role] = 0;
					}
					$role_counts[$main_role]++;
				} else {
					// Handle users without roles
					if (!isset($role_counts[$guest])) {
						$role_counts[$guest] = 0;
					}
					$role_counts[$guest]++;
				}
			}

			// Convert the counts array to a more user-friendly format if needed
			$formatted_results = array();
			foreach ($role_counts as $role => $count) {
				$formatted_results[] = (object) array('user_role' => translate_user_role( $role ), 'unique_user_count' => $count);
			}

			return $formatted_results;
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