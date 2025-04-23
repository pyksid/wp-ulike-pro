<?php
/**
 * Back-end AJAX Functionalities
 *
 * 
 * @package    wp-ulike-pro
 * @author     TechnoWich 2025
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

	if ( ! isset( $_POST['id'] ) ||  ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], '_notice_nonce' ) || ! current_user_can( 'manage_options' )  ) {
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

/**
 * Engagement history api
 *
 * @return void
 */
function wp_ulike_pro_history_api(){
	if( ! current_user_can( wp_ulike_get_user_access_capability('stats') ) || ! wp_ulike_is_valid_nonce( WP_ULIKE_PRO_DOMAIN )  ){
		wp_send_json_error( esc_html__( 'Error: You do not have permission to do that.', WP_ULIKE_PRO_DOMAIN ) );
	}

	$type    = isset( $_GET['type'] ) ? sanitize_text_field( $_GET['type'] ) : 'post';
	$page    = isset( $_GET['page'] ) ? absint( $_GET['page'] ) : 1;
	$perPage = isset( $_GET['perPage'] ) ? absint( $_GET['perPage'] ) : 20;
	$search  = isset( $_GET['searchQuery'] ) ? sanitize_text_field( $_GET['searchQuery'] ) : '';
	$sort    = isset( $_GET['sort'] ) ? $_GET['sort'] : array( 'type'  => 'ASC', 'field' => 'id' );
	$action  = isset( $_GET['selectAction'] ) ? $_GET['selectAction'] : false;
	$items   = isset( $_GET['selectedItems'] ) ? $_GET['selectedItems'] : array();


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

	$status     = isset( $_GET['status'] ) ? $_GET['status'] : '';
	$start_date = isset( $_GET['start_date'] ) ? $_GET['start_date'] : '';
	$end_date   = isset( $_GET['end_date'] ) ? $_GET['end_date'] : '';
	$category   = isset( $_GET['category'] ) ? $_GET['category'] : 'posts';

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

	$status     = isset( $_GET['status'] ) ? $_GET['status'] : [];
	$start_date = isset( $_GET['start_date'] ) ? $_GET['start_date'] : '';
	$end_date   = isset( $_GET['end_date'] ) ? $_GET['end_date'] : '';

	$date_range = ! empty( $start_date ) ? [
		'start' => $start_date,
		'end'   => $end_date
	] : NULL;


	$instance = WP_Ulike_Pro_Stats_V2::get_instance();
	$output   = $instance->count_country_codes( $date_range, $status );

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

	$types      = isset( $_GET['types'] ) ? $_GET['types'] : ['post','comment','topic','activity','engagers'];
	$status     = isset( $_GET['status'] ) ? $_GET['status'] : ['like','dislike'];
	$start_date = isset( $_GET['start_date'] ) ? $_GET['start_date'] : '';
	$end_date   = isset( $_GET['end_date'] ) ? $_GET['end_date'] : '';
	$rel_type   = isset( $_GET['rel_type'] ) ? $_GET['rel_type'] : 'post';
	$offset     = isset( $_GET['offset'] ) ? $_GET['offset'] : 1;
	$limit      = isset( $_GET['limit'] ) ? $_GET['limit'] : 10;

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
