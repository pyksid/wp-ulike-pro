<?php
/**
 * Comments Metaboxes
 *
 * 
 * @package    wp-ulike-pro
 * @author     TechnoWich 2024
 * @link       https://wpulike.com
*/

// no direct access allowed
if ( ! defined('ABSPATH') ) {
    die();
}

class WP_Ulike_Pro_Comment_Meta_Box {

    protected $option_domain = 'wp_ulike_pro_comment_meta_box';

    /**
     * __construct
     */
    function __construct() {
        add_action( 'ulf_loaded', array( $this, 'register_meta_panel' ) );
    }

    public function register_meta_panel(){

        // Create a comment metabox
        ULF::createCommentMetabox( $this->option_domain, array(
            'title' => WP_ULIKE_PRO_NAME . ' ' . esc_html__('Metabox Tools', WP_ULIKE_PRO_DOMAIN),
            'theme' => 'light wp-ulike-comment-metabox-panel'
        ) );


        // General section
        ULF::createSection( $this->option_domain, array(
            'title'  => esc_html__('General', WP_ULIKE_PRO_DOMAIN),
            'fields' => array(
                array(
                    'title' => esc_html__('Display Button', WP_ULIKE_PRO_DOMAIN),
                    'id'   => 'auto_display',
                    'desc' => esc_html__('Enable auto display if not activated in settings panel.', WP_ULIKE_PRO_DOMAIN),
                    'type' => 'switcher',
                ),
                array(
                    'title'      => esc_html__('Button Template', WP_ULIKE_PRO_DOMAIN),
                    'id'         => 'template',
                    'type'       => 'image_select',
                    'title'      => esc_html__( 'Select a Template',WP_ULIKE_PRO_DOMAIN),
                    'desc'       => sprintf( '%s <a target="_blank" href="%s" title="Click">%s</a>', esc_html__( 'Display online preview',WP_ULIKE_PRO_DOMAIN),  WP_ULIKE_PLUGIN_URI . 'templates/?utm_source=metabox-section&utm_campaign=plugin-uri&utm_medium=wp-dash',__( 'Here',WP_ULIKE_PRO_DOMAIN) ),
                    'options'    => $this->get_templates_option_array(),
                    'class'      => 'wp-ulike-visual-select',
                    'dependency' => array( 'auto_display', '==', 'true' ),
                ),
                array(
                    'title'             => esc_html__('Button Position', WP_ULIKE_PRO_DOMAIN),
                    'id'               => 'display_position',
                    'type'             => 'radio',
                    'options'          => array(
                        'top'        => esc_html__('Top of Content', WP_ULIKE_PRO_DOMAIN),
                        'bottom'     => esc_html__('Bottom of Content', WP_ULIKE_PRO_DOMAIN),
                        'top_bottom' => esc_html__('Top and Bottom', WP_ULIKE_PRO_DOMAIN)
                    ),
                    'dependency'  => array( 'auto_display', '==', 'true' ),
                ),
                array(
                    'title'       => esc_html__('Likes Counter Quantity', WP_ULIKE_PRO_DOMAIN),
                    'id'         => 'likes_counter_quantity',
                    'type'       => 'number',
                    'default'    => 0,
                    'unit'       => esc_html__('Likes', WP_ULIKE_PRO_DOMAIN),
                ),
                array(
                    'title'      => esc_html__('Dislikes Counter Quantity', WP_ULIKE_PRO_DOMAIN),
                    'id'         => 'dislikes_counter_quantity',
                    'type'       => 'number',
                    'default'    => 0,
                    'unit'       => esc_html__('Dislikes', WP_ULIKE_PRO_DOMAIN)
                )
            )
        ) );
    }

    /**
     * Get templates option array
     *
     * @return array
     */
    public function get_templates_option_array(){
        $options = wp_ulike_generate_templates_list();
        $output  = array();

        if( !empty( $options ) ){
            foreach ($options as $key => $args) {
                $output[$key] = $args['symbol'];
            }
        }

        return $output;
    }

}
