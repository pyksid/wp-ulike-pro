<?php

class WP_Ulike_Pro_Register_Public_Events
{
	public function __construct()
	{
		// Form endpoints exist only while Login & Signup is switched on, so a
		// site that does not use the forms exposes no form endpoints at all.
		if ( wp_ulike_pro_feature_enabled( 'login_signup' ) ) {
			// Front End Login
			add_action( 'wp_ajax_nopriv_ulp_login', array( $this, 'login' ) );
			add_action( 'wp_ajax_ulp_login', array( $this, 'login' ) );

			// Front End Signup
			add_action( 'wp_ajax_nopriv_ulp_signup', array( $this, 'signup' ) );
			add_action( 'wp_ajax_ulp_signup', array( $this, 'signup' ) );

			// Front Reset Password
			add_action( 'wp_ajax_nopriv_ulp_reset_password', array( $this, 'reset_password' ) );
			add_action( 'wp_ajax_ulp_reset_password', array( $this, 'reset_password' ) );

			// Two Factor Validation
			add_action( 'wp_ajax_nopriv_ulp_two_factor_validation', array( $this, 'two_factor_validation' ) );
			add_action( 'wp_ajax_ulp_two_factor_validation', array( $this, 'two_factor_validation' ) );

			// Two Factor Remove
			add_action( 'wp_ajax_nopriv_ulp_two_factor_remove', array( $this, 'two_factor_remove' ) );
			add_action( 'wp_ajax_ulp_two_factor_remove', array( $this, 'two_factor_remove' ) );

			// Ajax
			add_action( 'wp_ajax_nopriv_ulp_forms_toggle', array( $this, 'forms_toggle' ) );
			add_action( 'wp_ajax_ulp_forms_toggle', array( $this, 'forms_toggle' ) );
		}

		// Profile edit and avatar upload are reachable from the profile page and
		// from the edit-account form, so they stay registered while either of
		// those modules is on.
		if ( wp_ulike_pro_feature_enabled( 'profiles' ) || wp_ulike_pro_feature_enabled( 'login_signup' ) ) {
			// Front Edit Profile
			add_action( 'wp_ajax_nopriv_ulp_profile', array( $this, 'profile' ) );
			add_action( 'wp_ajax_ulp_profile', array( $this, 'profile' ) );

			// Avatar Upload
			add_action( 'wp_ajax_nopriv_ulp_avatar', array( $this, 'avatar' ) );
			add_action( 'wp_ajax_ulp_avatar', array( $this, 'avatar' ) );
		}

		// Likers List
		add_action( 'wp_ajax_nopriv_ulp_likers', array( $this, 'get_likers' ) );
		add_action( 'wp_ajax_ulp_likers', array( $this, 'get_likers' ) );

		// Views exist only to compute engagement rate in the reports.
		if ( wp_ulike_pro_feature_enabled( 'statistics' ) ) {
			add_action( 'wp_ajax_nopriv_ulp_track_view_batch', array( $this, 'track_button_view_batch' ) );
			add_action( 'wp_ajax_ulp_track_view_batch', array( $this, 'track_button_view_batch' ) );
		}

		// Engagements
		add_action( 'wp_ajax_nopriv_wp_ulike_pro_engagement_process', array( $this, 'engagement_process' ) );
		add_action( 'wp_ajax_wp_ulike_pro_engagement_process', array( $this, 'engagement_process' ) );
		add_action( 'wp_ajax_nopriv_ulp_engagement_engagers', array( $this, 'engagement_engagers' ) );
		add_action( 'wp_ajax_ulp_engagement_engagers', array( $this, 'engagement_engagers' ) );
	}

	/**
	* Login
	*/
	public function login()
	{
		new WP_Ulike_Pro_Login;
	}

	/**
	* Signup
	*/
	public function signup()
	{
		new WP_Ulike_Pro_SignUp;
	}

	/**
	* Reset password
	*/
	public function reset_password()
	{
		new WP_Ulike_Pro_Password;
	}

	/**
	* Profile
	*/
	public function profile()
	{
		new WP_Ulike_Pro_Profile;
    }

	/**
	* Profile
	*/
	public function get_likers()
	{
		new WP_Ulike_Pro_Likers;
    }

	/**
	* Avatar
	*/
	public function avatar()
	{
		new WP_Ulike_Pro_Avatar_Controller;
    }

	/**
	* Two factor validation
	*/
	public function two_factor_validation()
	{
		new WP_Ulike_Pro_Two_Factor_Validation;
    }

	/**
	* Two factor remove
	*/
	public function two_factor_remove()
	{
		new WP_Ulike_Pro_Two_Factor_Remove;
    }


	/**
	* Forms toggle
	*/
	public function forms_toggle()
	{
		new WP_Ulike_Pro_Forms_Toggle;
    }

	/**
	* Track button view batch
	*/
	public function track_button_view_batch()
	{
		$button_views = WP_Ulike_Pro_Views::get_instance();
		$button_views->ajax_track_view_batch();
    }

	/**
	 * Engagement vote / reaction process.
	 */
	public function engagement_process()
	{
		new WP_Ulike_Pro_Engagement_Listener();
	}

	/**
	 * Engagement engagers pile (modal / lazy load).
	 */
	public function engagement_engagers()
	{
		new WP_Ulike_Pro_Engagement_Engagers_Listener();
	}

}