<?php
/**
 * General Hooks
 *
 * 
 * @package    wp-ulike-pro
 * @author     TechnoWich 2025
 * @link       https://wpulike.com
 */

/**
 * Register premium templates
 *
 * @param array $templates
 * @return array
 */
function wp_ulike_pro_register_templates( $templates ){
    $templates['wp-ulike-pro-default'] = array(
        'name'                  => esc_html__('Simple Up/Down Vote', WP_ULIKE_PRO_DOMAIN),
        'callback'              => 'wp_ulike_pro_default_up_down_voting_template',
        'symbol'                => esc_url(WP_ULIKE_PRO_PUBLIC_URL . '/assets/img/templates/default.svg'),
        'is_text_support'       => true,
        'is_percentage_support' => true
    );
    $templates['wp-ulike-pro-book-heart'] = array(
        'name'                  => esc_html__('Book Heart', WP_ULIKE_PRO_DOMAIN),
        'callback'              => 'wp_ulike_pro_bookheart_template',
        'symbol'                => esc_url(WP_ULIKE_PRO_PUBLIC_URL . '/assets/img/templates/bookHeart.svg'),
        'is_text_support'       => false,
        'is_percentage_support' => true
    );
    $templates['wp-ulike-pro-checkmark'] = array(
        'name'                  => esc_html__('Check Mark', WP_ULIKE_PRO_DOMAIN),
        'callback'              => 'wp_ulike_pro_checkmark_template',
        'symbol'                => esc_url(WP_ULIKE_PRO_PUBLIC_URL . '/assets/img/templates/checkMark.svg'),
        'is_text_support'       => false,
        'is_percentage_support' => true
    );
    $templates['wp-ulike-pro-voters'] = array(
        'name'                  => esc_html__('Voter Thumb', WP_ULIKE_PRO_DOMAIN),
        'callback'              => 'wp_ulike_pro_voters_template',
        'symbol'                => esc_url(WP_ULIKE_PRO_PUBLIC_URL . '/assets/img/templates/voters.svg'),
        'is_text_support'       => false,
        'is_percentage_support' => true
    );
    $templates['wp-ulike-pro-check-like'] = array(
        'name'                  => esc_html__('Check Vote', WP_ULIKE_PRO_DOMAIN),
        'callback'              => 'wp_ulike_pro_checkvote_template',
        'symbol'                => esc_url(WP_ULIKE_PRO_PUBLIC_URL . '/assets/img/templates/checkVote.svg'),
        'is_text_support'       => false,
        'is_percentage_support' => true
    );
    $templates['wp-ulike-pro-broken-heart'] = array(
        'name'                  => esc_html__('Broken Heart', WP_ULIKE_PRO_DOMAIN),
        'callback'              => 'wp_ulike_pro_brokenheart_template',
        'symbol'                => esc_url(WP_ULIKE_PRO_PUBLIC_URL . '/assets/img/templates/brokenHeart.svg'),
        'is_text_support'       => false,
        'is_percentage_support' => true
    );
    $templates['wp-ulike-positive-negative'] = array(
        'name'                  => esc_html__('Positive/Negative Circles', WP_ULIKE_PRO_DOMAIN),
        'callback'              => 'wp_ulike_pro_positivecircle_template',
        'symbol'                => esc_url(WP_ULIKE_PRO_PUBLIC_URL . '/assets/img/templates/posNeg.svg'),
        'is_text_support'       => false,
        'is_percentage_support' => true
    );
    $templates['wp-ulike-feedback'] = array(
        'name'                  => esc_html__('FeedBack', WP_ULIKE_PRO_DOMAIN),
        'callback'              => 'wp_ulike_pro_feedback_template',
        'symbol'                => esc_url(WP_ULIKE_PRO_PUBLIC_URL . '/assets/img/templates/feedback.svg'),
        'is_text_support'       => false,
        'is_percentage_support' => true
    );
    $templates['wp-ulike-rating-face'] = array(
        'name'                  => esc_html__('Rating Face', WP_ULIKE_PRO_DOMAIN),
        'callback'              => 'wp_ulike_pro_rating_face_template',
        'symbol'                => esc_url(WP_ULIKE_PRO_PUBLIC_URL . '/assets/img/templates/ratingFace.svg'),
        'is_text_support'       => false,
        'is_percentage_support' => true
    );
    $templates['wp-ulike-rating-boy'] = array(
        'name'                  => esc_html__('Rating Boy', WP_ULIKE_PRO_DOMAIN),
        'callback'              => 'wp_ulike_pro_rating_boy_template',
        'symbol'                => esc_url(WP_ULIKE_PRO_PUBLIC_URL . '/assets/img/templates/ratingBoy.svg'),
        'is_text_support'       => false,
        'is_percentage_support' => true
    );
    $templates['wp-ulike-rating-girl'] = array(
        'name'                  => esc_html__('Rating Girl', WP_ULIKE_PRO_DOMAIN),
        'callback'              => 'wp_ulike_pro_rating_girl_template',
        'symbol'                => esc_url(WP_ULIKE_PRO_PUBLIC_URL . '/assets/img/templates/ratingGirl.svg'),
        'is_text_support'       => false,
        'is_percentage_support' => true

    );
    $templates['wp-ulike-stack-votings'] = array(
        'name'            => esc_html__('Up/Down Votes', WP_ULIKE_PRO_DOMAIN),
        'callback'        => 'wp_ulike_pro_stack_votings_template',
        'symbol'          => esc_url(WP_ULIKE_PRO_PUBLIC_URL . '/assets/img/templates/stackVotings.svg'),
        'is_text_support' => false,
        'has_subtotal'    => true
    );
    $templates['wp-ulike-star-thumb'] = array(
        'name'            => esc_html__('Star Thumb', WP_ULIKE_PRO_DOMAIN),
        'callback'        => 'wp_ulike_pro_star_thumb_template',
        'symbol'          => esc_url(WP_ULIKE_PRO_PUBLIC_URL . '/assets/img/templates/starThumb.svg'),
        'is_text_support' => false,
        'has_subtotal'    => true
    );
    $templates['wp-ulike-arrow-votings'] = array(
        'name'            => esc_html__('Arrow Votings', WP_ULIKE_PRO_DOMAIN),
        'callback'        => 'wp_ulike_pro_arrow_votings_template',
        'symbol'          => esc_url(WP_ULIKE_PRO_PUBLIC_URL . '/assets/img/templates/arrowVotings.svg'),
        'is_text_support' => false,
        'has_subtotal'    => true
    );
    $templates['wp-ulike-minimal-votings'] = array(
        'name'            => esc_html__('Minimal Votings', WP_ULIKE_PRO_DOMAIN),
        'callback'        => 'wp_ulike_pro_minimal_votings_template',
        'symbol'          => esc_url(WP_ULIKE_PRO_PUBLIC_URL . '/assets/img/templates/minimalVotings.svg'),
        'is_text_support' => false,
        'has_subtotal'    => true
    );
    $templates['wp-ulike-badge-thumb'] = array(
        'name'                  => esc_html__('Badge Thumb', WP_ULIKE_PRO_DOMAIN),
        'callback'              => 'wp_ulike_pro_badge_thumb_template',
        'symbol'                => esc_url(WP_ULIKE_PRO_PUBLIC_URL . '/assets/img/templates/badgeThumb.svg'),
        'is_text_support'       => false,
        'is_percentage_support' => true
    );
    $templates['wp-ulike-fave-star'] = array(
        'name'            => esc_html__('Fave Star', WP_ULIKE_PRO_DOMAIN),
        'callback'        => 'wp_ulike_pro_fave_star_template',
        'symbol'          => esc_url(WP_ULIKE_PRO_PUBLIC_URL . '/assets/img/templates/faveStar.svg'),
        'is_text_support' => false
    );
    $templates['wp-ulike-pin'] = array(
        'name'                  => esc_html__('Pin Button', WP_ULIKE_PRO_DOMAIN),
        'callback'              => 'wp_ulike_pro_pin_template',
        'symbol'                => esc_url(WP_ULIKE_PRO_PUBLIC_URL . '/assets/img/templates/pin.svg'),
        'is_text_support'       => false,
        'is_percentage_support' => true
    );
    $templates['wp-ulike-clapping'] = array(
        'name'            => esc_html__('Clapping Button', WP_ULIKE_PRO_DOMAIN),
        'callback'        => 'wp_ulike_pro_clapping_template',
        'symbol'          => esc_url(WP_ULIKE_PRO_PUBLIC_URL . '/assets/img/templates/clapping.svg'),
        'is_text_support' => false
    );
    $templates['wp-ulike-smiley-switch'] = array(
        'name'                  => esc_html__('Smiley Switch button', WP_ULIKE_PRO_DOMAIN),
        'callback'              => 'wp_ulike_pro_smiley_switch_template',
        'symbol'                => esc_url(WP_ULIKE_PRO_PUBLIC_URL . '/assets/img/templates/smileySwitch.svg'),
        'is_text_support'       => false,
        'is_percentage_support' => true
    );

    return $templates;
}
add_filter( 'wp_ulike_add_templates_list', 'wp_ulike_pro_register_templates', 10, 1 );

/**
 * Upgarde get_templates function arguments.
 *
 * @param array $info
 * @param array $args
 * @return array
 */
function wp_ulike_pro_upgrade_templates_args( $info, $args, $temp_list ){

    if( isset( $info['user_status'] ) ){
        $prefix = in_array( $info['status'], array( 2, 4 ) ) && strpos( $info['user_status'], 'dis') === 0  ? 'un' : '';
        $info['dis_button_text']   = wp_ulike_setting_repo::getButtonText( $args['type'], $prefix . 'dislike' );
    }

    $info['pro_button_class']  = wp_ulike_pro_generate_button_classes( $args, $info, $temp_list );
    $info['pro_general_class'] = wp_ulike_pro_generate_general_classes( $args, $info );

    if( isset( $info['wrapper_class'] ) ){
        $info['wrapper_class'] .= ' wpulike-is-pro';
    }

    // Count dislikes
    $info['total_dislikes'] = wp_ulike_get_counter_value( $args['id'], $args['slug'], 'dislike', $args['is_distinct'] );


    // Hide on zero value
    if( wp_ulike_setting_repo::isCounterZeroHidden( $args['slug'] ) && $info['total_dislikes'] == 0 ){
        $info['total_dislikes'] = '';
    }

    // Check for template filters
    if( ! empty( $args['style'] ) ){
        $templates_list = wp_ulike_generate_templates_list();
        if( isset( $templates_list[ $args['style'] ] ) ){
            $template_info = $templates_list[ $args['style'] ];
            if( isset( $template_info['is_percentage_support'] ) && $template_info['is_percentage_support'] ){
                $values = WP_Ulike_Pro_Options::maybePercentageValue( $args['slug'], array(
                    'up'   => $info['total_likes'],
                    'down' => $info['total_dislikes'],
                    'sub'  => 0
                ) );
                $info['total_likes']    = $values['up'];
                $info['total_dislikes'] = $values['down'];
            }
        }
    }

    $info['formatted_total_dislikes'] = '';
    if( wp_ulike_setting_repo::isCounterBoxVisible( $args['slug'] ) ){
        $info['formatted_total_dislikes'] = wp_ulike_format_number( $info['total_dislikes'], wp_ulike_maybe_convert_status( $info['user_status'], 'down' ) );
        $info['formatted_total_likes'] = wp_ulike_format_number( $info['total_likes'], wp_ulike_maybe_convert_status( $info['user_status'], 'up' ) );
    }

    return $info;
}
add_filter( 'wp_ulike_add_templates_args', 'wp_ulike_pro_upgrade_templates_args', 10, 3 );

/**
 * Upgarde ajax responds
 *
 * @param array $respond
 * @param integer $post_ID
 * @param string $status
 * @param array $args
 * @return array
 */
function wp_ulike_pro_upgrade_ajax_respond( $respond, $post_ID, $status, $args ){
    // check requireLogin status
    if( empty( $respond['requireLogin'] ) ){
        if( $args['factor'] === 'down' ){
            switch ( $status ){
                case 1:
                    $respond['message']     = wp_ulike_get_option( 'dislike_notice', esc_html__( 'Sorry! You Disliked This.', WP_ULIKE_PRO_DOMAIN ) );
                    $respond['messageType'] = 'success';
                    $respond['btnText']     = array(
                        'up'   => wp_ulike_setting_repo::getButtonText( $args['slug'], 'like' ),
                        'down' => wp_ulike_setting_repo::getButtonText( $args['slug'], 'dislike' )
                    );
                    break;
                case 2:
                    $respond['message']     = wp_ulike_get_option( 'undislike_notice', esc_html__( 'Thanks! You Undisliked This.', WP_ULIKE_PRO_DOMAIN ) );
                    $respond['messageType'] = 'info';
                    $respond['btnText']     = array(
                        'up'   => wp_ulike_setting_repo::getButtonText( $args['slug'], 'like' ),
                        'down' => wp_ulike_setting_repo::getButtonText( $args['slug'], 'dislike' )
                    );
                    break;
                case 3:
                    $respond['message']     = wp_ulike_get_option( 'dislike_notice', esc_html__( 'Sorry! You Disliked This.', WP_ULIKE_PRO_DOMAIN ) );
                    $respond['messageType'] = 'success';
                    $respond['btnText']     = array(
                        'up'   => wp_ulike_setting_repo::getButtonText( $args['slug'], 'like' ),
                        'down' => wp_ulike_setting_repo::getButtonText( $args['slug'], 'undislike' )
                    );
                    break;
                case 4:
                    $respond['message']     = wp_ulike_get_option( 'dislike_notice', esc_html__( 'Sorry! You Disliked This.', WP_ULIKE_PRO_DOMAIN ) );
                    $respond['messageType'] = 'success';
                    $respond['btnText']     = array(
                        'up'   => wp_ulike_setting_repo::getButtonText( $args['slug'], 'like' ),
                        'down' => wp_ulike_setting_repo::getButtonText( $args['slug'], 'undislike' )
                    );
                    break;
            }
        } else {
            $respond['btnText'] = array(
                'up'   => $respond['btnText'],
                'down' => html_entity_decode( wp_ulike_setting_repo::getButtonText( $args['slug'], 'dislike' ) )
            );
        }
    }

    if( ! empty( $args['style'] ) && wp_ulike_setting_repo::isCounterBoxVisible( $args['slug'] ) ){
        $templates_list = wp_ulike_generate_templates_list();
        if( isset( $templates_list[ $args['style'] ] ) ){
            $template_info = $templates_list[ $args['style'] ];
            if( isset( $template_info['has_subtotal'] ) && $template_info['has_subtotal'] ){
                $respond['data'] = isset( $respond['data']['sub'] ) ? $respond['data']['sub'] : NULL;
            }
        }
    }

    if( ! empty( $respond['requireLogin'] ) && wp_ulike_setting_repo::anonymousDisplay( $args['slug'] ) === 'modal' ){
        $respond['modalTemplate'] =  WP_Ulike_Pro_Options::getRequireModalTemplate( $args['slug'] );
    }


    $respond['modalAfterSuccess'] = apply_filters( 'wp_ulike_pro_init_modal_after_success', '', $args );

    return $respond;
}
add_filter( 'wp_ulike_ajax_respond', 'wp_ulike_pro_upgrade_ajax_respond', 10, 4 );

/**
 * Upgarde counter value in ajax respond
 *
 * @param integer $counterValue
 * @param integer $id
 * @param string $slug
 * @param string $status
 * @return array
 */
function wp_ulike_pro_upgrade_ajax_counter_value( $counterValue, $id, $slug, $status, $is_distinct, $template ){
    // Counters
    $up_vote   = wp_ulike_get_counter_value( $id, $slug, 'like', $is_distinct );
    $down_vote = wp_ulike_get_counter_value( $id, $slug, 'dislike', $is_distinct );

    if( in_array(  $slug, array('post', 'comment') ) ) {
        // Add Quantity values
        $up_vote    += wp_ulike_pro_get_counter_quantity( $id, 'like', $slug );
        $down_vote  += wp_ulike_pro_get_counter_quantity( $id, 'dislike', $slug );

        // Maybe update post meta
        if( ( $slug === 'post' && get_post_type( $id ) ) || ( $slug === 'comment' && wp_get_comment_status( $id ) ) ){
            WP_Ulike_Pro_Options::maybeUpdateMetaData( $slug, $id, array(
                'like_amount'    => $up_vote,
                'dislike_amount' => $down_vote,
                'net_votes'      => ( $up_vote - $down_vote )
            ) );
        }
    }

    if( ! wp_ulike_setting_repo::isCounterBoxVisible( $slug ) ){
        return '';
    }

    // Generate counter args
    $counter_args = array(
        'up'   => $up_vote,
        'down' => $down_vote,
        'sub'  => $up_vote - $down_vote
    );

    // Check zero display
    if( wp_ulike_setting_repo::isCounterZeroHidden( $slug ) ){
        $counter_args['sub']  = $counter_args['sub'] == 0 ? '' : $counter_args['sub'];
        $counter_args['up']   = $counter_args['up'] == 0 ? '' : $counter_args['up'];
        $counter_args['down'] = $counter_args['down'] == 0 ? '' : $counter_args['down'];
    }

    // Check template status
    $templates_list = wp_ulike_generate_templates_list();
    if( isset( $templates_list[ $template ] ) ){
        $template_info = $templates_list[ $template ];
        if( isset( $template_info['is_percentage_support'] ) && $template_info['is_percentage_support'] ){
            $counter_args = WP_Ulike_Pro_Options::maybePercentageValue( $slug, $counter_args );
        }
    }

    // Generate reverse status
    $up_status    = wp_ulike_maybe_convert_status( $status, 'up' );
    $down_status  = wp_ulike_maybe_convert_status( $status, 'down' );
    $total_status = $up_vote - $down_vote >= 0 ? 'like' : 'dislike';

    // Formatted output
    $output = array();
    $output['sub']  = wp_ulike_format_number( $counter_args['sub'], $total_status );
    $output['up']   = wp_ulike_format_number( $counter_args['up'], $up_status );
    $output['down'] = wp_ulike_format_number( $counter_args['down'], $down_status );

    // Remove double minus
    if( $total_status === 'dislike' ){
        $output['sub'] = str_replace( "--", "-", $output['sub'] );
    }

    return apply_filters( 'wp_ulike_pro_ajax_counter_data', $output, $counter_args, $id, $slug );
}
add_filter( 'wp_ulike_ajax_counter_value', 'wp_ulike_pro_upgrade_ajax_counter_value', 10, 6 );

/**
 * Upgrade user access roles to admin panel
 *
 * @param array $roles
 * @param string $type
 * @return void
 */
function wp_ulike_pro_upgrade_user_access_capabilities( $roles, $type ){
    switch ($type) {
        case 'stats':
            $roles = wp_ulike_get_option( 'statistics_display_roles' );
            break;
        case 'logs':
            $roles = wp_ulike_get_option( 'logs_display_roles' );
            break;
        case 'dashboard':
            $roles = wp_ulike_get_option( 'dashboard_access_roles' );
            break;
    }
    return $roles;
}
add_filter( 'wp_ulike_display_capabilities', 'wp_ulike_pro_upgrade_user_access_capabilities', 10, 2 );


/**
 * Upgrade plugin name
 *
 * @return string
 */
function wp_ulike_pro_upgrade_plugin_name(){
    return esc_html__( 'ULike ᴾᴿᴼ', WP_ULIKE_PRO_DOMAIN );
}
add_filter( 'wp_ulike_plugin_name', 'wp_ulike_pro_upgrade_plugin_name' );

/**
 * Update counter based on quantity meta value.
 *
 * @param integer $value
 * @param integer $id
 * @param string $type
 * @return void
 */
function wp_ulike_pro_update_counter_value( $value, $id, $type, $status ){
    if( in_array( $type, array('post', 'comment') ) && ( empty( $_POST['action'] ) || $_POST['action'] !==  'wp_ulike_process' )  ){
        // Check string output
        if( is_string( $value ) ){
            $value = (int) preg_replace( '/[^0-9.]+/', '', $value );
        }
        // Get counter quantity value
        $counter_quantity =  wp_ulike_pro_get_counter_quantity( $id, $status, $type );
        // Update value
        if( ! empty( $counter_quantity ) ){
            $value += $counter_quantity;
        }
    }
    return $value;
}
add_filter( 'wp_ulike_counter_value', 'wp_ulike_pro_update_counter_value', 10, 4 );

/**
 * General hooks init
 *
 * @return void
 */
function wp_ulike_pro_general_hooks_init(){
    global $ulp_session;

    // Remove old microdata markups
    add_filter('wp_ulike_posts_microdata', '__return_null', 10);
    add_filter('wp_ulike_posts_add_attr', '__return_null', 10);

    // Init user profiles
    new WP_Ulike_Pro_Rewrite();

    // Init avatar replace
    new WP_Ulike_Pro_Avatar();

    $ulp_session = new WP_Ulike_Pro_Session_Handler();
    $ulp_session->init();

    if( wp_ulike_get_option( 'enable_admin_limit_access' ) && is_user_logged_in() ){
        if ( is_admin() && ! current_user_can( wp_ulike_get_user_access_capability('dashboard') ) &&
        ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
            wp_redirect( wp_ulike_get_option( 'dashboard_custom_redirect', home_url() ) );
            exit;
        }
    }

    // redirect default wordpress login page
    if( WP_Ulike_Pro_Options::isWpLoginRedirectEnabled() && WP_Ulike_Pro_Options::getLoginPageUrl() ){
        // WP tracks the current page - global the variable to access it
        global $pagenow;
        // Check if a $_GET['action'] is set, and if so, load it into $action variable
        $action   = isset( $_GET['action'] ) ? $_GET['action'] : '';
        $redirect = isset( $_GET['redirect_to'] ) ? $_GET['redirect_to'] : '';
        // interim_login tells WordPress that the users session has expired, prompts the user to log back in and takes them back to the page they were on when the session expired.
        $interim  = isset( $_GET['interim-login'] ) ? $_GET['interim-login'] : false;
        // Check if we're on the login page, and ensure the action is not 'logout'
        if( $pagenow == 'wp-login.php' && ! $interim && (! $action || ( $action && ! in_array($action, array('logout', 'rp', 'resetpass'))))) {
            // Load the home page url
            $page_url = WP_Ulike_Pro_Permalinks::get_login_url();

            if( $action == 'lostpassword'){
                $page_url = WP_Ulike_Pro_Options::getResetPasswordPageUrl();
            } else {
                if( ! empty( $redirect ) ){
                    $page_url = esc_url( add_query_arg( array(
                        'redirect_to' => $redirect,
                    ), $page_url ) );
                }
            }

            // Redirect to the home page
            wp_redirect($page_url);
            // Stop execution to prevent the page loading for any reason
            exit();
        }
    }

    // Display social items
    if( ! is_admin() || wp_doing_ajax() ){
        wp_ulike_pro_social_share_auto_display();
        wp_ulike_pro_social_login_auto_display();
    }


    if( WP_Ulike_Pro_Options::isEmailVerifyEnabled() ){
        // Add the "Pending" role with no capabilities
        add_role(
            'pending',
            esc_html__('Pending', WP_ULIKE_PRO_DOMAIN),
            array(
                'read'          => false,
                'edit_posts'    => false,
                'delete_posts'  => false,
                'publish_posts' => false,
            )
        );
    }

}
add_action( 'init', 'wp_ulike_pro_general_hooks_init' );

/**
 * wp hooks init
 *
 * @return void
 */
function wp_ulike_pro_general_hooks_wp(){
    if( WP_Ulike_Pro_Options::getAvailabeSocialLogins() && ! empty( $_GET['ulp-api'] ) && ! empty( $_GET['provider'] ) ){
        global $ulp_session;
        // if session exists it means this request submitted by our forms.
        if( $ulp_session->get( 'current_url' ) ){
            $social_login = new WP_Ulike_Pro_Social_Login;
            $social_login->connectUser();
        }
    }
}
add_action( 'wp', 'wp_ulike_pro_general_hooks_wp' );

/**
 * General hooks after setup theme
 *
 * @return void
 */
function wp_ulike_pro_general_hooks_after_setup_theme() {
    if(  wp_ulike_get_option( 'enable_admin_limit_access', false ) && wp_ulike_get_option( 'hide_admin_bar', false ) && ! current_user_can( wp_ulike_get_user_access_capability('dashboard') ) ){
        add_filter('show_admin_bar', '__return_false');
    }
}
add_action( 'after_setup_theme', 'wp_ulike_pro_general_hooks_after_setup_theme' );

/**
 * Update the content value of post type
 *
 * @param string $current_content
 * @param string $post_content
 * @return string
 */
function wp_ulike_pro_update_the_content( $current_content, $post_content ){

    if( WpUlikeInit::is_frontend() && wp_ulike_is_true( wp_ulike_pro_get_metabox_value( 'auto_display' ) ) && in_the_loop() && is_main_query() && is_singular() ){

        // exclude to display like button on other pages
        if( is_page() && wp_ulike_get_the_id() != get_queried_object_id()  ){
            return $current_content;
        }

        $display_position = wp_ulike_pro_get_metabox_value( 'display_position' );
        $button_args      = array();
        // Select template
        if( '' !== ( $template = wp_ulike_pro_get_metabox_value( 'template' ) ) ){
            $button_args['style'] = $template;
        }

        $get_html_button  = wp_ulike( 'put', $button_args );

        switch ( $display_position ) {
            case 'top':
                $current_content = $get_html_button . $post_content;
                break;

            case 'top_bottom':
                $current_content = $get_html_button . $post_content . $get_html_button;
                break;

            default:
                $current_content = $post_content . $get_html_button;
                break;
        }
    }

    return $current_content;
}
add_filter( 'wp_ulike_the_content', 'wp_ulike_pro_update_the_content', 10, 2 );

/**
 * Update the content value of comment based on meta fields
 *
 * @param string $current_content
 * @param string $comment_content
 * @return string
 */
function wp_ulike_pro_update_comment_text( $current_content, $comment_content ){
    // Check meta conditions
    if( wp_ulike_is_true( wp_ulike_pro_get_comment_metabox_value( 'auto_display' ) ) && WP_Ulike_Pro::is_frontend() ){
        // Get display position if exist
        $display_position = wp_ulike_pro_get_comment_metabox_value( 'display_position' );
        $button_args      = array();

        // Select template
        if( '' !== ( $template = wp_ulike_pro_get_comment_metabox_value( 'template' ) ) ){
            $button_args['style'] = $template;
        }

        $get_html_button = wp_ulike_comments( 'put', $button_args );

        switch ( $display_position ) {
            case 'top':
                $current_content = $get_html_button . $comment_content;
                break;

            case 'top_bottom':
                $current_content = $get_html_button . $comment_content . $get_html_button;
                break;

            default:
                $current_content = $comment_content . $get_html_button;
                break;
        }
    }

    return $current_content;
}
add_filter( 'wp_ulike_comment_text', 'wp_ulike_pro_update_comment_text', 10, 2 );

/**
 * Enable REST API
 *
 * @return void
 */
function wp_ulike_pro_rest_api_init(){
    if ( wp_ulike_is_true( wp_ulike_get_option( 'enable_rest_api', false ) ) ) {
        $instance = new WP_Ulike_Pro_Rest_API();
        $instance->register_routes();
    }
}
add_action( 'rest_api_init', 'wp_ulike_pro_rest_api_init');

/**
 * Add recaptcha codes.
 *
 * @param string $type
 * @param object $args
 * @return void
 */
function wp_ulike_pro_forms_add_recaptcha( $type, $args ){
    if( WP_Ulike_Pro_Options::isRecaptchaEnabled() ){
        // enqueue scripts

        if( ! WP_Ulike_Pro_Options::isGlobalRecaptchaEnabled() ){
            WP_Ulike_Pro_reCAPTCHA_Enqueue::wp_enqueue_scripts();
        }

        if( wp_ulike_get_option( 'recaptcha_version' ) === 'v3' ) {
        ?>
<div class="ulp-recaptcha-field ulp-google-recaptcha" id="ulp-recaptcha-<?php echo esc_attr( $type . '-' . $args->form_id );?>"
    data-mode="<?php echo preg_replace("/[^A-Za-z0-9 ]/", '', esc_attr( $type ) ); ?>"></div>
<?php
        } else {
			$options = array(
				'data-type'     => wp_ulike_get_option( 'v2_recaptcha_type' ),
				'data-size'     => wp_ulike_get_option( 'v2_recaptcha_size' ),
				'data-theme'    => wp_ulike_get_option( 'v2_recaptcha_theme' ),
				'data-sitekey'  => wp_ulike_get_option( 'v2_recaptcha_sitekey' )
			);
			$attrs = '';
			foreach( $options as $att => $value ) {
				if( $value ) {
                    $attrs .= ' ' . sanitize_key( $att ) . '="' . esc_attr( $value ) . '" ';
				}
			}
        ?>
<div class="ulp-recaptcha-field ulp-flex-col-xl-12 ulp-flex-col-md-12 ulp-flex-col-xs-12">
    <div class="ulp-flex ulp-flex-center-xs">
        <div class="ulp-google-recaptcha" id="ulp-recaptcha-<?php echo esc_attr( $type . '-' . $args->form_id );?>" <?php echo $attrs; ?>></div>
    </div>
</div>
<?php
        }
    }
}
add_action( 'wp_ulike_pro_forms_before_submit', 'wp_ulike_pro_forms_add_recaptcha', 10, 2 );

/**
 * Validate recaptcha forms.
 *
 * @param array $args
 * @return void
 */
function wp_ulike_pro_validate_recaptcha( $args ){
    // phpcs:disable WordPress.Security.NonceVerification -- already verified here via WP ULike Pro Form nonce
    if( ! WP_Ulike_Pro_Options::isRecaptchaEnabled() ){
        return;
    }

    $error_codes = array(
        'missing-input-secret'   => WP_Ulike_Pro_Options::getNoticeMessage( 'missing_input_secret', esc_html__( 'The secret parameter is missing.', WP_ULIKE_PRO_DOMAIN ) ),
        'invalid-input-secret'   => WP_Ulike_Pro_Options::getNoticeMessage( 'invalid_input_secret', esc_html__( 'The secret parameter is invalid or malformed.', WP_ULIKE_PRO_DOMAIN ) ),
        'missing-input-response' => WP_Ulike_Pro_Options::getNoticeMessage( 'missing_input_response', esc_html__( 'Please confirm you are not a robot', WP_ULIKE_PRO_DOMAIN ) ),
        'invalid-input-response' => WP_Ulike_Pro_Options::getNoticeMessage( 'invalid_input_response', esc_html__( 'The response parameter is invalid or malformed.', WP_ULIKE_PRO_DOMAIN ) ),
        'bad-request'            => WP_Ulike_Pro_Options::getNoticeMessage( 'bad_request', esc_html__( 'The request is invalid or malformed.', WP_ULIKE_PRO_DOMAIN ) ),
        'timeout-or-duplicate'   => WP_Ulike_Pro_Options::getNoticeMessage( 'timeout_or_duplicate', esc_html__( 'The response is no longer valid: either is too old or has been used previously.', WP_ULIKE_PRO_DOMAIN ) ),
        'undefined'              => WP_Ulike_Pro_Options::getNoticeMessage( 'undefined', esc_html__( 'Undefined reCAPTCHA error.', WP_ULIKE_PRO_DOMAIN) ),
        'score'                  => WP_Ulike_Pro_Options::getNoticeMessage( 'score', esc_html__( 'It is very likely a bot.', WP_ULIKE_PRO_DOMAIN) ),
    );

    $secret_key = '';
    if( wp_ulike_get_option( 'recaptcha_version' ) === 'v3' ) {
        $secret_key = wp_ulike_get_option( 'v3_recaptcha_secretkey' );
    } else{
        $secret_key = wp_ulike_get_option( 'v2_recaptcha_secretkey' );
    }

	if ( empty( $_POST['g-recaptcha-response'] ) ) {
		throw new \Exception( $error_codes['missing-input-response'] );
	} else {
		$client_captcha_response = sanitize_textarea_field( $_POST['g-recaptcha-response'] );
	}

	$user_ip  = sanitize_text_field( $_SERVER['REMOTE_ADDR'] );
	$response = wp_remote_get( "https://www.google.com/recaptcha/api/siteverify?secret=$secret_key&response=$client_captcha_response&remoteip=$user_ip" );


	if ( is_array( $response ) ) {
		$result = json_decode( $response['body'] );

        // set default 0.5 because Google recommend by default set 0.5 score
        // https://developers.google.com/recaptcha/docs/v3#interpreting_the_score
        $validate_score = apply_filters( 'wp_ulike_pro_recaptcha_score_validation', 0.5 );

        if ( isset( $result->score ) && $result->score < (float) $validate_score ) {
            throw new \Exception( $error_codes['score'] );
        } elseif ( isset( $result->{'error-codes'} ) && ! $result->success ) {
            foreach ( $result->{'error-codes'} as $key => $error_code ) {
                $code = array_key_exists( $error_code, $error_codes ) ? $error_code : 'undefined';
                throw new \Exception( $error_codes[ $code ] );
            }
        }

	}
	// phpcs:enable WordPress.Security.NonceVerification -- already verified here via WP ULike Pro Form nonce
}
add_action( 'wp_ulike_pro_before_login_process', 'wp_ulike_pro_validate_recaptcha', 10, 1 );
add_action( 'wp_ulike_pro_before_signup_process', 'wp_ulike_pro_validate_recaptcha', 10, 1 );
add_action( 'wp_ulike_pro_before_reset_password_process', 'wp_ulike_pro_validate_recaptcha', 10, 1 );
add_action( 'wp_ulike_pro_before_profile_process', 'wp_ulike_pro_validate_recaptcha', 10, 1 );

/**
 * validate two factor in login form
 *
 * @param array $args
 * @return void
 */
function wp_ulike_pro_validate_two_factor( $args ){
    // check two factor enabled
    if( ! WP_Ulike_Pro_Options::is2FactorAuthEnabled() ){
        return;
    }

    // check user exist
    $user_data = get_user_by( 'login', $args->data['username'] );
    if ( ! $user_data ) {
        // check for user email
        $user_data = get_user_by( 'email', $args->data['username'] );
        if ( ! $user_data ) {
            throw new \Exception( WP_Ulike_Pro_Options::getNoticeMessage( 'login_failed', esc_html__( 'Invalid username or incorrect password!', WP_ULIKE_PRO_DOMAIN ) ) );
        }
    }

    // get otp code
    $otp_code = isset( $_POST['otp'] ) ? $_POST['otp'] : NULL;

    // check if user has 2fa
    $secrets = get_user_meta( $user_data->ID, 'ulp_two_factor_secrets', true );
    // if user has enabled two factor
    if( ! empty( $secrets ) ){
        // check status
        if( $otp_code !== NULL ){
            if( ! wp_ulike_pro_is_valid_otp( $otp_code, $secrets ) ){
                throw new \Exception( WP_Ulike_Pro_Options::getNoticeMessage( 'incorrect_tfa', esc_html__( 'The one-time password (TFA code) you entered was incorrect', WP_ULIKE_PRO_DOMAIN ) ) );
            }
        } else {
            // send fragments fields
            wp_send_json_success( array(
                'refresh_recaptcha' => true,
                'fragments'         => array(
                    '.ulp-login form .ulp-form-row > [class^="ulp-flex-"]:not([class^="ulp-submit-field"],[class^="ulp-recaptcha-field"])' => array(
                        'method'  => 'hidden'
                    ),
                    '.ulp-login form .ulp-form-row' => array(
                        'content' => wp_ulike_pro_get_two_factor_field(),
                        'method'  => 'prepend'
                    ),
                    '.ulp-login form .ulp-form-row' => array(
                        'content' => wp_ulike_pro_get_two_factor_field(),
                        'method'  => 'prepend'
                    )
                )
            ) );
        }
    }

}
add_action( 'wp_ulike_pro_before_login_process', 'wp_ulike_pro_validate_two_factor', 20, 1 );


/**
 * Add help links at end of the forms
 *
 * @param string $type
 * @param object $args
 * @return void
 */
function wp_ulike_pro_forms_end_hook( $type, $args ){

    switch ($type) {

        case 'login':
            if( get_option( 'users_can_register' ) ){
                ?>
<div class="ulp-flex-col-xl-12 ulp-helper ulp-flex-col-md-12 ulp-flex-col-xs-12">
    <div class="ulp-flex ulp-flex-center-xs">
        <span><?php echo sprintf( '%s <a %s href="%s">%s</a>', $args->signup_message, wp_ulike_is_true( $args->ajax_toggle ) ? 'data-form-toggle="signup"' : '',  WP_Ulike_Pro_Options::getSignUpPageUrl(), $args->signup_text ); ?></span>
    </div>
</div>
<?php
            }
            break;

        case 'signup':
            if( get_option( 'users_can_register' ) ){
                ?>
<div class="ulp-flex-col-xl-12 ulp-helper ulp-flex-col-md-12 ulp-flex-col-xs-12">
    <div class="ulp-flex ulp-flex-center-xs">
        <span><?php echo sprintf( '%s <a %s href="%s">%s</a>', $args->login_message, wp_ulike_is_true( $args->ajax_toggle ) ? 'data-form-toggle="login"' : '', WP_Ulike_Pro_Permalinks::get_login_url(), $args->login_text ); ?></span>
    </div>
</div>
<?php
            }
            break;

        case 'reset-password':
                ?>
<div class="ulp-flex-col-xl-12 ulp-helper ulp-flex-col-md-12 ulp-flex-col-xs-12">
    <div class="ulp-flex ulp-flex-center-xs">
        <span><?php echo sprintf( '<a %s href="%s">&larr;  %s</a>', wp_ulike_is_true( $args->ajax_toggle ) ? 'data-form-toggle="login"' : '', WP_Ulike_Pro_Permalinks::get_login_url(), $args->login_message ); ?></span>
    </div>
</div>
<?php
            break;

    }

}
add_action( 'wp_ulike_pro_forms_after_hook', 'wp_ulike_pro_forms_end_hook', 10, 2 );

/**
 * register wp_ulike_pro in mycred setup
 *
 * @param array $installed
 * @return array
 */
function wp_ulike_pro_register_mycred_hook( $installed ) {
    // Add pro widget
    $installed['wp_ulike_pro'] = array(
        'title'       => WP_ULIKE_NAME . ' : ' .  esc_html__( 'Points for dis-liking content', WP_ULIKE_PRO_DOMAIN ),
        'description' => esc_html__( 'This hook award / deducts points from users who Dislike/Undislike any content of WordPress, bbPress, BuddyPress & ...', WP_ULIKE_PRO_DOMAIN ),
        'callback'    => array( 'WP_Ulike_Pro_myCRED' )
    );

    return $installed;
}
add_filter( 'mycred_setup_hooks', 'wp_ulike_pro_register_mycred_hook' );

/**
 * Add ulike references
 *
 * @param array $list
 * @return array
 */
function wp_ulike_pro_mycred_references( $list ) {
    // Add our custom list
    $list['wp_add_dislike']   = esc_html__( 'Disliking Content', WP_ULIKE_PRO_DOMAIN );
    $list['wp_get_dislike']   = esc_html__( 'Disliking Content', WP_ULIKE_PRO_DOMAIN );
    $list['wp_add_undislike'] = esc_html__( 'Undisliking Content', WP_ULIKE_PRO_DOMAIN );
    $list['wp_get_undislike'] = esc_html__( 'Undisliking Content', WP_ULIKE_PRO_DOMAIN );

    return $list;
}
add_filter( 'mycred_all_references', 'wp_ulike_pro_mycred_references' );

/**
 * Add voting buttons to standard attachments
 *
 * @param string       $html          HTML img element or empty string on failure.
 * @param int          $attachment_id Image attachment ID.
 * @param string|int[] $size          Requested image size. Can be any registered image size name, or
 *                                    an array of width and height values in pixels (in that order).
 * @param bool         $icon          Whether the image should be treated as an icon.
 * @param string[]     $attr          Array of attribute values for the image markup, keyed by attribute name.
 *                                    See wp_get_attachment_image().
 * @return void
 */
function wp_ulike_pro_add_votings_for_attachments( $html, $attachment_id, $size, $icon, $attr ){
    // If has button then add it to html content
    if( WP_Ulike_Pro_Options::isAttachmentVisible( $attachment_id, $size, $attr ) ){
        $html .= wp_ulike( 'put', array(
            'id' => $attachment_id
        ) );
    }

    return $html;
}
add_filter( 'wp_get_attachment_image', 'wp_ulike_pro_add_votings_for_attachments', 10, 5 );

/**
 * Add modal pile template for inline display
 *
 * @param array $args
 * @param array $settings
 * @return void
 */
function wp_ulike_pro_add_pile_up_likers_list( $args, $settings ){
    // Extract settings
    extract( $settings );

    // If pile modal template selected
    if( $args['likers_style'] == 'pile' ){
        echo sprintf(
            '<div class="wp_ulike_likers_wrapper wp_ulike_pile_list_container wp_%s_likers_%s">%s</div>',
            $args['type'], $args['ID'], wp_ulike_get_likers_template( $table, $column, $args['ID'], $setting, array( 'style' => 'pile' ) )
        );
	}

}
add_action( 'wp_ulike_inline_display_likers_box', 'wp_ulike_pro_add_pile_up_likers_list', 10, 2 );

/**
 * Update likers template
 *
 * @param boolean $template
 * @param array $get_users
 * @param integer $item_id
 * @param array $args
 * @param string $table_name
 * @param string $column_name
 * @param array $options
 * @return boolean|string
 */
function wp_ulike_pro_add_pile_up_likers_template( $template, $get_users, $item_id, $args, $table_name, $column_name, $options ){

    // If pile modal template selected
    if( ! empty( $get_users ) && ( isset( $args['style'] ) && $args['style'] == 'pile' ) ) {
        $user_list = '';
        // Check has limit
        $has_limit = count( $get_users ) > $args['counter'];
        // Limit list
		$limit_users = $has_limit ? array_slice( $get_users, 0, $args['counter'] ) : $get_users;

        foreach ( $limit_users as $user ) {
            $user_info	= get_user_by( 'id', $user );
            // Check user existence
            if( ! $user_info ){
                continue;
            }

            $user_list .= sprintf( '<img src="%1$s" class="ulp-img-icon" title="%2$s" alt="%2$s" width="%3$d" height="%3$d"/>', get_avatar_url( $user_info->user_email, [ 'size' => $args['avatar_size'] ] ), esc_attr($user_info->display_name), $args['avatar_size'] );
        }

        // Add more icon
        if( $has_limit ){
            $user_list .= sprintf( '<img class="ulp-more-icon ulp-img-icon" alt="more" src="%1$s" width="%2$d" height="%2$d" />', WP_ULIKE_PRO_PUBLIC_URL . '/assets/img/svg/other/more.svg', $args['avatar_size']) ;
        }

        // Get item type
        $item_type = wp_ulike_get_type_by_table( $table_name );
        // Generate template
        $ajax_url  = esc_url( add_query_arg( array(
            'action' => 'ulp_likers',
            'id'     => $item_id,
            'type'   => $item_type
        ), admin_url( 'admin-ajax.php' ) ) );

        // Update template content and create modal attr
        $template = sprintf( '<div class="ulp-pile-list" data-ulpmodal-type="ajax" data-ulpmodal="%s">%s</div>',  $ajax_url, $user_list );
    }

    return $template;
}
add_filter( 'wp_ulike_get_likers_template', 'wp_ulike_pro_add_pile_up_likers_template', 10, 7 );

/**
 * Add filter on auto display funcitonality
 *
 * @param boolean $status
 * @param string $type
 * @return boolean
 */
function wp_ulike_pro_auto_display_filter( $status, $type ){
    if( $type == 'post' ){
        // Disable auto display functionality on core pages
        if( WP_Ulike_Pro_Options::isCorePage() ){
            return false;
        }
    }

    return $status;
}
add_filter( 'wp_ulike_enable_auto_display', 'wp_ulike_pro_auto_display_filter', 10, 2 );

/**
 * On wp_update_user function complete
 *
 * @param int $user_id
 * @param \WP_User $old_data
 */
function wp_ulike_pro_on_profile_update( $user_id ) {
    // Bail if no user ID was passed
    if ( empty( $user_id ) ) {
        return;
    }

    //Update permalink
    $user = new WP_Ulike_Pro_User();
    $user->generate_profile_slug( $user_id, true );
}
add_action( 'profile_update', 'wp_ulike_pro_on_profile_update', 10, 1 ); // user_id and old_user_data


/**
 * Add a filter to prevent users with the "Pending" role from logging in.
 *
 * @param object $user
 * @param string $password
 * @return object
 */
function check_pending_user_status($user, $password) {
    // Check if user exists and has 'pending' role
    if (isset($user->ID)) {
        $roles = $user->roles;
        if (in_array('pending', $roles)) {
            // If the user has a 'pending' role, return an error
            return new WP_Error('pending_account', WP_Ulike_Pro_Options::getNoticeMessage( 'account_not_verified_notice', esc_html__( 'Your account is not yet verified. Please check your email for the verification link.', WP_ULIKE_PRO_DOMAIN ) ) );
        }
    }

    // Proceed with the default authentication process if no issues
    return $user;
}
add_filter('wp_authenticate_user', 'check_pending_user_status', 10, 2);

/**
 * Localize rewrite rules to support wpml
 *
 * @param array $rules
 * @return array
 */
function wp_ulike_localize_rewrite_rules( $rules ){
    if ( wp_ulike_is_wpml_active() && wp_ulike_setting_repo::isWpmlSynchronizationOn() ) {
        $current_language = apply_filters( 'wpml_current_language', null );

        foreach ( $rules as $key => $value ) {
            // Check if 'lang' is already present in the value
            if ( strpos( $value, 'lang=' ) === false ) {
                // Append the lang parameter to the existing query string
                $rules[$key] = $value . '&lang=' . $current_language;
            }
        }
    }

    return $rules;
}
add_filter( 'wp_ulike_pro_rewrite_rules', 'wp_ulike_localize_rewrite_rules', 10, 1 );


/**
 * Processes and updates the location (country code) and device type data
 * based on the user's IP address and device information. This method is
 * triggered on both the 'wp_ulike_data_inserted' and 'wp_ulike_data_updated' actions.
 *
 * It uses a geo-location service to determine the user's country code from the
 * IP address and a device detection library to determine whether the user is
 * using a mobile, tablet, or desktop device. These values are then stored in
 * the respective columns (`country_code` and `device_type`) of the `ulike` table.
 *
 * @param array $data The data passed by the action hook, including:
 * - 'item_id'        => The ID of the item being inserted or updated.
 * - 'table'          => The table name where the data insert or update occurred.
 * - 'related_column' => The column name related to the insert or update action.
 * - 'type'           => The type of the item being inserted or updated.
 * - 'user_id'        => The ID of the user who performed the action.
 * - 'status'         => The status of the action (e.g., like, dislike).
 * - 'ip'             => The IP address of the user performing the action.
 *
 * The method updates the 'country_code' and 'device_type' columns in the
 * database for the specified item, adding the following information:
 * - Country code determined by the user's IP address (e.g., 'US', 'GB').
 * - Device type based on the user's device.
 *
 * @return void
 */
function wp_ulike_pro_process_and_update_location_and_device_data( $data ) {
    global $wpdb;

    // Extract necessary data
    $table          = $data['table'];
    $item_id        = $data['item_id'];
    $related_column = $data['related_column'];
    $user_id        = $data['user_id'];
    $ip_address     = $data['ip'];

    // 1. Get the country code using the IP address
    $country_code = wp_ulike_pro_get_country_code_from_ip( $ip_address );

    // 2. Get the device info based on the user agent
    $device_info = wp_ulike_pro_get_device_info();

    // 3. Update the database with the new country code and device type
    $wpdb->update(
        $table,
        array(
            'country_code' => $country_code,
            'device'       => $device_info['device'] ?? NULL,
            'os'           => $device_info['os'] ?? NULL,
            'browser'      => $device_info['browser'] ?? NULL,
        ),
        array(
            $related_column => $item_id,
            'user_id'       => $user_id,
        )
    );
}
add_action( 'wp_ulike_data_inserted', 'wp_ulike_pro_process_and_update_location_and_device_data', 10, 1 );
add_action( 'wp_ulike_data_updated', 'wp_ulike_pro_process_and_update_location_and_device_data', 10, 1 );

/**
 * change wordpress custom login url
 *
 * @param string $login_url
 * @param string $redirect
 * @param boolean $force_reauth
 * @return string
 */
// function wp_ulike_pro_custom_login_url( $login_url, $redirect, $force_reauth ) {
//     if( WP_Ulike_Pro_Options::isWpLoginRedirectEnabled() && WP_Ulike_Pro_Options::getLoginPageUrl() ){
//         // Load the home page url
//         $login_url = WP_Ulike_Pro_Permalinks::get_login_url();
//         // if has redirect
//         if( ! empty( $redirect ) ){
//             $login_url = esc_url( add_query_arg( array(
//                 'redirect_to' => $redirect,
//             ), $login_url ) );
//         }
//     }

//     return $login_url;
// }
// add_filter( 'login_url', 'wp_ulike_pro_custom_login_url', 10, 3 );