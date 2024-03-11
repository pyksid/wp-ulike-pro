<?php

class WP_Ulike_Pro_Register_Public_Events
{
	public function __construct()
	{
		// Front End Login
		add_action( 'wp_ajax_nopriv_ulp_login', array( $this, 'login' ) );
		add_action( 'wp_ajax_ulp_login', array( $this, 'login' ) );

		// Front End Signup
		add_action( 'wp_ajax_nopriv_ulp_signup', array( $this, 'signup' ) );
		add_action( 'wp_ajax_ulp_signup', array( $this, 'signup' ) );

		// Front Reset Password
		add_action( 'wp_ajax_nopriv_ulp_reset_password', array( $this, 'reset_password' ) );
		add_action( 'wp_ajax_ulp_reset_password', array( $this, 'reset_password' ) );

		// Front Edit Profile
		add_action( 'wp_ajax_nopriv_ulp_profile', array( $this, 'profile' ) );
		add_action( 'wp_ajax_ulp_profile', array( $this, 'profile' ) );

		// Likers List
		add_action( 'wp_ajax_nopriv_ulp_likers', array( $this, 'get_likers' ) );
		add_action( 'wp_ajax_ulp_likers', array( $this, 'get_likers' ) );

		// Avatar Upload
		add_action( 'wp_ajax_nopriv_ulp_avatar', array( $this, 'avatar' ) );
		add_action( 'wp_ajax_ulp_avatar', array( $this, 'avatar' ) );

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

}