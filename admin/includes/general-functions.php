<?php
/**
 * Admin Function
 *
 *
 * @package    wp-ulike-pro
 * @author     TechnoWich 2026
 * @link       https://wpulike.com
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die('No Naughty Business Please !');
}

/**
 * Check license activation status
 *
 * @return void
 */
function wp_ulike_pro_is_activated(){
    return WP_Ulike_Pro_API::is_license_active();
}

/**
 * Get license info from options table
 *
 * @return array|null
 */
function wp_ulike_pro_get_license_info(){
    return WP_Ulike_Pro_License::get_license_key();
}

/**
 * Update "flush" option for reset rules on wp_loaded hook
 *
 * @return void
 */
function wp_ulike_pro_reset_rules() {
    update_option( 'wp_ulike_pro_flush_rewrite_rules', 1 );
}


/**
 * Bulk counter config per content type (for Tools > Bulk Actions).
 *
 * @return array<string,array>
 */
function wp_ulike_pro_get_bulk_engagement_config() {
	$types  = array( 'post', 'comment', 'activity', 'topic' );
	$config = array();

	foreach ( $types as $item_type ) {
		$settings_mode = wp_ulike_pro_get_bulk_counter_mode( $item_type );
		$entry         = array(
			'mode'          => $settings_mode,
			'settings_mode' => $settings_mode,
			'label'         => ucfirst( $item_type ),
			'modes'         => array(
				'vote'  => array(
					'label' => __( 'Votes', WP_ULIKE_PRO_DOMAIN ),
				),
				'emoji' => array(
					'label' => __( 'Emoji', WP_ULIKE_PRO_DOMAIN ),
				),
				'star'  => array(
					'label' => __( 'Stars', WP_ULIKE_PRO_DOMAIN ),
				),
			),
		);

		if ( class_exists( 'WP_Ulike_Pro_Engagement_Registry' ) ) {
			$reactions = WP_Ulike_Pro_Engagement_Registry::get_enabled_reactions( $item_type );
			$entry['reactions'] = array();
			$entry['modes']['emoji']['reactions'] = array();

			foreach ( $reactions as $slug => $reaction ) {
				$reaction_row = array(
					'slug'  => $slug,
					'emoji' => $reaction['emoji'],
					'label' => wp_strip_all_tags( $reaction['label'] ),
				);
				$entry['reactions'][]                   = $reaction_row;
				$entry['modes']['emoji']['reactions'][] = $reaction_row;
			}

			$star_config                         = WP_Ulike_Pro_Engagement_Registry::get_star_config( $item_type );
			$entry['star_max']                   = max( 1, (int) $star_config['max'] );
			$entry['modes']['star']['star_max']  = $entry['star_max'];
		}

		$config[ $item_type ] = $entry;
	}

	return apply_filters( 'wp_ulike_pro_bulk_engagement_config', $config );
}

/**
 * Resolve bulk counter mode for a content type.
 *
 * @param string $item_type Content type slug.
 * @return string vote|emoji|star
 */
function wp_ulike_pro_get_bulk_counter_mode( $item_type ) {
	$mode = function_exists( 'wp_ulike_pro_get_engagement_mode_for_type' )
		? wp_ulike_pro_get_engagement_mode_for_type( $item_type )
		: 'none';

	if ( 'emoji' === $mode || 'star' === $mode ) {
		return $mode;
	}

	return 'vote';
}

/**
 * Map bulk item type to display automation rule content types.
 *
 * @param string $item_type Content type slug.
 * @return string[]
 */
function wp_ulike_pro_bulk_get_rule_content_types_for_item( $item_type ) {
	$map = array(
		'post'     => array( 'post' ),
		'comment'  => array( 'comment', 'product_review' ),
		'activity' => array( 'activity' ),
		'topic'    => array( 'topic' ),
	);

	return isset( $map[ $item_type ] ) ? $map[ $item_type ] : array( 'post' );
}

/**
 * Whether a display automation rule matches a specific item in admin bulk tools.
 *
 * @param array  $rule      Rule data.
 * @param int    $item_id   Item ID.
 * @param string $item_type Content type slug.
 * @return bool
 */
function wp_ulike_pro_bulk_rule_matches_item( $rule, $item_id, $item_type ) {
	$item_id   = absint( $item_id );
	$item_type = sanitize_key( $item_type );

	if ( ! $item_id ) {
		return false;
	}

	$content_type = $rule['content_type'] ?? 'post';
	$allowed      = wp_ulike_pro_bulk_get_rule_content_types_for_item( $item_type );

	if ( ! in_array( $content_type, $allowed, true ) ) {
		return false;
	}

	$conditions        = isset( $rule['conditions'] ) && is_array( $rule['conditions'] ) ? $rule['conditions'] : array();
	$post_id_for_terms = $item_id;

	if ( in_array( $item_type, array( 'comment' ), true ) ) {
		$comment = get_comment( $item_id );
		if ( ! $comment ) {
			return false;
		}

		if ( 'product_review' === $content_type ) {
			if ( 'review' !== get_comment_type( $comment ) || 'product' !== get_post_type( (int) $comment->comment_post_ID ) ) {
				return false;
			}
		} elseif ( 'comment' === $content_type && 'review' === get_comment_type( $comment ) ) {
			return false;
		}

		$post_id_for_terms = (int) $comment->comment_post_ID;
	}

	$post_types = isset( $conditions['post_types'] ) && is_array( $conditions['post_types'] ) ? $conditions['post_types'] : array();

	if ( 'post' === $item_type ) {
		$post = get_post( $item_id );
		if ( ! $post ) {
			return false;
		}

		if ( ! empty( $post_types ) && ! in_array( $post->post_type, $post_types, true ) ) {
			return false;
		}
	} elseif ( in_array( $item_type, array( 'comment' ), true ) && ! empty( $post_types ) ) {
		$parent_type = get_post_type( $post_id_for_terms );
		if ( ! $parent_type || ! in_array( $parent_type, $post_types, true ) ) {
			return false;
		}
	}

	$term_ids = isset( $conditions['term_ids'] ) ? array_map( 'absint', (array) $conditions['term_ids'] ) : array();
	if ( ! empty( $term_ids ) && in_array( $item_type, array( 'post', 'comment' ), true ) ) {
		$taxonomy = ! empty( $conditions['taxonomy'] ) ? sanitize_key( $conditions['taxonomy'] ) : '';

		if ( $taxonomy ) {
			if ( ! has_term( $term_ids, $taxonomy, $post_id_for_terms ) ) {
				return false;
			}
		} elseif ( ! has_term( $term_ids, '', $post_id_for_terms ) ) {
			return false;
		}
	}

	if ( 'post' === $item_type && class_exists( 'WP_Ulike_Pro_WooCommerce' ) ) {
		if ( ! WP_Ulike_Pro_WooCommerce::matches_product_conditions( $conditions, $item_id ) ) {
			return false;
		}
	}

	if ( 'topic' === $item_type && class_exists( 'WP_Ulike_Pro_BbPress' ) ) {
		if ( ! WP_Ulike_Pro_BbPress::matches_conditions( $conditions, $item_id ) ) {
			return false;
		}
	}

	return true;
}

/**
 * Suggested counter mode from display automation for a specific item.
 *
 * @param int    $item_id   Item ID.
 * @param string $item_type Content type slug.
 * @return array{mode:string,source:string}
 */
function wp_ulike_pro_bulk_get_item_counter_suggestion( $item_id, $item_type ) {
	$settings_mode = wp_ulike_pro_get_bulk_counter_mode( $item_type );
	$suggestion    = array(
		'mode'   => $settings_mode,
		'source' => 'settings',
	);

	if ( ! class_exists( 'WP_Ulike_Pro_Display_Automation' ) || ! class_exists( 'WP_Ulike_Pro_Engagement_Settings' ) ) {
		return $suggestion;
	}

	$rules = WP_Ulike_Pro_Display_Automation::get_rules();
	$map   = WP_Ulike_Pro_Engagement_Settings::get_engagement_template_map();

	usort(
		$rules,
		static function ( $a, $b ) {
			return (int) ( $a['priority'] ?? 10 ) <=> (int) ( $b['priority'] ?? 10 );
		}
	);

	foreach ( $rules as $rule ) {
		if ( empty( $rule['enabled'] ) ) {
			continue;
		}

		if ( ! wp_ulike_pro_bulk_rule_matches_item( $rule, $item_id, $item_type ) ) {
			continue;
		}

		$template = ! empty( $rule['template'] ) ? sanitize_key( (string) $rule['template'] ) : '';

		if ( $template && isset( $map[ $template ] ) ) {
			return array(
				'mode'   => $map[ $template ],
				'source' => 'automation',
			);
		}

		if ( $template ) {
			return array(
				'mode'   => 'vote',
				'source' => 'automation',
			);
		}
	}

	return $suggestion;
}

/**
 * Read vote counters for bulk tools.
 *
 * @param int    $item_id   Item ID.
 * @param string $item_type Content type slug.
 * @return array{likes:int,dislikes:int,has_data:bool}
 */
function wp_ulike_pro_bulk_get_vote_counters( $item_id, $item_type ) {
	$is_distinct     = wp_ulike_setting_repo::isDistinct( $item_type );
	$meta_key_prefix = $is_distinct ? 'count_distinct_' : 'count_total_';
	$likes           = wp_ulike_get_meta_data( $item_id, $item_type, $meta_key_prefix . 'like', true );
	$dislikes        = wp_ulike_get_meta_data( $item_id, $item_type, $meta_key_prefix . 'dislike', true );
	$likes           = ! empty( $likes ) ? (int) $likes : 0;
	$dislikes        = ! empty( $dislikes ) ? (int) $dislikes : 0;

	return array(
		'likes'    => $likes,
		'dislikes' => $dislikes,
		'has_data' => ( $likes + $dislikes ) > 0,
	);
}

/**
 * Read emoji counters for bulk tools.
 *
 * @param int    $item_id   Item ID.
 * @param string $item_type Content type slug.
 * @return array{reactions:array<string,int>,total:int,has_data:bool}
 */
function wp_ulike_pro_bulk_get_emoji_counters( $item_id, $item_type ) {
	if ( ! class_exists( 'WP_Ulike_Pro_Engagement_Counter' ) || ! class_exists( 'WP_Ulike_Pro_Engagement_Registry' ) ) {
		return array(
			'reactions' => array(),
			'total'     => 0,
			'has_data'  => false,
		);
	}

	$counts  = WP_Ulike_Pro_Engagement_Counter::get_all_reaction_counts( $item_id, $item_type );
	$total   = array_sum( $counts );
	$allowed = WP_Ulike_Pro_Engagement_Registry::get_enabled_reactions( $item_type );

	foreach ( array_keys( $allowed ) as $slug ) {
		if ( ! isset( $counts[ $slug ] ) ) {
			$counts[ $slug ] = 0;
		}
	}

	return array(
		'reactions' => $counts,
		'total'     => $total,
		'has_data'  => $total > 0,
	);
}

/**
 * Read star counters for bulk tools.
 *
 * @param int    $item_id   Item ID.
 * @param string $item_type Content type slug.
 * @return array{count:int,average:float,star_max:int,has_data:bool}
 */
function wp_ulike_pro_bulk_get_star_counters( $item_id, $item_type ) {
	$config = wp_ulike_pro_get_bulk_engagement_config();
	$star_max = isset( $config[ $item_type ]['star_max'] ) ? (int) $config[ $item_type ]['star_max'] : 5;

	if ( ! class_exists( 'WP_Ulike_Pro_Engagement_Counter' ) ) {
		return array(
			'count'    => 0,
			'average'  => 0.0,
			'star_max' => $star_max,
			'has_data' => false,
		);
	}

	$aggregates = WP_Ulike_Pro_Engagement_Counter::get_star_aggregates( $item_id, $item_type );
	$count      = (int) $aggregates['count'];

	return array(
		'count'    => $count,
		'average'  => (float) $aggregates['average'],
		'star_max' => $star_max,
		'has_data' => $count > 0,
	);
}

/**
 * Counter payload for a single item (Bulk Actions).
 *
 * @param int    $item_id   Item ID.
 * @param string $item_type Content type slug.
 * @return array
 */
function wp_ulike_pro_get_bulk_item_counter_payload( $item_id, $item_type ) {
	$item_id    = absint( $item_id );
	$item_type  = sanitize_key( $item_type );
	$suggestion = wp_ulike_pro_bulk_get_item_counter_suggestion( $item_id, $item_type );
	$vote       = wp_ulike_pro_bulk_get_vote_counters( $item_id, $item_type );
	$emoji      = wp_ulike_pro_bulk_get_emoji_counters( $item_id, $item_type );
	$star       = wp_ulike_pro_bulk_get_star_counters( $item_id, $item_type );

	$payload = array(
		'counter_mode'   => $suggestion['mode'],
		'settings_mode'  => wp_ulike_pro_get_bulk_counter_mode( $item_type ),
		'suggested_mode' => $suggestion['mode'],
		'source'         => $suggestion['source'],
		'modes'          => array(
			'vote'  => $vote,
			'emoji' => $emoji,
			'star'  => $star,
		),
	);

	// Backward-compatible top-level keys.
	$payload['likes']    = $vote['likes'];
	$payload['dislikes'] = $vote['dislikes'];
	$payload['reactions'] = $emoji['reactions'];
	$payload['total']     = $emoji['total'];
	$payload['star']      = array(
		'count'   => $star['count'],
		'average' => $star['average'],
	);
	$payload['star_max'] = $star['star_max'];

	return $payload;
}

/**
 * Validate item exists for bulk counter updates.
 *
 * @param int    $item_id   Item ID.
 * @param string $item_type Content type slug.
 * @return true|WP_Error
 */
function wp_ulike_pro_validate_bulk_item( $item_id, $item_type ) {
	$allowed_types = array( 'post', 'comment', 'activity', 'topic' );

	if ( ! in_array( $item_type, $allowed_types, true ) ) {
		return new WP_Error(
			'invalid_type',
			sprintf(
				/* translators: %d: item ID */
				esc_html__( 'Invalid type for item ID %d.', WP_ULIKE_PRO_DOMAIN ),
				$item_id
			)
		);
	}

	if ( 'post' === $item_type ) {
		if ( get_post( $item_id ) ) {
			return true;
		}

		global $wpdb;
		$meta_table  = $wpdb->prefix . 'ulike_meta';
		$meta_exists = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM `" . esc_sql( $meta_table ) . "` WHERE `item_id` = %d AND `meta_group` = %s",
				$item_id,
				$item_type
			)
		);

		if ( ! $meta_exists ) {
			return new WP_Error(
				'missing_item',
				sprintf(
					/* translators: 1: item ID, 2: content type */
					esc_html__( 'Item ID %1$d (type: %2$s) does not exist.', WP_ULIKE_PRO_DOMAIN ),
					$item_id,
					$item_type
				)
			);
		}
	} elseif ( 'comment' === $item_type ) {
		if ( get_comment( $item_id ) ) {
			return true;
		}

		global $wpdb;
		$meta_table  = $wpdb->prefix . 'ulike_meta';
		$meta_exists = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM `" . esc_sql( $meta_table ) . "` WHERE `item_id` = %d AND `meta_group` = %s",
				$item_id,
				$item_type
			)
		);

		if ( ! $meta_exists ) {
			return new WP_Error(
				'missing_item',
				sprintf(
					/* translators: 1: item ID, 2: content type */
					esc_html__( 'Item ID %1$d (type: %2$s) does not exist.', WP_ULIKE_PRO_DOMAIN ),
					$item_id,
					$item_type
				)
			);
		}
	}

	return true;
}

/**
 * Bulk update likes/dislikes for posts
 *
 * @param array $posts_data Array of post data with id, likes, dislikes
 * @return array|false
 */
function wp_ulike_pro_bulk_update_likes( $posts_data ) {
	global $wpdb;

	if ( ! current_user_can( 'manage_options' ) ) {
		return false;
	}

	if ( ! is_array( $posts_data ) || empty( $posts_data ) ) {
		return array(
			'success' => false,
			'message' => esc_html__( 'Invalid posts data provided.', WP_ULIKE_PRO_DOMAIN ),
		);
	}

	$processed = 0;
	$errors    = array();

	foreach ( $posts_data as $post_data ) {
		if ( ! isset( $post_data['id'] ) ) {
			continue;
		}

		$item_id   = absint( $post_data['id'] );
		$item_type = isset( $post_data['type'] ) ? sanitize_key( $post_data['type'] ) : 'post';
		$mode      = isset( $post_data['counter_mode'] )
			? sanitize_key( $post_data['counter_mode'] )
			: wp_ulike_pro_get_bulk_counter_mode( $item_type );

		$validation = wp_ulike_pro_validate_bulk_item( $item_id, $item_type );
		if ( is_wp_error( $validation ) ) {
			$errors[] = $validation->get_error_message();
			continue;
		}

		if ( 'emoji' === $mode ) {
			if ( empty( $post_data['reactions'] ) || ! is_array( $post_data['reactions'] ) || ! class_exists( 'WP_Ulike_Pro_Engagement_Counter' ) ) {
				$errors[] = sprintf(
					/* translators: %d: item ID */
					esc_html__( 'Missing reaction counts for item ID %d.', WP_ULIKE_PRO_DOMAIN ),
					$item_id
				);
				continue;
			}

			$allowed = WP_Ulike_Pro_Engagement_Registry::get_enabled_reactions( $item_type );
			foreach ( $post_data['reactions'] as $slug => $count ) {
				$slug = sanitize_key( $slug );
				if ( ! isset( $allowed[ $slug ] ) ) {
					continue;
				}
				WP_Ulike_Pro_Engagement_Counter::set_reaction_count( $item_id, $item_type, $slug, absint( $count ) );
			}

			++$processed;
			continue;
		}

		if ( 'star' === $mode ) {
			if ( ! class_exists( 'WP_Ulike_Pro_Engagement_Counter' ) ) {
				continue;
			}

			$star_count   = isset( $post_data['star_count'] ) ? absint( $post_data['star_count'] ) : 0;
			$star_average = isset( $post_data['star_average'] ) ? (float) $post_data['star_average'] : 0;

			WP_Ulike_Pro_Engagement_Counter::set_star_aggregates( $item_id, $item_type, $star_count, $star_average );
			++$processed;
			continue;
		}

		$new_likes    = isset( $post_data['likes'] ) ? absint( $post_data['likes'] ) : 0;
		$new_dislikes = isset( $post_data['dislikes'] ) ? absint( $post_data['dislikes'] ) : 0;

		$is_distinct     = wp_ulike_setting_repo::isDistinct( $item_type );
		$meta_key_prefix = $is_distinct ? 'count_distinct_' : 'count_total_';

		wp_ulike_update_meta_data( $item_id, $item_type, $meta_key_prefix . 'like', $new_likes );
		wp_ulike_update_meta_data( $item_id, $item_type, $meta_key_prefix . 'dislike', $new_dislikes );

		if ( in_array( $item_type, array( 'post', 'comment' ), true ) ) {
			if ( 'post' === $item_type && get_post( $item_id ) ) {
				update_metadata( $item_type, $item_id, 'like_amount', $new_likes );
				update_metadata( $item_type, $item_id, 'dislike_amount', $new_dislikes );
				update_metadata( $item_type, $item_id, 'net_votes', ( $new_likes - $new_dislikes ) );
			} elseif ( 'comment' === $item_type && get_comment( $item_id ) ) {
				update_metadata( $item_type, $item_id, 'like_amount', $new_likes );
				update_metadata( $item_type, $item_id, 'dislike_amount', $new_dislikes );
				update_metadata( $item_type, $item_id, 'net_votes', ( $new_likes - $new_dislikes ) );
			}
		}

		$current_likers = wp_ulike_get_meta_data( $item_id, $item_type, 'likers_list', true );
		$likers_list    = is_array( $current_likers ) ? $current_likers : array();
		wp_ulike_update_meta_data( $item_id, $item_type, 'likers_list', $likers_list );

		++$processed;
	}

	$message = sprintf(
		/* translators: %d: number of updated items */
		esc_html__( 'Successfully updated %d item(s).', WP_ULIKE_PRO_DOMAIN ),
		$processed
	);

	if ( ! empty( $errors ) ) {
		$message .= ' ' . esc_html__( 'Errors:', WP_ULIKE_PRO_DOMAIN ) . ' ' . implode( ', ', $errors );
	}

	return array(
		'success'       => true,
		'rows_affected' => $processed,
		'message'       => $message,
		'errors'        => $errors,
	);
}

/**
 * Bulk add likes/dislikes to posts
 *
 * @param array $post_ids Array of post IDs
 * @param int $likes Number of likes to add
 * @param int $dislikes Number of dislikes to add
 * @return array|false
 */
function wp_ulike_pro_bulk_add_likes( $post_ids, $likes, $dislikes ) {
	global $wpdb;

	if ( ! current_user_can( 'manage_options' ) ) {
		return false;
	}

	// Pulse/dual write path stores the ledger in ulike_pulse. Meta-only bumps
	// would diverge from logs, stats, and recounts — refuse until a real
	// pulse writer path exists for synthetic votes.
	if ( function_exists( 'wp_ulike_writes_pulse' ) && wp_ulike_writes_pulse() ) {
		return array(
			'success' => false,
			'message' => esc_html__( 'Bulk add likes cannot adjust counters only while Pulse storage is active. That would desync logs and statistics. Insert real votes or use legacy mode.', WP_ULIKE_PRO_DOMAIN ),
		);
	}

	// Sanitize and validate post IDs
	if ( ! is_array( $post_ids ) || empty( $post_ids ) ) {
		return array(
			'success' => false,
			'message' => esc_html__( 'Invalid post IDs provided.', WP_ULIKE_PRO_DOMAIN )
		);
	}

	$post_ids = array_map( 'absint', $post_ids );
	$post_ids = array_filter( $post_ids, function( $id ) {
		return $id > 0;
	});

	if ( empty( $post_ids ) ) {
		return array(
			'success' => false,
			'message' => esc_html__( 'No valid post IDs provided.', WP_ULIKE_PRO_DOMAIN )
		);
	}

	$likes = absint( $likes );
	$dislikes = absint( $dislikes );

	if ( $likes === 0 && $dislikes === 0 ) {
		return array(
			'success' => false,
			'message' => esc_html__( 'Please enter at least one like or dislike count.', WP_ULIKE_PRO_DOMAIN )
		);
	}

	$type = 'post';
	$processed = 0;
	$errors = array();

	// Check if distinct mode is enabled
	$is_distinct = wp_ulike_setting_repo::isDistinct( $type );
	$meta_key_prefix = $is_distinct ? 'count_distinct_' : 'count_total_';

	// Get current time for date_time
	$current_time = current_time( 'mysql' );

	foreach ( $post_ids as $post_id ) {
		// Verify post exists
		if ( ! get_post( $post_id ) ) {
			$errors[] = sprintf( esc_html__( 'Post ID %d does not exist.', WP_ULIKE_PRO_DOMAIN ), $post_id );
			continue;
		}

		// Get current counts from meta
		$current_like_meta = wp_ulike_get_meta_data( $post_id, $type, $meta_key_prefix . 'like', true );
		$current_dislike_meta = wp_ulike_get_meta_data( $post_id, $type, $meta_key_prefix . 'dislike', true );

		$current_likes = ! empty( $current_like_meta ) ? (int) $current_like_meta : 0;
		$current_dislikes = ! empty( $current_dislike_meta ) ? (int) $current_dislike_meta : 0;

		// Calculate new totals
		$new_likes = $current_likes + $likes;
		$new_dislikes = $current_dislikes + $dislikes;

		// Update meta counters
		wp_ulike_update_meta_data( $post_id, $type, $meta_key_prefix . 'like', $new_likes );
		wp_ulike_update_meta_data( $post_id, $type, $meta_key_prefix . 'dislike', $new_dislikes );

		// Update post meta
		update_metadata( $type, $post_id, 'like_amount', $new_likes );
		update_metadata( $type, $post_id, 'dislike_amount', $new_dislikes );
		update_metadata( $type, $post_id, 'net_votes', ( $new_likes - $new_dislikes ) );

		// Update likers_list if needed (for likes only)
		if ( $likes > 0 ) {
			$current_likers = wp_ulike_get_meta_data( $post_id, $type, 'likers_list', true );
			$likers_list = is_array( $current_likers ) ? $current_likers : array();

			// Add placeholder user IDs (0) for bulk likes - or you could use a specific user ID
			// For now, we'll just update the count without adding actual user IDs to likers_list
			// since this is for initial setup
			wp_ulike_update_meta_data( $post_id, $type, 'likers_list', $likers_list );
		}

		$processed++;
	}

	$message = sprintf(
		esc_html__( 'Successfully added %d likes and %d dislikes to %d post(s).', WP_ULIKE_PRO_DOMAIN ),
		$likes,
		$dislikes,
		$processed
	);

	if ( ! empty( $errors ) ) {
		$message .= ' ' . esc_html__( 'Errors:', WP_ULIKE_PRO_DOMAIN ) . ' ' . implode( ', ', $errors );
	}

	return array(
		'success' => true,
		'rows_affected' => $processed,
		'message' => $message,
		'errors' => $errors
	);
}

/**
 * Get date range of tops
 *
 * @param string $type
 * @return string
 */
function wp_ulike_pro_get_daterange_of_tops( $type ){
	$period = wp_ulike_get_transient( 'wp_ulike_pro_daterange_of_top_' . $type );
	return is_array( $period ) ? implode( ' - ', $period ) : esc_html__( 'All Times', WP_ULIKE_PRO_DOMAIN );
}

/**
 * Convert array to csv
 *
 * @param array $data
 * @param string $filename
 * @return void
 */
function wp_ulike_pro_produce_csv( $results, $filename = "export.csv" ) {

    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header('Content-Description: File Transfer');
    header("Content-type: text/csv");
    header("Content-Disposition: attachment; filename={$filename}");
    header("Expires: 0");
    header("Pragma: public");

    $fh = @fopen( 'php://output', 'w' );

    $headerDisplayed = false;

    foreach ( $results as $data ) {
        // Add a header row if it hasn't been added yet
        if ( !$headerDisplayed ) {
            // Use the keys from $data as the titles
            fputcsv($fh, array_keys($data));
            $headerDisplayed = true;
        }

        // Put the data into the stream
        fputcsv($fh, $data);
    }
    // Close the file
    fclose($fh);
    // Make sure nothing else is sent, our file is done
    exit;

}

/**
 * Search for attachments
 *
 * @param string $term (default: '') Term to search for.
 * @param bool   $include_variations in search or not.
 */
function wp_ulike_pro_search_attachments( $term = '', $include_variations = false ) {

	if ( empty( $term ) && isset( $_REQUEST['term'] ) ) {
		$term = (string) wp_unslash( $_REQUEST['term'] );
	}

	if ( empty( $term ) ) {
		return array();
	}

    $query = array(
        's'           => $term,
        'post_type'   => 'attachment',
        'post_status' => 'inherit'
    );

    if ( current_user_can( get_post_type_object( 'attachment' )->cap->read_private_posts ) ) {
        $query['post_status'] .= ',private';
    }

    $query = new WP_Query( $query );
    $attachments = array();

    // Check that we have query results.
    if ( $query->have_posts() ) {
        // Start looping over the query results.
        while ( $query->have_posts() ) {
            $query->the_post();
            $attachments[ get_the_ID() ] = rawurldecode( wp_strip_all_tags( sprintf( '%s [ ID : %s ]', get_the_title(), get_the_ID() ) ) );
        }
        // Restore original post data.
        wp_reset_postdata();
    }

	return $attachments;
}

/**
 * Search for attachment title
 *
 * @param integer $id
 * @return string
 */
function wp_ulike_pro_search_attachments_title( $id ) {
    // Get attachment info
	$post = get_post( $id );
    // Return title
    return ! empty( $post ) ? rawurldecode( wp_strip_all_tags( sprintf( '%s [ ID : %s ]', $post->post_title, $post->ID ) ) ) : $id;
}

/**
 * Get admin avatar box html wrapper
 *
 * @return void
 */
function wp_ulike_pro_admin_avatar_box_callback(){
    echo sprintf( '
        <div class="ulf-title">
            <h4>%s</h4>
        </div>
        <div class="ulf-fieldset">
            <div class="ulf--wrap">%s</div>
            <div class="clear"></div>
        </div>',
        esc_html__('Upload Avatar', WP_ULIKE_PRO_DOMAIN),
        WP_Ulike_Pro_Avatar::get_avatar_uploader()
    );
}

/**
 * Get admin logs columns for vuejs
 *
 * @param string $type
 * @return array
 */
function wp_ulike_pro_get_admin_logs_columns( $type ){
    // Output
    $output = array();

    switch ($type) {
        case 'comment':
            $output = array(
                array(
                    'label' => 'ID',
                    'field' => 'id',
                    'type' => 'number'
                ),
                array(
                    'label' => 'Username',
                    'field' => 'user_id',
                    'html'  => true
                ),
                array(
                    'label' => 'Comment ID',
                    'field' => 'comment_id'
                ),
                array(
                    'label'    => 'Comment Author',
                    'field'    => 'comment_author',
                    'sortable' => false
                ),
                array(
                    'label'    => 'Comment Content',
                    'field'    => 'comment_content',
                    'html'     => true,
                    'sortable' => false
                ),
                array(
                    'label' => 'Status',
                    'field' => 'status'
                ),
                array(
                    'label' => 'Date/Time',
                    'field' => 'date_time'
                ),
                array(
                    'label' => 'IP',
                    'field' => 'ip',
                    'type' => 'number'
                )
            );
            break;

        case 'topic':
            $output = array(
                array(
                    'label' => 'ID',
                    'field' => 'id',
                    'type' => 'number'
                ),
                array(
                    'label' => 'Username',
                    'field' => 'user_id',
                    'html'  => true
                ),
                array(
                    'label' => 'Topic ID',
                    'field' => 'topic_id'
                ),
                array(
                    'label'    => 'Topic Title',
                    'field'    => 'topic_title',
                    'html'     => true,
                    'sortable' => false,
                ),
                array(
                    'label' => 'Status',
                    'field' => 'status'
                ),
                array(
                    'label' => 'Date/Time',
                    'field' => 'date_time'
                ),
                array(
                    'label' => 'IP',
                    'field' => 'ip',
                    'type' => 'number'
                )
            );
            break;

        case 'activity':
            $output = array(
                array(
                    'label' => 'ID',
                    'field' => 'id',
                    'type' => 'number'
                ),
                array(
                    'label' => 'Username',
                    'field' => 'user_id',
                    'html'  => true
                ),
                array(
                    'label' => 'Activity ID',
                    'field' => 'activity_id'
                ),
                array(
                    'label' => 'Activity Title',
                    'field' => 'activity_title',
                    'html'  => true
                ),
                array(
                    'label' => 'Status',
                    'field' => 'status'
                ),
                array(
                    'label' => 'Date/Time',
                    'field' => 'date_time'
                ),
                array(
                    'label' => 'IP',
                    'field' => 'ip',
                    'type' => 'number'
                )
            );
            break;

        default:
            $output = array(
                array(
                    'label' => 'ID',
                    'field' => 'id',
                    'type' => 'number'
                ),
                array(
                    'label' => 'Username',
                    'field' => 'user_id',
                    'html'  => true
                ),
                array(
                    'label' => 'Post ID/Title',
                    'field' => 'post_title',
                    'html'  => true
                ),
                array(
                    'label'    => 'Post Type',
                    'field'    => 'post_type',
                    'html'     => true,
                    'sortable' => false
                ),
                array(
                    'label'    => 'Category',
                    'field'    => 'category',
                    'html'     => true,
                    'sortable' => false
                ),
                array(
                    'label' => 'Status',
                    'field' => 'status'
                ),
                array(
                    'label' => 'Date/Time',
                    'field' => 'date_time'
                ),
                array(
                    'label' => 'IP',
                    'field' => 'ip',
                    'type' => 'number'
                )
            );
            break;
    }

    return apply_filters( 'wp_ulike_pro_admin_logs_columns', $output, $type );
}

/**
 * Get installed core pages list
 *
 * @return array
 */
function wp_ulike_pro_get_installed_core_pages(){
    $installed_pages = array();

    $core_pages = get_posts( array(
		'post_type'      => 'page',
		'posts_per_page' => -1,
		'meta_query'     => array(
			array(
				'key'     => '_wp_ulike_pro_core',
				'compare' => 'EXISTS',
			)
		)
	) );

	if ( $core_pages ) {
        foreach ( $core_pages as $core_page ) {
            $installed_pages[$core_page->ID] = $core_page->post_name;
        }
	}

    return $installed_pages;
}

