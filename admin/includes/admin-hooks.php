<?php
/**
 * Admin Hooks
 *
 * 
 * @package    wp-ulike-pro
 * @author     TechnoWich 2024
 * @link       https://wpulike.com
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die('No Naughty Business Please !');
}


/**
 * This makes a new admin menu page to introduce premium version
 *
 * @param array $submenus
 * @return array
 */
function wp_ulike_pro_upgrade_statistics_admin_menu( $submenus ){
    if( is_array( $submenus ) ){
        if( isset( $submenus['statistics'] ) ){
            $submenus['statistics']['path'] = WP_ULIKE_PRO_ADMIN_DIR . '/includes/templates/statistics.php';
        }
    }

	return $submenus;
}
add_filter( 'wp_ulike_admin_pages', 'wp_ulike_pro_upgrade_statistics_admin_menu', 10, 1 );

/**
 * Add custom column to post list
 *
 * @param   array  $columns
 *
 * @return  array
 */
function wp_ulike_pro_manage_posts_columns( $columns, $current_post_type ){
    // add sortable columns
    add_filter( 'manage_edit-' . $current_post_type . '_sortable_columns', function( $columns ){
        $columns['wp-ulike-thumbs-down'] = 'dislikes';
        return $columns;
    } );

    return array_merge( $columns,
    array( 'wp-ulike-thumbs-down' => '<i class="dashicons dashicons-thumbs-down"></i> ' . esc_html__('Dislike',WP_ULIKE_PRO_DOMAIN) ) );
}
add_action( 'wp_ulike_manage_posts_columns', 'wp_ulike_pro_manage_posts_columns', 10, 2 );


/**
 * Display custom column
 *
 * @param   array  		$column
 * @param   integer  	$post_id
 *
 * @return  void
 */
function wp_ulike_pro_manage_posts_custom_column( $column, $post_id ) {
    if ( $column === 'wp-ulike-thumbs-down' ){
		$is_distinct = wp_ulike_setting_repo::isDistinct('post');
        $post_id     = wp_ulike_get_the_id( $post_id );
        echo sprintf( '<span class="wp-ulike-counter-box">%d</span>',  wp_ulike_get_counter_value( $post_id, 'post', 'dislike', $is_distinct ) );
    }
}
add_action( 'manage_posts_custom_column' , 'wp_ulike_pro_manage_posts_custom_column', 10, 2 );
add_action( 'manage_pages_custom_column' , 'wp_ulike_pro_manage_posts_custom_column', 10, 2 );


/**
 * Display custom column
 *
 * @param   array  		$column
 * @return  void
 */
function wp_ulike_pro_manage_sortable_columns_order( $query ) {
	if ( ! empty( $query->query['orderby'] ) && 'dislikes' == $query->query['orderby'] ) {
		$post__in = wp_ulike_get_popular_items_ids(array(
			'rel_type' => $query->get('post_type'),
			'status'   => 'dislike',
			"order"    => $query->get('order'),
			"offset"   => $query->get('paged'),
			"limit"    => $query->get('posts_per_page')
        ));

		$query->set( 'offset', 0 );
		$query->set( 'post__in', $post__in );
		$query->set( 'orderby', 'post__in' );
	}
}
add_action( 'wp_ulike_manage_sortable_columns_order' , 'wp_ulike_pro_manage_sortable_columns_order', 10, 1 );

/**
 * Export column logs
 *
 * @param   array  		$column
 * @param   integer  	$post_id
 *
 * @return  void
 */
function wp_ulike_pro_export_current_query( $query ) {
	if ( isset( $_GET['ulp_export_post_type'] ) && ! empty( $query->query['orderby'] ) && in_array( $query->query['orderby'], array( 'likes', 'dislikes' ) ) ) {
        $wp_query = new WP_Query( $query->query_vars );
        $taxonomy = get_object_taxonomies( $query->query['post_type'] );
        $exporter = array();

        while ( $wp_query->have_posts() ) {
            $wp_query->the_post();

            $data = array(
                'post_id'    => get_the_ID(),
                'post_title' => get_the_title(),
                'post_url'   => get_permalink()
            );

            if( ! empty( $taxonomy ) ){
                foreach ($taxonomy as $tax) {
                    $terms = get_the_terms( get_the_ID(), $tax );
                    $data[$tax] = $terms && ! is_wp_error( $terms ) ? join(', ', wp_list_pluck( $terms, 'name' ) ) : NULL;
                }
            }

            $is_distinct = \wp_ulike_setting_repo::isDistinct('post');
            $data['likes']    = wp_ulike_get_counter_value( wp_ulike_get_the_id(), 'post', 'like', $is_distinct  );
            $data['dislikes'] = wp_ulike_get_counter_value( wp_ulike_get_the_id(), 'post', 'dislike', $is_distinct );

            $exporter[] = $data;
        }

        if( !empty( $exporter ) ){
            wp_ulike_pro_produce_csv( apply_filters( 'wp_ulike_pro_export_current_query', $exporter, $wp_query ), sprintf( 'ulp-%s-export-%s.csv', $query->query['post_type'], current_time( 'timestamp' ) ) );
        }
	}
}
add_action( 'wp_ulike_manage_sortable_columns_order' , 'wp_ulike_pro_export_current_query', 15, 1 );

/**
 * Update pagination found posts value for custom column filter
 *
 * @param integer $found_posts
 * @param object $query
 * @return integer
 */
function wp_ulike_pro_manage_columns_found_posts( $found_posts, $query ){
	if ( ! is_admin() ){
		return $found_posts;
	}

	if ( ! empty( $query->query['orderby'] ) && 'dislikes' == $query->query['orderby'] ) {
		$found_posts = wp_ulike_get_popular_items_total_number(array(
			"rel_type" => $query->get('post_type'),
			"status"   => 'dislike'
        ));
	}

	return $found_posts;
}
add_filter( 'found_posts', 'wp_ulike_pro_manage_columns_found_posts', 10, 2 );

/**
 * Add export logs button on admin columns
 *
 * @param string $which
 * @return string
 */
function wp_ulike_pro_add_export_button_for_post_types( $which ) {
    global $typenow;

    if ( 'top' === $which && ! empty( $_GET['orderby'] ) && in_array( $_GET['orderby'], array( 'likes', 'dislikes' ) ) ) {
        echo sprintf( '<input type="submit" name="ulp_export_post_type" class="button button-primary" value="%s" />', esc_html__('Export Logs',WP_ULIKE_PRO_DOMAIN) );
    }
}
add_action( 'manage_posts_extra_tablenav', 'wp_ulike_pro_add_export_button_for_post_types', 20, 1 );


/**
 * Add comment columns
 *
 * @param array $columns
 * @return array
 */
function wp_ulike_pro_comment_columns( $columns ) {
    if( wp_ulike_get_option( 'comments_group|enable_admin_columns', false ) ){
	    $columns['wp-ulike-thumbs-down'] = '<i class="dashicons dashicons-thumbs-down"></i> ' . esc_html__('Dislike',WP_ULIKE_PRO_DOMAIN);
    }

	return $columns;
}
add_filter( 'manage_edit-comments_columns', 'wp_ulike_pro_comment_columns' );

/**
 * Set sortable columns for comments
 *
 * @param array $columns
 * @return array
 */
function wp_ulike_pro_comments_sortable_columns( $columns ) {
    if( wp_ulike_get_option( 'comments_group|enable_admin_columns', false ) ){
        $columns['wp-ulike-thumbs-down'] = 'dislikes';
    }

    return $columns;
}
add_filter( 'manage_edit-comments_sortable_columns', 'wp_ulike_pro_comments_sortable_columns' );

/**
 * Display column content for comment
 *
 * @param string $column
 * @param integer $comment_ID
 * @return void
 */
function wp_ulike_pro_comment_column_content( $column, $comment_ID ) {
    if ( $column === 'wp-ulike-thumbs-down' ){
		$is_distinct = wp_ulike_setting_repo::isDistinct('comment');
        echo sprintf( '<span class="wp-ulike-counter-box">%d</span>',  wp_ulike_get_counter_value( $comment_ID, 'comment', 'dislike', $is_distinct ) );
    }
}
add_filter( 'manage_comments_custom_column', 'wp_ulike_pro_comment_column_content', 10, 2 );

/**
 * Display custom column for comment
 *
 * @param   array  		$column
 * @return  void
 */
function wp_ulike_pro_manage_comment_sortable_columns_order( $query ) {
	if ( ! empty( $query->query_vars['orderby'] ) && 'dislikes' == $query->query_vars['orderby'] ) {
		$comment__in = wp_ulike_get_popular_items_ids(array(
			'type'     => 'comment',
			'status'   => 'dislike',
			"order"    => $query->query_vars['order'],
			"offset"   => $query->query_vars['paged'],
			"limit"    => $query->query_vars['number']
		));

		$query->query_vars['comment__in'] = $comment__in;
		$query->query_vars['orderby']     = 'comment__in';
	}
}
add_action( 'wp_ulike_manage_comment_sortable_columns_order' , 'wp_ulike_pro_manage_comment_sortable_columns_order', 10, 1 );

/**
 * Add custom admin notices
 *
 * @param array $notice_list
 * @return array
 */
function wp_ulike_pro_admin_notices( $notice_list ){
    $installed_core_pages = wp_ulike_pro_get_installed_core_pages();

    if( empty( $installed_core_pages ) && WP_Ulike_Pro_API::has_permission() ){
        $notice_list[ 'wp_ulike_install_core_pages' ] = new wp_ulike_notices([
            'id'          => 'wp_ulike_install_core_pages',
            'title'       => esc_html__( 'Install Core Pages', WP_ULIKE_SLUG ),
            'description' => esc_html__( "WP ULike Pro needs to create several pages (User Profiles, Edit Account, Registration, Login, Password Reset) to function correctly." , WP_ULIKE_SLUG ),
            'skin'        => 'default',
            'has_close'   => true,
            'buttons'     => array(
                array(
                    'label'        => esc_html__( "Create Pages", WP_ULIKE_SLUG ),
                    'ajax_request' => array(
                        'action' => 'wp_ulike_pro_install_core_pages'
                    )
                ),
                array(
                    'label'      => esc_html__('No thanks and never ask me again', WP_ULIKE_SLUG),
                    'type'       => 'skip',
                    'color_name' => 'error',
                    'expiration' => YEAR_IN_SECONDS * 10
                )
            )
        ]);
    }

    return $notice_list;
}
add_filter( 'wp_ulike_admin_notices_instances', 'wp_ulike_pro_admin_notices', 10, 1  );



/**
 * Add a post display state for special ULike pages in the page list table.
 *
 * @param array $post_states An array of post display states.
 * @param \WP_Post $post The current post object.
 *
 * @return mixed
 */
function wp_ulike_pro_add_display_post_states( $post_states, $post ) {

    $core_pages = WP_Ulike_Pro_Options::getCorePages();

    if( ! empty( $core_pages ) && in_array( $post->ID, $core_pages ) ){
        $current_page = array_search( $post->ID, $core_pages);
        if( isset($core_pages[$current_page]) ){
            $post_name = get_post_meta( $core_pages[$current_page], '_wp_ulike_pro_core', true );
            if( ! empty( $post_name ) ){
                $post_states[ 'wp-ulike-pro-core-' . $post_name ] = sprintf('ULike ᴾᴿᴼ - %s', ucwords(str_replace('-',' ', esc_attr($post_name))));
            }
        }
    }

	return $post_states;
}
add_filter( 'display_post_states', 'wp_ulike_pro_add_display_post_states', 10, 2 );