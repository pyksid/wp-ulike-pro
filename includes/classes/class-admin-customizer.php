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
        add_action( 'wp_ulike_customizer_likers_preview_extra', array( $this, 'render_likers_pile_preview' ) );
    }

    /**
     * License check at call time — API class is not available when this object is constructed.
     *
     * @return bool
     */
    protected function has_permission() {
        return class_exists( 'WP_Ulike_Pro_API' ) && WP_Ulike_Pro_API::has_permission();
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

        // Keep Pro tabs visible, but replace options + hide preview until licensed.
        if ( ! $this->has_permission() ) {
            $this->register_locked_pro_sections();
            return $this->merge_sections_in_nav_order( $sections, $this->sections );
        }

        // Register Pro sections (engagement near Button; account/UI later).
        $this->register_emoji_reactions_section();
        $this->register_star_rating_section();
        $this->register_socials_section();
        $this->register_forms_section();
        $this->register_modals_section();
        $this->register_profile_section();

        // Extend free Likers Box controls for Pro pile avatars (additive selectors only).
        $sections = $this->extend_likers_box_section( $sections );

        // Place engagement tabs after Button; keep Likers/Toast; then remaining Pro tabs.
        return $this->merge_sections_in_nav_order( $sections, $this->sections );
    }

    /**
     * Pro tabs as stubs: keep template/icon for the list, options = license notice only.
     *
     * @return void
     */
    protected function register_locked_pro_sections() {
        $fields = $this->get_license_required_fields();

        foreach ( array(
            array( 'emoji_reactions_template', esc_html__( 'Emoji', 'wp-ulike-pro' ), 'emoji_reactions', 'face-smile' ),
            array( 'star_rating_template', esc_html__( 'Stars', 'wp-ulike-pro' ), 'star_rating', 'star' ),
            array( 'social_buttons', esc_html__( 'Social' ), 'social', 'share' ),
            array( 'login_signup_forms', esc_html__( 'Form', 'wp-ulike-pro' ), 'forms', 'key' ),
            array( 'modals', esc_html__( 'Modal', 'wp-ulike-pro' ), 'modals', 'squares-2x2' ),
            array( 'profile_template', esc_html__( 'Profile' ), 'profile', 'user' ),
        ) as $section ) {
            $this->sections[] = array(
                'parent'   => WP_ULIKE_SLUG,
                'id'       => $section[0],
                'title'    => $section[1],
                'template' => $section[2],
                'icon'     => $section[3],
                'fields'   => $fields,
            );
        }
    }

    /**
     * License notice for locked customizer tabs (settings-panel style).
     *
     * @return array
     */
    protected function get_license_required_fields() {
        return array(
            array(
                'id'      => 'ulp_customizer_license_required',
                'type'    => 'submessage',
                'style'   => 'info',
                'content' => $this->get_license_required_content(),
            ),
        );
    }

    /**
     * Shared license notice markup for controls + preview iframe.
     *
     * @param string $link_target Optional anchor target (e.g. _top for iframe).
     * @return string
     */
    protected function get_license_required_content( $link_target = '' ) {
        $license_url = class_exists( 'WP_Ulike_Pro_License' )
            ? WP_Ulike_Pro_License::get_url()
            : self_admin_url( 'admin.php?page=wp-ulike-pro-license' );

        $target_attr = $link_target !== ''
            ? ' target="' . esc_attr( $link_target ) . '" rel="noopener noreferrer"'
            : '';

        return sprintf(
            '<p><strong>%1$s</strong></p><p>%2$s</p><p><a class="button button-primary" href="%3$s"%4$s>%5$s</a></p>',
            esc_html__( 'License required', 'wp-ulike-pro' ),
            esc_html__( 'You need an active license to customize this Pro feature. Open the License page to activate or refresh your key.', 'wp-ulike-pro' ),
            esc_url( $license_url ),
            $target_attr,
            esc_html__( 'Go to License', 'wp-ulike-pro' )
        );
    }

    /**
     * Merge Pro sections into a consistent nav order without renaming IDs.
     *
     * Button → Emoji → Stars → Likers → Toast → Social → Form → Modal → Profile
     *
     * @param array $base_sections Free (+ parent) sections.
     * @param array $pro_sections  Pro sections in register order.
     * @return array
     */
    protected function merge_sections_in_nav_order( $base_sections, $pro_sections ) {
        $by_id   = array();
        $parent  = array();
        $ordered = array();

        foreach ( (array) $base_sections as $section ) {
            if ( empty( $section['id'] ) ) {
                continue;
            }
            if ( empty( $section['parent'] ) ) {
                $parent[] = $section;
                continue;
            }
            $by_id[ $section['id'] ] = $section;
        }

        foreach ( (array) $pro_sections as $section ) {
            if ( ! empty( $section['id'] ) ) {
                $by_id[ $section['id'] ] = $section;
            }
        }

        $nav_order = array(
            'button_templates',
            'emoji_reactions_template',
            'star_rating_template',
            'likers_box',
            'toast_messages',
            'social_buttons',
            'login_signup_forms',
            'modals',
            'profile_template',
        );

        foreach ( $nav_order as $id ) {
            if ( isset( $by_id[ $id ] ) ) {
                $ordered[] = $by_id[ $id ];
                unset( $by_id[ $id ] );
            }
        }

        foreach ( $by_id as $section ) {
            $ordered[] = $section;
        }

        return array_merge( $parent, $ordered );
    }

    /**
     * Let Avatar Size also target Pro pile images without changing field IDs.
     *
     * @param array $sections Customizer sections.
     * @return array
     */
    protected function extend_likers_box_section( $sections ) {
        if ( empty( $sections ) || ! is_array( $sections ) ) {
            return $sections;
        }

        $pile_selector = '.wp_ulike_likers_wrapper .ulp-pile-list .ulp-img-icon';

        foreach ( $sections as $index => $section ) {
            if ( empty( $section['id'] ) || 'likers_box' !== $section['id'] || empty( $section['fields'] ) || ! is_array( $section['fields'] ) ) {
                continue;
            }

            foreach ( $section['fields'] as $field_index => $field ) {
                if ( empty( $field['id'] ) || 'likers_avatar_dimensions' !== $field['id'] || empty( $field['output'] ) ) {
                    continue;
                }

                if ( false === strpos( $field['output'], $pile_selector ) ) {
                    $sections[ $index ]['fields'][ $field_index ]['output'] = $field['output'] . ', ' . $pile_selector;
                }
            }
        }

        return $sections;
    }

    /**
     * Append Pile layout card to the free Likers Box customizer preview.
     *
     * @return void
     */
    public function render_likers_pile_preview() {
        if ( ! $this->has_permission() || ! defined( 'WP_ULIKE_PRO_PUBLIC_URL' ) ) {
            return;
        }

        $pile_html = $this->get_likers_preview_pile_html();

        echo '<div class="ulp-customizer-button-preview-item ulp-customizer-likers-pile">';
        echo '<p class="ulp-customizer-preview-label">' . esc_html__( 'Pile', 'wp-ulike-pro' ) . '</p>';
        echo '<div class="wpulike wpulike-default">';
        echo '<div class="wp_ulike_general_class wp_ulike_is_not_liked">';
        echo '<button type="button" class="wp_ulike_btn wp_ulike_put_text" disabled aria-hidden="true"><span>' . esc_html__( 'Like', 'wp-ulike' ) . '</span></button>';
        echo '<span class="count-box"><span class="wp_ulike_counter_up">12</span></span>';
        echo '</div>';
        echo '<div class="wp_ulike_likers_wrapper wp_ulike_pile_list_container">';
        // No data-ulpmodal — preview must not open the live modal.
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from escaped URLs
        echo '<div class="ulp-pile-list">' . $pile_html . '</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }

    /**
     * Sample Pro pile avatar stack for customizer preview.
     *
     * @return string
     */
    protected function get_likers_preview_pile_html() {
        $emails = array(
            'preview1@example.com',
            'preview2@example.com',
            'preview3@example.com',
            'preview4@example.com',
        );
        $size       = 40;
        $user_label = __( 'User' );
        $html       = '';

        foreach ( $emails as $email ) {
            $html .= sprintf(
                '<img src="%1$s" class="ulp-img-icon" title="%2$s" alt="%2$s" width="%3$d" height="%3$d" />',
                esc_url( get_avatar_url( $email, array( 'size' => $size ) ) ),
                esc_attr( $user_label ),
                $size
            );
        }

        $html .= sprintf(
            '<img class="ulp-more-icon ulp-img-icon" alt="%1$s" src="%2$s" width="%3$d" height="%3$d" />',
            esc_attr__( 'More' ),
            esc_url( WP_ULIKE_PRO_PUBLIC_URL . '/assets/img/svg/other/more.svg' ),
            $size
        );

        return $html;
    }

    /**
     * Add pro version assets via filter
     *
     * @param array $assets Existing assets
     * @return array Modified assets with pro assets added
     */
    public function add_customizer_assets( $assets ) {
        // Always enqueue Pro preview CSS/JS so Button gallery + free tabs keep working
        // even when the license gate replaces Pro section fields.
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

        // Keep free JS for like/dislike button hover preview; Pro scripts still load after.

        $ver = defined( 'WP_ULIKE_PRO_VERSION' ) ? WP_ULIKE_PRO_VERSION : '1.0.0';

        // Add Pro CSS files (always minified) - marked as pro
        $assets['css'][] = array(
            'url'    => add_query_arg( 'ver', $ver, WP_ULIKE_PRO_PUBLIC_URL . '/assets/css/wp-ulike-pro.min.css' ),
            'source' => 'pro',
            'ver'    => $ver,
        );
        $assets['css'][] = array(
            'url'    => add_query_arg( 'ver', $ver, WP_ULIKE_PRO_PUBLIC_URL . '/assets/css/uploader.min.css' ),
            'source' => 'pro',
            'ver'    => $ver,
        );
        $assets['css'][] = array(
            'url'    => add_query_arg( 'ver', $ver, WP_ULIKE_PRO_PUBLIC_URL . '/assets/css/customizer-preview.css' ),
            'source' => 'pro',
            'ver'    => $ver,
        );

        // Add Pro JS files (always minified) - marked as pro
        $assets['js'][] = array(
            'url'    => add_query_arg( 'ver', $ver, WP_ULIKE_PRO_PUBLIC_URL . '/assets/js/wp-ulike-pro.min.js' ),
            'source' => 'pro',
            'ver'    => $ver,
        );
        $assets['js'][] = array(
            'url'    => add_query_arg( 'ver', $ver, WP_ULIKE_PRO_PUBLIC_URL . '/assets/js/solo/share.min.js' ),
            'source' => 'pro',
            'ver'    => $ver,
        );
        $assets['js'][] = array(
            'url'    => add_query_arg( 'ver', $ver, WP_ULIKE_PRO_PUBLIC_URL . '/assets/js/solo/uploader.min.js' ),
            'source' => 'pro',
            'ver'    => $ver,
        );

        // Always in preview mode in customizer context
        $ajax_url = add_query_arg( array( 'preview' => true ), admin_url( 'admin-ajax.php' ) );

        // UlikeProCommonConfig - marked as pro
        $assets['localized_scripts']['UlikeProCommonConfig'] = array(
            'data' => array(
                'AjaxUrl' => $ajax_url,
                'Nonce'   => wp_create_nonce( WP_ULIKE_PRO_DOMAIN ),
                'TabSide' => wp_ulike_get_option( 'user_profiles_appearance|tabs_side', 'top' ),
                // Preview iframe is for styling only — no view tracking or engagement AJAX.
                'ViewTracking' => array(
                    'enabledTypes' => array(),
                ),
                'Engagements' => array(
                    'action'  => 'wp_ulike_pro_engagement_process',
                    'enabled' => false,
                    'i18n'    => WP_Ulike_Pro_Options::getEngagementI18n(),
                ),
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
        if ( ! $this->has_permission() ) {
            return $options;
        }

        $options = $this->extend_icon_color_outputs( $options );

        $options[0]['fields'][] =  array(
            'id'               => 'normal_dislike_image',
            'type'             => 'background',
            'background_color' => false,
            'title'            => esc_html__( 'Dislike Image', 'wp-ulike-pro' ),
            'output'           => '.wpulike .wp_ulike_general_class.wpulike_down_vote .wp_ulike_btn.wp_ulike_put_image::after',
        );
        $options[1]['fields'][] =  array(
            'id'               => 'hover_dislike_image',
            'type'             => 'background',
            'background_color' => false,
            'output_important' => true,
            'title'            => esc_html__( 'Dislike Image', 'wp-ulike-pro' ),
            'output'           => '.wpulike .wp_ulike_general_class.wpulike_down_vote .wp_ulike_btn.wp_ulike_put_image:hover::after',
        );
        $options[2]['fields'][] =  array(
            'id'               => 'active_dislike_image',
            'type'             => 'background',
            'background_color' => false,
            'title'            => esc_html__( 'Dislike Image', 'wp-ulike-pro' ),
            'output'           => '.wpulike .wp_ulike_general_class.wpulike_down_vote .wp_ulike_btn.wp_ulike_btn_is_active.wp_ulike_put_image::after',
        );

        return $options;

    }

    /**
     * Wire Icon Color to Pro templates that do not use ::after background SVGs.
     *
     * - Fave Star: font icon → color
     * - Smiley Switch: smiley on ::before → filter (via output selector)
     * - Clapping: inline SVG → stroke (idle) / fill (active)
     *
     * @param array $options Button group tabs.
     * @return array
     */
    protected function extend_icon_color_outputs( $options ) {
        if ( ! is_array( $options ) ) {
            return $options;
        }

        $map = array(
            'normal_icon_color' => array(
                'output_append' => ', .wpulike-smiley-switch .wp_ulike_general_class .wp_ulike_btn.wp_ulike_put_image::before',
                'also'          => array(
                    array(
                        'selector' => '.wpulike-fave-star .wp_ulike_star_icon, .wpulike-fave-star .wp_ulike_btn_is_active .wp_ulike_star_icon',
                        'property' => 'color',
                    ),
                    array(
                        'selector' => '.wpulike-clapping .clap-icon-svg, .wpulike-clapping .clap-icon-svg path',
                        'property' => 'stroke',
                    ),
                    array(
                        'selector' => '.wpulike-clapping .wp_ulike_btn_is_active .clap-icon-svg, .wpulike-clapping .wp_ulike_btn_is_active .clap-icon-svg path',
                        'property' => 'fill',
                    ),
                ),
            ),
            'hover_icon_color' => array(
                'output_append' => ', .wpulike-smiley-switch .wp_ulike_general_class .wp_ulike_btn.wp_ulike_put_image:hover::before',
                'also'          => array(
                    array(
                        'selector' => '.wpulike-fave-star .wp_ulike_btn:hover .wp_ulike_star_icon',
                        'property' => 'color',
                    ),
                    array(
                        'selector' => '.wpulike-clapping .wp_ulike_btn:hover .clap-icon-svg, .wpulike-clapping .wp_ulike_btn:hover .clap-icon-svg path',
                        'property' => 'stroke',
                    ),
                ),
            ),
            'active_icon_color' => array(
                'output_append' => ', .wpulike-smiley-switch .wp_ulike_general_class .wp_ulike_btn.wp_ulike_btn_is_active.wp_ulike_put_image::before',
                'also'          => array(
                    array(
                        'selector' => '.wpulike-fave-star button.wp_ulike_btn.wp_ulike_btn_is_active .wp_ulike_star_icon',
                        'property' => 'color',
                    ),
                    array(
                        'selector' => '.wpulike-clapping button.wp_ulike_btn.wp_ulike_btn_is_active .clap-icon-svg, .wpulike-clapping button.wp_ulike_btn.wp_ulike_btn_is_active .clap-icon-svg path',
                        'property' => 'fill',
                    ),
                ),
            ),
            'removed_icon_color' => array(
                'output_append' => ', .wpulike-smiley-switch .wp_ulike_general_class.wp_ulike_is_unliked .wp_ulike_btn.wp_ulike_put_image::before, .wpulike-smiley-switch .wp_ulike_general_class.wp_ulike_is_already_unliked .wp_ulike_btn.wp_ulike_put_image::before',
                'also'          => array(
                    array(
                        'selector' => '.wpulike-fave-star .wp_ulike_is_unliked .wp_ulike_star_icon, .wpulike-fave-star .wp_ulike_is_already_unliked .wp_ulike_star_icon',
                        'property' => 'color',
                    ),
                    array(
                        'selector' => '.wpulike-clapping .wp_ulike_is_unliked .clap-icon-svg, .wpulike-clapping .wp_ulike_is_unliked .clap-icon-svg path, .wpulike-clapping .wp_ulike_is_already_unliked .clap-icon-svg, .wpulike-clapping .wp_ulike_is_already_unliked .clap-icon-svg path',
                        'property' => 'stroke',
                    ),
                ),
            ),
        );

        foreach ( $options as $tab_index => $tab ) {
            if ( empty( $tab['fields'] ) || ! is_array( $tab['fields'] ) ) {
                continue;
            }

            foreach ( $tab['fields'] as $field_index => $field ) {
                if ( empty( $field['id'] ) || ! isset( $map[ $field['id'] ] ) ) {
                    continue;
                }

                $extra = $map[ $field['id'] ];

                if ( ! empty( $extra['output_append'] ) && ! empty( $field['output'] ) ) {
                    $options[ $tab_index ]['fields'][ $field_index ]['output'] = $field['output'] . $extra['output_append'];
                }

                if ( ! empty( $extra['also'] ) && is_array( $extra['also'] ) ) {
                    $existing = ! empty( $field['output_also'] ) && is_array( $field['output_also'] )
                        ? $field['output_also']
                        : array();
                    $options[ $tab_index ]['fields'][ $field_index ]['output_also'] = array_merge( $existing, $extra['also'] );
                }
            }
        }

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
            'title'  => esc_html__( 'Profile' ),
            'template' => 'profile',
            'icon'   => 'user',
            'field_browser' => true,
            'fields' => array(
                array(
                    'id'      => 'profile_header_heading',
                    'type'    => 'heading',
                    'content' => esc_html__( 'Header' ),
                ),
                array(
                    'id'               => 'header_name_typography',
                    'type'             => 'typography',
                    'responsive'       => true,
                    'title'            => esc_html__( 'Name' ),
                    'output'           => '.wp-ulike-pro-section-profile .wp-ulike-pro-profile-name',
                    'output_important' => true,
                    'units'            => array('px', 'em', 'rem')
                ),
                array(
                    'id'               => 'header_desc_typography',
                    'type'             => 'typography',
                    'responsive'       => true,
                    'title'            => esc_html__( 'Description' ),
                    'output'           => '.wp-ulike-pro-section-profile .wp-ulike-pro-profile-desc',
                    'output_important' => true,
                    'units'            => array('px', 'em', 'rem')
                ),
                array(
                    'id'               => 'header_custom_info_typography',
                    'type'             => 'typography',
                    'responsive'       => true,
                    'title'            => esc_html__( 'Info Typography', 'wp-ulike-pro' ),
                    'output'           => '.wp-ulike-pro-section-profile .wp-ulike-pro-profile-custom-info',
                    'output_important' => true,
                    'units'            => array('px', 'em', 'rem')
                ),
                array(
                    'id'               => 'header_avatar_size',
                    'type'             => 'dimensions',
                    'responsive'       => true,
                    'title'            => esc_html__( 'Avatar Size', 'wp-ulike-pro' ),
                    'output_prefix'    => 'max',
                    // Cover legacy fileuploader wrapper + plain / local avatar markup.
                    'output'           => '.fileuploader-theme-avatar, .wp-ulike-pro-profile-user-avatar img, .wp-ulike-pro-profile-user-avatar .ulp-avatar-preview',
                    'output_important' => true,
                    'units'            => array('px', 'em', 'rem')
                ),
                array(
                    'id'               => 'header_action_buttons_typography',
                    'type'             => 'typography',
                    'responsive'       => true,
                    'title'            => esc_html__( 'Button Typography', 'wp-ulike-pro' ),
                    'output'           => '.wp-ulike-pro-section-profile .wp-ulike-pro-profile-action-btn',
                    'output_important' => true,
                    'units'            => array('px', 'em', 'rem')
                ),
                array(
                    'id'               => 'header_action_buttons_background',
                    'type'             => 'color',
                    'title'            => esc_html__( 'Button Background', 'wp-ulike-pro' ),
                    'output'           => '.wp-ulike-pro-section-profile .wp-ulike-pro-profile-action-btn',
                    'output_mode'      => 'background-color',
                    'output_important' => true
                ),
                array(
                    'id'               => 'header_action_buttons_hover_background',
                    'type'             => 'color',
                    'title'            => esc_html__( 'Button Hover Background', 'wp-ulike-pro' ),
                    'output'           => '.wp-ulike-pro-section-profile .wp-ulike-pro-profile-action-btn:hover',
                    'output_mode'      => 'background-color',
                    'output_important' => true
                ),
                array(
                    'id'               => 'header_action_buttons_border',
                    'type'             => 'border',
                    'title'            => esc_html__( 'Button Border', 'wp-ulike-pro' ),
                    'output'           => '.wp-ulike-pro-section-profile .wp-ulike-pro-profile-action-btn',
                    'output_important' => true,
                    'units'            => array('px', 'em', 'rem')
                ),
                array(
                    'id'               => 'header_action_buttons_padding',
                    'type'             => 'spacing',
                    'responsive'       => true,
                    'title'            => esc_html__( 'Button Padding', 'wp-ulike-pro' ),
                    'output'           => '.wp-ulike-pro-section-profile .wp-ulike-pro-profile-action-btn',
                    'output_important' => true,
                    'units'            => array('px', 'em', 'rem')
                ),
                // Badges
                array(
                    'id'      => 'profile_badges_heading',
                    'type'    => 'heading',
                    'content' => esc_html__( 'Badges', 'wp-ulike-pro' ),
                ),
                array(
                    'id'               => 'badges_title_typography',
                    'type'             => 'typography',
                    'responsive'       => true,
                    'title'            => esc_html__( 'Title' ),
                    'output'           => '.wp-ulike-pro-section-profile .wp-ulike-pro-badges-section .wp-ulike-pro-badge-info .wp-ulike-pro-badge-title',
                    'output_important' => true,
                    'units'            => array('px', 'em', 'rem')
                ),
                array(
                    'id'               => 'badges_subtitle_typography',
                    'type'             => 'typography',
                    'responsive'       => true,
                    'title'            => esc_html__( 'Subtitle', 'wp-ulike-pro' ),
                    'output'           => '.wp-ulike-pro-section-profile .wp-ulike-pro-badges-section .wp-ulike-pro-badge-info .wp-ulike-pro-badge-subtitle',
                    'output_important' => true,
                    'units'            => array('px', 'em', 'rem')
                ),
                array(
                    'id'               => 'badges_item_hover_background',
                    'type'             => 'color',
                    'title'            => esc_html__( 'Hover Background', 'wp-ulike-pro' ),
                    'output'           => '.wp-ulike-pro-section-profile .wp-ulike-pro-badge-item:hover',
                    'output_mode'      => 'background-color',
                    'output_important' => true
                ),
                array(
                    'id'               => 'badges_item_spacing',
                    'type'             => 'spacing',
                    'responsive'       => true,
                    'title'            => esc_html__( 'Padding', 'wp-ulike-pro' ),
                    'output'           => '.wp-ulike-pro-section-profile .wp-ulike-pro-badge-item',
                    'output_important' => true,
                    'units'            => array('px', 'em', 'rem')
                ),
                                                                // Tabs
                array(
                    'id'      => 'profile_tabs_heading',
                    'type'    => 'heading',
                    'content' => esc_html__( 'Tabs', 'wp-ulike-pro' ),
                ),
                array(
                    'id'               => 'profile_tabs_typography',
                    'type'             => 'typography',
                    'responsive'       => true,
                    'title'            => esc_html__( 'Typography', 'wp-ulike-pro' ),
                    'output'           => '.ulp-tabs > .tab_nav .nav_item',
                    'output_important' => true,
                    'units'            => array('px', 'em', 'rem')
                ),
                array(
                    'id'               => 'profile_tabs_color',
                    'type'             => 'color',
                    'title'            => esc_html__( 'Color' ),
                    'output'           => '.ulp-tabs > .tab_nav .nav_item',
                    'output_important' => true
                ),
                array(
                    'id'               => 'profile_tabs_hover_color',
                    'type'             => 'color',
                    'title'            => esc_html__( 'Hover Color', 'wp-ulike-pro' ),
                    'output'           => '.ulp-tabs > .tab_nav .nav_item:hover',
                    'output_important' => true
                ),
                array(
                    'id'               => 'profile_tabs_active_color',
                    'type'             => 'color',
                    'title'            => esc_html__( 'Active Color', 'wp-ulike-pro' ),
                    'output'           => '.ulp-tabs > .tab_nav .nav_item.active',
                    'output_important' => true
                ),
                array(
                    'id'               => 'profile_tabs_border',
                    'type'             => 'border',
                    'title'            => esc_html__( 'Border' ),
                    'output'           => '.ulp-tabs > .tab_nav',
                    'output_important' => true,
                    'units'            => array('px', 'em', 'rem')
                ),
                array(
                    'id'               => 'profile_tabs_border_active_color',
                    'type'             => 'color',
                    'title'            => esc_html__( 'Active Border Color', 'wp-ulike-pro' ),
                    'output'           => '.ulp-tabs > .tab_nav .nav_item.active',
                    'output_mode'      => 'border-color',
                    'output_important' => true
                ),
                array(
                    'id'               => 'profile_tabs_secondary_color',
                    'type'             => 'color',
                    'title'            => esc_html__( 'Indicator', 'wp-ulike-pro' ),
                    'output'           => '.ulp-tabs.left_side > .tab_nav .nav_item::after, .ulp-tabs.right_side > .tab_nav .nav_item::after',
                    'output_mode'      => 'background-color',
                    'output_important' => true
                ),
                array(
                    'id'               => 'profile_tabs_main_color',
                    'type'             => 'color',
                    'title'            => esc_html__( 'Active Indicator', 'wp-ulike-pro' ),
                    'output'           => '.ulp-tabs .controller span, .ulp-tabs.left_side > .tab_nav .nav_item.active:after, .ulp-tabs.right_side > .tab_nav .nav_item.active:after',
                    'output_mode'      => 'background-color',
                    'output_important' => true
                ),
                array(
                    'id'               => 'profile_tabs_spacing',
                    'type'             => 'spacing',
                    'responsive'       => true,
                    'title'            => esc_html__( 'Padding', 'wp-ulike-pro' ),
                    'output'           => '.ulp-tabs > .tab_nav .nav_item',
                    'output_important' => true,
                    'units'            => array('px', 'em', 'rem')
                ),
                array(
                    'id'               => 'profile_tabs_content_typography',
                    'type'             => 'typography',
                    'responsive'       => true,
                    'title'            => esc_html__( 'Content Typography', 'wp-ulike-pro' ),
                    'output'           => '.ulp-tabs .content_wrapper',
                    'output_important' => true,
                    'units'            => array('px', 'em', 'rem')
                ),
                array(
                    'id'               => 'profile_tabs_content_background',
                    'type'             => 'color',
                    'title'            => esc_html__( 'Content Background', 'wp-ulike-pro' ),
                    'output'           => '.ulp-tabs .content_wrapper',
                    'output_mode'      => 'background-color',
                    'output_important' => true
                ),
                array(
                    'id'               => 'profile_tabs_menu_background',
                    'type'             => 'color',
                    'title'            => esc_html__( 'Side Menu Background', 'wp-ulike-pro' ),
                    'output'           => '.ulp-tabs.left_side > .tab_nav, .ulp-tabs.right_side > .tab_nav',
                    'output_mode'      => 'background-color',
                    'output_important' => true
                ),
                array(
                    'id'               => 'profile_tabs_content_border',
                    'type'             => 'border',
                    'title'            => esc_html__( 'Content Border', 'wp-ulike-pro' ),
                    'output'           => '.ulp-tabs .content_wrapper',
                    'output_important' => true,
                    'units'            => array('px', 'em', 'rem')
                ),
                array(
                    'id'               => 'profile_tabs_content_spacing',
                    'type'             => 'spacing',
                    'responsive'       => true,
                    'title'            => esc_html__( 'Content Padding', 'wp-ulike-pro' ),
                    'output'           => '.ulp-tabs .content_wrapper',
                    'output_important' => true,
                    'units'            => array('px', 'em', 'rem')
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
            'title'  => esc_html__( 'Form', 'wp-ulike-pro' ),
            'template' => 'forms',
            'icon'   => 'key',
            'field_browser' => true,
            'fields' => array(
                array(
                    'id'      => 'forms_wrapper_heading',
                    'type'    => 'heading',
                    'content' => esc_html__( 'Wrapper', 'wp-ulike-pro' ),
                ),
                array(
                    'id'               => 'forms_typography',
                    'type'             => 'typography',
                    'responsive'       => true,
                    'title'            => esc_html__( 'Typography', 'wp-ulike-pro' ),
                    'output'           => '.ulp-form, .ulp-form p, .ulp-form span, .ulp-form label',
                    'output_important' => true,
                    'units'            => array('px', 'em', 'rem')
                ),
                array(
                    'id'               => 'forms_max_width',
                    'type'             => 'slider',
                    'responsive'       => true,
                    'output_mode'      => 'max-width',
                    'title'            => esc_html__( 'Max Width' ),
                    'output'           => '.ulp-form',
                    'min'              => 240,
                    'max'              => 1600,
                    'unit'             => 'px',
                    'output_important' => true
                ),
                array(
                    'id'               => 'forms_margin',
                    'type'             => 'spacing',
                    'responsive'       => true,
                    'title'            => esc_html__( 'Margin', 'wp-ulike-pro' ),
                    'output'           => '.ulp-form',
                    'output_mode'      => 'margin',
                    'output_important' => true,
                    'units'            => array('px', 'em', 'rem')
                ),
                array(
                    'id'               => 'forms_spacing',
                    'type'             => 'spinner',
                    'responsive'       => true,
                    'title'            => esc_html__( 'Spacing', 'wp-ulike-pro' ),
                    'output'           => '[class^="ulp-flex-col-"], [class*="ulp-flex-col-"]',
                    'output_mode'      => 'padding-bottom',
                    'unit'             => 'px',
                    'output_important' => true
                ),
                // Inputs
                array(
                    'id'      => 'forms_inputs_heading',
                    'type'    => 'heading',
                    'content' => esc_html__( 'Inputs', 'wp-ulike-pro' ),
                ),
                array(
                    'id'               => 'forms_input_typography',
                    'type'             => 'typography',
                    'responsive'       => true,
                    'title'            => esc_html__( 'Typography', 'wp-ulike-pro' ),
                    'output'           => '.ulp-form .ulp-floating-input',
                    'output_important' => true,
                    'units'            => array('px', 'em', 'rem')
                ),
                array(
                    'id'               => 'forms_input_background',
                    'type'             => 'color',
                    'title'            => esc_html__( 'Background' ),
                    'output'           => '.ulp-form .ulp-floating-input',
                    'output_mode'      => 'background-color',
                    'output_important' => true
                ),
                array(
                    'id'               => 'forms_input_border',
                    'type'             => 'border',
                    'title'            => esc_html__( 'Border' ),
                    'output'           => '.ulp-form .ulp-floating-input',
                    'output_important' => true,
                    'units'            => array('px', 'em', 'rem')
                ),
                array(
                    'id'               => 'forms_input_border_radius',
                    'type'             => 'slider',
                    'title'            => esc_html__( 'Border Radius', 'wp-ulike-pro' ),
                    'output'           => '.ulp-form .ulp-floating-input',
                    'output_mode'      => 'border-radius',
                    'min'              => 0,
                    'max'              => 50,
                    'unit'             => 'px',
                    'output_important' => true
                ),
                array(
                    'id'               => 'forms_input_border_active',
                    'type'             => 'color',
                    'title'            => esc_html__( 'Focus Color', 'wp-ulike-pro' ),
                    // Match frontend: hover/focus only for non-error / non-success inputs.
                    'output'           => '.ulp-form .ulp-floating:hover .ulp-floating-input:not(.ulp-input-error):not(.ulp-input-success), .ulp-form .ulp-floating-input:hover:not(.ulp-input-error):not(.ulp-input-success), .ulp-form .ulp-floating-input:focus:not(.ulp-input-error):not(.ulp-input-success), .ulp-form .ulp-floating-input:focus-within:not(.ulp-input-error):not(.ulp-input-success), .ulp-form .ulp-customizer-forms-focus .ulp-floating-input:not(.ulp-input-error):not(.ulp-input-success)',
                    'output_mode'      => 'border-color',
                    'output_important' => true,
                    'focus_ring'       => true,
                    'focus_ring_alpha' => 0.12,
                    'focus_ring_selector' => '.ulp-form .ulp-floating-input:focus:not(.ulp-input-error):not(.ulp-input-success), .ulp-form .ulp-floating-input:focus-within:not(.ulp-input-error):not(.ulp-input-success), .ulp-form .ulp-customizer-forms-focus .ulp-floating-input:not(.ulp-input-error):not(.ulp-input-success)',
                ),
                array(
                    'id'               => 'forms_input_error_border',
                    'type'             => 'color',
                    'title'            => esc_html__( 'Error Color', 'wp-ulike-pro' ),
                    // Cover idle + hover + focus (and preview focus mock).
                    'output'           => '.ulp-form .ulp-floating-input.ulp-input-error, .ulp-form .ulp-floating-input.ulp-input-error:hover, .ulp-form .ulp-floating-input.ulp-input-error:focus, .ulp-form .ulp-floating-input.ulp-input-error:focus-within, .ulp-form .ulp-customizer-forms-focus .ulp-floating-input.ulp-input-error',
                    'output_mode'      => 'border-color',
                    'output_important' => true,
                    'focus_ring'       => true,
                    'focus_ring_alpha' => 0.18,
                    'output_also'      => array(
                        array(
                            'selector' => '.ulp-form .ulp-floating-input.ulp-input-error + .ulp-floating-label::before, .ulp-form .ulp-floating-input.ulp-input-error:hover + .ulp-floating-label::before, .ulp-form .ulp-floating-input.ulp-input-error:focus + .ulp-floating-label::before, .ulp-form .ulp-floating-input.ulp-input-error:focus-within + .ulp-floating-label::before, .ulp-form .ulp-customizer-forms-focus .ulp-floating-input.ulp-input-error + .ulp-floating-label::before',
                            'property' => 'color',
                        ),
                    ),
                ),
                array(
                    'id'               => 'forms_input_success_border',
                    'type'             => 'color',
                    'title'            => esc_html__( 'Success Color', 'wp-ulike-pro' ),
                    'output'           => '.ulp-form .ulp-floating-input.ulp-input-success, .ulp-form .ulp-floating-input.ulp-input-success:hover, .ulp-form .ulp-floating-input.ulp-input-success:focus, .ulp-form .ulp-floating-input.ulp-input-success:focus-within, .ulp-form .ulp-customizer-forms-focus .ulp-floating-input.ulp-input-success',
                    'output_mode'      => 'border-color',
                    'output_important' => true,
                    'focus_ring'       => true,
                    'focus_ring_alpha' => 0.18,
                    'output_also'      => array(
                        array(
                            'selector' => '.ulp-form .ulp-floating-input.ulp-input-success + .ulp-floating-label::before, .ulp-form .ulp-floating-input.ulp-input-success:hover + .ulp-floating-label::before, .ulp-form .ulp-floating-input.ulp-input-success:focus + .ulp-floating-label::before, .ulp-form .ulp-floating-input.ulp-input-success:focus-within + .ulp-floating-label::before, .ulp-form .ulp-customizer-forms-focus .ulp-floating-input.ulp-input-success + .ulp-floating-label::before',
                            'property' => 'color',
                        ),
                    ),
                ),
                array(
                    'id'               => 'forms_input_padding',
                    'type'             => 'spacing',
                    'responsive'       => true,
                    'title'            => esc_html__( 'Padding', 'wp-ulike-pro' ),
                    'output'           => '.ulp-form .ulp-floating-input',
                    'output_important' => true,
                    'units'            => array('px', 'em', 'rem')
                ),
                // Labels
                array(
                    'id'      => 'forms_labels_heading',
                    'type'    => 'heading',
                    'content' => esc_html__( 'Label', 'wp-ulike-pro' ),
                ),
                array(
                    'id'               => 'forms_label_color',
                    'type'             => 'color',
                    'title'            => esc_html__( 'Color' ),
                    'output'           => '.ulp-form .ulp-floating-label::before',
                    'output_important' => true
                ),
                array(
                    'id'               => 'forms_active_label_color',
                    'type'             => 'color',
                    'title'            => esc_html__( 'Focus Color', 'wp-ulike-pro' ),
                    // Default focus only — error/success label colors follow their border fields.
                    'output'           => '.ulp-form .ulp-floating-input:focus:not(.ulp-input-error):not(.ulp-input-success) + .ulp-floating-label::before, .ulp-form .ulp-floating-input:focus-within:not(.ulp-input-error):not(.ulp-input-success) + .ulp-floating-label::before, .ulp-form .ulp-customizer-forms-focus .ulp-floating-input:not(.ulp-input-error):not(.ulp-input-success) + .ulp-floating-label::before',
                    'output_important' => true
                ),

                // Button
                array(
                    'id'      => 'forms_button_heading',
                    'type'    => 'heading',
                    'content' => esc_html__( 'Button' ),
                ),
                array(
                    'id'               => 'forms_button_typography',
                    'type'             => 'typography',
                    'responsive'       => true,
                    'title'            => esc_html__( 'Typography', 'wp-ulike-pro' ),
                    'output'           => '.ulp-form .ulp-button',
                    'output_important' => true,
                    'units'            => array('px', 'em', 'rem')
                ),
                array(
                    'id'               => 'forms_button_hover_color',
                    'type'             => 'color',
                    'title'            => esc_html__( 'Hover Color', 'wp-ulike-pro' ),
                    'output'           => '.ulp-form .ulp-button:hover, .ulp-form .ulp-button:focus, .ulp-form .ulp-button:active',
                    'output_important' => true
                ),
                array(
                    'id'               => 'forms_button_background',
                    'type'             => 'color',
                    'title'            => esc_html__( 'Background' ),
                    'output'           => '.ulp-form .ulp-button',
                    'output_mode'      => 'background-color',
                    'output_important' => true
                ),
                array(
                    'id'               => 'forms_button_hover_background',
                    'type'             => 'color',
                    'title'            => esc_html__( 'Hover Background', 'wp-ulike-pro' ),
                    'output'           => '.ulp-form .ulp-button:hover, .ulp-form .ulp-button:focus, .ulp-form .ulp-button:active',
                    'output_mode'      => 'background-color',
                    'output_important' => true
                ),
                array(
                    'id'               => 'forms_button_border',
                    'type'             => 'border',
                    'title'            => esc_html__( 'Border' ),
                    'output'           => '.ulp-form .ulp-button',
                    'output_important' => true,
                    'units'            => array('px', 'em', 'rem')
                ),
                array(
                    'id'               => 'forms_button_border_radius',
                    'type'             => 'slider',
                    'title'            => esc_html__( 'Border Radius', 'wp-ulike-pro' ),
                    'output'           => '.ulp-form .ulp-button',
                    'output_mode'      => 'border-radius',
                    'min'              => 0,
                    'max'              => 50,
                    'unit'             => 'px',
                    'output_important' => true
                ),
                array(
                    'id'               => 'forms_button_padding',
                    'type'             => 'spacing',
                    'responsive'       => true,
                    'title'            => esc_html__( 'Padding', 'wp-ulike-pro' ),
                    'output'           => '.ulp-form .ulp-button',
                    'output_important' => true,
                    'units'            => array('px', 'em', 'rem')
                ),
                array(
                    'id'               => 'forms_button_spinner',
                    'type'             => 'background',
                    'background_color' => false,
                    'title'            => esc_html__( 'Spinner', 'wp-ulike-pro' ),
                    'output'           => '.ulp-form .ulp-loading input[type=submit]',
                    'output_important' => true
                ),
                // Links
                array(
                    'id'      => 'forms_links_heading',
                    'type'    => 'heading',
                    'content' => esc_html__( 'Links' ),
                ),
                array(
                    'id'               => 'forms_link_color',
                    'type'             => 'color',
                    'title'            => esc_html__( 'Color' ),
                    'output'           => '.ulp-form a',
                    'output_important' => true
                ),
                array(
                    'id'               => 'forms_link_hover_color',
                    'type'             => 'color',
                    'title'            => esc_html__( 'Hover Color', 'wp-ulike-pro' ),
                    'output'           => '.ulp-form a:hover',
                    'output_important' => true
                ),
            )
        );
    }

    /**
     * Register shared modal chrome + pile/likers modal list styles.
     * Covers login require modal, pile likers modal, and engagers modal shell.
     *
     * @return void
     */
    public function register_modals_section() {
        $this->sections[] = array(
            'parent'   => WP_ULIKE_SLUG,
            'id'       => 'modals',
            'title'    => esc_html__( 'Modal', 'wp-ulike-pro' ),
            'template' => 'modals',
            'icon'     => 'squares-2x2',
            'fields'   => array(
                array(
                    'type'    => 'heading',
                    'content' => esc_html__( 'Panel', 'wp-ulike-pro' ),
                ),
                array(
                    'id'               => 'modal_title_typography',
                    'type'             => 'typography',
                    'responsive'       => true,
                    'title'            => esc_html__( 'Title' ),
                    'output'           => '.ulpmodal .ulpmodal-header__title, .ulpmodal .ulpmodal-header__title .ulpmodal-title, .ulpmodal .ulpmodal-title',
                    'output_important' => true,
                    'units'            => array( 'px', 'em', 'rem' ),
                ),
                array(
                    'id'               => 'modal_panel_width',
                    'type'             => 'slider',
                    'responsive'       => true,
                    'title'            => esc_html__( 'Width' ),
                    'subtitle'         => esc_html__( 'Max width of the modal panel (stays fluid on small screens).', 'wp-ulike-pro' ),
                    'output'           => '.ulpmodal .ulpmodal-content',
                    'output_mode'      => 'max-width',
                    'min'              => 240,
                    'max'              => 720,
                    'unit'             => 'px',
                    'output_important' => true,
                ),
                array(
                    'id'               => 'modal_overlay',
                    'type'             => 'color',
                    'title'            => esc_html__( 'Overlay', 'wp-ulike-pro' ),
                    'output'           => '.ulpmodal',
                    'output_mode'      => 'background-color',
                    'output_important' => true,
                ),
                array(
                    'id'               => 'modal_panel_background',
                    'type'             => 'color',
                    'title'            => esc_html__( 'Background' ),
                    'output'           => '.ulpmodal .ulpmodal-content',
                    'output_mode'      => 'background-color',
                    'output_important' => true,
                ),
                array(
                    'id'               => 'modal_close_color',
                    'type'             => 'color',
                    'title'            => esc_html__( 'Close' ),
                    'output'           => '.ulpmodal .ulpmodal-close-icon',
                    'output_important' => true,
                ),
                array(
                    'id'               => 'modal_close_hover_color',
                    'type'             => 'color',
                    'title'            => esc_html__( 'Close Hover', 'wp-ulike-pro' ),
                    'output'           => '.ulpmodal .ulpmodal-close-icon:hover',
                    'output_important' => true,
                ),
                array(
                    'id'               => 'modal_panel_border',
                    'type'             => 'border',
                    'title'            => esc_html__( 'Border' ),
                    'output'           => '.ulpmodal .ulpmodal-content',
                    'output_important' => true,
                    'units'            => array( 'px', 'em', 'rem' ),
                ),
                array(
                    'id'               => 'modal_panel_radius',
                    'type'             => 'slider',
                    'title'            => esc_html__( 'Border Radius', 'wp-ulike-pro' ),
                    'output'           => '.ulpmodal .ulpmodal-content',
                    'output_mode'      => 'border-radius',
                    'min'              => 0,
                    'max'              => 40,
                    'unit'             => 'px',
                    'output_important' => true,
                ),
                array(
                    'id'               => 'modal_body_padding',
                    'type'             => 'spacing',
                    'responsive'       => true,
                    'title'            => esc_html__( 'Padding', 'wp-ulike-pro' ),
                    'output'           => '.ulpmodal .ulpmodal-inner, .ulpmodal.ulpmodal-ajax .ulpmodal-inner',
                    'output_important' => true,
                    'units'            => array( 'px', 'em', 'rem' ),
                ),
                array(
                    'type'    => 'heading',
                    'content' => esc_html__( 'Likers', 'wp-ulike-pro' ),
                ),
                array(
                    'id'               => 'modal_list_title_typography',
                    'type'             => 'typography',
                    'responsive'       => true,
                    'title'            => esc_html__( 'Name' ),
                    'output'           => '.ulp-modal-likers-list .ulp-title',
                    'output_important' => true,
                    'units'            => array( 'px', 'em', 'rem' ),
                ),
                array(
                    'id'               => 'modal_list_avatar_dimensions',
                    'type'             => 'dimensions',
                    'responsive'       => true,
                    'title'            => esc_html__( 'Avatar Size', 'wp-ulike-pro' ),
                    'output'           => '.ulp-modal-likers-list .ulp-img-icon, .ulp-engagement-engagers-list .ulp-engagement-engager-avatar img',
                    'output_important' => true,
                    'units'            => array( 'px', 'em', 'rem' ),
                ),
                array(
                    'id'               => 'modal_list_row_hover',
                    'type'             => 'color',
                    'title'            => esc_html__( 'Hover Background', 'wp-ulike-pro' ),
                    'output'           => '.ulp-modal-likers-list .ulp-flex-row:hover, .ulp-modal-likers-list a.ulp-flex-row:hover',
                    'output_mode'      => 'background-color',
                    'output_important' => true,
                ),
                                            ),
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
            'title'  => esc_html__( 'Social' ),
            'template' => 'social',
            'icon'   => 'share',
            'fields' => array(
                array(
                    'id'               => 'social_typography',
                    'type'             => 'typography',
                    'responsive'       => true,
                    'title'            => esc_html__( 'Typography', 'wp-ulike-pro' ),
                    'output'           => '.ulp-share-btn',
                    'output_important' => true,
                    'units'            => array('px', 'em', 'rem')
                ),
                array(
                    'id'               => 'social_max_width',
                    'type'             => 'slider',
                    'responsive'       => true,
                    'title'            => esc_html__( 'Max Width' ),
                    'output'           => '.ulp-social-wrapper, .ulp-social-login-wrapper',
                    'output_mode'      => 'max-width',
                    'min'              => 1,
                    'max'              => 2000,
                    'unit'             => 'px',
                    'output_important' => true
                ),
                array(
                    'id'               => 'social_height',
                    'type'             => 'slider',
                    'responsive'       => true,
                    'title'            => esc_html__( 'Height' ),
                    'output'           => '.ulp-share-btn',
                    'output_mode'      => 'height',
                    'min'              => 1,
                    'max'              => 50,
                    'unit'             => 'em',
                    'output_important' => true
                ),
                array(
                    'id'               => 'social_background',
                    'type'             => 'color',
                    'title'            => esc_html__( 'Background' ),
                    'subtitle'         => esc_html__( 'Applies when social button color mode is Custom.', 'wp-ulike-pro' ),
                    'output'           => '.ulp-social-buttons-color-custom.ulp-social-skin-flat .ulp-share-btn, .ulp-social-buttons-color-custom.ulp-social-skin-gradient .ulp-share-btn, .ulp-social-buttons-color-custom.ulp-social-skin-boxed .ulp-share-btn-icon, .ulp-social-buttons-color-custom.ulp-social-skin-minimal .ulp-share-btn-icon',
                    'output_mode'      => 'background-color',
                    'output_important' => true
                ),
                array(
                    'id'               => 'social_hover_background',
                    'type'             => 'color',
                    'title'            => esc_html__( 'Hover Background', 'wp-ulike-pro' ),
                    'output'           => '.ulp-share-btn:hover',
                    'output_mode'      => 'background-color',
                    'output_important' => true
                ),
                array(
                    'id'               => 'social_border',
                    'type'             => 'border',
                    'title'            => esc_html__( 'Border' ),
                    'output'           => '.ulp-share-btn',
                    'output_important' => true,
                    'units'            => array('px', 'em', 'rem')
                ),
                array(
                    'id'               => 'social_border_radius',
                    'type'             => 'slider',
                    'title'            => esc_html__( 'Border Radius', 'wp-ulike-pro' ),
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
                    'responsive'       => true,
                    'title'            => esc_html__( 'Padding', 'wp-ulike-pro' ),
                    'output'           => '.ulp-share-btn',
                    'output_important' => true,
                    'units'            => array('px', 'em', 'rem')
                ),
                array(
                    'type'    => 'heading',
                    'content' => esc_html__( 'Icon' ),
                ),
                array(
                    'id'               => 'social_icon_size',
                    'type'             => 'slider',
                    'responsive'       => true,
                    'title'            => esc_html__( 'Size' ),
                    'output'           => '.ulp-share-btn-icon i',
                    'output_mode'      => 'font-size',
                    'min'              => 1,
                    'max'              => 50,
                    'unit'             => 'em',
                    'output_important' => true
                ),
                array(
                    'id'               => 'social_icon_width',
                    'type'             => 'slider',
                    'responsive'       => true,
                    'title'            => esc_html__( 'Width' ),
                    'output'           => '.ulp-share-btn-icon',
                    'output_mode'      => 'width',
                    'min'              => 1,
                    'max'              => 50,
                    'unit'             => 'em',
                    'output_important' => true
                ),
                array(
                    'id'               => 'social_icon_color',
                    'type'             => 'color',
                    'title'            => esc_html__( 'Color' ),
                    'output'           => '.ulp-share-btn-icon',
                    'output_important' => true
                ),
                                                array(
                    'type'    => 'heading',
                    'content' => esc_html__( 'Text' ),
                ),
                array(
                    'id'               => 'social_text_typography',
                    'type'             => 'typography',
                    'responsive'       => true,
                    'title'            => esc_html__( 'Typography', 'wp-ulike-pro' ),
                    'output'           => '.ulp-share-btn-text',
                    'output_important' => true,
                    'units'            => array('px', 'em', 'rem')
                ),
                array(
                    'id'               => 'social_text_color',
                    'type'             => 'color',
                    'title'            => esc_html__( 'Text Color' ),
                    'output'           => '.ulp-share-btn-text',
                    'output_important' => true
                ),
                array(
                    'id'               => 'social_hover_color',
                    'type'             => 'color',
                    'title'            => esc_html__( 'Hover Color', 'wp-ulike-pro' ),
                    'output'           => '.ulp-share-btn:hover',
                    'output_important' => true
                ),
            )
        );
    }

    /**
     * Register emoji reactions customizer section.
     *
     * @return void
     */
    public function register_emoji_reactions_section() {
        $this->sections[] = array(
            'parent'   => WP_ULIKE_SLUG,
            'id'       => 'emoji_reactions_template',
            'title'    => esc_html__( 'Emoji', 'wp-ulike-pro' ),
            'template' => 'emoji_reactions',
            'icon'     => 'face-smile',
            'fields'   => array(
                array(
                    'type'    => 'heading',
                    'content' => esc_html__( 'Reactions', 'wp-ulike-pro' ),
                ),
                array(
                    'id'               => 'emoji_align',
                    'type'             => 'button_set',
                    'title'            => esc_html__( 'Alignment' ),
                    'options'          => array(
                        'left'   => esc_html__( 'Left' ),
                        'center' => esc_html__( 'Center' ),
                        'right'  => esc_html__( 'Right' ),
                    ),
                    'output_css'       => array(
                        'left'   => array(
                            array( 'selector' => '.wpulike.wpulike-engagement-template:has(.wpulike-engagements-emoji)', 'property' => 'text-align', 'value' => 'left' ),
                            array( 'selector' => '.wpulike.wpulike-engagement-template:has(.wpulike-engagements-emoji)', 'property' => 'justify-content', 'value' => 'flex-start' ),
                            array( 'selector' => '.wpulike-engagements-emoji.ulp-picker-style-inline .ulp-engagement-reactions', 'property' => 'justify-content', 'value' => 'flex-start' ),
                        ),
                        'center' => array(
                            array( 'selector' => '.wpulike.wpulike-engagement-template:has(.wpulike-engagements-emoji)', 'property' => 'text-align', 'value' => 'center' ),
                            array( 'selector' => '.wpulike.wpulike-engagement-template:has(.wpulike-engagements-emoji)', 'property' => 'justify-content', 'value' => 'center' ),
                            array( 'selector' => '.wpulike-engagements-emoji.ulp-picker-style-inline .ulp-engagement-reactions', 'property' => 'justify-content', 'value' => 'center' ),
                        ),
                        'right'  => array(
                            array( 'selector' => '.wpulike.wpulike-engagement-template:has(.wpulike-engagements-emoji)', 'property' => 'text-align', 'value' => 'right' ),
                            array( 'selector' => '.wpulike.wpulike-engagement-template:has(.wpulike-engagements-emoji)', 'property' => 'justify-content', 'value' => 'flex-end' ),
                            array( 'selector' => '.wpulike-engagements-emoji.ulp-picker-style-inline .ulp-engagement-reactions', 'property' => 'justify-content', 'value' => 'flex-end' ),
                        ),
                    ),
                    'output_important' => true,
                ),
                array(
                    'id'               => 'emoji_reaction_size',
                    'type'             => 'slider',
                    'responsive'       => true,
                    'title'            => esc_html__( 'Size' ),
                    'min'              => 24,
                    'max'              => 72,
                    'step'             => 1,
                    'unit'             => 'px',
                    'output'           => '.wpulike-engagements-emoji',
                    'output_mode'      => '--ulp-eng-reaction-size',
                    'output_important' => true,
                ),
                array(
                    'id'               => 'emoji_font_size',
                    'type'             => 'slider',
                    'responsive'       => true,
                    'title'            => esc_html__( 'Emoji Size', 'wp-ulike-pro' ),
                    'min'              => 14,
                    'max'              => 40,
                    'step'             => 1,
                    'unit'             => 'px',
                    'output'           => '.wpulike-engagements-emoji',
                    'output_mode'      => '--ulp-eng-emoji-size',
                    'output_important' => true,
                ),
                array(
                    'id'               => 'emoji_accent_color',
                    'type'             => 'color',
                    'title'            => esc_html__( 'Accent', 'wp-ulike-pro' ),
                    'subtitle'         => esc_html__( 'Focus rings and default accent mixes.', 'wp-ulike-pro' ),
                    'output'           => '.wpulike-engagements-emoji',
                    'output_mode'      => '--ulp-eng-accent',
                    'output_important' => true,
                ),
                array(
                    'id'               => 'emoji_reaction_hover_bg',
                    'type'             => 'color',
                    'title'            => esc_html__( 'Hover Background', 'wp-ulike-pro' ),
                    'output'           => '.wpulike-engagements-emoji',
                    'output_mode'      => '--ulp-eng-reaction-hover-bg',
                    'output_important' => true,
                ),
                array(
                    'id'               => 'emoji_reaction_active_bg',
                    'type'             => 'color',
                    'title'            => esc_html__( 'Selected Background', 'wp-ulike-pro' ),
                    'output'           => '.wpulike-engagements-emoji',
                    'output_mode'      => '--ulp-eng-reaction-active-bg',
                    'output_important' => true,
                ),
                array(
                    'id'               => 'emoji_reaction_border',
                    'type'             => 'border',
                    'title'            => esc_html__( 'Border' ),
                    'output'           => '.wpulike-engagements-emoji .ulp-engagement-reaction',
                    'output_important' => true,
                    'units'            => array( 'px', 'em', 'rem' ),
                ),
                array(
                    'id'               => 'emoji_reaction_gap',
                    'type'             => 'slider',
                    'responsive'       => true,
                    'title'            => esc_html__( 'Spacing', 'wp-ulike-pro' ),
                    'min'              => 0,
                    'max'              => 16,
                    'step'             => 1,
                    'unit'             => 'px',
                    'output'           => '.wpulike-engagements-emoji .ulp-engagement-reactions',
                    'output_mode'      => 'gap',
                    'output_important' => true,
                ),
                array(
                    'id'               => 'emoji_reaction_hover_scale',
                    'type'             => 'slider',
                    'responsive'       => true,
                    'title'            => esc_html__( 'Hover Scale', 'wp-ulike-pro' ),
                    'subtitle'         => esc_html__( 'How much a reaction enlarges on hover (desktop).', 'wp-ulike-pro' ),
                    'min'              => 100,
                    'max'              => 140,
                    'step'             => 1,
                    'unit'             => '%',
                    'output'           => '.wpulike-engagements-emoji',
                    'output_mode'      => '--ulp-eng-reaction-hover-scale',
                    'output_important' => true,
                ),
                array(
                    'type'    => 'heading',
                    'content' => esc_html__( 'Trigger', 'wp-ulike-pro' ),
                ),
                array(
                    'id'               => 'emoji_trigger_bg',
                    'type'             => 'color',
                    'title'            => esc_html__( 'Background' ),
                    'output'           => '.wpulike-engagements-emoji',
                    'output_mode'      => '--ulp-eng-trigger-bg',
                    'output_important' => true,
                ),
                array(
                    'id'               => 'emoji_trigger_hover_bg',
                    'type'             => 'color',
                    'title'            => esc_html__( 'Hover Background', 'wp-ulike-pro' ),
                    'output'           => '.wpulike-engagements-emoji',
                    'output_mode'      => '--ulp-eng-trigger-hover-bg',
                    'output_important' => true,
                ),
                array(
                    'id'               => 'emoji_trigger_reacted_bg',
                    'type'             => 'color',
                    'title'            => esc_html__( 'Reacted Background', 'wp-ulike-pro' ),
                    'subtitle'         => esc_html__( 'When the visitor has an active reaction.', 'wp-ulike-pro' ),
                    'output'           => '.wpulike-engagements-emoji',
                    'output_mode'      => '--ulp-eng-trigger-reacted-bg',
                    'output_important' => true,
                ),
                array(
                    'id'               => 'emoji_trigger_border',
                    'type'             => 'border',
                    'title'            => esc_html__( 'Border' ),
                    'output'           => '.wpulike-engagements-emoji .ulp-engagement-trigger',
                    'output_important' => true,
                    'units'            => array( 'px', 'em', 'rem' ),
                ),
                array(
                    'id'               => 'emoji_trigger_padding',
                    'type'             => 'spacing',
                    'responsive'       => true,
                    'title'            => esc_html__( 'Padding', 'wp-ulike-pro' ),
                    'output'           => '.wpulike-engagements-emoji .ulp-engagement-trigger',
                    'output_important' => true,
                    'units'            => array( 'px', 'em', 'rem' ),
                ),
                array(
                    'type'    => 'heading',
                    'content' => esc_html__( 'Picker', 'wp-ulike-pro' ),
                ),
                array(
                    'id'               => 'emoji_picker_background',
                    'type'             => 'color',
                    'title'            => esc_html__( 'Background' ),
                    'output'           => '.wpulike-engagements-emoji',
                    'output_mode'      => '--ulp-eng-picker-bg',
                    'output_important' => true,
                ),
                array(
                    'id'               => 'emoji_picker_border',
                    'type'             => 'border',
                    'title'            => esc_html__( 'Border' ),
                    'output'           => '.wpulike-engagements-emoji .ulp-engagement-picker',
                    'output_important' => true,
                    'units'            => array( 'px', 'em', 'rem' ),
                ),
                array(
                    'id'               => 'emoji_picker_padding',
                    'type'             => 'spacing',
                    'responsive'       => true,
                    'title'            => esc_html__( 'Padding', 'wp-ulike-pro' ),
                    'output'           => '.wpulike-engagements-emoji .ulp-engagement-picker',
                    'output_important' => true,
                    'units'            => array( 'px', 'em', 'rem' ),
                ),
                                                array(
                    'type'    => 'heading',
                    'content' => esc_html__( 'Counter', 'wp-ulike-pro' ),
                ),
                array(
                    'id'               => 'emoji_count_typography',
                    'type'             => 'typography',
                    'responsive'       => true,
                    'title'            => esc_html__( 'Typography', 'wp-ulike-pro' ),
                    'subtitle'         => esc_html__( 'Per-reaction and trigger total counts.', 'wp-ulike-pro' ),
                    'output'           => '.wpulike-engagements-emoji .ulp-engagement-count, .wpulike-engagements-emoji .ulp-engagement-trigger-count',
                    'output_important' => true,
                    'units'            => array( 'px', 'em', 'rem' ),
                ),
            ),
        );
    }

    /**
     * Register star rating customizer section.
     *
     * @return void
     */
    public function register_star_rating_section() {
        $this->sections[] = array(
            'parent'   => WP_ULIKE_SLUG,
            'id'       => 'star_rating_template',
            'title'    => esc_html__( 'Stars', 'wp-ulike-pro' ),
            'template' => 'star_rating',
            'icon'     => 'star',
            'fields'   => array(
                array(
                    'type'    => 'heading',
                    'content' => esc_html__( 'Stars', 'wp-ulike-pro' ),
                ),
                array(
                    'id'               => 'star_align',
                    'type'             => 'button_set',
                    'title'            => esc_html__( 'Alignment' ),
                    'options'          => array(
                        'left'   => esc_html__( 'Left' ),
                        'center' => esc_html__( 'Center' ),
                        'right'  => esc_html__( 'Right' ),
                    ),
                    'output_css'       => array(
                        'left'   => array(
                            array( 'selector' => '.wpulike.wpulike-engagement-template:has(.wpulike-engagements-star)', 'property' => 'text-align', 'value' => 'left' ),
                            array( 'selector' => '.wpulike.wpulike-engagement-template:has(.wpulike-engagements-star)', 'property' => 'justify-content', 'value' => 'flex-start' ),
                        ),
                        'center' => array(
                            array( 'selector' => '.wpulike.wpulike-engagement-template:has(.wpulike-engagements-star)', 'property' => 'text-align', 'value' => 'center' ),
                            array( 'selector' => '.wpulike.wpulike-engagement-template:has(.wpulike-engagements-star)', 'property' => 'justify-content', 'value' => 'center' ),
                        ),
                        'right'  => array(
                            array( 'selector' => '.wpulike.wpulike-engagement-template:has(.wpulike-engagements-star)', 'property' => 'text-align', 'value' => 'right' ),
                            array( 'selector' => '.wpulike.wpulike-engagement-template:has(.wpulike-engagements-star)', 'property' => 'justify-content', 'value' => 'flex-end' ),
                        ),
                    ),
                    'output_important' => true,
                ),
                array(
                    'id'               => 'star_size',
                    'type'             => 'slider',
                    'responsive'       => true,
                    'title'            => esc_html__( 'Size' ),
                    'min'              => 16,
                    'max'              => 56,
                    'step'             => 1,
                    'unit'             => 'px',
                    'output'           => '.wpulike-engagements-star',
                    'output_mode'      => '--ulp-eng-star-size',
                    'output_important' => true,
                ),
                array(
                    'id'               => 'star_dot_size',
                    'type'             => 'slider',
                    'responsive'       => true,
                    'title'            => esc_html__( 'Empty Size', 'wp-ulike-pro' ),
                    'subtitle'         => esc_html__( 'Size of unselected dots relative to the star.', 'wp-ulike-pro' ),
                    'min'              => 16,
                    'max'              => 50,
                    'step'             => 1,
                    'unit'             => '%',
                    'output'           => '.wpulike-engagements-star',
                    'output_mode'      => '--ulp-eng-star-dot-size',
                    'output_important' => true,
                ),
                array(
                    'id'               => 'star_active_color',
                    'type'             => 'color',
                    'title'            => esc_html__( 'Active Color', 'wp-ulike-pro' ),
                    'subtitle'         => esc_html__( 'Filled stars and hover preview.', 'wp-ulike-pro' ),
                    'output'           => '.wpulike-engagements-star',
                    'output_mode'      => '--ulp-eng-star-active',
                    'output_important' => true,
                ),
                array(
                    'id'               => 'star_inactive_color',
                    'type'             => 'color',
                    'title'            => esc_html__( 'Empty Color', 'wp-ulike-pro' ),
                    'output'           => '.wpulike-engagements-star',
                    'output_mode'      => '--ulp-eng-star-inactive',
                    'output_important' => true,
                ),
                array(
                    'id'               => 'star_gap',
                    'type'             => 'slider',
                    'responsive'       => true,
                    'title'            => esc_html__( 'Spacing', 'wp-ulike-pro' ),
                    'min'              => 0,
                    'max'              => 24,
                    'step'             => 1,
                    'unit'             => 'px',
                    'output'           => '.wpulike-engagements-star .ulp-engagement-stars',
                    'output_mode'      => 'gap',
                    'output_important' => true,
                ),
                array(
                    'type'    => 'heading',
                    'content' => esc_html__( 'Hover', 'wp-ulike-pro' ),
                ),
                array(
                    'id'               => 'star_hover_scale',
                    'type'             => 'slider',
                    'responsive'       => true,
                    'title'            => esc_html__( 'Scale' ),
                    'subtitle'         => esc_html__( 'How much the focused star enlarges on hover.', 'wp-ulike-pro' ),
                    'min'              => 100,
                    'max'              => 140,
                    'step'             => 1,
                    'unit'             => '%',
                    'output'           => '.wpulike-engagements-star',
                    'output_mode'      => '--ulp-eng-star-focus-scale',
                    'output_important' => true,
                ),
                array(
                    'id'               => 'star_motion',
                    'type'             => 'slider',
                    'responsive'       => true,
                    'title'            => esc_html__( 'Animation', 'wp-ulike-pro' ),
                    'subtitle'         => esc_html__( 'Morph and spring intensity. Set to 0 for instant changes.', 'wp-ulike-pro' ),
                    'min'              => 0,
                    'max'              => 100,
                    'step'             => 10,
                    'unit'             => '%',
                    'output'           => '.wpulike-engagements-star',
                    'output_mode'      => '--ulp-eng-star-motion',
                    'output_important' => true,
                ),
                array(
                    'type'    => 'heading',
                    'content' => esc_html__( 'Summary', 'wp-ulike-pro' ),
                ),
                array(
                    'id'               => 'star_summary_typography',
                    'type'             => 'typography',
                    'responsive'       => true,
                    'title'            => esc_html__( 'Typography', 'wp-ulike-pro' ),
                    'output'           => '.wpulike-engagements-star .ulp-engagement-rating-summary',
                    'output_important' => true,
                    'units'            => array( 'px', 'em', 'rem' ),
                ),
            ),
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
        if ( ! in_array( $template_type, array( 'profile', 'forms', 'modals', 'social', 'emoji_reactions', 'star_rating' ), true ) ) {
            return $preview_html;
        }

        if ( ! $this->has_permission() ) {
            return '<div class="ulp-customizer-license-gate">' . $this->get_license_required_content( '_top' ) . '</div>';
        }

        // Ensure preview mode is detected
        if ( ! isset( $_GET['preview'] ) ) {
            $_GET['preview'] = true;
        }

        ob_start();

        switch ( $template_type ) {
            case 'emoji_reactions':
                echo '<div class="ulp-customizer-preview-root ulp-customizer-engagement-preview">';
                echo '<div class="ulp-customizer-engagement-block is-hover-preview">';
                echo '<p class="ulp-customizer-preview-label">' . esc_html__( 'Hover', 'wp-ulike-pro' ) . '</p>';
                echo '<div class="wpulike wpulike-engagement-template"><div class="wp_ulike_general_class">';
                echo $this->render_engagement_preview( 'emoji', 'hover' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo '</div></div></div>';
                echo '<div class="ulp-customizer-engagement-block">';
                echo '<p class="ulp-customizer-preview-label">' . esc_html__( 'Inline' ) . '</p>';
                echo '<div class="wpulike wpulike-engagement-template"><div class="wp_ulike_general_class">';
                echo $this->render_engagement_preview( 'emoji', 'inline' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo '</div></div></div>';
                echo '</div>';
                break;

            case 'star_rating':
                echo '<div class="ulp-customizer-preview-root ulp-customizer-engagement-preview">';
                echo '<div class="wpulike wpulike-engagement-template">';
                echo '<div class="wp_ulike_general_class">';
                echo $this->render_engagement_preview( 'star' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo '</div></div></div>';
                break;

            case 'profile':
                $this->render_profile_preview();
                break;

            case 'forms':
                $this->render_forms_preview();
                break;

            case 'modals':
                $this->render_modals_preview();
                break;

            case 'social':
                $this->render_social_preview();
                break;
        }

        return ob_get_clean();
    }

    /**
     * Stable Profile Template preview — always shows styleable regions
     * (avatar, actions, badges, bio, tabs) without relying on live profile settings/links.
     *
     * @return void
     */
    private function render_profile_preview() {
        $avatar = get_avatar(
            'preview@example.com',
            200,
            '',
            __( 'Sample User', 'wp-ulike-pro' ),
            array( 'class' => 'avatar avatar-200 photo' )
        );

        $badges = array(
            array(
                'title'    => __( '91 Likes', 'wp-ulike-pro' ),
                'subtitle' => __( 'Total up votes', 'wp-ulike-pro' ),
            ),
            array(
                'title'    => __( '6 Dislikes', 'wp-ulike-pro' ),
                'subtitle' => __( 'Total down votes', 'wp-ulike-pro' ),
            ),
            array(
                'title'    => __( '1 day ago', 'wp-ulike-pro' ),
                'subtitle' => __( 'Last Activity', 'wp-ulike-pro' ),
            ),
        );

        $tabs = array(
            __( 'Licenses', 'wp-ulike-pro' ),
            __( 'Two Factor', 'wp-ulike-pro' ),
            __( 'Recent Likes', 'wp-ulike-pro' ),
        );

        echo '<div class="ulp-customizer-preview-root ulp-customizer-profile-preview">';
        echo '<div class="wp-ulike-pro-section-profile ulp-flex-row ulp-flex-top-xs">';

        echo '<div class="wp-ulike-pro-header-section ulp-flex-row ulp-flex-center-xs ulp-flex-col-xl-12 ulp-flex-col-md-12 ulp-flex-col-xs-12">';
        echo '<div class="wp-ulike-pro-info-section ulp-flex-col-xl-12 ulp-flex-col-md-12 ulp-flex-col-xs-12">';
        echo '<div class="ulp-flex-row ulp-flex-middle-xs ulp-flex-center-xs ulp-flex-start-md">';

        echo '<div class="ulp-flex-col-xl-3 ulp-flex-col-md-3 ulp-flex-col-xs-12 wp-ulike-pro-header-avatar-col">';
        echo '<div class="wp-ulike-pro-profile-user-avatar">';
        // Same as non-owner profile view: plain avatar + sizing wrapper (no uploader chrome / list bullets).
        echo '<div class="fileuploader-theme-avatar">';
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_avatar()
        echo $avatar;
        echo '</div></div></div>';

        echo '<div class="ulp-flex-col-xl-9 ulp-flex-col-md-9 ulp-flex-col-xs-12 wp-ulike-pro-header-info-col">';
        echo '<div class="wp-ulike-pro-profile-user-info">';
        echo '<div class="wp-ulike-pro-profile-header-top">';
        echo '<div class="wp-ulike-pro-profile-header-left">';
        echo '<h3 class="wp-ulike-pro-profile-name">' . esc_html__( 'Sample User', 'wp-ulike-pro' ) . '</h3>';
        echo '</div>';
        echo '<div class="wp-ulike-pro-profile-actions">';
        echo '<a href="#" class="wp-ulike-pro-profile-action-btn wp-ulike-pro-profile-edit-btn" title="' . esc_attr__( 'Edit' ) . '">';
        echo '<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M11.3333 2.00004C11.5084 1.82493 11.7163 1.68607 11.9441 1.59131C12.1719 1.49655 12.4151 1.44775 12.6667 1.44775C12.9182 1.44775 13.1614 1.49655 13.3892 1.59131C13.617 1.68607 13.8249 1.82493 14 2.00004C14.1751 2.17515 14.314 2.38305 14.4087 2.61087C14.5035 2.83869 14.5523 3.08189 14.5523 3.33337C14.5523 3.58486 14.5035 3.82806 14.4087 4.05588C14.314 4.2837 14.1751 4.4916 14 4.66671L5.00001 13.6667L1.33334 14.6667L2.33334 11L11.3333 2.00004Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        echo '<span>' . esc_html__( 'Edit' ) . '</span></a>';
        echo '<a href="#" class="wp-ulike-pro-profile-action-btn wp-ulike-pro-profile-logout-btn" title="' . esc_attr__( 'Log Out' ) . '">';
        echo '<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M6 14H3.33333C2.89131 14 2.46738 13.8244 2.15482 13.5118C1.84226 13.1993 1.66667 12.7754 1.66667 12.3333V3.66667C1.66667 3.22464 1.84226 2.80072 2.15482 2.48816C2.46738 2.17559 2.89131 2 3.33333 2H6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M10.6667 11.3333L14.3333 7.66667L10.6667 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M14.3333 7.66667H6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        echo '<span>' . esc_html__( 'Log Out' ) . '</span></a>';
        echo '</div></div></div>';

        echo '<div class="wp-ulike-pro-badges-section"><div class="wp-ulike-pro-badges-list">';
        foreach ( $badges as $badge ) {
            echo '<div class="wp-ulike-pro-badge-item">';
            echo '<div class="wp-ulike-pro-badge-info">';
            echo '<span class="wp-ulike-pro-badge-title">' . esc_html( $badge['title'] ) . '</span>';
            echo '<span class="wp-ulike-pro-badge-subtitle">' . esc_html( $badge['subtitle'] ) . '</span>';
            echo '</div></div>';
        }
        echo '</div></div>';

        echo '</div></div></div></div>';

        echo '<div class="ulp-flex-col-xl-12 ulp-flex-col-md-12 ulp-flex-col-xs-12">';
        echo '<p class="wp-ulike-pro-profile-desc">' . esc_html__( 'Sample bio text so you can style the profile description without depending on a real user account.', 'wp-ulike-pro' ) . '</p>';
        echo '<div class="wp-ulike-pro-profile-custom-info">' . esc_html__( 'Custom info preview area.', 'wp-ulike-pro' ) . '</div>';
        echo '</div>';

        // Top tabs (default profile layout).
        echo '<div class="wp-ulike-pro-tabs-section ulp-flex-col-xl-12 ulp-flex-col-md-12 ulp-flex-col-xs-12">';
        echo '<div class="ulp-flex-row ulp-flex-middle-xs ulp-flex-start-md"><div class="ulp-flex-col-xs-12">';
        echo '<div class="ulp-tabs top_side">';
        echo '<div class="tab_nav">';
        foreach ( $tabs as $index => $tab_label ) {
            $active = 0 === $index ? ' active' : '';
            echo '<a href="#" class="nav_item nav_internal' . esc_attr( $active ) . '">' . esc_html( $tab_label ) . '</a>';
        }
        echo '</div>';
        echo '<div class="content_wrapper"><div class="tab_content">';
        echo esc_html__( 'Tab content for typography, padding, border, and background.', 'wp-ulike-pro' );
        echo '</div></div></div></div></div></div>';

        // Compact left-side tabs — targets Left/Right menu color controls.
        echo '<div class="wp-ulike-pro-tabs-section ulp-customizer-profile-side-tabs ulp-flex-col-xl-12 ulp-flex-col-md-12 ulp-flex-col-xs-12">';
        echo '<div class="ulp-tabs left_side">';
        echo '<div class="tab_nav">';
        echo '<a href="#" class="nav_item nav_internal active">' . esc_html__( 'Activity' ) . '</a>';
        echo '<a href="#" class="nav_item nav_internal">' . esc_html__( 'Stats', 'wp-ulike-pro' ) . '</a>';
        echo '</div>';
        echo '<div class="content_wrapper"><div class="tab_content">';
        echo esc_html__( 'Side-menu layout preview.', 'wp-ulike-pro' );
        echo '</div></div></div></div>';

        echo '</div></div>';
    }

    /**
     * Stable forms preview with every styleable state (input, link, loading).
     *
     * @return void
     */
    private function render_forms_preview() {
        $fields = array(
            array(
                'id'          => 'ulp-preview-username',
                'label'       => __( 'Username or Email Address' ),
                'type'        => 'text',
                'value'       => '',
                'input_class' => '',
                'wrapper'     => '',
            ),
            array(
                'id'          => 'ulp-preview-focus',
                'label'       => __( 'Focused field', 'wp-ulike-pro' ),
                'type'        => 'text',
                'value'       => 'sample.user',
                'input_class' => '',
                'wrapper'     => 'ulp-customizer-forms-focus',
                'autofocus'   => true,
            ),
            array(
                'id'          => 'ulp-preview-password',
                'label'       => __( 'Password' ),
                'type'        => 'password',
                'value'       => 'password',
                'input_class' => 'ulp-input-success',
                // Show success + focus ring / label without interaction.
                'wrapper'     => 'ulp-customizer-forms-focus',
            ),
            array(
                'id'          => 'ulp-preview-email',
                'label'       => __( 'Email' ),
                'type'        => 'email',
                'value'       => 'bad@',
                'input_class' => 'ulp-input-error',
                // Show error + focus ring / label without interaction.
                'wrapper'     => 'ulp-customizer-forms-focus',
            ),
        );

        echo '<div class="ulp-customizer-preview-root ulp-customizer-forms-preview">';
        echo '<div class="ulp-form ulp-form-center ulp-login">';
        echo '<form action="#" method="post" autocomplete="off">';
        echo '<div class="ulp-form-row ulp-flex-row ulp-flex-middle-xs">';

        foreach ( $fields as $field ) {
            $input_class   = trim( 'ulp-floating-input ' . $field['input_class'] );
            $wrapper_class = trim( 'ulp-floating ' . $field['wrapper'] );
            $autofocus     = ! empty( $field['autofocus'] ) ? ' autofocus' : '';
            echo '<div class="ulp-flex-col-xl-12 ulp-flex-col-md-12 ulp-flex-col-xs-12">';
            echo '<div class="' . esc_attr( $wrapper_class ) . '">';
            printf(
                '<input id="%1$s" class="%2$s" name="%1$s" type="%3$s" value="%4$s" placeholder="%5$s" readonly%6$s />',
                esc_attr( $field['id'] ),
                esc_attr( $input_class ),
                esc_attr( $field['type'] ),
                esc_attr( $field['value'] ),
                esc_attr( $field['label'] ),
                $autofocus // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- literal attribute
            );
            printf(
                '<label for="%1$s" class="ulp-floating-label" data-content="%2$s"><span class="ulp-hidden-visually">%3$s</span></label>',
                esc_attr( $field['id'] ),
                esc_attr( $field['label'] ),
                esc_html( $field['label'] )
            );
            echo '</div></div>';
        }

        echo '<div class="ulp-flex-col-xl-12 ulp-flex-col-md-12 ulp-flex-col-xs-12">';
        echo '<div class="ulp-flex ulp-flex-end-md ulp-flex-center-xs">';
        echo '<a href="#">' . esc_html__( 'Lost your password?' ) . '</a>';
        echo '</div></div>';

        echo '<div class="ulp-submit-field ulp-flex-col-xl-12 ulp-flex-col-md-12 ulp-flex-col-xs-12">';
        echo '<div class="ulp-flex ulp-flex-center-xs">';
        echo '<input class="ulp-button" type="submit" value="' . esc_attr__( 'Log in' ) . '" />';
        echo '</div></div>';

        echo '<div class="ulp-submit-field ulp-flex-col-xl-12 ulp-flex-col-md-12 ulp-flex-col-xs-12">';
        echo '<div class="ulp-flex ulp-flex-center-xs ulp-loading">';
        echo '<input class="ulp-button" type="submit" value="' . esc_attr__( 'Please wait…', 'wp-ulike-pro' ) . '" disabled />';
        echo '</div></div>';

        echo '</div></form></div></div>';
    }

    /**
     * Static modal previews: login require modal + pile likers modal.
     * Does not open live ulpmodal / AJAX.
     *
     * @return void
     */
    private function render_modals_preview() {
        echo '<div class="ulp-customizer-preview-root ulp-customizer-modals-preview">';

        // Login / require-login modal (forms sit inside shared shell).
        echo '<div class="ulp-customizer-modal-block">';
        echo '<p class="ulp-customizer-preview-label">' . esc_html__( 'Log In' ) . '</p>';
        $login_body  = '<div class="ulp-form ulp-form-center ulp-login">';
        $login_body .= '<form action="#" method="post" autocomplete="off">';
        $login_body .= '<div class="ulp-form-row ulp-flex-row ulp-flex-middle-xs">';
        $login_body .= '<div class="ulp-flex-col-xl-12 ulp-flex-col-md-12 ulp-flex-col-xs-12"><div class="ulp-floating">';
        $login_body .= '<input id="ulp-modal-preview-user" class="ulp-floating-input" type="text" value="sample.user" placeholder="' . esc_attr__( 'Username' ) . '" readonly />';
        $login_body .= '<label for="ulp-modal-preview-user" class="ulp-floating-label" data-content="' . esc_attr__( 'Username' ) . '"><span class="ulp-hidden-visually">' . esc_html( 'Username' ) . '</span></label>';
        $login_body .= '</div></div>';
        $login_body .= '<div class="ulp-submit-field ulp-flex-col-xl-12 ulp-flex-col-md-12 ulp-flex-col-xs-12"><div class="ulp-flex ulp-flex-center-xs">';
        $login_body .= '<input class="ulp-button" type="submit" value="' . esc_attr__( 'Log in' ) . '" />';
        $login_body .= '</div></div></div></form></div>';
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built with escaped strings
        echo $this->get_modal_preview_shell_html( __( 'Log In' ), $login_body, false );
        echo '</div>';

        // Pile / likers modal list.
        echo '<div class="ulp-customizer-modal-block">';
        echo '<p class="ulp-customizer-preview-label">' . esc_html__( 'Likers', 'wp-ulike-pro' ) . '</p>';
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built with escaped strings
        echo $this->get_modal_preview_shell_html(
            __( 'Likers', 'wp-ulike-pro' ),
            $this->get_likers_modal_preview_list_html(),
            true
        );
        echo '</div>';

        echo '</div>';
    }

    /**
     * Shared ulpmodal shell markup for customizer preview (static, not fixed).
     *
     * @param string $title   Header title.
     * @param string $body    Inner HTML.
     * @param bool   $is_ajax Whether to use ajax modal inner padding.
     * @return string
     */
    private function get_modal_preview_shell_html( $title, $body, $is_ajax = false ) {
        $classes = 'ulpmodal ulpmodal-visible ulpmodal-has-title';
        if ( $is_ajax ) {
            $classes .= ' ulpmodal-ajax';
        }

        $html  = '<div class="' . esc_attr( $classes ) . '" role="dialog" aria-modal="true">';
        $html .= '<div class="ulpmodal-content">';
        $html .= '<div class="ulpmodal-header">';
        $html .= '<div class="ulpmodal-header__title"><h3 class="ulpmodal-title">' . esc_html( $title ) . '</h3></div>';
        $html .= '<button class="ulpmodal-close-icon ulpmodal-close" type="button" aria-label="' . esc_attr( 'Close' ) . '" tabindex="-1">&#10005;</button>';
        $html .= '</div>';
        $html .= '<div class="ulpmodal-body"><div class="ulpmodal-inner" role="document">';
        $html .= $body;
        $html .= '</div></div></div></div>';

        return $html;
    }

    /**
     * Sample likers rows for pile modal preview.
     *
     * @return string
     */
    private function get_likers_modal_preview_list_html() {
        $users = array(
            array( 'email' => 'preview1@example.com', 'name' => __( 'Alex Rivera', 'wp-ulike-pro' ) ),
            array( 'email' => 'preview2@example.com', 'name' => __( 'Jordan Lee', 'wp-ulike-pro' ) ),
            array( 'email' => 'preview3@example.com', 'name' => __( 'Sam Patel', 'wp-ulike-pro' ) ),
        );

        $rows = '';
        foreach ( $users as $user ) {
            $avatar = get_avatar_url( $user['email'], array( 'size' => 72 ) );
            $rows  .= '<a href="#" class="ulp-flex-row ulp-flex-middle-xs ulp-flex-start-md" tabindex="-1">';
            $rows  .= '<span class="ulp-flex-col-md-2 ulp-flex-col-xs-1 ulp-user-icon">';
            $rows  .= '<img src="' . esc_url( $avatar ) . '" class="ulp-img-icon" alt="" width="36" height="36" />';
            $rows  .= '</span>';
            $rows  .= '<span class="ulp-flex-col-md-10 ulp-flex-col-xs-11 ulp-user-info"><span class="ulp-title">' . esc_html( $user['name'] ) . '</span></span>';
            $rows  .= '</a>';
        }

        return '<div class="ulpmodal-ajax-wrapper"><div class="ulp-modal-likers-list">' . $rows . '</div></div>';
    }

    /**
     * Social Share + Social Login preview (shared customizer chrome).
     *
     * @return void
     */
    private function render_social_preview() {
        $share_networks = array( 'facebook', 'twitter', 'linkedin', 'whatsapp' );
        $social_items   = wp_ulike_get_option( 'social_share', array() );
        $share_view     = 'icon_text';
        $share_skin     = 'flat';
        $share_color    = 'official';
        $share_shape    = 'square';

        if ( ! empty( $social_items ) && is_array( $social_items ) ) {
            foreach ( $social_items as $item ) {
                if ( empty( $item['buttons'] ) || ! is_array( $item['buttons'] ) ) {
                    continue;
                }
                $from_settings = array();
                foreach ( $item['buttons'] as $button ) {
                    if ( ! empty( $button['network'] ) ) {
                        $from_settings[] = sanitize_key( $button['network'] );
                    }
                }
                if ( ! empty( $from_settings ) ) {
                    $share_networks = $from_settings;
                }
                if ( ! empty( $item['view'] ) ) {
                    $share_view = sanitize_key( $item['view'] );
                }
                if ( ! empty( $item['skin'] ) ) {
                    $share_skin = sanitize_key( $item['skin'] );
                }
                if ( ! empty( $item['color'] ) ) {
                    $share_color = sanitize_key( $item['color'] );
                }
                if ( ! empty( $item['shape'] ) ) {
                    $share_shape = sanitize_key( $item['shape'] );
                }
                break;
            }
        }

        $share_mods = sprintf(
            'ulp-social-skin-%1$s ulp-social-buttons-color-%2$s ulp-social-shape-%3$s ulp-social-view-%4$s',
            esc_attr( $share_skin ),
            esc_attr( $share_color ),
            esc_attr( $share_shape ),
            esc_attr( $share_view )
        );

        // Login uses the same options / markup as wp_ulike_pro_get_social_logins().
        $login_view  = sanitize_key( (string) wp_ulike_get_option( 'social_login_view', 'icon_text' ) );
        $login_skin  = sanitize_key( (string) wp_ulike_get_option( 'social_login_skin', 'gradient' ) );
        $login_color = sanitize_key( (string) wp_ulike_get_option( 'social_login_color', 'official' ) );
        $login_shape = sanitize_key( (string) wp_ulike_get_option( 'social_login_shape', 'square' ) );
        $login_width = wp_ulike_get_option(
            'social_login_layout',
            array(
                'desktop' => '12',
                'tablet'  => '12',
                'mobile'  => '12',
            )
        );
        if ( ! is_array( $login_width ) ) {
            $login_width = array(
                'desktop' => '12',
                'tablet'  => '12',
                'mobile'  => '12',
            );
        }
        $col_xl = isset( $login_width['desktop'] ) ? sanitize_key( (string) $login_width['desktop'] ) : '12';
        $col_md = isset( $login_width['tablet'] ) ? sanitize_key( (string) $login_width['tablet'] ) : '12';
        $col_xs = isset( $login_width['mobile'] ) ? sanitize_key( (string) $login_width['mobile'] ) : '12';

        $login_networks = array();
        if ( class_exists( 'WP_Ulike_Pro_Options' ) && method_exists( 'WP_Ulike_Pro_Options', 'getAvailabeSocialLogins' ) ) {
            $available = WP_Ulike_Pro_Options::getAvailabeSocialLogins();
            if ( ! empty( $available ) && is_array( $available ) ) {
                foreach ( $available as $item ) {
                    if ( ! empty( $item['network'] ) ) {
                        $login_networks[] = array(
                            'network' => sanitize_key( $item['network'] ),
                            'label'   => ! empty( $item['login_label'] ) ? $item['login_label'] : ucfirst( $item['network'] ),
                        );
                    }
                }
            }
        }
        if ( empty( $login_networks ) ) {
            $login_networks = array(
                array( 'network' => 'google', 'label' => 'Google' ),
                array( 'network' => 'facebook', 'label' => 'Facebook' ),
                array( 'network' => 'twitter', 'label' => 'Twitter' ),
            );
        }

        $login_mods = sprintf(
            'ulp-social-skin-%1$s ulp-social-buttons-color-%2$s ulp-social-shape-%3$s ulp-social-view-%4$s',
            esc_attr( $login_skin ),
            esc_attr( $login_color ),
            esc_attr( $login_shape ),
            esc_attr( $login_view )
        );

        echo '<div class="ulp-customizer-preview-root ulp-customizer-social-preview">';

        echo '<div class="ulp-customizer-social-block">';
        echo '<p class="ulp-customizer-preview-label">' . esc_html__( 'Share' ) . '</p>';
        echo '<div class="ulp-social-wrapper">';
        echo '<div class="ulp-social ulp-social-share ' . esc_attr( $share_mods ) . '">';
        foreach ( array_slice( $share_networks, 0, 4 ) as $network ) {
            echo '<div class="ulp-social-item">';
            echo $this->get_social_preview_button_html( $network, $share_view, 'share' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo '</div>';
        }
        echo '</div></div></div>';

        echo '<div class="ulp-customizer-social-block">';
        echo '<p class="ulp-customizer-preview-label">' . esc_html__( 'Log In' ) . '</p>';
        echo '<div class="ulp-social ulp-social-login ' . esc_attr( $login_mods ) . '">';
        echo '<div class="ulp-social-login-wrapper ulp-flex-row">';
        foreach ( $login_networks as $item ) {
            $network = $item['network'];
            $label   = $item['label'];
            echo '<div class="ulp-social-item ulp-flex-center-xs ulp-flex-col-xl-' . esc_attr( $col_xl ) . ' ulp-flex-col-md-' . esc_attr( $col_md ) . ' ulp-flex-col-xs-' . esc_attr( $col_xs ) . '">';
            echo $this->get_social_preview_button_html( $network, $login_view, 'login', $label ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo '</div>';
        }
        echo '</div></div></div>';

        echo '</div>';
    }

    /**
     * One share/login button for customizer preview (no live OAuth / share JS).
     *
     * @param string $network Network slug.
     * @param string $view    icon_text|icon|text.
     * @param string $context share|login.
     * @param string $label   Optional label override.
     * @return string
     */
    private function get_social_preview_button_html( $network, $view = 'icon_text', $context = 'share', $label = '' ) {
        $network = sanitize_key( $network );
        $label   = '' !== $label ? $label : ucfirst( $network );
        $is_login = ( 'login' === $context );

        if ( $is_login ) {
            $html = sprintf( '<a class="ulp-share-btn ulp-social-btn ulp-share-%s" href="#">', esc_attr( $network ) );
        } else {
            $html = sprintf( '<div class="ulp-share-btn ulp-share-%s">', esc_attr( $network ) );
        }

        if ( in_array( $view, array( 'icon_text', 'icon' ), true ) ) {
            $html .= '<span class="ulp-share-btn-icon"><i class="ulp-icon-' . esc_attr( $network ) . '"></i>';
            $html .= '<span class="ulp-screen-only">' . esc_html( $label ) . '</span></span>';
        }
        if ( in_array( $view, array( 'icon_text', 'text' ), true ) ) {
            // Share shortcode uses <div>; login markup uses <span>.
            if ( $is_login ) {
                $html .= '<span class="ulp-share-btn-text"><span class="ulp-share-btn-title">' . esc_html( $label ) . '</span></span>';
            } else {
                $html .= '<div class="ulp-share-btn-text"><span class="ulp-share-btn-title">' . esc_html( $label ) . '</span></div>';
            }
        }
        $html .= $is_login ? '</a>' : '</div>';

        return $html;
    }

    /**
     * Render engagement widget in customizer preview.
     *
     * @param string      $mode         emoji|star.
     * @param string|null $picker_style hover|inline for emoji dual preview; null = site setting.
     * @return string
     */
    private function render_engagement_preview( $mode, $picker_style = null ) {
        if (
            ! class_exists( 'WP_Ulike_Pro_Engagement_Display' )
            || ! class_exists( 'WP_Ulike_Pro_Engagement_Settings' )
        ) {
            return '';
        }

        $template = 'emoji' === $mode
            ? WP_Ulike_Pro_Engagement_Settings::TEMPLATE_EMOJI
            : WP_Ulike_Pro_Engagement_Settings::TEMPLATE_STAR;

        $context = array(
            'template'         => $template,
            'display_counters' => true,
            'display_likers'   => false,
        );

        if ( 'emoji' === $mode && in_array( $picker_style, array( 'hover', 'inline' ), true ) ) {
            $context['engagement_picker_style'] = $picker_style;
        }

        WP_Ulike_Pro_Engagement_Settings::push_context( 'post', $context );

        try {
            $html = WP_Ulike_Pro_Engagement_Display::render( 1, 'post' );
        } finally {
            WP_Ulike_Pro_Engagement_Settings::pop_context( 'post' );
        }

        if ( ! $html ) {
            return '';
        }

        // Keep hover picker open so size/border controls are visible without hover.
        if ( 'emoji' === $mode && false !== strpos( $html, 'ulp-picker-style-hover' ) ) {
            $html = preg_replace(
                '/class="([^"]*\bulp-picker-style-hover\b[^"]*)"/',
                'class="$1 ulp-picker-open"',
                $html,
                1
            );
            // Show selected / reacted states for those customizer controls.
            $html = preg_replace(
                '/class="([^"]*\bulp-engagement-reaction\b[^"]*)"/',
                'class="$1 is-active"',
                $html,
                1
            );
            $html = preg_replace(
                '/class="([^"]*\bulp-engagement-trigger\b[^"]*)"/',
                'class="$1 is-reacted"',
                $html,
                1
            );
        }

        if ( 'emoji' === $mode && false !== strpos( $html, 'ulp-picker-style-inline' ) ) {
            $html = preg_replace(
                '/class="([^"]*\bulp-engagement-reaction\b[^"]*)"/',
                'class="$1 is-active"',
                $html,
                1
            );
        }

        if ( 'star' === $mode ) {
            // Ensure rating summary stays visible for typography controls.
            $html = str_replace( ' is-zero', '', $html );
            $html = preg_replace(
                '/(data-ulp-rating-summary=["\'])0(["\'])/',
                '${1}4.5$2',
                $html
            );
        }

        return is_string( $html ) ? $html : '';
    }

}

