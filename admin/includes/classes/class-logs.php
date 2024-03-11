<?php
/**
 * Class for logs process
 * 
 * @package    wp-ulike-pro
 * @author     TechnoWich 2024
 * @link       https://wpulike.com
 */

// no direct access allowed
if ( ! defined('ABSPATH') ) {
    die();
}

if ( ! class_exists( 'WP_Ulike_Pro_Logs' ) ) {

	class WP_Ulike_Pro_Logs{
		// Private variables
		private $wpdb, $table, $page, $per_page, $sort, $search;

		/**
		 * Constructor
		 */
		function __construct( $table, $page = 1, $per_page = 15, $search = '', $sort = array(
			'type'  => 'ASC',
			'field' => 'id'
		) ){
			global $wpdb;
			$this->wpdb     = $wpdb;
			$this->table    = $table;
			$this->page     = $page;
			$this->per_page = $per_page;
			$this->search   = $search;
			$this->sort     = $sort;
		}

		/**
		 * get SQL results
		 *
		 * @return object
		 */
		public function get_results(){
			$table = esc_sql( $this->wpdb->prefix . $this->table );
			$paged = ( $this->page - 1 ) * $this->per_page;
			$orderBy   = $this->sort['field'];
			$orderType = $this->sort['type'];
			$serachBy  = $this->generate_search_condition( $this->search );

			return $this->wpdb->get_results(
				$this->wpdb->prepare( "SELECT * FROM {$table} $serachBy ORDER BY {$orderBy} {$orderType} LIMIT {$paged}, {$this->per_page}" )
			);
		}

		/**
		 * get SQL row
		 *
		 * @return object
		 */
		public function get_row( $item_ID ){
			$table = esc_sql( $this->wpdb->prefix . $this->table );

			return $this->wpdb->get_row( "
				SELECT *
				FROM `$table`
				WHERE `id` = $item_ID"
			);
		}

		/**
		 * get all SQL results
		 *
		 * @return object
		 */
		public function get_all_rows(){
			$table = esc_sql( $this->wpdb->prefix . $this->table );
			$orderBy   = $this->sort['field'];
			$orderType = $this->sort['type'];

			return $this->wpdb->get_results( "
				SELECT *
				FROM `$table`
				ORDER BY $orderBy $orderType"
			);
		}

		/**
		 * Generate search condition string by table type
		 *
		 * @param string $search
		 * @return string
		 */
		private function generate_search_condition( $search ){
			$output = 'WHERE 1';
			$search = normalize_whitespace( $search );

			if( ! empty( $search ) ){
				switch ( $this->table ) {
					case 'ulike_comments':
						$output  = sprintf( '
						WHERE Concat(`comment_id`, " ", `status`, " ", `ip`, " ", `date_time` ) like "%1$s" OR `comment_id` IN
						(SELECT comment_ID FROM `%2$s` WHERE `comment_content` LIKE "%1$s" OR `comment_author` LIKE "%1$s" ) OR `user_id` IN
						(SELECT ID FROM `%3$s` WHERE `user_login` LIKE "%1$s")'
						, '%' . $search . '%', $this->wpdb->comments, $this->wpdb->users
						);
						break;

					case 'ulike_activities':
						if ( is_multisite() ) {
							$bp_prefix = 'base_prefix';
						} else {
							$bp_prefix = 'prefix';
						}

						$output  = sprintf( '
						WHERE Concat(`activity_id`, " ", `status`, " ", `ip`, " ", `date_time` ) like "%1$s" OR `activity_id` IN
						(SELECT id FROM `%2$sbp_activity` WHERE `id` LIKE "%1$s") OR `user_id` IN
						(SELECT ID FROM `%3$s` WHERE `user_login` LIKE "%1$s")'
						, '%' . $search . '%', $this->wpdb->$bp_prefix, $this->wpdb->users
						);
						break;

					case 'ulike_forums':
						$output  = sprintf( '
						WHERE Concat(`topic_id`, " ", `status`, " ", `ip`, " ", `date_time` ) like "%1$s" OR `topic_id` IN
						(SELECT ID FROM `%2$s` WHERE `post_title` LIKE "%1$s") OR `user_id` IN
						(SELECT ID FROM `%3$s` WHERE `user_login` LIKE "%1$s")'
						, '%' . $search . '%', $this->wpdb->posts, $this->wpdb->users
						);
						break;

					default:
						$output  = sprintf( '
						WHERE Concat(`post_id`, " ", `status`, " ", `ip`, " ", `date_time` ) like "%1$s" OR `post_id` IN
						(SELECT ID FROM `%2$s` WHERE `post_title` LIKE "%1$s") OR `user_id` IN
						(SELECT ID FROM `%3$s` WHERE `user_login` LIKE "%1$s")'
						, '%' . $search . '%', $this->wpdb->posts, $this->wpdb->users
						);
						break;
				}
			}

			return $output;
		}

		/**
		 * Delete selected rows
		 *
		 * @param array $items
		 * @return void
		 */
		public function delete_rows( $items ){
			$table = esc_sql( $this->wpdb->prefix . $this->table );
			$selectedIds = array();

			foreach ($items as $key => $item) {
				if( ! empty( $item['id'] ) ){
					$selectedIds[] = $item['id'];
				}
			}
			if( ! empty( $selectedIds ) ){
				$selectedIds = implode( ',', array_map( 'absint', $selectedIds ) );
				$this->wpdb->query( "DELETE FROM $table WHERE ID IN($selectedIds)" );
			}
		}

		/**
		 * Get total rows per table
		 *
		 * @return string
		 */
		private function get_total_records(){
			$table  = esc_sql( $this->wpdb->prefix . $this->table );
			$search = $this->generate_search_condition( $this->search );
			return $this->wpdb->get_var( "SELECT COUNT(*) FROM `$table` $search" );
		}

		/**
		 * Get response in JSON
		 *
		 * @param integer $page
		 * @param integer $per_page
		 * @param array $sort
		 * @param string $search
		 * @return string
		 */
		public function get_rows( $page, $per_page, $sort, $search ){
			$this->page     = esc_sql( $page );
			$this->per_page = esc_sql( $per_page );
			$this->sort     = esc_sql( $sort );
			$this->search   = esc_sql( $search );
			$records = $this->get_trnasformed_rows();
			return json_encode( array(
				'rows'         => $records,
				'totalRecords' => $this->get_total_records()
			) );
		}

		/**
		 * get transformed rows
		 *
		 * @return object
		 */
		private function get_trnasformed_rows(){
			$dataset = $this->get_results();
			return $this->get_formatted_data( $dataset );
		}

		/**
		 * get transformed rows for csv export
		 *
		 * @return array
		 */
		public function get_csv_trnasformed_rows(){
			$dataset = $this->get_all_rows();
			$formatted_data = $this->get_formatted_data( $dataset );
			$output = [];

			foreach ($formatted_data as $key => $row) {
				if( isset( $row->post_id ) ){
					$output[$key]['post id'] = $row->post_id;
				}
				if( isset( $row->comment_id ) ){
					$output[$key]['comment id'] = $row->comment_id;
				}
				if( isset( $row->activity_id ) ){
					$output[$key]['activity id'] = $row->activity_id;
				}
				if( isset( $row->topic_id ) ){
					$output[$key]['topic id'] = $row->topic_id;
				}
				if( isset( $row->user_id ) ){
					$output[$key]['user name'] = wp_strip_all_tags($row->user_id);
				}
				if( isset( $row->post_title ) ){
					$output[$key]['post title'] = wp_strip_all_tags($row->post_title);
				}
				if( isset( $row->topic_title ) ){
					$output[$key]['topic title'] = wp_strip_all_tags($row->topic_title);
				}
				if( isset( $row->activity_title ) ){
					$output[$key]['activity title'] = wp_strip_all_tags($row->activity_title);
				}
				if( isset( $row->post_type ) ){
					$output[$key]['post type'] = $row->post_type;
				}
				if( isset( $row->category ) ){
					$output[$key]['category'] = wp_strip_all_tags( $row->category );
				}
				if( isset( $row->comment_author ) ){
					$output[$key]['comment author'] = $row->comment_author;
				}
				if( isset( $row->comment_content ) ){
					$output[$key]['comment content'] = wp_strip_all_tags($row->comment_content);
				}
				if( isset( $row->date_time ) ){
					$output[$key]['date time'] = $row->date_time;
				}
				if( isset( $row->status ) ){
					$output[$key]['status'] = $row->status;
				}
				if( isset( $row->ip ) ){
					$output[$key]['ip'] = $row->ip;
				}
			}

			return $output;
		}

		/**
		 * formate inputted dataset
		 *
		 * @param array $dataset
		 * @return array
		 */
		private function get_formatted_data( $dataset ){
			$output = $dataset;

			foreach ($dataset as $key => $row) {
				if( isset( $row->date_time ) ){
					$output[$key]->date_time = wp_ulike_date_i18n( $row->date_time );
				}
				if( isset( $row->user_id ) ){
					if( NULL != ( $user_info = get_userdata( $row->user_id ) ) ){
						$output[$key]->user_id = get_avatar( $user_info->user_email, 16, '' , 'avatar') . '<em> @' . $user_info->user_login . '</em>';
					} else {
						$output[$key]->user_id = '<em> #'. esc_html__( 'Guest User', WP_ULIKE_PRO_DOMAIN ) .'</em>';
					}
				}
				if( isset( $row->post_id ) ){
					$title = get_the_title( $row->post_id );
					if( !empty( $title ) ){
						$output[$key]->post_type = get_post_type( $row->post_id );

						$post_categories = wp_get_post_categories( $row->post_id );
						$cats = '';

						foreach($post_categories as $k => $c){
							$cat = get_category( $c );
							$cats.= sprintf( '%s<a href="%s">%s</a>', $k ? ' , ' : '', get_category_link($cat), $cat->name );
						}

						$output[$key]->category = $cats;

						$output[$key]->post_title   = sprintf( "<a href='%s'> %s </a>" , get_permalink($row->post_id), $title );
					}
				}
				if( isset( $row->topic_id ) ){
					$topic_title = function_exists('bbp_get_forum_title') ? bbp_get_forum_title( $row->topic_id ) : get_the_title( $row->topic_id );
					if( !empty( $topic_title ) ){
						$output[$key]->topic_title = sprintf( "<a href='%s'> %s </a>" , get_permalink($row->topic_id), $topic_title );
					}
				}
				if( isset( $row->activity_id ) ){
					// Activity link
					$activity_link  = function_exists('bp_activity_get_permalink') ? bp_activity_get_permalink( $row->activity_id ) : '';
					// Activity title
					$activity_title = esc_html__('Activity Permalink',WP_ULIKE_PRO_DOMAIN);
					if( class_exists('BP_Activity_Activity') ){
						$activity_obj = new BP_Activity_Activity( $row->activity_id );

						if ( isset( $activity_obj->current_comment ) ) {
							$activity_obj = $activity_obj->current_comment;
						}

						$activity_title = ! empty( $activity_obj->content ) ? $activity_obj->content : $activity_obj->action;
					}

 					$output[$key]->activity_title = sprintf( "<a href='%s'> %s </a>" , $activity_link, wp_strip_all_tags( $activity_title ) );
				}
				if( isset( $row->comment_id ) ){
					if( NULL != ( $comment = get_comment( $row->comment_id ) ) ){
						$output[$key]->comment_author  = $comment->comment_author;
						$output[$key]->comment_content = sprintf( "<a href='%s'> %s </a>" , esc_url( get_comment_link( $comment ) ), $comment->comment_content );
					} else {
						$output[$key]->comment_author  = $output[$key]->comment_content = esc_html__( 'Not Found!', WP_ULIKE_PRO_DOMAIN );
					}
				}
			}

			return apply_filters( 'wp_ulike_pro_get_trnasformed_rows', $output );
		}

	}


}