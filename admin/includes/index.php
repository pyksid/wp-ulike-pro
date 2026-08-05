<?php

// commeon functions
include_once( 'general-functions.php' );
include_once( 'admin-hooks.php' );

// Options panel
new WP_Ulike_Pro_Options_Panel();
// Per-post display overrides (native meta box)
new WP_Ulike_Pro_Post_Display_Meta();
// Per-comment display overrides (native meta box)
new WP_Ulike_Pro_Comment_Display_Meta();

// License Controller
new WP_Ulike_Pro_License();

// Maintenance actions
include_once( 'classes/class-maintenance.php' );

// Tools Controller
include_once( 'classes/class-tools.php' );
new WP_Ulike_Pro_Tools();
new WP_Ulike_Pro_Schema_Generator_Tool();

// Overview screen (hooks into free WP ULike Overview)
include_once( 'classes/class-overview.php' );
new WP_Ulike_Pro_Overview();

// Help backup extensions (display rules, REST API settings)
include_once( 'classes/class-wp-ulike-pro-backup.php' );

include_once( 'classes/class-update-prepare.php' );
new WP_Ulike_Pro_Update_Prepare();

if ( class_exists( 'WP_Ulike_Pro_Stats_Cron' ) ) {
	WP_Ulike_Pro_Stats_Cron::init();
}

