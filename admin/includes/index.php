<?php

// commeon functions
include_once( 'general-functions.php' );
include_once( 'admin-hooks.php' );

// Options panel
new WP_Ulike_Pro_Options_Panel();
// Generate post type metabox
new WP_Ulike_Pro_Meta_Box();
// Generate comment metabox
new WP_Ulike_Pro_Comment_Meta_Box();
// Generate profile metabox
new WP_Ulike_Pro_Profile_Meta_Box();
// Init shortcoder
new WP_Ulike_Pro_Shortcoder();

// License Controller
new WP_Ulike_Pro_License();

include_once( 'classes/class-update-prepare.php' );
new WP_Ulike_Pro_Update_Prepare();