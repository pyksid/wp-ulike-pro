<?php
/**
 * Class for statistics process
 * 
 * @package    wp-ulike-pro
 * @author     TechnoWich 2024
 * @link       https://wpulike.com
 */

// no direct access allowed
if ( ! defined('ABSPATH') ) {
    die();
}

if ( ! class_exists( 'WP_Ulike_Pro_Stats' ) ) {

	class WP_Ulike_Pro_Stats extends wp_ulike_widget{

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
		 * @author       	Alimir
		 * @since           2.0
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

		/**
		 * Set all data info for ajax requests
		 *
		 * @author       	Alimir
		 * @since           3.5.1
		 * @return			Array
		 */
		public function get_all_data(){

			$tables = $this->get_tables();

			$output = array(
				'count_all_logs_all'       => $this->count_all_logs('all'),
				'count_all_logs_today'     => $this->count_all_logs('today'),
				'count_all_logs_yesterday' => $this->count_all_logs('yesterday'),
				'get_top_likers'           => $this->get_top( 'likers', false ),
			);

			foreach ( $tables as $type => $table ) {
				$output[ 'dataset_' . $table ]              = $this->get_dataset( $table, false );
				$output[ 'get_top_' . $type ]               = $this->get_top( $type, false );
				$output[ 'count_logs_' . $table . '_week' ] = $this->count_logs( array( "table" => $table, "date" => 'week' ) );
				$output[ 'count_logs_' . $table . '_month'] = $this->count_logs( array( "table" => $table, "date" => 'month' ) );
				$output[ 'count_logs_' . $table . '_year' ] = $this->count_logs( array( "table" => $table, "date" => 'year' ) );
				$output[ 'count_logs_' . $table . '_all' ]  = $this->count_logs( array( "table" => $table, "date" => 'all' ) );
			}

			return $output;
		}


		/**
		 * Set data info for ajax requests
		 *
		 * @author       	Alimir
		 * @since           3.5.1
		 * @return			Array
		 */
		public function get_data( $date_range = array(), $selected_status = array(), $dataset = '', $filter = '', $is_api = false ){
			$output = array();
			$this->setDateRange( $date_range );
			$this->selectedStatus = $selected_status;

			$has_daterange = empty( $this->dateRange ) ? false : true;

			if ( strpos( $dataset, 'dataset_' ) !== false ) {
				$table = str_replace( 'dataset_', '', $dataset );
				if( ! $has_daterange && empty( $this->selectedStatus ) ){
					$this->flush_transient( 'wp_ulike_pro_daterange_of_' . $table );
				}

				return $this->get_dataset( $table, $has_daterange, $is_api );
			} elseif( strpos( $dataset, 'get_top_' ) !== false ){
				$type = str_replace( 'get_top_', '', $dataset );
				if( ! $has_daterange && empty( $this->selectedStatus ) ){
					$this->flush_transient( 'wp_ulike_pro_daterange_of_top_' . $type );
				}

				return $this->get_top( $type, $has_daterange, $filter );
			} elseif( strpos( $dataset, 'count_logs_' ) !== false ){

				$pattern = '/count_logs_(\w+)_(week|month|year|all)/';

				if (preg_match($pattern, $dataset, $matches)) {
					$table_name = $matches[1];
					$date_period = $matches[2];

					return $this->count_logs( array( "table" => $matches[1], "date" => $date_period ) );
				}

				return '';
			} elseif( strpos( $dataset, 'count_all_' ) !== false ){
				switch ($dataset) {
					case 'count_all_logs_all':
						return $this->count_all_logs('all');
						break;

					case 'count_all_logs_today':
						return $this->count_all_logs('today');
						break;

					case 'count_all_logs_yesterday':
						return $this->count_all_logs('yesterday');
						break;

					default:
						return 0;
						break;
				}
			}
		}

		/**
		 * Get custom dataset
		 *
		 * @since 2.0
		 * @param string $table
		 * @return void
		 */
		public function get_dataset( $table, $has_daterange, $is_api = false ){
			$output  = array();

			if( ! $has_daterange ){
				$lastDateRange =  wp_ulike_get_transient( 'wp_ulike_pro_daterange_of_' . $table );
				$firstDateTime = $this->get_first_log_datetime( $table );
				$this->setDateRange( $lastDateRange ? $lastDateRange : array(
					'start' => empty( $firstDateTime ) ? date( "Y-m-d H:i:s",strtotime("-1 month") ) : $firstDateTime,
					'end'   => date( 'Y-m-d H:i:s' )
				)  );
			} else {
				wp_ulike_set_transient( 'wp_ulike_pro_daterange_of_' . $table, $this->dateRange , 1 * YEAR_IN_SECONDS );
			}

			$output['label'] = $this->getAllDateRanges();

			// Get data
			$results = $this->select_linear_data( $table );

			if( $is_api ){
				foreach( $results as $id => $info ){
					foreach ( $output['label'] as $counter => $date ) {
						$output['data'][] = isset( $info['rawInfo'][$date] ) ? $info['rawInfo'][$date] : 0;
					}
				}
			} else {
				foreach( $results as $id => $info ){
					foreach ( $output['label'] as $counter => $date ) {
						$info['datasets']['data'][] = isset( $info['rawInfo'][$date] ) ? $info['rawInfo'][$date] : 0;
					}
					$output['datasets'][] = $info['datasets'];
				}

				$output['options'] = array(
					'title' => array (
						'display' => true,
						'text' => sprintf( '%s %s %s %s', esc_html__( "Growth ratings chart from", WP_ULIKE_PRO_DOMAIN ), $this->dateRange['start'], esc_html__( "To", WP_ULIKE_PRO_DOMAIN ), $this->dateRange['end'] )
					)
				);
			}


			return $output;
		}

		/**
		 * Get all dates between start/end dates
		 *
		 * @return array
		 */
		private function getAllDateRanges(){
			$start = new DateTime( $this->dateRange['start'] );
			$end   = new DateTime( $this->dateRange['end']  );
			$end   = $end->modify( '+1 day' );

			$period = new DatePeriod(
				$start,
				new DateInterval('P1D'),
				$end
			);

			$output = array();
			foreach ($period as $key => $value) {
				$output[] = $value->format("Y-m-d");
			}

			return $output;
		}

		/**
		 * Get list of all status types in table info
		 *
		 * @param string $table
		 * @return array
		 */
		public function get_table_status_list( $table ){
			// $selected_table = $this->wpdb->prefix . $table;
			// $status_list    = $this->wpdb->get_results( "SELECT DISTINCT `status` FROM `$selected_table`" );
			// $final_list     = array();

			// foreach ( $status_list as $key => $value ) {
			// 	$final_list[$value->status] = $value->status;
			 // }

			return array(
				'like'      => esc_html__( 'Like (Up Vote)', WP_ULIKE_PRO_DOMAIN ),
				'unlike'    => esc_html__( 'Un-Like (Removed Up Vote)', WP_ULIKE_PRO_DOMAIN ),
				'dislike'   => esc_html__( 'Dislike (Down Vote)', WP_ULIKE_PRO_DOMAIN ),
				'undislike' => esc_html__( 'Un-Dislike (Removed Down Vote)', WP_ULIKE_PRO_DOMAIN )
			);
		}

		/**
		 * Get first logged datetime from specific table
		 *
		 * @param string $table
		 * @return string|null
		 */
		public function get_first_log_datetime( $table ){
			$table  = $this->wpdb->prefix . $table;
			return $this->wpdb->get_var( "
				SELECT DATE(`date_time`)
				FROM `$table`
				LIMIT 1"
			);
		}

		/**
		 * Get The Linear Logs Data From Tables
		 *
		 * @author Alimir
		 * @param string $table
		 * @since 2.0
		 * @return String
		 */
		public function select_linear_data( $table ){

			$result = array();
			$table  = $this->wpdb->prefix . $table;
			$range  = $this->getMySqlDateRange('date_time');

			if( empty( $this->selectedStatus ) ){
				$dataInfo = $this->wpdb->get_results( "
					SELECT DATE(`date_time`) AS labels,
					COUNT(`date_time`) AS counts
					FROM `$table`
					WHERE $range
					GROUP BY labels ORDER BY labels ASC",
					ARRAY_A
				);
				$result['all']['datasets'] = $this->charts_options( 'all' );
				if( ! empty( $dataInfo ) ) {
					$result['all']['rawInfo'] = $this->mergeTableInfo( $dataInfo );
				} else {
					$result['all']['rawInfo'] = array();
				}
			} else {
				foreach ( $this->selectedStatus as $key => $status ) {
					$dataInfo = $this->wpdb->get_results( $this->wpdb->prepare( "
							SELECT DATE(`date_time`) AS labels,
							COUNT(`date_time`) AS counts
							FROM `$table`
							WHERE $range
							AND `status` LIKE %s
							GROUP BY labels ORDER BY labels ASC",
							$status
						),
						ARRAY_A
					);
					$result[$status]['datasets'] = $this->charts_options($status);
					if( ! empty( $dataInfo ) ) {
						$result[$status]['rawInfo']  = $this->mergeTableInfo( $dataInfo );
					} else {
						$result[$status]['rawInfo'] = array();
					}
				}
			}

			return $result;
		}

		private function mergeTableInfo( $data ){
			$output = array();

			foreach ( $data as $key => $value ) {
				$output[ $value['labels'] ] = $value[ 'counts' ];
			}
			krsort( $output );

			return $output;
		}

		/**
		 * Get mysql date range format
		 *
		 * @param string $dateTimeTable
		 * @return string
		 */
		private function getMySqlDateRange( $dateTimeTable ){
			if( empty( $this->dateRange ) ){
				return 1;
			}

			if( $this->dateRange['start'] === $this->dateRange['end'] ){
				return sprintf( 'DATE(`%s`) = \'%s\'', $dateTimeTable, $this->dateRange['start'] );
			}

			return sprintf( 'DATE(`%1$s`) >= \'%2$s\' AND DATE(`%1$s`) <= \'%3$s\'', $dateTimeTable, $this->dateRange['start'], $this->dateRange['end'] );
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
		 * Get posts dataset
		 *
		 * @since 2.0
		 * @param string $table
		 * @return void
		 */
		public function dataset( $table ){
			$output  = array();
			// Get data
			$results = $this->select_data( $table );
			// Create chart dataset
			foreach( $results as $result ){
				$output['label'][] = date_i18n( "M j, Y", strtotime( $result->labels ) );
				$output['data'][]  = $result->counts;
			}

			return $output;
		}

		/**
		 * Set custom options for charts
		 *
		 * @since 3.5
		 * @param string $table
		 * @param array $options
		 * @return void
		 */
		public function charts_options( $name, $options = array() ){

			switch ( $name ) {
				case 'all':
					$options = array(
						'label'                => esc_html__( "All", WP_ULIKE_PRO_DOMAIN ),
						'backgroundColor'      => "#888add",
						'borderColor'          => "#888add",
						'pointBackgroundColor' => "rgba(255,255,255,1)",
						'fill'                 => false,
						'lineTension'          => 0,
						'borderWidth'          => 3
					);
					break;

				case 'like':
					$options = array(
						'label'                => esc_html__( "Like", WP_ULIKE_PRO_DOMAIN ),
						'backgroundColor'      => "#12b89b",
						'borderColor'          => "#12b89b",
						'pointBackgroundColor' => "rgba(255,255,255,1)",
						'fill'                 => false,
						'lineTension'          => 0,
						'borderWidth'          => 2
					);
					break;

				case 'dislike':
					$options = array(
						'label'                => esc_html__( "Dislike", WP_ULIKE_PRO_DOMAIN ),
						'backgroundColor'      => "#e24f4e",
						'borderColor'          => "#e24f4e",
						'pointBackgroundColor' => "rgba(255,255,255,1)",
						'fill'                 => false,
						'lineTension'          => 0,
						'borderWidth'          => 2
					);
					break;

				case 'unlike':
					$options = array(
						'label'                => esc_html__( "Un-Like", WP_ULIKE_PRO_DOMAIN ),
						'backgroundColor'      => "#f18e2d",
						'borderColor'          => "#f18e2d",
						'pointBackgroundColor' => "rgba(255,255,255,1)",
						'fill'                 => false,
						'lineTension'          => 0,
						'borderWidth'          => 2
					);
					break;
				case 'undislike':
					$options = array(
						'label'                => esc_html__( "Un-Dislike", WP_ULIKE_PRO_DOMAIN ),
						'backgroundColor'      => "#4a5ca4",
						'borderColor'          => "#4a5ca4",
						'pointBackgroundColor' => "rgba(255,255,255,1)",
						'fill'                 => false,
						'lineTension'          => 0,
						'borderWidth'          => 2
					);
					break;
			}

			return $options;
		}

		/**
		 * Get The Logs Data From Tables
		 *
		 * @author Alimir
		 * @param string $table
		 * @since 2.0
		 * @return String
		 */
		public function select_data( $table ){

			$query  = sprintf( "
					SELECT DATE(date_time) AS labels,
					count(date_time) AS counts
					FROM %s
					WHERE TO_DAYS(NOW()) - TO_DAYS(date_time) <= 30
					GROUP BY labels
					ASC LIMIT %d",
					$this->wpdb->prefix . $table,
					30
				);

			$result = $this->wpdb->get_results( $query );

			if( empty( $result ) ) {
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
					return number_format_i18n( $count_all_logs );
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

	        return  empty( $counter_value ) ? 0 : number_format_i18n( $counter_value );
		}

		/**
		 * Flush some transient
		 *
		 * @param string $transientName
		 * @return void
		 */
		public function flush_transient( $transientName ){
			wp_ulike_delete_transient( $transientName );
		}

		/**
		 * Display top likers in html format
		 *
		 * @return string
		 */
		public function display_top_likers( $daterange ){
			$top_likers = wp_ulike_get_best_likers_info( 5, $daterange );
			$result     = '';
			$counter    = 1;

			if( empty( $top_likers ) ){
				$period_info = is_array( $daterange ) ? implode( ' - ', $daterange ) : $daterange;
				return sprintf( '<div class="wp-ulike-flex wp-ulike-users-list">%s "%s" %s</div>', esc_html__( 'No results were found in', WP_ULIKE_PRO_DOMAIN ), $period_info, esc_html__( 'period', WP_ULIKE_PRO_DOMAIN ) );
			}

			foreach ( $top_likers as $user ) {
				$user_ID  = stripslashes( $user->user_id );
				$userdata = get_userdata( $user_ID );
				$username = empty( $userdata ) ? esc_html__('Guest User',WP_ULIKE_PRO_DOMAIN) : $userdata->display_name;

				$result  .= '
	            <div class="wp-ulike-flex wp-ulike-users-list">
	                <div class="wp-ulike-counter">
	                	<i class="wp-ulike-icons-trophy"></i>
	                	<span class="wp-ulike-counter">'.$counter++.'th</span>
	                </div>
	                <div class="wp-ulike-info">
	                	<i class="wp-ulike-icons-profile-male"></i>
						<span class="wp-ulike-user-name">'.$username.'</span>
	                </div>
	                <div class="wp-ulike-total">
	                	<i class="wp-ulike-icons-heart"></i>
						<span class="wp-ulike-user-name">'. number_format_i18n( $user->SumUser ) .'</span>
	                </div>
	            </div>';
			}

			return $result;
		}

		/**
		 * Get top posts
		 *
		 * @param array $args
		 * @return void
		 */
		public function top_posts( $args = array() ) {

			$defaults = array(
				"numberOf"    => 15,
				"rel_type"    => '',
				"period"      => 'all'
			);
			// Parse args
			$settings = wp_parse_args( $args, $defaults );
			// Extract settings
			extract($settings);

			$posts = wp_ulike_get_most_liked_posts( $numberOf, $rel_type, 'post', $period, array( 'like', 'dislike' ) );

			if( empty( $posts ) ){
				$period_info = is_array( $period ) ? implode( ' - ', $period ) : $period;
				return sprintf( '<li>%s "%s" %s</li>', esc_html__( 'No results were found in', WP_ULIKE_PRO_DOMAIN ), $period_info, esc_html__( 'period', WP_ULIKE_PRO_DOMAIN ) );
			}

			$result = '';

			foreach ($posts as $post) {
				// Check post title existence
				if( empty( $post->post_title ) ){
					continue;
				}

				$post_title    = stripslashes($post->post_title);
				$permalink     = get_permalink($post->ID);
				$is_distinct   = wp_ulike_setting_repo::isDistinct('post');
				$like_count    = wp_ulike_get_counter_value( wp_ulike_get_the_id( $post->ID ), 'post', 'like', $is_distinct, $period );
				$dislike_count = wp_ulike_get_counter_value( wp_ulike_get_the_id( $post->ID ), 'post', 'dislike', $is_distinct, $period );

				$result .= sprintf(
					'<li><a href="%s">%s</a> <span class="wp_ulike_item_counter">%s%s</span></li>',
					$permalink,
					$post_title,
					!empty( $like_count ) ? '<span class="wp_ulike_up_vote_count"><i class="wp-ulike-icons-thumbs-up2"></i> ' . number_format_i18n( $like_count ) . '</span>' : '',
					!empty( $dislike_count ) ? '<span class="wp_ulike_down_vote_count"><i class="wp-ulike-icons-thumbs-down2"></i> ' . number_format_i18n( $dislike_count ) . '</span>' : ''
				);
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
				"numberOf"    => 15,
				"period"      => 'all'
			);
			// Parse args
			$settings 		= wp_parse_args( $args, $defaults );
			// Extract settings
			extract($settings);

			$comments = wp_ulike_get_most_liked_comments( $numberOf, '', $period, array( 'like', 'dislike' ) );

			if( empty( $comments ) ){
				$period_info = is_array( $period ) ? implode( ' - ', $period ) : $period;
				return sprintf( '<li>%s "%s" %s</li>', esc_html__( 'No results were found in', WP_ULIKE_PRO_DOMAIN ), $period_info, esc_html__( 'period', WP_ULIKE_PRO_DOMAIN ) );
			}

			$result = '';

			foreach ($comments as $comment) {
				$comment_author    = stripslashes($comment->comment_author);
				$post_title        = get_the_title($comment->comment_post_ID);
				$comment_permalink = get_comment_link($comment->comment_ID);
				$is_distinct       = wp_ulike_setting_repo::isDistinct('comment');
				$like_count        = wp_ulike_get_counter_value($comment->comment_ID, 'comment', 'like', $is_distinct, $period );
				$dislike_count     = wp_ulike_get_counter_value($comment->comment_ID, 'comment', 'dislike', $is_distinct, $period );

				$result .= sprintf(
					'<li><span class="comment-info"><span class="comment-author-link">%s</span> %s <a href="%s">%s</a></span><span class="wp_ulike_item_counter">%s%s</span></li>',
					$comment_author,
					esc_html__('on',WP_ULIKE_PRO_DOMAIN),
					$comment_permalink,
					$post_title,
					!empty( $like_count ) ? '<span class="wp_ulike_up_vote_count"><i class="wp-ulike-icons-thumbs-up2"></i> ' . number_format_i18n( $like_count ) . '</span>' : '',
					!empty( $dislike_count ) ? '<span class="wp_ulike_down_vote_count"><i class="wp-ulike-icons-thumbs-down2"></i> ' . number_format_i18n( $dislike_count ) . '</span>' : ''
				);
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
				return '<li>' . sprintf( esc_html__( '%s is Not Activated!', WP_ULIKE_PRO_DOMAIN ) ,esc_html__( 'bbPress', WP_ULIKE_PRO_DOMAIN ) ) . '</li>';
			}

			$defaults = array(
				"numberOf"    => 15,
				"period"      => 'all'
			);
			// Parse args
			$settings = wp_parse_args( $args, $defaults );
			// Extract settings
			extract($settings);

			$posts = wp_ulike_get_most_liked_posts( $numberOf, array( 'topic', 'reply' ), 'topic', $period, array( 'like', 'dislike' ) );

			if( empty( $posts ) ){
				$period_info = is_array( $period ) ? implode( ' - ', $period ) : $period;
				return sprintf( '<li>%s "%s" %s</li>', esc_html__( 'No results were found in', WP_ULIKE_PRO_DOMAIN ), $period_info, esc_html__( 'period', WP_ULIKE_PRO_DOMAIN ) );
			}

			$result = '';

			foreach ($posts as $post) {
				$post_title    = function_exists('bbp_get_forum_title') ? bbp_get_forum_title( $post->ID ) : $post->post_title;
				$permalink     = 'topic' === get_post_type( $post->ID ) ? bbp_get_topic_permalink( $post->ID ) : bbp_get_reply_url( $post->ID );
				$is_distinct   = wp_ulike_setting_repo::isDistinct('topic');
				$like_count    = wp_ulike_get_counter_value($post->ID, 'topic', 'like', $is_distinct, $period );
				$dislike_count = wp_ulike_get_counter_value($post->ID, 'topic', 'dislike', $is_distinct, $period );

				$result .= sprintf(
					'<li><a href="%s">%s</a> <span class="wp_ulike_item_counter">%s%s</span></li>',
					$permalink,
					$post_title,
					!empty( $like_count ) ? '<span class="wp_ulike_up_vote_count"><i class="wp-ulike-icons-thumbs-up2"></i> ' . number_format_i18n( $like_count ) . '</span>' : '',
					!empty( $dislike_count ) ? '<span class="wp_ulike_down_vote_count"><i class="wp-ulike-icons-thumbs-down2"></i> ' . number_format_i18n( $dislike_count ) . '</span>' : ''
				);
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
				return '<li>' . sprintf( esc_html__( '%s is Not Activated!', WP_ULIKE_PRO_DOMAIN ) ,esc_html__( 'BuddyPress', WP_ULIKE_PRO_DOMAIN ) ) . '</li>';
			}

			$defaults = array(
				"numberOf"    => 15,
				"period"      => 'all'
			);
			// Parse args
			$settings 		= wp_parse_args( $args, $defaults );
			// Extract settings
			extract($settings);

	        if ( is_multisite() ) {
	            $bp_prefix = 'base_prefix';
	        } else {
	            $bp_prefix = 'prefix';
			}

			$activities = wp_ulike_get_most_liked_activities( $numberOf, $period, array( 'like', 'dislike' ) );

			if( empty( $activities ) ){
				$period_info = is_array( $period ) ? implode( ' - ', $period ) : $period;
				return sprintf( '<li>%s "%s" %s</li>', esc_html__( 'No results were found in', WP_ULIKE_PRO_DOMAIN ), $period_info, esc_html__( 'period', WP_ULIKE_PRO_DOMAIN ) );
			}

			$result = '';

			foreach ($activities as $activity) {
				$activity_permalink = function_exists('bp_activity_get_permalink') ? bp_activity_get_permalink( $activity->id ) : '';
				$activity_action    = ! empty( $activity->content ) ? $activity->content : $activity->action;
				$is_distinct        = wp_ulike_setting_repo::isDistinct('activity');
				$like_count         = wp_ulike_get_counter_value($activity->id, 'activity', 'like', $is_distinct, $period );
				$dislike_count      = wp_ulike_get_counter_value($activity->id, 'activity', 'dislike', $is_distinct, $period );

				// Skip empty activities
				if( empty( $activity_action ) ){
					continue;
				}

				$result .= sprintf(
					'<li><a href="%s">%s</a> <span class="wp_ulike_item_counter">%s%s</span></li>',
					esc_url( $activity_permalink ),
					wp_trim_words( $activity_action, 20, null ),
					!empty( $like_count ) ? '<span class="wp_ulike_up_vote_count"><i class="wp-ulike-icons-thumbs-up2"></i> ' . number_format_i18n( $like_count ) . '</span>' : '',
					!empty( $dislike_count ) ? '<span class="wp_ulike_down_vote_count"><i class="wp-ulike-icons-thumbs-down2"></i> ' . number_format_i18n( $dislike_count ) . '</span>' : ''
				);
			}

			return $result;
		}

		/**
		 * Tops Summaries
		 *
		 * @param string $type
		 * @since 3.5
		 * @return array
		 */
		public function get_top( $type, $has_daterange, $filter = '' ){

			if( ! $has_daterange ){
				$lastDateRange = wp_ulike_get_transient( 'wp_ulike_pro_daterange_of_top_' . $type );
				$this->setDateRange( $lastDateRange ? $lastDateRange : 'all' );
			} else {
				wp_ulike_set_transient( 'wp_ulike_pro_daterange_of_top_' . $type, $this->dateRange , 1 * YEAR_IN_SECONDS );
			}

			switch( $type ){
				case 'posts':
					return $this->top_posts( array(
						'rel_type' => $filter,
						'period'   => empty( $this->dateRange ) ? 'all' : $this->dateRange,
					) );
					break;
				case 'comments':
					return $this->top_comments( array(
						'period' => empty( $this->dateRange ) ? 'all' : $this->dateRange
					) );
				break;
				case 'activities':
					return $this->top_activities( array(
						'period' => empty( $this->dateRange ) ? 'all' : $this->dateRange
					) );
				break;
				case 'topics':
					return $this->top_topics( array(
						'period' => empty( $this->dateRange ) ? 'all' : $this->dateRange
					) );
				break;
				case 'likers':
					return $this->display_top_likers( empty( $this->dateRange ) ? 'all' : $this->dateRange );
				break;
				default:
					return;
			}
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