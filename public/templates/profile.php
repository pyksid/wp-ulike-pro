<?php
/**
 * User profile template
 *
 */
if ( ! defined( 'ABSPATH' ) ) exit;

global $wp_ulike_user_profile_id, $wp_ulike_pro_logged_in_user_id;

$get_user_id  = empty( $wp_ulike_user_profile_id ) ? wp_ulike_pro_get_current_user_id() : $wp_ulike_user_profile_id;
$get_userdata = ! empty( $get_user_id ) ? get_userdata( $get_user_id ) : NULL;
$current_tab  = get_query_var( 'wp_ulike_profile_tab' );

// Get options
$options  = wp_ulike_get_option( 'user_profiles_appearance', array() );

$header_wrapper_width =  ! empty( $options['header_wrapper_width'] ) ? $options['header_wrapper_width'] : array(
    'desktop' => '12',
    'tablet'  => '12',
    'mobile'  => '12'
);
?>
<!-- user-profile -->
<?php wp_ulike_pro_print_notices(); ?>

<div class="wp-ulike-pro-section-profile ulp-flex-row ulp-flex-top-xs">

    <?php do_action( 'wp_ulike_pro_profile_before_hook', $get_userdata ); ?>

    <div
        class="wp-ulike-pro-header-section ulp-flex-row ulp-flex-center-xs ulp-flex-col-xl-<?php echo esc_attr( $header_wrapper_width['desktop'] ); ?> ulp-flex-col-md-<?php echo esc_attr( $header_wrapper_width['tablet'] ); ?> ulp-flex-col-xs-<?php echo esc_attr( $header_wrapper_width['mobile'] ); ?>">

        <?php if( !empty( $get_userdata ) ) :
        $header_info_width =  ! empty( $options['header_info_width'] ) ? $options['header_info_width'] : array(
            'desktop' => '12',
            'tablet'  => '12',
            'mobile'  => '12'
        );
        ?>
        <!-- profile-header -->
        <div
            class="wp-ulike-pro-info-section ulp-flex-col-xl-<?php echo esc_attr( $header_info_width['desktop'] ); ?> ulp-flex-col-md-<?php echo esc_attr( $header_info_width['tablet'] ); ?> ulp-flex-col-xs-<?php echo esc_attr( $header_info_width['mobile'] ); ?>">
            <div class="ulp-flex-row ulp-flex-middle-xs ulp-flex-center-xs ulp-flex-start-md">
                <?php if( ! empty( $options['display_avatar'] ) && wp_ulike_is_true( $options['display_avatar'] ) ):
                    $avatar_width =  ! empty( $options['avatar_width'] ) ? $options['avatar_width'] : array(
                        'desktop' => '3',
                        'tablet'  => '3',
                        'mobile'  => '12'
                    );
                ?>
                <!-- avatar -->
                <div
                    class="ulp-flex-col-xl-<?php echo esc_attr( $avatar_width['desktop'] ); ?> ulp-flex-col-md-<?php echo esc_attr( $avatar_width['tablet'] ); ?> ulp-flex-col-xs-<?php echo esc_attr( $avatar_width['mobile'] ); ?> wp-ulike-pro-header-avatar-col">
                    <div class="wp-ulike-pro-profile-user-avatar">
                        <?php if( (int) $get_user_id === (int) $wp_ulike_pro_logged_in_user_id && WP_Ulike_Pro_Options::isLocalAvatars() ) : ?>
                        <?php echo WP_Ulike_Pro_Avatar::get_avatar_uploader( $get_user_id, [ 'size' => !empty( $options['avatar_size'] ) ? $options['avatar_size'] : 200  ] );?>
                        <?php else : ?>
                        <?php echo WP_Ulike_Pro_Avatar::get_avatar( $get_userdata->user_email, !empty( $options['avatar_size'] ) ? $options['avatar_size'] : 200 );?>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php do_action( 'wp_ulike_pro_profile_after_avatar', $get_userdata ); ?>

                <?php if( ! empty( $options['display_info'] ) && wp_ulike_is_true( $options['display_info'] ) ):
                    $info_width =  ! empty( $options['info_width'] ) ? $options['info_width'] : array(
                        'desktop' => '9',
                        'tablet'  => '9',
                        'mobile'  => '12'
                    );
                ?>
                <!-- info -->
                <div
                    class="ulp-flex-col-xl-<?php echo esc_attr( $info_width['desktop'] ); ?> ulp-flex-col-md-<?php echo esc_attr( $info_width['tablet'] ); ?> ulp-flex-col-xs-<?php echo esc_attr( $info_width['mobile'] ); ?> wp-ulike-pro-header-info-col">
                    <div class="wp-ulike-pro-profile-user-info">
                        <div class="wp-ulike-pro-profile-header-top">
                            <div class="wp-ulike-pro-profile-header-left">
                                <?php if( ! empty( $options['display_name'] ) && wp_ulike_is_true( $options['display_name'] ) ): ?>
                                <!-- name -->
                                <h3 class="wp-ulike-pro-profile-name"><?php echo esc_html( $get_userdata->display_name ); ?></h3>
                                <?php endif; ?>
                            </div>

                            <?php if( (int) $get_user_id === (int) $wp_ulike_pro_logged_in_user_id ): ?>
                            <!-- action buttons for profile owner -->
                            <div class="wp-ulike-pro-profile-actions">
                                <?php
                                $edit_account_page = WP_Ulike_Pro_Options::getEditAccountPage();
                                if( ! empty( $edit_account_page ) ):
                                    $edit_account_url = get_permalink( $edit_account_page );
                                ?>
                                <a href="<?php echo esc_url( $edit_account_url ); ?>" class="wp-ulike-pro-profile-action-btn wp-ulike-pro-profile-edit-btn" title="<?php echo esc_attr__( 'Edit Profile', WP_ULIKE_PRO_DOMAIN ); ?>" aria-label="<?php echo esc_attr__( 'Edit Profile', WP_ULIKE_PRO_DOMAIN ); ?>">
                                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                        <path d="M11.3333 2.00004C11.5084 1.82493 11.7163 1.68607 11.9441 1.59131C12.1719 1.49655 12.4151 1.44775 12.6667 1.44775C12.9182 1.44775 13.1614 1.49655 13.3892 1.59131C13.617 1.68607 13.8249 1.82493 14 2.00004C14.1751 2.17515 14.314 2.38305 14.4087 2.61087C14.5035 2.83869 14.5523 3.08189 14.5523 3.33337C14.5523 3.58486 14.5035 3.82806 14.4087 4.05588C14.314 4.2837 14.1751 4.4916 14 4.66671L5.00001 13.6667L1.33334 14.6667L2.33334 11L11.3333 2.00004Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    <span><?php echo esc_html( wp_ulike_get_option( 'avatar_edit_text', esc_html__( 'Edit', WP_ULIKE_PRO_DOMAIN ) ) ); ?></span>
                                </a>
                                <?php endif; ?>

                                <?php
                                $logout_url = WP_Ulike_Pro_Permalinks::get_logout_url();
                                if( ! empty( $logout_url ) ):
                                ?>
                                <a href="<?php echo esc_url( $logout_url ); ?>" class="wp-ulike-pro-profile-action-btn wp-ulike-pro-profile-logout-btn" title="<?php echo esc_attr__( 'Log Out', WP_ULIKE_PRO_DOMAIN ); ?>" aria-label="<?php echo esc_attr__( 'Log Out', WP_ULIKE_PRO_DOMAIN ); ?>">
                                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                        <path d="M6 14H3.33333C2.89131 14 2.46738 13.8244 2.15482 13.5118C1.84226 13.1993 1.66667 12.7754 1.66667 12.3333V3.66667C1.66667 3.22464 1.84226 2.80072 2.15482 2.48816C2.46738 2.17559 2.89131 2 3.33333 2H6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M10.6667 11.3333L14.3333 7.66667L10.6667 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M14.3333 7.66667H6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    <span><?php echo esc_html( wp_ulike_get_option( 'avatar_logout_text', esc_html__( 'Log Out', WP_ULIKE_PRO_DOMAIN ) ) ); ?></span>
                                </a>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php do_action( 'wp_ulike_pro_profile_after_user_info', $get_userdata ); ?>
                    </div>

                    <?php do_action( 'wp_ulike_pro_profile_before_badges', $get_userdata ); ?>

                    <?php if( ! empty( $options['display_badges'] ) && ! empty( $options['badges'] ) && wp_ulike_is_true( $options['display_badges'] ) ): ?>
                    <!-- profile-badges -->
                    <div class="wp-ulike-pro-badges-section">
                        <div class="wp-ulike-pro-badges-list">
                            <?php foreach ($options['badges'] as $badge_key => $badge_args): ?>
                            <div class="wp-ulike-pro-badge-item">
                                <?php if( empty( $badge_args['badge_type'] ) || $badge_args['badge_type'] === 'default' ): ?>
                                    <?php if( !empty( $badge_args['image']['url'] ) ): ?>
                                    <!-- image -->
                                    <div class="wp-ulike-pro-badge-symbol">
                                        <img class="wp-ulike-pro-badge-image"
                                            src="<?php echo esc_url( $badge_args['image']['url'] ); ?>"
                                            alt="<?php echo esc_attr( $badge_args['image']['title'] ); ?>"
                                            width="<?php echo esc_attr( $badge_args['image']['width'] ); ?>"
                                            height="<?php echo esc_attr( $badge_args['image']['height'] ); ?>">
                                    </div>
                                    <?php endif; ?>

                                    <?php if( !empty( $badge_args['title'] ) || !empty( $badge_args['subtitle'] ) ): ?>
                                    <!-- info -->
                                    <div class="wp-ulike-pro-badge-info">
                                        <?php if( !empty( $badge_args['title'] ) ): ?>
                                        <!-- title -->
                                        <span class="wp-ulike-pro-badge-title">
                                            <?php echo wp_kses_post( do_shortcode( $badge_args['title'] ) ); ?>
                                        </span>
                                        <?php endif; ?>
                                        <?php if( !empty( $badge_args['subtitle'] ) ): ?>
                                        <!-- subtitle -->
                                        <span class="wp-ulike-pro-badge-subtitle">
                                            <?php echo wp_kses_post( $badge_args['subtitle'] ); ?>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>

                                <?php elseif( $badge_args['badge_type'] === 'custom' && ! empty( $badge_args['custom'] ) ): ?>
                                <!-- custom-html -->
                                <div class="wp-ulike-pro-badge-custom">
                                    <?php echo wp_kses_post( do_shortcode( $badge_args['custom'] ) ); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="ulp-flex-col-xl-12 ulp-flex-col-md-12 ulp-flex-col-xs-12">
        <?php if( ! empty( $options['display_bio'] ) && wp_ulike_is_true( $options['display_bio'] ) ): ?>
        <!-- bio -->
        <p class="wp-ulike-pro-profile-desc">
            <?php
                $user_bio = get_user_meta( $get_user_id, 'description' , true );
                if( empty( $user_bio ) && ! empty( $options['display_custom_message'] ) && wp_ulike_is_true( $options['display_custom_message'] ) ){
                    $user_bio = isset( $options['custom_message'] ) ? $options['custom_message'] : NULL;
                }
                echo wp_kses_post( $user_bio );
            ?>
        </p>
        <?php endif; ?>

        <?php if( ! empty( $options['custom_html'] ) ): ?>
        <!-- custom-info -->
        <div class="wp-ulike-pro-profile-custom-info">
            <?php
            // SECURITY: Sanitize output to prevent XSS attacks
            // Use wp_kses_post to allow safe HTML while preventing script injection
            echo wp_kses_post( do_shortcode( $options['custom_html'] ) );
            ?>
        </div>
        <?php endif; ?>
    </div>

    <?php do_action( 'wp_ulike_pro_profile_before_tabs', $get_userdata ); ?>

    <?php if( ! empty( $options['display_tabs'] ) && ! empty( $options['tabs'] ) && wp_ulike_is_true( $options['display_tabs'] ) ):
    $tabs_wrapper_width =  ! empty( $options['tabs_wrapper_width'] ) ? $options['tabs_wrapper_width'] : array(
        'desktop' => '12',
        'tablet'  => '12',
        'mobile'  => '12'
    );
    // Select tab side
    $tab_side    = ! empty( $options['tabs_side'] ) ? $options['tabs_side'] . '_side' : 'top_side';
    $profile_url = wp_ulike_pro_get_user_profile_permalink( $get_user_id );

    // If no current tab is specified, find the first available (non-restricted) tab
    if( empty( $current_tab ) ){
        foreach ($options['tabs'] as $tab_key => $tab_args):
            if( ! empty( $tab_args['restrict'] ) && ( $get_user_id !== $wp_ulike_pro_logged_in_user_id ) ){
                continue;
            }
            // Found first available tab, set it as current
            // UTF-8: Use mb_strtolower for proper UTF-8 handling
            $current_tab = esc_attr( mb_strtolower( preg_replace( '/\s+/', '-', $tab_args['title'] ), 'UTF-8' ) );
            break;
        endforeach;
    }
    ?>
    <!-- tabs -->
    <div
        class="wp-ulike-pro-tabs-section ulp-flex-col-xl-<?php echo esc_attr( $tabs_wrapper_width['desktop'] ); ?> ulp-flex-col-md-<?php echo esc_attr( $tabs_wrapper_width['tablet'] ); ?> ulp-flex-col-xs-<?php echo esc_attr( $tabs_wrapper_width['mobile'] ); ?>">
        <div class="ulp-flex-row ulp-flex-middle-xs ulp-flex-start-md">
            <div class="ulp-flex-col-xs-12">
                <div class="ulp-tabs <?php echo esc_attr( $tab_side ); ?>">
                    <div class="tab_nav">
                        <?php foreach ($options['tabs'] as $tab_key => $tab_args):
                        // SECURITY: Use strict comparison for user IDs
                        if( !empty( $tab_args['restrict'] ) && (int) $get_user_id !== (int) $wp_ulike_pro_logged_in_user_id ){
                            continue;
                        }

                        $tab_type = 'nav_internal';
                        $tab_slug = esc_attr( strtolower( preg_replace( '/\s+/', '-', $tab_args['title'] ) ) );
                        $tab_link = WP_Ulike_Pro_Permalinks::localize_url( $profile_url, $tab_slug, 'wp_ulike_profile_tab' );
                        // Resolve URL: prefer has_link['url'] if set, otherwise has_link (string); avoid access when empty
                        $tab_link_url = '';
                        if ( ! empty( $tab_args['has_link'] ) ) {
                            if ( is_array( $tab_args['has_link'] ) && ! empty( $tab_args['has_link']['url'] ) ) {
                                $tab_link_url = $tab_args['has_link']['url'];
                            } elseif ( is_string( $tab_args['has_link'] ) ) {
                                $tab_link_url = $tab_args['has_link'];
                            }
                        }
                        if ( ! empty( $tab_link_url ) && filter_var( $tab_link_url, FILTER_VALIDATE_URL ) ) {
                            $tab_link = esc_url( $tab_link_url );
                            $tab_type = 'nav_external';
                        }

                        // If this is the first available tab (matches current_tab when URL has no tab), use profile URL
                        $is_first_available_tab = ( ! empty( $current_tab ) && $tab_slug == $current_tab && empty( get_query_var( 'wp_ulike_profile_tab' ) ) );
                        $tab_link = $tab_type !== 'nav_external' && $is_first_available_tab ? $profile_url : $tab_link;

                        if( ! empty( $current_tab ) && $tab_slug == $current_tab ){
                            $tab_type .= ' active';
                        }
                    ?>
                        <a href="<?php echo esc_url( $tab_link ); ?>"
                            class="nav_item <?php echo esc_attr( $tab_type ); ?>"><?php echo esc_html( $tab_args['title'] ); ?></a>
                        <?php endforeach; ?>
                    </div>
                    <div class="content_wrapper">
                        <?php
                    $content_exist = false;

                    foreach ($options['tabs'] as $tab_key => $tab_args):
                        if( ! empty( $tab_args['restrict'] ) && ( $get_user_id !== $wp_ulike_pro_logged_in_user_id ) ){
                            continue;
                        }
                        // Tab slug
                        // UTF-8: Use mb_strtolower for proper UTF-8 handling
                        $tab_slug = esc_attr( mb_strtolower( preg_replace( '/\s+/', '-', $tab_args['title'] ), 'UTF-8' ) );

                        if( !empty( $current_tab ) && $tab_slug != $current_tab ){
                            continue;
                        } elseif( empty( $current_tab ) && $tab_key ) {
                            continue;
                        }

                        $content_exist = true;
                    ?>
                        <div id="<?php echo 'ulp-content-' . esc_attr( $tab_slug ); ?>" class="tab_content">
                            <?php echo do_shortcode( $tab_args['content'] ); ?></div>
                        <?php
                    endforeach;
                    // Check tab content exist
                    if( ! $content_exist ){
                        echo sprintf( '<div class="tab_content">%s</div>', esc_html__( 'This tab is looking a little empty!', WP_ULIKE_PRO_DOMAIN ) );
                    }
                    ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php else: ?>

    <div class="wp-ulike-pro-user-not-found">
        <?php echo wp_kses_post( wp_ulike_get_option( 'user_not_found', esc_html__( 'Not found!', WP_ULIKE_PRO_DOMAIN ) ) ); ?>
    </div>

    <?php endif; ?>

    <?php do_action( 'wp_ulike_pro_profile_after_hook', $get_userdata ); ?>

</div>