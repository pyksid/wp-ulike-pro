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
			$table  = $this->wpdb->prefix . $table;

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
				// Prepare each status safely.
				$selectedStatus = array();
				foreach ( $this->selectedStatus as $status ) {
					$selectedStatus[] = $this->wpdb->prepare( '%s', $status );
				}

				$dataInfo = $this->wpdb->get_results( "
					SELECT DATE(`date_time`) AS labels,
						status,
						COUNT(`date_time`) AS counts
					FROM `$table`
					WHERE $range
					AND `status` IN (" . implode( ',', $selectedStatus ) . ")
					GROUP BY labels, status
					ORDER BY labels, status ASC;
				" );

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
				$start = $this->dateRange['start'];
				$end = $this->dateRange['end'];

				return $start === $end
					? sprintf( "DATE(`date_time`) = '%s'", $start )
					: sprintf( "DATE(`date_time`) BETWEEN '%s' AND '%s'", $start, $end );
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
			$date_30_days_before   = date( 'Y-m-d', $latest_date_timestamp - DAY_IN_SECONDS * 30 );
			$latest_date           = date( 'Y-m-d', $latest_date_timestamp );

			return sprintf( "DATE(`date_time`) BETWEEN '%s' AND '%s'", $date_30_days_before, $latest_date );
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

			$this->dateRange['start'] = date( "Y-m-d", strtotime( $rawDate['start'] ) );
			$this->dateRange['end'] = !empty( $rawDate['end'] )
				? date( "Y-m-d", strtotime( $rawDate['end'] ) )
				: $this->dateRange['start'];
		}


		/**
		 * Get The Logs Data From Tables
		 *
		 * @return Object
		 */
		public function select_data( $table ){

			$table_name = "{$this->wpdb->prefix}{$table}";
			$data_limit = apply_filters( 'wp_ulike_stats_data_limit', 30 );
			$date_range = $this->getMySqlDateRange( $table_name );

			// Prepare the main query with the fetched latest date
			$query  = $this->wpdb->prepare( "
				SELECT DATE(date_time) AS labels,
				count(date_time) AS counts
				FROM `$table_name`
				WHERE $date_range
				GROUP BY labels
				ORDER BY labels ASC
				LIMIT %d",
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

			// Extract variables
			extract( $parsed_args );

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

			// Extract variables
			extract( $parsed_args );

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
				$total_interactions = $this->count_total_interactions(array("table" => 'ulike', "date" => $settings['period'] ?? 'all'));

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

					$comment_number  = get_comments_number($post_id);
					$engagement_rate = ( ( $like_count + $dislike_count + $comment_number ) / $total_interactions ) * 100;

					$result[] = [
						'id'             => $post_id,
						'title'          => get_the_title(),
						'image'          => $thumbnail,
						'permalink'      => get_permalink(),
						'likes_count'    => $like_count,
						'dislikes_count' => $dislike_count,
						'engaged_users'  => $engaged_users_info,
						'meta_data'      => [
							'Published'  => get_the_date( '', $post_id ),
							'Comments'   => get_comments_number($post_id),
							'Engagement' => number_format($engagement_rate, 2). "%",
						],
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
				$total_interactions = $this->count_total_interactions(array("table" => 'ulike_comments', "date" => $settings['period'] ?? 'all'));

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

					$comment_number  = get_comments_number( $comment->comment_post_ID );
					$engagement_rate = ( ( $like_count + $dislike_count + $comment_number ) / $total_interactions ) * 100;

					$result[] = [
						'id'             => $comment->comment_ID,
						'title'          => get_the_title($comment->comment_post_ID),
						'image'          => get_avatar_url( $comment->comment_author_email, [ 'size' => 100 ] ),
						'permalink'      => get_comment_link($comment->comment_ID),
						'likes_count'    => $like_count,
						'dislikes_count' => $dislike_count,
						'engaged_users'  => $engaged_users_info,
						'meta_data'      => [
							'Published'  => get_comment_date( '', $comment->comment_ID ),
							'By'         => esc_attr( $comment->comment_author ),
							'Engagement' => number_format($engagement_rate, 2). "%",
						],
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
				$total_interactions = $this->count_total_interactions(array("table" => 'ulike_forums', "date" => $settings['period'] ?? 'all'));

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

					$engagement_rate = ( ( $like_count + $dislike_count ) / $total_interactions ) * 100;

					$author_avatar = NULL;
					if ( ! bbp_is_topic_anonymous( $topic_id ) ) {
						$author_avatar = get_avatar_url( bbp_get_topic_author_id( $topic_id ), 100 );
					} else {
						$author_avatar = get_avatar_url( get_post_meta( $topic_id, '_bbp_anonymous_email', true ), 100 );
					}

					$result[] = [
						'id'             => $topic_id,
						'title'          => bbp_get_forum_title( $topic_id ),
						'image'          => $author_avatar,
						'permalink'      => 'topic' === get_post_type( $topic_id ) ? bbp_get_topic_permalink( $topic_id ) : bbp_get_reply_url( $topic_id ),
						'likes_count'    => $like_count,
						'dislikes_count' => $dislike_count,
						'engaged_users'  => $engaged_users_info,
						'meta_data'      => [
							'Published'  => bbp_get_topic_post_date( $topic_id ),
							'By'         => bbp_get_topic_author_display_name( $topic_id ),
							'Engagement' => number_format($engagement_rate, 2). "%",
						],
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
				$total_interactions = $this->count_total_interactions(array("table" => 'ulike_activities', "date" => $settings['period'] ?? 'all'));

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

					$author          = get_user_by( 'id', $activity->user_id );
					$engagement_rate = ( ( $like_count + $dislike_count ) / $total_interactions ) * 100;

					$result[] = [
						'id'             => $activity->id,
						'title'          => ! empty( $activity->content ) ? wp_strip_all_tags( $activity->content ) : wp_strip_all_tags( $activity->action ),
						'image'          => get_avatar_url( $author->user_email, [ 'size' => 100 ] ),
						'permalink'      => function_exists('bp_activity_get_permalink') ? bp_activity_get_permalink( $activity->id ) : '',
						'likes_count'    => $like_count,
						'dislikes_count' => $dislike_count,
						'engaged_users'  => $engaged_users_info,
						'meta_data'      => [
							'Published'  => wp_ulike_date_i18n( $activity->date_recorded ),
							'By'         => esc_attr( $author->display_name ),
							'Engagement' => number_format($engagement_rate, 2). "%",
						],
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

		// Method to count device types across all relevant tables in the last 6 months
		public function count_device_types() {
			// Check cache first
			$device_counts = wp_cache_get('device_types', WP_ULIKE_PRO_DOMAIN);
			if ($device_counts !== false) {
				return $device_counts;
			}

			// Get the date 6 months ago
			$six_months_ago = date('Y-m-d H:i:s', strtotime('-6 months'));

			// Tables to query
			$tables = [
				"{$this->wpdb->prefix}ulike",
				"{$this->wpdb->prefix}ulike_comments",
				"{$this->wpdb->prefix}ulike_activities",
				"{$this->wpdb->prefix}ulike_forums"
			];

			// Initialize result array
			$device_counts = [];

			// Query each table
			foreach ($tables as $table) {
				$query = $this->wpdb->prepare(
					"SELECT device, COUNT(DISTINCT user_id) AS count FROM $table WHERE date_time > %s AND device IS NOT NULL GROUP BY device",
					$six_months_ago
				);

				$results = $this->wpdb->get_results($query, ARRAY_A);

				foreach ($results as $row) {
					$device = $row['device'];
					$count = (int) $row['count'];

					if (isset($device_counts[$device])) {
						$device_counts[$device] += $count;
					} else {
						$device_counts[$device] = $count;
					}
				}
			}

			wp_cache_set('device_types', $device_counts, WP_ULIKE_PRO_DOMAIN, 10);

			return $device_counts;
		}

		public function count_country_codes( $dateRange = [], $selected_status = [] ) {
			// Set the date range if provided
			if (!empty($dateRange)) {
				$this->setDateRange($dateRange);
			}

			// Generate a unique cache key based on the date range
			$cache_key = 'country_counts_' . md5(json_encode($this->dateRange));
			$country_counts = wp_cache_get($cache_key, WP_ULIKE_PRO_DOMAIN);
			if (false !== $country_counts) {
				return json_decode($country_counts, true);
			}

			// Initialize result array
			$country_counts = [];

			// Loop through each table and fetch country codes
			foreach ($this->tables as $content_type => $table_name) {
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

			// Cache the result for 12 hours
			wp_cache_set($cache_key, json_encode($country_counts), WP_ULIKE_PRO_DOMAIN, 10);

			return $country_counts;
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