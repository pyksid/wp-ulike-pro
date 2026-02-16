<?php
/**
 * Back-end AJAX Functionalities
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

/*******************************************************
  Start AJAX From Here
*******************************************************/

/**
 * Generate API Keys
 *
 * @return void
 */
function wp_ulike_pro_generate_api_key() {

	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'wp_ulike_generate_api_keys' ) || ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array(
			'success' 	=> 0,
			'status'    => 'error',
			'message' 	=> esc_html__( 'Something wrong happened!', WP_ULIKE_PRO_DOMAIN ),
		) );
	}

	$get_keys = get_option( 'wp_ulike_rest_api_keys', array() );
	
	// Ensure it's an array
	if ( ! is_array( $get_keys ) ) {
		$get_keys = array();
	}
	
	// Generate new key - store token in variable to ensure consistency
	$token = wp_generate_password( 120, false );
	$date  = current_time( 'mysql', true );
	
	// Create key array with exact token
	$new_key = array(
		'token' => $token,
		'date'  => $date
	);
	
	// Add to array (newest at the end)
	$get_keys[] = $new_key;
	
	// Save to database
	update_option( 'wp_ulike_rest_api_keys', $get_keys );

	// Return the exact same token variable we generated and saved
	// This ensures JavaScript receives the identical token that's in the database
	wp_send_json_success( array(
		'success' => 1,
		'status'  => 'success',
		'message' => esc_html__( 'API key successfully generated.', WP_ULIKE_PRO_DOMAIN ),
		'key'     => array(
			'token' => $token, // Exact token from variable, not from array
			'date'  => $date
		)
	) );

}
add_action( 'wp_ajax_wp_ulike_generate_api_key', 'wp_ulike_pro_generate_api_key' );

/**
 * Delete API Key
 *
 * @return void
 */
function wp_ulike_pro_delete_api_key() {

	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'wp_ulike_generate_api_keys' ) || ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array(
			'success' 	=> 0,
			'status'    => 'error',
			'message' 	=> esc_html__( 'Something wrong happened!', WP_ULIKE_PRO_DOMAIN ),
		) );
	}

	if ( ! isset( $_POST['token'] ) || empty( $_POST['token'] ) ) {
		wp_send_json_error( array(
			'success' 	=> 0,
			'status'    => 'error',
			'message' 	=> esc_html__( 'Token is required.', WP_ULIKE_PRO_DOMAIN ),
		) );
	}

	$token = sanitize_text_field( $_POST['token'] );
	$token = trim( $token );
	
	if ( empty( $token ) ) {
		wp_send_json_error( array(
			'success' 	=> 0,
			'status'    => 'error',
			'message' 	=> esc_html__( 'Token is required.', WP_ULIKE_PRO_DOMAIN ),
		) );
	}
	
	$get_keys = get_option( 'wp_ulike_rest_api_keys', array() );

	if ( ! is_array( $get_keys ) || empty( $get_keys ) ) {
		wp_send_json_error( array(
			'success' 	=> 0,
			'status'    => 'error',
			'message' 	=> esc_html__( 'No API keys found.', WP_ULIKE_PRO_DOMAIN ),
		) );
	}

	// Remove the key with matching token
	$updated_keys = array();
	$found = false;
	
	foreach ( $get_keys as $key => $value ) {
		if ( ! is_array( $value ) || ! isset( $value['token'] ) ) {
			// Keep invalid entries
			$updated_keys[] = $value;
			continue;
		}
		
		$stored_token = trim( (string) $value['token'] );
		
		// Exact token match
		if ( $stored_token === $token ) {
			$found = true;
			continue; // Skip this key (don't add to updated_keys)
		}
		
		// Keep this key
		$updated_keys[] = $value;
	}

	if ( ! $found ) {
		wp_send_json_error( array(
			'success' 	=> 0,
			'status'    => 'error',
			'message' 	=> esc_html__( 'API key not found.', WP_ULIKE_PRO_DOMAIN ),
		) );
	}

	update_option( 'wp_ulike_rest_api_keys', $updated_keys );

	wp_send_json_success( array(
		'success' => 1,
		'status'  => 'success',
		'message' => esc_html__( 'API key successfully deleted.', WP_ULIKE_PRO_DOMAIN ),
	) );

}
add_action( 'wp_ajax_wp_ulike_delete_api_key', 'wp_ulike_pro_delete_api_key' );

/**
 * Search users for GDPR tab
 *
 * @return void
 */
function wp_ulike_pro_search_users() {
	// Check nonce for security
	if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'wp_ulike_pro_search_users' ) ) {
		wp_send_json_error( array(
			'message' => esc_html__( 'Security check failed.', WP_ULIKE_PRO_DOMAIN ),
		) );
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array(
			'message' => esc_html__( 'You do not have access.', WP_ULIKE_PRO_DOMAIN ),
		) );
	}

	$search = isset( $_GET['search'] ) ? sanitize_text_field( wp_unslash( $_GET['search'] ) ) : '';

	if( empty( $search ) || strlen( $search ) < 2 ){
		wp_send_json_success( array( 'users' => array() ) );
	}

	$users = get_users( array(
		'search' => '*' . esc_attr( $search ) . '*',
		'search_columns' => array( 'user_login', 'user_nicename', 'user_email', 'display_name' ),
		'number' => 20,
		'orderby' => 'display_name',
		'order' => 'ASC'
	) );

	$results = array();
	foreach( $users as $user ){
		$results[] = array(
			'id' => $user->ID,
			'name' => $user->display_name,
			'email' => $user->user_email,
			'login' => $user->user_login
		);
	}

	wp_send_json_success( array( 'users' => $results ) );
}
add_action( 'wp_ajax_wp_ulike_pro_search_users', 'wp_ulike_pro_search_users' );

/**
 * Remove user votes - GDPR compliance
 *
 * @return void
 */
function wp_ulike_pro_remove_user_votes_ajax() {
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'wp_ulike_pro_remove_user_votes' ) || ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array(
			'message' => esc_html__( 'You do not have access.', WP_ULIKE_PRO_DOMAIN ),
		) );
	}

	if( empty( $_POST['user_ids'] ) ){
		wp_send_json_error( array(
			'message' => esc_html__( 'Please select at least one user.', WP_ULIKE_PRO_DOMAIN ),
		) );
	}

	$user_ids = isset( $_POST['user_ids'] ) ? wp_unslash( $_POST['user_ids'] ) : array();

	// Ensure it's an array
	if( is_string( $user_ids ) ){
		$user_ids = json_decode( $user_ids, true );
	}

	if( ! is_array( $user_ids ) || empty( $user_ids ) ){
		wp_send_json_error( array(
			'message' => esc_html__( 'Invalid user IDs provided.', WP_ULIKE_PRO_DOMAIN ),
		) );
	}

	$result = wp_ulike_pro_remove_user_votes( $user_ids );

	if( $result && is_array( $result ) && $result['success'] ){
		wp_send_json_success( array(
			'message' => $result['message'],
			'rows_affected' => isset( $result['rows_affected'] ) ? $result['rows_affected'] : 0
		) );
	} else {
		wp_send_json_error( array(
			'message' => esc_html__( 'Failed to remove user votes. Please try again.', WP_ULIKE_PRO_DOMAIN ),
		) );
	}
}
add_action( 'wp_ajax_wp_ulike_pro_remove_user_votes', 'wp_ulike_pro_remove_user_votes_ajax' );

/**
 * Search posts for bulk actions
 *
 * @return void
 */
function wp_ulike_pro_search_posts() {
	// Check nonce
	if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'wp_ulike_pro_search_posts' ) ) {
		wp_send_json_error( array(
			'message' => esc_html__( 'Security check failed.', WP_ULIKE_PRO_DOMAIN ),
		) );
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array(
			'message' => esc_html__( 'You do not have access.', WP_ULIKE_PRO_DOMAIN ),
		) );
	}

	$post_type = isset( $_GET['post_type'] ) ? sanitize_text_field( wp_unslash( $_GET['post_type'] ) ) : '';
	$category = isset( $_GET['category'] ) ? absint( $_GET['category'] ) : 0;
	$taxonomy = isset( $_GET['taxonomy'] ) ? sanitize_text_field( wp_unslash( $_GET['taxonomy'] ) ) : '';
	$search = isset( $_GET['search'] ) ? sanitize_text_field( wp_unslash( $_GET['search'] ) ) : '';

	$args = array(
		'post_type'      => ! empty( $post_type ) ? $post_type : 'post',
		'post_status'    => 'publish',
		'posts_per_page' => 50,
		'orderby'        => 'title',
		'order'          => 'ASC'
	);

	if ( ! empty( $post_type ) ) {
		$args['post_type'] = $post_type;
	} else {
		// If no post type selected, get all public post types
		$args['post_type'] = get_post_types( array( 'public' => true ) );
	}

	if ( ! empty( $search ) ) {
		$args['s'] = $search;
	}

	if ( ! empty( $category ) ) {
		// Use taxonomy query if taxonomy is specified, otherwise use default category
		if ( ! empty( $taxonomy ) && $taxonomy !== 'category' ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => $taxonomy,
					'field'    => 'term_id',
					'terms'    => $category,
				),
			);
		} else {
			// Default to standard category
			$args['cat'] = $category;
		}
	}

	$query = new WP_Query( $args );
	$posts = array();

	if ( $query->have_posts() ) {
		while ( $query->have_posts() ) {
			$query->the_post();
			$posts[] = array(
				'id'    => get_the_ID(),
				'title' => get_the_title()
			);
		}
		wp_reset_postdata();
	}

	wp_send_json_success( array( 'posts' => $posts ) );
}
add_action( 'wp_ajax_wp_ulike_pro_search_posts', 'wp_ulike_pro_search_posts' );

/**
 * Bulk add likes/dislikes to posts
 *
 * @return void
 */
function wp_ulike_pro_bulk_add_likes_ajax() {
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'wp_ulike_pro_bulk_add_likes' ) || ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array(
			'message' => esc_html__( 'You do not have access.', WP_ULIKE_PRO_DOMAIN ),
		) );
	}

	if ( empty( $_POST['post_ids'] ) ) {
		wp_send_json_error( array(
			'message' => esc_html__( 'Please select at least one post.', WP_ULIKE_PRO_DOMAIN ),
		) );
	}

	$post_ids = isset( $_POST['post_ids'] ) ? wp_unslash( $_POST['post_ids'] ) : array();

	// Ensure it's an array
	if ( is_string( $post_ids ) ) {
		$post_ids = json_decode( $post_ids, true );
	}

	if ( ! is_array( $post_ids ) || empty( $post_ids ) ) {
		wp_send_json_error( array(
			'message' => esc_html__( 'Invalid post IDs provided.', WP_ULIKE_PRO_DOMAIN ),
		) );
	}

	$likes = isset( $_POST['likes'] ) ? absint( $_POST['likes'] ) : 0;
	$dislikes = isset( $_POST['dislikes'] ) ? absint( $_POST['dislikes'] ) : 0;

	if ( $likes === 0 && $dislikes === 0 ) {
		wp_send_json_error( array(
			'message' => esc_html__( 'Please enter at least one like or dislike count.', WP_ULIKE_PRO_DOMAIN ),
		) );
	}

	$result = wp_ulike_pro_bulk_add_likes( $post_ids, $likes, $dislikes );

	if ( $result && is_array( $result ) && $result['success'] ) {
		wp_send_json_success( array(
			'message' => $result['message'],
			'rows_affected' => isset( $result['rows_affected'] ) ? $result['rows_affected'] : 0
		) );
	} else {
		wp_send_json_error( array(
			'message' => isset( $result['message'] ) ? $result['message'] : esc_html__( 'Failed to add likes/dislikes. Please try again.', WP_ULIKE_PRO_DOMAIN ),
		) );
	}
}
add_action( 'wp_ajax_wp_ulike_pro_bulk_add_likes', 'wp_ulike_pro_bulk_add_likes_ajax' );

/**
 * Get current like/dislike counts for a post or custom item
 *
 * @return void
 */
function wp_ulike_pro_get_post_counts() {
	if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'wp_ulike_pro_get_post_counts' ) ) {
		wp_send_json_error( array(
			'message' => esc_html__( 'Security check failed.', WP_ULIKE_PRO_DOMAIN ),
		) );
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array(
			'message' => esc_html__( 'You do not have access.', WP_ULIKE_PRO_DOMAIN ),
		) );
	}

	$post_id = isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0;
	$item_type = isset( $_GET['item_type'] ) ? sanitize_text_field( wp_unslash( $_GET['item_type'] ) ) : 'post';

	if ( $post_id <= 0 ) {
		wp_send_json_error( array(
			'message' => esc_html__( 'Invalid item ID.', WP_ULIKE_PRO_DOMAIN ),
		) );
	}

	// Validate item_type
	$allowed_types = array( 'post', 'comment', 'activity', 'topic' );
	if ( ! in_array( $item_type, $allowed_types, true ) ) {
		wp_send_json_error( array(
			'message' => esc_html__( 'Invalid item type.', WP_ULIKE_PRO_DOMAIN ),
		) );
	}

	$is_distinct = wp_ulike_setting_repo::isDistinct( $item_type );
	$meta_key_prefix = $is_distinct ? 'count_distinct_' : 'count_total_';

	$likes = wp_ulike_get_meta_data( $post_id, $item_type, $meta_key_prefix . 'like', true );
	$dislikes = wp_ulike_get_meta_data( $post_id, $item_type, $meta_key_prefix . 'dislike', true );

	wp_send_json_success( array(
		'likes' => ! empty( $likes ) ? (int) $likes : 0,
		'dislikes' => ! empty( $dislikes ) ? (int) $dislikes : 0
	) );
}
add_action( 'wp_ajax_wp_ulike_pro_get_post_counts', 'wp_ulike_pro_get_post_counts' );

/**
 * Bulk update likes/dislikes for posts
 *
 * @return void
 */
function wp_ulike_pro_bulk_update_likes_ajax() {
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'wp_ulike_pro_bulk_update_likes' ) || ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array(
			'message' => esc_html__( 'You do not have access.', WP_ULIKE_PRO_DOMAIN ),
		) );
	}

	if ( empty( $_POST['posts'] ) ) {
		wp_send_json_error( array(
			'message' => esc_html__( 'No posts provided.', WP_ULIKE_PRO_DOMAIN ),
		) );
	}

	$posts_data = isset( $_POST['posts'] ) ? wp_unslash( $_POST['posts'] ) : array();

	// Ensure it's an array
	if ( is_string( $posts_data ) ) {
		$posts_data = json_decode( $posts_data, true );
	}

	if ( ! is_array( $posts_data ) || empty( $posts_data ) ) {
		wp_send_json_error( array(
			'message' => esc_html__( 'Invalid posts data provided.', WP_ULIKE_PRO_DOMAIN ),
		) );
	}

	$result = wp_ulike_pro_bulk_update_likes( $posts_data );

	if ( $result && is_array( $result ) && $result['success'] ) {
		wp_send_json_success( array(
			'message' => $result['message'],
			'rows_affected' => isset( $result['rows_affected'] ) ? $result['rows_affected'] : 0
		) );
	} else {
		wp_send_json_error( array(
			'message' => isset( $result['message'] ) ? $result['message'] : esc_html__( 'Failed to update likes/dislikes. Please try again.', WP_ULIKE_PRO_DOMAIN ),
		) );
	}
}
add_action( 'wp_ajax_wp_ulike_pro_bulk_update_likes', 'wp_ulike_pro_bulk_update_likes_ajax' );

/**
 * Get categories for a specific post type
 *
 * @return void
 */
function wp_ulike_pro_get_categories() {
	if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'wp_ulike_pro_get_categories' ) ) {
		wp_send_json_error( array(
			'message' => esc_html__( 'Security check failed.', WP_ULIKE_PRO_DOMAIN ),
		) );
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array(
			'message' => esc_html__( 'You do not have access.', WP_ULIKE_PRO_DOMAIN ),
		) );
	}

	$post_type = isset( $_GET['post_type'] ) ? sanitize_text_field( wp_unslash( $_GET['post_type'] ) ) : '';
	$taxonomy_name = isset( $_GET['taxonomy'] ) ? sanitize_text_field( wp_unslash( $_GET['taxonomy'] ) ) : '';

	$categories = array();
	$available_taxonomies = array();
	$selected_taxonomy_label = esc_html__( 'Category', WP_ULIKE_PRO_DOMAIN );
	$selected_taxonomy_name = '';

	if ( ! empty( $post_type ) ) {
		// Get all taxonomies for this post type
		$taxonomies = get_object_taxonomies( $post_type, 'objects' );

		// Build list of available hierarchical taxonomies
		foreach ( $taxonomies as $taxonomy ) {
			if ( $taxonomy->hierarchical && $taxonomy->public ) {
				$available_taxonomies[] = array(
					'name' => $taxonomy->name,
					'label' => $taxonomy->label
				);
			}
		}

		// If taxonomy is specified, use it; otherwise use first available
		if ( ! empty( $taxonomy_name ) ) {
			$selected_taxonomy_name = $taxonomy_name;
		} elseif ( ! empty( $available_taxonomies ) ) {
			$selected_taxonomy_name = $available_taxonomies[0]['name'];
		}

		// Get terms for selected taxonomy
		if ( ! empty( $selected_taxonomy_name ) ) {
			$taxonomy_obj = get_taxonomy( $selected_taxonomy_name );
			if ( $taxonomy_obj ) {
				$selected_taxonomy_label = $taxonomy_obj->label;
			}

			$terms = get_terms( array(
				'taxonomy' => $selected_taxonomy_name,
				'hide_empty' => false,
			) );

			if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
				foreach ( $terms as $term ) {
					$categories[] = array(
						'id' => $term->term_id,
						'name' => $term->name,
						'count' => $term->count
					);
				}
			}
		}
	} else {
		// If no post type selected, get all categories
		$all_categories = get_categories( array( 'hide_empty' => false ) );
		foreach ( $all_categories as $category ) {
			$categories[] = array(
				'id' => $category->term_id,
				'name' => $category->name,
				'count' => $category->count
			);
		}
		// Default to 'category' taxonomy
		$taxonomy = get_taxonomy( 'category' );
		if ( $taxonomy ) {
			$selected_taxonomy_label = $taxonomy->label;
			$selected_taxonomy_name = 'category';
			$available_taxonomies[] = array(
				'name' => 'category',
				'label' => $taxonomy->label
			);
		}
	}

	wp_send_json_success( array(
		'categories' => $categories,
		'taxonomy_label' => $selected_taxonomy_label,
		'taxonomy_name' => $selected_taxonomy_name,
		'available_taxonomies' => $available_taxonomies
	) );
}
add_action( 'wp_ajax_wp_ulike_pro_get_categories', 'wp_ulike_pro_get_categories' );

/**
 * Search items by custom Item ID in ulike_meta table
 * Supports searching by type only, item IDs only, or both
 *
 * @return void
 */
function wp_ulike_pro_search_by_item_id() {
	if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'wp_ulike_pro_search_by_item_id' ) ) {
		wp_send_json_error( array(
			'message' => esc_html__( 'Security check failed.', WP_ULIKE_PRO_DOMAIN ),
		) );
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array(
			'message' => esc_html__( 'You do not have access.', WP_ULIKE_PRO_DOMAIN ),
		) );
	}

	$item_type = isset( $_GET['item_type'] ) ? sanitize_text_field( wp_unslash( $_GET['item_type'] ) ) : '';
	$item_ids_input = isset( $_GET['item_ids'] ) ? sanitize_text_field( wp_unslash( $_GET['item_ids'] ) ) : '';

	// Validate: at least type or item IDs must be provided
	if ( empty( $item_type ) && empty( $item_ids_input ) ) {
		wp_send_json_error( array(
			'message' => esc_html__( 'Please select a type or enter at least one item ID.', WP_ULIKE_PRO_DOMAIN ),
		) );
	}

	// Validate type if provided
	$allowed_types = array( 'post', 'comment', 'activity', 'topic' );
	if ( ! empty( $item_type ) && ! in_array( $item_type, $allowed_types, true ) ) {
		wp_send_json_error( array(
			'message' => esc_html__( 'Invalid type provided.', WP_ULIKE_PRO_DOMAIN ),
		) );
	}

	// Parse comma-separated item IDs
	$item_ids = array();
	if ( ! empty( $item_ids_input ) ) {
		$ids_array = explode( ',', $item_ids_input );
		foreach ( $ids_array as $id ) {
			$id = absint( trim( $id ) );
			if ( $id > 0 ) {
				$item_ids[] = $id;
			}
		}
	}

	global $wpdb;
	$meta_table = $wpdb->prefix . 'ulike_meta';

	$items = array();
	$where_conditions = array();
	$where_values = array();

	// Build WHERE clause
	if ( ! empty( $item_type ) ) {
		$where_conditions[] = "`meta_group` = %s";
		$where_values[] = $item_type;
	}

	if ( ! empty( $item_ids ) ) {
		$placeholders = implode( ',', array_fill( 0, count( $item_ids ), '%d' ) );
		$where_conditions[] = "`item_id` IN ($placeholders)";
		$where_values = array_merge( $where_values, $item_ids );
	}

	// Also filter for counter meta keys only (to avoid other meta data)
	$where_conditions[] = "(`meta_key` LIKE %s OR `meta_key` = %s)";
	$where_values[] = '%count_%';
	$where_values[] = 'likers_list';

	$where_clause = ! empty( $where_conditions ) ? 'WHERE ' . implode( ' AND ', $where_conditions ) : '';

	// Get distinct item_id and meta_group combinations
	$query = "SELECT DISTINCT `item_id`, `meta_group` FROM `" . esc_sql( $meta_table ) . "` " . $where_clause;

	if ( ! empty( $where_values ) ) {
		$query = $wpdb->prepare( $query, $where_values );
	}

	$results = $wpdb->get_results( $query );

	if ( empty( $results ) ) {
		wp_send_json_success( array( 'items' => array() ) );
	}

	// Process each result
	foreach ( $results as $result ) {
		$item_id = absint( $result->item_id );
		$meta_group = sanitize_text_field( $result->meta_group );

		// Validate meta_group
		if ( ! in_array( $meta_group, $allowed_types, true ) ) {
			continue;
		}

		// Get distinct setting for this type
		$is_distinct = wp_ulike_setting_repo::isDistinct( $meta_group );
		$meta_key_prefix = $is_distinct ? 'count_distinct_' : 'count_total_';

		// Get likes and dislikes for this item
		$likes = wp_ulike_get_meta_data( $item_id, $meta_group, $meta_key_prefix . 'like', true );
		$dislikes = wp_ulike_get_meta_data( $item_id, $meta_group, $meta_key_prefix . 'dislike', true );

		// Try to get a title/name for this item
		$title = '';
		if ( $meta_group === 'post' ) {
			$post = get_post( $item_id );
			if ( $post ) {
				$title = get_the_title( $item_id );
			} else {
				$title = sprintf( esc_html__( 'Custom Post #%d', WP_ULIKE_PRO_DOMAIN ), $item_id );
			}
		} elseif ( $meta_group === 'comment' ) {
			$comment = get_comment( $item_id );
			if ( $comment ) {
				$title = sprintf( esc_html__( 'Comment #%d', WP_ULIKE_PRO_DOMAIN ), $item_id );
			} else {
				$title = sprintf( esc_html__( 'Custom Comment #%d', WP_ULIKE_PRO_DOMAIN ), $item_id );
			}
		} elseif ( $meta_group === 'activity' ) {
			$title = sprintf( esc_html__( 'Activity #%d', WP_ULIKE_PRO_DOMAIN ), $item_id );
		} elseif ( $meta_group === 'topic' ) {
			$title = sprintf( esc_html__( 'Topic #%d', WP_ULIKE_PRO_DOMAIN ), $item_id );
		} else {
			$title = sprintf( esc_html__( 'Custom Item #%d', WP_ULIKE_PRO_DOMAIN ), $item_id );
		}

		$items[] = array(
			'id' => $item_id,
			'type' => $meta_group,
			'title' => $title,
			'likes' => ! empty( $likes ) ? (int) $likes : 0,
			'dislikes' => ! empty( $dislikes ) ? (int) $dislikes : 0
		);
	}

	// Sort by item ID
	usort( $items, function( $a, $b ) {
		if ( $a['id'] == $b['id'] ) {
			return strcmp( $a['type'], $b['type'] );
		}
		return $a['id'] - $b['id'];
	} );

	wp_send_json_success( array( 'items' => $items ) );
}
add_action( 'wp_ajax_wp_ulike_pro_search_by_item_id', 'wp_ulike_pro_search_by_item_id' );

/**
 * Ajax Button Actions
 *
 * @return void
 */
function wp_ulike_pro_ajax_button_field() {

	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'wp_ulike_pro_ajax_button_field' ) || ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array(
			'success' 	=> 0,
			'status'    => 'security',
			'message' 	=> esc_html__( 'You do not have access.', WP_ULIKE_PRO_DOMAIN ),
		) );
	}

	if(  empty( $_POST['type'] ) || empty( $_POST['method'] ) ){
		wp_send_json_error( array(
			'success' 	=> 0,
			'status'    => 'empty',
			'message' 	=> esc_html__( 'Please enter required fields', WP_ULIKE_PRO_DOMAIN ),
		) );
	}

	// Sanitize input to prevent security issues
	// Handle type as array (for post_metabox_truncate) or string (for other actions)
	$type_raw = wp_unslash( $_POST['type'] );
	if( is_array( $type_raw ) ){
		// For array types (like post_metabox_truncate), sanitize array values and encode as JSON
		$type = wp_json_encode( array_map( 'sanitize_text_field', $type_raw ) );
	} else {
		$type = sanitize_text_field( $type_raw );
	}
	$action = sanitize_text_field( wp_unslash( $_POST['method'] ) );

	// Whitelist allowed actions for security
	$allowed_actions = array(
		'truncate_table',
		'delete_orphaned_rows',
		'delete_meta_group',
		'optimize_table',
		'migrate_metadata',
		'delete_duplicate_rows',
		'optimize_post_meta',
		'manage_default_pages',
		'clear_all_cache',
		'clear_transients',
		'cleanup_sessions',
		'recalculate_counters',
		'repair_tables',
		'analyze_tables',
		'sync_indexes',
		'upgrade_unserialize_post_meta',
		'post_metabox_truncate',
		'delete_views'
	);

	if( ! in_array( $action, $allowed_actions, true ) || ! function_exists( 'wp_ulike_pro_' . $action ) ){
		wp_send_json_error( array(
			'success' 	=> 0,
			'status'    => 'empty',
			'message' 	=> esc_html__( 'Item does not exist.', WP_ULIKE_PRO_DOMAIN ),
		) );
	}

	// Catch any PHP errors or exceptions
	$result = null;
	$php_error = null;

	try {
		$result = call_user_func( 'wp_ulike_pro_' . $action, $type );
	} catch ( Exception $e ) {
		$php_error = $e->getMessage();
	} catch ( Error $e ) {
		$php_error = $e->getMessage();
	}

	// If PHP error occurred, report it
	if( $php_error !== null ){
		wp_send_json_error( array(
			'success' 	=> 0,
			'status'    => 'error',
			'message' 	=> sprintf( esc_html__( 'PHP Error: %s', WP_ULIKE_PRO_DOMAIN ), esc_html( $php_error ) ),
		) );
	}

	if( $result ){
		// Check if result is an array with detailed information
		if( is_array( $result ) && isset( $result['success'] ) ){
			if( $result['success'] ){
				wp_send_json_success( array(
					'success' => 1,
					'status'  => 'success',
					'message' => isset( $result['message'] ) ? $result['message'] : esc_html__( 'Operation completed successfully.', WP_ULIKE_PRO_DOMAIN ),
					'rows_affected' => isset( $result['rows_affected'] ) ? $result['rows_affected'] : 0
				) );
			} else {
				// Function returned array with success=false, show the error message
				wp_send_json_error( array(
					'success' 	=> 0,
					'status'    => 'error',
					'message' 	=> isset( $result['message'] ) ? $result['message'] : esc_html__( 'Operation failed.', WP_ULIKE_PRO_DOMAIN ),
				) );
			}
		} elseif( $result === true ){
			// Fallback for functions that still return true
			wp_send_json_success( array(
				'success' => 1,
				'status'  => 'success',
				'message' => esc_html__( 'Operation completed successfully.', WP_ULIKE_PRO_DOMAIN ),
				'rows_affected' => 0
			) );
		}
	}

	// If we get here, something went wrong
	wp_send_json_error( array(
		'success' 	=> 0,
		'status'    => 'error',
		'message' 	=> esc_html__( 'Something wrong happened!', WP_ULIKE_PRO_DOMAIN ),
	) );

}
add_action( 'wp_ajax_wp_ulike_ajax_button_field', 'wp_ulike_pro_ajax_button_field' );

/**
 * Install core pages
 *
 * @return void
 */
function wp_ulike_pro_install_core_pages() {

	if ( ! isset( $_POST['id'] ) ||  ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], '_notice_nonce' ) || ! current_user_can( 'manage_options' )  ) {
		wp_send_json_error(  esc_html__( 'Token Error.', WP_ULIKE_PRO_DOMAIN ) );
	}

	$status = WP_Ulike_Pro_Core_Pages::install();

	if( $status ){
		wp_ulike_set_transient( 'wp-ulike-notice-' . $_POST['id'], 1, 10 * YEAR_IN_SECONDS );
		wp_send_json_success( esc_html__( 'Done.', WP_ULIKE_PRO_DOMAIN ) );
	}

	wp_send_json_error(  esc_html__( 'Something wrong happened!', WP_ULIKE_PRO_DOMAIN ) );

}
add_action( 'wp_ajax_wp_ulike_pro_install_core_pages', 'wp_ulike_pro_install_core_pages' );

/**
 * Engagement history api
 *
 * @return void
 */
function wp_ulike_pro_history_api(){
	if( ! current_user_can( wp_ulike_get_user_access_capability('stats') ) || ! wp_ulike_is_valid_nonce( WP_ULIKE_PRO_DOMAIN )  ){
		wp_send_json_error( esc_html__( 'Error: You do not have permission to do that.', WP_ULIKE_PRO_DOMAIN ) );
	}

	$type    = isset( $_GET['type'] ) ? sanitize_text_field( wp_unslash( $_GET['type'] ) ) : 'post';
	$page    = isset( $_GET['page'] ) ? absint( $_GET['page'] ) : 1;
	$perPage = isset( $_GET['perPage'] ) ? absint( $_GET['perPage'] ) : 20;
	$search  = isset( $_GET['searchQuery'] ) ? sanitize_text_field( wp_unslash( $_GET['searchQuery'] ) ) : '';

	// Sanitize sort array
	$sort_default = array( 'type' => 'ASC', 'field' => 'id' );
	$sort_raw = isset( $_GET['sort'] ) ? $_GET['sort'] : $sort_default;
	if ( is_array( $sort_raw ) ) {
		$sort = array(
			'type'  => isset( $sort_raw['type'] ) && in_array( strtoupper( $sort_raw['type'] ), array( 'ASC', 'DESC' ), true )
				? strtoupper( sanitize_text_field( $sort_raw['type'] ) )
				: 'ASC',
			'field' => isset( $sort_raw['field'] ) ? sanitize_text_field( $sort_raw['field'] ) : 'id'
		);
	} else {
		$sort = $sort_default;
	}

	$action  = isset( $_GET['selectAction'] ) ? sanitize_text_field( wp_unslash( $_GET['selectAction'] ) ) : false;

	// Sanitize selected items array
	$items_raw = isset( $_GET['selectedItems'] ) ? $_GET['selectedItems'] : array();
	$items = array();
	if ( is_array( $items_raw ) ) {
		$items = array_map( 'absint', $items_raw );
		$items = array_filter( $items ); // Remove invalid values
	}


	$settings = new wp_ulike_setting_type( $type );
	$instance = new WP_Ulike_Pro_Logs( $settings->getTableName(), $page, $perPage, $search, $sort  );

	if( $action === 'delete' && ! empty( $items ) ){
		$instance->delete_rows( $items );
		wp_send_json_success();
	}

	$output = [];
	if( $action === 'export' ){
		$output = $instance->get_csv_trnasformed_rows();
	} else {
		// Fix an error log issue
		if( ! empty( $sort['type'] ) ){
			$sort['type'] = in_array( strtolower( $sort['type'] ), array('asc', 'desc') ) ? $sort['type'] : 'ASC';
		}

		$output = $instance->get_rows();
	}

	wp_send_json( $output );
}
add_action('wp_ajax_wp_ulike_pro_history_api','wp_ulike_pro_history_api');

/**
 * Get charts data api
 *
 * @return void
 */
function wp_ulike_pro_custom_datasets_api(){
	if( ! current_user_can( wp_ulike_get_user_access_capability('stats') ) || ! wp_ulike_is_valid_nonce( WP_ULIKE_PRO_DOMAIN ) ){
		wp_send_json_error( esc_html__( 'Error: You do not have permission to do that.', WP_ULIKE_PRO_DOMAIN ) );
	}

	// Sanitize status - can be array or string
	$status_raw = isset( $_GET['status'] ) ? $_GET['status'] : '';
	if ( is_array( $status_raw ) ) {
		$status = array_map( 'sanitize_text_field', $status_raw );
	} else {
		$status = sanitize_text_field( wp_unslash( $status_raw ) );
	}

	$start_date = isset( $_GET['start_date'] ) ? sanitize_text_field( wp_unslash( $_GET['start_date'] ) ) : '';
	$end_date   = isset( $_GET['end_date'] ) ? sanitize_text_field( wp_unslash( $_GET['end_date'] ) ) : '';
	$category   = isset( $_GET['category'] ) ? sanitize_text_field( wp_unslash( $_GET['category'] ) ) : 'posts';

	$instance = WP_Ulike_Pro_Stats_V2::get_instance();
	$output   = $instance->get_custom_dataset( $category, $start_date, $end_date, $status );

    return wp_send_json($output);
}
add_action('wp_ajax_wp_ulike_pro_custom_datasets_api','wp_ulike_pro_custom_datasets_api');


/**
 * Get charts data api
 *
 * @return void
 */
function wp_ulike_pro_custom_country_codes_api(){
	if( ! current_user_can( wp_ulike_get_user_access_capability('stats') ) || ! wp_ulike_is_valid_nonce( WP_ULIKE_PRO_DOMAIN ) ){
		wp_send_json_error( esc_html__( 'Error: You do not have permission to do that.', WP_ULIKE_PRO_DOMAIN ) );
	}

	// Sanitize status array
	$status_raw = isset( $_GET['status'] ) ? $_GET['status'] : array();
	$status = array();
	if ( is_array( $status_raw ) ) {
		$status = array_map( 'sanitize_text_field', $status_raw );
	}

	// Sanitize types array
	$types_raw = isset( $_GET['types'] ) ? $_GET['types'] : array();
	$types = array();
	if ( is_array( $types_raw ) ) {
		$types = array_map( 'sanitize_text_field', $types_raw );
	}

	$start_date = isset( $_GET['start_date'] ) ? sanitize_text_field( wp_unslash( $_GET['start_date'] ) ) : '';
	$end_date   = isset( $_GET['end_date'] ) ? sanitize_text_field( wp_unslash( $_GET['end_date'] ) ) : '';

	$date_range = ! empty( $start_date ) ? [
		'start' => $start_date,
		'end'   => $end_date
	] : NULL;


	$instance = WP_Ulike_Pro_Stats_V2::get_instance();
	$output   = $instance->count_country_codes( $date_range, $status, $types );

    return wp_send_json($output);
}
add_action('wp_ajax_wp_ulike_pro_custom_country_codes_api','wp_ulike_pro_custom_country_codes_api');

/**
 * Top items API
 *
 * @return void
 */
function wp_ulike_pro_tops_api(){
	if( ! current_user_can( wp_ulike_get_user_access_capability('stats') ) || ! wp_ulike_is_valid_nonce( WP_ULIKE_PRO_DOMAIN ) ){
		wp_send_json_error( esc_html__( 'Error: You do not have permission to do that.', WP_ULIKE_PRO_DOMAIN ) );
	}

	// Sanitize types array
	$types_raw = isset( $_GET['types'] ) ? $_GET['types'] : array( 'post', 'comment', 'topic', 'activity', 'engagers' );
	$types = array();
	if ( is_array( $types_raw ) ) {
		$allowed_types = array( 'post', 'comment', 'topic', 'activity', 'engagers' );
		$types = array_intersect( array_map( 'sanitize_text_field', $types_raw ), $allowed_types );
	}
	if ( empty( $types ) ) {
		$types = array( 'post', 'comment', 'topic', 'activity', 'engagers' );
	}

	// Sanitize status array
	$status_raw = isset( $_GET['status'] ) ? $_GET['status'] : array( 'like', 'dislike' );
	$status = array();
	if ( is_array( $status_raw ) ) {
		$allowed_statuses = array( 'like', 'dislike', 'unlike', 'undislike' );
		$status = array_intersect( array_map( 'sanitize_text_field', $status_raw ), $allowed_statuses );
	}
	if ( empty( $status ) ) {
		$status = array( 'like', 'dislike' );
	}

	$start_date = isset( $_GET['start_date'] ) ? sanitize_text_field( wp_unslash( $_GET['start_date'] ) ) : '';
	$end_date   = isset( $_GET['end_date'] ) ? sanitize_text_field( wp_unslash( $_GET['end_date'] ) ) : '';
	$rel_type   = isset( $_GET['rel_type'] ) ? sanitize_text_field( wp_unslash( $_GET['rel_type'] ) ) : 'post';
	$offset     = isset( $_GET['offset'] ) ? absint( $_GET['offset'] ) : 1;
	$limit      = isset( $_GET['limit'] ) ? absint( $_GET['limit'] ) : 10;

	$date_range = ! empty( $start_date ) ? [
		'start' => $start_date,
		'end'   => $end_date
	] : NULL;

	$instance = WP_Ulike_Pro_Stats_V2::get_instance();

	$output = [];

	foreach ($types as $type) {
		$output[$type] = $instance->get_top(
			[
				'type'       => $type,
				"rel_type"   => $rel_type,
				"is_popular" => true,
				"status"     => $status,
				"offset"     => $offset,
				"limit"      => $limit
			],
			$date_range
		);
	}

    return wp_send_json($output);
}
add_action('wp_ajax_wp_ulike_pro_tops_api','wp_ulike_pro_tops_api');

/**
 * Dashboard API
 *
 * @return void
 */
function wp_ulike_pro_stats_api(){
	if( ! current_user_can( wp_ulike_get_user_access_capability('stats') ) || ! wp_ulike_is_valid_nonce( WP_ULIKE_PRO_DOMAIN )  ){
		wp_send_json_error( esc_html__( 'Error: You do not have permission to do that.', WP_ULIKE_PRO_DOMAIN ) );
	}

    $stats = WP_Ulike_Pro_Stats_V2::get_instance()->get_api_data();
    return wp_send_json($stats);
}
add_action('wp_ajax_wp_ulike_pro_stats_api','wp_ulike_pro_stats_api');

/**
 * Count device types
 *
 * @return void
 */
function wp_ulike_pro_devices_api(){
	if( ! current_user_can( wp_ulike_get_user_access_capability('stats') ) || ! wp_ulike_is_valid_nonce( WP_ULIKE_PRO_DOMAIN ) ){
		wp_send_json_error( esc_html__( 'Error: You do not have permission to do that.', WP_ULIKE_PRO_DOMAIN ) );
	}

	$type     = isset( $_GET['type'] ) ? sanitize_text_field( wp_unslash( $_GET['type'] ) ) : 'device';
	// Whitelist allowed types
	$allowed_types = array( 'device', 'os', 'browser' );
	if ( ! in_array( $type, $allowed_types, true ) ) {
		$type = 'device';
	}

	$start_date = isset( $_GET['start_date'] ) ? sanitize_text_field( wp_unslash( $_GET['start_date'] ) ) : '';
	$end_date   = isset( $_GET['end_date'] ) ? sanitize_text_field( wp_unslash( $_GET['end_date'] ) ) : '';

	$date_range = ! empty( $start_date ) ? [
		'start' => $start_date,
		'end'   => $end_date
	] : NULL;

	$instance = WP_Ulike_Pro_Stats_V2::get_instance();
	$output   = $instance->count_device_types( $date_range, $type );

    return wp_send_json($output);
}
add_action('wp_ajax_wp_ulike_pro_devices_api','wp_ulike_pro_devices_api');
