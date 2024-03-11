<?php
/**
 * Admin Function
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
 * Api keys callback for options panel
 *
 * @return void
 */
function wp_ulike_pro_rest_api_keys_settings_callback(){
    $api_keys = '';
    $get_keys = get_option( 'wp_ulike_rest_api_keys', array() );
    if( is_array( $get_keys ) ){
        foreach ($get_keys as $key => $value) {
            $api_keys .= sprintf( '<tr><td>%s</td><td>%s</td></tr>', $value['token'], $value['date']  );
        }
    }
    echo sprintf( '
            <div class="wp-ulike-pro-api-keys">
                <div class="ulf-field ulf-field-submessage">
                    <div id="wp-ulike-pro-api-keys-info-message" class="ulf-submessage ulf-submessage-info">
                        %s
                    </div>
                </div>
                <div class="clear"></div>
                <div class="ulf-field ulf-field-textarea">
                    <div class="ulf-title">
                        <h4>API Keys</h4>
                    </div>
                    <div class="lntf-fieldset">
                        <div class="ulf--wrap wp-ulike-settings-license-activation">
                            <input type="button" id="wp-ulike-pro-generate-api-key" class="button" value="%s">
                            %s
                        </div>
                    </div>
                    <div class="clear"></div>
                </div>
                <table class="wp-ulike-simple-table table-bordered">
                    <thead>
                        <tr>
                            <th>Secret Token</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                       %s
                    </tbody>
                </table>
            </div>
        ',
        esc_html__( 'These API keys allow you to use the REST API to retrieve store data in JSON for external applications or devices.', WP_ULIKE_PRO_DOMAIN ),
        esc_html__( 'Generate New API Keys', WP_ULIKE_PRO_DOMAIN ),
        wp_nonce_field( 'wp_ulike_generate_api_keys', 'wp-ulike-pro-api-keys-nonce-field', true, false ),
        $api_keys
    );
}

/**
 * Ajax button callback
 *
 * @param array $args
 * @return void
 */
function wp_ulike_pro_ajax_button_callback( $args = array() ){

    $defaults   = array(
        'title'     => NULL,
        'label'     => NULL,
        'type'      => NULL,
        'inline_js' => false,
        'desc'      => NULL,
        'action'    => NULL
    );
    $parsed_args = wp_parse_args( $args, $defaults );
    // Extract values
    extract( $parsed_args );

    $inline_script = sprintf( '<script>%s</script>', '(function ($) {
        $(\'.wp-ulike-pro-ajax-button-field\').on(\'click\',function (e) {
            e.preventDefault();
            if (confirm(\'Are you sure you want to make this change in your database?\')) {
                var $self = $(this),
                    $loaderElement = $self.closest(\'.wp-ulike-pro-ajax-button\');

                $loaderElement.addClass(\'wp-ulike-is-loading\');
                $.ajax({
                    data: {
                        action: \'wp_ulike_ajax_button_field\',
                        nonce: $self.data(\'nonce\'),
                        type: $self.data(\'type\'),
                        method: $self.data(\'action\')
                    },
                    dataType: "json",
                    type: "POST",
                    timeout: 10000,
                    url: ajaxurl,
                    success: function (response) {
                        $loaderElement.removeClass(\'wp-ulike-is-loading\');
                        $self.addClass(\'wp-ulike-success-primary\');
                        $self.prop(\'value\', response.data.message);
                    }
                });
            }
        });
    })(jQuery);' );

    echo sprintf( '
            <div class="wp-ulike-pro-ajax-button">
                <div class="ulf-field ulf-field-text">
                    <div class="ulf-title">
                        <h4>%s</h4>
                    </div>
                    <div class="ulf-fieldset">
                        <div class="ulf--wrap wp-ulike-settings-ajax-button">
                            <input type="button" class="wp-ulike-pro-ajax-button-field button ulf-warning-primary" value="%s" data-type="%s" data-action="%s" data-nonce="%s">
                            <div class="ulf-desc-text">%s</div>
                        </div>
                        <div class="clear"></div>
                    </div>
                </div>
            </div>
            %s
        ',
        $title,
        esc_attr( $label ),
        esc_attr( $type ),
        esc_attr( $action ),
        wp_create_nonce('wp_ulike_pro_ajax_button_field'),
        $desc,
        $inline_js ? $inline_script : NULL
    );
}

/**
 * Delete all logs
 *
 * @param string $type
 * @return boolean
 */
function wp_ulike_pro_truncate_table( $type ) {
    global $wpdb;

	if( ! current_user_can( 'manage_options' ) ){
		return false;
	}

    $info_args = wp_ulike_get_table_info( $type );
    if( empty( $info_args ) || ! isset( $info_args['table'] ) ){
        return false;
    }

    // TRUNCATE TABLE
    if ( $wpdb->query( sprintf( "TRUNCATE TABLE `%s`", $wpdb->prefix . $info_args['table'] ) ) === FALSE ) {
        return false;
    }

    return true;
}

/**
 * Delete singular logs|meta from meta box panel
 *
 * @param string $type
 * @return boolean
 */
function wp_ulike_pro_post_metabox_truncate( $args ) {
    global $wpdb;

	if( ! current_user_can( 'manage_options' ) || ! is_array( $args ) ){
		return false;
    }

    $table = $args['method'] === 'meta' ? 'ulike_meta' : 'ulike';
    $where = $args['method'] === 'meta' ?  array( 'item_id' => $args['id'],  'meta_group' => 'post' ) : array( 'post_id' => $args['id'] );

    // TRUNCATE TABLE
    if ( $wpdb->delete( $wpdb->prefix . $table, $where ) === FALSE ) {
        return false;
    }

    return true;
}

/**
 * Delete all orphaned rows.
 *
 * @param string $type
 * @return boolean
 */
function wp_ulike_pro_delete_orphaned_rows( $type ) {
    global $wpdb;

	if( ! current_user_can( 'manage_options' ) ){
		return false;
	}

    $info_args = wp_ulike_get_table_info( $type );
    if( empty( $info_args ) || ! isset( $info_args['table'] ) ){
        return false;
    }

    // Create query string
    $query  = sprintf( "
        DELETE FROM %s
        WHERE `%s`
        NOT IN (SELECT dt.`%s`
        FROM %s dt)",
        $wpdb->prefix . $info_args['table'],
        $info_args['column'],
        $info_args['related_column'],
        $info_args['related_table_prefix']
    );

    if ( $wpdb->query( $query ) === FALSE ) {
        return false;
    }

    return true;
}

/**
 * Optimize tables
 *
 * @param string $type
 * @return boolean
 */
function wp_ulike_pro_optimize_table( $type ) {
    global $wpdb;

	if( ! current_user_can( 'manage_options' ) ){
		return false;
	}

    $info_args = wp_ulike_get_table_info( $type );
    if( empty( $info_args ) || ! isset( $info_args['table'] ) ){
        return false;
    }

    // Create query string
    $query  = sprintf( "OPTIMIZE TABLE `%s`", $wpdb->prefix . $info_args['table'] );

    if ( $wpdb->query( $query ) === FALSE ) {
        return false;
    }

    return true;
}

/**
 * Migrate counter meta values
 *
 * @param string $type
 * @return boolean
 */
function wp_ulike_pro_migrate_metadata( $type ) {
    global $wpdb;

	if( ! current_user_can( 'manage_options' ) || ! in_array( $type, array( 'post','comment' ) ) ){
		return false;
    }

    $meta_key = ! wp_ulike_setting_repo::isDistinct( $type ) ? 'count_total_' : 'count_distinct_';

    // Create query string
    $query = sprintf( "
        SELECT *  FROM %s WHERE `meta_group` LIKE '%s' AND `meta_key` LIKE '%s'",
        $wpdb->prefix . 'ulike_meta',
        $type,
        '%' . $meta_key . '%'
    );

    // get results
    $result = $wpdb->get_results( $query );

    // return false if meta not exist
    if( empty( $result ) ){
        return false;
    }

    // Update metadata
    foreach ( $result as $key => $value ) {
        if( get_post_type( $value->item_id ) ){
            $status     = str_replace( $meta_key, '', $value->meta_key);
            $quantity   = wp_ulike_pro_get_counter_quantity( $value->item_id, $status, $type );
            $meta_value = (int) $value->meta_value + (int) $quantity;
            update_metadata( $type, $value->item_id, $status . '_amount' , $meta_value );
        }
    }

    return true;
}

/**
 * Reset all counters
 *
 * @param string $type
 * @return boolean
 */
function wp_ulike_pro_reset_counter( $type ) {
    global $wpdb;

	if( ! current_user_can( 'manage_options' ) ){
		return false;
    }

    $meta_key = ! wp_ulike_setting_repo::isDistinct( $type ) ? 'count_total_' : 'count_distinct_';

    // Create query string
    $query = sprintf( "
        UPDATE %s SET meta_value = 0 WHERE `meta_group` = '%s' AND ( `meta_key` = 'likers_list' OR `meta_key` LIKE '%s' )",
        $wpdb->prefix . 'ulike_meta',
        $type,
        '%' . $meta_key . '%'
    );
    if ( $wpdb->query( $query ) === FALSE ) {
        return false;
    }

    // Create query string
    $query = sprintf( "
        SELECT *  FROM %s WHERE `meta_group` LIKE 'user' AND `meta_key` LIKE '%s'",
        $wpdb->prefix . 'ulike_meta',
        $type . '_status'
    );
    // get results
    $result = $wpdb->get_results( $query );
    if( ! empty( $result ) ){
        foreach ( $result as $_key => $_value ) {
            $meta_list = maybe_unserialize( $_value->meta_value );
            // Check empty values
            if(empty($meta_list)){
                continue;
            }
            $status_key = array_keys( $meta_list );
            $status_val = array_fill(0, count($status_key), false);
            $status_out = array_combine($status_key, $status_val);
            wp_ulike_update_meta_data( $_value->item_id, 'user', $_value->meta_key, $status_out );
        }
    }

    // Update post meta logs
    if( in_array( $type, array( 'post','comment' ) ) ){

        // Create query string
        $meta_query  = sprintf( "
            SELECT * FROM `%s` WHERE `meta_key` IN ('like_amount','dislike_amount', 'net_votes', 'likes_counter_quantity','dislikes_counter_quantity') ",
            $type === 'post' ? $wpdb->postmeta : $wpdb->commentmeta
        );
        $meta_data = $wpdb->get_results( $meta_query );

        if( ! empty( $meta_data ) ){
            foreach ( $meta_data as $key => $value ) {
                update_metadata( $type, $type === 'post' ? $value->post_id : $value->comment_id, $value->meta_key, 0 );
            }
        }
    }

    return true;
}

/**
 * Delete meta data by group name
 *
 * @param string $group_name
 * @return boolean
 */
function wp_ulike_pro_delete_meta_group( $group_name ){
    global $wpdb;

	if( ! current_user_can( 'manage_options' ) ){
		return false;
	}

    // Create ulike meta logs
    $query = sprintf( "
        SELECT *  FROM %s WHERE `meta_group` LIKE '%s'",
        $wpdb->prefix . 'ulike_meta',
        $group_name
    );
    $data  = $wpdb->get_results( $query );

    if( ! empty( $data ) ){
        foreach ( $data as $m_key => $m_value ) {
            wp_ulike_delete_meta_data( $group_name, $m_value->item_id, $m_value->meta_key );
        }
    }

    // Delete post meta logs
    if( in_array( $group_name, array( 'post','comment' ) ) ){

        // Create query string
        $meta_query  = sprintf( "
            SELECT * FROM `%s` WHERE `meta_key` IN ('like_amount','dislike_amount', 'net_votes', 'likes_counter_quantity','dislikes_counter_quantity') ",
            $group_name === 'post' ? $wpdb->postmeta : $wpdb->commentmeta
        );
        $meta_data = $wpdb->get_results( $meta_query );

        if( ! empty( $meta_data ) ){
            foreach ( $meta_data as $key => $value ) {
                delete_metadata( $group_name, $group_name === 'post' ? $value->post_id : $value->comment_id, $value->meta_key );
            }
        }
    }

    return true;
}

/**
 * Delete meta data by group name
 *
 * @param string $group_name
 * @return boolean
 */
function wp_ulike_pro_delete_duplicate_rows( $type ){
    global $wpdb;

	if( ! current_user_can( 'manage_options' ) ){
		return false;
    }

    $info_args = wp_ulike_get_table_info( $type );
    if( empty( $info_args ) || ! isset( $info_args['table'] ) ){
        return false;
    }

    // Create query string
    $query  = sprintf( '
        DELETE FROM `%1$s`
        WHERE `id` NOT IN (SELECT MAX(`id`) FROM `%1$s` GROUP BY `user_id`,`%2$s`)',
        $wpdb->prefix . $info_args['table'],
        $info_args['column']
    );

    if ( $wpdb->query( $query ) === FALSE ) {
        return false;
    }

    return true;
}

/**
 * Delete empty post meta rows
 *
 * @param string $group_name
 * @return boolean
 */
function wp_ulike_pro_optimize_post_meta( $group_name ){
    global $wpdb;

	if( ! current_user_can( 'manage_options' ) ){
		return false;
    }

    $related_condition = $group_name !== 'delete_all' ? "`meta_value` = ''" : "`meta_key` NOT LIKE 'wp_ulike_pro_meta_box'";

    // Create query string
    $query  = sprintf( "
        DELETE FROM %s
        WHERE `meta_key` LIKE '%%wp_ulike_pro%%' AND %s",
        $wpdb->postmeta,
        $related_condition
    );

    if ( $wpdb->query( $query ) === FALSE ) {
        return false;
    }

    return true;
}

/**
 * Convert the old meta boxes to the new serialize structure.
 *
 * @param string $group_name
 * @return boolean
 */
function wp_ulike_pro_upgrade_unserialize_post_meta( $group_name ){
    global $wpdb;

	if( ! current_user_can( 'manage_options' ) ){
		return false;
    }

    // Create query string
    $posts_query  = sprintf( "
        SELECT DISTINCT `post_id` FROM %s
        WHERE `meta_key` LIKE '%%wp_ulike_pro%%'",
        $wpdb->postmeta
    );
    $deprecated_options = $wpdb->get_results( $posts_query );

    foreach ( $deprecated_options as $post ) {
        $options_val = array();
        $meta_query  = sprintf( "
            SELECT `meta_key`, `meta_value` FROM %s
            WHERE `meta_key` LIKE '%%wp_ulike_pro%%' AND `post_id` = '%s'",
            $wpdb->postmeta,
            $post->post_id
        );
        $meta_data = $wpdb->get_results( $meta_query );
        if( ! empty( $meta_data ) ){
            foreach ( $meta_data as $meta ) {
                $option_key = str_replace( 'wp_ulike_pro_', '', $meta->meta_key );
                $options_val[ $option_key ] = $meta->meta_value;
            }
        }

        if( ! empty( $options_val ) ){
            update_post_meta( $post->post_id, 'wp_ulike_pro_meta_box', $options_val );
        }
    }

    return true;
}

/**
 * Manage default pages
 *
 * @param string $action
 * @return boolean
 */
function wp_ulike_pro_manage_default_pages( $action ){
    global $wpdb;

	if( ! current_user_can( 'manage_options' ) ){
		return false;
    }

    if( $action == 'create' ){
        // install pages
        if( ! class_exists( 'WP_Ulike_Pro_Activator' ) ){
            require_once WP_ULIKE_PRO_DIR . 'public/class-activator.php';
        }
        WP_Ulike_Pro_Core_Pages::install();
    } else {
        // delete pages
        $pages = get_posts( array(
            'post_type'      => 'page',
            'posts_per_page' => -1,
            'meta_query'     => array(
                array(
                    'key'     => '_wp_ulike_pro_core',
                    'compare' => 'EXISTS',
                )
            )
        ) );
        if( ! empty( $pages ) ) {
            foreach ( $pages as $page ) {
                // Delete all products.
                wp_delete_post( $page->ID, true ); // Set to False if you want to send them to Trash.
            }
        }
    }

    return true;
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