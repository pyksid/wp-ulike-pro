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
                        <?php if( $get_user_id == $wp_ulike_pro_logged_in_user_id && WP_Ulike_Pro_Options::isLocalAvatars() ) : ?>
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
                        <?php if( ! empty( $options['display_name'] ) && wp_ulike_is_true( $options['display_name'] ) ): ?>
                        <!-- name -->
                        <h3 class="wp-ulike-pro-profile-name"><?php echo esc_html( $get_userdata->display_name ); ?></h3>
                        <?php endif; ?>

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
                            <?php echo do_shortcode( $options['custom_html'] ); ?>
                        </div>
                        <?php endif; ?>
                        <?php do_action( 'wp_ulike_pro_profile_after_user_info', $get_userdata ); ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <?php do_action( 'wp_ulike_pro_profile_before_badges', $get_userdata ); ?>

        <?php if( ! empty( $options['display_badges'] ) && ! empty( $options['badges'] ) && wp_ulike_is_true( $options['display_badges'] ) ):
            $badges_width =  ! empty( $options['badges_width'] ) ? $options['badges_width'] : array(
                'desktop' => '3',
                'tablet'  => '4',
                'mobile'  => '12'
            );
            $header_bagdes_width =  ! empty( $options['header_bagdes_width'] ) ? $options['header_bagdes_width'] : array(
                'desktop' => '12',
                'tablet'  => '12',
                'mobile'  => '12'
            );
        ?>
        <!-- profile-badges -->
        <div
            class="wp-ulike-pro-badges-section ulp-flex-col-xl-<?php echo esc_attr( $header_bagdes_width['desktop'] ); ?> ulp-flex-col-md-<?php echo esc_attr( $header_bagdes_width['tablet'] ); ?> ulp-flex-col-xs-<?php echo esc_attr( $header_bagdes_width['mobile'] ); ?>">
            <div class="ulp-flex-row ulp-flex-middle-xs ulp-flex-start-md">
                <?php foreach ($options['badges'] as $badge_key => $badge_args): ?>
                <div
                    class="ulp-flex-col-xl-<?php echo esc_attr( $badges_width['desktop'] ); ?> ulp-flex-col-md-<?php echo esc_attr( $badges_width['tablet'] ); ?> ulp-flex-col-xs-<?php echo esc_attr( $badges_width['mobile'] ); ?> wp-ulike-pro-badge-item-col">
                    <div class="ulp-flex-row ulp-flex-middle-xs ulp-flex-start-xs">
                        <?php if( empty( $badge_args['badge_type'] ) || $badge_args['badge_type'] === 'default' ): ?>

                        <?php if( !empty( $badge_args['image']['url'] ) ): ?>
                        <!-- image -->
                        <div class="ulp-flex-col-md-4 wp-ulike-pro-badge-symbol-col">
                            <img class="wp-ulike-pro-badge-image"
                                src="<?php echo esc_url( $badge_args['image']['url'] ); ?>"
                                alt="<?php echo esc_attr( $badge_args['image']['title'] ); ?>"
                                width="<?php echo esc_attr( $badge_args['image']['width'] ); ?>"
                                height="<?php echo esc_attr( $badge_args['image']['height'] ); ?>">
                        </div>
                        <?php endif; ?>

                        <?php if( !empty( $badge_args['title'] ) || !empty( $badge_args['subtitle'] ) ): ?>
                        <!-- info -->
                        <div class="ulp-flex-col-md-8 wp-ulike-pro-badge-info-col">
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
                        <div class="ulp-flex-col-md-12 wp-ulike-pro-badge-custom-col">
                            <?php echo wp_kses_post( do_shortcode( $badge_args['custom'] ) ); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
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
    ?>
    <!-- tabs -->
    <div
        class="wp-ulike-pro-tabs-section ulp-flex-col-xl-<?php echo esc_attr( $tabs_wrapper_width['desktop'] ); ?> ulp-flex-col-md-<?php echo esc_attr( $tabs_wrapper_width['tablet'] ); ?> ulp-flex-col-xs-<?php echo esc_attr( $tabs_wrapper_width['mobile'] ); ?>">
        <div class="ulp-flex-row ulp-flex-middle-xs ulp-flex-start-md">
            <div class="ulp-flex-col-xs-12">
                <div class="ulp-tabs <?php echo esc_attr( $tab_side ); ?>">
                    <div class="tab_nav">
                        <?php foreach ($options['tabs'] as $tab_key => $tab_args):
                        if( !empty( $tab_args['restrict'] ) && ( $get_user_id !== $wp_ulike_pro_logged_in_user_id ) ){
                            continue;
                        }

                        $tab_type = 'nav_internal';
                        $tab_slug = esc_attr( strtolower( preg_replace( '/\s+/', '-', $tab_args['title'] ) ) );
                        $tab_link = WP_Ulike_Pro_Permalinks::localize_url( $profile_url, $tab_slug, 'wp_ulike_profile_tab' );
                        if( ! empty( $tab_args['has_link']['url'] ) ){
                            $tab_link = esc_url( $tab_args['has_link']['url'] );
                            $tab_type = 'nav_external';
                        }

                        $tab_link = $tab_type !== 'nav_external' && ! $tab_key ? $profile_url : $tab_link;

                        if( ( ! empty( $current_tab ) && $tab_slug == $current_tab ) || ( empty( $current_tab ) && ! $tab_key )  ){
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
                        $tab_slug = esc_attr( strtolower( preg_replace( '/\s+/', '-', $tab_args['title'] ) ) );

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
        <?php echo wp_kses_post( wp_ulike_get_option( 'user_not_found', esc_html__( 'User Not Found!', WP_ULIKE_PRO_DOMAIN ) ) ); ?>
    </div>

    <?php endif; ?>

    <?php do_action( 'wp_ulike_pro_profile_after_hook', $get_userdata ); ?>

</div>