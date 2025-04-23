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
 * Elementor 'MostLikedComments' widget.
 *
 * Elementor widget that displays an 'MostLikedComments' with lightbox.
 *
 * @since 1.0.0
 */
class MostLikedComments extends Widget_Base {

    /**
     * Get widget name.
     *
     * Retrieve 'MostLikedComments' widget name.
     *
     * @since 1.0.0
     * @access public
     *
     * @return string Widget name.
     */
    public function get_name() {
        return 'wp_ulike_top_comments';
    }

    /**
     * Get widget title.
     *
     * Retrieve 'MostLikedComments' widget title.
     *
     * @since 1.0.0
     * @access public
     *
     * @return string Widget title.
     */
    public function get_title() {
        return esc_html__('Top Comments', WP_ULIKE_PRO_DOMAIN );
    }

    /**
     * Get widget icon.
     *
     * Retrieve 'MostLikedComments' widget icon.
     *
     * @since 1.0.0
     * @access public
     *
     * @return string Widget icon.
     */
    public function get_icon() {
        return 'eicon-comments';
    }

    /**
     * Get recent comments.
     *
     * Retrieve 'MostLikedComments' widget query.
     *
     * @since 1.0.0
     * @access public
     *
     * @return array Query.
     */
    public function get_post_types( $args = array()  ) {
        // Result variable
        $result = array( 'all' => esc_html__( 'All Post Types', WP_ULIKE_PRO_DOMAIN  ) );
        // Get all public post types
        $get_post_types = get_post_types( array(
            'public' => true,
            'exclude_from_search' => false
            )
        );
        foreach ( $get_post_types as $key =>  $value ) {
            $result[ $key ] = ucfirst( $value );
        }

        return $result;
    }

    /**
     * Get widget categories.
     *
     * Retrieve 'MostLikedComments' widget icon.
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
     * Register 'MostLikedComments' widget controls.
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
            'general_section',
            array(
                'label'      => esc_html__('General', WP_ULIKE_PRO_DOMAIN ),
            )
        );

        $this->add_control(
            'post_types',
            array(
                'label'       => esc_html__( 'Post Types', WP_ULIKE_PRO_DOMAIN ),
                'label_block' => true,
                'type'        => Controls_Manager::SELECT,
                'default'     => 'all',
                'options'     => $this->get_post_types()
            )
        );

        $this->add_control(
            'number',
            array(
                'label'       => esc_html__('Number of items', WP_ULIKE_PRO_DOMAIN),
                'type'        => Controls_Manager::NUMBER,
                'default'     => '8',
                'min'         => 1,
                'step'        => 1
            )
        );

        $this->add_control(
            'peroid_limit',
            array(
                'label'   => esc_html__( 'Peroid Limit', WP_ULIKE_PRO_DOMAIN ),
                'type'    => Controls_Manager::SELECT,
                'options' => array(
					'all'       => esc_html__( 'All', WP_ULIKE_PRO_DOMAIN ),
					'today'     => esc_html__( 'Today', WP_ULIKE_PRO_DOMAIN ),
					'yesterday' => esc_html__( 'Yesterday', WP_ULIKE_PRO_DOMAIN ),
					'week'      => esc_html__( 'Week', WP_ULIKE_PRO_DOMAIN ),
					'month'     => esc_html__( 'Month', WP_ULIKE_PRO_DOMAIN ),
					'year'      => esc_html__( 'Year', WP_ULIKE_PRO_DOMAIN ),
					'past_days' => esc_html__( 'Last X Days', WP_ULIKE_PRO_DOMAIN )
                ),
                'default'   => 'all',
            )
        );

		$this->add_control(
			'past_days_num',
			array(
				'label' => esc_html__( 'Past Days Number', WP_ULIKE_PRO_DOMAIN ),
				'type' => Controls_Manager::NUMBER,
				'default' => 30,
                'condition' => array(
                    'peroid_limit' => 'past_days'
                )
            )
        );

        $this->add_control(
            'display_title',
            array(
                'label'        => esc_html__('Display title',WP_ULIKE_PRO_DOMAIN ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__( 'On', WP_ULIKE_PRO_DOMAIN ),
                'label_off'    => esc_html__( 'Off', WP_ULIKE_PRO_DOMAIN ),
                'return_value' => 'yes',
                'default'      => 'yes'
            )
        );

        $this->add_control(
            'title_tag',
            array(
                'label'   => esc_html__( 'Title Tag', WP_ULIKE_PRO_DOMAIN ),
                'type'    => Controls_Manager::SELECT,
                'options' => array(
					'h1'   => 'H1',
					'h2'   => 'H2',
					'h3'   => 'H3',
					'h4'   => 'H4',
					'h5'   => 'H5',
					'h6'   => 'H6',
					'div'  => 'div',
					'span' => 'span',
					'p'    => 'p'
                ),
                'default'   => 'h3',
                'condition' => array(
                    'display_title' => 'yes'
                )
            )
        );

        $this->add_control(
            'display_content',
            array(
                'label'        => esc_html__('Display content',WP_ULIKE_PRO_DOMAIN ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__( 'On', WP_ULIKE_PRO_DOMAIN ),
                'label_off'    => esc_html__( 'Off', WP_ULIKE_PRO_DOMAIN ),
                'return_value' => 'yes',
                'default'      => 'yes'
            )
        );

        $this->add_control(
            'display_info',
            array(
                'label'        => esc_html__('Display info',WP_ULIKE_PRO_DOMAIN ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__( 'On', WP_ULIKE_PRO_DOMAIN ),
                'label_off'    => esc_html__( 'Off', WP_ULIKE_PRO_DOMAIN ),
                'return_value' => 'yes',
                'default'      => 'yes'
            )
        );

        $this->end_controls_section();

        /*-----------------------------------------------------------------------------------*/
        /*  pagination_section
        /*-----------------------------------------------------------------------------------*/

        $this->start_controls_section(
            'pagination_section',
            array(
                'label'      => esc_html__('Pagination', WP_ULIKE_PRO_DOMAIN ),
            )
        );

        $this->add_control(
            'enable_pagination',
            array(
                'label'        => esc_html__('Enable Pagination',WP_ULIKE_PRO_DOMAIN ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__( 'On', WP_ULIKE_PRO_DOMAIN ),
                'label_off'    => esc_html__( 'Off', WP_ULIKE_PRO_DOMAIN ),
                'return_value' => 'yes',
                'default'      => 'no'
            )
        );

		$this->add_control(
			'prev_text',
			array(
				'label'       => esc_html__( 'Previous Text', WP_ULIKE_PRO_DOMAIN),
				'type'        => Controls_Manager::TEXT,
                'default'     => 'Prev',
				'condition' => array(
                    'enable_pagination' => 'yes'
                )
			)
        );

		$this->add_control(
			'next_text',
			array(
				'label'       => esc_html__( 'Next Text', WP_ULIKE_PRO_DOMAIN),
				'type'        => Controls_Manager::TEXT,
                'default'     => 'Next',
				'condition' => array(
                    'enable_pagination' => 'yes'
                )
			)
        );

        $this->end_controls_section();

        /*-----------------------------------------------------------------------------------*/
        /*  custom_text_section
        /*-----------------------------------------------------------------------------------*/

        $this->start_controls_section(
            'custom_text_section',
            array(
                'label'      => esc_html__('Custom Text', WP_ULIKE_PRO_DOMAIN ),
            )
        );

		$this->add_control(
			'not_found_text',
			[
				'label'       => esc_html__( 'Not Found Text', WP_ULIKE_PRO_DOMAIN),
				'type'        => Controls_Manager::TEXT,
				'default'     => 'No comment found!',
				'placeholder' => esc_html__( 'Type your text here', WP_ULIKE_PRO_DOMAIN ),
			]
        );

		$this->add_control(
			'by_text',
			[
				'label'       => esc_html__( 'By Text', WP_ULIKE_PRO_DOMAIN),
				'type'        => Controls_Manager::TEXT,
				'default'     => 'By',
				'placeholder' => esc_html__( 'Type your text here', WP_ULIKE_PRO_DOMAIN ),
			]
        );

		$this->add_control(
			'ago_text',
			[
				'label'       => esc_html__( 'Ago Text', WP_ULIKE_PRO_DOMAIN),
				'type'        => Controls_Manager::TEXT,
				'default'     => 'Ago',
				'placeholder' => esc_html__( 'Type your text here', WP_ULIKE_PRO_DOMAIN ),
			]
        );

		$this->add_control(
			'visit_text',
			[
				'label'       => esc_html__( 'Visit Text', WP_ULIKE_PRO_DOMAIN),
				'type'        => Controls_Manager::TEXT,
				'default'     => '[Visit]',
				'placeholder' => esc_html__( 'Type your text here', WP_ULIKE_PRO_DOMAIN ),
			]
        );

        $this->end_controls_section();

        /*-----------------------------------------------------------------------------------*/
        /*  title_style_section
        /*-----------------------------------------------------------------------------------*/

        $this->start_controls_section(
            'title_style_section',
            array(
                'label'     => esc_html__( 'Title', WP_ULIKE_PRO_DOMAIN ),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => array(
                    'display_title' => 'yes'
                )
            )
        );

        $this->start_controls_tabs( 'title_colors' );

        $this->start_controls_tab(
            'title_color_normal',
            array(
                'label' => esc_html__( 'Normal' , WP_ULIKE_PRO_DOMAIN )
            )
        );

        $this->add_control(
            'title_color',
            array(
                'label' => esc_html__( 'Color', WP_ULIKE_PRO_DOMAIN ),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .wp-ulike-entry-title a' => 'color: {{VALUE}};',
                )
            )
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'title_color_hover',
            array(
                'label' => esc_html__( 'Hover' , WP_ULIKE_PRO_DOMAIN )
            )
        );

        $this->add_control(
            'title_hover_color',
            array(
                'label' => esc_html__( 'Color', WP_ULIKE_PRO_DOMAIN ),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .wp-ulike-entry-title a:hover' => 'color: {{VALUE}};',
                )
            )
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name' => 'title_typography',
                'scheme' => '1',
                'selector' => '{{WRAPPER}} .wp-ulike-entry-title'
            )
        );

        $this->add_responsive_control(
            'title_margin_bottom',
            array(
                'label' => esc_html__( 'Bottom space', WP_ULIKE_PRO_DOMAIN ),
                'type' => Controls_Manager::SLIDER,
                'range' => array(
                    'px' => array(
                        'max' => 100,
                    ),
                ),
                'selectors' => array(
                    '{{WRAPPER}} .wp-ulike-entry-title' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                )
            )
        );

        $this->end_controls_section();

        /*-----------------------------------------------------------------------------------*/
        /*  item_style_section
        /*-----------------------------------------------------------------------------------*/

        $this->start_controls_section(
            'item_style_section',
            array(
                'label'     => esc_html__( 'Items', WP_ULIKE_PRO_DOMAIN ),
                'tab'       => Controls_Manager::TAB_STYLE
            )
        );

        $this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name' => 'item_border',
				'label' => esc_html__( '', WP_ULIKE_PRO_DOMAIN ),
				'selector' => '{{WRAPPER}} .wp-ulike-item',
			]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            array(
                'name'      => 'item_shadow',
                'selector'  => '{{WRAPPER}} .wp-ulike-item'
            )
        );

        $this->add_responsive_control(
            'item_padding',
            array(
                'label'      => esc_html__( 'Padding', WP_ULIKE_PRO_DOMAIN ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => array( 'px' ),
                'selectors'  => array(
                    '{{WRAPPER}} .wp-ulike-item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                )
            )
        );

        $this->add_responsive_control(
            'item_margin',
            array(
                'label'      => esc_html__( 'Margin', WP_ULIKE_PRO_DOMAIN ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => array( 'px' ),
                'selectors'  => array(
                    '{{WRAPPER}} .wp-ulike-item' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                )
            )
        );

		$this->add_control(
			'alignment',
			array(
				'label' => esc_html__( 'Alignment', WP_ULIKE_PRO_DOMAIN ),
				'type' => Controls_Manager::CHOOSE,
				'label_block' => false,
				'options' => array(
					'left' => array(
						'title' => esc_html__( 'Left', WP_ULIKE_PRO_DOMAIN ),
						'icon' => 'fa fa-align-left',
					),
					'center' => array(
						'title' => esc_html__( 'Center', WP_ULIKE_PRO_DOMAIN ),
						'icon' => 'fa fa-align-center',
					),
					'right' => array(
						'title' => esc_html__( 'Right', WP_ULIKE_PRO_DOMAIN ),
						'icon' => 'fa fa-align-right',
					),
				),
				'prefix_class' => 'wp-ulike-posts--align-',
			)
		);

        $this->end_controls_section();

        /*-----------------------------------------------------------------------------------*/
        /*  title_style_section
        /*-----------------------------------------------------------------------------------*/

        $this->start_controls_section(
            'content_style_section',
            array(
                'label'     => esc_html__( 'Content', WP_ULIKE_PRO_DOMAIN ),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => array(
                    'display_content' => 'yes'
                )
            )
        );

        $this->add_control(
            'content_color',
            array(
                'label' => esc_html__( 'Color', WP_ULIKE_PRO_DOMAIN ),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .wp-ulike-entry-content' => 'color: {{VALUE}};',
                )
            )
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name' => 'content_typography',
                'scheme' => '1',
                'selector' => '{{WRAPPER}} .wp-ulike-entry-content'
            )
        );

        $this->add_responsive_control(
            'content_margin_bottom',
            array(
                'label' => esc_html__( 'Bottom space', WP_ULIKE_PRO_DOMAIN ),
                'type' => Controls_Manager::SLIDER,
                'range' => array(
                    'px' => array(
                        'max' => 100,
                    ),
                ),
                'selectors' => array(
                    '{{WRAPPER}} .wp-ulike-entry-content' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                )
            )
        );

        $this->end_controls_section();

        /*-----------------------------------------------------------------------------------*/
        /*  info_style_section
        /*-----------------------------------------------------------------------------------*/

        $this->start_controls_section(
            'info_style_section',
            array(
                'label'     => esc_html__( 'Meta Info', WP_ULIKE_PRO_DOMAIN ),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => array(
                    'display_info' => 'yes'
                )
            )
        );

        $this->start_controls_tabs( 'info_colors' );

        $this->start_controls_tab(
            'info_color_normal',
            array(
                'label' => esc_html__( 'Normal' , WP_ULIKE_PRO_DOMAIN )
            )
        );

        $this->add_control(
            'info_color',
            array(
                'label' => esc_html__( 'Color', WP_ULIKE_PRO_DOMAIN ),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .wp-ulike-entry-info a, {{WRAPPER}} .wp-ulike-entry-info' => 'color: {{VALUE}};',
                )
            )
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'info_color_hover',
            array(
                'label' => esc_html__( 'Hover' , WP_ULIKE_PRO_DOMAIN )
            )
        );

        $this->add_control(
            'info_hover_color',
            array(
                'label' => esc_html__( 'Color', WP_ULIKE_PRO_DOMAIN ),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .wp-ulike-entry-info a:hover' => 'color: {{VALUE}};',
                )
            )
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name' => 'info_typography',
                'scheme' => '1',
                'selector' => '{{WRAPPER}} .wp-ulike-entry-info, {{WRAPPER}} .wp-ulike-entry-info a'
            )
        );

        $this->add_responsive_control(
            'info_margin_bottom',
            array(
                'label' => esc_html__( 'Bottom space', WP_ULIKE_PRO_DOMAIN ),
                'type'  => Controls_Manager::SLIDER,
                'range' => array(
                    'px' => array(
                        'max' => 100
                    )
                ),
                'selectors' => array(
                    '{{WRAPPER}} .wp-ulike-entry-info' => 'margin-bottom: {{SIZE}}{{UNIT}};'
                )
            )
        );

        $this->add_responsive_control(
            'info_spacing_between',
            array(
                'label' => esc_html__( 'Space between metas', WP_ULIKE_PRO_DOMAIN ),
                'type'  => Controls_Manager::SLIDER,
                'range' => array(
                    'px' => array(
                        'max' => 30
                    )
                ),
                'selectors' => array(
                    '{{WRAPPER}} .wp-ulike-entry-info [class^="wp-ulike-entry-"]:after' =>
                    'margin-right: {{SIZE}}{{UNIT}}; margin-left: {{SIZE}}{{UNIT}};'
                )
            )
        );

        $this->add_control(
            'date_icon',
            array(
                'label'        => esc_html__('Date Icon', WP_ULIKE_PRO_DOMAIN ),
                'type'         => Controls_Manager::ICONS,
				'default'      => array(
					'value' => 'fas fa-clock',
					'library' => 'fa-solid',
                )
            )
        );
        $this->add_control(
            'user_icon',
            array(
                'label'        => esc_html__('User Icon', WP_ULIKE_PRO_DOMAIN ),
                'type'         => Controls_Manager::ICONS,
				'default'      => array(
					'value' => 'fas fa-user',
					'library' => 'fa-solid',
                )
            )
        );
        $this->add_control(
            'like_icon',
            array(
                'label'        => esc_html__('Like Icon', WP_ULIKE_PRO_DOMAIN ),
                'type'         => Controls_Manager::ICONS,
				'default'      => array(
					'value' => 'fas fa-thumbs-up',
					'library' => 'fa-solid',
                )
            )
        );
        $this->add_control(
            'dislike_icon',
            array(
                'label'        => esc_html__('Dislike Icon', WP_ULIKE_PRO_DOMAIN ),
                'type'         => Controls_Manager::ICONS,
				'default'      => array(
					'value' => 'fas fa-thumbs-down',
					'library' => 'fa-solid',
                )
            )
        );

        $this->end_controls_section();

        /*-----------------------------------------------------------------------------------*/
        /*  pagination_style_section
        /*-----------------------------------------------------------------------------------*/

        $this->start_controls_section(
            'pagination_style_section',
            array(
                'label'     => esc_html__( 'Pagination', WP_ULIKE_PRO_DOMAIN ),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => array(
                    'enable_pagination' => 'yes'
                )
            )
        );

        $this->start_controls_tabs( 'pagination_colors' );

        $this->start_controls_tab(
            'pagination_color_normal',
            array(
                'label' => esc_html__( 'Normal' , WP_ULIKE_PRO_DOMAIN )
            )
        );

        $this->add_control(
            'pagination_color',
            array(
                'label' => esc_html__( 'Color', WP_ULIKE_PRO_DOMAIN ),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .wp-ulike-pro-pagination .page-numbers' => 'color: {{VALUE}};',
                )
            )
        );

        $this->add_control(
            'pagination_bg_color',
            array(
                'label' => esc_html__( 'Background', WP_ULIKE_PRO_DOMAIN ),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .wp-ulike-pro-pagination .page-numbers' => 'background-color: {{VALUE}};',
                )
            )
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'pagination_color_hover',
            array(
                'label' => esc_html__( 'Hover' , WP_ULIKE_PRO_DOMAIN )
            )
        );

        $this->add_control(
            'pagination_hover_color',
            array(
                'label' => esc_html__( 'Color', WP_ULIKE_PRO_DOMAIN ),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .wp-ulike-pro-pagination .page-numbers:hover' => 'color: {{VALUE}};',
                )
            )
        );

        $this->add_control(
            'pagination_bg_hover_color',
            array(
                'label' => esc_html__( 'Background', WP_ULIKE_PRO_DOMAIN ),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .wp-ulike-pro-pagination .page-numbers:hover' => 'background-color: {{VALUE}};',
                )
            )
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'pagination_color_active',
            array(
                'label' => esc_html__( 'Current' , WP_ULIKE_PRO_DOMAIN )
            )
        );

        $this->add_control(
            'pagination_active_color',
            array(
                'label' => esc_html__( 'Color', WP_ULIKE_PRO_DOMAIN ),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .wp-ulike-pro-pagination .page-numbers.current' => 'color: {{VALUE}};',
                )
            )
        );

        $this->add_control(
            'pagination_bg_active_color',
            array(
                'label' => esc_html__( 'Background', WP_ULIKE_PRO_DOMAIN ),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .wp-ulike-pro-pagination .page-numbers.current' => 'background-color: {{VALUE}};',
                )
            )
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_responsive_control(
            'pagination_margin',
            array(
                'label'      => esc_html__( 'Margin', WP_ULIKE_PRO_DOMAIN ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => array( 'px' ),
                'selectors'  => array(
                    '{{WRAPPER}} .wp-ulike-pro-pagination' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                )
            )
        );

        $this->add_responsive_control(
            'pagination_padding',
            array(
                'label'      => esc_html__( 'Padding', WP_ULIKE_PRO_DOMAIN ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => array( 'px' ),
                'selectors'  => array(
                    '{{WRAPPER}} .wp-ulike-pro-pagination .page-numbers' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                )
            )
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            array(
                'name'      => 'pagination_shadow',
                'selector'  => '{{WRAPPER}} .wp-ulike-pro-pagination .page-numbers'
            )
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name' => 'pagination_typography',
                'scheme' => '1',
                'selector' => '{{WRAPPER}} .wp-ulike-pro-pagination .page-numbers'
            )
        );

        $this->end_controls_section();

    }

    /**
    * Render recentcomments widget output on the frontend.
    *
    * Written in PHP and used to generate the final HTML.
    *
    * @since 1.0.0
    * @access protected
    */
    protected function render() {
        $settings   = $this->get_settings_for_display();

        $post_type = $settings['post_types'] !== 'all' ? $settings['post_types'] : '';
        $paged     = wp_ulike_is_true( $settings['enable_pagination'] ) ? max( 1, get_query_var('paged'), get_query_var('page') ) : 1;

        // Update peroid limit value
        if( $settings['peroid_limit'] === 'past_days' ){
            $settings['peroid_limit'] = array(
                'interval_value' => $settings['past_days_num'],
                'interval_unit'  => 'DAY'
            );
        }

        $comments  = wp_ulike_get_most_liked_comments(
            $settings['number'],
            $post_type,
            $settings['peroid_limit'],
            array( 'like', 'dislike' ),
            $paged
        );

        $this->add_render_attribute( 'wrapper', 'class', 'wp-ulike-most-liked-widget wp-ulike-most-liked-comments-wrapper' );

        if( empty( $comments ) ){
            $output = sprintf( '<p>%s<p>', $settings['not_found_text'] );
        } else {
            // widget output -----------------------
            ob_start();
            foreach ( $comments as $comment ):
        ?>
            <div id="wp-ulike-comment-<?php echo esc_attr( $comment->comment_ID ); ?>" class="wp-ulike-comment wp-ulike-item">
                <?php if(  wp_ulike_is_true( $settings['display_title'] ) ) : ?>
                <div class="wp-ulike-entry-header">
                    <<?php echo $settings['title_tag']; ?> class="wp-ulike-entry-title">
                        <a href="<?php echo get_comment_link( $comment->comment_ID ); ?>" title="<?php echo esc_attr( get_the_title( $comment->comment_post_ID ) ); ?>">
                            <?php echo get_the_title( $comment->comment_post_ID ); ?>
                        </a>
                    </<?php echo $settings['title_tag']; ?>>
                </div>
                <?php endif; ?>
                <?php if( wp_ulike_is_true( $settings['display_content'] ) ) : ?>
                <div class="wp-ulike-entry-content">
                    <p><?php echo $comment->comment_content; ?></p>
                    <?php if( ! wp_ulike_is_true( $settings['display_title'] ) ) : ?>
                        <span class="wp-ulike-visit-url"><a href="<?php echo get_comment_link( $comment->comment_ID ); ?>"><?php echo sprintf( '<strong>%s</strong>', $settings['visit_text'] ); ?></a></span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <?php if( wp_ulike_is_true( $settings['display_info'] ) ) : ?>
                <div class="wp-ulike-entry-info">
                    <div class="wp-ulike-entry-date">
                        <?php
                        Icons_Manager::render_icon( $settings['date_icon'], [ 'aria-hidden' => 'true' ] );
                        echo human_time_diff( strtotime( $comment->comment_date_gmt ) ) . ' ' . $settings['ago_text'];
                        ?>
                    </div>
                    <div class="wp-ulike-entry-author">
                        <?php
                        Icons_Manager::render_icon( $settings['user_icon'], [ 'aria-hidden' => 'true' ] );
                        echo $settings['by_text']; ?> <?php echo esc_html( $comment->comment_author );
                        ?>
                    </div>
                    <div class="wp-ulike-entry-votes">
                        <?php
                        $is_distinct = \wp_ulike_setting_repo::isDistinct('comment');
                        $likes       = wp_ulike_get_counter_value( $comment->comment_ID, 'comment', 'like', $is_distinct  );
                        $dislikes    = wp_ulike_get_counter_value( $comment->comment_ID, 'comment', 'dislike', $is_distinct );

                        if( ! empty( $likes ) ){
                        ?>
                        <span class="wp-ulike-up-votes">
                        <?php
                            Icons_Manager::render_icon( $settings['like_icon'], [ 'aria-hidden' => 'true' ] );
                            echo $likes;
                        ?>
                        </span>
                        <?php
                        }
                        if( ! empty( $dislikes ) ){
                            ?>
                        <span class="wp-ulike-down-votes">
                        <?php
                            Icons_Manager::render_icon( $settings['dislike_icon'], [ 'aria-hidden' => 'true' ] );
                            echo $dislikes;
                        ?>
                        </span>
                        <?php
                        }
                        ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        <?php
            endforeach;
            $output = ob_get_clean();
        }

        // Pagination
        $pagination  = '';
        if( wp_ulike_is_true( $settings['enable_pagination'] ) ){
            $total_items = wp_ulike_get_popular_items_total_number(array(
                "type"     => 'comment',
                "rel_type" => '',
                "period"   => $settings['peroid_limit'],
                "status"   => array( 'like', 'dislike' )
            ));

            $pagination  = wp_ulike_pro_pagination( array(
                "total_pages" => $total_items,
                "per_page"    => $settings['number'],
                "prev_text"   => $settings['prev_text'],
                "next_text"   => $settings['next_text']
            ) );
        }

        echo sprintf( '<div %s>%s</div>%s', $this->get_render_attribute_string( 'wrapper' ), $output, $pagination );
    }

}
