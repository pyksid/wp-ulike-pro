<?php
/**
 * Profile Metaboxes
 *
 * 
 * @package    wp-ulike-pro
 * @author     TechnoWich 2025
 * @link       https://wpulike.com
*/

// no direct access allowed
if ( ! defined('ABSPATH') ) {
    die();
}

class WP_Ulike_Pro_Profile_Meta_Box {

    protected $option_domain = 'wp_ulike_pro_profile_meta_box';

    /**
     * __construct
     */
    function __construct() {
        add_action( 'ulf_loaded', array( $this, 'register_meta_panel' ) );
    }

    public function register_meta_panel(){

        // Check local avatar activation
        if( ! WP_Ulike_Pro_Options::isLocalAvatars() ){
            return;
        }

        // Create a comment metabox
        ULF::createProfileOptions( $this->option_domain, array(
            'data_type' => 'unserialize'
        ) );

        // General section
        ULF::createSection( $this->option_domain, array(
            'title'  => esc_html__('Avatar', WP_ULIKE_PRO_DOMAIN),
            'fields' => array(
                array(
                    'type'     => 'callback',
                    'function' => 'wp_ulike_pro_admin_avatar_box_callback'
                ),
                array(
                    'id'          => 'ulp_avatar_rating',
                    'type'        => 'radio',
                    'title'       => esc_html__('Rating', WP_ULIKE_PRO_DOMAIN),
                    'desc'        => esc_html__('If the local avatar is inappropriate for this site, Gravatar will be attempted.', WP_ULIKE_PRO_DOMAIN),
                    'options'     => array(
                        'G'  => esc_html__( 'G &#8212; Suitable for all audiences', WP_ULIKE_PRO_DOMAIN ),
                        'PG' => esc_html__( 'PG &#8212; Possibly offensive, usually for audiences 13 and above', WP_ULIKE_PRO_DOMAIN ),
                        'R'  => esc_html__( 'R &#8212; Intended for adult audiences above 17', WP_ULIKE_PRO_DOMAIN ),
                        'X'  => esc_html__( 'X &#8212; Even more mature than above', WP_ULIKE_PRO_DOMAIN )
                    ),
                    'default'     => 'G'
                )
            )
        ) );
    }

}
