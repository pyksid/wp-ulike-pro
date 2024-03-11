<?php
namespace WpUlikePro\Includes\Elementor\Modules;

use Elementor\Plugin;
use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Image_Size;
use Elementor\Group_Control_Typography;
use Elementor\Control_Media;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Background;


class Common {

    /**
     * Instance of this class.
     *
     * @var      object
     */
    protected static $instance = null;


    function __construct(){
        add_action( "elementor/element/after_section_end", array( $this, 'add_custom_css_controls_section'), 25, 3 );
        // Render the custom CSS
        if ( ! defined('ELEMENTOR_PRO_VERSION') && ! defined('THEME_PRO' ) ) {
            add_action( 'elementor/element/parse_css', array( $this, 'add_post_css' ), 10, 2 );
        }
    }

    /**
     * Return an instance of this class.
     *
     * @return    object    A single instance of this class.
     */
    public static function get_instance() {
        // If the single instance hasn't been set, set it now.
        if ( null == self::$instance ) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * Add custom css control to all elements
     *
     * @return void
     */
    public function add_custom_css_controls_section( $widget, $section_id, $args ){

        if( 'section_custom_css_pro' !== $section_id ){
            return;
        }

        if( ! defined('ELEMENTOR_PRO_VERSION') && ! defined('THEME_PRO' ) ) {

            $widget->start_controls_section(
                'wp_ulike_core_common_custom_css_section',
                array(
                    'label'     => WP_ULIKE_PRO_NAME . ' - ' .esc_html__( 'Custom CSS', WP_ULIKE_PRO_NAME ),
                    'tab'       => Controls_Manager::TAB_ADVANCED
                )
            );

            $widget->add_control(
                'custom_css',
                array(
                    'type'        => Controls_Manager::CODE,
                    'label'       => esc_html__( 'Custom CSS', WP_ULIKE_PRO_NAME ),
                    'label_block' => true,
                    'language'    => 'css'
                )
            );

            $widget->add_control(
                'custom_css_description',
                array(
                    'raw'             => __( 'Use "selector" to target wrapper element. Examples:<br>selector {color: red;} // For main element<br>selector .child-element {margin: 10px;} // For child element<br>.my-class {text-align: center;} // Or use any custom selector', WP_ULIKE_PRO_NAME ),
                    'type'            => Controls_Manager::RAW_HTML,
                    'content_classes' => 'elementor-descriptor',
                )
            );

            $widget->end_controls_section();
        }

    }

    /**
     * Retrives the setting value or checkes whether the setting value
     * mathes with a value or not
     *
     * @param  array  $settings Settings in an array
     * @param  string $key      The setting key
     * @param  string $value    An optional value to compare with the setting value
     *
     * @return mixed           Setting value or a boolean value
     */
    private function setting_value( $settings, $key, $value = null ){
        if( ! isset( $settings[ $key ] ) ){
            return;
        }
        // Retrieves the setting value
        if( is_null( $value ) ){
            return $settings[ $key ];
        }
        // Validates the setting value
        return ! empty( $settings[ $key ] ) && $value == $settings[ $key ];
    }

    /**
     * Render Custom CSS for an Elementor Element
     *
     * @param $post_css Post_CSS_File
     * @param $element Element_Base
     */
    public function add_post_css( $post_css, $element ) {
        $element_settings = $element->get_settings();

        if ( empty( $element_settings['custom_css'] ) ) {
            return;
        }

        $css = trim( $element_settings['custom_css'] );

        if ( empty( $css ) ) {
            return;
        }
        $css = str_replace( 'selector', $post_css->get_element_unique_selector( $element ), $css );

        // Add a css comment
        $css = sprintf( '/* Start custom CSS for %s, class: %s */', $element->get_name(), $element->get_unique_selector() ) . $css . '/* End custom CSS */';

        $post_css->get_stylesheet()->add_raw_css( $css );
    }

}
