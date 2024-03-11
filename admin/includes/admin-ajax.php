<?php
/**
 * Back-end AJAX Functionalities
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

/*******************************************************
  Start AJAX From Here
*******************************************************/


/**
 * AJAX handler to get statistics data
 *
 * @return void
 */
function wp_ulike_pro_ajax_stats() {

	$nonce  = isset( $_POST['nonce'] ) ? $_POST['nonce'] : '';

	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'wp-ulike-ajax-nonce' ) || ! current_user_can( wp_ulike_get_user_access_capability('stats') ) ) {
		wp_send_json_error( esc_html__( 'Something Wrong Happened!', WP_ULIKE_PRO_DOMAIN ) );
  }

	$date_range      = isset( $_POST['range'] ) ? json_decode( stripslashes( $_POST['range'] ), true ) : '';
	$selected_status = isset( $_POST['status'] ) ? json_decode( stripslashes( $_POST['status'] ), true ) : '';
	$filter          = isset( $_POST['filter'] ) ? json_decode( stripslashes( $_POST['filter'] ), true ) : '';
	$is_refresh      = isset( $_POST['refresh'] ) ? $_POST['refresh'] : false;
	$method          = isset( $_POST['dataset'] ) ? $_POST['dataset'] : '';

	$instance = WP_Ulike_Pro_Stats::get_instance();
	$output   = ! ( wp_ulike_is_true( $is_refresh ) ) ? $instance->get_all_data() : $instance->get_data( $date_range, $selected_status, $method, $filter );

	wp_send_json_success( json_encode( $output ) );

}
add_action( 'wp_ajax_wp_ulike_pro_ajax_stats', 'wp_ulike_pro_ajax_stats' );


/**
 * AJAX handler to control logs data
 *
 * @return void
 */
function wp_ulike_pro_ajax_logs() {

	$nonce  = isset( $_POST['nonce'] ) ? $_POST['nonce'] : '';

	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'wp-ulike-ajax-nonce' ) || ! current_user_can( wp_ulike_get_user_access_capability('logs') ) ) {
		wp_send_json_error( esc_html__( 'Something Wrong Happened!', WP_ULIKE_PRO_DOMAIN ) );
  	}

	$table   = isset( $_POST['table'] ) ? $_POST['table'] : '';
	$page    = isset( $_POST['page'] ) ? $_POST['page'] : 1;
	$perPage = isset( $_POST['perPage'] ) ? $_POST['perPage'] : 15;
	$sort    = isset( $_POST['sort'] ) ? $_POST['sort'] : array( 'type'  => 'ASC', 'field' => 'id' );
	$search  = isset( $_POST['searchQuery'] ) ? $_POST['searchQuery'] : '';
	$action  = isset( $_POST['selectAction'] ) ? $_POST['selectAction'] : false;
	$items   = isset( $_POST['selectedItems'] ) ? $_POST['selectedItems'] : array();

	$instance = new WP_Ulike_Pro_Logs( $table );

	if( $action === 'delete' && ! empty( $items ) ){
		$instance->delete_rows( $items );
	}

	// Fix an error log issue
	if( ! empty( $sort['type'] ) ){
		$sort['type'] = in_array( strtolower( $sort['type'] ), array('asc', 'desc') ) ? $sort['type'] : 'ASC';
	}

	$output = $instance->get_rows( $page, $perPage, $sort, $search );

	wp_send_json_success( $output );

}
add_action( 'wp_ajax_wp_ulike_pro_ajax_logs', 'wp_ulike_pro_ajax_logs' );

/**
 * AJAX handler to control logs data
 *
 * @return void
 */
function wp_ulike_pro_export_logs() {

	$nonce  = isset( $_POST['nonce'] ) ? $_POST['nonce'] : '';

	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'wp-ulike-ajax-nonce' ) || ! current_user_can( wp_ulike_get_user_access_capability('logs') ) ) {
		wp_send_json_error( esc_html__( 'Something Wrong Happened!', WP_ULIKE_PRO_DOMAIN ) );
	}

	$table   = isset( $_POST['table'] ) ? $_POST['table'] : '';

    if( empty( $table ) ){
        wp_send_json_error( esc_html__( 'Please select your target table.', WP_ULIKE_PRO_DOMAIN ) );
    }

	$instance = new WP_Ulike_Pro_Logs( $table );
	$output   = $instance->get_csv_trnasformed_rows();

    if( empty( $output ) ){
        wp_send_json_error( esc_html__( 'No data found!', WP_ULIKE_PRO_DOMAIN ) );
	}

    switch ( $table ) {
        case 'ulike_comments':
			$table = 'comments';
		break;

        case 'ulike_activities':
            $table = 'activities';
		break;

        case 'ulike_forums':
            $table = 'topics';
		break;

        default:
			$table = 'posts';
    }

    wp_send_json_success( array(
        'content'  => $output,
        'fileName' => sprintf( '%s-%s-logs-%s', WP_ULIKE_PRO_DOMAIN, $table, current_time('timestamp') )
    ) );


}
add_action( 'wp_ajax_wp_ulike_pro_export_logs', 'wp_ulike_pro_export_logs' );

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
			'message' 	=> esc_html__( 'Something Wrong Happened!', WP_ULIKE_PRO_DOMAIN ),
		) );
	}

	$get_keys   = get_option( 'wp_ulike_rest_api_keys', array() );
	$get_keys[] = array(
		'token' => wp_generate_password( 120, false ),
		'date'  => current_time( 'mysql' )
	);

	update_option( 'wp_ulike_rest_api_keys', $get_keys );

    $api_keys = '';
    if( is_array( $get_keys ) ){
        foreach ($get_keys as $key => $value) {
            $api_keys .= sprintf( '<tr><td>%s</td><td>%s</td></tr>', $value['token'], $value['date']  );
        }
    }

	wp_send_json_success( array(
		'success' => 1,
		'status'  => 'success',
		'message' => esc_html__( 'API keys successfully generated.', WP_ULIKE_PRO_DOMAIN ),
		'content' => $api_keys
	) );

}
add_action( 'wp_ajax_wp_ulike_generate_api_key', 'wp_ulike_pro_generate_api_key' );


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
			'message' 	=> esc_html__( 'Please enter required params.', WP_ULIKE_PRO_DOMAIN ),
		) );
	}

	$type   = $_POST['type'];
	$action = $_POST['method'];

	if( ! function_exists( 'wp_ulike_pro_' . $action ) ){
		wp_send_json_error( array(
			'success' 	=> 0,
			'status'    => 'empty',
			'message' 	=> esc_html__( 'Action not exist.', WP_ULIKE_PRO_DOMAIN ),
		) );
	}

	if( call_user_func( 'wp_ulike_pro_' . $action, $type ) ){
		wp_send_json_success( array(
			'success' => 1,
			'status'  => 'success',
			'message' => esc_html__( 'Mission Accomplished.', WP_ULIKE_PRO_DOMAIN )
		) );
	}

	wp_send_json_error( array(
		'success' 	=> 0,
		'status'    => 'error',
		'message' 	=> esc_html__( 'Something Wrong Happened!', WP_ULIKE_PRO_DOMAIN ),
	) );

}
add_action( 'wp_ajax_wp_ulike_ajax_button_field', 'wp_ulike_pro_ajax_button_field' );

/**
 * Install core pages
 *
 * @return void
 */
function wp_ulike_pro_install_core_pages() {

	if ( ! isset( $_POST['id'] ) ||  ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], '_notice_nonce' ) ) {
		wp_send_json_error(  esc_html__( 'Token Error.', WP_ULIKE_PRO_DOMAIN ) );
	}

	$status = WP_Ulike_Pro_Core_Pages::install();

	if( $status ){
		wp_ulike_set_transient( 'wp-ulike-notice-' . $_POST['id'], 1, 10 * YEAR_IN_SECONDS );
		wp_send_json_success( esc_html__( 'Done.', WP_ULIKE_PRO_DOMAIN ) );
	}

	wp_send_json_error(  esc_html__( 'Something Wrong Happened!', WP_ULIKE_PRO_DOMAIN ) );

}
add_action( 'wp_ajax_wp_ulike_pro_install_core_pages', 'wp_ulike_pro_install_core_pages' );