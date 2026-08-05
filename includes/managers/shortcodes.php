<?php
/**
 * Shortcodes manager
 *
 *
 * @package    wp-ulike-pro
 * @author     TechnoWich 2026
 * @link       https://wpulike.com
 */


/**
 * Create shortcode: [wp_ulike_pro_completeness_profile]
 *
 * @param array $atts
 *
 * @return void
 */
function  wp_ulike_pro_profile_shortcode( $atts ){
    // Global variable
    global $wp_ulike_user_profile_id, $wp_ulike_pro_logged_in_user_id;
    // Default Args

    $args = shortcode_atts( array(
        "user_id"            => '',
        "force_current_user" => true
    ), $atts );

    $args['user_id'] = empty( $args['user_id'] ) ? wp_ulike_pro_get_current_user_id() : intval($args['user_id']);

	// Set user ID in global var
    $wp_ulike_user_profile_id = $args['user_id'];
    // Set current user id
    $wp_ulike_pro_logged_in_user_id = get_current_user_id();

    if( wp_ulike_is_true( $args['force_current_user'] ) && ! WP_Ulike_Pro::is_preview_mode() && ! is_admin() ){
        wp_set_current_user( $wp_ulike_user_profile_id );
    }

    // enqueue scripts (New standalone avatar uploader)
    if( WP_Ulike_Pro_Options::isLocalAvatars() ){
        wp_enqueue_script( 'ulp-uploader' );
        wp_enqueue_style( 'ulp-uploader' );
    }

    // Load template
    return wp_ulike_pro_get_public_template( 'profile', $args['user_id'] );
}
add_shortcode( 'wp_ulike_pro_completeness_profile', 'wp_ulike_pro_profile_shortcode' );


/**
 * Count string/int status values for array_count_values()-safe tallies.
 *
 * @param array $values Flat status map values.
 * @return array<string|int,int>
 */
function wp_ulike_pro_count_scalar_status_values( $values ) {
    if ( ! is_array( $values ) ) {
        return array();
    }

    $scalars = array();
    foreach ( $values as $value ) {
        if ( ( is_string( $value ) || is_int( $value ) ) && ! empty( $value ) ) {
            $scalars[] = $value;
        }
    }

    return empty( $scalars ) ? array() : array_count_values( $scalars );
}

/**
 * Count active engagement keys from nested user engagement meta.
 *
 * Shape: [ item_id => [ kind => [ engagement_key, status, ... ] ] ]
 *
 * @param array $cache Engagement meta cache.
 * @return array<string,int>
 */
function wp_ulike_pro_count_engagement_meta_statuses( $cache ) {
    if ( ! is_array( $cache ) ) {
        return array();
    }

    $counts = array();

    foreach ( $cache as $kinds ) {
        if ( ! is_array( $kinds ) ) {
            continue;
        }

        foreach ( $kinds as $state ) {
            if ( ! is_array( $state ) ) {
                continue;
            }

            if ( isset( $state['status'] ) && 'active' !== $state['status'] ) {
                continue;
            }

            $key = isset( $state['engagement_key'] ) ? sanitize_key( $state['engagement_key'] ) : '';
            if ( '' === $key ) {
                continue;
            }

            $counts[ $key ] = isset( $counts[ $key ] ) ? (int) $counts[ $key ] + 1 : 1;
        }
    }

    return $counts;
}

/**
 * Sum status tallies while excluding removed vote buckets (unlike/undislike).
 *
 * @param array $counts Status => count map.
 * @return int
 */
function wp_ulike_pro_sum_active_status_counts( $counts ) {
    if ( ! is_array( $counts ) ) {
        return 0;
    }

    $sum = 0;
    foreach ( $counts as $status_key => $count ) {
        if ( in_array( (string) $status_key, array( 'unlike', 'undislike' ), true ) ) {
            continue;
        }
        $sum += (int) $count;
    }

    return $sum;
}

/**
 * Create shortcode: [wp_ulike_pro_user_info]
 *
 * @param array $atts
 *
 * @return integer|string
 */
function  wp_ulike_pro_user_info_shortcode( $atts ){
    // Global variable
    global $wp_ulike_user_profile_id;

    // Default Args
    $args   = shortcode_atts( array(
        "user_id"     => '',
        "type"        => '',   // Contains: data_counter, last_activity, last_status
        "table"       => '',   // Contains: post, comment, activity, topic
        "status"      => '',   // Contains: like, dislike, unlike, undislike
        "before_text" => '',
        "after_text"  => '',
        "empty_text"  => ''
    ), $atts );

    // Extract variables safely instead of using extract()
    $user_id = isset( $args['user_id'] ) ? $args['user_id'] : '';
    $type = isset( $args['type'] ) ? $args['type'] : '';
    $table = isset( $args['table'] ) ? $args['table'] : '';
    $status = isset( $args['status'] ) ? $args['status'] : '';
    $before_text = isset( $args['before_text'] ) ? $args['before_text'] : '';
    $after_text = isset( $args['after_text'] ) ? $args['after_text'] : '';
    $empty_text = isset( $args['empty_text'] ) ? $args['empty_text'] : '';

    // Modify user ID
    $user_id = empty( $user_id ) ? $wp_ulike_user_profile_id : $user_id;
    $result  = '';

    if( empty( $user_id ) ){
        $user_id = wp_ulike_pro_get_current_user_id();
    }

    if( empty($type) || $type === 'data_counter' ){
        $user_info = wp_ulike_get_meta_data( $user_id, 'user' );
        $raw_data  = array();
        $result    = 0;

        if( is_array( $user_info ) ){
            foreach ( $user_info as $key => $value ) {
                if ( empty( $value[0] ) ) {
                    continue;
                }

                $unserialize_value = maybe_unserialize( $value[0] );
                if ( ! is_array( $unserialize_value ) ) {
                    continue;
                }

                $status_slug = '';
                $counts      = array();

                // Legacy vote history: user_{type}_status => [ item_id => 'like' ]
                if ( preg_match( '/_status$/', $key ) ) {
                    $status_slug = $key;
                    $counts      = wp_ulike_pro_count_scalar_status_values( $unserialize_value );
                // Engagement history: user_{type}_engagements => nested item/kind maps
                } elseif ( preg_match( '/_engagements$/', $key ) ) {
                    $status_slug = preg_replace( '/_engagements$/', '_status', $key );
                    $counts      = wp_ulike_pro_count_engagement_meta_statuses( $unserialize_value );
                }

                if ( '' === $status_slug || empty( $counts ) ) {
                    continue;
                }

                // Merge so legacy + engagement meta for the same type both contribute.
                if ( ! isset( $raw_data[ $status_slug ] ) ) {
                    $raw_data[ $status_slug ] = array();
                }

                foreach ( $counts as $status_key => $count ) {
                    $raw_data[ $status_slug ][ $status_key ] = ( isset( $raw_data[ $status_slug ][ $status_key ] ) ? (int) $raw_data[ $status_slug ][ $status_key ] : 0 ) + (int) $count;
                }
            }

            if ( empty( $table ) ) {
                foreach ( $raw_data as $raw_value ) {
                    $current_status_value = ! empty( $raw_value[ $status ] ) ? (int) $raw_value[ $status ] : 0;
                    $result += empty( $status ) ? wp_ulike_pro_sum_active_status_counts( $raw_value ) : $current_status_value;
                }
            } else {
                $slug = sprintf( 'user_%s_status', sanitize_key( $table ) );
                if ( isset( $raw_data[ $slug ] ) ) {
                    $result = empty( $status )
                        ? wp_ulike_pro_sum_active_status_counts( $raw_data[ $slug ] )
                        : ( isset( $raw_data[ $slug ][ $status ] ) ? (int) $raw_data[ $slug ][ $status ] : 0 );
                }
            }
        }

    } else {
        $activity = wp_ulike_pro_get_user_global_latest_activity( $user_id );

        if ( ! empty( $activity ) ) {
            switch ( $type ) {
                case 'last_activity':
                    $result = human_time_diff( strtotime( $activity['date_time'] ) );
                    break;

                case 'last_status':
                    $result = $activity['status'];
                    break;
            }
        }
    }

    return empty( $result ) && !is_numeric( $result ) ? wp_kses_post( $empty_text ) :  wp_kses_post( sprintf( '%s %s %s', $before_text, $result, $after_text ) );
}
add_shortcode( 'wp_ulike_pro_user_info', 'wp_ulike_pro_user_info_shortcode' );


/**
 * Create shortcode: [wp_ulike_pro_items]
 *
 * @param array $atts
 *
 * @return void
 */
function  wp_ulike_pro_items_shortcode( $atts ){
    // Global variable
    global $wp_ulike_query_args, $wp_ulike_user_profile_id;

    // Default Args
    $args   = shortcode_atts( array(
        "user_id"        => '',
        "anonymize_user" => false,
        "type"           => 'post',
        "rel_type"       => 'post',
        "status"         => 'like',
        "is_popular"     => false,
        "period"         => 'all',
        "style"          => 'default',
        "has_pagination" => false,
        "limit"          => 10,
        "empty_text"     => esc_html__( 'No Results Found!', WP_ULIKE_PRO_DOMAIN ),
        "desktop_column" => 1,
        "tablet_column"  => 1,
        "mobile_column"  => 1,
        "exclude"        => "thumbnail"
    ), $atts );

    $args['user_id'] = empty( $args['user_id'] ) ? $wp_ulike_user_profile_id : $args['user_id'];

    if( empty( $args['user_id'] ) ){
        if( wp_ulike_is_true( $args['anonymize_user'] ) ){
            $args['user_id'] = is_user_logged_in() ? get_current_user_id() : wp_ulike_generate_user_id( wp_ulike_get_user_ip() );
        } else {
            $args['user_id'] = wp_ulike_pro_get_current_user_id();
        }
    }

    // Set global var
    $wp_ulike_query_args = $args;

    // Load template
    return wp_ulike_pro_get_public_template( 'content', $args['user_id'] );
}
add_shortcode( 'wp_ulike_pro_items', 'wp_ulike_pro_items_shortcode' );

/**
 * Create shortcode: [wp_ulike_pro_login_form]
 *
 * @param array $atts
 *
 * @return string
 */
function  wp_ulike_pro_login_form_shortcode( $atts ){
    // Global variable
    global $wp_ulike_form_args;

    // check if requested for lostpassword
    // SECURITY: Sanitize action parameter
    $action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : '';
    $allowed_actions = array( 'checkemail', 'lostpassword', 'changepassword' );
    if( ! empty( $action ) && in_array( $action, $allowed_actions, true ) ){
        return wp_ulike_pro_reset_password_form_shortcode( $atts );
    }

    // Default Args
    $args = shortcode_atts( array(
        "form_id"        => 1,
        "username"       => WP_Ulike_Pro_Options::getFormLabel( 'login', 'username', esc_html__( 'Username', WP_ULIKE_PRO_DOMAIN ) ),
        "password"       => WP_Ulike_Pro_Options::getFormLabel( 'login', 'password',esc_html__( 'Password', WP_ULIKE_PRO_DOMAIN ) ),
        "remember"       => WP_Ulike_Pro_Options::getFormLabel( 'login', 'remember',esc_html__( 'Remember Me', WP_ULIKE_PRO_DOMAIN )),
        "button"         => WP_Ulike_Pro_Options::getFormLabel( 'login', 'button',esc_html__( 'Log in', WP_ULIKE_PRO_DOMAIN ) ),
        "reset_password" => WP_Ulike_Pro_Options::getFormLabel( 'login', 'reset_password',esc_html__( 'Forgot Password?', WP_ULIKE_PRO_DOMAIN ) ),
        "reset_url"      => WP_Ulike_Pro_Options::getResetPasswordPageUrl(),
        "signup_message" => WP_Ulike_Pro_Options::getFormLabel( 'login', 'signup_message',esc_html__( 'Don\'t have an account?', WP_ULIKE_PRO_DOMAIN ) ),
        "signup_text"    => WP_Ulike_Pro_Options::getFormLabel( 'login', 'signup_text',esc_html__( 'Create Account', WP_ULIKE_PRO_DOMAIN ) ),
        "redirect_to"    => '',
        "ajax_toggle"    => false
    ), $atts );

    // Check redirect page
    if( $args['redirect_to'] === 'current_page' ){
        $args['redirect_to'] = wp_ulike_pro_get_referer_url();
    }

    // Hash form id parameter for secutiry reasons
    $args['form_id'] = wp_ulike_pro_create_hash( $args['form_id'] );

    // Set global var
    $wp_ulike_form_args = (object) $args;

    // Load template
    return wp_ulike_pro_get_public_template( 'form/login' );
}
add_shortcode( 'wp_ulike_pro_login_form', 'wp_ulike_pro_login_form_shortcode' );


/**
 * Create shortcode: [wp_ulike_pro_signup_form]
 *
 * @param array $atts
 *
 * @return string
 */
function  wp_ulike_pro_signup_form_shortcode( $atts ){
    // Global variable
    global $wp_ulike_form_args;

    // Default Args
    $args = shortcode_atts( array(
        "form_id"       => 1,
        "username"      => WP_Ulike_Pro_Options::getFormLabel( 'signup', 'username', esc_html__( 'Username', WP_ULIKE_PRO_DOMAIN ) ),
        "firstname"     => WP_Ulike_Pro_Options::getFormLabel( 'signup', 'firstname', esc_html__( 'First Name', WP_ULIKE_PRO_DOMAIN ) ),
        "lastname"      => WP_Ulike_Pro_Options::getFormLabel( 'signup', 'lastname', esc_html__( 'Last Name', WP_ULIKE_PRO_DOMAIN ) ),
        "email"         => WP_Ulike_Pro_Options::getFormLabel( 'signup', 'email', esc_html__( 'Email Address', WP_ULIKE_PRO_DOMAIN ) ),
        "password"      => WP_Ulike_Pro_Options::getFormLabel( 'signup', 'password', esc_html__( 'Password', WP_ULIKE_PRO_DOMAIN ) ),
        "button"        => WP_Ulike_Pro_Options::getFormLabel( 'signup', 'button', esc_html__( 'Register', WP_ULIKE_PRO_DOMAIN ) ),
        "login_message" => WP_Ulike_Pro_Options::getFormLabel( 'signup', 'login_message', esc_html__( 'Already have an account?', WP_ULIKE_PRO_DOMAIN ) ),
        "login_text"    => WP_Ulike_Pro_Options::getFormLabel( 'signup', 'login_text', esc_html__( 'Sign In', WP_ULIKE_PRO_DOMAIN ) ),
        "redirect_to"   => '',
        "ajax_toggle"   => false
    ), $atts );

    // Check redirect page
    if( $args['redirect_to'] === 'current_page' ){
        $args['redirect_to'] = wp_ulike_pro_get_referer_url();
    }

    // Hash form id parameter for secutiry reasons
    $args['form_id'] = wp_ulike_pro_create_hash( $args['form_id'] );

    // Set global var
    $wp_ulike_form_args = (object) $args;

    // Load template
    return wp_ulike_pro_get_public_template( 'form/signup' );
}
add_shortcode( 'wp_ulike_pro_signup_form', 'wp_ulike_pro_signup_form_shortcode' );

/**
 * Create shortcode: [wp_ulike_pro_reset_password_form]
 *
 * @param array $atts
 *
 * @return string
 */
function  wp_ulike_pro_reset_password_form_shortcode( $atts ){
    // Global variable
    global $wp_ulike_form_args;

    // Default Args
    $args = shortcode_atts( array(
        "form_id"        => 1,
        "reset_message"  => WP_Ulike_Pro_Options::getFormLabel( 'rp', 'reset_message', esc_html__( 'To reset your password, please enter your email address or username below', WP_ULIKE_PRO_DOMAIN ) ),
        "change_message" => WP_Ulike_Pro_Options::getFormLabel( 'rp', 'change_message', esc_html__( 'Enter your new password below.', WP_ULIKE_PRO_DOMAIN ) ),
        "username"       => WP_Ulike_Pro_Options::getFormLabel( 'rp', 'username', esc_html__( 'Username or Email', WP_ULIKE_PRO_DOMAIN ) ),
        "new_pass"       => WP_Ulike_Pro_Options::getFormLabel( 'rp', 'new_pass', esc_html__( 'New Password', WP_ULIKE_PRO_DOMAIN ) ),
        "re_new_pass"    => WP_Ulike_Pro_Options::getFormLabel( 'rp', 're_new_pass', esc_html__( 'Re-enter New Password', WP_ULIKE_PRO_DOMAIN ) ),
        "reset_button"   => WP_Ulike_Pro_Options::getFormLabel( 'rp', 'reset_button', esc_html__( 'Get New Password', WP_ULIKE_PRO_DOMAIN ) ),
        "change_button"  => WP_Ulike_Pro_Options::getFormLabel( 'rp', 'change_button', esc_html__( 'Reset password', WP_ULIKE_PRO_DOMAIN ) ),
        "login_message"  => WP_Ulike_Pro_Options::getFormLabel( 'rp', 'login_message', esc_html__( 'Go to login page', WP_ULIKE_PRO_DOMAIN ) ),
        "ajax_toggle"    => false
    ), $atts );

    // Hash form id parameter for secutiry reasons
    $args['form_id'] = wp_ulike_pro_create_hash( $args['form_id'] );

    // Set global var
    $wp_ulike_form_args = (object) $args;

    // Load template
    return wp_ulike_pro_get_public_template( 'form/reset-password' );
}
add_shortcode( 'wp_ulike_pro_reset_password_form', 'wp_ulike_pro_reset_password_form_shortcode' );

/**
 * Create shortcode: [wp_ulike_pro_account_form]
 *
 * @param array $atts
 *
 * @return string
 */
function  wp_ulike_pro_account_form_shortcode( $atts ){
    // Global variable
    global $wp_ulike_form_args, $wp_ulike_user_profile_id, $wp_ulike_pro_logged_in_user_id;

    // Default Args
    $args = shortcode_atts( array(
        "form_id"            => 1,
        "firstname"          => WP_Ulike_Pro_Options::getFormLabel( 'ea', 'firstname', esc_html__( 'First Name', WP_ULIKE_PRO_DOMAIN ) ),
        "lastname"           => WP_Ulike_Pro_Options::getFormLabel( 'ea', 'lastname', esc_html__( 'Last Name', WP_ULIKE_PRO_DOMAIN ) ),
        "website"            => WP_Ulike_Pro_Options::getFormLabel( 'ea', 'website', esc_html__( 'Website', WP_ULIKE_PRO_DOMAIN ) ),
        "description"        => WP_Ulike_Pro_Options::getFormLabel( 'ea', 'description', esc_html__( 'Biographical Info', WP_ULIKE_PRO_DOMAIN ) ),
        "email"              => WP_Ulike_Pro_Options::getFormLabel( 'ea', 'email', esc_html__( 'Email Address', WP_ULIKE_PRO_DOMAIN ) ),
        "avatar"             => WP_Ulike_Pro_Options::getFormLabel( 'ea', 'avatar', esc_html__( 'Upload Avatar', WP_ULIKE_PRO_DOMAIN ) ),
        "button"             => WP_Ulike_Pro_Options::getFormLabel( 'ea', 'button', esc_html__( 'Submit', WP_ULIKE_PRO_DOMAIN ) ),
        "permission_message" => WP_Ulike_Pro_Options::getFormLabel( 'ea', 'permission_message', esc_html__( 'You don\'t have access to edit the information on this page!', WP_ULIKE_PRO_DOMAIN ))
    ), $atts );

    // Hash form id parameter for secutiry reasons
    $args['form_id'] = wp_ulike_pro_create_hash( $args['form_id'] );

    if( ! is_user_logged_in() ){
        return wpautop( $args['permission_message'] );
    }

    if( ! empty( $wp_ulike_user_profile_id ) ){
        if( $wp_ulike_user_profile_id !== $wp_ulike_pro_logged_in_user_id  ){
            return wpautop( $args['permission_message'] );
        }

        $args['user'] = get_userdata( $wp_ulike_user_profile_id );
    } else {
        $args['user'] = get_userdata( get_current_user_id() );
    }

    // Set global var
    $wp_ulike_form_args = (object) $args;

    // enqueue scripts (New standalone avatar uploader)
    if( WP_Ulike_Pro_Options::isLocalAvatars() ){
        wp_enqueue_script( 'ulp-uploader' );
        wp_enqueue_style( 'ulp-uploader' );
    }

    // Load template
    return wp_ulike_pro_get_public_template( 'form/profile' );
}
add_shortcode( 'wp_ulike_pro_account_form', 'wp_ulike_pro_account_form_shortcode' );


/**
 * Create shortcode: [wp_ulike_pro_social_share_shortcode]
 *
 * @param array $atts
 *
 * @return string
 */
function  wp_ulike_pro_social_share_shortcode( $atts ){

    // Default Args
    $args = shortcode_atts( array(
        "slug"             => '',
        "buttons"          => '',  // JSON string or comma-separated list of networks
        "view"             => '',  // Override view (icon_text, icon, text)
        "skin"             => '',  // Override skin (gradient, flat, etc.)
        "color"            => '',  // Override color (official, custom)
        "shape"            => '',  // Override shape (square, rounded, circle)
        "data-url"         => '',
        "data-title"       => '',
        "data-description" => '',
        "data-image"       => ''
    ), $atts );

    // Parse buttons if provided directly
    $buttons = array();
    if ( ! empty( $args['buttons'] ) ) {
        // Try to decode as JSON first
        $decoded = json_decode( $args['buttons'], true );
        if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded ) ) {
            $buttons = $decoded;
        } else {
            // Fallback: treat as comma-separated list
            $networks = array_map( 'trim', explode( ',', $args['buttons'] ) );
            foreach ( $networks as $network ) {
                if ( ! empty( $network ) ) {
                    $buttons[] = array( 'network' => $network, 'label' => ucfirst( $network ) );
                }
            }
        }
    }

    // Get from options if buttons not provided directly
    $social_items = wp_ulike_get_option( 'social_share', array() );
    $social_key = false;
    $social_item = null;

    if ( ! empty( $args['slug'] ) && ! empty( $social_items ) ) {
        $social_key = array_search( $args['slug'], array_column( $social_items, 'slug' ) );
        if ( $social_key !== false && ! empty( $social_items[ $social_key ] ) ) {
            $social_item = $social_items[ $social_key ];
            // Use buttons from options if not provided directly
            if ( empty( $buttons ) && ! empty( $social_item['buttons'] ) ) {
                $buttons = $social_item['buttons'];
            }
        }
    }

    // If still no buttons, return error
    if ( empty( $buttons ) ) {
        return esc_html__( 'Social network are empty! Please provide buttons parameter or configure in settings.', WP_ULIKE_PRO_DOMAIN );
    }

    // Determine display options (use params first, then options, then defaults)
    $view  = ! empty( $args['view'] ) ? $args['view'] : ( ! empty( $social_item['view'] ) ? $social_item['view'] : 'icon_text' );
    $skin  = ! empty( $args['skin'] ) ? $args['skin'] : ( ! empty( $social_item['skin'] ) ? $social_item['skin'] : 'gradient' );
    $color = ! empty( $args['color'] ) ? $args['color'] : ( ! empty( $social_item['color'] ) ? $social_item['color'] : 'official' );
    $shape = ! empty( $args['shape'] ) ? $args['shape'] : ( ! empty( $social_item['shape'] ) ? $social_item['shape'] : 'square' );
    $slug  = ! empty( $args['slug'] ) ? $args['slug'] : 'preview';

    $attrs = '';
    foreach ($args as $attr_name => $attr_value) {
        if( ! empty( $attr_value ) && strpos( $attr_name, 'data-' ) !== false ){
            $attrs .= sprintf( ' %s="%s"', $attr_name, esc_attr( $attr_value ) );
        }
    }

    ob_start();

    $social_data = $social_item ? $social_item : array();
    do_action( 'wp_ulike_pro_share_buttons_before', $slug, $social_data );

    echo sprintf( '<div class="ulp-social-wrapper ulp-social-%s">', esc_attr( $slug ) );

    echo ! empty( $social_data['before'] ) ? do_shortcode( $social_data['before'] ) : '';

    echo sprintf( '<div class="ulp-social ulp-social-share ulp-social-skin-%s ulp-social-buttons-color-%s ulp-social-shape-%s ulp-social-view-%s">', esc_attr( $skin ), esc_attr( $color ), esc_attr( $shape ), esc_attr( $view ) );
        foreach ( $buttons as $key => $value ) {
                // Check network exist
                if( empty( $value['network'] ) ){
                    continue;
                }
                // Set label
                $label = ! empty( $value['label'] ) ? $value['label'] : ucfirst( $value['network'] );
            ?>
<div class="ulp-social-item">
    <div class="ulp-share-btn ulp-share-<?php echo esc_attr( $value['network'] ); ?>"
        data-social="<?php echo esc_attr( $value['network'] ); ?>" <?php echo $attrs; ?>>

        <?php if( in_array( $view, array( 'icon_text', 'icon' ) ) ): ?>
        <span class="ulp-share-btn-icon">
            <i class="ulp-icon-<?php echo esc_attr( $value['network'] ); ?>"></i>
            <span class="ulp-screen-only"><?php echo esc_attr( $label ); ?></span>
        </span>
        <?php endif; ?>

        <?php if( in_array( $view, array( 'icon_text', 'text' ) ) ): ?>
        <div class="ulp-share-btn-text">
            <span class="ulp-share-btn-title"><?php echo esc_html( $label ); ?></span>
        </div>
        <?php endif; ?>

    </div>
</div>
<?php
    }
    echo '</div>';

    echo ! empty( $social_data['after'] ) ? do_shortcode( $social_data['after'] ) : '';

    echo '</div>';

    do_action( 'wp_ulike_pro_share_buttons_after', $slug, $social_data );

    return ob_get_clean();
}
add_shortcode( 'wp_ulike_pro_social_share', 'wp_ulike_pro_social_share_shortcode' );

/**
 * Create shortcode: [wp_ulike_pro_two_factor_setup]
 *
 * @param array $atts
 *
 * @return string
 */
function  wp_ulike_pro_two_factor_shortcode( $atts ){
    // Global variable
    global $wp_ulike_user_profile_id, $wp_ulike_pro_logged_in_user_id;

    // check two factor is enabled
    if( ! WP_Ulike_Pro_Options::is2FactorAuthEnabled() ){
        return wpautop( esc_html__( '2-factor support is not enabled!', WP_ULIKE_PRO_DOMAIN ) );
    }

    $description = sprintf( 'Download the free %s app, add a new account, then scan this barcode to set up your account.',
    sprintf( '<a target="_blank" href="https://support.google.com/accounts/answer/1066447?hl=en">%s</a>', esc_html__( 'Google Authenticator', WP_ULIKE_PRO_DOMAIN ) ) );

    // Default Args
    $args = shortcode_atts( array(
        "title"              => esc_html__( 'Setup 2-factor Authentication', WP_ULIKE_PRO_DOMAIN ),
        "description"        => $description,
        "accounts_title"     => esc_html__( 'Usable authentication accounts', WP_ULIKE_PRO_DOMAIN ),
        "app_name"           => esc_html__( 'Authenticator app', WP_ULIKE_PRO_DOMAIN ),
        "ago_text"           => esc_html__( 'ago', WP_ULIKE_PRO_DOMAIN ),
        "button"             => esc_html__( 'Submit', WP_ULIKE_PRO_DOMAIN ),
        "limit_accounts"     => 5,
        "qrcode_size"        => 256,
        "limit_message"      => esc_html__( 'You have reached the limit for requesting authentication acccounts for this user. If you want to update your authentication account, try to remove some of the following apps.', WP_ULIKE_PRO_DOMAIN ),
        "permission_message" => esc_html__( 'You don\'t have access to edit the information on this page!', WP_ULIKE_PRO_DOMAIN )
    ), $atts );

    if( ! is_user_logged_in() ){
        return wpautop( $args['permission_message'] );
    }

    if( ! empty( $wp_ulike_user_profile_id ) ){
        if( $wp_ulike_user_profile_id !== $wp_ulike_pro_logged_in_user_id  ){
            return wpautop( $args['permission_message'] );
        }
        $args['user'] = get_userdata( $wp_ulike_user_profile_id );
    } else {
        $args['user'] = get_userdata( get_current_user_id() );
    }

    $tfa    = new RobThree\Auth\TwoFactorAuth();
    $secret = $tfa->createSecret();
    $label  = apply_filters( 'wp_ulike_pro_two_factor_app_label', sprintf( '%s (%s)', get_bloginfo( 'name' ), $args['user']->user_email ) );

    // get user secrets list
    $secrets = get_user_meta(  $args['user']->ID, 'ulp_two_factor_secrets', true );

    // nonce field
    $nonce = wp_create_nonce('wp_ulike_pro_two_factor_nonce_field');

    ob_start();
    ?>
<div class="ulp-2fa-wrapper">
    <div class="ulp-flex-row ulp-flex-middle-xs">
        <div class="ulp-flex-col-xl-12 ulp-flex-col-md-12 ulp-flex-col-xs-12">
            <h3 class="ulp-title">
                <?php echo esc_html( $args['title'] ); ?>
            </h3>
        </div>
        <?php if( empty( $secrets ) || ( ! empty( $secrets ) && count( $secrets ) < $args['limit_accounts'] ) ) : ?>
        <div class="ulp-flex-col-xl-12 ulp-flex-col-md-12 ulp-flex-col-xs-12">
            <p class="ulp-description">
                <?php echo wp_kses_post( $args['description'] ); ?>
            </p>
        </div>
        <div class="ulp-flex-col-xl-4 ulp-flex-col-md-4 ulp-flex-col-xs-12">
            <img class="ulp-qrcode"
                src="<?php echo $tfa->getQRCodeImageAsDataUri( $label , $secret, $args['qrcode_size'] ); ?>">
        </div>
        <div class="ulp-flex-col-xl-8 ulp-flex-col-md-8 ulp-flex-col-xs-12">
            <div class="ulp-form ulp-ajax-form ulp-2fa-form">
                <form method="post" action="">
                    <div class="ulp-flex-row ulp-flex-middle-xs">
                        <?php echo wp_ulike_pro_get_two_factor_field(); ?>
                        <div class="ulp-flex-col-xl-12 ulp-flex-col-md-12 ulp-flex-col-xs-12">
                            <div class="ulp-flex ulp-flex-center-xs">
                                <input id="ulp-submit-code" value="<?php echo esc_attr( $args['button'] ); ?>"
                                    class="ulp-button" type="submit" name="submit" />
                            </div>
                        </div>
                        <input type="hidden" name="action" value="ulp_two_factor_validation" />
                        <input type="hidden" name="secret" value="<?php echo esc_attr( $secret ); ?>" />
                        <input type="hidden" name="nonce" value="<?php echo esc_attr( $nonce ); ?>" />
                    </div>
                </form>
            </div>
        </div>
        <?php else : ?>
        <div class="ulp-flex-col-xl-12 ulp-flex-col-md-12 ulp-flex-col-xs-12">
            <p class="ulp-description"><?php echo wp_kses_post( $args['limit_message'] ); ?></p>
        </div>
        <?php endif; ?>
        <?php
        if( ! empty( $secrets ) ){
            $secrets_list = '';
            foreach ( $secrets as $secret_value => $secret_args ) {
                $secrets_list .= sprintf( '
                <div class="ulp-2fa-item">
                    <strong class="ulp-2fa-name">%s</strong> <small>( %s %s )</small>
                    <small class="ulp-2fa-info">%s</small>
                    <a href="#" class="ulp-2fa-remove" data-key="%s" data-nonce="%s" tabindex="0" title="remove" role="button">remove</a>
                </div>', $args['app_name'], human_time_diff( $secret_args['created_at'], current_time( 'timestamp' ) ), $args['ago_text'], esc_html( $secret_args['user_agent'] ), esc_attr( $secret_value ), esc_attr( $nonce ) );
            }
            // echo list
            echo sprintf( '
            <div class="ulp-flex-col-xl-12 ulp-flex-col-md-12 ulp-flex-col-xs-12">
                <h3 class="ulp-title">%s</h3>
                <div class="ulp-2fa-list">%s</div>
            </div>', $args['accounts_title'], $secrets_list );
        }
        ?>
    </div>
</div>
<?php
    return ob_get_clean();
}
add_shortcode( 'wp_ulike_pro_two_factor_setup', 'wp_ulike_pro_two_factor_shortcode' );


/**
 * Create shortcode: [wp_ulike_pro_social_login]
 *
 * @param array $atts
 *
 * @return string
 */
function  wp_ulike_pro_social_login_shortcode( $atts ){
    // Default Args
    $args = shortcode_atts( array(
        "before" => null,
        "after"  => null,
        "view"   => null,   // icon_text, icon, text
        "skin"   => null,   // gradient, minimal, framed, boxed, flat
        "shape"  => null,   // square, rounded, circle
        "color"  => null    // official, custom
    ), $atts );

    return wp_ulike_pro_get_social_logins( $args );
}
add_shortcode( 'wp_ulike_pro_social_login', 'wp_ulike_pro_social_login_shortcode' );

/**
 * Shortcode: [wp_ulike_pro_engagements]
 *
 * @param array $atts Shortcode attributes.
 * @return string
 */
function wp_ulike_pro_engagements_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'id'   => 0,
			'type' => 'post',
		),
		$atts,
		'wp_ulike_pro_engagements'
	);

	$item_id   = absint( $atts['id'] );
	$item_type = sanitize_key( $atts['type'] );

	if ( ! $item_id ) {
		if ( 'post' === $item_type ) {
			$item_id = get_the_ID();
		} elseif ( 'comment' === $item_type ) {
			$item_id = get_comment_ID();
		}
	}

	if ( ! $item_id ) {
		return '';
	}

	return wp_ulike_pro_engagements( $item_id, $item_type );
}
add_shortcode( 'wp_ulike_pro_engagements', 'wp_ulike_pro_engagements_shortcode' );