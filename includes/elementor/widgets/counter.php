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
 * Elementor 'Counter' widget.
 *
 * Elementor widget that displays wp ulike button.
 *
 * @since 1.0.0
 */
class Counter extends Widget_Base {

    /**
     * Get widget name.
     *
     * Retrieve 'Counter' widget name.
     *
     * @since 1.0.0
     * @access public
     *
     * @return string Widget name.
     */
    public function get_name() {
        return 'wp_ulike_counter';
    }

    /**
     * Get widget title.
     *
     * Retrieve 'Counter' widget title.
     *
     * @since 1.0.0
     * @access public
     *
     * @return string Widget title.
     */
    public function get_title() {
        return esc_html__('Like/Dislike Counter', WP_ULIKE_PRO_DOMAIN );
    }

    /**
     * Get widget icon.
     *
     * Retrieve 'Counter' widget icon.
     *
     * @since 1.0.0
     * @access public
     *
     * @return string Widget icon.
     */
    public function get_icon() {
        return 'eicon-counter-circle';
    }

    /**
     * Get widget categories.
     *
     * Retrieve 'Counter' widget icon.
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
     * Register 'Counter' widget controls.
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
            'status',
            array(
                'label'       => esc_html__('Select status', WP_ULIKE_PRO_DOMAIN),
                'type'        => Controls_Manager::SELECT,
                'default'     => 'like',
                'options'     => array(
                    'like'    => esc_html__('Like', WP_ULIKE_PRO_DOMAIN),
                    'dislike' => esc_html__('DisLike', WP_ULIKE_PRO_DOMAIN)
                )
            )
        );

        // $this->add_control(
        //     'type',
        //     array(
        //         'label'       => esc_html__('Select Counter Type', WP_ULIKE_PRO_DOMAIN),
        //         'type'        => Controls_Manager::SELECT,
        //         'default'     => 'post',
        //         'options'     => array(
        //             'post'     => esc_html__('Post', WP_ULIKE_PRO_DOMAIN),
        //             'comment'  => esc_html__('Comment', WP_ULIKE_PRO_DOMAIN),
        //             'activity' => esc_html__('Activity', WP_ULIKE_PRO_DOMAIN),
        //             'topic'    => esc_html__('Topic', WP_ULIKE_PRO_DOMAIN)
        //         )
        //     )
        // );

        $this->add_control(
            'selected_icon',
            array(
                'label'        => esc_html__('Icon', WP_ULIKE_PRO_DOMAIN ),
                'type'         => Controls_Manager::ICONS
            )
        );


		$this->add_control(
			'icon_align',
			[
				'label' => esc_html__( 'Icon Position', WP_ULIKE_PRO_DOMAIN ),
				'type' => Controls_Manager::SELECT,
				'default' => 'left',
				'options' => [
					'left' => esc_html__( 'Before', WP_ULIKE_PRO_DOMAIN ),
					'right' => esc_html__( 'After', WP_ULIKE_PRO_DOMAIN ),
				],
				'condition' => [
					'selected_icon[value]!' => '',
				],
			]
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
							'name' => 'selected_icon[value]',
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
				'label' => esc_html__( 'Normal', WP_ULIKE_PRO_DOMAIN ),
			]
		);

		$this->add_control(
			'primary_color',
			[
				'label' => esc_html__( 'Primary Color', WP_ULIKE_PRO_DOMAIN ),
				'type' => Controls_Manager::COLOR,
				'default' => '',
				'selectors' => [
					'{{WRAPPER}} .elementor-icon' => 'background-color: {{VALUE}};',
					'{{WRAPPER}} .elementor-icon' => 'color: {{VALUE}}; border-color: {{VALUE}};',
					'{{WRAPPER}} .elementor-icon svg path, {{WRAPPER}} .elementor-icon svg' => 'fill: {{VALUE}} !important;',
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
				'label' => esc_html__( 'Hover', WP_ULIKE_PRO_DOMAIN ),
			]
		);

		$this->add_control(
			'hover_primary_color',
			[
				'label' => esc_html__( 'Primary Color', WP_ULIKE_PRO_DOMAIN ),
				'type' => Controls_Manager::COLOR,
				'default' => '',
				'selectors' => [
					'{{WRAPPER}} .elementor-icon:hover' => 'background-color: {{VALUE}};',
					'{{WRAPPER}} .elementor-icon:hover' => 'color: {{VALUE}} !important;',
					'{{WRAPPER}} .elementor-icon:hover svg path, {{WRAPPER}} .elementor-icon:hover svg' => 'fill: {{VALUE}} !important;',
				],
				'scheme' => [
					'type' => 'color',
					'value' => '1',
				],
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
				'tab'        => Controls_Manager::TAB_STYLE
			]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name'     => 'counter_typography',
                'scheme'   => '1',
                'selector' => '{{WRAPPER}} .wp-ulike-counter-value'
            )
        );

        $this->add_responsive_control(
            'counter_padding',
            array(
                'label'      => esc_html__( 'Padding', WP_ULIKE_PRO_DOMAIN ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => array( 'px' ),
                'selectors'  => array(
                    '{{WRAPPER}} .wp-ulike-counter-value' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                )
            )
        );

        $this->end_controls_section();
    }

    /**
    * Render Counter widget output on the frontend.
    *
    * Written in PHP and used to generate the final HTML.
    *
    * @since 1.0.0
    * @access protected
    */
    protected function render() {
		$settings = $this->get_settings_for_display();

		$this->add_render_attribute( 'wrapper', 'class', 'wp-ulike-counter-wrapper elementor-icon-' . $settings['icon_align'] );

        $this->add_render_attribute( 'icon-wrapper', 'class', 'elementor-icon'  );
        $this->add_render_attribute( 'counter-wrapper', 'class', 'wp-ulike-counter-value' );

		$icon_tag = 'div';

		if ( empty( $settings['icon'] ) && ! Icons_Manager::is_migration_allowed() ) {
			// add old default
			$settings['icon'] = 'fa fa-star';
		}

		if ( ! empty( $settings['icon'] ) ) {
			$this->add_render_attribute( 'icon', 'class', $settings['icon'] );
			$this->add_render_attribute( 'icon', 'aria-hidden', 'true' );
		}

		$migrated = isset( $settings['__fa4_migrated']['selected_icon'] );
        $is_new = empty( $settings['icon'] ) && Icons_Manager::is_migration_allowed();

        $item_ID     = empty($settings['item_id']) ? wp_ulike_pro_get_auto_id( 'post' ) : $settings['item_id'];
        $is_distinct = \wp_ulike_setting_repo::isDistinct('post');
        $counter     = wp_ulike_get_counter_value( $item_ID, 'post', $settings['status'], $is_distinct  );

		?>
		<div <?php echo $this->get_render_attribute_string( 'wrapper' ); ?>>
			<div <?php echo $this->get_render_attribute_string( 'icon-wrapper' ); ?>>
			<?php if ( $is_new || $migrated ) :
				Icons_Manager::render_icon( $settings['selected_icon'], [ 'aria-hidden' => 'true' ] );
			else : ?>
				<i <?php echo $this->get_render_attribute_string( 'icon' ); ?>></i>
			<?php endif; ?>
			</div>
            <div <?php echo $this->get_render_attribute_string( 'counter-wrapper' ); ?>>
                <?php echo esc_html( $counter ); ?>
            </div>
		</div>
		<?php
    }

}
