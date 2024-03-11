<?php
// common functions
include_once( 'general-functions.php' );
include_once( 'general-hooks.php' );

// register prevent cache helper
WP_Ulike_Pro_Prevent_Caching::init();

// register customizer class
new WP_Ulike_Pro_Admin_Customizer;

// managers
include_once( 'managers/shortcodes.php' );

// init elementor
include_once( 'elementor/class-elements.php' );