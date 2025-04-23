<?php
/**
 * General Hooks
 *
 * 
 * @package    wp-ulike-pro
 * @author     TechnoWich 2025
 * @link       https://wpulike.com
 */


/**
 * Generate schema markup base on metabox options for singular pages.
 *
 * @return void
 */
function wp_ulike_pro_generate_schema(){

    if( is_singular() && ! WP_Ulike_Pro::is_preview_mode() ){
        // Get schema generator class
        $schema = new WP_Ulike_Pro_Schema_Generator( wp_ulike_get_the_id() );
        // Auto schema generator
        if( wp_ulike_is_true( wp_ulike_pro_get_metabox_value( 'enable_schema' ) ) ){
            $schema_type = wp_ulike_pro_get_metabox_value( 'schema_type' );
            if( !empty( $schema_type ) ){
                $schema->generateAutoSchema( $schema_type );
            }
        }
        // Generate FAQ Schema
        if( wp_ulike_is_true( wp_ulike_pro_get_metabox_value( 'enable_faq' ) ) ){
            $schema->generateCustomFAQSchema();
        }
    }
}
add_action ('wp_head', 'wp_ulike_pro_generate_schema');