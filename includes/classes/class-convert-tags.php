<?php

class WP_Ulike_Pro_Convert_Tags {

    protected $args;

    protected $vars;

	public function __construct( $atts = array(), $extra_vars = array() ){
        $this->args = $atts;
        $this->vars = $extra_vars;

        $this->global_vars();
        $this->option_vars();

        if( ! empty( $this->args['user_id'] ) ){
            $this->user_vars( $this->args['user_id'] );
        }
    }

    /**
     * Change global placeholders
     *
     * @return void
     */
    protected function global_vars(){
        $this->vars['{site_url}']  = network_site_url( '/' );
        $this->vars['{site_name}'] = get_bloginfo( 'name' );
    }

    /**
     * Change self options placeholders
     *
     * @return void
     */
    protected function option_vars(){
        $this->vars['{admin_email}'] = WP_Ulike_Pro_Options::getAdminEmail();
        $this->vars['{login_url}']   = WP_Ulike_Pro_Permalinks::get_login_url();
        $this->vars['{profile_url}'] = WP_Ulike_Pro_Options::getProfilePageUrl();
        $this->vars['{logout_url}']  = WP_Ulike_Pro_Permalinks::get_logout_url();
    }

    /**
     * Change user data placeholders
     *
     * @return void
     */
    protected function user_vars( $user_id ){
        $userdata = get_userdata( $user_id  );

        if( $userdata ){
            $this->vars['{user_id}']      = $user_id;
            $this->vars['{display_name}'] = esc_attr( $userdata->display_name );
            $this->vars['{first_name}']   = esc_attr( $userdata->first_name );
            $this->vars['{last_name}']    = esc_attr( $userdata->last_name );
            $this->vars['{username}']     = $userdata->user_login;
            $this->vars['{avatar_url}']   = get_avatar_url( $userdata->user_email );
            $this->vars['{email}']        = $userdata->user_email;

            $this->vars['{up_profile_url}'] = wp_ulike_pro_get_user_profile_permalink( $user_id );
            $this->vars['{bp_profile_url}'] = function_exists('bp_members_get_user_url') ? bp_members_get_user_url( $user_id ) : '#';
            $this->vars['{um_profile_url}'] = function_exists('um_user_profile_url') ?  um_user_profile_url( $user_id ) : '#';
        }

    }

    /**
     * Replace placeholders
     *
     * @param string $content
     * @return string
     */
	public function convert( $content ){
        $variables = apply_filters( 'wp_ulike_pro_template_tags_patterns', $this->vars );
        return strtr( $content, $variables );
    }

}