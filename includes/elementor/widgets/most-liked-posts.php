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
use Elementor\Group_Control_Css_Filter;
use Elementor\Icons_Manager;
use WpUlikePro\Includes\Elementor\Modules\QueryControl\Controls\Group_Control_Related;
use WpUlikePro\Includes\Elementor\Modules\QueryControl\Module as Module_Query;


if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly.
}

/**
 * Elementor 'MostLikedPosts' widget.
 *
 * Elementor widget that displays an 'MostLikedPosts' with lightbox.
 *
 * @since 1.0.0
 */
class MostLikedPosts extends Widget_Base {

    /**
     * Get widget name.
     *
     * Retrieve 'MostLikedPosts' widget name.
     *
     * @since 1.0.0
     * @access public
     *
     * @return string Widget name.
     */
    public function get_name() {
        return 'wp_ulike_top_posts';
    }

    /**
     * Get widget title.
     *
     * Retrieve 'MostLikedPosts' widget title.
     *
     * @since 1.0.0
     * @access public
     *
     * @return string Widget title.
     */
    public function get_title() {
        return esc_html__('Top Posts', WP_ULIKE_PRO_DOMAIN );
    }

    /**
     * Get widget icon.
     *
     * Retrieve 'MostLikedPosts' widget icon.
     *
     * @since 1.0.0
     * @access public
     *
     * @return string Widget icon.
     */
    public function get_icon() {
        return 'eicon-posts-grid';
    }

    /**
     * Get widget categories.
     *
     * Retrieve 'MostLikedPosts' widget icon.
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
     * Register 'MostLikedPosts' widget controls.
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

        $this->add_responsive_control(
            'columns',
            array(
                'label' => esc_html__( 'Columns', WP_ULIKE_PRO_DOMAIN ),
                'type' => Controls_Manager::SELECT,
                'default' => '3',
                'tablet_default' => '2',
                'mobile_default' => '1',
                'options' => array(
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                    '5' => '5',
                    '6' => '6',
                ),
                'prefix_class' => 'elementor-grid%s-',
                'frontend_available' => true,
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
            'display_thumbnail',
            array(
                'label'        => esc_html__('Display thumbnail',WP_ULIKE_PRO_DOMAIN ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__( 'On', WP_ULIKE_PRO_DOMAIN ),
                'label_off'    => esc_html__( 'Off', WP_ULIKE_PRO_DOMAIN ),
                'return_value' => 'yes',
                'default'      => 'yes'
            )
        );

		$this->add_group_control(
			Group_Control_Image_Size::get_type(),
			array(
				'name' => 'thumbnail_size',
				'default' => 'medium',
				'exclude' => array( 'custom' ),
                'condition' => array(
                    'display_thumbnail' => 'yes'
                )
			)
        );

		$this->add_responsive_control(
			'item_ratio',
			array(
				'label' => esc_html__( 'Image Ratio', WP_ULIKE_PRO_DOMAIN ),
				'type' => Controls_Manager::SLIDER,
				'range' => array(
					'px' => array(
						'min' => 0.1,
						'max' => 2,
						'step' => 0.01,
					),
				),
				'selectors' => array(
					'{{WRAPPER}} .wp-ulike-post-thumbnail' => 'padding-bottom: calc( {{SIZE}} * 100% );',
					'{{WRAPPER}}:after' => 'content: "{{SIZE}}"; position: absolute; color: transparent;',
				),
                'condition' => array(
                    'display_thumbnail' => 'yes'
                )
			)
		);

		$this->add_responsive_control(
			'image_width',
			array(
				'label' => esc_html__( 'Image Width', WP_ULIKE_PRO_DOMAIN ),
				'type' => Controls_Manager::SLIDER,
				'range' => array(
					'%' => array(
						'min' => 10,
						'max' => 100,
					),
					'px' => array(
						'min' => 10,
						'max' => 600,
					),
				),
				'default' => array(
					'size' => 100,
					'unit' => '%',
				),
				'tablet_default' => array(
					'size' => '',
					'unit' => '%',
				),
				'mobile_default' => array(
					'size' => 100,
					'unit' => '%',
				),
				'size_units' => array( '%', 'px' ),
				'selectors' => array(
					'{{WRAPPER}} .wp-ulike-post-thumbnail-link' => 'width: {{SIZE}}{{UNIT}};',
				),
                'condition' => array(
                    'display_thumbnail' => 'yes'
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
                'label'        => esc_html__('Display content', WP_ULIKE_PRO_DOMAIN ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__( 'On', WP_ULIKE_PRO_DOMAIN ),
                'label_off'    => esc_html__( 'Off', WP_ULIKE_PRO_DOMAIN ),
                'return_value' => 'yes',
                'default'      => 'yes'
            )
        );

		$this->add_control(
			'content_length',
			array(
				'label' => esc_html__( 'Content Length', WP_ULIKE_PRO_DOMAIN ),
				'type' => Controls_Manager::NUMBER,
				/** This filter is documented in wp-includes/formatting.php */
				'default' => apply_filters( 'excerpt_length', 25 ),
                'condition' => array(
                    'display_content' => 'yes'
                )
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

        $this->add_control(
            'display_meta_date',
            array(
                'label'        => esc_html__('Display Date',WP_ULIKE_PRO_DOMAIN ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__( 'On', WP_ULIKE_PRO_DOMAIN ),
                'label_off'    => esc_html__( 'Off', WP_ULIKE_PRO_DOMAIN ),
                'return_value' => 'yes',
                'default'      => 'yes',
                'condition' => array(
                    'display_info' => 'yes'
                )
            )
        );


        $this->add_control(
            'display_human_time_diff',
            array(
                'label'        => esc_html__('Display Human Time Format',WP_ULIKE_PRO_DOMAIN ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__( 'On', WP_ULIKE_PRO_DOMAIN ),
                'label_off'    => esc_html__( 'Off', WP_ULIKE_PRO_DOMAIN ),
                'return_value' => 'yes',
                'default'      => 'yes',
                'condition' => array(
                    'display_meta_date' => 'yes'
                )
            )
        );

        $this->add_control(
            'display_meta_author',
            array(
                'label'        => esc_html__('Display Author',WP_ULIKE_PRO_DOMAIN ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__( 'On', WP_ULIKE_PRO_DOMAIN ),
                'label_off'    => esc_html__( 'Off', WP_ULIKE_PRO_DOMAIN ),
                'return_value' => 'yes',
                'default'      => 'yes',
                'condition' => array(
                    'display_info' => 'yes'
                )
            )
        );

        $this->add_control(
            'display_meta_vote',
            array(
                'label'        => esc_html__('Display Votes',WP_ULIKE_PRO_DOMAIN ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__( 'On', WP_ULIKE_PRO_DOMAIN ),
                'label_off'    => esc_html__( 'Off', WP_ULIKE_PRO_DOMAIN ),
                'return_value' => 'yes',
                'default'      => 'yes',
                'condition' => array(
                    'display_info' => 'yes'
                )
            )
        );

        $this->add_control(
            'display_readmore',
            array(
                'label'        => esc_html__('Display readmore',WP_ULIKE_PRO_DOMAIN ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__( 'On', WP_ULIKE_PRO_DOMAIN ),
                'label_off'    => esc_html__( 'Off', WP_ULIKE_PRO_DOMAIN ),
                'return_value' => 'yes',
                'default'      => 'yes'
            )
        );

        $this->add_control(
            'display_button',
            array(
                'label'        => esc_html__('Display CTA Button',WP_ULIKE_PRO_DOMAIN ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__( 'On', WP_ULIKE_PRO_DOMAIN ),
                'label_off'    => esc_html__( 'Off', WP_ULIKE_PRO_DOMAIN ),
                'return_value' => 'yes',
                'default'      => 'no'
            )
        );

        $this->add_control(
            'style',
            array(
                'label'       => esc_html__('Template', WP_ULIKE_PRO_DOMAIN),
                'type'        => Controls_Manager::SELECT,
                'default'     => 'wpulike-default',
                'options'     => wp_ulike_pro_get_templates_list_by_name(),
                'condition'    => array(
                    'display_button' => 'yes',
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
                'default'      => 'yes',
                'condition'    => array(
                    'display_button' => 'yes',
                )
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
                'default'      => 'no',
                'condition'    => array(
                    'display_button' => 'yes',
                )
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
                'condition'    => array(
                    'display_likers' => 'yes',
                )
            )
        );

        $this->end_controls_section();

        /*-----------------------------------------------------------------------------------*/
        /*  query_section
        /*-----------------------------------------------------------------------------------*/

        $this->start_controls_section(
            'query_section',
            array(
                'label'      => esc_html__('Query', WP_ULIKE_PRO_DOMAIN ),
            )
        );

		$this->add_group_control(
			Group_Control_Related::get_type(),
			array(
				'name' => 'posts',
				'presets' => array( 'full' ),
				'exclude' => array(
					'', //use the one from Layout section
				),
			)
        );

        $this->add_control(
            'status',
            array(
                'label'    => esc_html__( 'Select Status', WP_ULIKE_PRO_DOMAIN ),
                'type'     => Controls_Manager::SELECT2,
                'default'  => array( 'like', 'dislike' ),
                'multiple' => true,
                'options'  => array(
                    'like'    => esc_html__('Like', WP_ULIKE_PRO_DOMAIN ),
                    'dislike' => esc_html__('Dislike', WP_ULIKE_PRO_DOMAIN )
                )
            )
        );


        $this->add_control(
            'force_default_query',
            array(
                'label'        => esc_html__('Force Default Query',WP_ULIKE_PRO_DOMAIN ),
                'description'  => esc_html__('Force default query results without dependence on ulike results.',WP_ULIKE_PRO_DOMAIN ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__( 'On', WP_ULIKE_PRO_DOMAIN ),
                'label_off'    => esc_html__( 'Off', WP_ULIKE_PRO_DOMAIN ),
                'return_value' => 'yes',
                'default'      => 'no'
            )
        );

        $this->add_control(
            'is_normal',
            array(
                'label'        => esc_html__('Enable Normal Query',WP_ULIKE_PRO_DOMAIN ),
                'description'  => esc_html__('Display posts even if they have no log. (like/dislike)',WP_ULIKE_PRO_DOMAIN ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__( 'On', WP_ULIKE_PRO_DOMAIN ),
                'label_off'    => esc_html__( 'Off', WP_ULIKE_PRO_DOMAIN ),
                'return_value' => 'yes',
                'default'      => 'no'
            )
        );

        $this->end_controls_section();

        /*-----------------------------------------------------------------------------------*/
        /*  custom_content
        /*-----------------------------------------------------------------------------------*/

        $this->start_controls_section(
            'custom_content',
            array(
                'label' => esc_html__('Custom Content', WP_ULIKE_PRO_DOMAIN ),
            )
        );

		$this->add_control(
			'not_found_text',
			array(
				'label'       => esc_html__( 'Not Found Text', WP_ULIKE_PRO_DOMAIN),
				'type'        => Controls_Manager::TEXT,
				'default'     => 'No post found!',
				'placeholder' => esc_html__( 'Type your text here', WP_ULIKE_PRO_DOMAIN ),
			)
        );

		$this->add_control(
			'by_text',
			array(
				'label'       => esc_html__( 'By Text', WP_ULIKE_PRO_DOMAIN),
				'type'        => Controls_Manager::TEXT,
				'default'     => 'By',
				'placeholder' => esc_html__( 'Type your text here', WP_ULIKE_PRO_DOMAIN ),
			)
        );

		$this->add_control(
			'ago_text',
			array(
				'label'       => esc_html__( 'Ago Text', WP_ULIKE_PRO_DOMAIN),
				'type'        => Controls_Manager::TEXT,
				'default'     => 'Ago',
				'placeholder' => esc_html__( 'Type your text here', WP_ULIKE_PRO_DOMAIN ),
			)
        );

		$this->add_control(
			'readmore_text',
			array(
				'label'       => esc_html__( 'Readmore Text', WP_ULIKE_PRO_DOMAIN),
				'type'        => Controls_Manager::TEXT,
				'default'     => 'Read More »',
				'placeholder' => esc_html__( 'Type your text here', WP_ULIKE_PRO_DOMAIN ),
			)
        );

        $this->add_control(
            'custom_heading_content',
            array(
                'label'       => esc_html__('Custom Heading Content',WP_ULIKE_PRO_DOMAIN ),
                'description' => esc_html__('A Custom HTML structure where you can display it at the item box wrapper. (This option also supports shortcode)',WP_ULIKE_PRO_DOMAIN ),
                'type'        => Controls_Manager::CODE,
                'language'    => 'html',
                'rows'        => 20
            )
        );


        $this->add_control(
            'custom_inner_content',
            array(
                'label'       => esc_html__('Custom Inner Content',WP_ULIKE_PRO_DOMAIN ),
                'description' => esc_html__('A Custom HTML structure where you can display it at the item box wrapper. (This option also supports shortcode)',WP_ULIKE_PRO_DOMAIN ),
                'type'        => Controls_Manager::CODE,
                'language'    => 'html',
                'rows'        => 20
            )
        );

        $this->add_control(
            'custom_footer_content',
            array(
                'label'       => esc_html__('Custom Footer Content',WP_ULIKE_PRO_DOMAIN ),
                'description' => esc_html__('A Custom HTML structure where you can display it at the item box wrapper. (This option also supports shortcode)',WP_ULIKE_PRO_DOMAIN ),
                'type'        => Controls_Manager::CODE,
                'language'    => 'html',
                'rows'        => 20
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
        /*  layout_style_section
        /*-----------------------------------------------------------------------------------*/

		$this->start_controls_section(
			'layout_style_section',
			array(
				'label' => esc_html__( 'Layout', WP_ULIKE_PRO_DOMAIN ),
				'tab' => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'column_gap',
			array(
				'label' => esc_html__( 'Columns Gap', WP_ULIKE_PRO_DOMAIN ),
				'type' => Controls_Manager::SLIDER,
				'default' => array(
					'size' => 30,
				),
				'range' => array(
					'px' => array(
						'min' => 0,
						'max' => 100,
					),
				),
				'selectors' => array(
					'{{WRAPPER}} .wp-ulike-posts-container' => 'grid-column-gap: {{SIZE}}{{UNIT}}',
					'.elementor-msie {{WRAPPER}} .wp-ulike-top-item' => 'padding-right: calc( {{SIZE}}{{UNIT}}/2 ); padding-left: calc( {{SIZE}}{{UNIT}}/2 );',
					'.elementor-msie {{WRAPPER}} .wp-ulike-posts-container' => 'margin-left: calc( -{{SIZE}}{{UNIT}}/2 ); margin-right: calc( -{{SIZE}}{{UNIT}}/2 );',
				),
			)
		);

		$this->add_control(
			'row_gap',
			array(
				'label' => esc_html__( 'Rows Gap', WP_ULIKE_PRO_DOMAIN ),
				'type' => Controls_Manager::SLIDER,
				'default' => array(
					'size' => 35,
				),
				'range' => array(
					'px' => array(
						'min' => 0,
						'max' => 100,
					),
				),
				'frontend_available' => true,
				'selectors' => array(
					'{{WRAPPER}} .wp-ulike-posts-container' => 'grid-row-gap: {{SIZE}}{{UNIT}}',
					'.elementor-msie {{WRAPPER}} .wp-ulike-top-item' => 'padding-bottom: {{SIZE}}{{UNIT}};',
				),
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
        /*  item_style_section
        /*-----------------------------------------------------------------------------------*/

        $this->start_controls_section(
            'box_style_section',
            array(
                'label'     => esc_html__( 'Box', WP_ULIKE_PRO_DOMAIN ),
                'tab'       => Controls_Manager::TAB_STYLE
            )
        );

        $this->add_responsive_control(
            'item_padding',
            array(
                'label'      => esc_html__( 'Padding', WP_ULIKE_PRO_DOMAIN ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => array( 'px' ),
                'selectors'  => array(
                    '{{WRAPPER}} .wp-ulike-top-item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                )
            )
        );

        $this->add_responsive_control(
            'item_content_padding',
            array(
                'label'      => esc_html__( 'Content Padding', WP_ULIKE_PRO_DOMAIN ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => array( 'px' ),
                'selectors'  => array(
                    '{{WRAPPER}} .wp-ulike-entry-inner-content' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
                    '{{WRAPPER}} .wp-ulike-top-item' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                )
            )
        );

		$this->start_controls_tabs( 'box_tabs' );

		$this->start_controls_tab( 'box_normal',
			array(
				'label' => esc_html__( 'Normal', WP_ULIKE_PRO_DOMAIN ),
			)
        );

        $this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name' => 'item_border',
				'label' => esc_html__( '', WP_ULIKE_PRO_DOMAIN ),
				'selector' => '{{WRAPPER}} .wp-ulike-top-item',
			)
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            array(
                'name'      => 'item_shadow',
                'selector'  => '{{WRAPPER}} .wp-ulike-top-item'
            )
        );

        $this->add_control(
            'item_bg_color',
            array(
                'label' => esc_html__( 'Background', WP_ULIKE_PRO_DOMAIN ),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .wp-ulike-top-item' => 'background-color: {{VALUE}};',
                )
            )
        );

		$this->end_controls_tab();

		$this->start_controls_tab( 'box_hover',
			array(
				'label' => esc_html__( 'Hover', WP_ULIKE_PRO_DOMAIN ),
			)
        );

        $this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name' => 'item_border:hover',
				'label' => esc_html__( '', WP_ULIKE_PRO_DOMAIN ),
				'selector' => '{{WRAPPER}} .wp-ulike-top-item:hover',
			)
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            array(
                'name'      => 'item_hover_shadow',
                'selector'  => '{{WRAPPER}} .wp-ulike-top-item:hover'
            )
        );

        $this->add_control(
            'item_hover_bg_color',
            array(
                'label' => esc_html__( 'Background', WP_ULIKE_PRO_DOMAIN ),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .wp-ulike-top-item:hover' => 'background-color: {{VALUE}};',
                )
            )
        );

		$this->end_controls_tab();

		$this->end_controls_tabs();

        $this->end_controls_section();

        /*-----------------------------------------------------------------------------------*/
        /*  thumbnail_style_section
        /*-----------------------------------------------------------------------------------*/

		$this->start_controls_section(
			'thumbnail_style_section',
			array(
				'label' => esc_html__( 'Image', WP_ULIKE_PRO_DOMAIN ),
				'tab' => Controls_Manager::TAB_STYLE,
                'condition' => array(
                    'display_thumbnail' => 'yes'
                )
			)
		);

		$this->add_control(
			'img_border_radius',
			array(
				'label' => esc_html__( 'Border Radius', WP_ULIKE_PRO_DOMAIN ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors' => array(
					'{{WRAPPER}} .wp-ulike-post-thumbnail' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				)
			)
		);

		$this->add_control(
			'image_spacing',
			array(
				'label' => esc_html__( 'Spacing', WP_ULIKE_PRO_DOMAIN ),
				'type' => Controls_Manager::SLIDER,
				'range' => array(
					'px' => array(
						'max' => 100,
					),
				),
				'selectors' => array(
					'{{WRAPPER}} .wp-ulike-post-thumbnail' => 'margin-bottom: {{SIZE}}{{UNIT}}',
				),
				'default' => array(
					'size' => 20,
				)
			)
		);

		$this->start_controls_tabs( 'thumbnail_effects_tabs' );

		$this->start_controls_tab( 'normal',
			array(
				'label' => esc_html__( 'Normal', WP_ULIKE_PRO_DOMAIN ),
			)
		);

		$this->add_group_control(
			Group_Control_Css_Filter::get_type(),
			array(
				'name' => 'thumbnail_filters',
				'selector' => '{{WRAPPER}} .wp-ulike-post-thumbnail img',
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab( 'hover',
			array(
				'label' => esc_html__( 'Hover', WP_ULIKE_PRO_DOMAIN ),
			)
		);

		$this->add_group_control(
			Group_Control_Css_Filter::get_type(),
			array(
				'name' => 'thumbnail_hover_filters',
				'selector' => '{{WRAPPER}} .elementor-post:hover .wp-ulike-post-thumbnail img',
			)
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->end_controls_section();

        /*-----------------------------------------------------------------------------------*/
        /*  content_style_section
        /*-----------------------------------------------------------------------------------*/

        $this->start_controls_section(
            'content_style_section',
            array(
                'label'     => esc_html__( 'Content', WP_ULIKE_PRO_DOMAIN ),
                'tab'       => Controls_Manager::TAB_STYLE
            )
        );

		$this->add_control(
			'title_heading',
			array(
				'label' => esc_html__( 'Title', WP_ULIKE_PRO_DOMAIN ),
				'type' => Controls_Manager::HEADING,
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

		$this->add_control(
			'expert_heading',
			array(
				'label' => esc_html__( 'Excerpt', WP_ULIKE_PRO_DOMAIN ),
				'type' => Controls_Manager::HEADING,
				'separator' => 'before',
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

		$this->add_control(
			'meta_heading',
			array(
				'label' => esc_html__( 'Meta', WP_ULIKE_PRO_DOMAIN ),
				'type' => Controls_Manager::HEADING,
				'separator' => 'before',
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
                    '{{WRAPPER}} .wp-ulike-most-liked-widget .wp-ulike-entry-info > div::after' => 'background-color: {{VALUE}};'
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
                    '{{WRAPPER}} .wp-ulike-most-liked-widget .wp-ulike-entry-info > div:hover::after' => 'background-color: {{VALUE}};'
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

		$this->add_control(
			'read_more_heading',
			array(
				'label' => esc_html__( 'Read More', WP_ULIKE_PRO_DOMAIN ),
				'type' => Controls_Manager::HEADING,
				'separator' => 'before',
            )
        );

        $this->start_controls_tabs( 'readmore_colors' );

        $this->start_controls_tab(
            'readmore_color_normal',
            array(
                'label' => esc_html__( 'Normal' , WP_ULIKE_PRO_DOMAIN )
            )
        );

        $this->add_control(
            'readmore_color',
            array(
                'label' => esc_html__( 'Color', WP_ULIKE_PRO_DOMAIN ),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .wp-ulike-entry-footer a' => 'color: {{VALUE}};',
                )
            )
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'readmore_color_hover',
            array(
                'label' => esc_html__( 'Hover' , WP_ULIKE_PRO_DOMAIN )
            )
        );

        $this->add_control(
            'readmore_hover_color',
            array(
                'label' => esc_html__( 'Color', WP_ULIKE_PRO_DOMAIN ),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .wp-ulike-entry-footer a:hover' => 'color: {{VALUE}};',
                )
            )
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name' => 'readmore_typography',
                'scheme' => '1',
                'selector' => '{{WRAPPER}} .wp-ulike-entry-footer'
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
    * Render recentposts widget output on the frontend.
    *
    * Written in PHP and used to generate the final HTML.
    *
    * @since 1.0.0
    * @access protected
    */
    protected function render() {
        $settings   = $this->get_settings_for_display();

        $wrapper_classes = 'wp-ulike-most-liked-widget wp-ulike-most-liked-posts-wrapper wp-ulike-posts-container elementor-grid';

        if( !empty( $settings['item_ratio']['size'] ) ){
            $wrapper_classes .= ' wp-ulike-has-item-ratio';
        }

        $this->add_render_attribute( 'wrapper', 'class', $wrapper_classes );

        $paged = wp_ulike_is_true( $settings['enable_pagination'] ) ? max( 1, get_query_var('paged'), get_query_var('page') ) : 1;

        // Check deprecated option value
        if( ! isset( $settings['likers_style'] ) && isset( $settings['disable_pophover'] ) ){
            $settings['likers_style'] = wp_ulike_is_true( $settings['disable_pophover'] ) ? 'default' : 'popover';
        }

        // Set default args
        $myargs = array(
            'posts_per_page' => $settings['posts_posts_per_page'],
            'paged'          => $paged
        );
        // Check have posts
        $have_posts = false;
        // Check normal query
        if( ! wp_ulike_is_true( $settings['force_default_query'] ) && ! wp_ulike_is_true( $settings['is_normal'] ) ){
            // Update peroid limit value
            if( $settings['peroid_limit'] === 'past_days' ){
                $settings['peroid_limit'] = array(
                    'interval_value' => $settings['past_days_num'],
                    'interval_unit'  => 'DAY'
                );
            }

            $post__in = wp_ulike_get_popular_items_ids( array(
                "rel_type" => $settings['posts_post_type'],
                "period"   => $settings['peroid_limit'],
                "status"   => ! empty($settings['status']) ? $settings['status'] : array( 'like', 'dislike'),
                "limit"    => -1
            ) );

            if( ! empty( $post__in ) ){
                $myargs['post__in'] = $post__in;
                $myargs['orderby']  = 'post__in';
            } else {
                $myargs = false;
            }
        }

        if( $myargs !== false ){
            $elementor_query = Module_Query::instance();
            $wp_query   = $elementor_query->get_query( $this, 'posts', $myargs, [] );
            $have_posts = $wp_query->have_posts();
        }

        if( ! $have_posts ){
            $output = sprintf( '<p>%s<p>', $settings['not_found_text'] );
        } else {
            // widget output -----------------------
            ob_start();
            while ( $wp_query->have_posts() ):
                $wp_query->the_post();
                get_post();
            ?>
            <article <?php post_class( 'wp-ulike-top-item elementor-grid-item' ); ?>>
                <?php if( ! empty( $settings['custom_heading_content'] ) ) : ?>
                    <div class="wp-ulike-entry-heading">
                    <?php echo do_shortcode( $settings['custom_heading_content'] ); ?>
                    </div>
                <?php endif; ?>
                <?php do_action( 'wp_ulike_pro/elementor_widget/before_thumbnail', $this->get_name() ); ?>
                <?php if(  wp_ulike_is_true( $settings['display_thumbnail'] ) && has_post_thumbnail() ) : ?>
                <div class="wp-ulike-entry-media">
                    <?php
                    $settings['thumbnail_size'] = [
                        'id' => get_post_thumbnail_id(),
                    ];
                    $thumbnail_html = Group_Control_Image_Size::get_attachment_image_html( $settings, 'thumbnail_size' );
                    ?>
                    <a class="wp-ulike-post-thumbnail-link" href="<?php echo get_permalink(); ?>">
                        <div class="wp-ulike-post-thumbnail"><?php echo $thumbnail_html; ?></div>
                    </a>
                </div>
                <?php endif; ?>
                <div class="wp-ulike-entry-inner-content">
                    <?php do_action( 'wp_ulike_pro/elementor_widget/before_title', $this->get_name() ); ?>
                    <?php if(  wp_ulike_is_true( $settings['display_title'] ) ) : ?>
                    <div class="wp-ulike-entry-header">
                        <<?php echo $settings['title_tag']; ?> class="wp-ulike-entry-title">
                            <a href="<?php echo get_permalink(); ?>" title="<?php the_title_attribute(); ?>">
                                <?php echo get_the_title(); ?>
                            </a>
                        </<?php echo $settings['title_tag']; ?>>
                    </div>
                    <?php endif; ?>
                    <?php do_action( 'wp_ulike_pro/elementor_widget/before_info', $this->get_name() ); ?>
                    <?php if( wp_ulike_is_true( $settings['display_info'] ) ) : ?>
                    <div class="wp-ulike-entry-info">
                        <?php if( wp_ulike_is_true( $settings['display_meta_date'] ) ) : ?>
                        <div class="wp-ulike-entry-date">
                            <?php
                            Icons_Manager::render_icon( $settings['date_icon'], [ 'aria-hidden' => 'true' ] );
                            $get_the_date = get_the_modified_time( 'U' );
                            $get_the_date = ! $get_the_date ? get_the_time( 'U' ) : $get_the_date;
                            if( wp_ulike_is_true( $settings['display_human_time_diff'] ) ){
                                echo human_time_diff( $get_the_date ) . ' ' . $settings['ago_text'];
                            } else {
                                echo wp_date( get_option('date_format'), $get_the_date );
                            }
                            ?>
                        </div>
                        <?php endif; ?>
                        <?php if( wp_ulike_is_true( $settings['display_meta_author'] ) ) : ?>
                        <div class="wp-ulike-entry-author">
                            <?php
                            Icons_Manager::render_icon( $settings['user_icon'], [ 'aria-hidden' => 'true' ] );
                            echo $settings['by_text']; ?> <?php echo esc_html( get_the_author_meta('nickname') );
                            ?>
                        </div>
                        <?php endif; ?>
                        <?php if( wp_ulike_is_true( $settings['display_meta_vote'] ) ) : ?>
                        <div class="wp-ulike-entry-votes">
                            <?php
                            $is_distinct = \wp_ulike_setting_repo::isDistinct('post');
                            $likes       = wp_ulike_get_counter_value( wp_ulike_get_the_id(), 'post', 'like', $is_distinct  );
                            $dislikes    = wp_ulike_get_counter_value( wp_ulike_get_the_id(), 'post', 'dislike', $is_distinct );

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
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <?php do_action( 'wp_ulike_pro/elementor_widget/before_content', $this->get_name() ); ?>
                    <?php if( wp_ulike_is_true( $settings['display_content'] ) ) : ?>
                    <div class="wp-ulike-entry-content">
                        <?php echo wp_trim_words( get_the_content(), $settings['content_length'], '...' ); ?>
                    </div>
                    <?php endif; ?>
                    <?php do_action( 'wp_ulike_pro/elementor_widget/before_button', $this->get_name() ); ?>
                    <?php if( wp_ulike_is_true( $settings['display_button'] ) ) : ?>
                    <div class="wp-ulike-cta-button">
                        <?php
                            //Main data
                            $defaults =  array(
                                "display_likers" => wp_ulike_is_true( $settings['display_likers'] ),
                                "likers_style"   => $settings['likers_style'],
                                "style"          => $settings['style'],
                                "button_type"    => 'image',
                                "wrapper_class"  => !wp_ulike_is_true($settings['display_counter']) ? ' wpulike-hide-counter' : '',
                            );
                            echo wp_ulike( 'get', $defaults );
                        ?>
                    </div>
                    <?php endif; ?>
                    <?php do_action( 'wp_ulike_pro/elementor_widget/before_footer', $this->get_name() ); ?>
                    <?php if( ! empty( $settings['custom_inner_content'] ) ) : ?>
                        <div class="wp-ulike-entry-custom-content">
                        <?php echo do_shortcode( $settings['custom_inner_content'] ); ?>
                        </div>
                    <?php endif; ?>
                    <?php if( wp_ulike_is_true( $settings['display_readmore'] ) ) : ?>
                    <div class="wp-ulike-entry-footer">
                        <a href="<?php echo get_permalink(); ?>" title="<?php echo esc_attr( $settings['readmore_text'] ) ?>">
                            <?php echo $settings['readmore_text']; ?>
                        </a>
                    </div>
                    <?php endif; ?>
                    <?php do_action( 'wp_ulike_pro/elementor_widget/after_footer', $this->get_name() ); ?>
                </div>
                <?php if( ! empty( $settings['custom_footer_content'] ) ) : ?>
                    <div class="wp-ulike-entry-footer">
                    <?php echo do_shortcode( $settings['custom_footer_content'] ); ?>
                    </div>
                <?php endif; ?>
            </article>
            <?php
            endwhile;

            wp_reset_postdata();
            $output = ob_get_clean();
        }

        // Pagination
        $pagination  = '';
        if( wp_ulike_is_true( $settings['enable_pagination'] ) ){
            // Set total items
            $total_items = '';
            // Check conditions
            if( ! in_array( $settings['posts_post_type'], array( 'current_query' ) ) ){
                if( wp_ulike_is_true( $settings['force_default_query'] ) || wp_ulike_is_true( $settings['is_normal'] ) ){
                    $total_items = ! empty( $wp_query->found_posts ) ? $wp_query->found_posts  : '';
                } else {
                    $total_items = wp_ulike_get_popular_items_total_number(array(
                        "rel_type" => $settings['posts_post_type'],
                        "period"   => $settings['peroid_limit'],
                        "status"   => ! empty($settings['status']) ? $settings['status'] : array( 'like', 'dislike'),
                    ));
                }
            }

            $pagination  = wp_ulike_pro_pagination( array(
                "total_pages" => $total_items,
                "per_page"    => $settings['posts_posts_per_page'],
                "prev_text"   => $settings['prev_text'],
                "next_text"   => $settings['next_text']
            ) );
        }

        echo sprintf( '<div %s>%s</div>%s', $this->get_render_attribute_string( 'wrapper' ), $output, $pagination );
    }

}
