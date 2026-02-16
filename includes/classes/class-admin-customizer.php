<?php

class WP_Ulike_Pro_Admin_Customizer {

    protected $option_domain = 'wp_ulike_customize';
    protected $sections = array();

    /**
     * __construct
     */
    function __construct() {
        // Init Hook
        $this->init();
    }

    public function init(){
        // Filters
        add_filter( 'wp_ulike_customizer_button_group_options', array( $this, 'update_button_group_options' ), 10, 1 );
        add_filter( 'wp_ulike_customizer_sections', array( $this, 'add_customizer_sections' ), 10, 1 );
        add_filter( 'wp_ulike_customizer_assets', array( $this, 'add_customizer_assets' ), 10, 1 );
        add_filter( 'wp_ulike_customizer_template_preview', array( $this, 'render_template_preview' ), 10, 2 );
    }

    /**
     * Add customizer sections via filter
     *
     * @param array $sections Existing sections
     * @return array Modified sections with pro sections added
     */
    public function add_customizer_sections( $sections ) {
        // Reset sections array to avoid duplicates
        $this->sections = array();

        // Register all pro sections
        $this->register_profile_section();
        $this->register_forms_section();
        $this->register_socials_section();

        // Merge pro sections with existing sections
        return array_merge( $sections, $this->sections );
    }

    /**
     * Add pro version assets via filter
     *
     * @param array $assets Existing assets
     * @return array Modified assets with pro assets added
     */
    public function add_customizer_assets( $assets ) {
        if ( ! defined( 'WP_ULIKE_PRO_PUBLIC_URL' ) ) {
            return $assets;
        }

        // Initialize arrays if needed
        if ( ! is_array( $assets['css'] ) ) {
            $assets['css'] = ! empty( $assets['css'] ) ? array( $assets['css'] ) : array();
        }
        if ( ! is_array( $assets['js'] ) ) {
            $assets['js'] = ! empty( $assets['js'] ) ? array( $assets['js'] ) : array();
        }

        // Remove free plugin JS and localized scripts
        // Filter out free plugin assets (marked with 'source' => 'free')
        $assets['js'] = array_filter( $assets['js'], function( $js_asset ) {
            // If it's the new format with source, check if it's free
            if ( is_array( $js_asset ) && isset( $js_asset['source'] ) ) {
                return $js_asset['source'] !== 'free';
            }
            // If it's a string URL, check if it's the free plugin JS (minified only)
            if ( is_string( $js_asset ) ) {
                return strpos( $js_asset, 'wp-ulike.min.js' ) === false;
            }
            return true;
        } );
        $assets['js'] = array_values( $assets['js'] ); // Re-index array

        // Remove free plugin localized scripts
        if ( isset( $assets['localized_scripts'] ) && is_array( $assets['localized_scripts'] ) ) {
            // Remove wp_ulike_params (free plugin script)
            unset( $assets['localized_scripts']['wp_ulike_params'] );

            // Filter out any other free plugin scripts
            foreach ( $assets['localized_scripts'] as $key => $script_data ) {
                if ( is_array( $script_data ) && isset( $script_data['source'] ) && $script_data['source'] === 'free' ) {
                    unset( $assets['localized_scripts'][ $key ] );
                }
            }
        } else {
            $assets['localized_scripts'] = array();
        }

        // Add Pro CSS files (always minified) - marked as pro
        $assets['css'][] = array(
            'url' => WP_ULIKE_PRO_PUBLIC_URL . '/assets/css/wp-ulike-pro.min.css',
            'source' => 'pro'
        );
        $assets['css'][] = array(
            'url' => WP_ULIKE_PRO_PUBLIC_URL . '/assets/css/uploader.min.css',
            'source' => 'pro'
        );

        // Add Pro JS files (always minified) - marked as pro
        $assets['js'][] = array(
            'url' => WP_ULIKE_PRO_PUBLIC_URL . '/assets/js/wp-ulike-pro.min.js',
            'source' => 'pro'
        );
        $assets['js'][] = array(
            'url' => WP_ULIKE_PRO_PUBLIC_URL . '/assets/js/solo/share.min.js',
            'source' => 'pro'
        );
        $assets['js'][] = array(
            'url' => WP_ULIKE_PRO_PUBLIC_URL . '/assets/js/solo/uploader.min.js',
            'source' => 'pro'
        );

        // Always in preview mode in customizer context
        $ajax_url = add_query_arg( array( 'preview' => true ), admin_url( 'admin-ajax.php' ) );

        // UlikeProCommonConfig - marked as pro
        $assets['localized_scripts']['UlikeProCommonConfig'] = array(
            'data' => array(
                'AjaxUrl' => $ajax_url,
                'Nonce'   => wp_create_nonce( WP_ULIKE_PRO_DOMAIN ),
                'TabSide' => wp_ulike_get_option( 'user_profiles_appearance|tabs_side', 'top' ),
                'ViewTracking' => array(
                    'enabledTypes' => wp_ulike_get_option( 'view_tracking_enabled_types', array( 'post' ) )
                )
            ),
            'source' => 'pro'
        );

        // fileUploaderCommonConfig - marked as pro
        $upload_dir = wp_upload_dir();
        $upload_slug = defined( 'WP_ULIKE_SLUG' ) ? WP_ULIKE_SLUG : 'wp-ulike';
        $upload_url = trailingslashit( $upload_dir['baseurl'] ) . $upload_slug . '/avatars/';

        $assets['localized_scripts']['fileUploaderCommonConfig'] = array(
            'data' => array(
                'AjaxUrl' => $ajax_url,
                'Nonce' => wp_create_nonce( WP_ULIKE_PRO_DOMAIN ),
                'uploadUrl' => trailingslashit( $upload_url ),
                'avatarConfig' => class_exists( 'WP_Ulike_Pro_Options' ) ? WP_Ulike_Pro_Options::getAvatarConfigForJs() : array()
            ),
            'source' => 'pro'
        );

        return $assets;
    }

    /**
     * Update button group options
     *
     * @param array $options
     * @return array
     */
    public function update_button_group_options( $options ){

        $options[0]['fields'][] =  array(
            'id'               => 'normal_dislike_image',
            'type'             => 'background',
            'background_color' => false,
            'title'            => esc_html__( 'Dislike Image', WP_ULIKE_PRO_DOMAIN ),
            'output'           => '.wpulike .wp_ulike_general_class.wpulike_down_vote .wp_ulike_btn.wp_ulike_put_image::after',
        );
        $options[1]['fields'][] =  array(
            'id'               => 'hover_dislike_image',
            'type'             => 'background',
            'background_color' => false,
            'output_important' => true,
            'title'            => esc_html__( 'Dislike Image', WP_ULIKE_PRO_DOMAIN ),
            'output'           => '.wpulike .wp_ulike_general_class.wpulike_down_vote .wp_ulike_btn.wp_ulike_put_image:hover::after',
        );
        $options[2]['fields'][] =  array(
            'id'               => 'active_dislike_image',
            'type'             => 'background',
            'background_color' => false,
            'title'            => esc_html__( 'Dislike Image', WP_ULIKE_PRO_DOMAIN ),
            'output'           => '.wpulike .wp_ulike_general_class.wpulike_down_vote .wp_ulike_btn.wp_ulike_btn_is_active.wp_ulike_put_image::after',
        );

        return $options;

    }

    /**
     * Register profile section on customizer panel
     *
     * @return void
     */
    public function register_profile_section(){
        $this->sections[] = array(
            'parent' => WP_ULIKE_SLUG,
            'id'     => 'profile_template',
            'title'  => esc_html__( 'Profile Template', WP_ULIKE_PRO_DOMAIN ),
            'template' => 'profile',
            'icon'   => 'user',
            'fields' => array(
                array(
                    'type'    => 'heading',
                    'content' => esc_html__( 'Header', WP_ULIKE_PRO_DOMAIN ),
                ),
                array(
                    'id'               => 'header_name_typography',
                    'type'             => 'typography',
                    'title'            => esc_html__( 'Name Typography', WP_ULIKE_PRO_DOMAIN ),
                    'output'           => '.wp-ulike-pro-section-profile .wp-ulike-pro-profile-name',
                    'output_important' => true,
                    'units'            => array('px', 'em', 'rem')
                ),
                array(
                    'id'               => 'header_desc_typography',
                    'type'             => 'typography',
                    'title'            => esc_html__( 'Desc Typography', WP_ULIKE_PRO_DOMAIN ),
                    'output'           => '.wp-ulike-pro-section-profile .wp-ulike-pro-profile-desc',
                    'output_important' => true,
                    'units'            => array('px', 'em', 'rem')
                ),
                array(
                    'id'               => 'header_avatar_size',
                    'type'             => 'dimensions',
                    'title'            => esc_html__('Avatar size', WP_ULIKE_PRO_DOMAIN),
                    'output_prefix'    => 'max',
                    'output'           => '.fileuploader-theme-avatar',
                    'output_important' => true,
                    'units'            => array('px', 'em', 'rem')
                ),
                array(
                    'id'               => 'header_action_buttons_typography',
                    'type'             => 'typography',
                    'title'            => esc_html__( 'Action Buttons Typography', WP_ULIKE_PRO_DOMAIN ),
                    'output'           => '.wp-ulike-pro-section-profile .wp-ulike-pro-profile-action-btn',
                    'output_important' => true,
                    'units'            => array('px', 'em', 'rem')
                ),
                array(
                    'id'               => 'header_action_buttons_border',
                    'type'             => 'border',
                    'title'            => esc_html__( 'Action Buttons Border', WP_ULIKE_PRO_DOMAIN ),
                    'output'           => '.wp-ulike-pro-section-profile .wp-ulike-pro-profile-action-btn',
                    'output_important' => true,
                    'units'            => array('px', 'em', 'rem')
                ),
                array(
                    'id'               => 'header_action_buttons_background',
                    'type'             => 'color',
                    'title'            => esc_html__( 'Action Buttons Background', WP_ULIKE_PRO_DOMAIN ),
                    'output'           => '.wp-ulike-pro-section-profile .wp-ulike-pro-profile-action-btn',
                    'output_mode'      => 'background-color',
                    'output_important' => true
                ),
                array(
                    'id'               => 'header_action_buttons_hover_background',
                    'type'             => 'color',
                    'title'            => esc_html__( 'Action Buttons Hover Background', WP_ULIKE_PRO_DOMAIN ),
                    'output'           => '.wp-ulike-pro-section-profile .wp-ulike-pro-profile-action-btn:hover',
                    'output_mode'      => 'background-color',
                    'output_important' => true
                ),
                array(
                    'id'               => 'header_action_buttons_padding',
                    'type'             => 'spacing',
                    'title'            => esc_html__( 'Action Buttons Padding', WP_ULIKE_PRO_DOMAIN ),
                    'output'           => '.wp-ulike-pro-section-profile .wp-ulike-pro-profile-action-btn',
                    'output_important' => true,
                    'units'            => array('px', 'em', 'rem')
                ),
                array(
                    'id'               => 'header_custom_info_typography',
                    'type'             => 'typography',
                    'title'            => esc_html__( 'Custom Info Typography', WP_ULIKE_PRO_DOMAIN ),
                    'output'           => '.wp-ulike-pro-section-profile .wp-ulike-pro-profile-custom-info',
                    'output_important' => true,
                    'units'            => array('px', 'em', 'rem')
                ),
                // Badges
                array(
                    'type'    => 'heading',
                    'content' => esc_html__( 'Badges', WP_ULIKE_PRO_DOMAIN ),
                ),
                array(
                    'id'               => 'badges_title_typography',
                    'type'             => 'typography',
                    'title'            => esc_html__( 'Title Typography', WP_ULIKE_PRO_DOMAIN ),
                    'output'           => '.wp-ulike-pro-section-profile .wp-ulike-pro-badges-section .wp-ulike-pro-badge-info .wp-ulike-pro-badge-title',
                    'output_important' => true,
                    'units'            => array('px', 'em', 'rem')
                ),
                array(
                    'id'               => 'badges_subtitle_typography',
                    'type'             => 'typography',
                    'title'            => esc_html__( 'Subtitle Typography', WP_ULIKE_PRO_DOMAIN ),
                    'output'           => '.wp-ulike-pro-section-profile .wp-ulike-pro-badges-section .wp-ulike-pro-badge-info .wp-ulike-pro-badge-subtitle',
                    'output_important' => true,
                    'units'            => array('px', 'em', 'rem')
                ),
                array(
                    'id'               => 'badges_item_spacing',
                    'type'             => 'spacing',
                    'title'            => esc_html__( 'Badge Item Padding', WP_ULIKE_PRO_DOMAIN ),
                    'output'           => '.wp-ulike-pro-section-profile .wp-ulike-pro-badge-item',
                    'output_important' => true,
                    'units'            => array('px', 'em', 'rem')
                ),
                array(
                    'id'               => 'badges_item_hover_background',
                    'type'             => 'color',
                    'title'            => esc_html__( 'Badge Item Hover Background', WP_ULIKE_PRO_DOMAIN ),
                    'output'           => '.wp-ulike-pro-section-profile .wp-ulike-pro-badge-item:hover',
                    'output_mode'      => 'background-color',
                    'output_important' => true
                ),
                // Tabs
                array(
                    'type'    => 'heading',
                    'content' => esc_html__( 'Tabs', WP_ULIKE_PRO_DOMAIN ),
                ),
                array(
                    'id'               => 'profile_tabs_typography',
                    'type'             => 'typography',
                    'title'            => esc_html__( 'Menu Typography', WP_ULIKE_PRO_DOMAIN ),
                    'output'           => '.ulp-tabs > .tab_nav .nav_item',
                    'output_important' => true,
                    'units'            => array('px', 'em', 'rem')
                ),
                array(
                    'id'               => 'profile_tabs_color',
                    'type'             => 'color',
                    'title'            => esc_html__( 'Menu Default Color', WP_ULIKE_PRO_DOMAIN ),
                    'output'           => '.ulp-tabs > .tab_nav .nav_item',
                    'output_important' => true
                ),
                array(
                    'id'               => 'profile_tabs_hover_color',
                    'type'             => 'color',
                    'title'            => esc_html__( 'Menu Hover Color', WP_ULIKE_PRO_DOMAIN ),
                    'output'           => '.ulp-tabs > .tab_nav .nav_item:hover',
                    'output_important' => true
                ),
                array(
                    'id'               => 'profile_tabs_active_color',
                    'type'             => 'color',
                    'title'            => esc_html__( 'Menu Active Color', WP_ULIKE_PRO_DOMAIN ),
                    'output'           => '.ulp-tabs > .tab_nav .nav_item.active',
                    'output_important' => true
                ),
                array(
                    'id'               => 'profile_tabs_border',
                    'type'             => 'border',
                    'title'            => esc_html__( 'Menu Border', WP_ULIKE_PRO_DOMAIN ),
                    'output'           => '.ulp-tabs > .tab_nav',
                    'output_important' => true,
                    'units'            => array('px', 'em', 'rem')
                ),
                array(
                    'id'               => 'profile_tabs_border_active_color',
                    'type'             => 'color',
                    'title'            => esc_html__( 'Menu Border Active Color', WP_ULIKE_PRO_DOMAIN ),
                    'output'           => '.ulp-tabs > .tab_nav .nav_item.active',
                    'output_mode'      => 'border-color',
                    'output_important' => true
                ),
                array(
                    'id'               => 'profile_tabs_spacing',
                    'type'             => 'spacing',
                    'title'            => esc_html__( 'Menu Padding', WP_ULIKE_PRO_DOMAIN ),
                    'output'           => '.ulp-tabs > .tab_nav .nav_item',
                    'output_important' => true,
                    'units'            => array('px', 'em', 'rem')
                ),

                array(
                    'id'               => 'profile_tabs_secondary_color',
                    'type'             => 'color',
                    'title'            => esc_html__( 'Left/Right Menu Default Color', WP_ULIKE_PRO_DOMAIN ),
                    'output'           => '.ulp-tabs.left_side > .tab_nav .nav_item::after, .ulp-tabs.right_side > .tab_nav .nav_item::after',
                    'output_mode'      => 'background-color',
                    'output_important' => true
                ),
                array(
                    'id'               => 'profile_tabs_main_color',
                    'type'             => 'color',
                    'title'            => esc_html__( 'Left/Right Menu Active Color', WP_ULIKE_PRO_DOMAIN ),
                    'output'           => '.ulp-tabs .controller span, .ulp-tabs.left_side > .tab_nav .nav_item.active:after, .ulp-tabs.right_side > .tab_nav .nav_item.active:after',
                    'output_mode'      => 'background-color',
                    'output_important' => true
                ),

                array(
                    'id'               => 'profile_tabs_content_typography',
                    'type'             => 'typography',
                    'title'            => esc_html__( 'Content Typography', WP_ULIKE_PRO_DOMAIN ),
                    'output'           => '.ulp-tabs .content_wrapper',
                    'output_important' => true,
                    'units'            => array('px', 'em', 'rem')
                ),
                array(
                    'id'               => 'profile_tabs_content_border',
                    'type'             => 'border',
                    'title'            => esc_html__( 'Content Border', WP_ULIKE_PRO_DOMAIN ),
                    'output'           => '.ulp-tabs .content_wrapper',
                    'output_important' => true,
                    'units'            => array('px', 'em', 'rem')
                ),
                array(
                    'id'               => 'profile_tabs_content_spacing',
                    'type'             => 'spacing',
                    'title'            => esc_html__( 'Content Padding', WP_ULIKE_PRO_DOMAIN ),
                    'output'           => '.ulp-tabs .content_wrapper',
                    'output_important' => true,
                    'units'            => array('px', 'em', 'rem')
                ),
                array(
                    'id'               => 'profile_tabs_content_background',
                    'type'             => 'color',
                    'title'            => esc_html__( 'Content Background', WP_ULIKE_PRO_DOMAIN ),
                    'output'           => '.ulp-tabs .content_wrapper',
                    'output_mode'      => 'background-color',
                    'output_important' => true
                ),
                array(
                    'id'               => 'profile_tabs_menu_background',
                    'type'             => 'color',
                    'title'            => esc_html__( 'Menu Background (Left/Right Side)', WP_ULIKE_PRO_DOMAIN ),
                    'output'           => '.ulp-tabs.left_side > .tab_nav, .ulp-tabs.right_side > .tab_nav',
                    'output_mode'      => 'background-color',
                    'output_important' => true
                ),
            )
        );
    }

    /**
     * Register forms section on customizer panel
     *
     * @return void
     */
    public function register_forms_section(){
        $this->sections[] = array(
            'parent' => WP_ULIKE_SLUG,
            'id'     => 'login_signup_forms',
            'title'  => esc_html__( 'Login & Signup Forms', WP_ULIKE_PRO_DOMAIN ),
            'template' => 'forms',
            'icon'   => 'key',
            'fields' => array(
                array(
                    'type'    => 'heading',
                    'content' => esc_html__( 'Wrapper', WP_ULIKE_PRO_DOMAIN ),
                ),
                array(
                    'id'               => 'forms_typography',
                    'type'             => 'typography',
                    'title'            => esc_html__( 'Typography', WP_ULIKE_PRO_DOMAIN ),
                    'output'           => '.ulp-form, .ulp-form p, .ulp-form span, .ulp-form label',
                    'output_important' => true,
                    'units'            => array('px', 'em', 'rem')
                ),
                array(
                    'id'               => 'forms_max_width',
                    'type'             => 'slider',
                    'output_mode'      => 'max-width',
                    'title'            => esc_html__( 'Max Width', WP_ULIKE_PRO_DOMAIN ),
                    'output'           => '.ulp-form',
                    'min'              => 240,
                    'max'              => 1600,
                    'default'          => 480,
                    'unit'             => 'px',
                    'output_important' => true
                ),
                array(
                    'id'               => 'forms_margin',
                    'type'             => 'spacing',
                    'title'            => esc_html__( 'Form Margin', WP_ULIKE_PRO_DOMAIN ),
                    'output'           => '.ulp-form',
                    'output_mode'      => 'margin',
                    'output_important' => true,
                    'units'            => array('px', 'em', 'rem')
                ),
                array(
                    'id'               => 'forms_spacing',
                    'type'             => 'spinner',
                    'title'            => esc_html__( 'Spacing', WP_ULIKE_PRO_DOMAIN ),
                    'output'           => '[class^="ulp-flex-col-"], [class*="ulp-flex-col-"]',
                    'output_mode'      => 'padding-bottom',
                    'units'            => array('px', 'em', 'rem', '%'),
                    'output_important' => true
                ),
                // Inputs
                array(
                    'type'    => 'heading',
                    'content' => esc_html__( 'Inputs', WP_ULIKE_PRO_DOMAIN ),
                ),
                array(
                    'id'               => 'forms_input_typography',
                    'type'             => 'typography',
                    'title'            => esc_html__( 'Typography', WP_ULIKE_PRO_DOMAIN ),
                    'output'           => '.ulp-form .ulp-floating-input',
                    'output_important' => true,
                    'units'            => array('px', 'em', 'rem')
                ),
                array(
                    'id'               => 'forms_input_border',
                    'type'             => 'border',
                    'title'            => esc_html__( 'Border', WP_ULIKE_PRO_DOMAIN ),
                    'output'           => '.ulp-form .ulp-floating-input',
                    'output_important' => true,
                    'units'            => array('px', 'em', 'rem')
                ),
                array(
                    'id'               => 'forms_input_border_active',
                    'type'             => 'color',
                    'title'            => esc_html__( 'Border Hover Color', WP_ULIKE_PRO_DOMAIN ),
                    'output'           => '.ulp-form .ulp-floating:hover .ulp-floating-input, .ulp-form .ulp-floating-input:hover, .ulp-form .ulp-floating-input:focus-within',
                    'output_mode'      => 'border-color',
                    'output_important' => true
                ),
                array(
                    'id'               => 'forms_input_padding',
                    'type'             => 'spacing',
                    'title'            => esc_html__( 'Padding', WP_ULIKE_PRO_DOMAIN ),
                    'output'           => '.ulp-form .ulp-floating-input',
                    'output_important' => true,
                    'units'            => array('px', 'em', 'rem')
                ),
                array(
                    'id'               => 'forms_input_background',
                    'type'             => 'color',
                    'title'            => esc_html__( 'Background Color', WP_ULIKE_PRO_DOMAIN ),
                    'output'           => '.ulp-form .ulp-floating-input',
                    'output_mode'      => 'background-color',
                    'output_important' => true
                ),
                array(
                    'id'               => 'forms_input_border_radius',
                    'type'             => 'slider',
                    'title'            => esc_html__( 'Border Radius', WP_ULIKE_PRO_DOMAIN ),
                    'output'           => '.ulp-form .ulp-floating-input',
                    'output_mode'      => 'border-radius',
                    'min'              => 0,
                    'max'              => 50,
                    'unit'             => 'px',
                    'output_important' => true
                ),
                array(
                    'id'               => 'forms_input_error_border',
                    'type'             => 'color',
                    'title'            => esc_html__( 'Error Border Color', WP_ULIKE_PRO_DOMAIN ),
                    'output'           => '.ulp-form .ulp-floating-input.ulp-input-error',
                    'output_mode'      => 'border-color',
                    'output_important' => true
                ),
                array(
                    'id'               => 'forms_input_success_border',
                    'type'             => 'color',
                    'title'            => esc_html__( 'Success Border Color', WP_ULIKE_PRO_DOMAIN ),
                    'output'           => '.ulp-form .ulp-floating-input.ulp-input-success',
                    'output_mode'      => 'border-color',
                    'output_important' => true
                ),
                // Labels
                array(
                    'type'    => 'heading',
                    'content' => esc_html__( 'Label', WP_ULIKE_PRO_DOMAIN ),
                ),
                array(
                    'id'               => 'forms_label_color',
                    'type'             => 'color',
                    'title'            => esc_html__( 'Color', WP_ULIKE_PRO_DOMAIN ),
                    'output'           => '.ulp-form .ulp-floating-label::before ',
                    'output_important' => true
                ),
                array(
                    'id'               => 'forms_active_label_color',
                    'type'             => 'color',
                    'title'            => esc_html__( 'Active Color', WP_ULIKE_PRO_DOMAIN ),
                    'output'           => '.ulp-form .ulp-floating-input:focus + .ulp-floating-label::before',
                    'output_important' => true
                ),

                // Button
                array(
                    'type'    => 'heading',
                    'content' => esc_html__( 'Button', WP_ULIKE_PRO_DOMAIN ),
                ),
                array(
                    'id'               => 'forms_button_typography',
                    'type'             => 'typography',
                    'title'            => esc_html__( 'Typography', WP_ULIKE_PRO_DOMAIN ),
                    'output'           => '.ulp-form .ulp-button',
                    'output_important' => true,
                    'units'            => array('px', 'em', 'rem')
                ),
                array(
                    'id'               => 'forms_button_hover_color',
                    'type'             => 'color',
                    'title'            => esc_html__( 'Hover Color', WP_ULIKE_PRO_DOMAIN ),
                    'output'           => '.ulp-form .ulp-button:hover, .ulp-form .ulp-button:focus, .ulp-form .ulp-button:active',
                    'output_important' => true
                ),
                array(
                    'id'               => 'forms_button_padding',
                    'type'             => 'spacing',
                    'title'            => esc_html__( 'Padding', WP_ULIKE_PRO_DOMAIN ),
                    'output'           => '.ulp-form .ulp-button',
                    'output_important' => true,
                    'units'            => array('px', 'em', 'rem')
                ),
                array(
                    'id'               => 'forms_button_background',
                    'type'             => 'color',
                    'title'            => esc_html__( 'Background Color', WP_ULIKE_PRO_DOMAIN ),
                    'output'           => '.ulp-form .ulp-button',
                    'output_mode'      => 'background-color',
                    'output_important' => true
                ),
                array(
                    'id'               => 'forms_button_hover_background',
                    'type'             => 'color',
                    'title'            => esc_html__( 'Background Hover Color', WP_ULIKE_PRO_DOMAIN ),
                    'output'           => '.ulp-form .ulp-button:hover, .ulp-form .ulp-button:focus, .ulp-form .ulp-button:active',
                    'output_mode'      => 'background-color',
                    'output_important' => true
                ),
                array(
                    'id'               => 'forms_button_border',
                    'type'             => 'border',
                    'title'            => esc_html__( 'Border', WP_ULIKE_PRO_DOMAIN ),
                    'output'           => '.ulp-form .ulp-button',
                    'output_important' => true,
                    'units'            => array('px', 'em', 'rem')
                ),
                array(
                    'id'               => 'forms_button_border_radius',
                    'type'             => 'slider',
                    'title'            => esc_html__( 'Border Radius', WP_ULIKE_PRO_DOMAIN ),
                    'output'           => '.ulp-form .ulp-button',
                    'output_mode'      => 'border-radius',
                    'min'              => 0,
                    'max'              => 50,
                    'unit'             => 'px',
                    'output_important' => true
                ),
                array(
                    'id'               => 'forms_button_spinner',
                    'type'             => 'background',
                    'background_color' => false,
                    'title'            => esc_html__( 'Loading Spinner', WP_ULIKE_PRO_DOMAIN ),
                    'output'           => '.ulp-form .ulp-loading input[type=submit]',
                    'output_important' => true
                ),
                // Links
                array(
                    'type'    => 'heading',
                    'content' => esc_html__( 'Links', WP_ULIKE_PRO_DOMAIN ),
                ),
                array(
                    'id'               => 'forms_link_color',
                    'type'             => 'color',
                    'title'            => esc_html__( 'Link Color', WP_ULIKE_PRO_DOMAIN ),
                    'output'           => '.ulp-form a',
                    'output_important' => true
                ),
                array(
                    'id'               => 'forms_link_hover_color',
                    'type'             => 'color',
                    'title'            => esc_html__( 'Link Hover Color', WP_ULIKE_PRO_DOMAIN ),
                    'output'           => '.ulp-form a:hover',
                    'output_important' => true
                ),
            )
        );
    }

    /**
     * Register social share section on customizer panel
     *
     * @return void
     */
    public function register_socials_section(){
        $this->sections[] = array(
            'parent' => WP_ULIKE_SLUG,
            'id'     => 'social_buttons',
            'title'  => esc_html__( 'Social Buttons', WP_ULIKE_PRO_DOMAIN ),
            'template' => 'social',
            'icon'   => 'share',
            'fields' => array(
                array(
                    'id'               => 'social_max_width',
                    'type'             => 'slider',
                    'title'            => esc_html__( 'Max Width', WP_ULIKE_PRO_DOMAIN ),
                    'output'           => '.ulp-social-wrapper, .ulp-social-login-wrapper',
                    'output_mode'      => 'max-width',
                    'min'              => 1,
                    'max'              => 2000,
                    'unit'             => 'px',
                    'output_important' => true
                ),
                array(
                    'id'               => 'social_typography',
                    'type'             => 'typography',
                    'title'            => esc_html__( 'Typography', WP_ULIKE_PRO_DOMAIN ),
                    'output'           => '.ulp-share-btn',
                    'output_important' => true,
                    'units'            => array('px', 'em', 'rem')
                ),
                array(
                    'id'               => 'social_background',
                    'type'             => 'color',
                    'title'            => esc_html__( 'Background', WP_ULIKE_PRO_DOMAIN ),
                    'output'           => '.ulp-social-buttons-color-custom.ulp-social-skin-flat .ulp-share-btn, .ulp-social-buttons-color-custom.ulp-social-skin-gradient .ulp-share-btn, .ulp-social-buttons-color-custom.ulp-social-skin-boxed .ulp-share-btn-icon, .ulp-social-buttons-color-custom.ulp-social-skin-minimal .ulp-share-btn-icon',
                    'output_mode'      => 'background-color',
                    'output_important' => true
                ),
                array(
                    'id'               => 'social_border',
                    'type'             => 'border',
                    'title'            => esc_html__( 'Border', WP_ULIKE_PRO_DOMAIN ),
                    'output'           => '.ulp-share-btn',
                    'output_important' => true,
                    'units'            => array('px', 'em', 'rem')
                ),
                array(
                    'id'               => 'social_border_radius',
                    'type'             => 'slider',
                    'title'            => esc_html__( 'Border Radius', WP_ULIKE_PRO_DOMAIN ),
                    'output'           => '.ulp-share-btn',
                    'output_mode'      => 'border-radius',
                    'min'              => 0,
                    'max'              => 50,
                    'unit'             => 'px',
                    'output_important' => true
                ),
                array(
                    'id'               => 'social_padding',
                    'type'             => 'spacing',
                    'title'            => esc_html__( 'Padding', WP_ULIKE_PRO_DOMAIN ),
                    'output'           => '.ulp-share-btn',
                    'output_important' => true,
                    'units'            => array('px', 'em', 'rem')
                ),
                array(
                    'id'               => 'social_hover_background',
                    'type'             => 'color',
                    'title'            => esc_html__( 'Hover Background', WP_ULIKE_PRO_DOMAIN ),
                    'output'           => '.ulp-share-btn:hover',
                    'output_mode'      => 'background-color',
                    'output_important' => true
                ),
                array(
                    'id'               => 'social_height',
                    'type'             => 'slider',
                    'title'            => esc_html__( 'Height', WP_ULIKE_PRO_DOMAIN ),
                    'output'           => '.ulp-share-btn',
                    'output_mode'      => 'height',
                    'min'              => 1,
                    'max'              => 50,
                    'unit'             => 'em',
                    'output_important' => true
                ),
                array(
                    'type'    => 'heading',
                    'content' => esc_html__( 'Icon', WP_ULIKE_PRO_DOMAIN ),
                ),
                array(
                    'id'               => 'social_icon_color',
                    'type'             => 'color',
                    'title'            => esc_html__( 'Color', WP_ULIKE_PRO_DOMAIN ),
                    'output'           => '.ulp-share-btn-icon',
                    'output_important' => true
                ),
                array(
                    'id'               => 'social_icon_width',
                    'type'             => 'slider',
                    'title'            => esc_html__( 'Width', WP_ULIKE_PRO_DOMAIN ),
                    'output'           => '.ulp-share-btn-icon',
                    'output_mode'      => 'width',
                    'min'              => 1,
                    'max'              => 50,
                    'unit'             => 'em',
                    'output_important' => true
                ),
                array(
                    'id'               => 'social_icon_size',
                    'type'             => 'slider',
                    'title'            => esc_html__( 'Size', WP_ULIKE_PRO_DOMAIN ),
                    'output'           => '.ulp-share-btn-icon i',
                    'output_mode'      => 'font-size',
                    'min'              => 1,
                    'max'              => 50,
                    'unit'             => 'em',
                    'output_important' => true
                ),
                array(
                    'type'    => 'heading',
                    'content' => esc_html__( 'Text', WP_ULIKE_PRO_DOMAIN ),
                ),
                array(
                    'id'               => 'social_text_typography',
                    'type'             => 'typography',
                    'title'            => esc_html__( 'Text Typography', WP_ULIKE_PRO_DOMAIN ),
                    'output'           => '.ulp-share-btn-text',
                    'output_important' => true,
                    'units'            => array('px', 'em', 'rem')
                ),
                array(
                    'id'               => 'social_text_color',
                    'type'             => 'color',
                    'title'            => esc_html__( 'Text Color', WP_ULIKE_PRO_DOMAIN ),
                    'output'           => '.ulp-share-btn-text',
                    'output_important' => true
                ),
                array(
                    'id'               => 'social_hover_color',
                    'type'             => 'color',
                    'title'            => esc_html__( 'Hover Color', WP_ULIKE_PRO_DOMAIN ),
                    'output'           => '.ulp-share-btn:hover',
                    'output_important' => true
                ),
            )
        );
    }

    /**
     * Render template preview for pro templates
     *
     * @param string $preview_html Existing preview HTML (empty by default)
     * @param string $template_type Template type
     * @return string Rendered HTML
     */
    public function render_template_preview( $preview_html, $template_type ) {
        // Only handle pro templates
        if ( ! in_array( $template_type, array( 'profile', 'forms', 'social' ), true ) ) {
            return $preview_html;
        }

        // Ensure preview mode is detected
        if ( ! isset( $_GET['preview'] ) ) {
            $_GET['preview'] = true;
        }

        ob_start();

        switch ( $template_type ) {
            case 'profile':
                $user_id = get_current_user_id();
                echo '<div style="position: relative; padding: 20px; min-height: 300px; display: flex; align-items: center; justify-content: center;">';
                echo '<div style="max-width: 800px; width: 100%; margin: 0 auto;">';
                echo do_shortcode( '[wp_ulike_pro_completeness_profile user_id="' . absint( $user_id ) . '"]' );
                echo '</div>';
                echo '</div>';
                break;

            case 'forms':
                echo '<div style="position: relative; padding: 20px; min-height: 300px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 30px;">';
                echo '<div style="width: 100%;">';
                echo do_shortcode( '[wp_ulike_pro_login_form]' );
                echo '</div>';
                echo '<div style="width: 100%;">';
                echo do_shortcode( '[wp_ulike_pro_signup_form]' );
                echo '</div>';
                echo '<div style="width: 100%;">';
                echo do_shortcode( '[wp_ulike_pro_reset_password_form]' );
                echo '</div>';
                echo '</div>';
                break;

            case 'social':
                $default_networks = 'facebook,twitter,linkedin,pinterest,whatsapp';
                $social_items = wp_ulike_get_option( 'social_share', array() );
                $buttons_param = $default_networks;
                $shortcode_atts = array( 'buttons' => $buttons_param );

                if ( ! empty( $social_items ) && is_array( $social_items ) ) {
                    foreach ( $social_items as $item ) {
                        if ( ! empty( $item['slug'] ) && ! empty( $item['buttons'] ) ) {
                            $networks = array();
                            foreach ( $item['buttons'] as $button ) {
                                if ( ! empty( $button['network'] ) ) {
                                    $networks[] = $button['network'];
                                }
                            }
                            if ( ! empty( $networks ) ) {
                                $shortcode_atts['buttons'] = implode( ',', $networks );
                            }
                            if ( ! empty( $item['slug'] ) ) {
                                $shortcode_atts['slug'] = $item['slug'];
                            }
                            if ( ! empty( $item['view'] ) ) {
                                $shortcode_atts['view'] = $item['view'];
                            }
                            if ( ! empty( $item['skin'] ) ) {
                                $shortcode_atts['skin'] = $item['skin'];
                            }
                            if ( ! empty( $item['color'] ) ) {
                                $shortcode_atts['color'] = $item['color'];
                            }
                            if ( ! empty( $item['shape'] ) ) {
                                $shortcode_atts['shape'] = $item['shape'];
                            }
                            break;
                        }
                    }
                }

                $shortcode = '[wp_ulike_pro_social_share';
                foreach ( $shortcode_atts as $key => $value ) {
                    if ( ! empty( $value ) ) {
                        $shortcode .= ' ' . esc_attr( $key ) . '="' . esc_attr( $value ) . '"';
                    }
                }
                $shortcode .= ']';

                echo '<div style="position: relative; padding: 20px; min-height: 200px; display: flex; align-items: center; justify-content: center;">';
                echo '<div style="width: 100%; max-width: 500px;">';
                echo do_shortcode( $shortcode );
                echo '</div>';
                echo '</div>';
                break;
        }

        return ob_get_clean();
    }

}
