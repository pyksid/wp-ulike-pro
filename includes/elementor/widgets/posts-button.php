<?php
namespace WpUlikePro\Includes\Elementor\Elements;

use Elementor\Plugin;
use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Image_Size;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Utils;
use Elementor\Control_Media;
use Elementor\Group_Control_Border;
use Elementor\Icons_Manager;


if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly.
}

/**
 * Elementor 'PostsButton' widget.
 *
 * Elementor widget that displays wp ulike button.
 *
 * @since 1.0.0
 */
class PostsButton extends Widget_Base {

    /**
     * Get widget name.
     *
     * Retrieve 'PostsButton' widget name.
     *
     * @since 1.0.0
     * @access public
     *
     * @return string Widget name.
     */
    public function get_name() {
        return 'wp_ulike_posts_button';
    }

    /**
     * Get widget title.
     *
     * Retrieve 'PostsButton' widget title.
     *
     * @since 1.0.0
     * @access public
     *
     * @return string Widget title.
     */
    public function get_title() {
        return esc_html__('Like Button', WP_ULIKE_PRO_DOMAIN );
    }

    /**
     * Get widget icon.
     *
     * Retrieve 'PostsButton' widget icon.
     *
     * @since 1.0.0
     * @access public
     *
     * @return string Widget icon.
     */
    public function get_icon() {
        return 'eicon-facebook-like-box';
    }

    /**
     * Get widget categories.
     *
     * Retrieve 'PostsButton' widget icon.
     *
     * @since 1.0.0
     * @access public
     *
     * @return string Widget icon.
     */
    public function get_categories() {
        return array( WP_ULIKE_PRO_DOMAIN );
    }

    /**
     * Register 'PostsButton' widget controls.
     *
     * Adds different input fields to allow the user to change and customize the widget settings.
     *
     * @since 1.0.0
     * @access protected
     */
    protected function register_controls() {

        /*-----------------------------------------------------------------------------------*/
        /*  Content TAB
        /*-----------------------------------------------------------------------------------*/

        $this->start_controls_section(
            'general',
            array(
                'label'      => esc_html__('General', WP_ULIKE_PRO_DOMAIN ),
            )
        );

        $this->add_control(
            'style',
            array(
                'label'       => esc_html__('Template', WP_ULIKE_PRO_DOMAIN),
                'type'        => Controls_Manager::SELECT,
                'default'     => 'wpulike-default',
                'options'     => wp_ulike_pro_get_templates_list_by_name()
            )
        );

        $this->add_control(
            'like_icon',
            array(
                'label'        => esc_html__('Like Icon', WP_ULIKE_PRO_DOMAIN ),
                'type'         => Controls_Manager::ICONS,
                'condition'    => array(
                    'style' => array( 'wpulike-default', 'wpulike-heart', 'wp-ulike-pro-default' ),
                )
            )
        );

        $this->add_control(
            'dislike_icon',
            array(
                'label'        => esc_html__('Dislike Icon', WP_ULIKE_PRO_DOMAIN ),
                'type'         => Controls_Manager::ICONS,
                'condition'    => array(
                    'style' => array( 'wp-ulike-pro-default' ),
                )
            )
        );

        $this->add_control(
            'custom_id',
            array(
                'label'        => esc_html__('Enable Custom ID',WP_ULIKE_PRO_DOMAIN ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__( 'On', WP_ULIKE_PRO_DOMAIN ),
                'label_off'    => esc_html__( 'Off', WP_ULIKE_PRO_DOMAIN ),
                'return_value' => 'yes',
                'default'      => 'no'
            )
        );

        $this->add_control(
            'item_id',
            array(
                'label'       => esc_html__('Enter a Post ID',WP_ULIKE_PRO_DOMAIN ),
                'description' => esc_html__('You can set a custom id instead of our automattic get id function to make multiple buttons in a page.',WP_ULIKE_PRO_DOMAIN ),
                'type'        => Controls_Manager::NUMBER,
                'default'     => '',
                'step'        => 1,
                'condition'    => array(
                    'custom_id' => 'yes',
                )
            )
        );

        $this->add_control(
            'enable_dynamic_id',
            array(
                'label'        => esc_html__('Enable Custom Dynamic ID',WP_ULIKE_PRO_DOMAIN ),
                'description'  => esc_html__('By activating this option, the IDs of the buttons are created dynamically according to the Item ID set.',WP_ULIKE_PRO_DOMAIN ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__( 'On', WP_ULIKE_PRO_DOMAIN ),
                'label_off'    => esc_html__( 'Off', WP_ULIKE_PRO_DOMAIN ),
                'return_value' => 'yes',
                'default'      => 'no',
                'condition'    => array(
                    'custom_id' => 'yes',
                    'item_id!'  => '',
                )
            )
        );

        $this->add_control(
            'display_counter',
            array(
                'label'        => esc_html__('Display Counter',WP_ULIKE_PRO_DOMAIN ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__( 'On', WP_ULIKE_PRO_DOMAIN ),
                'label_off'    => esc_html__( 'Off', WP_ULIKE_PRO_DOMAIN ),
                'return_value' => 'yes',
                'default'      => 'yes'
            )
        );

        $this->add_control(
            'display_likers',
            array(
                'label'        => esc_html__('Display Likers Box',WP_ULIKE_PRO_DOMAIN ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__( 'On', WP_ULIKE_PRO_DOMAIN ),
                'label_off'    => esc_html__( 'Off', WP_ULIKE_PRO_DOMAIN ),
                'return_value' => 'yes',
                'default'      => 'no'
            )
        );

        $this->add_control(
            'likers_style',
            array(
                'label'   => esc_html__( 'Likers Display', WP_ULIKE_PRO_DOMAIN ),
                'type'    => Controls_Manager::SELECT,
                'options' => array(
					'default' => esc_html__( 'Default', WP_ULIKE_PRO_DOMAIN ),
					'popover' => esc_html__( 'Popover', WP_ULIKE_PRO_DOMAIN ),
					'pile'    => esc_html__( 'Pile Modal', WP_ULIKE_PRO_DOMAIN )
                ),
                'default'   => 'popover',
                'condition' => array(
                    'display_likers' => 'yes',
                )
            )
        );

        $this->end_controls_section();


        $this->start_controls_section(
			'section_style_icon',
			[
				'label'      => esc_html__( 'Icon', WP_ULIKE_PRO_DOMAIN ),
				'tab'        => Controls_Manager::TAB_STYLE,
				'conditions' => array(
                    'relation' => 'or',
                    'terms'    => [
						[
							'name' => 'like_icon[value]',
							'operator' => '!=',
							'value' => '',
						],
						[
							'name' => 'dislike_icon[value]',
							'operator' => '!=',
							'value' => '',
						]
					],
                )
			]
		);

		$this->start_controls_tabs( 'icon_colors' );

		$this->start_controls_tab(
			'icon_colors_normal',
			[
				'label' => esc_html__( 'Not Liked', WP_ULIKE_PRO_DOMAIN ),
			]
		);

		$this->add_control(
			'primary_color',
			[
				'label' => esc_html__( 'Primary Color', WP_ULIKE_PRO_DOMAIN ),
				'type' => Controls_Manager::COLOR,
				'default' => '',
				'selectors' => [
					'{{WRAPPER}} .wp_ulike_btn .elementor-icon' => 'background-color: {{VALUE}};',
					'{{WRAPPER}} .wp_ulike_btn .elementor-icon' => 'color: {{VALUE}}; border-color: {{VALUE}};',
					'{{WRAPPER}} .wp_ulike_btn .elementor-icon svg path, {{WRAPPER}} .elementor-icon svg' => 'fill: {{VALUE}} !important;',
				],
				'scheme' => [
					'type' => 'color',
					'value' => '1',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'icon_colors_hover',
			[
				'label' => esc_html__( 'Liked', WP_ULIKE_PRO_DOMAIN ),
			]
		);

		$this->add_control(
			'active_primary_color',
			[
				'label' => esc_html__( 'Primary Color', WP_ULIKE_PRO_DOMAIN ),
				'type' => Controls_Manager::COLOR,
				'default' => '',
				'selectors' => [
					'{{WRAPPER}} .wp_ulike_btn.wp_ulike_btn_is_active .elementor-icon' => 'background-color: {{VALUE}};',
					'{{WRAPPER}} .wp_ulike_btn.wp_ulike_btn_is_active .elementor-icon' => 'color: {{VALUE}} !important;',
					'{{WRAPPER}} .wp_ulike_btn.wp_ulike_btn_is_active .elementor-icon svg path, {{WRAPPER}} .elementor-icon svg' => 'fill: {{VALUE}} !important;',
				],
				'scheme' => [
					'type' => 'color',
					'value' => '1',
				],
			]
        );

		$this->add_control(
			'hover_animation',
			[
				'label' => esc_html__( 'Hover Animation', WP_ULIKE_PRO_DOMAIN ),
				'type' => Controls_Manager::HOVER_ANIMATION,
			]
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_responsive_control(
			'size',
			[
				'label' => esc_html__( 'Size', WP_ULIKE_PRO_DOMAIN ),
				'type' => Controls_Manager::SLIDER,
				'range' => [
					'px' => [
						'min' => 6,
						'max' => 300,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .elementor-icon' => 'font-size: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'rotate',
			[
				'label' => esc_html__( 'Rotate', WP_ULIKE_PRO_DOMAIN ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ 'deg' ],
				'default' => [
					'size' => 0,
					'unit' => 'deg',
				],
				'tablet_default' => [
					'unit' => 'deg',
				],
				'mobile_default' => [
					'unit' => 'deg',
				],
				'selectors' => [
					'{{WRAPPER}} .elementor-icon i, {{WRAPPER}} .elementor-icon svg' => 'transform: rotate({{SIZE}}{{UNIT}});',
				],
			]
        );

        $this->end_controls_section();

        $this->start_controls_section(
			'section_style_counter',
			[
				'label'      => esc_html__( 'Counter', WP_ULIKE_PRO_DOMAIN ),
				'tab'        => Controls_Manager::TAB_STYLE,
				'condition' => array(
                    'display_counter' => 'yes'
                )
			]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name'     => 'counter_typography',
                'scheme'   => '1',
                'selector' => '{{WRAPPER}} .wpulike-elementor-widget .count-box'
            )
        );

        $this->add_responsive_control(
            'counter_padding',
            array(
                'label'      => esc_html__( 'Padding', WP_ULIKE_PRO_DOMAIN ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => array( 'px' ),
                'selectors'  => array(
                    '{{WRAPPER}} .wpulike-elementor-widget .count-box' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                )
            )
        );

        $this->end_controls_section();

        $this->start_controls_section(
			'section_style_button',
			[
				'label'      => esc_html__( 'Button', WP_ULIKE_PRO_DOMAIN ),
				'tab'        => Controls_Manager::TAB_STYLE,
			]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            array(
                'name'      => 'box_shadow',
                'selector'  => '{{WRAPPER}} .wp_ulike_btn'
            )
        );

        $this->add_responsive_control(
            'button_padding',
            array(
                'label'      => esc_html__( 'Padding', WP_ULIKE_PRO_DOMAIN ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => array( 'px' ),
                'selectors'  => array(
                    '{{WRAPPER}} .wp_ulike_btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                )
            )
        );

        $this->add_responsive_control(
            'button_margin',
            array(
                'label'      => esc_html__( 'Margin', WP_ULIKE_PRO_DOMAIN ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => array( 'px' ),
                'selectors'  => array(
                    '{{WRAPPER}} .wp_ulike_btn' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                )
            )
        );

		$this->add_control(
			'border_radius',
			[
				'label' => esc_html__( 'Border Radius', WP_ULIKE_PRO_DOMAIN ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors' => [
					'{{WRAPPER}} .wp_ulike_btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				]
			]
        );

        $this->add_responsive_control(
			'icon_width',
			[
				'label' => esc_html__( 'Icon Width', WP_ULIKE_PRO_DOMAIN ),
                'type' => Controls_Manager::SLIDER,
                'size_units' => [ 'px', 'em' ],
				'selectors' => [
					'{{WRAPPER}} .wp_ulike_btn, {{WRAPPER}} .wp_ulike_put_image:after' => 'width: {{SIZE}}{{UNIT}};',
                ]
			]
        );
        $this->add_responsive_control(
			'icon_height',
			[
				'label' => esc_html__( 'Icon Height', WP_ULIKE_PRO_DOMAIN ),
                'type' => Controls_Manager::SLIDER,
                'size_units' => [ 'px', 'em' ],
				'selectors' => [
					'{{WRAPPER}} .wp_ulike_btn, {{WRAPPER}} .wp_ulike_put_image:after' => 'height: {{SIZE}}{{UNIT}};',
                ]
			]
        );

        $this->end_controls_section();
    }

    /**
    * Render PostsButton widget output on the frontend.
    *
    * Written in PHP and used to generate the final HTML.
    *
    * @since 1.0.0
    * @access protected
    */
    protected function render() {
        $settings      = $this->get_settings_for_display();
        $custom_icon   = false;
        $like_icon     = NULL;
        $dislike_icon  = NULL;
        $wrapper_class = 'wpulike-elementor-widget';

        // var_export($settings['icons']);
        $this->add_render_attribute( 'icon-wrapper', 'class', [ 'elementor-icon', 'elementor-animation-' . $settings['hover_animation'] ] );
		if ( !empty( $settings['like_icon']['value'] ) && Icons_Manager::is_migration_allowed() ) {
            ob_start();
            Icons_Manager::render_icon( $settings['like_icon'], [ 'aria-hidden' => 'true' ] );
            $like_icon = sprintf( '<span %s>%s</span>', $this->get_render_attribute_string( 'icon-wrapper' ), ob_get_clean() );
            $custom_icon = true;
        }
		if ( !empty( $settings['dislike_icon']['value'] ) && Icons_Manager::is_migration_allowed() ) {
            ob_start();
            Icons_Manager::render_icon( $settings['dislike_icon'], [ 'aria-hidden' => 'true' ] );
            $dislike_icon = sprintf( '<span %s>%s</span>', $this->get_render_attribute_string( 'icon-wrapper' ), ob_get_clean() );
            $custom_icon = true;
        }

        if( $custom_icon ){
            $wrapper_class .= ' wpulike-elementor-icon-enabled';
        }

        if( !wp_ulike_is_true($settings['display_counter']) ){
            $wrapper_class .= ' wpulike-hide-counter';
        }

        // Check deprecated option value
        if( ! isset( $settings['likers_style'] ) && isset( $settings['disable_pophover'] ) ){
            $settings['likers_style'] = wp_ulike_is_true( $settings['disable_pophover'] ) ? 'default' : 'popover';
        }

        //Main data
		$defaults =  array(
			"id"                   => $settings['item_id'],
			"display_likers"       => wp_ulike_is_true( $settings['display_likers'] ),
			"likers_style"         => $settings['likers_style'],
			"style"                => $settings['style'],
			"button_type"          => 'image',
			"wrapper_class"        => $wrapper_class,
			"up_vote_inner_text"   => $custom_icon ? $like_icon : NULL,
			"down_vote_inner_text" => $custom_icon ? $dislike_icon : NULL
        );

        if( empty( $defaults['id'] ) ){
            unset($defaults['id']);

        } elseif( wp_ulike_is_true( $settings['enable_dynamic_id'] ) ){
            global $post;
            $post_id = ! empty( $post->ID ) ? wp_ulike_get_the_id( $post->ID ) : 1;
            // Update id value
            $defaults['id'] = intval( preg_replace('/\D/', '', $this->get_id() ) *  $defaults['id'] *  $post_id );
        }

        return wp_ulike( 'get', $defaults );
    }

}
