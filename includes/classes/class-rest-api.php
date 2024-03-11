<?php

/**
 * Rest API generator
 *
 */
class WP_Ulike_Pro_Rest_API extends WP_REST_Controller {

    /** @var \WP_User $current_user */
    public $current_user, $final_user_id, $namespace;

    /**
     * Register the routes for the objects of the controller.
     */
    public function register_routes() {

        // register meta fields
        $this->register_meta();

        /** wp_get_current_user() does not work inside register_rest_route() */
        $this->current_user  = wp_get_current_user();

        // Get final user ID
        $this->final_user_id = $this->get_final_user_id();

        $version = '1';
        $this->namespace = WP_ULIKE_PRO_DOMAIN . '/v' . $version;

        // Main Route
        register_rest_route( $this->namespace, '/vote', array(
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'get_items' ),
                'permission_callback' => array( $this, 'get_permission_for_readable_routes' ),
                'args'                => $this->get_collection_params(),
            ),
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'create_item' ),
                'permission_callback' => array( $this, 'get_permission_for_writable_routes' ),
                'args'                => $this->create_item_params(),
            ),
            array(
                'methods'             => 'PUT, PATCH',
                'callback'            => array( $this, 'update_item' ),
                'permission_callback' => array( $this, 'get_permission_for_writable_routes' ),
                'args'                => $this->create_item_params(),
            )
        ) );

        // Single Route
        register_rest_route( $this->namespace, '/vote/(?P<item_id>[\d]+)', array(
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'get_item' ),
                'permission_callback' => array( $this, 'get_permission_for_readable_routes' ),
                'args'                => $this->get_item_params()
            ),
            array(
                'methods'             => 'PUT, PATCH',
                'callback'            => array( $this, 'update_item' ),
                'permission_callback' => array( $this, 'get_permission_for_writable_routes' ),
                'args'                => $this->create_item_params(),
            ),
            array(
                'methods'             => 'DELETE',
                'callback'            => array( $this, 'delete_item' ),
                'permission_callback' => array( $this, 'get_permission_for_writable_routes' ),
                'args'                => $this->create_item_params(),
            )
        ) );

        // Get User Status Route
        register_rest_route( $this->namespace, '/user-status/(?P<id>[\d]+)', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'get_user_status' ),
            'permission_callback' => array( $this, 'get_permission_for_readable_routes' ),
            'args'                => $this->get_user_status_params(),
        ) );

        // Get top users
        register_rest_route( $this->namespace, '/user', array(
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'get_users' ),
                'permission_callback' => array( $this, 'get_permission_for_readable_routes' ),
                'args'                => $this->get_user_collection_params(),
            ),
        ) );

        // Get single user info
        register_rest_route( $this->namespace, '/user/(?P<id>[\d]+)', array(
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'get_user' ),
                'permission_callback' => array( $this, 'get_permission_for_readable_routes' ),
                'args'                => $this->get_user_params()
            )
        ) );

        // Stats info
        register_rest_route( $this->namespace, '/stats', array(
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'get_stats' ),
                'permission_callback' => array( $this, 'get_permission_for_readable_routes' ),
                'args'                => $this->get_stats_params()
            )
        ) );

        do_action_ref_array( 'wp_ulike_pro_rest_api_routes', array( &$this ) );

    }

    /**
     * register meta keys on api
     *
     * @return void
     */
    public function register_meta(){

        if( WP_Ulike_Pro_Options::isPostMetaEnabled( 'post' ) ) {
            register_rest_field( 'post', 'votes_info', array(
                'get_callback' => function( $params ) {
                    return array(
                        'like_amount'    => wp_ulike_get_post_likes( $params['id'], 'like' ),
                        'dislike_amount' => wp_ulike_get_post_likes( $params['id'], 'dislike' ),
                        'likers_ids'     => wp_ulike_get_likers_list_per_post( 'ulike', 'post_id', $params['id'], NULL )
                    );
                }
            ) );
        }

        if( WP_Ulike_Pro_Options::isPostMetaEnabled( 'comment' ) ) {
            register_rest_field( 'comment', 'votes_info', array(
                'get_callback' => function( $params ) {
                    return array(
                        'like_amount'    => wp_ulike_get_comment_likes( $params['id'], 'like' ),
                        'dislike_amount' => wp_ulike_get_comment_likes( $params['id'], 'dislike' ),
                        'likers_ids'     => wp_ulike_get_likers_list_per_post( 'ulike_comments', 'comment_id', $params['id'], NULL )
                    );
                }
            ) );
        }
    }

    /**
     * Get current user id if option has been enabled.
     *
     * @return integer|null
     */
    private function get_final_user_id(){
        return wp_ulike_get_option( 'enable_auto_user_id' ) && ! empty( $this->current_user->ID ) ? $this->current_user->ID : NULL;
    }

    /**
     * Get table name from API type
     *
     * @param string $type
     * @return string
     */
    public function get_table_info( $type ){
        return wp_ulike_get_table_info( $type );
    }

    /**
     * Get entities process object
     *
     * @param integer $user_id
     * @param string $user_ip
     * @param string $type
     * @param string $method
     * @return object
     */
    private function get_entities_process( $item_id, $item_factor, $user_id, $user_ip, $type ){
        return new wp_ulike_cta_process( array(
            'item_id'     => $item_id,
            'item_factor' => $item_factor,
            'user_id'     => $user_id,
            'user_ip'     => $user_ip,
            'item_type'   => $type
        ) );
    }

    /**
     * Get a collection of items
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Response
     */
    public function get_items( $request ) {
        $params = $request->get_params();
        $info   = $this->get_table_info( $params['type'] );
        $logs   = new WP_Ulike_Pro_Logs( $info['table'], $params['page'], $params['per_page'], $params['search'] );
        $data   = $logs->get_results();

        //return a response or error based on some conditional
        if ( ! empty( $data ) ) {
            return new WP_REST_Response( array(
                'code'    => 'success',
                'message' => esc_html__( 'All information received successfully.', WP_ULIKE_PRO_DOMAIN ),
                'data'    => $data
            ), 200 );
        } else {
            return new WP_Error( 'empty-list', esc_html__( 'Votes list are empty.', WP_ULIKE_PRO_DOMAIN ) );
        }
    }

    /**
     * Get one item from the collection
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Response
     */
    public function get_item( $request ) {
        global $wpdb;

        //get parameters from request
        $params = $request->get_params();
        $info   = $this->get_table_info( $params['type'] );

        $table  = esc_sql( $wpdb->prefix . $info['table'] );
        $column = $info['column'];
        $data   = NULL;

        if( $params['output'] === 'logs' ){
            $data = $wpdb->get_row(
                $wpdb->prepare( "
                    SELECT * FROM `$table`
                    WHERE `$column` = %d",
                    $params['item_id']
                )
            );
        } else {
            $data = array(
                'like_amount'    => wp_ulike_meta_counter_value( $params['item_id'], $params['type'], 'like',  wp_ulike_setting_repo::isDistinct( $params['type'] ) ),
                'dislike_amount' => wp_ulike_meta_counter_value( $params['item_id'], $params['type'], 'dislike',  wp_ulike_setting_repo::isDistinct( $params['type'] ) ),
                'likers_list'    => wp_ulike_get_likers_list_per_post( $info['table'], $column, $params['item_id'], NULL )
            );
        }

        //return a response or error based on some conditional
        if ( ! empty( $data ) ) {
            return new WP_REST_Response( array(
                'code'    => 'success',
                'message' => esc_html__( 'Item information successfully obtained.', WP_ULIKE_PRO_DOMAIN ),
                'data'    => $data
            ), 200 );
        } else {
            return new WP_Error( 'not-found', esc_html__( 'Item not found!', WP_ULIKE_PRO_DOMAIN ) );
        }
    }

    /**
     * Create one item from the collection
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Response
     */
    public function create_item( $request ) {

        //get parameters from request
        $params = $request->get_params();

        // Update final user id if exist
        $params['user_id'] = ! empty( $this->final_user_id ) ? $this->final_user_id : $params['user_id'];

        $entities_instance = $this->get_entities_process( $params['item_id'], $params['factor'], $params['user_id'], $params['user_ip'], $params['type'] );

        $entities_instance->setPrevStatus( $params['item_id'] );

        // Get logging method
        $logging_method = wp_ulike_setting_repo::getMethod( $params['type'] );
        // set item factor
        $params['item_factor'] = $params['factor'];
        // Set current status
        if( in_array( $logging_method, array('do_not_log','by_cookie') ) ){
            $entities_instance->setCurrentStatus( $params, true );
        } else {
            $entities_instance->setCurrentStatus( $params );
        }

        $data = false;

        // Insert/Update logs
        if( ! in_array( $logging_method, array('do_not_log','by_cookie') ) && $entities_instance->getPrevStatus() ){
            $data = $entities_instance->updateData( $params['item_id'] );
        } else {
            $data = $entities_instance->insertData( $params['item_id'] );
        }

        if ( $data ) {
            // Update meta
			$entities_instance->updateMetaData( $params['item_id'] );
            // Do actions
            do_action_ref_array('wp_ulike_after_process', $entities_instance->getActionAtts() );

            return new WP_REST_Response( array(
                'code'    => 'success',
                'message' => esc_html__( 'Item Inserted.', WP_ULIKE_PRO_DOMAIN ),
                'data'    => array(
                    'status_code'   => $entities_instance->getStatusCode(),
                    'counter_value' => $entities_instance->getCounterValue()
                )
            ), 200 );
        }

        return new WP_Error( 'cant-create', esc_html__( 'Something wrong happened!', WP_ULIKE_PRO_DOMAIN ), array( 'status' => 500 ) );
    }

    /**
     * Update one item from the collection
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Response
     */
    public function update_item( $request ) {

        //get parameters from request
        $params = $request->get_params();

        // Update final user id if exist
        $params['user_id'] = ! empty( $this->final_user_id ) ? $this->final_user_id : $params['user_id'];

        $entities_instance = $this->get_entities_process( $params['item_id'], $params['factor'], $params['user_id'], $params['user_ip'], $params['type'] );

        $entities_instance->setPrevStatus( $params['item_id'] );
        $entities_instance->setCurrentStatus( false, false, $params['status'] );

        $data = $entities_instance->updateData( $params['item_id'] );

        if ( $data ) {
            // Update meta
			$entities_instance->updateMetaData( $params['item_id'] );
            // Do actions
            do_action_ref_array('wp_ulike_after_process', $entities_instance->getActionAtts() );

            return new WP_REST_Response( array(
                'code'    => 'success',
                'message' => esc_html__( 'Item Updated.', WP_ULIKE_PRO_DOMAIN ),
                'data'    => array(
                    'status_code'   => $entities_instance->getStatusCode(),
                    'counter_value' => $entities_instance->getCounterValue()
                )
            ), 200 );
        }

        return new WP_Error( 'cant-update', esc_html__( 'Something wrong happened!', WP_ULIKE_PRO_DOMAIN ), array( 'status' => 500 ) );
    }

    /**
     * Delete one item from the collection
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Response
     */
    public function delete_item( $request ) {

        //get parameters from request
        $params = $request->get_params();

        // Update final user id if exist
        $params['user_id'] = ! empty( $this->final_user_id ) ? $this->final_user_id : $params['user_id'];

        $entities_instance = $this->get_entities_process( $params['item_id'], $params['factor'], $params['user_id'], $params['user_ip'], $params['type'] );

        $entities_instance->setPrevStatus( $params['item_id'] );
        $data = $entities_instance->deleteData( $params['item_id'] );

        if ( $data ) {
            // Decrease meta counter values
            $counter_value = wp_ulike_meta_counter_value( $params['item_id'], $params['type'], $entities_instance->getPrevStatus(), $entities_instance->isDistinct() );
            if( $counter_value ){
                wp_ulike_update_meta_counter_value( $params['item_id'], --$counter_value, $params['type'], $entities_instance->getPrevStatus(), $entities_instance->isDistinct() );
            }
            // Update likers list
            $get_likers = wp_ulike_get_meta_data( $params['item_id'], $params['type'], 'likers_list', true );
            if( ! empty( $get_likers ) && ( false !== ( $liker_key = array_search( $params['user_id'], $get_likers ) )  ) ){
                unset( $get_likers[ $liker_key ] );
                wp_ulike_update_meta_data( $params['item_id'], $params['type'], 'likers_list', $get_likers );
            }
            // Update user status
            $user_info = wp_ulike_get_meta_data( $params['user_id'], 'user', sanitize_key( $params['type'] . '_status' ), true );
            if( isset( $user_info[ $params['item_id'] ] ) ){
                unset( $user_info[ $params['item_id'] ] );
                wp_ulike_update_meta_data( $params['user_id'], 'user', sanitize_key( $params['type'] . '_status' ), $user_info );
            }

            // Do actions
            do_action_ref_array('wp_ulike_after_process', $entities_instance->getActionAtts() );

            return new WP_REST_Response( array(
                'code'    => 'success',
                'message' => esc_html__( 'Item Updated.', WP_ULIKE_PRO_DOMAIN ),
                'data'    => array(
                    'status_code'   => $entities_instance->getStatusCode(),
                    'counter_value' => $entities_instance->getCounterValue()
                )
            ), 200 );
        }

        return new WP_Error( 'cant-update', esc_html__( 'Something wrong happened!', WP_ULIKE_PRO_DOMAIN ), array( 'status' => 500 ) );
    }

    /**
     * Get user status
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Response
     */
    public function get_user_status( $request ){
        global $wpdb;

        $params = $request->get_params();
        $info   = $this->get_table_info( $params['type'] );

        $query  = sprintf( '
                SELECT `status`
                FROM %s
                WHERE `user_id` = \'%s\' AND `%s` = \'%s\' ORDER BY `id` DESC
            ',
            $wpdb->prefix . $info['table'],
            $params['id'],
            $info['column'],
            $params['item_id']
        );

        $data  = $wpdb->get_var( stripslashes( $query ) );

        if ( !empty( $data ) ) {
            return new WP_REST_Response( array(
                'code'    => 'success',
                'message' => esc_html__( 'Ok, we found it.', WP_ULIKE_PRO_DOMAIN ),
                'data'    => $data
            ), 200 );
        }

        return new WP_Error( 'status-not-exist', esc_html__( 'User status not found!', WP_ULIKE_PRO_DOMAIN ), array( 'status' => 500 ) );
    }

    /**
     * Get users collection
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Response
     */
    public function get_users( $request ){

        $params = $request->get_params();

        $data   = wp_ulike_get_users(
            array(
                'type'     => $params['type'],
                'period'   => $params['period'],
                'status'   => $params['status'],
                'order'    => $params['order'],
                'page'     => $params['page'],
                'per_page' => $params['per_page']
            )
        );

        if ( !empty( $data ) ) {
            return new WP_REST_Response( array(
                'code'    => 'success',
                'message' => esc_html__( 'Ok, we found it.', WP_ULIKE_PRO_DOMAIN ),
                'data'    => $data
            ), 200 );
        }

        return new WP_Error( 'user-data-not-exist', esc_html__( 'User data not found!', WP_ULIKE_PRO_DOMAIN ), array( 'status' => 500 ) );
    }


    /**
     * Get user data
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Response
     */
    public function get_user( $request ){

        $params = $request->get_params();

        $data   = wp_ulike_get_user_data(
            $params['id'],
            array(
                'type'     => $params['type'],
                'period'   => $params['period'],
                'status'   => $params['status'],
                'order'    => $params['order'],
                'page'     => $params['page'],
                'per_page' => $params['per_page']
            )
        );

        if ( !empty( $data ) ) {
            return new WP_REST_Response( array(
                'code'    => 'success',
                'message' => esc_html__( 'Ok, we found it.', WP_ULIKE_PRO_DOMAIN ),
                'data'    => $data
            ), 200 );
        }

        return new WP_Error( 'user-data-not-exist', esc_html__( 'User data not found!', WP_ULIKE_PRO_DOMAIN ), array( 'status' => 500 ) );
    }

    /**
     * Get stars data
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|WP_REST_Response
     */
    public function get_stats( $request ){
        $params = $request->get_params();
	    $instance = WP_Ulike_Pro_Stats::get_instance();

        // filter post types
        $filter = ! empty( $params['filter'] ) ? explode( ',', $params['filter'] ) : '';
        $status = ! empty( $params['status'] ) ? array( $params['status'] ) : '';
        $range  = ! empty( $params['start_date'] ) && ! empty( $params['end_date'] ) ? array(
            'start' => $params['start_date'],
            'end'   => $params['end_date']
        ) : '';

        return new WP_REST_Response( array(
            'code'    => 'success',
            'message' => esc_html__( 'Ok, we found it.', WP_ULIKE_PRO_DOMAIN ),
            'data'    => $instance->get_data( $range, $status, $params['dataset'], $filter, true )
        ), 200 );
    }

    /**
     * Check if a given request has access to get items
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|bool
     */
    public function get_permission_for_readable_routes( $request ) {
        return $request->get_method() === 'GET' ? $this->check_authentication( 'rest_api_permission_for_readable_routes' ) : false;
    }

    /**
     * Check if a given request has access to create items
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|bool
     */
    public function get_permission_for_writable_routes( $request ) {
        return $request->get_method() !== 'GET' ? $this->check_authentication( 'rest_api_permission_for_writable_routes' ) : false;
    }

	/**
	 * Get Authorization header
	 *
	 * @return void
	 */
	private function get_authorization_header(){
		$requestHeaders = apache_request_headers();
		// Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
        $requestHeaders = array_combine( array_map('ucwords', array_keys( $requestHeaders ) ), array_values( $requestHeaders ) );
		// Get Authorization Header
		if ( isset( $requestHeaders['Authorization'] ) ) {
			return trim( $requestHeaders['Authorization'] );
		} else {
			return isset( $_REQUEST['authorization'] ) ? trim( $_REQUEST['authorization'] ) : NULL;
		}
	}

    /**
     * Check authentication status
     *
     * @param string $option_name
     * @return bool
     */
    public function check_authentication( $option_name ){
        $authentication_type = wp_ulike_get_option( 'authentication_type' );

        if( $authentication_type ===  'login' ){
            return $this->current_user_can( $option_name );
        } elseif( $authentication_type ===  'token' ) {
            $token_key = $this->get_authorization_header();
            // Check for Bearer token
            if( preg_match('/Bearer\s(\S+)/', $token_key, $matches ) ) {
                $token_key     = preg_match('/Bearer\s(\S+)/', $token_key, $matches ) ? $matches[1] : NULL;
            }
            // If exist, use it.
			if( ! empty( $token_key ) ){
                $get_keys = get_option( 'wp_ulike_rest_api_keys', array() );
                if( !empty( $get_keys ) ){
                    foreach ( $get_keys as $key => $value ) {
                        if( array_search( $token_key, $value  ) ){
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }

	public function current_user_can( $access_name ){
        $allowed_roles = wp_ulike_get_option( $access_name );
        if( ! empty( $allowed_roles ) ){
            if( empty( $this->current_user->roles ) ){
                return false;
            }
            $user_caps = array_intersect( $allowed_roles, $this->current_user->roles ) ? key( $this->current_user->allcaps ) : 'manage_options';
            return current_user_can( $user_caps );
        }

        return true;
    }

    public function isValidDate($date, $format = 'Y-m-d') {
        $d = DateTime::createFromFormat($format, $date);
        // The Y ( 4 digits year ) returns TRUE for any integer with any number of digits so changing the comparison from == to === fixes the issue.
        return $d && $d->format($format) === $date;
    }


    // function get_current_user_id() {
    //     if (!class_exists('Jwt_Auth_Public'))
    //         return false;

    //    $jwt = new \Jwt_Auth_Public('jwt-auth', '1.1.0');
    //    $token = $jwt->validate_token(false);
    //    if (\is_wp_error($token))
    //             return false;
    //    return $token->data->user->id;
    // }

    /**
     * Get the query params for collections
     *
     * @return array
     */
    public function get_collection_params() {
        return array(
            'type'   => array(
                'description'       => 'Select collection type.',
                'type'              => 'string',
                'default'           => 'post',
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => function($param, $request, $key) {
                    return in_array( strtolower($param), array( 'post', 'comment', 'activity', 'topic' ) );
                }
            ),
            'page'     => array(
                'description'       => 'Current page of the collection.',
                'type'              => 'integer',
                'default'           => 1,
                'sanitize_callback' => 'absint',
            ),
            'per_page' => array(
                'description'       => 'Maximum number of items to be returned in result set.',
                'type'              => 'integer',
                'default'           => 10,
                'sanitize_callback' => 'absint',
            ),
            'search'   => array(
                'description'       => 'Limit results to those matching a string.',
                'type'              => 'string',
                'default'           => '',
                'sanitize_callback' => 'sanitize_text_field',
            ),
        );
    }

    /**
     * Get the query params for an item
     *
     * @return array
     */
    public function get_item_params() {
        return array(
            'item_id' => array(
                'description'       => 'Select row ID.',
                'type'              => 'integer',
                'required'          => true,
                'validate_callback' => function( $param, $request, $key ) {
                    return is_numeric( $param );
                }
            ),
            'type'   => array(
                'description'       => 'Select collection type.',
                'type'              => 'string',
                'default'           => 'post',
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => function($param, $request, $key) {
                    return in_array( strtolower($param), array( 'post', 'comment', 'activity', 'topic' ) );
                }
            ),
            'output' => array(
                'description'       => 'Select collection type.',
                'type'              => 'string',
                'default'           => 'logs',
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => function($param, $request, $key) {
                    return in_array( strtolower($param), array( 'logs', 'data' ) );
                }
            )
        );
    }

    /**
     * Get the query params for an item
     *
     * @return array
     */
    public function create_item_params() {
        return array(
            'item_id' => array(
                'description'       => 'Select item ID.',
                'type'              => 'integer',
                'required'          => true,
                'validate_callback' => function( $param, $request, $key ) {
                    return is_numeric( $param );
                }
            ),
            'user_id' => array(
                'description'       => 'Select user ID.',
                'type'              => 'integer',
                'required'          => true,
                'validate_callback' => function( $param, $request, $key ) {
                    return is_numeric( $param );
                }
            ),
            'type'   => array(
                'description'       => 'Select collection type.',
                'type'              => 'string',
                'required'          => true,
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => function($param, $request, $key) {
                    return in_array( strtolower($param), array( 'post', 'comment', 'activity', 'topic' ) );
                }
            ),
            'user_ip'   => array(
                'description'       => 'Select user ip.',
                'type'              => 'string',
                'default'           => '127.0.0.1',
                'required'          => false,
                'validate_callback' => function( $param, $request, $key ) {
                    return filter_var( $param, FILTER_VALIDATE_IP );
                }
            ),
            'status'   => array(
                'description'       => 'Select status.',
                'type'              => 'string',
                'required'          => false,
                'default'           => 'like',
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => function($param, $request, $key) {
                    return in_array( strtolower($param), array( 'like', 'dislike', 'unlike', 'undislike' ) );
                }
            ),
            'factor'   => array(
                'description'       => 'Select factor.',
                'type'              => 'string',
                'required'          => false,
                'default'           => 'up',
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => function($param, $request, $key) {
                    return in_array( strtolower($param), array( 'up', 'down' ) );
                }
            )
        );
    }

    /**
     * Get the query params for user status
     *
     * @return array
     */
    public function get_user_status_params() {
        return array(
            'id' => array(
                'description'       => 'Select row ID.',
                'type'              => 'integer',
                'required'          => true,
                'validate_callback' => function( $param, $request, $key ) {
                    return is_numeric( $param );
                }
            ),
            'item_id' => array(
                'description'       => 'Select item ID.',
                'type'              => 'integer',
                'required'          => true,
                'validate_callback' => function( $param, $request, $key ) {
                    return is_numeric( $param );
                }
            ),
            'type'   => array(
                'description'       => 'Select collection type.',
                'type'              => 'string',
                'required'          => true,
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => function($param, $request, $key) {
                    return in_array( strtolower($param), array( 'post', 'comment', 'activity', 'topic' ) );
                }
            )
        );
    }

    /**
     * Get the query params for collections
     *
     * @return array
     */
    public function get_user_collection_params() {
        return array(
            'type'   => array(
                'description'       => 'Select collection type.',
                'type'              => 'string',
                'default'           => 'post',
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => function($param, $request, $key) {
                    return in_array( strtolower($param), array( 'post', 'comment', 'activity', 'topic' ) );
                }
            ),
            'status'   => array(
                'description'       => 'Select status.',
                'type'              => 'string',
                'default'           => 'like',
                'required'          => true,
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => function($param, $request, $key) {
                    return in_array( strtolower($param), array( 'like', 'dislike', 'unlike', 'undislike' ) );
                }
            ),
            'order'   => array(
                'description'       => 'Order By',
                'type'              => 'string',
                'default'           => 'DESC',
                'validate_callback' => function($param, $request, $key) {
                    return in_array( strtoupper($param), array( 'DESC', 'ASC' ) );
                }
            ),
            'period' => array(
                'description'       => 'Select period limit.',
                'type'              => 'string',
                'default'           => 'all',
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'page'     => array(
                'description'       => 'Current page of the collection.',
                'type'              => 'integer',
                'default'           => 1,
                'sanitize_callback' => 'absint',
            ),
            'per_page' => array(
                'description'       => 'Maximum number of items to be returned in result set.',
                'type'              => 'integer',
                'default'           => 10,
                'sanitize_callback' => 'absint',
            )
        );
    }

    /**
     * Get the query params for user info
     *
     * @return array
     */
    public function get_user_params() {
        return array(
            'type'   => array(
                'description'       => 'Select collection type.',
                'type'              => 'string',
                'default'           => 'post',
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => function($param, $request, $key) {
                    return in_array( strtolower($param), array( 'post', 'comment', 'activity', 'topic' ) );
                }
            ),
            'status'   => array(
                'description'       => 'Select status.',
                'type'              => 'string',
                'default'           => 'like',
                'required'          => true,
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => function($param, $request, $key) {
                    return in_array( strtolower($param), array( 'like', 'dislike', 'unlike', 'undislike' ) );
                }
            ),
            'page'     => array(
                'description'       => 'Current page of the collection.',
                'type'              => 'integer',
                'default'           => 1,
                'sanitize_callback' => 'absint',
            ),
            'per_page' => array(
                'description'       => 'Maximum number of items to be returned in result set.',
                'type'              => 'integer',
                'default'           => 10,
                'sanitize_callback' => 'absint',
            ),
            'order'   => array(
                'description'       => 'Order By',
                'type'              => 'string',
                'default'           => 'DESC',
                'validate_callback' => function($param, $request, $key) {
                    return in_array( strtoupper($param), array( 'DESC', 'ASC' ) );
                }
            ),
            'period' => array(
                'description'       => 'Select period limit.',
                'type'              => 'string',
                'default'           => 'all',
                'sanitize_callback' => 'sanitize_text_field',
            )
        );
    }

    /**
     * Get the query params for an item
     *
     * @return array
     */
    public function get_stats_params() {
        return array(
            'dataset'   => array(
                'description'       => 'Select dataset.',
                'type'              => 'string',
                'required'          => true,
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => function($param, $request, $key) {
                    return in_array( strtolower($param), array(
                        'count_all_logs_all',
                        'count_all_logs_today',
                        'count_all_logs_yesterday',
                        'get_top_likers',
                        'dataset_ulike',
                        'get_top_posts',
                        'count_logs_ulike_week',
                        'count_logs_ulike_month',
                        'count_logs_ulike_year',
                        'count_logs_ulike_all',
                        'dataset_ulike_comments',
                        'get_top_comments',
                        'count_logs_ulike_comments_week',
                        'count_logs_ulike_comments_month',
                        'count_logs_ulike_comments_year',
                        'count_logs_ulike_comments_all',
                        'dataset_ulike_activities',
                        'get_top_activities',
                        'count_logs_ulike_activities_week',
                        'count_logs_ulike_activities_month',
                        'count_logs_ulike_activities_year',
                        'count_logs_ulike_activities_all',
                        'dataset_ulike_forums',
                        'get_top_topics',
                        'count_logs_ulike_forums_week',
                        'count_logs_ulike_forums_month',
                        'count_logs_ulike_forums_year',
                        'count_logs_ulike_forums_all'
                    ) );
                }
            ),
            'start_date'   => array(
                'description'       => 'Select start date.',
                'type'              => 'string',
                'default'           => NULL,
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => function($param, $request, $key) {
                    return $this->isValidDate($param);
                }
            ),
            'end_date'   => array(
                'description'       => 'Select end date.',
                'type'              => 'string',
                'default'           => NULL,
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => function($param, $request, $key) {
                    return $this->isValidDate($param);
                }
            ),
            'status'   => array(
                'description'       => 'Select status.',
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => function($param, $request, $key) {
                    return in_array( strtolower($param), array( 'like', 'dislike', 'unlike', 'undislike' ) );
                }
            ),
            'filter' => array(
                'description'       => 'Select post types filter. (separate items by comma)',
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            )
        );
    }

}