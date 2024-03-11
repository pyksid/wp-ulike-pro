<?php

class WP_Ulike_Pro_Admin_Customizer {

    protected $has_permission;

    protected $option_domain = 'wp_ulike_customize';

    /**
     * __construct
     */
    function __construct() {
        $this->has_permission = WP_Ulike_Pro_API::has_permission();
        // Init Hook
        $this->init();
    }

    public function init(){
        // Filters
        add_filter( 'wp_ulike_customizer_button_group_options', array( $this, 'update_button_group_options' ), 10, 1 );
        // Actions
        add_action( 'wp_ulike_customize_ended', array( $this, 'actions' ) );
    }

    public function actions(){
        $this->register_profile_section();
        $this->register_forms_section();
        $this->register_socials_section();
        $this->register_backup_section();
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
        if( ! class_exists( 'ULF' ) ){
            return;
        }

        ULF::createSection( $this->option_domain, array(
            'parent' => WP_ULIKE_SLUG,                           // The slug id of the parent section
            'title'  => esc_html__( 'Profile Template', WP_ULIKE_PRO_DOMAIN ),
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
                    'output_important' => true
                ),
                array(
                    'id'               => 'header_desc_typography',
                    'type'             => 'typography',
                    'title'            => esc_html__( 'Desc Typography', WP_ULIKE_PRO_DOMAIN ),
                    'output'           => '.wp-ulike-pro-section-profile .wp-ulike-pro-profile-desc',
                    'output_important' => true
                ),
                array(
                    'id'               => 'header_avatar_size',
                    'type'             => 'dimensions',
                    'title'            => esc_html__('Avatar Max/Min Size', WP_ULIKE_PRO_DOMAIN),
                    'output_prefix'    => 'max',
                    'output'           => '.fileuploader-theme-avatar',
                    'output_important' => true
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
                    'output'           => '.wp-ulike-pro-section-profile .wp-ulike-pro-badges-section .wp-ulike-pro-badge-info-col .wp-ulike-pro-badge-title',
                    'output_important' => true
                ),
                array(
                    'id'               => 'badges_subtitle_typography',
                    'type'             => 'typography',
                    'title'            => esc_html__( 'Subtitle Typography', WP_ULIKE_PRO_DOMAIN ),
                    'output'           => '.wp-ulike-pro-section-profile .wp-ulike-pro-badges-section .wp-ulike-pro-badge-info-col .wp-ulike-pro-badge-subtitle ',
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
                    'output'           => '.ulp-tabs > .tab_nav .nav_item ',
                    'output_important' => true
                ),
                array(
                    'id'               => 'profile_tabs_border',
                    'type'             => 'border',
                    'title'            => esc_html__( 'Menu Border', WP_ULIKE_PRO_DOMAIN ),
                    'output'           => '.ulp-tabs > .tab_nav',
                    'output_important' => true
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
                    'output_important' => true
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
                    'output_important' => true
                ),
                array(
                    'id'               => 'profile_tabs_content_border',
                    'type'             => 'border',
                    'title'            => esc_html__( 'Content Border', WP_ULIKE_PRO_DOMAIN ),
                    'output'           => '.ulp-tabs .content_wrapper',
                    'output_important' => true
                ),
                array(
                    'id'               => 'profile_tabs_content_spacing',
                    'type'             => 'spacing',
                    'title'            => esc_html__( 'Content Padding', WP_ULIKE_PRO_DOMAIN ),
                    'output'           => '.ulp-tabs .content_wrapper',
                    'output_important' => true
                ),
            )
        ));

    }

    /**
     * Register forms section on customizer panel
     *
     * @return void
     */
    public function register_forms_section(){
        if( ! class_exists( 'ULF' ) ){
            return;
        }

        ULF::createSection( $this->option_domain, array(
            'parent' => WP_ULIKE_SLUG,
            'title'  => esc_html__( 'Login & Signup Forms', WP_ULIKE_PRO_DOMAIN ),
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
                    'output_important' => true
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
                    'output_important' => true
                ),
                array(
                    'id'               => 'forms_margin',
                    'type'             => 'spacing',
                    'title'            => esc_html__( 'Form Margin', WP_ULIKE_PRO_DOMAIN ),
                    'output'           => '.ulp-form',
                    'output_mode'      => 'margin',
                    'output_important' => true
                ),
                array(
                    'id'               => 'forms_spacing',
                    'type'             => 'spinner',
                    'title'            => esc_html__( 'Spacing', WP_ULIKE_PRO_DOMAIN ),
                    'output'           => '[class^="ulp-flex-col-"], [class*="ulp-flex-col-"]',
                    'output_mode'      => 'padding-bottom',
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
                    'output_important' => true
                ),
                array(
                    'id'               => 'forms_input_border',
                    'type'             => 'border',
                    'title'            => esc_html__( 'Border', WP_ULIKE_PRO_DOMAIN ),
                    'output'           => '.ulp-form .ulp-floating-input',
                    'output_important' => true
                ),
                array(
                    'id'               => 'forms_input_border_active',
                    'type'             => 'color',
                    'title'            => esc_html__( 'Border Hover Color', WP_ULIKE_PRO_DOMAIN ),
                    'output'           => 'ulp-form .ulp-floating:hover .ulp-floating-input, .ulp-form .ulp-floating-input:hover, .ulp-form .ulp-floating-input:focus-within',
                    'output_mode'      => 'border-color',
                    'output_important' => true
                ),
                array(
                    'id'               => 'forms_input_padding',
                    'type'             => 'spacing',
                    'title'            => esc_html__( 'Padding', WP_ULIKE_PRO_DOMAIN ),
                    'output'           => '.ulp-form .ulp-floating-input',
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
                    'output_important' => true
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
                    'output_important' => true
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
                    'id'               => 'forms_button_spinner',
                    'type'             => 'background',
                    'background_color' => false,
                    'title'            => esc_html__( 'Loading Spinner', WP_ULIKE_PRO_DOMAIN ),
                    'output'           => '.ulp-form .ulp-loading input[type=submit]',
                    'output_important' => true
                ),
            )
        ));

    }

    /**
     * Register social share section on customizer panel
     *
     * @return void
     */
    public function register_socials_section(){
        if( ! class_exists( 'ULF' ) ){
            return;
        }

        ULF::createSection( $this->option_domain, array(
            'parent' => WP_ULIKE_SLUG,
            'title'  => esc_html__( 'Social Buttons', WP_ULIKE_PRO_DOMAIN ),
            'fields' => array(
                array(
                    'type'    => 'submessage',
                    'style'   => 'info',
                    'content' => esc_html__( 'If you are using official colors for social buttons, note that the color settings of this section will be overwritten on them.', WP_ULIKE_PRO_DOMAIN )
                ),
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
                    'output_important' => true
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
            )
        ));
    }

    /**
     * Register backup section on customizer panel
     *
     * @return void
     */
    public function register_backup_section(){
        if( ! class_exists( 'ULF' ) ){
            return;
        }

        $backup_option = ! $this->has_permission ? array(
            'type'    => 'notice',
            'style'   => 'danger',
            'content' => sprintf( '<p>%s</p><a class="button" href="%s">%s</a>', esc_html__( 'Features of the Pro version are only available once you have registered your license. If you don\'t yet have a license key, get WP ULike Pro now.' , WP_ULIKE_PRO_DOMAIN ), self_admin_url( 'admin.php?page=wp-ulike-pro-license' ), esc_html__( 'Activate License', WP_ULIKE_PRO_DOMAIN ) ),
        ) : array( 'type' => 'backup' );

        ULF::createSection( $this->option_domain, array(
            'parent' => WP_ULIKE_SLUG,
            'title'  => esc_html__( 'Backup',WP_ULIKE_PRO_DOMAIN),
            'fields' => array( $backup_option )
        ) );
    }

}