<?php
/**
 * Emoji/star engagement logs (ulike_pulse table).
 *
 * @package WP ULike Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_Ulike_Pro_Engagement_Logs' ) ) {

	/**
	 * Logs reader for emoji reactions and star ratings.
	 */
	class WP_Ulike_Pro_Engagement_Logs {

		private $wpdb;
		private $item_type;
		private $page;
		private $per_page;
		private $sort;
		private $search;

		/**
		 * @param string $item_type post|comment|activity|topic.
		 * @param int    $page      Page number.
		 * @param int    $per_page  Rows per page.
		 * @param string $search    Search term.
		 * @param array  $sort      Sort config.
		 */
		public function __construct( $item_type, $page = 1, $per_page = 15, $search = '', $sort = array(
			'type'  => 'ASC',
			'field' => 'id',
		) ) {
			global $wpdb;

			$this->wpdb      = $wpdb;
			$this->item_type = sanitize_key( $item_type );
			$this->page      = max( 1, (int) $page );
			$this->per_page  = max( 1, (int) $per_page );
			$this->search    = $search;
			$this->sort      = $sort;
		}

		/**
		 * @return array
		 */
		public function get_results() {
			$table    = esc_sql( wp_ulike_pro_engagement_table() );
			$paged    = absint( ( $this->page - 1 ) * $this->per_page );
			$per_page = absint( $this->per_page );

			$allowed_fields = array( 'id', 'user_id', 'item_id', 'engagement_kind', 'engagement_key', 'status', 'ip', 'date_time', 'value' );
			$order_by       = isset( $this->sort['field'] ) && in_array( $this->sort['field'], $allowed_fields, true )
				? esc_sql( $this->sort['field'] )
				: 'id';
			$order_type     = isset( $this->sort['type'] ) && in_array( strtoupper( $this->sort['type'] ), array( 'ASC', 'DESC' ), true )
				? strtoupper( $this->sort['type'] )
				: 'DESC';

			$search_sql = $this->generate_search_condition( $this->search );

			return $this->wpdb->get_results(
				$this->wpdb->prepare(
					"SELECT * FROM `{$table}` {$search_sql} ORDER BY `{$order_by}` {$order_type} LIMIT %d, %d",
					$paged,
					$per_page
				)
			);
		}

		/**
		 * @return array
		 */
		public function get_all_rows() {
			$table = esc_sql( wp_ulike_pro_engagement_table() );

			$allowed_fields = array( 'id', 'user_id', 'item_id', 'engagement_kind', 'engagement_key', 'status', 'ip', 'date_time', 'value' );
			$order_by       = isset( $this->sort['field'] ) && in_array( $this->sort['field'], $allowed_fields, true )
				? esc_sql( $this->sort['field'] )
				: 'id';
			$order_type     = isset( $this->sort['type'] ) && in_array( strtoupper( $this->sort['type'] ), array( 'ASC', 'DESC' ), true )
				? strtoupper( $this->sort['type'] )
				: 'DESC';

			$search_sql = $this->generate_search_condition( $this->search );

			return $this->wpdb->get_results( "SELECT * FROM `{$table}` {$search_sql} ORDER BY `{$order_by}` {$order_type}" );
		}

		/**
		 * @param string $search Search term.
		 * @return string
		 */
		private function generate_search_condition( $search ) {
			$kinds_sql = ' AND ' . wp_ulike_pro_pulse_pro_kinds_sql();
			$output = $this->wpdb->prepare( 'WHERE item_type = %s' . $kinds_sql, $this->item_type );

			$search = normalize_whitespace( $search );
			if ( empty( $search ) ) {
				return $output;
			}

			$search_like   = '%' . $this->wpdb->esc_like( $search ) . '%';
			$users_table   = esc_sql( $this->wpdb->users );
			$posts_table   = esc_sql( $this->wpdb->posts );
			$comments_table = esc_sql( $this->wpdb->comments );

			switch ( $this->item_type ) {
				case 'comment':
					return $this->wpdb->prepare(
						"{$output} AND (
							CONCAT(item_id, ' ', engagement_key, ' ', status, ' ', ip, ' ', date_time) LIKE %s
							OR item_id IN (SELECT comment_ID FROM `{$comments_table}` WHERE comment_content LIKE %s OR comment_author LIKE %s)
							OR user_id IN (SELECT ID FROM `{$users_table}` WHERE user_login LIKE %s OR display_name LIKE %s OR user_email LIKE %s)
						)",
						$search_like,
						$search_like,
						$search_like,
						$search_like,
						$search_like,
						$search_like
					);

				case 'activity':
					if ( ! function_exists( 'is_buddypress' ) ) {
						break;
					}
					$bp_prefix = is_multisite() ? $this->wpdb->base_prefix : $this->wpdb->prefix;
					$bp_activity_table = esc_sql( $bp_prefix . 'bp_activity' );
					return $this->wpdb->prepare(
						"{$output} AND (
							CONCAT(item_id, ' ', engagement_key, ' ', status, ' ', ip, ' ', date_time) LIKE %s
							OR item_id IN (SELECT id FROM `{$bp_activity_table}` WHERE content LIKE %s)
							OR user_id IN (SELECT ID FROM `{$users_table}` WHERE user_login LIKE %s OR display_name LIKE %s OR user_email LIKE %s)
						)",
						$search_like,
						$search_like,
						$search_like,
						$search_like,
						$search_like
					);

				case 'topic':
					return $this->wpdb->prepare(
						"{$output} AND (
							CONCAT(item_id, ' ', engagement_key, ' ', status, ' ', ip, ' ', date_time) LIKE %s
							OR item_id IN (SELECT ID FROM `{$posts_table}` WHERE post_title LIKE %s)
							OR user_id IN (SELECT ID FROM `{$users_table}` WHERE user_login LIKE %s OR display_name LIKE %s OR user_email LIKE %s)
						)",
						$search_like,
						$search_like,
						$search_like,
						$search_like,
						$search_like
					);

				default:
					return $this->wpdb->prepare(
						"{$output} AND (
							CONCAT(item_id, ' ', engagement_key, ' ', status, ' ', ip, ' ', date_time) LIKE %s
							OR item_id IN (SELECT ID FROM `{$posts_table}` WHERE post_title LIKE %s)
							OR user_id IN (SELECT ID FROM `{$users_table}` WHERE user_login LIKE %s OR display_name LIKE %s OR user_email LIKE %s)
						)",
						$search_like,
						$search_like,
						$search_like,
						$search_like,
						$search_like
					);
			}

			return $output;
		}

		/**
		 * @param array $items Row IDs.
		 * @return void
		 */
		public function delete_rows( $items ) {
			$table = esc_sql( wp_ulike_pro_engagement_table() );

			if ( empty( $items ) || ! is_array( $items ) ) {
				return;
			}

			$items = array_filter( array_map( 'absint', $items ) );
			if ( empty( $items ) ) {
				return;
			}

			$placeholders = implode( ',', array_fill( 0, count( $items ), '%d' ) );
			$args         = array_merge( array( $this->item_type ), $items );
			$rows         = $this->wpdb->get_results(
				$this->wpdb->prepare(
					"SELECT DISTINCT item_id FROM `{$table}` WHERE item_type = %s AND id IN ({$placeholders})",
					...$args
				)
			);

			$this->wpdb->query(
				$this->wpdb->prepare(
					"DELETE FROM `{$table}` WHERE item_type = %s AND id IN ({$placeholders})",
					...$args
				)
			);

			if ( ! empty( $rows ) && class_exists( 'WP_Ulike_Pro_Engagement_Counter' ) ) {
				foreach ( $rows as $row ) {
					WP_Ulike_Pro_Engagement_Counter::rebuild_item_counters( (int) $row->item_id, $this->item_type );
				}
			}
		}

		/**
		 * @return int
		 */
		private function get_total_records() {
			$table      = esc_sql( wp_ulike_pro_engagement_table() );
			$search_sql = $this->generate_search_condition( $this->search );

			return (int) $this->wpdb->get_var( "SELECT COUNT(*) FROM `{$table}` {$search_sql}" );
		}

		/**
		 * @return array
		 */
		public function get_rows() {
			return array(
				'rows'         => $this->get_trnasformed_rows(),
				'totalRecords' => $this->get_total_records(),
			);
		}

		/**
		 * @return array
		 */
		private function get_trnasformed_rows() {
			return $this->get_formatted_data( $this->get_results() );
		}

		/**
		 * @return array
		 */
		public function get_csv_trnasformed_rows() {
			$formatted_data = $this->get_formatted_data( $this->get_all_rows() );
			$output         = array();

			foreach ( $formatted_data as $key => $row ) {
				if ( isset( $row->post_id ) ) {
					$output[ $key ]['post id'] = $row->post_id;
				}
				if ( isset( $row->comment_id ) ) {
					$output[ $key ]['comment id'] = $row->comment_id;
				}
				if ( isset( $row->activity_id ) ) {
					$output[ $key ]['activity id'] = $row->activity_id;
				}
				if ( isset( $row->topic_id ) ) {
					$output[ $key ]['topic id'] = $row->topic_id;
				}
				if ( isset( $row->user_id ) ) {
					$output[ $key ]['user name'] = wp_strip_all_tags( $row->user_id );
				}
				if ( isset( $row->post_title ) ) {
					$output[ $key ]['post title'] = wp_strip_all_tags( $row->post_title );
				}
				if ( isset( $row->topic_title ) ) {
					$output[ $key ]['topic title'] = wp_strip_all_tags( $row->topic_title );
				}
				if ( isset( $row->activity_title ) ) {
					$output[ $key ]['activity title'] = wp_strip_all_tags( $row->activity_title );
				}
				if ( isset( $row->date_time ) ) {
					$output[ $key ]['date time'] = $row->date_time;
				}
				if ( isset( $row->status ) ) {
					$output[ $key ]['status'] = $row->status;
				}
				if ( isset( $row->ip ) ) {
					$output[ $key ]['ip'] = $row->ip;
				}
			}

			// Back-compat: restores the pre-rewrite CSV export filter so sites
			// customizing/redacting exported log rows keep working.
			return apply_filters( 'wp_ulike_get_trnasformed_rows', $output );
		}

		/**
		 * @param array $dataset Raw rows.
		 * @return array
		 */
		private function get_formatted_data( $dataset ) {
			$output = $dataset;

			if ( empty( $output ) ) {
				return array();
			}

			foreach ( $dataset as $key => $row ) {
				if ( isset( $row->date_time ) ) {
					$output[ $key ]->date_time = wp_date( 'Y-m-d H:i:s', strtotime( $row->date_time ) );
				}

				if ( isset( $row->user_id ) ) {
					if ( null !== ( $user_info = get_userdata( $row->user_id ) ) ) {
						$output[ $key ]->user_id = '@' . $user_info->user_login;
					} else {
						$output[ $key ]->user_id = '#' . esc_html__( 'Guest User', WP_ULIKE_PRO_DOMAIN );
					}
				}

				$output[ $key ]->status = $this->format_status_label( $row );

				$item_id = isset( $row->item_id ) ? (int) $row->item_id : 0;
				if ( ! $item_id ) {
					continue;
				}

				switch ( $this->item_type ) {
					case 'comment':
						$output[ $key ]->comment_id = $item_id;
						$this->append_comment_fields( $output[ $key ], $item_id );
						break;
					case 'activity':
						$output[ $key ]->activity_id = $item_id;
						$this->append_activity_fields( $output[ $key ], $item_id );
						break;
					case 'topic':
						$output[ $key ]->topic_id = $item_id;
						$this->append_topic_fields( $output[ $key ], $item_id );
						break;
					default:
						$output[ $key ]->post_id = $item_id;
						$this->append_post_fields( $output[ $key ], $item_id );
						break;
				}
			}

			return apply_filters( 'wp_ulike_pro_engagement_log_rows', $output, $this->item_type );
		}

		/**
		 * @param object $row Log row.
		 * @return string
		 */
		private function format_status_label( $row ) {
			if ( 'removed' === $row->status ) {
				return esc_html__( 'Removed', WP_ULIKE_PRO_DOMAIN );
			}

			if ( 'emoji' === $row->engagement_kind && class_exists( 'WP_Ulike_Pro_Engagement_Registry' ) ) {
				$key      = (string) $row->engagement_key;
				$enabled  = WP_Ulike_Pro_Engagement_Registry::get_enabled_reactions( $this->item_type );
				$reaction = WP_Ulike_Pro_Engagement_Registry::get_reaction( $key, $this->item_type );
				if ( $reaction ) {
					$label = $reaction['emoji'] . ' ' . wp_strip_all_tags( $reaction['label'] );
					// Historical rows for reactions that are no longer enabled.
					if ( ! isset( $enabled[ $key ] ) ) {
						return sprintf(
							/* translators: %s: reaction label */
							esc_html__( '%s (disabled)', WP_ULIKE_PRO_DOMAIN ),
							$label
						);
					}
					return $label;
				}
			}

			if ( 'star' === $row->engagement_kind && ! empty( $row->value ) ) {
				return sprintf( esc_html__( '%d stars', WP_ULIKE_PRO_DOMAIN ), (int) $row->value );
			}

			return sanitize_text_field( (string) $row->engagement_key );
		}

		/**
		 * @param object $output Output row.
		 * @param int    $post_id Post ID.
		 * @return void
		 */
		private function append_post_fields( $output, $post_id ) {
			$title = get_the_title( $post_id );
			if ( empty( $title ) ) {
				return;
			}

			$output->post_type = get_post_type( $post_id );
			$output->post_title = sprintf(
				"<a href='%s'>%s</a>",
				esc_url( get_permalink( $post_id ) ),
				esc_html( $title )
			);

			$post_categories = wp_get_post_categories( $post_id );
			$cats            = '';
			foreach ( $post_categories as $k => $c ) {
				$cat = get_category( $c );
				if ( ! $cat || is_wp_error( $cat ) ) {
					continue;
				}
				$cats .= sprintf(
					'%s<a href="%s">%s</a>',
					$k ? ' , ' : '',
					esc_url( get_category_link( $cat ) ),
					esc_html( $cat->name )
				);
			}
			$output->category = $cats;
		}

		/**
		 * @param object $output Output row.
		 * @param int    $comment_id Comment ID.
		 * @return void
		 */
		private function append_comment_fields( $output, $comment_id ) {
			$comment = get_comment( $comment_id );
			if ( ! $comment ) {
				$output->comment_author  = esc_html__( 'Not found!', WP_ULIKE_PRO_DOMAIN );
				$output->comment_content = esc_html__( 'Not found!', WP_ULIKE_PRO_DOMAIN );
				return;
			}

			$output->comment_author  = esc_html( $comment->comment_author );
			$output->comment_content = sprintf(
				"<a href='%s'>%s</a>",
				esc_url( get_comment_link( $comment ) ),
				esc_html( wp_strip_all_tags( $comment->comment_content ) )
			);
		}

		/**
		 * @param object $output Output row.
		 * @param int    $activity_id Activity ID.
		 * @return void
		 */
		private function append_activity_fields( $output, $activity_id ) {
			$activity_link  = function_exists( 'bp_activity_get_permalink' ) ? bp_activity_get_permalink( $activity_id ) : '';
			$activity_title = esc_html__( 'Activity Permalink', WP_ULIKE_PRO_DOMAIN );

			if ( class_exists( 'BP_Activity_Activity' ) ) {
				$activity_obj = new BP_Activity_Activity( $activity_id );
				if ( isset( $activity_obj->current_comment ) ) {
					$activity_obj = $activity_obj->current_comment;
				}
				$activity_title = ! empty( $activity_obj->content ) ? $activity_obj->content : $activity_obj->action;
			}

			$output->activity_title = sprintf(
				"<a href='%s'>%s</a>",
				esc_url( $activity_link ),
				esc_html( wp_strip_all_tags( $activity_title ) )
			);
		}

		/**
		 * @param object $output Output row.
		 * @param int    $topic_id Topic ID.
		 * @return void
		 */
		private function append_topic_fields( $output, $topic_id ) {
			$topic_title = function_exists( 'bbp_get_forum_title' ) ? bbp_get_forum_title( $topic_id ) : get_the_title( $topic_id );
			if ( empty( $topic_title ) ) {
				return;
			}

			$output->topic_title = sprintf(
				"<a href='%s'>%s</a>",
				esc_url( get_permalink( $topic_id ) ),
				esc_html( $topic_title )
			);
		}
	}
}

