<?php
/**
 * @package WP_Ulike_Pro
 */
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

trait WP_Ulike_Pro_Stats_Trait_Tops {
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

			$cache_key = 'top_items_' . md5( wp_json_encode( $args ) . wp_json_encode( $date_range ) );
			$cached    = wp_cache_get( $cache_key, WP_ULIKE_PRO_DOMAIN );
			if ( false !== $cached ) {
				return $cached;
			}

			$result = null;

			switch( $args['type']  ){
				case 'post':
					$result = [
						'items' => $this->top_posts( $args ),
						'types' => wp_ulike_pro_get_public_post_types(),
						'total' => $this->get_top_counts( $args, 'posts' ),
					];
					break;
				case 'comment':
					$result = [
						'items' => $this->top_comments( $args ),
						'total' => $this->get_top_counts( $args, 'comments' ),
					];
					break;
				case 'activity':
					$result = [
						'items' => $this->top_activities( $args ),
						'total' => $this->get_top_counts( $args, 'activities' ),
					];
					break;
				case 'topic':
					$result = [
						'items' => $this->top_topics( $args ),
						'total' => $this->get_top_counts( $args, 'topics' ),
					];
					break;
				case 'engagers':
					$result = [
						'items' => $this->top_engagers( $args ),
						'total' => $this->get_top_counts( $args, 'engagers' ),
					];
					break;
			}

			if ( null !== $result ) {
				wp_cache_set( $cache_key, $result, WP_ULIKE_PRO_DOMAIN, 10 );
			}

			return $result;
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
				$args = wp_parse_args(
					$args,
					array(
						'period' => 'all',
						'status' => array( 'like', 'dislike' ),
						'search' => '',
					)
				);

				if ( ! empty( $args['search'] ) ) {
					return wp_ulike_pro_count_filtered_engagers( $args );
				}

				return wp_ulike_pro_count_top_combined_engagers( $args['period'], $args['status'] );
			}

		// Posts / Topics: always use WP_Query->found_posts so the total
		// matches the actual items WP_Query returns (the raw popular-ID count
		// would include deleted/trashed posts and inflate the page count,
		// producing phantom pages that render empty and reset pagination).
		if ( in_array( $data_type, array( 'posts', 'topics' ), true ) ) {
			return wp_ulike_pro_get_posts_query_total( $args );
		}

		if ( 'comments' === $data_type && ! empty( $args['search'] ) ) {
			return wp_ulike_pro_count_filtered_comments( $args );
		}

		if ( 'activities' === $data_type && ! empty( $args['search'] ) ) {
			return wp_ulike_pro_count_filtered_activities( $args );
		}

		// Comments / Activities: count UNIQUE items that have any engagement
		// kind (vote + emoji + star). Summing the per-kind totals would
		// double-count items with multiple engagement kinds.
		if ( in_array( $data_type, array( 'comments', 'activities' ), true ) ) {
			$item_map = array(
				'comments'   => 'comment',
				'activities' => 'activity',
			);
			$args['type'] = $item_map[ $data_type ];
			// Expand beyond the current page so pagination totals are real.
			$args['limit']  = wp_ulike_pro_tops_search_pool_limit();
			$args['offset'] = 1;

			$popular_args = wp_ulike_pro_prepare_popular_items_args( $args );
			$unique       = wp_ulike_pro_get_tops_union_item_ids( $popular_args );

			return count( $unique );
		}

		return wp_ulike_get_popular_items_total_number( $this->normalize_popular_items_args( $args ) );
	}

		/**
		 * Ensure popular-items queries have valid rel_type / status defaults.
		 *
		 * @param array $args Query arguments.
		 * @return array
		 */
		private function normalize_popular_items_args( $args ) {
			$defaults = array(
				'type'       => 'post',
				'rel_type'   => '',
				'is_popular' => true,
				'status'     => array( 'like', 'dislike' ),
			);
			$args = wp_parse_args( $args, $defaults );

			if ( 'topic' === $args['type'] ) {
				$args['rel_type'] = array( 'topic', 'reply' );
			} elseif ( empty( $args['rel_type'] ) && in_array( $args['type'], array( 'post', 'topic' ), true ) ) {
				$args['rel_type'] = get_post_types_by_support(
					array(
						'title',
						'editor',
						'thumbnail',
					)
				);
			}

			if ( empty( $args['rel_type'] ) && in_array( $args['type'], array( 'post', 'topic' ), true ) ) {
				$args['rel_type'] = array( 'post' );
			}

			if ( empty( $args['status'] ) ) {
				$args['status'] = array( 'like', 'dislike' );
			}

			return $args;
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
				"limit"      => 10,
				"search"     => '',
				"category"   => 0,
				"taxonomy"   => '',
				"include_engaged_users" => true,
				"engagement_keys" => array(),
				"values"          => array(),
			);
			// Parse args
			$settings = wp_parse_args( $args, $defaults );

			$posts = wp_ulike_pro_get_posts_query( $settings );

			$result = [];

			if($posts && $posts->have_posts()) {
				while($posts->have_posts()) {
					$posts->the_post();

					$post_id = wp_ulike_get_the_id();
					$period  = $settings['period'] ?? 'all';
					$metrics = wp_ulike_pro_get_tops_item_metrics( $post_id, 'post', $period, array(
						'engagement_keys' => (array) $settings['engagement_keys'],
						'values'          => (array) $settings['values'],
					) );

					$like_count    = $metrics['likes_count'];
					$dislike_count = $metrics['dislikes_count'];
					$thumbnail     = get_the_post_thumbnail_url( $post_id, 'thumbnail');
					$engaged_users_info = ! empty( $settings['include_engaged_users'] )
						? wp_ulike_pro_build_tops_engaged_users_list(
							$post_id,
							'post',
							$metrics['mode'],
							'ulike',
							'post_id'
						)
						: array();

					if( empty( $thumbnail ) ){
						$thumbnail = WP_ULIKE_PRO_ADMIN_URL . '/assets/img/no-image.svg';
					}

					$comment_number = get_comments_number($post_id);

					// Calculate engagement rate and views
					$engagement_data = $this->calculate_engagement_rate( $post_id, 'post', $like_count, $dislike_count, $period );
					$total_views = $engagement_data['total_views'];
					$engagement_rate = $engagement_data['engagement_rate'];

					// Skip period-over-period growth when there is no rate — avoids
					// two extra rate recalculations per row on empty-view sites.
					$engagement_growth = $engagement_rate > 0
						? $this->calculate_engagement_growth( $post_id, 'post', $like_count, $dislike_count, $period, $engagement_rate )
						: 0;

					// Build meta_data array conditionally
					$meta_data = [
						'Published'  => get_the_date( '', $post_id ),
						'Comments'   => $comment_number,
					];

					// Only include Views if it has a value
					if ( $total_views > 0 ) {
						$meta_data['Views'] = $total_views;
					}

					$tops_engagement = wp_ulike_pro_build_tops_engagement_payload(
						$metrics,
						$post_id,
						'post',
						$period,
						$engagement_rate,
						$engagement_growth
					);

					$result[] = [
						'id'             => $post_id,
						'title'          => get_the_title(),
						'image'          => $thumbnail,
						'permalink'      => get_permalink(),
						'likes_count'    => $like_count,
						'dislikes_count' => $dislike_count,
						'engaged_users'  => $engaged_users_info,
						'engager_count'  => wp_ulike_pro_count_item_unique_engagers( $post_id, 'post' ),
						'meta_data'      => $meta_data,
						'engagement'     => $tops_engagement['engagement'],
						'emoji_breakdown'=> $tops_engagement['emoji_breakdown'],
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
				"limit"      => 10,
				"search"     => '',
				"engagement_keys" => array(),
				"values"          => array(),
			);
			// Parse args
			$settings = wp_parse_args( $args, $defaults );

			$comments = wp_ulike_pro_get_comments_query( $settings );

			$result = [];

			if( $comments ) {
				foreach ( $comments as $comment ) {
					$period  = $settings['period'] ?? 'all';
					$metrics = wp_ulike_pro_get_tops_item_metrics( $comment->comment_ID, 'comment', $period, array(
						'engagement_keys' => (array) $settings['engagement_keys'],
						'values'          => (array) $settings['values'],
					) );

					$like_count    = $metrics['likes_count'];
					$dislike_count = $metrics['dislikes_count'];

					$engaged_users_info = wp_ulike_pro_build_tops_engaged_users_list(
						$comment->comment_ID,
						'comment',
						$metrics['mode'],
						'ulike_comments',
						'comment_id'
					);

					$comment_number = get_comments_number( $comment->comment_post_ID );

					// Calculate engagement rate and views
					$engagement_data = $this->calculate_engagement_rate( $comment->comment_ID, 'comment', $like_count, $dislike_count, $period );
					$total_views = $engagement_data['total_views'];
					$engagement_rate = $engagement_data['engagement_rate'];

					// Calculate engagement growth (period-over-period)
					$engagement_growth = $engagement_rate > 0
						? $this->calculate_engagement_growth( $comment->comment_ID, 'comment', $like_count, $dislike_count, $period, $engagement_rate )
						: 0;

					// Build meta_data array conditionally
					$meta_data = [
						'Published'  => get_comment_date( '', $comment->comment_ID ),
						'By'         => esc_attr( $comment->comment_author ),
					];

					// Only include Views if it has a value
					if ( $total_views > 0 ) {
						$meta_data['Views'] = $total_views;
					}

					$tops_engagement = wp_ulike_pro_build_tops_engagement_payload(
						$metrics,
						$comment->comment_ID,
						'comment',
						$period,
						$engagement_rate,
						$engagement_growth
					);

					$result[] = [
						'id'             => $comment->comment_ID,
						'title'          => get_the_title($comment->comment_post_ID),
						'image'          => get_avatar_url( $comment->comment_author_email, [ 'size' => 100 ] ),
						'permalink'      => get_comment_link($comment->comment_ID),
						'likes_count'    => $like_count,
						'dislikes_count' => $dislike_count,
						'engaged_users'  => $engaged_users_info,
						'engager_count'  => wp_ulike_pro_count_item_unique_engagers( $comment->comment_ID, 'comment' ),
						'meta_data'      => $meta_data,
						'engagement'     => $tops_engagement['engagement'],
						'emoji_breakdown'=> $tops_engagement['emoji_breakdown'],
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
				"limit"      => 10,
				"search"     => '',
				"category"   => 0,
				"taxonomy"   => '',
				"engagement_keys" => array(),
				"values"          => array(),
			);
			// Parse args
			$settings = wp_parse_args( $args, $defaults );

			$topics = wp_ulike_pro_get_posts_query( $settings );

			$result = [];

			if($topics && $topics->have_posts()) {
				while($topics->have_posts()) {
					$topics->the_post();

					$topic_id = get_the_ID();
					$period   = $settings['period'] ?? 'all';
					$metrics  = wp_ulike_pro_get_tops_item_metrics( $topic_id, 'topic', $period, array(
						'engagement_keys' => (array) $settings['engagement_keys'],
						'values'          => (array) $settings['values'],
					) );

					$like_count    = $metrics['likes_count'];
					$dislike_count = $metrics['dislikes_count'];

					$engaged_users_info = wp_ulike_pro_build_tops_engaged_users_list(
						$topic_id,
						'topic',
						$metrics['mode'],
						'ulike_forums',
						'topic_id'
					);

					// Calculate engagement rate and views
					$engagement_data = $this->calculate_engagement_rate( $topic_id, 'topic', $like_count, $dislike_count, $period );
					$total_views = $engagement_data['total_views'];
					$engagement_rate = $engagement_data['engagement_rate'];

					// Calculate engagement growth (period-over-period)
					$engagement_growth = $engagement_rate > 0
						? $this->calculate_engagement_growth( $topic_id, 'topic', $like_count, $dislike_count, $period, $engagement_rate )
						: 0;

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

					$tops_engagement = wp_ulike_pro_build_tops_engagement_payload(
						$metrics,
						$topic_id,
						'topic',
						$period,
						$engagement_rate,
						$engagement_growth
					);

					$topic_title = 'topic' === get_post_type( $topic_id )
						? bbp_get_topic_title( $topic_id )
						: ( function_exists( 'bbp_get_reply_topic_title' )
							? bbp_get_reply_topic_title( $topic_id )
							: get_the_title( $topic_id ) );

					$result[] = [
						'id'             => $topic_id,
						'title'          => $topic_title,
						'image'          => $author_avatar,
						'permalink'      => 'topic' === get_post_type( $topic_id ) ? bbp_get_topic_permalink( $topic_id ) : bbp_get_reply_url( $topic_id ),
						'likes_count'    => $like_count,
						'dislikes_count' => $dislike_count,
						'engaged_users'  => $engaged_users_info,
						'engager_count'  => wp_ulike_pro_count_item_unique_engagers( $topic_id, 'topic' ),
						'meta_data'      => $meta_data,
						'engagement'     => $tops_engagement['engagement'],
						'emoji_breakdown'=> $tops_engagement['emoji_breakdown'],
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
				"limit"      => 10,
				"search"     => '',
				"engagement_keys" => array(),
				"values"          => array(),
			);
			// Parse args
			$settings = wp_parse_args( $args, $defaults );

			$activities = wp_ulike_pro_get_activity_query( $settings );

			$result = [];

			if( $activities ) {
				foreach ( $activities as $activity ) {
					$period  = $settings['period'] ?? 'all';
					$metrics = wp_ulike_pro_get_tops_item_metrics( $activity->id, 'activity', $period, array(
						'engagement_keys' => (array) $settings['engagement_keys'],
						'values'          => (array) $settings['values'],
					) );

					$like_count    = $metrics['likes_count'];
					$dislike_count = $metrics['dislikes_count'];

					$engaged_users_info = wp_ulike_pro_build_tops_engaged_users_list(
						$activity->id,
						'activity',
						$metrics['mode'],
						'ulike_activities',
						'activity_id'
					);

					$author = get_user_by( 'id', $activity->user_id );

					// Calculate engagement rate and views
					$period = $settings['period'] ?? 'all';
					$engagement_data = $this->calculate_engagement_rate( $activity->id, 'activity', $like_count, $dislike_count, $period );
					$total_views = $engagement_data['total_views'];
					$engagement_rate = $engagement_data['engagement_rate'];

					// Calculate engagement growth (period-over-period)
					$engagement_growth = $engagement_rate > 0
						? $this->calculate_engagement_growth( $activity->id, 'activity', $like_count, $dislike_count, $period, $engagement_rate )
						: 0;

					// Build meta_data array conditionally
					$meta_data = [
						'Published'  => wp_ulike_date_i18n( $activity->date_recorded ),
						'By'         => $author ? esc_attr( $author->display_name ) : esc_html__( 'Guest User', WP_ULIKE_PRO_DOMAIN ),
					];

					// Only include Views if it has a value
					if ( $total_views > 0 ) {
						$meta_data['Views'] = $total_views;
					}

					$tops_engagement = wp_ulike_pro_build_tops_engagement_payload(
						$metrics,
						$activity->id,
						'activity',
						$period,
						$engagement_rate,
						$engagement_growth
					);

					$result[] = [
						'id'             => $activity->id,
						'title'          => ! empty( $activity->content ) ? wp_strip_all_tags( $activity->content ) : wp_strip_all_tags( $activity->action ),
						'image'          => get_avatar_url( $author ? $author->user_email : '', [ 'size' => 100 ] ),
						'permalink'      => function_exists('bp_activity_get_permalink') ? bp_activity_get_permalink( $activity->id ) : '',
						'likes_count'    => $like_count,
						'dislikes_count' => $dislike_count,
						'engaged_users'  => $engaged_users_info,
						'engager_count'  => wp_ulike_pro_count_item_unique_engagers( $activity->id, 'activity' ),
						'meta_data'      => $meta_data,
						'engagement'     => $tops_engagement['engagement'],
						'emoji_breakdown'=> $tops_engagement['emoji_breakdown'],
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
			$search = trim( (string) ( $args['search'] ?? '' ) );
			$order  = isset( $args['order'] ) && 'ASC' === strtoupper( (string) $args['order'] ) ? 'ASC' : 'DESC';

			$pool_limit  = '' !== $search ? wp_ulike_pro_tops_search_pool_limit() : $limit;
			$pool_offset = '' !== $search ? 1 : $offset;

			$top_likers = wp_ulike_pro_get_top_combined_engagers(
				array(
					'limit'  => $pool_limit,
					'offset' => $pool_offset,
					'period' => $period,
					'search' => $search,
					'order'  => $order,
					'status' => $status,
				)
			);

			if ( '' !== $search ) {
				// Combined helper already search-filters when search is set; paginate only.
				$top_likers = wp_ulike_pro_paginate_tops_items( $top_likers, $offset, $limit );
			}

			$result     = [];

			if( ! empty( $top_likers ) ){
				foreach ( $top_likers as $user ) {
					$user_ID         = stripslashes( $user->user_id );
					$userdata        = get_userdata( $user_ID );
					$username        = empty( $userdata ) ? esc_html__('Guest User', WP_ULIKE_PRO_DOMAIN) : esc_attr( $userdata->display_name );
					$latest_activity = wp_ulike_pro_get_latest_user_activity_date( $user_ID );

					$row = [
						'id'               => $user_ID,
						'image'            => get_avatar_url( $user_ID, ['size' => 256] ),
						'title'            => $username,
						'permalink'        => get_edit_profile_url( $user_ID ),
						'last_activity'    => $latest_activity,
						'likes_count'      => absint( $user->likeCount ?? 0 ),
						'dislikes_count'   => absint( $user->dislikeCount ?? 0 ),
						'unlikes_count'    => absint( $user->unlikeCount ?? 0 ),
						'undislikes_count' => absint( $user->undislikeCount ?? 0 ),
						'emoji_reactions_count' => absint( $user->emoji_count ?? 0 ),
						'star_ratings_count'    => absint( $user->star_count ?? 0 ),
						'engagement_total'      => absint( $user->total_count ?? 0 ),
					];

					if ( ( $row['emoji_reactions_count'] + $row['star_ratings_count'] ) > 0 ) {
						$row['mode'] = 'engagement';
					}

					$result[] = $row;
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

					$filtered_like_count = wp_ulike_pro_get_counter_value( $item_id, $type, 'like', $is_distinct, $period_array );
					$filtered_dislike_count = wp_ulike_pro_get_counter_value( $item_id, $type, 'dislike', $is_distinct, $period_array );
				}

			// Engagement rate = (all engagements) / Views * 100. Count classic
			// votes (like + dislike) + emoji + star regardless of the type's
			// current template mode so display automation and historical data
			// are reflected. Note: can exceed 100% for viral content.
			$total_engagements = $filtered_like_count + $filtered_dislike_count;

			if ( function_exists( 'wp_ulike_pro_count_engagement_activity' ) ) {
				foreach ( array( 'emoji', 'star' ) as $kind ) {
					if ( $view_period_start || is_array( $period ) ) {
						$range_start = is_array( $period ) && isset( $period['start'] ) ? $period['start'] : $effective_start_date;
						$range_end   = is_array( $period ) && isset( $period['end'] ) ? $period['end'] : $effective_end_date;
						$total_engagements += wp_ulike_pro_count_engagement_activity_for_range( $item_id, $type, $kind, $range_start, $range_end );
					} else {
						$total_engagements += wp_ulike_pro_count_engagement_activity( $item_id, $type, $kind, $period );
					}
				}
			}

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
		 * Site-wide marketing intelligence for the overview dashboard.
		 *
		 * @return array
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

		/**
		 * Emoji / star engagement stats from ulike_pulse table.
		 *
		 * @param string $type posts|comments|activities|topics
		 * @return array|null
		 */
}

