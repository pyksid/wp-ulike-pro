<?php
/**
 * Shortcodes manager
 *
 * 
 * @package    wp-ulike-pro
 * @author     TechnoWich 2024
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

    // enqueue scripts
    wp_enqueue_script( 'ulp-uploader' );
    wp_enqueue_style( 'ulp-uploader' );

    // Load template
    return wp_ulike_pro_get_public_template( 'profile', $args['user_id'] );
}
add_shortcode( 'wp_ulike_pro_completeness_profile', 'wp_ulike_pro_profile_shortcode' );


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

    extract( $args );

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
            foreach ($user_info as $key => $value) {
                if( !empty( $value[0] ) ){
                    $unserialize_value = maybe_unserialize( $value[0] );
                    $raw_data[ $key ]  = array_count_values( array_filter( $unserialize_value ) );
                }
            }
            if( empty( $table ) ){
                foreach ($raw_data as $raw_key => $raw_value) {
                    $current_status_value = !empty( $raw_value[$status] ) ? $raw_value[$status] : 0;
                    $result += empty( $status ) ? array_sum( $raw_value ) : $current_status_value;
                }
            } else {
                $slug   = sprintf( 'user_%s_status', $table );
                if( isset( $raw_data[$slug] ) ){
                    $result = empty( $status ) ? array_sum( $raw_data[$slug] ) : $raw_data[$slug][$status];
                }
            }
        }

    } else {
        global $wpdb;
        $tables = array( 'ulike', 'ulike_comments', 'ulike_activities', 'ulike_forums' );
        $data   = array();

        foreach ( $tables as $t_key => $t_value ) {
            $get_query = $wpdb->get_row( sprintf( "SELECT * FROM %s WHERE `user_id` = '%s' ORDER BY id DESC LIMIT 1 ", $wpdb->prefix . $t_value, $user_id ), ARRAY_A );
            if( ! empty( $get_query ) ){
                $data[] = $get_query;
            }
        }

        if( !empty( $data ) ){
            $max_date = strtotime( $data[0]['date_time'] );
            $max_key  = 0;

            foreach ( $data as $d_key => $d_value ) {
                if( strtotime( $d_value['date_time'] ) > $max_date ){
                    $max_key  = $d_key;
                    $max_date = strtotime( $d_value['date_time'] );
                }
            }

            switch ($type) {
                case 'last_activity':
                    $date_i18n = date_i18n( 'Y-m-d H:i:s', $max_date );
                    $result    = human_time_diff( strtotime( $date_i18n ) );
                    break;

                case 'last_status':
                    $result = $data[$max_key]['status'];
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
    if( isset( $_GET['action'] ) && in_array(  $_GET['action'], array( 'checkemail', 'lostpassword', 'changepassword' ) ) ){
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

    // enqueue scripts
    wp_enqueue_script( 'ulp-uploader' );
    wp_enqueue_style( 'ulp-uploader' );

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
        "data-url"         => '',
        "data-title"       => '',
        "data-description" => '',
        "data-image"       => ''
    ), $atts );

    if( empty( $args['slug'] ) ){
        return esc_html__( 'Please select a slug for social share!', WP_ULIKE_PRO_DOMAIN );
    }

    $social_items = wp_ulike_get_option( 'social_share', array() );

    if( empty( $social_items ) ){
        return esc_html__( 'Social network are empty! Please check your configurations.', WP_ULIKE_PRO_DOMAIN );
    }

    $social_key = array_search( $args['slug'], array_column( $social_items, 'slug' ) );

    if( $social_key === false || empty( $social_items[ $social_key ]['buttons'] ) ){
        return esc_html__( 'Social network are empty! Please check your configurations.', WP_ULIKE_PRO_DOMAIN );
    }

    $view  = ! empty( $social_items[ $social_key ]['view'] ) ?  $social_items[ $social_key ]['view'] : 'icon_text';
    $skin  = ! empty( $social_items[ $social_key ]['skin'] ) ?  $social_items[ $social_key ]['skin'] : 'gradient';
    $color = ! empty( $social_items[ $social_key ]['color'] ) ?  $social_items[ $social_key ]['color'] : 'official';
    $shape = ! empty( $social_items[ $social_key ]['shape'] ) ?  $social_items[ $social_key ]['shape'] : 'square';

    $attrs = '';
    foreach ($args as $attr_name => $attr_value) {
        if( ! empty( $attr_value ) && strpos( $attr_name, 'data-' ) !== false ){
            $attrs .= sprintf( ' %s="%s"', $attr_name, esc_attr( $attr_value ) );
        }
    }

    ob_start();

    do_action( 'wp_ulike_pro_share_buttons_before', $args['slug'], $social_items[ $social_key ] );

    echo sprintf( '<div class="ulp-social-wrapper ulp-social-%s">', $args['slug'] );

    echo ! empty( $social_items[ $social_key ]['before'] ) ? do_shortcode( $social_items[ $social_key ]['before'] ) : '';

    echo sprintf( '<div class="ulp-social ulp-social-share ulp-social-skin-%s ulp-social-buttons-color-%s ulp-social-shape-%s ulp-social-view-%s">', $skin, $color, $shape, $view );
        foreach ( $social_items[ $social_key ]['buttons'] as $key => $value ) {
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
            <span class="ulp-share-btn-title"><?php echo $label; ?></span>
        </div>
        <?php endif; ?>

    </div>
</div>
<?php
    }
    echo '</div>';

    echo ! empty( $social_items[ $social_key ]['after'] ) ? do_shortcode( $social_items[ $social_key ]['after'] ) : '';

    echo '</div>';

    do_action( 'wp_ulike_pro_share_buttons_after', $args['slug'], $social_items[ $social_key ] );

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
                <?php echo $args['title']; ?>
            </h3>
        </div>
        <?php if( empty( $secrets ) || ( ! empty( $secrets ) && count( $secrets ) < $args['limit_accounts'] ) ) : ?>
        <div class="ulp-flex-col-xl-12 ulp-flex-col-md-12 ulp-flex-col-xs-12">
            <p class="ulp-description">
                <?php echo $args['description']; ?>
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
            <p class="ulp-description"><?php echo $args['limit_message']; ?></p>
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