<?php
/**
 * Option panel
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

class WP_Ulike_Pro_Options_Panel {

    protected $has_permission;

    /**
     * __construct
     */
    function __construct() {
        $this->has_permission = WP_Ulike_Pro_API::has_permission();
        // Init Hook
        $this->init();
    }

    public function init(){
        // Filters
        add_filter( 'wp_ulike_panel_general', array( $this, 'update_general_section' ), 10, 1 );
        add_filter( 'wp_ulike_panel_content_options', array( $this, 'content_options_section' ), 10, 1 );
        add_filter( 'wp_ulike_panel_integrations', array( $this, 'integrations_section' ), 10, 1 );
        add_filter( 'wp_ulike_panel_profiles', array( $this, 'profiles_section' ), 10, 1 );
        add_filter( 'wp_ulike_panel_share_buttons', array( $this, 'social_share_section' ), 10, 1 );
        add_filter( 'wp_ulike_panel_forms', array( $this, 'forms_section' ), 10, 1 );
        add_filter( 'wp_ulike_panel_social_logins', array( $this, 'social_login' ), 10, 1 );
        add_filter( 'wp_ulike_panel_translations', array( $this, 'translations_section' ), 10, 1 );
        add_filter( 'wp_ulike_panel_rest_api', array( $this, 'rest_api_section' ), 10, 1 );
        add_filter( 'wp_ulike_panel_optimization', array( $this, 'optimization_section' ), 10, 1 );
        add_filter( 'wp_ulike_panel_post_type_options', array( $this, 'post_type_options' ), 10, 1 );
        add_filter( 'wp_ulike_panel_comment_type_options', array( $this, 'comment_type_options' ), 10, 1 );

        // add_filter( 'wp_ulike_panel_content_options', array( $this, 'update_content_options' ), 10, 1 );
        // Custom options
        add_filter( 'wp_ulike_filter_counter_options', array( $this, 'filter_counter_options' ), 10, 2 );

        // Actions
        add_action( 'wp_ulike_panel_sections_ended', array( $this, 'actions' ) );

        // Hooks
        add_action( 'ulf_wp_ulike_settings_saved', array( $this, 'options_saved' ) );
    }

    public function actions(){
        $this->register_email_translations_section();
        $this->register_backup_section();
    }

    public function options_saved(){
        // Reset rewrite rules
        wp_ulike_pro_reset_rules();
    }

    // public function update_content_options( $options ){

    //     $custom_template = array(
    //         'custom_template' => array(
    //             'id'         => 'custom_template',
    //             'type'       => 'code_editor',
    //             'title'      => esc_html__('Custom PHP Template',WP_ULIKE_PRO_DOMAIN),
    //             'dependency' => array( 'template', '==', 'wp-ulike-custom-template' ),
    //             'settings'   => array(
    //                 'mode'   => 'php'
    //             ),
    //             'sanitize'   => false,
    //         )
    //     );

    //     $options = $this->array_insert_after( $options, 'template', $custom_template );

    //     return $options;
    // }


    /**
     * Custom function to insert item after array key
     *
     * @param array $array
     * @param string $key
     * @param array $new
     * @return array
     */
    public function array_insert_after( array $array, $key, array $new ) {
        $keys = array_keys( $array );
        $index = array_search( $key, $keys );
        $pos = false === $index ? count( $array ) : $index + 1;

        return array_merge( array_slice( $array, 0, $pos ), $new, array_slice( $array, $pos ) );
    }

    /**
     * Enable meta options
     *
     * @param array $options
     * @return array
     */
    public function post_type_options( $options ){
        $options[] = array(
            'id'      => 'enable_metadata',
            'type'    => 'switcher',
            'default' => true,
            'title'   => esc_html__('Enable Standard Meta Data', WP_ULIKE_PRO_DOMAIN),
            'desc'    => esc_html__('By activating this option, the counter data is stored simultaneously in the standard meta table and you can use it to create custom queries.', WP_ULIKE_PRO_DOMAIN) . '<br>' . 'Meta Keys: <code>like_amount</code>, <code>dislike_amount</code>, <code>net_votes</code>',
            'help'    => 'If you are an old user, after activating this option, go to Developer Tools > Optimization section and once click on "Migrate Counter Metadata" button to move counter values from wp_ulike_meta table to meta table.'
        );
        $options[] = array(
            'id'      => 'enable_attachments',
            'type'    => 'switcher',
            'default' => false,
            'title'   => esc_html__('Enable Attachments', WP_ULIKE_PRO_DOMAIN),
            'desc'    => esc_html__('By activating this option, you can add voting buttons for image attachments. This option only works when you use WordPress +5.6 and also have the standard `wp_get_attachment_image` function in your theme.', WP_ULIKE_PRO_DOMAIN),
        );

        $options[] = array(
            'id'         => 'filter_attachment_ids',
            'type'       => 'select',
            'chosen'     => true,
            'settings'   => array(
                'min_length' => 1
            ),
            'ajax'       => true,
            'multiple'   => true,
            'title'      => esc_html__('Filter By Attachment ID', WP_ULIKE_PRO_DOMAIN),
            'options'    => 'wp_ulike_pro_search_attachments',
            'dependency' => array( 'enable_attachments', '==', 'true' )
        );

        $options[] = array(
            'id'         => 'filter_attachment_class',
            'type'       => 'repeater',
            'fields'     => array(
                array(
                    'id'    => 'name',
                    'type'  => 'text',
                    'title' => esc_html__('class name', WP_ULIKE_PRO_DOMAIN)
                ),
            ),
            'title'      => esc_html__('Filter By Class Name', WP_ULIKE_PRO_DOMAIN),
            'dependency' => array( 'enable_attachments', '==', 'true' ),
            'desc'       => esc_html__('Add attachment specified class names. (e.g. attachment-full)', WP_ULIKE_PRO_DOMAIN)
        );

        $options[] = array(
            'id'         => 'filter_attachment_size',
            'type'       => 'repeater',
            'fields'     => array(
                array(
                    'id'    => 'name',
                    'type'  => 'text',
                    'title' => esc_html__('image size', WP_ULIKE_PRO_DOMAIN)
                ),
            ),
            'title'      => esc_html__('Filter By Attachment Size', WP_ULIKE_PRO_DOMAIN),
            'dependency' => array( 'enable_attachments', '==', 'true' ),
            'desc'       => esc_html__('Add attachment standard size. (e.g. large, thumbnail, etc.)', WP_ULIKE_PRO_DOMAIN)
        );

        return $options;
    }

    /**
     * Enable meta options
     *
     * @param array $options
     * @return array
     */
    public function comment_type_options( $options ){
        $options[] = array(
            'id'      => 'enable_metadata',
            'type'    => 'switcher',
            'default' => true,
            'title'   => esc_html__('Enable Standard Meta Data', WP_ULIKE_PRO_DOMAIN),
            'desc'    => esc_html__('By activating this option, the counter data is stored simultaneously in the standard meta table and you can use it to create custom queries.', WP_ULIKE_PRO_DOMAIN) . '<br>' . 'Meta Keys: <code>like_amount</code>, <code>dislike_amount</code>, <code>net_votes</code>' ,
            'help'        => 'If you are an old user, after activating this option, go to Developer Tools > Optimization section and once click on "Migrate Counter Metadata" button to move counter values from wp_ulike_meta table to meta table.'
        );

        return $options;
    }

    /**
     * Add new options to filter counter
     *
     * @param array $options
     * @param string $type
     * @return array
     */
    public function filter_counter_options( $options, $type ){

        if( $type === 'prefix' ){
            $options[] = array(
                'id'      => 'dislike_prefix',
                'type'    => 'text',
                'default' => '-',
                'title'   => esc_html__('Dislike',WP_ULIKE_PRO_DOMAIN)
            );
            $options[] = array(
                'id'      => 'undislike_prefix',
                'type'    => 'text',
                'default' => '-',
                'title'   => esc_html__('Undislike',WP_ULIKE_PRO_DOMAIN)
            );
        }

        if( $type === 'postfix' ){
            $options[] = array(
                'id'      => 'dislike_postfix',
                'type'    => 'text',
                'title'   => esc_html__('Dislike',WP_ULIKE_PRO_DOMAIN)
            );
            $options[] = array(
                'id'      => 'undislike_postfix',
                'type'    => 'text',
                'title'   => esc_html__('Undislike',WP_ULIKE_PRO_DOMAIN)
            );
        }

        return $options;
    }


    /**
     * Update general section in setting panel
     *
     * @param array $options
     * @return array
     */
    public function update_general_section( $options ){
        // Get all display roles
        $user_roles_list = wp_ulike_pro_get_user_roles_list( array( 'Administrator', 'Subscriber' ) );

        $options[] = array(
            'id'      => 'enable_shortcoder',
            'type'    => 'switcher',
            'default' => true,
            'title'   => esc_html__('Enable Shortcode Generator', WP_ULIKE_PRO_DOMAIN),
            'desc'    => esc_html__('Comes with pre-built wp ulike shortcode editor to manage your content.', WP_ULIKE_PRO_DOMAIN)
        );
        $options[] =  array(
            'id'       => 'statistics_display_roles',
            'type'     => 'select',
            'title'    => esc_html__( 'Display Stats Menu Capability', WP_ULIKE_PRO_DOMAIN),
            'desc'     => esc_html__( 'Manage users\' access level to view this page',WP_ULIKE_PRO_DOMAIN ),
            'chosen'   => true,
            'multiple' => true,
            'options'  => $user_roles_list
        );
        $options[] = array(
            'id'       => 'logs_display_roles',
            'type'     => 'select',
            'title'    => esc_html__('Display Logs Menu Capability',WP_ULIKE_PRO_DOMAIN),
            'desc'     => esc_html__( 'Manage users\' access level to view this page',WP_ULIKE_PRO_DOMAIN ),
            'chosen'   => true,
            'multiple' => true,
            'options'  => $user_roles_list
        );
        $options[] = array(
            'id'       => 'enable_meta_box',
            'type'     => 'select',
            'title'    => esc_html__( 'Enable Meta Box',WP_ULIKE_PRO_DOMAIN ),
            'desc'     => esc_html__( 'Display meta box panel in selected post types.',WP_ULIKE_PRO_DOMAIN ),
            'chosen'   => true,
            'multiple' => true,
            'default'  => array('post', 'page'),
            'options'  => 'post_types'
        );

        return $options;
    }

    /**
     * Update content options
     *
     * @param array $options
     * @return array
     */
    public function content_options_section( $options ){
        if( isset( $options['text_group']['tabs'] ) ){
            $options['text_group']['tabs'][] = array(
                'title'     => esc_html__('Dislike',WP_ULIKE_PRO_DOMAIN),
                'fields'    => array(
                    array(
                        'id'      => 'dislike',
                        'type'    => 'text',
                        'title'   => esc_html__('Button Text',WP_ULIKE_PRO_DOMAIN),
                        'default' => 'Dislike'
                    ),
                )
            );
            $options['text_group']['tabs'][] = array(
                'title'     => esc_html__('Undislike',WP_ULIKE_PRO_DOMAIN),
                'fields'    => array(
                    array(
                        'id'      => 'undislike',
                        'type'    => 'text',
                        'title'   => esc_html__('Button Text',WP_ULIKE_PRO_DOMAIN),
                        'default' => 'Disliked'
                    ),
                )
            );
        }
        if( isset( $options['image_group']['tabs'] ) ){
            $options['image_group']['tabs'][] = array(
                'title'     => esc_html__('Dislike',WP_ULIKE_PRO_DOMAIN),
                'fields'    => array(
                    array(
                        'id'           => 'dislike',
                        'type'         => 'upload',
                        'title'        => esc_html__('Button Image',WP_ULIKE_PRO_DOMAIN),
                        'library'      => 'image',
                        'placeholder'  => 'http://'
                    ),
                )
            );
            $options['image_group']['tabs'][] = array(
                'title'     => esc_html__('Undislike',WP_ULIKE_PRO_DOMAIN),
                'fields'    => array(
                    array(
                        'id'           => 'undislike',
                        'type'         => 'upload',
                        'title'        => esc_html__('Button Image',WP_ULIKE_PRO_DOMAIN),
                        'library'      => 'image',
                        'placeholder'  => 'http://'
                    ),
                )
            );
        }

        // Add modal option
        if( isset( $options['logged_out_display_type']['options'] ) ){
            $options['logged_out_display_type']['options']['modal'] = esc_html__('Modal', WP_ULIKE_PRO_DOMAIN);
        }

        // Add modal option
        if( isset( $options['likers_style']['options'] ) ){
            $options['likers_style']['options']['pile'] = esc_html__('Pile + Modal', WP_ULIKE_PRO_DOMAIN);
        }

        // Add percentage option
        $percentage_list = wp_ulike_pro_get_templates_list_by_attribute( 'is_percentage_support' );
        $options = $this->array_insert_after( $options, 'hide_zero_counter', array(
            'enable_percentage_values' => array(
                'id'         => 'enable_percentage_values',
                'type'       => 'switcher',
                'title'      => esc_html__('Enable Percentage Values', WP_ULIKE_PRO_DOMAIN),
                'dependency' => array( 'counter_display_condition|template', '!=|any', 'hidden|' . $percentage_list )
            )
        ) );

        // Add modal login template
        $options = $this->array_insert_after( $options, 'login_template', array(
            'modal_template' => array(
                'id'         => 'modal_template',
                'type'       => 'wp_editor',
                'height'     => '100px',
                'default'    => '[wp_ulike_pro_login_form ajax_toggle=1 redirect_to="current_page"]',
                'title'      => esc_html__('Modal Template', WP_ULIKE_PRO_DOMAIN),
                'dependency' => array( 'logged_out_display_type|enable_only_logged_in_users', '==|==', 'modal|true' )
            )
        ) );

        // Pile modal title
        $options = $this->array_insert_after( $options, 'likers_style', array(
            'likers_modal_title' => array(
                'id'         => 'likers_modal_title',
                'type'       => 'text',
                'default'    => esc_html__('Likers', WP_ULIKE_PRO_DOMAIN),
                'title'      => esc_html__('Likers Modal Title', WP_ULIKE_PRO_DOMAIN),
                'dependency' => array( 'enable_likers_box|likers_style', '==|any', 'true|pile'  ),
            )
        ) );

        // Pile modal template
        $options = $this->array_insert_after( $options, 'likers_modal_title', array(
            'likers_modal_template' => array(
                'id'       => 'likers_modal_template',
                'type'     => 'code_editor',
                'settings' => array(
                    'theme' => 'shadowfox',
                    'mode'  => 'htmlmixed',
                ),
                'default'  => '<a href="{up_profile_url}" class="ulp-flex-row ulp-flex-middle-xs ulp-flex-start-md">
  <span class="ulp-flex-col-md-2 ulp-flex-col-xs-1 ulp-user-icon">
    <img src="{avatar_url}" class="ulp-img-icon" title="{display_name}" alt="{display_name}" width="80" height="80"/>
  </span>
  <span class="ulp-flex-col-md-10 ulp-flex-col-xs-11 ulp-user-info">
    <span class="ulp-title">{display_name}</span>
  </span>
</a>',
                'title'      => esc_html__('Likers Modal Template', WP_ULIKE_PRO_DOMAIN),
                'desc'       => esc_html__('Allowed Variables:', WP_ULIKE_PRO_DOMAIN) . ' <code>{up_profile_url}</code> , <code>{bp_profile_url}</code> , <code>{um_profile_url}</code> , <code>{avatar_url}</code> , <code>{display_name}</code> , <code>{first_name}</code> , <code>{last_name}</code> , <code>{username}</code> , <code>{email}</code>, <code>{user_id}</code> , <code>{user_status}</code>',
                'dependency' => array( 'enable_likers_box|likers_style', '==|any', 'true|pile'  ),
            )
        ) );

        return $options;
    }

    /**
     * Update integrations section in setting panel
     *
     * @param array $options
     * @return array
     */
    public function integrations_section( $options ){
        $options[] = array(
            'id'         => 'enable_serialize',
            'type'       => 'switcher',
            'default'    => false,
            'title'      => esc_html__('Enable Serialize Data type', WP_ULIKE_PRO_DOMAIN),
            'desc'       => esc_html__('By activating this option, the metabox information will be serialized and stored in a single row. This will lead to fewer records in the database and more performance. But if you are an old user, you probably need to use the options below to convert the old metaboxes to the new serialize structure.', WP_ULIKE_PRO_DOMAIN)
        );
        $options[] = array(
            'id'     => 'opt-integration',
            'type'   => 'fieldset',
            'title'  => esc_html__( 'Conversions', WP_ULIKE_PRO_DOMAIN),
            'fields' => array(
                array(
                    'type'     => 'callback',
                    'function' => 'wp_ulike_pro_ajax_button_callback',
                    'args'     => array(
                        'title'  => esc_html__( 'Upgrade Metabox Values', WP_ULIKE_PRO_DOMAIN),
                        'label'  => esc_html__( 'Merge Rows', WP_ULIKE_PRO_DOMAIN),
                        'desc'   => esc_html__( 'Convert the old meta boxes to the new serialize structure.', WP_ULIKE_PRO_DOMAIN),
                        'type'   => 'post',
                        'action' => 'upgrade_unserialize_post_meta'
                    )
                ),
                array(
                    'type'     => 'callback',
                    'function' => 'wp_ulike_pro_ajax_button_callback',
                    'args'     => array(
                        'title'  => esc_html__( 'Delete Old Post Meta', WP_ULIKE_PRO_DOMAIN),
                        'label'  => esc_html__( 'Delete All Rows', WP_ULIKE_PRO_DOMAIN),
                        'desc'   => esc_html__( 'Drop all unserialized meta box rows after upgrade to serialize structure. This Action Is Not Reversible.', WP_ULIKE_PRO_DOMAIN),
                        'type'   => 'delete_all',
                        'action' => 'optimize_post_meta'
                    )
                ),
            ),
            'dependency' => array( 'enable_serialize', '==', 'true' )
        );

        return $options;
    }

    /**
     * Update translation section in setting panel
     *
     * @param array $options
     * @return array
     */
    public function translations_section( $options ){
        $options[] =  array(
            'id'      => 'dislike_notice',
            'type'    => 'text',
            'default' => esc_html__('Sorry! You Disliked This.',WP_ULIKE_PRO_DOMAIN),
            'title'   => esc_html__( 'Dislike Notice Message', WP_ULIKE_PRO_DOMAIN)
        );
        $options[] = array(
            'id'      => 'undislike_notice',
            'type'    => 'text',
            'default' => esc_html__('Thanks! You Undisliked This.',WP_ULIKE_PRO_DOMAIN),
            'title'   => esc_html__( 'Undislike Notice Message', WP_ULIKE_PRO_DOMAIN)
        );
        $options[] = array(
            'id'      => 'dislike_button_aria_label',
            'type'    => 'text',
            'default' => esc_html__( 'Dislike Button',WP_ULIKE_PRO_DOMAIN),
            'title'   => esc_html__( 'Dislike Button Aria Label', WP_ULIKE_PRO_DOMAIN)
        );

        // Notices
        $options[] = array(
            'type'    => 'heading',
            'content' => esc_html__( 'Forms Notices', WP_ULIKE_PRO_DOMAIN)
        );

        $options[] = array(
            'id'      => 'required_fields_notice',
            'type'    => 'text',
            'default' => esc_html__( 'Please enter required fields.', WP_ULIKE_PRO_DOMAIN ),
            'title'   => esc_html__( 'Enter Required Fields', WP_ULIKE_PRO_DOMAIN)
        );

        $options[] = array(
            'id'      => 'permission_denied_notice',
            'type'    => 'text',
            'default' => esc_html__( 'Something went wrong. Please try again or contact the admin.', WP_ULIKE_PRO_DOMAIN ),
            'title'   => esc_html__( 'Permission Denied', WP_ULIKE_PRO_DOMAIN)
        );

        $options[] = array(
            'id'      => 'login_success_notice',
            'type'    => 'text',
            'default' => esc_html__( 'Login successful.', WP_ULIKE_PRO_DOMAIN ),
            'title'   => esc_html__( 'Login Successful', WP_ULIKE_PRO_DOMAIN)
        );

        $options[] = array(
            'id'      => 'login_failed_notice',
            'type'    => 'text',
            'default' => esc_html__( 'Invalid username or incorrect password!', WP_ULIKE_PRO_DOMAIN ),
            'title'   => esc_html__( 'Login Failed', WP_ULIKE_PRO_DOMAIN)
        );

        $options[] = array(
            'id'      => 'password_reset_notice',
            'type'    => 'text',
            'default' => esc_html__( 'Your password has been reset.', WP_ULIKE_PRO_DOMAIN ),
            'title'   => esc_html__( 'Password Reset', WP_ULIKE_PRO_DOMAIN)
        );

        $options[] = array(
            'id'      => 'password_match_notice',
            'type'    => 'text',
            'default' => esc_html__( 'Oops! Password did not match! Try again.', WP_ULIKE_PRO_DOMAIN ),
            'title'   => esc_html__( 'Password Match', WP_ULIKE_PRO_DOMAIN)
        );

        $options[] = array(
            'id'      => 'empty_username_notice',
            'type'    => 'text',
            'default' => esc_html__( 'Enter a username or email address.', WP_ULIKE_PRO_DOMAIN ),
            'title'   => esc_html__( 'Empty Username', WP_ULIKE_PRO_DOMAIN)
        );

        $options[] = array(
            'id'      => 'invalidcombo_notice',
            'type'    => 'text',
            'default' => esc_html__( 'There is no account with that username or email address.', WP_ULIKE_PRO_DOMAIN ),
            'title'   => esc_html__( 'Invalid Combo', WP_ULIKE_PRO_DOMAIN)
        );

        $options[] = array(
            'id'      => 'email_error_notice',
            'type'    => 'text',
            'default' => esc_html__( 'The email could not be sent.', WP_ULIKE_PRO_DOMAIN ),
            'title'   => esc_html__( 'Email Error', WP_ULIKE_PRO_DOMAIN)
        );

        $options[] = array(
            'id'      => 'fill_signup_form_notice',
            'type'    => 'text',
            'default' => esc_html__( 'Error Occured please fill up the signup form carefully.', WP_ULIKE_PRO_DOMAIN ),
            'title'   => esc_html__( 'Fill Signup Form', WP_ULIKE_PRO_DOMAIN)
        );

        $options[] = array(
            'id'      => 'error_occurred_notice',
            'type'    => 'text',
            'default' => esc_html__( 'An error has occurred! Please try again later.', WP_ULIKE_PRO_DOMAIN ),
            'title'   => esc_html__( 'Error Occurred', WP_ULIKE_PRO_DOMAIN)
        );

        $options[] = array(
            'id'      => 'signup_success_notice',
            'type'    => 'text',
            'default' => esc_html__( 'Signup successful.', WP_ULIKE_PRO_DOMAIN ),
            'title'   => esc_html__( 'Signup Sucess', WP_ULIKE_PRO_DOMAIN)
        );

        $options[] = array(
            'id'      => 'disabled_registration_notice',
            'type'    => 'text',
            'default' => esc_html__( 'Registration is currently disabled.', WP_ULIKE_PRO_DOMAIN ),
            'title'   => esc_html__( 'Disabled Registration', WP_ULIKE_PRO_DOMAIN)
        );

        $options[] = array(
            'id'      => 'invalid_email_notice',
            'type'    => 'text',
            'default' => esc_html__( 'Email address is not valid!', WP_ULIKE_PRO_DOMAIN ),
            'title'   => esc_html__( 'Invalid Email Address', WP_ULIKE_PRO_DOMAIN)
        );

        $options[] = array(
            'id'      => 'email_exist_notice',
            'type'    => 'text',
            'default' => esc_html__( 'Sorry, that email address is already used!', WP_ULIKE_PRO_DOMAIN ),
            'title'   => esc_html__( 'Email Exist', WP_ULIKE_PRO_DOMAIN)
        );

        $options[] = array(
            'id'      => 'username_exist_notice',
            'type'    => 'text',
            'default' => esc_html__( 'Sorry, that username is already used!', WP_ULIKE_PRO_DOMAIN ),
            'title'   => esc_html__( 'Username Exist', WP_ULIKE_PRO_DOMAIN)
        );

        // Avatar
        $options[] = array(
            'type'    => 'heading',
            'content' => esc_html__( 'Upload Avatar Labels', WP_ULIKE_PRO_DOMAIN)
        );

        $options[] = array(
            'id'      => 'user_not_found',
            'type'    => 'text',
            'default' => esc_html__( 'User Not Found!',WP_ULIKE_PRO_DOMAIN),
            'title'   => esc_html__( 'User Not Found Message', WP_ULIKE_PRO_DOMAIN)
        );

        $options[] = array(
            'id'      => 'avatar_upload_text',
            'type'    => 'text',
            'default' => esc_html__( 'Upload', WP_ULIKE_PRO_DOMAIN ),
            'title'   => esc_html__( 'Avatar Upload Text', WP_ULIKE_PRO_DOMAIN)
        );

        $options[] = array(
            'id'      => 'avatar_edit_text',
            'type'    => 'text',
            'default' => esc_html__( 'Edit', WP_ULIKE_PRO_DOMAIN ),
            'title'   => esc_html__( 'Avatar Edit Text', WP_ULIKE_PRO_DOMAIN)
        );

        $options[] = array(
            'id'      => 'avatar_delete_text',
            'type'    => 'text',
            'default' => esc_html__( 'Delete', WP_ULIKE_PRO_DOMAIN ),
            'title'   => esc_html__( 'Avatar Delete Text', WP_ULIKE_PRO_DOMAIN)
        );

        $options[] = array(
            'id'      => 'avatar_logout_text',
            'type'    => 'text',
            'default' => esc_html__( 'Log Out', WP_ULIKE_PRO_DOMAIN ),
            'title'   => esc_html__( 'Profile Log Out Text', WP_ULIKE_PRO_DOMAIN)
        );


        // login
        $options[] = array(
            'type'    => 'heading',
            'content' => esc_html__( 'Login Form Labels', WP_ULIKE_PRO_DOMAIN)
        );

        $options[] = array(
            'id'      => 'login_username',
            'type'    => 'text',
            'default' => esc_html__( 'Username', WP_ULIKE_PRO_DOMAIN ),
            'title'   => esc_html__( 'Username', WP_ULIKE_PRO_DOMAIN )
        );

        $options[] = array(
            'id'      => 'login_password',
            'type'    => 'text',
            'default' => esc_html__( 'Password', WP_ULIKE_PRO_DOMAIN ),
            'title'   => esc_html__( 'Password', WP_ULIKE_PRO_DOMAIN )
        );

        $options[] = array(
            'id'      => 'login_remember',
            'type'    => 'text',
            'default' => esc_html__( 'Remember Me', WP_ULIKE_PRO_DOMAIN ),
            'title'   => esc_html__( 'Remember Me', WP_ULIKE_PRO_DOMAIN )
        );

        $options[] = array(
            'id'      => 'login_button',
            'type'    => 'text',
            'default' => esc_html__( 'Log in', WP_ULIKE_PRO_DOMAIN ),
            'title'   => esc_html__( 'Log in', WP_ULIKE_PRO_DOMAIN )
        );

        $options[] = array(
            'id'      => 'login_reset_password',
            'type'    => 'text',
            'default' => esc_html__( 'Forgot Password?', WP_ULIKE_PRO_DOMAIN ),
            'title'   => esc_html__( 'Forgot Password?', WP_ULIKE_PRO_DOMAIN )
        );

        $options[] = array(
            'id'      => 'login_signup_message',
            'type'    => 'text',
            'default' => esc_html__( 'Don\'t have an account?', WP_ULIKE_PRO_DOMAIN ),
            'title'   => esc_html__( 'Don\'t have an account?', WP_ULIKE_PRO_DOMAIN )
        );

        $options[] = array(
            'id'      => 'login_signup_text',
            'type'    => 'text',
            'default' => esc_html__( 'Create Account', WP_ULIKE_PRO_DOMAIN ),
            'title'   => esc_html__( 'Create Account', WP_ULIKE_PRO_DOMAIN )
        );

        // signup
        $options[] = array(
            'type'    => 'heading',
            'content' => esc_html__( 'Signup Form Labels', WP_ULIKE_PRO_DOMAIN)
        );

        $options[] = array(
            'id'      => 'signup_username',
            'type'    => 'text',
            'default' => esc_html__( 'Username', WP_ULIKE_PRO_DOMAIN ),
            'title'   => esc_html__( 'Username', WP_ULIKE_PRO_DOMAIN )
        );

        $options[] = array(
            'id'      => 'signup_firstname',
            'type'    => 'text',
            'default' => esc_html__( 'First Name', WP_ULIKE_PRO_DOMAIN ),
            'title'   => esc_html__( 'First Name', WP_ULIKE_PRO_DOMAIN )
        );

        $options[] = array(
            'id'      => 'signup_lastname',
            'type'    => 'text',
            'default' => esc_html__( 'Last Name', WP_ULIKE_PRO_DOMAIN ),
            'title'   => esc_html__( 'Last Name', WP_ULIKE_PRO_DOMAIN )
        );

        $options[] = array(
            'id'      => 'signup_email',
            'type'    => 'text',
            'default' => esc_html__( 'Email Address', WP_ULIKE_PRO_DOMAIN ),
            'title'   => esc_html__( 'Email Address', WP_ULIKE_PRO_DOMAIN )
        );

        $options[] = array(
            'id'      => 'signup_password',
            'type'    => 'text',
            'default' => esc_html__( 'Password', WP_ULIKE_PRO_DOMAIN ),
            'title'   => esc_html__( 'Password', WP_ULIKE_PRO_DOMAIN )
        );

        $options[] = array(
            'id'      => 'signup_button',
            'type'    => 'text',
            'default' => esc_html__( 'Register', WP_ULIKE_PRO_DOMAIN ),
            'title'   => esc_html__( 'Register', WP_ULIKE_PRO_DOMAIN )
        );

        $options[] = array(
            'id'      => 'signup_login_message',
            'type'    => 'text',
            'default' => esc_html__( 'Already have an account?', WP_ULIKE_PRO_DOMAIN ),
            'title'   => esc_html__( 'Already have an account?', WP_ULIKE_PRO_DOMAIN )
        );

        $options[] = array(
            'id'      => 'signup_login_text',
            'type'    => 'text',
            'default' => esc_html__( 'Sign In', WP_ULIKE_PRO_DOMAIN ),
            'title'   => esc_html__( 'Sign In', WP_ULIKE_PRO_DOMAIN )
        );

        // reset password
        $options[] = array(
            'type'    => 'heading',
            'content' => esc_html__( 'Reset Password Form Labels', WP_ULIKE_PRO_DOMAIN)
        );

        $options[] = array(
            'id'      => 'rp_reset_message',
            'type'    => 'text',
            'default' => esc_html__( 'To reset your password, please enter your email address or username below', WP_ULIKE_PRO_DOMAIN ),
            'title'   => esc_html__( 'To reset your password...', WP_ULIKE_PRO_DOMAIN )
        );

        $options[] = array(
            'id'      => 'rp_change_message',
            'type'    => 'text',
            'default' => esc_html__( 'Enter your new password below.', WP_ULIKE_PRO_DOMAIN ),
            'title'   => esc_html__( 'Enter your new password below.', WP_ULIKE_PRO_DOMAIN )
        );

        $options[] = array(
            'id'      => 'rp_mail_message',
            'type'    => 'text',
            'default' => esc_html__( 'Check your e-mail address linked to the account for the confirmation link, including the spam or junk folder.
            ', WP_ULIKE_PRO_DOMAIN ),
            'title'   => esc_html__( 'Email Confirmation', WP_ULIKE_PRO_DOMAIN )
        );

        $options[] = array(
            'id'      => 'rp_username',
            'type'    => 'text',
            'default' => esc_html__( 'Username or Email', WP_ULIKE_PRO_DOMAIN ),
            'title'   => esc_html__( 'Username or Email', WP_ULIKE_PRO_DOMAIN )
        );

        $options[] = array(
            'id'      => 'rp_new_pass',
            'type'    => 'text',
            'default' => esc_html__( 'New Password', WP_ULIKE_PRO_DOMAIN ),
            'title'   => esc_html__( 'New Password', WP_ULIKE_PRO_DOMAIN )
        );

        $options[] = array(
            'id'      => 'rp_re_new_pass',
            'type'    => 'text',
            'default' => esc_html__( 'Re-enter New Password', WP_ULIKE_PRO_DOMAIN ),
            'title'   => esc_html__( 'Re-enter New Password', WP_ULIKE_PRO_DOMAIN )
        );

        $options[] = array(
            'id'      => 'rp_invalidkey',
            'type'    => 'text',
            'default' => esc_html__( 'Your password reset link appears to be invalid. Please request a new link below.', WP_ULIKE_PRO_DOMAIN ),
            'title'   => esc_html__( 'Your password reset link appears to be invalid.', WP_ULIKE_PRO_DOMAIN )
        );

        $options[] = array(
            'id'      => 'rp_expiredkey',
            'type'    => 'text',
            'default' => esc_html__( 'Your password reset link has expired. Please request a new link below.', WP_ULIKE_PRO_DOMAIN ),
            'title'   => esc_html__( 'Your password reset link has expired.', WP_ULIKE_PRO_DOMAIN )
        );

        $options[] = array(
            'id'      => 'rp_reset_button',
            'type'    => 'text',
            'default' => esc_html__( 'Get New Password', WP_ULIKE_PRO_DOMAIN ),
            'title'   => esc_html__( 'Get New Password', WP_ULIKE_PRO_DOMAIN )
        );

        $options[] = array(
            'id'      => 'rp_change_button',
            'type'    => 'text',
            'default' => esc_html__( 'Reset password', WP_ULIKE_PRO_DOMAIN ),
            'title'   => esc_html__( 'Reset password', WP_ULIKE_PRO_DOMAIN )
        );

        $options[] = array(
            'id'      => 'rp_login_message',
            'type'    => 'text',
            'default' => esc_html__( 'Go to login page', WP_ULIKE_PRO_DOMAIN ),
            'title'   => esc_html__( 'Go to login page', WP_ULIKE_PRO_DOMAIN )
        );

        // edit account
        $options[] = array(
            'type'    => 'heading',
            'content' => esc_html__( 'Edit Account Form Labels', WP_ULIKE_PRO_DOMAIN)
        );

        $options[] = array(
            'id'      => 'ea_firstname',
            'type'    => 'text',
            'default' => esc_html__( 'First Name', WP_ULIKE_PRO_DOMAIN ),
            'title'   =>esc_html__( 'First Name', WP_ULIKE_PRO_DOMAIN )
        );

        $options[] = array(
            'id'      => 'ea_lastname',
            'type'    => 'text',
            'default' => esc_html__( 'Last Name', WP_ULIKE_PRO_DOMAIN ),
            'title'   =>esc_html__( 'Last Name', WP_ULIKE_PRO_DOMAIN )
        );

        $options[] = array(
            'id'      => 'ea_website',
            'type'    => 'text',
            'default' => esc_html__( 'Website', WP_ULIKE_PRO_DOMAIN ),
            'title'   => esc_html__( 'Website', WP_ULIKE_PRO_DOMAIN )
        );

        $options[] = array(
            'id'      => 'ea_description',
            'type'    => 'text',
            'default' => esc_html__( 'Biographical Info', WP_ULIKE_PRO_DOMAIN ),
            'title'   => esc_html__( 'Biographical Info', WP_ULIKE_PRO_DOMAIN )
        );

        $options[] = array(
            'id'      => 'ea_email',
            'type'    => 'text',
            'default' => esc_html__( 'Email Address', WP_ULIKE_PRO_DOMAIN ),
            'title'   => esc_html__( 'Email Address', WP_ULIKE_PRO_DOMAIN )
        );

        $options[] = array(
            'id'      => 'ea_avatar',
            'type'    => 'text',
            'default' => esc_html__( 'Upload Avatar', WP_ULIKE_PRO_DOMAIN ),
            'title'   => esc_html__( 'Upload Avatar', WP_ULIKE_PRO_DOMAIN )
        );

        $options[] = array(
            'id'      => 'ea_button',
            'type'    => 'text',
            'default' => esc_html__( 'Submit', WP_ULIKE_PRO_DOMAIN ),
            'title'   => esc_html__( 'Submit', WP_ULIKE_PRO_DOMAIN )
        );

        $options[] = array(
            'id'      => 'ea_permission_message',
            'type'    => 'text',
            'default' => esc_html__( 'You don\'t have access to edit the information on this page!', WP_ULIKE_PRO_DOMAIN ),
            'title'   => esc_html__( 'You don\'t have access Message', WP_ULIKE_PRO_DOMAIN )
        );

        // Two Factor
        $options[] = array(
            'type'    => 'heading',
            'content' => esc_html__( 'Two Factor Notices', WP_ULIKE_PRO_DOMAIN)
        );

        $options[] = array(
            'id'      => 'two_factor_field_title',
            'type'    => 'text',
            'default' => esc_html__( 'Enter the six-digit code from the application', WP_ULIKE_PRO_DOMAIN ),
            'title'   => esc_html__( 'Two Factor Title', WP_ULIKE_PRO_DOMAIN)
        );

        $options[] = array(
            'id'      => 'incorrect_tfa_notice',
            'type'    => 'text',
            'default' => esc_html__( 'The one-time password (TFA code) you entered was incorrect', WP_ULIKE_PRO_DOMAIN ),
            'title'   => esc_html__( 'Incorrect one-time password message', WP_ULIKE_PRO_DOMAIN)
        );

        // Recaptcha
        $options[] = array(
            'type'    => 'heading',
            'content' => esc_html__( 'Recaptcha Notices', WP_ULIKE_PRO_DOMAIN)
        );

        $options[] = array(
            'id'      => 'missing_input_secret_notice',
            'type'    => 'text',
            'default' => esc_html__( 'The secret parameter is missing.', WP_ULIKE_PRO_DOMAIN ),
            'title'   => esc_html__( 'Recaptcha Missing Secret.', WP_ULIKE_PRO_DOMAIN)
        );

        $options[] = array(
            'id'      => 'invalid_input_secret_notice',
            'type'    => 'text',
            'default' => esc_html__( 'The secret parameter is invalid or malformed.', WP_ULIKE_PRO_DOMAIN ),
            'title'   => esc_html__( 'Recaptcha Invalid Secret.', WP_ULIKE_PRO_DOMAIN)
        );

        $options[] = array(
            'id'      => 'missing_input_response_notice',
            'type'    => 'text',
            'default' => esc_html__( 'Please confirm you are not a robot', WP_ULIKE_PRO_DOMAIN ),
            'title'   => esc_html__( 'Recaptcha Missing Input.', WP_ULIKE_PRO_DOMAIN)
        );

        $options[] = array(
            'id'      => 'invalid_input_response_notice',
            'type'    => 'text',
            'default' => esc_html__( 'The response parameter is invalid or malformed.', WP_ULIKE_PRO_DOMAIN ),
            'title'   => esc_html__( 'Recaptcha Invalid Input.', WP_ULIKE_PRO_DOMAIN)
        );

        $options[] = array(
            'id'      => 'bad_request_notice',
            'type'    => 'text',
            'default' => esc_html__( 'The request is invalid or malformed.', WP_ULIKE_PRO_DOMAIN ),
            'title'   => esc_html__( 'Recaptcha Bad Request.', WP_ULIKE_PRO_DOMAIN)
        );

        $options[] = array(
            'id'      => 'timeout_or_duplicate_notice',
            'type'    => 'text',
            'default' => esc_html__( 'The response is no longer valid: either is too old or has been used previously.', WP_ULIKE_PRO_DOMAIN ),
            'title'   => esc_html__( 'The response is no longer valid.', WP_ULIKE_PRO_DOMAIN)
        );

        $options[] = array(
            'id'      => 'undefined_notice',
            'type'    => 'text',
            'default' => esc_html__( 'Undefined reCAPTCHA error.', WP_ULIKE_PRO_DOMAIN ),
            'title'   => esc_html__( 'Undefined reCAPTCHA error.', WP_ULIKE_PRO_DOMAIN)
        );

        $options[] = array(
            'id'      => 'score_notice',
            'type'    => 'text',
            'default' => esc_html__( 'It is very likely a bot.', WP_ULIKE_PRO_DOMAIN ),
            'title'   => esc_html__( 'It is very likely a bot.', WP_ULIKE_PRO_DOMAIN)
        );

        return $options;
    }

    public function forms_section( $options ){
        // Check license permission
        if( ! $this->has_permission ){
            return $this->get_permission_notice();
        }

        return array(
            array(
                'type'    => 'submessage',
                'style'   => 'info',
                'content' => 'When you activate WP ULike Pro the plugin will install some default pages which are required for the plugin to work correctly. These pages include shortcodes that are used to display profile pages, edit account info, login, register and reset password forms.<br>You can edit these pages at any time or use the following shortcodes on a custom page. Just note that after changing the pages, you have to select and save the new path in the relevant options.<br><br><code>[wp_ulike_pro_login_form]</code> <code>[wp_ulike_pro_signup_form]</code> <code>[wp_ulike_pro_reset_password_form]</code>  <code>[wp_ulike_pro_account_form]</code>'
            ),
            array(
                'type'    => 'submessage',
                'style'   => 'warning',
                'content' => esc_html__('Warning: Please never cache pages "Login", "Password Reset", "Register", "Edit Account", "User Profiles". These pages may work wrong if cached.', WP_ULIKE_PRO_DOMAIN)
            ),
            array(
                'id'      => 'login_core_page',
                'type'    => 'select',
                'chosen'  => true,
                'ajax'    => true,
                'title'   => esc_html__('Select Login Page', WP_ULIKE_PRO_DOMAIN),
                'options' => 'pages'
            ),
            array(
                'id'         => 'login_custom_redirect',
                'type'       => 'text',
                'title'      => esc_html__( 'Login Redirect URL', WP_ULIKE_PRO_DOMAIN),
                'dependency' => array( 'login_core_page', '!=', '' )
            ),
            array(
                'id'         => 'logout_custom_redirect',
                'type'       => 'text',
                'title'      => esc_html__( 'Logout Redirect URL', WP_ULIKE_PRO_DOMAIN),
                'dependency' => array( 'login_core_page', '!=', '' )
            ),
            array(
                'id'         => 'enable_wp_login_redirect',
                'type'       => 'switcher',
                'default'    => false,
                'title'      => esc_html__('Redirect WordPress Default Login', WP_ULIKE_PRO_DOMAIN),
                'desc'       => esc_html__( 'You can easily replace default WordPress login page with your own custom login page.', WP_ULIKE_PRO_DOMAIN ),
                'dependency' => array( 'login_core_page', '!=', '' )
            ),
            array(
                'id'      => 'signup_core_page',
                'type'    => 'select',
                'chosen'  => true,
                'ajax'    => true,
                'title'   => esc_html__('Select Signup Page', WP_ULIKE_PRO_DOMAIN),
                'options' => 'pages'
            ),
            array(
                'id'         => 'signup_custom_redirect',
                'type'       => 'text',
                'title'      => esc_html__( 'Signup Redirect URL', WP_ULIKE_PRO_DOMAIN),
                'dependency' => array( 'signup_core_page', '!=', '' )
            ),
            array(
                'id'         => 'signup_enable_auto_login',
                'type'       => 'switcher',
                'default'    => false,
                'title'      => esc_html__('Enable Auto Login After Signup', WP_ULIKE_PRO_DOMAIN),
                'dependency' => array( 'signup_core_page', '!=', '' )
            ),
            array(
                'id'      => 'reset_password_core_page',
                'type'    => 'select',
                'chosen'  => true,
                'ajax'    => true,
                'title'   => esc_html__('Select Reset Password Page', WP_ULIKE_PRO_DOMAIN),
                'options' => 'pages'
            ),
            array(
                'id'      => 'edit_account_core_page',
                'type'    => 'select',
                'chosen'  => true,
                'ajax'    => true,
                'title'   => esc_html__('Select Edit Account Page', WP_ULIKE_PRO_DOMAIN),
                'options' => 'pages'
            ),
            array(
                'id'            => 'logged_in_message',
                'type'          => 'wp_editor',
                'tinymce'       => false,
                'media_buttons' => false,
                'quicktags'     => true,
                'sanitize'      => false,
                'default'       => '<div class="ulp-avatar"><img src="{avatar_url}"></div> <span>Logged in as {display_name}. (<a href="{profile_url}">Profile</a>) (<a href="{logout_url}">Logout</a>)</span>',
                'title'         => esc_html__( 'Logged In Message', WP_ULIKE_PRO_DOMAIN)
            ),
            array(
                'id'      => 'enable_2fa',
                'type'    => 'switcher',
                'default' => false,
                'title'   => esc_html__('Enable 2-factor Authentication', WP_ULIKE_PRO_DOMAIN),
                'desc'    => esc_html__( 'Increase security for user accounts by using multiple authentication steps.', WP_ULIKE_PRO_DOMAIN )
                . '<br>'  . sprintf( '<em>' .  esc_html__( 'After activating this option, put the %s shortcode in the profile tabs or any another page.', WP_ULIKE_PRO_DOMAIN ) . '</em>', "<code>[wp_ulike_pro_two_factor_setup]</code>")
            ),
            array(
                'id'      => 'enable_recaptcha',
                'type'    => 'switcher',
                'default' => false,
                'title'   => esc_html__('Enable Google reCAPTCHA', WP_ULIKE_PRO_DOMAIN),
                'desc'    => esc_html__( 'Turn on or off your Google reCAPTCHA on your site registration, login and reset password forms by default.', WP_ULIKE_PRO_DOMAIN ),
            ),
            array(
                'id'         => 'global_recaptcha',
                'type'       => 'switcher',
                'default'    => false,
                'title'      => esc_html__('Enable Global reCAPTCHA Scripts', WP_ULIKE_PRO_DOMAIN),
                'desc'       => esc_html__( 'Enable this option if you use the Recaptcha gobally on all pages. Like when you use it on Modal login forms.', WP_ULIKE_PRO_DOMAIN ),
                'dependency' => array( 'enable_recaptcha', '==', 'true' )
            ),
            array(
                'id'      => 'recaptcha_version',
                'type'    => 'select',
                'desc'    => esc_html__( 'Choose the type of reCAPTCHA for this site key. A site key only works with a single reCAPTCHA site type.', WP_ULIKE_PRO_DOMAIN ),
                'default' => 'v3',
                'title'   => esc_html__('reCAPTCHA type', WP_ULIKE_PRO_DOMAIN),
                'options' => array(
                    'v2' => esc_html__( 'reCAPTCHA v2', WP_ULIKE_PRO_DOMAIN ),
                    'v3' => esc_html__( 'reCAPTCHA v3', WP_ULIKE_PRO_DOMAIN ),
                ),
                'dependency' => array( 'enable_recaptcha', '==', 'true' )
            ),

            /* reCAPTCHA v3 */
            array(
                'id'         => 'v3_recaptcha_sitekey',
                'type'       => 'text',
                'title'      => esc_html__( 'Site Key', WP_ULIKE_PRO_DOMAIN ),
                'desc'       => __( 'You can register your site and generate a site key via <a href="https://www.google.com/recaptcha/">Google reCAPTCHA</a>', WP_ULIKE_PRO_DOMAIN ),
                'dependency' => array( 'recaptcha_version|enable_recaptcha', '==|==', 'v3|true' )
            ),
            array(
                'id'         => 'v3_recaptcha_secretkey',
                'type'       => 'text',
                'title'      => esc_html__( 'Secret Key', WP_ULIKE_PRO_DOMAIN ),
                'desc'       => __( 'Keep this a secret. You can get your secret key via <a href="https://www.google.com/recaptcha/">Google reCAPTCHA</a>', WP_ULIKE_PRO_DOMAIN ),
                'dependency' => array( 'recaptcha_version|enable_recaptcha', '==|==', 'v3|true' )
            ),

            /* reCAPTCHA v2 */

            array(
                'id'         => 'v2_recaptcha_sitekey',
                'type'       => 'text',
                'title'      => esc_html__( 'Site Key', WP_ULIKE_PRO_DOMAIN ),
                'desc'       => __( 'You can register your site and generate a site key via <a href="https://www.google.com/recaptcha/">Google reCAPTCHA</a>', WP_ULIKE_PRO_DOMAIN ),
                'dependency' => array( 'recaptcha_version|enable_recaptcha', '==|==', 'v2|true' )
            ),
            array(
                'id'         => 'v2_recaptcha_secretkey',
                'type'       => 'text',
                'title'      => esc_html__( 'Secret Key', WP_ULIKE_PRO_DOMAIN ),
                'desc'       => __( 'Keep this a secret. You can get your secret key via <a href="https://www.google.com/recaptcha/">Google reCAPTCHA</a>', WP_ULIKE_PRO_DOMAIN ),
                'dependency' => array( 'recaptcha_version|enable_recaptcha', '==|==', 'v2|true' )
            ),
            array(
                'id'      => 'v2_recaptcha_type',
                'type'    => 'select',
                'default' => 'image',
                'title'   => esc_html__( 'Type', WP_ULIKE_PRO_DOMAIN ),
                'desc'    => esc_html__( 'The type of reCAPTCHA to serve.', WP_ULIKE_PRO_DOMAIN ),
                'options' => array(
                    'audio' => esc_html__( 'Audio', WP_ULIKE_PRO_DOMAIN ),
                    'image' => esc_html__( 'Image', WP_ULIKE_PRO_DOMAIN ),
                ),
                'dependency' => array( 'recaptcha_version|enable_recaptcha', '==|==', 'v2|true' )
            ),
            array(
                'id'      => 'v2_recaptcha_language_code',
                'type'    => 'select',
                'default' => 'en',
                'title'   => esc_html__( 'Language', WP_ULIKE_PRO_DOMAIN ),
                'desc'    => esc_html__( 'Select the language to be used in your reCAPTCHA.', WP_ULIKE_PRO_DOMAIN ),
                'options'     => array(
                    'ar'     => 'Arabic',
                    'af'     => 'Afrikaans',
                    'am'     => 'Amharic',
                    'hy'     => 'Armenian',
                    'az'     => 'Azerbaijani',
                    'eu'     => 'Basque',
                    'bn'     => 'Bengali',
                    'bg'     => 'Bulgarian',
                    'ca'     => 'Catalan',
                    'zh-HK'  => 'Chinese (Hong Kong)',
                    'zh-CN'  => 'Chinese (Simplified)',
                    'zh-TW'  => 'Chinese (Traditional)',
                    'hr'     => 'Croatian',
                    'cs'     => 'Czech',
                    'da'     => 'Danish',
                    'nl'     => 'Dutch',
                    'en-GB'  => 'English (UK)',
                    'en'     => 'English (US)',
                    'et'     => 'Estonian',
                    'fil'    => 'Filipino',
                    'fi'     => 'Finnish',
                    'fr'     => 'French',
                    'fr-CA'  => 'French (Canadian)',
                    'gl'     => 'Galician',
                    'ka'     => 'Georgian',
                    'de'     => 'German',
                    'de-AT'  => 'German (Austria)',
                    'de-CH'  => 'German (Switzerland)',
                    'el'     => 'Greek',
                    'gu'     => 'Gujarati',
                    'iw'     => 'Hebrew',
                    'hi'     => 'Hindi',
                    'hu'     => 'Hungarain',
                    'is'     => 'Icelandic',
                    'id'     => 'Indonesian',
                    'it'     => 'Italian',
                    'ja'     => 'Japanese',
                    'kn'     => 'Kannada',
                    'ko'     => 'Korean',
                    'lo'     => 'Laothian',
                    'lv'     => 'Latvian',
                    'lt'     => 'Lithuanian',
                    'ms'     => 'Malay',
                    'ml'     => 'Malayalam',
                    'mr'     => 'Marathi',
                    'mn'     => 'Mongolian',
                    'no'     => 'Norwegian',
                    'fa'     => 'Persian',
                    'pl'     => 'Polish',
                    'pt'     => 'Portuguese',
                    'pt-BR'  => 'Portuguese (Brazil)',
                    'pt-PT'  => 'Portuguese (Portugal)',
                    'ro'     => 'Romanian',
                    'ru'     => 'Russian',
                    'sr'     => 'Serbian',
                    'si'     => 'Sinhalese',
                    'sk'     => 'Slovak',
                    'sl'     => 'Slovenian',
                    'es'     => 'Spanish',
                    'es-419' => 'Spanish (Latin America)',
                    'sw'     => 'Swahili',
                    'sv'     => 'Swedish',
                    'ta'     => 'Tamil',
                    'te'     => 'Telugu',
                    'th'     => 'Thai',
                    'tr'     => 'Turkish',
                    'uk'     => 'Ukrainian',
                    'ur'     => 'Urdu',
                    'vi'     => 'Vietnamese',
                    'zu'     => 'Zulu'
                ),
                'dependency' => array( 'recaptcha_version|enable_recaptcha', '==|==', 'v2|true' )
            ),
            array(
                'id'      => 'v2_recaptcha_theme',
                'type'    => 'select',
                'default' => 'light',
                'title'   => esc_html__( 'Theme',WP_ULIKE_PRO_DOMAIN ),
                'desc'    => esc_html__( 'Select a color theme of the widget.', WP_ULIKE_PRO_DOMAIN ),
                'options' => array(
                    'dark'  => esc_html__( 'Dark', WP_ULIKE_PRO_DOMAIN ),
                    'light' => esc_html__( 'Light', WP_ULIKE_PRO_DOMAIN ),
                ),
                'dependency' => array( 'recaptcha_version|enable_recaptcha', '==|==', 'v2|true' )
            ),
            array(
                'id'      => 'v2_recaptcha_size',
                'type'    => 'select',
                'default' => 'normal',
                'title'   => esc_html__( 'Size', WP_ULIKE_PRO_DOMAIN ),
                'desc'    => esc_html__( 'The type of reCAPTCHA to serve.', WP_ULIKE_PRO_DOMAIN ),
                'options' => array(
                    'compact'   => esc_html__( 'Compact', WP_ULIKE_PRO_DOMAIN ),
                    'normal'    => esc_html__( 'Normal', WP_ULIKE_PRO_DOMAIN )
                ),
                'dependency' => array( 'recaptcha_version|enable_recaptcha', '==|==', 'v2|true' )
            )
        );
    }

    /**
     * Update social login section in setting panel
     *
     * @param array $options
     * @return array
     */
    public function social_login( $options ){
        // Check license permission
        if( ! $this->has_permission ){
            return $this->get_permission_notice();
        }

        return array(
            array(
                'type'    => 'submessage',
                'style'   => 'info',
                'content' => esc_html__('Enhance your user experience by integrating seamless social logins on your platform. Whether you prefer to auto-display login options or manually place them with our specialized following shortcode, this panel provides a streamlined configuration process to get you set up swiftly. Dive in and optimize your user onboarding today!', WP_ULIKE_PRO_DOMAIN) .
                '<br><br><code>[wp_ulike_pro_social_login]</code>'
            ),
            array(
                'id'         => 'enable_social_login',
                'type'       => 'switcher',
                'default'    => false,
                'title'      => esc_html__('Enable Social Logins', WP_ULIKE_PRO_DOMAIN)
            ),
            array(
                'id'         => 'social_logins',
                'type'       => 'group',
                'title'      => esc_html__('Social networks', WP_ULIKE_PRO_DOMAIN),
                'dependency' => array( 'enable_social_login', '==', 'true' ),
                'fields'     => array(
                    array(
                        'id'       => 'network',
                        'type'     => 'select',
                        'title'    => esc_html__( 'Network', WP_ULIKE_PRO_DOMAIN),
                        'chosen'   => true,
                        'multiple' => false,
                        'options'  => array(
                            'Facebook'  => esc_html__( 'Facebook', WP_ULIKE_PRO_DOMAIN),
                            'GitHub'    => esc_html__( 'GitHub', WP_ULIKE_PRO_DOMAIN),
                            'Google'    => esc_html__( 'Google', WP_ULIKE_PRO_DOMAIN),
                            'Twitter'   => esc_html__( 'Twitter', WP_ULIKE_PRO_DOMAIN),
                            'Amazon'    => esc_html__( 'Amazon', WP_ULIKE_PRO_DOMAIN),
                            'LinkedIn'  => esc_html__( 'LinkedIn', WP_ULIKE_PRO_DOMAIN),
                            'Apple'     => esc_html__( 'Apple', WP_ULIKE_PRO_DOMAIN),
                            'WordPress' => esc_html__( 'WordPress', WP_ULIKE_PRO_DOMAIN),
                            'Yahoo'     => esc_html__( 'Yahoo', WP_ULIKE_PRO_DOMAIN),
                            'Slack'     => esc_html__( 'Slack', WP_ULIKE_PRO_DOMAIN),
                            'Medium'    => esc_html__( 'Medium', WP_ULIKE_PRO_DOMAIN),
                            'Dribbble'  => esc_html__( 'Dribbble', WP_ULIKE_PRO_DOMAIN),
                            'Paypal'    => esc_html__( 'Paypal', WP_ULIKE_PRO_DOMAIN)
                        )
                    ),
                    array(
                        'id'    => 'login_label',
                        'type'  => 'text',
                        'title' => esc_html__( 'Login Button Text',WP_ULIKE_PRO_DOMAIN),
                        'desc'  => esc_html__('Controls the text displayed on the login button.', WP_ULIKE_PRO_DOMAIN)
                    ),
                    array(
                        'id'    => 'link_label',
                        'type'  => 'text',
                        'title' => esc_html__( 'Link Button Text',WP_ULIKE_PRO_DOMAIN),
                        'desc'  => esc_html__( 'Controls the text displayed on the link account button.',WP_ULIKE_PRO_DOMAIN)
                    ),
                    array(
                        'id'    => 'key',
                        'type'  => 'text',
                        'title' => esc_html__( 'Client ID',WP_ULIKE_PRO_DOMAIN),
                        'desc'  => esc_html__('Your app ID', WP_ULIKE_PRO_DOMAIN)
                    ),
                    array(
                        'id'    => 'secret',
                        'type'  => 'text',
                        'title' => esc_html__( 'Client Secret', WP_ULIKE_PRO_DOMAIN ),
                        'desc'  => esc_html__('Your app secret', WP_ULIKE_PRO_DOMAIN)
                    ),
                    array(
                        'id'      => 'disable',
                        'type'    => 'switcher',
                        'default' => false,
                        'title'   => esc_html__('Disable Social Connect', WP_ULIKE_PRO_DOMAIN),
                    ),
                    array(
                        'type'       => 'submessage',
                        'style'      => 'normal',
                        'content'    => esc_html__( 'The redirect URI is:', WP_ULIKE_PRO_DOMAIN) . ' <code>' . WP_Ulike_Pro_Permalinks::get_social_login_callback_url( 'Facebook' ) . '</code>',
                        'dependency' => array( 'network', '==', 'Facebook' )
                    ),
                    array(
                        'type'       => 'submessage',
                        'style'      => 'normal',
                        'content'    => esc_html__( 'The redirect URI is:', WP_ULIKE_PRO_DOMAIN) . ' <code>' . WP_Ulike_Pro_Permalinks::get_social_login_callback_url( 'GitHub' ) . '</code>',
                        'dependency' => array( 'network', '==', 'GitHub' )
                    ),
                    array(
                        'type'       => 'submessage',
                        'style'      => 'normal',
                        'content'    => esc_html__( 'The redirect URI is:', WP_ULIKE_PRO_DOMAIN) . ' <code>' . WP_Ulike_Pro_Permalinks::get_social_login_callback_url( 'Google' ) . '</code>',
                        'dependency' => array( 'network', '==', 'Google' )
                    ),
                    array(
                        'type'       => 'submessage',
                        'style'      => 'normal',
                        'content'    => esc_html__( 'The redirect URI is:', WP_ULIKE_PRO_DOMAIN) . ' <code>' . WP_Ulike_Pro_Permalinks::get_social_login_callback_url( 'Twitter' ) . '</code>',
                        'dependency' => array( 'network', '==', 'Twitter' )
                    ),
                    array(
                        'type'       => 'submessage',
                        'style'      => 'normal',
                        'content'    => esc_html__( 'The redirect URI is:', WP_ULIKE_PRO_DOMAIN) . ' <code>' . WP_Ulike_Pro_Permalinks::get_social_login_callback_url( 'Amazon' ) . '</code>',
                        'dependency' => array( 'network', '==', 'Amazon' )
                    ),
                    array(
                        'type'       => 'submessage',
                        'style'      => 'normal',
                        'content'    => esc_html__( 'The redirect URI is:', WP_ULIKE_PRO_DOMAIN) . ' <code>' . WP_Ulike_Pro_Permalinks::get_social_login_callback_url( 'LinkedIn' ) . '</code>',
                        'dependency' => array( 'network', '==', 'LinkedIn' )
                    ),
                    array(
                        'type'       => 'submessage',
                        'style'      => 'normal',
                        'content'    => esc_html__( 'The redirect URI is:', WP_ULIKE_PRO_DOMAIN) . ' <code>' . WP_Ulike_Pro_Permalinks::get_social_login_callback_url( 'Paypal' ) . '</code>',
                        'dependency' => array( 'network', '==', 'Paypal' )
                    ),
                    array(
                        'type'       => 'submessage',
                        'style'      => 'normal',
                        'content'    => esc_html__( 'The redirect URI is:', WP_ULIKE_PRO_DOMAIN) . ' <code>' . WP_Ulike_Pro_Permalinks::get_social_login_callback_url( 'Apple' ) . '</code>',
                        'dependency' => array( 'network', '==', 'Apple' )
                    ),
                    array(
                        'type'       => 'submessage',
                        'style'      => 'normal',
                        'content'    => esc_html__( 'The redirect URI is:', WP_ULIKE_PRO_DOMAIN) . ' <code>' . WP_Ulike_Pro_Permalinks::get_social_login_callback_url( 'WordPress' ) . '</code>',
                        'dependency' => array( 'network', '==', 'WordPress' )
                    ),
                    array(
                        'type'       => 'submessage',
                        'style'      => 'normal',
                        'content'    => esc_html__( 'The redirect URI is:', WP_ULIKE_PRO_DOMAIN) . ' <code>' . WP_Ulike_Pro_Permalinks::get_social_login_callback_url( 'Yahoo' ) . '</code>',
                        'dependency' => array( 'network', '==', 'Yahoo' )
                    ),
                    array(
                        'type'       => 'submessage',
                        'style'      => 'normal',
                        'content'    => esc_html__( 'The redirect URI is:', WP_ULIKE_PRO_DOMAIN) . ' <code>' . WP_Ulike_Pro_Permalinks::get_social_login_callback_url( 'Slack' ) . '</code>',
                        'dependency' => array( 'network', '==', 'Slack' )
                    ),
                    array(
                        'type'       => 'submessage',
                        'style'      => 'normal',
                        'content'    => esc_html__( 'The redirect URI is:', WP_ULIKE_PRO_DOMAIN) . ' <code>' . WP_Ulike_Pro_Permalinks::get_social_login_callback_url( 'Medium' ) . '</code>',
                        'dependency' => array( 'network', '==', 'Medium' )
                    ),
                    array(
                        'type'       => 'submessage',
                        'style'      => 'normal',
                        'content'    => esc_html__( 'The redirect URI is:', WP_ULIKE_PRO_DOMAIN) . ' <code>' . WP_Ulike_Pro_Permalinks::get_social_login_callback_url( 'Dribbble' ) . '</code>',
                        'dependency' => array( 'network', '==', 'Dribbble' )
                    ),
                )
            ),
            array(
                'id'      => 'social_login_view',
                'type'    => 'button_set',
                'default' => 'icon_text',
                'title'   => esc_html__( 'View', WP_ULIKE_PRO_DOMAIN),
                'options' => array(
                    'icon_text' => esc_html__( 'Icon & Text', WP_ULIKE_PRO_DOMAIN),
                    'icon'      => esc_html__( 'Icon', WP_ULIKE_PRO_DOMAIN),
                    'text'      => esc_html__( 'Text', WP_ULIKE_PRO_DOMAIN)
                ),
                'dependency' => array( 'enable_social_login', '==', 'true' )
            ),
            array(
                'id'      => 'social_login_skin',
                'type'    => 'button_set',
                'default' => 'gradient',
                'title'   => esc_html__( 'Skin', WP_ULIKE_PRO_DOMAIN),
                'options' => array(
                    'gradient' => esc_html__( 'Gradient', WP_ULIKE_PRO_DOMAIN),
                    'minimal'  => esc_html__( 'Minimal', WP_ULIKE_PRO_DOMAIN),
                    'framed'   => esc_html__( 'Framed', WP_ULIKE_PRO_DOMAIN),
                    'boxed'    => esc_html__( 'Boxed', WP_ULIKE_PRO_DOMAIN),
                    'flat'     => esc_html__( 'Flat', WP_ULIKE_PRO_DOMAIN)
                ),
                'dependency' => array( 'enable_social_login', '==', 'true' )
            ),
            array(
                'id'      => 'social_login_shape',
                'type'    => 'button_set',
                'title'   => esc_html__( 'Shape', WP_ULIKE_PRO_DOMAIN),
                'default' => 'rounded',
                'options' => array(
                    'square'  => esc_html__( 'Square', WP_ULIKE_PRO_DOMAIN),
                    'rounded' => esc_html__( 'Rounded', WP_ULIKE_PRO_DOMAIN),
                    'circle'  => esc_html__( 'Circle', WP_ULIKE_PRO_DOMAIN)
                ),
                'dependency' => array( 'enable_social_login', '==', 'true' )
            ),
            array(
                'id'      => 'social_login_color',
                'type'    => 'button_set',
                'default' => 'official',
                'title'   => esc_html__( 'Color', WP_ULIKE_PRO_DOMAIN),
                'options' => array(
                    'official' => esc_html__( 'Official', WP_ULIKE_PRO_DOMAIN),
                    'custom'   => esc_html__( 'Custom', WP_ULIKE_PRO_DOMAIN)
                ),
                'dependency' => array( 'enable_social_login', '==', 'true' )
            ),
            array(
                'id'         => 'social_login_layout',
                'type'       => 'fieldset',
                'title'      => esc_html__('Layout', WP_ULIKE_PRO_DOMAIN),
                'fields'     => $this->responsive_width_fields( '12', '12', '12' ),
                'dependency' => array( 'enable_social_login', '==', 'true' )
            ),
            array(
                'id'      => 'social_login_auto_display',
                'type'    => 'radio',
                'title'   => esc_html__( 'Auto Display', WP_ULIKE_PRO_DOMAIN),
                'default' => 'after_login_form',
                'options' => array(
                    'none'              => esc_html__( 'None', WP_ULIKE_PRO_DOMAIN),
                    'after_login_form'  => esc_html__( 'After Login Form', WP_ULIKE_PRO_DOMAIN),
                    'before_login_form' => esc_html__( 'Before Login Form', WP_ULIKE_PRO_DOMAIN),
                    'custom_hook'       => esc_html__( 'Cutom Hook', WP_ULIKE_PRO_DOMAIN)
                ),
                'dependency' => array( 'enable_social_login', '==', 'true' )
            ),
            array(
                'id'         => 'social_login_auto_custom_hook',
                'type'       => 'text',
                'title'      => esc_html__( 'Enter Hook Name',WP_ULIKE_PRO_DOMAIN),
                'desc'       => esc_html__('Please enter your desired action name in this field so that the social buttons are automatically displayed there.', WP_ULIKE_PRO_DOMAIN),
                'dependency' => array( 'enable_social_login|social_login_auto_display', '==|==', 'true|custom_hook' )
            ),
            array(
                'id'            => 'social_login_before',
                'type'          => 'wp_editor',
                'tinymce'       => false,
                'media_buttons' => false,
                'quicktags'     => true,
                'sanitize'      => false,
                'default'       => '<div style="align-items: center; display: flex; flex: 0 0 auto; justify-content: center; width:100%; margin:30px 0;"> <div style="border-top: 1px solid #b2b2b2;flex: 1 1 auto;"></div> <div style="margin: 0 10px;">OR</div> <div style="border-top: 1px solid #b2b2b2;flex: 1 1 auto;"></div> </div>',
                'title'         => esc_html__( 'Before Content',WP_ULIKE_PRO_DOMAIN),
                'dependency'    => array( 'enable_social_login', '==', 'true' )
            ),
            array(
                'id'            => 'social_login_after',
                'type'          => 'wp_editor',
                'tinymce'       => false,
                'media_buttons' => false,
                'quicktags'     => true,
                'sanitize'      => false,
                'title'         => esc_html__( 'After Content',WP_ULIKE_PRO_DOMAIN),
                'dependency'    => array( 'enable_social_login', '==', 'true' )
            )
        );
    }


    /**
     * Update rest api section in setting panel
     *
     * @param array $options
     * @return array
     */
    public function profiles_section( $options ){
        // Check license permission
        if( ! $this->has_permission ){
            return $this->get_permission_notice();
        }

        return  array(
            array(
                'type'    => 'submessage',
                'style'   => 'info',
                'content' => 'This section deals with the appearance settings and some rules of the user profile page. Here\'s how it works: After activating the plugin, a page titled "User Profile" is added in which the <strong>[wp_ulike_pro_completeness_profile]</strong> shortcode is placed. You can edit the URL of this page at any time and then return to this panel and update it in the "Select Profile Page" option. This way you can create your custom path for user profiles. e.g.<br><br>
                <strong>example.com/user-profiles/john</strong><br>
                <strong>example.com/account/nick</strong><br>
                <strong>example.com/user/emily</strong><br><br>
                We have tried to design the options in such a way that you have the most flexibility and you can customize the profiles to your liking.<br><br>' . sprintf(
                    '<a href="%s" title="Documents" target="_blank">%s</a>',
                    'https://docs.wpulike.com/article/16-profiles-settings',
                    esc_html__( 'Read More', WP_ULIKE_PRO_DOMAIN )
                ),
            ),
            array(
                'id'      => 'enable_user_profiles',
                'type'    => 'switcher',
                'default' => false,
                'title'   => esc_html__('Enable User Profiles', WP_ULIKE_PRO_DOMAIN),
            ),
            array(
                'id'         => 'user_profiles_core_page',
                'type'       => 'select',
                'chosen'     => true,
                'ajax'       => true,
                'title'      => esc_html__('Select Profile Page', WP_ULIKE_PRO_DOMAIN),
                'options'    => 'pages',
                'dependency' => array( 'enable_user_profiles', '==', 'true' )
            ),
            array(
                'id'         => 'user_profiles_permalink_base',
                'type'       => 'select',
                'default'    => 'user_login',
                'desc'       => esc_html__('Here you can control the permalink structure of the user profile URL globally.', WP_ULIKE_PRO_DOMAIN),
                'title'      => esc_html__('Profile Permalink Base', WP_ULIKE_PRO_DOMAIN),
                'options'    => array(
                    'user_login' => esc_html__( 'Username', WP_ULIKE_PRO_DOMAIN ),
                    'name'       => esc_html__( 'First and Last Name with \'.\'', WP_ULIKE_PRO_DOMAIN ),
                    'name_dash'  => esc_html__( 'First and Last Name with \'-\'', WP_ULIKE_PRO_DOMAIN ),
                    'name_plus'  => esc_html__( 'First and Last Name with \'+\'', WP_ULIKE_PRO_DOMAIN ),
                    'user_id'    => esc_html__( 'User ID', WP_ULIKE_PRO_DOMAIN )
                ),
                'dependency' => array( 'enable_user_profiles', '==', 'true' )
            ),
            array(
                'id'         => 'enable_author_redirect',
                'type'       => 'switcher',
                'default'    => false,
                'title'      => esc_html__('Redirect author page to their profile', WP_ULIKE_PRO_DOMAIN),
                'dependency' => array( 'enable_user_profiles', '==', 'true' )
            ),
            array(
                'id'         => 'user_profiles_access',
                'type'       => 'select',
                'default'    => 'everyone',
                'title'      => esc_html__('User Profile Access', WP_ULIKE_PRO_DOMAIN),
                'options'    => array(
                    'everyone'        => esc_html__('Everyone', WP_ULIKE_PRO_DOMAIN),
                    'logged_in_users' => esc_html__('Logged In Users', WP_ULIKE_PRO_DOMAIN)
                ),
                'dependency' => array( 'enable_user_profiles', '==', 'true' )
            ),
            array(
                'id'         => 'user_custom_redirect',
                'type'       => 'text',
                'default'    => home_url(),
                'title'      => esc_html__( 'Custom Redirect URL', WP_ULIKE_PRO_DOMAIN),
                'dependency' => array( 'enable_user_profiles|user_profiles_access', '==|==', 'true|logged_in_users' )
            ),
            array(
                'id'         => 'user_restrict_profile_owner',
                'type'       => 'switcher',
                'default'    => false,
                'title'      => esc_html__('Show only for profile owner', WP_ULIKE_PRO_DOMAIN),
                'desc'       => esc_html__('Restrict access to profiles only for the account holder. (In case of discrepancies, users will be redirected to their own profile.)', WP_ULIKE_PRO_DOMAIN),
                'dependency' => array( 'enable_user_profiles|user_profiles_access', '==|==', 'true|logged_in_users' )
            ),
            array(
                'id'         => 'user_restrict_exclusive_roles',
                'type'       => 'checkbox',
                'title'      => esc_html__('Exclusive Roles', WP_ULIKE_PRO_DOMAIN),
                'desc'       => esc_html__('Specify all user roles that can access the profile except the account holder.', WP_ULIKE_PRO_DOMAIN),
                'options'    => wp_ulike_pro_get_user_roles_list( array( 'Subscriber' ) ),
                'default'    => array( 'administrator' ),
                'dependency' => array( 'enable_user_profiles|user_profiles_access|user_restrict_profile_owner', '==|==|==', 'true|logged_in_users|true' )
            ),
            array(
                'id'            => 'user_profiles_appearance',
                'type'          => 'tabbed',
                'title'         => esc_html__('Profile Appearance', WP_ULIKE_PRO_DOMAIN),
                'dependency'    => array( 'enable_user_profiles', '==', 'true' ),
                'tabs'          => array(
                    array(
                    'title'     => esc_html__('User Info', WP_ULIKE_PRO_DOMAIN),
                    'fields'    => array(
                            array(
                                'id'      => 'display_avatar',
                                'type'    => 'switcher',
                                'default' => true,
                                'title'   => esc_html__('Show user avatar', WP_ULIKE_PRO_DOMAIN),
                            ),
                            array(
                                'id'         => 'avatar_size',
                                'type'       => 'spinner',
                                'title'      => esc_html__('Avatar image size', WP_ULIKE_PRO_DOMAIN),
                                'step'       => 2,
                                'min'        => 32,
                                'max'        => 512,
                                'unit'       => 'px',
                                'default'    => 200,
                                'dependency' => array( 'display_avatar', '==', 'true' )
                            ),
                            array(
                                'id'      => 'display_info',
                                'type'    => 'switcher',
                                'default' => true,
                                'title'   => esc_html__('Show user info section', WP_ULIKE_PRO_DOMAIN),
                            ),
                            array(
                                'id'         => 'display_name',
                                'type'       => 'switcher',
                                'default'    => true,
                                'title'      => esc_html__('Show display name', WP_ULIKE_PRO_DOMAIN),
                                'dependency' => array( 'display_info', '==', 'true' )
                            ),
                            array(
                                'id'         => 'display_bio',
                                'type'       => 'switcher',
                                'default'    => true,
                                'title'      => esc_html__('Show user description', WP_ULIKE_PRO_DOMAIN),
                                'dependency' => array( 'display_info', '==', 'true' )
                            ),
                            array(
                                'id'         => 'display_custom_message',
                                'type'       => 'switcher',
                                'default'    => false,
                                'title'      => esc_html__('Show a custom message if user bio is empty', WP_ULIKE_PRO_DOMAIN),
                                'dependency' => array( 'display_bio|display_info', '==|==', 'true|true' )
                            ),
                            array(
                                'id'            => 'custom_message',
                                'type'          => 'wp_editor',
                                'title'         => esc_html__('Custom message', WP_ULIKE_PRO_DOMAIN),
                                'height'        => '100px',
                                'media_buttons' => false,
                                'tinymce'       => false,
                                'dependency'    => array( 'display_bio|display_custom_message|display_info', '==|==|==', 'true|true|true' )
                            ),
                            array(
                                'id'       => 'custom_html',
                                'type'     => 'code_editor',
                                'settings' => array(
                                    'theme' => 'shadowfox',
                                    'mode'  => 'htmlmixed',
                                ),
                                'title'    => esc_html__('Custom HTML', WP_ULIKE_PRO_DOMAIN),
                                'desc'     => esc_html__( 'A Custom HTML structure where you can display it at the bottom of the Info section. (This option also supports shortcode)', WP_ULIKE_PRO_DOMAIN),
                                'dependency'=> array( 'display_info', '==', 'true' ),
                            ),
                        )
                    ),
                    array(
                    'title'     => esc_html__('Badges', WP_ULIKE_PRO_DOMAIN),
                    'fields'    => array(
                        array(
                            'id'      => 'display_badges',
                            'type'    => 'switcher',
                            'default' => true,
                            'title'   => esc_html__('Show badges section', WP_ULIKE_PRO_DOMAIN),
                        ),
                        array(
                            'id'        => 'badges',
                            'type'      => 'group',
                            'title'     => esc_html__('Add Profile Badges', WP_ULIKE_PRO_DOMAIN),
                            'fields'    => array(
                                array(
                                    'id'         => 'badge_type',
                                    'type'       => 'button_set',
                                    'title'      => esc_html__( 'Badge Type', WP_ULIKE_PRO_DOMAIN),
                                    'options'    => array(
                                        'default' => esc_html__('Default', WP_ULIKE_PRO_DOMAIN),
                                        'custom'  => esc_html__('Custom', WP_ULIKE_PRO_DOMAIN)
                                    ),
                                    'default'    => 'default'
                                ),
                                array(
                                    'id'            => 'title',
                                    'type'          => 'wp_editor',
                                    'title'         => esc_html__('Title', WP_ULIKE_PRO_DOMAIN),
                                    'height'        => '85px',
                                    'dependency'    => array( 'badge_type', '==', 'default' ),
                                ),
                                array(
                                    'id'         => 'subtitle',
                                    'type'       => 'text',
                                    'title'      => esc_html__('Subtitle', WP_ULIKE_PRO_DOMAIN),
                                    'dependency' => array( 'badge_type', '==', 'default' ),
                                ),
                                array(
                                    'id'         => 'image',
                                    'type'       => 'media',
                                    'title'      => esc_html__('Image', WP_ULIKE_PRO_DOMAIN),
                                    'dependency' => array( 'badge_type', '==', 'default' ),
                                ),
                                array(
                                    'id'       => 'custom',
                                    'type'     => 'code_editor',
                                    'settings' => array(
                                        'theme' => 'shadowfox',
                                        'mode'  => 'htmlmixed',
                                    ),
                                    'title'      => esc_html__('Custom HTML', WP_ULIKE_PRO_DOMAIN),
                                    'desc'       => esc_html__( 'A Custom HTML structure where you can display it as a badge item. (This option also supports shortcode)', WP_ULIKE_PRO_DOMAIN),
                                    'dependency' => array( 'badge_type', '==', 'custom' ),
                                )
                            ),
                                'accordion_title_number' => true,
                                'dependency' => array( 'display_badges', '==', 'true' ),
                                'default'    => array(
                                    array(
                                        'badge_type' => 'default',
                                        'title'      => '[wp_ulike_pro_user_info status=like] Likes',
                                        'subtitle'   => 'Total up votes',
                                        'image'      => array(
                                            'url'    => WP_ULIKE_PRO_PUBLIC_URL . '/assets/img/svg/profile/thumb-up.svg',
                                            'width'  => 64,
                                            'height' => 64,
                                            'title'  => 'Total Likes',
                                        ),
                                    ),
                                    array(
                                        'badge_type' => 'default',
                                        'title'      => '[wp_ulike_pro_user_info status=dislike] Dislikes',
                                        'subtitle'   => 'Total down votes',
                                        'image'      => array(
                                            'url'    => WP_ULIKE_PRO_PUBLIC_URL . '/assets/img/svg/profile/thumb-down.svg',
                                            'width'  => 64,
                                            'height' => 64,
                                            'title'  => 'Total Disikes'
                                        ),
                                    ),
                                    array(
                                        'badge_type' => 'default',
                                        'title'      => '[wp_ulike_pro_user_info type=last_activity after_text=ago empty_text=Inactive]',
                                        'subtitle'   => 'Last Activity',
                                        'image'      => array(
                                            'url'    => WP_ULIKE_PRO_PUBLIC_URL . '/assets/img/svg/profile/history.svg',
                                            'width'  => 64,
                                            'height' => 64,
                                            'title'  => 'Last Activity'
                                        ),
                                    ),
                                ),
                            ),
                        )
                    ),
                    array(
                    'title'     => esc_html__('Tabs', WP_ULIKE_PRO_DOMAIN),
                    'fields'    => array(
                        array(
                            'id'      => 'display_tabs',
                            'type'    => 'switcher',
                            'default' => true,
                            'title'   => esc_html__('Show tabs section', WP_ULIKE_PRO_DOMAIN),
                        ),
                        array(
                            'id'         => 'tabs_side',
                            'type'       => 'button_set',
                            'title'      => esc_html__( 'Select Tabs Side', WP_ULIKE_PRO_DOMAIN),
                            'default'    => 'top',
                            'options'    => array(
                                'top'   => esc_html__('Top', WP_ULIKE_PRO_DOMAIN),
                                'left'  => esc_html__('Left', WP_ULIKE_PRO_DOMAIN),
                                'right' => esc_html__('Right', WP_ULIKE_PRO_DOMAIN)
                            )
                        ),
                        array(
                            'id'        => 'tabs',
                            'type'      => 'group',
                            'title'     => esc_html__('Add Profile Tabs', WP_ULIKE_PRO_DOMAIN),
                            'fields'    => array(
                                array(
                                    'id'      => 'title',
                                    'type'    => 'text',
                                    'title'   => esc_html__('Title', WP_ULIKE_PRO_DOMAIN)
                                ),
                                array(
                                    'id'     => 'content',
                                    'type'   => 'wp_editor',
                                    'title'  => esc_html__('Content', WP_ULIKE_PRO_DOMAIN),
                                    'height' => '100px',
                                    'desc'   => esc_html__( 'A simple HTML/Text structure where you can use different shortcodes.', WP_ULIKE_PRO_DOMAIN),
                                ),
                                array(
                                    'id'      => 'has_link',
                                    'type'    => 'link',
                                    'default' => false,
                                    'title'   => esc_html__('Create Link Tab', WP_ULIKE_PRO_DOMAIN),
                                    'desc'    => esc_html__('Using this option, you can add custom external link for profile nav tabs.', WP_ULIKE_PRO_DOMAIN),
                                ),
                                array(
                                    'id'      => 'restrict',
                                    'type'    => 'switcher',
                                    'default' => false,
                                    'title'   => esc_html__('Show only for profile owner', WP_ULIKE_PRO_DOMAIN),
                                )
                            ),
                            'dependency' => array( 'display_tabs', '==', 'true' ),
                            'default'    => array(
                                array(
                                    'title'   => 'Recent Posts',
                                    'content' => '[wp_ulike_pro_items type="post" status="like" limit="5" empty_text="No Results Found!"]',
                                ),
                                array(
                                    'title'   => 'Recent Comments',
                                    'content' => '[wp_ulike_pro_items type="comment" status="like" limit="5" empty_text="No Results Found!"]',
                                )
                            ),
                        ),
                        )
                    ),
                    array(
                    'title'     => esc_html__('Appearance', WP_ULIKE_PRO_DOMAIN),
                    'fields'    => array(
                            array(
                                'type'    => 'subheading',
                                'content' => esc_html__('Wrapper', WP_ULIKE_PRO_DOMAIN),
                            ),
                            array(
                                'id'         => 'header_wrapper_width',
                                'type'       => 'fieldset',
                                'title'      => esc_html__('Header column width', WP_ULIKE_PRO_DOMAIN),
                                'fields'     => $this->responsive_width_fields( '12', '12', '12' )
                            ),
                            array(
                                'id'         => 'tabs_wrapper_width',
                                'type'       => 'fieldset',
                                'title'      => esc_html__('Tabs column width', WP_ULIKE_PRO_DOMAIN),
                                'fields'     => $this->responsive_width_fields( '12', '12', '12' )
                            ),
                            array(
                                'type'    => 'subheading',
                                'content' => esc_html__('Heading', WP_ULIKE_PRO_DOMAIN),
                            ),
                            array(
                                'id'         => 'header_info_width',
                                'type'       => 'fieldset',
                                'title'      => esc_html__('Header info width', WP_ULIKE_PRO_DOMAIN),
                                'fields'     => $this->responsive_width_fields( '12', '12', '12' )
                            ),
                            array(
                                'id'         => 'header_bagdes_width',
                                'type'       => 'fieldset',
                                'title'      => esc_html__('Header badges width', WP_ULIKE_PRO_DOMAIN),
                                'fields'     => $this->responsive_width_fields( '12', '12', '12' )
                            ),
                            array(
                                'type'    => 'subheading',
                                'content' => esc_html__('User Info', WP_ULIKE_PRO_DOMAIN),
                            ),
                            array(
                                'id'         => 'avatar_width',
                                'type'       => 'fieldset',
                                'title'      => esc_html__('Avatar column width', WP_ULIKE_PRO_DOMAIN),
                                'fields'     => $this->responsive_width_fields( '3', '3', '12' )
                            ),
                            array(
                                'id'         => 'info_width',
                                'type'       => 'fieldset',
                                'title'      => esc_html__('User info column width', WP_ULIKE_PRO_DOMAIN),
                                'fields'     => $this->responsive_width_fields( '9', '9', '12' )
                            ),
                            array(
                                'type'    => 'subheading',
                                'content' => esc_html__('Badges', WP_ULIKE_PRO_DOMAIN),
                            ),
                            array(
                                'id'         => 'badges_width',
                                'type'       => 'fieldset',
                                'title'      => esc_html__('Badge item width', WP_ULIKE_PRO_DOMAIN),
                                'fields'     => $this->responsive_width_fields( '3', '4', '12' )
                            ),
                        )
                    ),
                ),
                // 'default'       => array(
                //     'opt-text-1'  => 'This is text 1 value',
                //     'opt-text-2'  => 'This is text 2 value',
                //     'opt-color-1' => '#555',
                //     'opt-color-2' => '#999',
                // )
            ),
            array(
                'id'      => 'enable_local_avatars',
                'type'    => 'switcher',
                'default' => false,
                'title'   => esc_html__('Enable Upload Local Avatar', WP_ULIKE_PRO_DOMAIN),
            ),
            array(
                'id'         => 'use_gravatars',
                'type'       => 'switcher',
                'default'    => true,
                'desc'       => esc_html__('Do you want to use gravatars instead of the default plugin profile photo (If the user did not upload a custom profile photo / avatar)', WP_ULIKE_PRO_DOMAIN),
                'title'      => esc_html__('Use Gravatars?', WP_ULIKE_PRO_DOMAIN),
                'dependency' => array( 'enable_local_avatars', '==', 'true' )
            ),
            array(
                'id'         => 'default_avatar',
                'type'       => 'media',
                'title'      => esc_html__('Default Avatar', WP_ULIKE_PRO_DOMAIN),
                'library'    => 'image',
                'default'    => array(
                    'url'    => WP_ULIKE_PRO_PUBLIC_URL . '/assets/img/png/default-avatar.png',
                    'width'  => 256,
                    'height' => 256,
                    'title'  => 'Default Avatar',
                ),
                'dependency' => array( 'enable_local_avatars|use_gravatars', '==|==', 'true|false' )
            ),
            array(
                'id'         => 'max_avatar_size',
                'type'       => 'slider',
                'title'      => esc_html__( 'Avatar Maximum File Size', WP_ULIKE_PRO_DOMAIN),
                'default'    => 2,
                'max'        => 50,
                'step'       => 1,
                'unit'       => 'MB',
                'dependency' => array( 'enable_local_avatars', '==', 'true' ),
            ),
            array(
                'id'         => 'max_avatar_width',
                'type'       => 'slider',
                'title'      => esc_html__( 'Avatar Maximum Width ', WP_ULIKE_PRO_DOMAIN),
                'default'    => 512,
                'unit'       => 'px',
                'dependency' => array( 'enable_local_avatars', '==', 'true' ),
            ),
            array(
                'id'         => 'image_quality',
                'type'       => 'slider',
                'title'      => esc_html__( 'Image Quality', WP_ULIKE_PRO_DOMAIN),
                'desc'       => esc_html__( 'Quality is used to determine quality of image uploads, and ranges from 0 (worst quality, smaller file) to 100 (best quality, biggest file). The default range is 60.', WP_ULIKE_PRO_DOMAIN),
                'min'        => 0,
                'max'        => 100,
                'default'    => 60,
                'dependency' => array( 'enable_local_avatars', '==', 'true' ),
            ),
            array(
                'id'      => 'enable_admin_limit_access',
                'type'    => 'switcher',
                'default' => false,
                'title'   => esc_html__('Enable Dashboard Limit Access', WP_ULIKE_PRO_DOMAIN),
            ),
            array(
                'id'         => 'hide_admin_bar',
                'type'       => 'switcher',
                'default'    => true,
                'title'      => esc_html__('Hide Admin Bar', WP_ULIKE_PRO_DOMAIN),
                'dependency' => array( 'enable_admin_limit_access', '==', 'true' )
            ),
            array(
                'id'         => 'dashboard_access_roles',
                'type'       => 'checkbox',
                'title'      => esc_html__('Dashboard User Access', WP_ULIKE_PRO_DOMAIN),
                'desc'       => esc_html__('Dashboard access can be restricted to users of certain roles only.', WP_ULIKE_PRO_DOMAIN),
                'options'    => wp_ulike_pro_get_user_roles_list( array( 'Administrator', 'Subscriber' ) ),
                'dependency' => array( 'enable_admin_limit_access', '==', 'true' )
            ),
            array(
                'id'         => 'dashboard_custom_redirect',
                'type'       => 'text',
                'default'    => home_url(),
                'title'      => esc_html__( 'Custom Redirect URL', WP_ULIKE_PRO_DOMAIN),
                'dependency' => array( 'enable_admin_limit_access', '==', 'true' )
            ),
        );
    }

    public function responsive_width_fields( $desktop = '3', $tablet = '4', $mobile = '12' ){
        return array(
            array(
                'id'          => 'desktop',
                'type'        => 'select',
                'title'       => esc_html__('Desktop', WP_ULIKE_PRO_DOMAIN),
                'options'     => array(
                '1'  => '1/12',
                '2'  => '2/12',
                '3'  => '3/12',
                '4'  => '4/12',
                '5'  => '5/12',
                '6'  => '6/12',
                '7'  => '7/12',
                '8'  => '8/12',
                '9'  => '9/12',
                '10' => '10/12',
                '11' => '11/12',
                '12' => '12/12'
                ),
                'default'     => $desktop
            ),
            array(
                'id'          => 'tablet',
                'type'        => 'select',
                'title'       => esc_html__('Tablet', WP_ULIKE_PRO_DOMAIN),
                'options'     => array(
                '1'  => '1/12',
                '2'  => '2/12',
                '3'  => '3/12',
                '4'  => '4/12',
                '5'  => '5/12',
                '6'  => '6/12',
                '7'  => '7/12',
                '8'  => '8/12',
                '9'  => '9/12',
                '10' => '10/12',
                '11' => '11/12',
                '12' => '12/12'
                ),
                'default'     => $tablet
            ),
            array(
                'id'          => 'mobile',
                'type'        => 'select',
                'title'       => esc_html__('Mobile', WP_ULIKE_PRO_DOMAIN),
                'options'     => array(
                '1'  => '1/12',
                '2'  => '2/12',
                '3'  => '3/12',
                '4'  => '4/12',
                '5'  => '5/12',
                '6'  => '6/12',
                '7'  => '7/12',
                '8'  => '8/12',
                '9'  => '9/12',
                '10' => '10/12',
                '11' => '11/12',
                '12' => '12/12'
                ),
                'default'     => $mobile
            ),
        );
    }

    /**
     * Update rest api section in setting panel
     *
     * @param array $options
     * @return array
     */
    public function rest_api_section( $options ){
        // Check license permission
        if( ! $this->has_permission ){
            return $this->get_permission_notice();
        }

        return array(
            array(
                'id'      => 'enable_rest_api',
                'type'    => 'switcher',
                'default' => false,
                'title'   => esc_html__('Enable REST API', WP_ULIKE_PRO_DOMAIN),
            ),
            array(
                'id'         => 'authentication_type',
                'type'       => 'button_set',
                'title'      => esc_html__( 'Authentication Type', WP_ULIKE_PRO_DOMAIN),
                'default'    => 'login',
                'options'    => array(
                    'login' => esc_html__('User Login', WP_ULIKE_PRO_DOMAIN),
                    'token' => esc_html__('Custom Keys', WP_ULIKE_PRO_DOMAIN)
                ),
                'dependency' => array( 'enable_rest_api', '==', 'true' ),
            ),
            array(
                'id'         => 'rest_api_permission_for_readable_routes',
                'type'       => 'select',
                'title'      => esc_html__( 'Access to readable routes', WP_ULIKE_PRO_DOMAIN),
                'desc'       => esc_html__( 'Manage users\' access level to REST API.',WP_ULIKE_PRO_DOMAIN ),
                'chosen'     => true,
                'multiple'   => true,
                'default'    => array( 'administrator' ),
                'options'    => 'roles',
                'dependency' => array( 'enable_rest_api|authentication_type', '==|==', 'true|login' ),
            ),
            array(
                'id'         => 'rest_api_permission_for_writable_routes',
                'type'       => 'select',
                'title'      => esc_html__( 'Access to writable routes', WP_ULIKE_PRO_DOMAIN),
                'desc'       => esc_html__( 'Manage users\' access level to REST API.',WP_ULIKE_PRO_DOMAIN ),
                'chosen'     => true,
                'chosen'     => true,
                'multiple'   => true,
                'default'    => array( 'administrator' ),
                'options'    => 'roles',
                'dependency' => array( 'enable_rest_api|authentication_type', '==|==', 'true|login' ),
            ),
            array(
                'id'         => 'enable_auto_user_id',
                'type'       => 'switcher',
                'default'    => false,
                'title'      => esc_html__('Enable Auto User ID', WP_ULIKE_PRO_DOMAIN),
                'dependency' => array( 'enable_rest_api|rest_api_permission_for_writable_routes', '==|!=', 'true|' ),
            ),
            array(
                'type'       => 'callback',
                'function'   => 'wp_ulike_pro_rest_api_keys_settings_callback',
                'dependency' => array( 'enable_rest_api|authentication_type', '==|==', 'true|token' ),
            ),
        );
    }

    /**
     * Update optimization section in setting panel
     *
     * @param array $options
     * @return array
     */
    public function optimization_section( $options ){
        // Check license permission
        if( ! $this->has_permission ){
            return $this->get_permission_notice();
        }

        return array(
            array(
                'type'    => 'submessage',
                'style'   => 'warning',
                'content' => '<strong>Warning:</strong> All options in this panel work directly on optimizing and modifying database table rows. It is best to have a backup before doing anything.',
            ),
            array(
                'id'     => 'opt-posts',
                'type'   => 'fieldset',
                'title'  => esc_html__( 'Posts', WP_ULIKE_PRO_DOMAIN),
                'fields' => array(
                    array(
                        'type'     => 'callback',
                        'function' => 'wp_ulike_pro_ajax_button_callback',
                        'args'     => array(
                            'title'  => esc_html__( 'Delete All Logs', WP_ULIKE_PRO_DOMAIN),
                            'label'  => esc_html__( 'Delete Records', WP_ULIKE_PRO_DOMAIN),
                            'desc'   => esc_html__( 'You Are About To Delete All Likes Logs. This Action Is Not Reversible.', WP_ULIKE_PRO_DOMAIN),
                            'type'   => 'post',
                            'action' => 'truncate_table'
                        )
                    ),
                    array(
                        'type'     => 'callback',
                        'function' => 'wp_ulike_pro_ajax_button_callback',
                        'args'     => array(
                            'title'  => esc_html__( 'Delete Orphaned Rows', WP_ULIKE_PRO_DOMAIN),
                            'label'  => esc_html__( 'Delete Rows', WP_ULIKE_PRO_DOMAIN),
                            'desc'   => esc_html__( 'Drop all rows in the logs table where its related table row no longer exists. (Don\'t try this option if you\'re using custom IDs)', WP_ULIKE_PRO_DOMAIN),
                            'type'   => 'post',
                            'action' => 'delete_orphaned_rows'
                        )
                    ),
                    array(
                        'type'     => 'callback',
                        'function' => 'wp_ulike_pro_ajax_button_callback',
                        'args'     => array(
                            'title'  => esc_html__( 'Delete Meta Counter Values', WP_ULIKE_PRO_DOMAIN),
                            'label'  => esc_html__( 'Delete Values', WP_ULIKE_PRO_DOMAIN),
                            'desc'   => esc_html__( 'You Are About To Delete All Meta Counter Data. This Action Is Not Reversible.', WP_ULIKE_PRO_DOMAIN),
                            'type'   => 'post',
                            'action' => 'delete_meta_group'
                        )
                    ),
                    array(
                        'type'     => 'callback',
                        'function' => 'wp_ulike_pro_ajax_button_callback',
                        'args'     => array(
                            'title'  => esc_html__( 'Delete Dulicate Rows', WP_ULIKE_PRO_DOMAIN),
                            'label'  => esc_html__( 'Delete Rows', WP_ULIKE_PRO_DOMAIN),
                            'desc'   => esc_html__( 'Delete all duplicate rows generated by the spam users.', WP_ULIKE_PRO_DOMAIN),
                            'type'   => 'post',
                            'action' => 'delete_duplicate_rows'
                        )
                    ),
                    array(
                        'type'     => 'callback',
                        'function' => 'wp_ulike_pro_ajax_button_callback',
                        'args'     => array(
                            'title'  => esc_html__( 'Optimize Table Overhead', WP_ULIKE_PRO_DOMAIN),
                            'label'  => esc_html__( 'Optimize Table', WP_ULIKE_PRO_DOMAIN),
                            'desc'   => esc_html__( 'Overhead is a temporary disk space that is used by your database to store queries. Over time, a table’s overhead will increase.', WP_ULIKE_PRO_DOMAIN),
                            'type'   => 'post',
                            'action' => 'optimize_table'
                        )
                    ),
                    array(
                        'type'     => 'callback',
                        'function' => 'wp_ulike_pro_ajax_button_callback',
                        'args'     => array(
                            'title'  => esc_html__( 'Migrate Counter Metadata', WP_ULIKE_PRO_DOMAIN),
                            'label'  => esc_html__( 'Migrate Rows', WP_ULIKE_PRO_DOMAIN),
                            'desc'   => esc_html__( 'You are about to migrate counter values to wordpress meta table.', WP_ULIKE_PRO_DOMAIN),
                            'type'   => 'post',
                            'action' => 'migrate_metadata'
                        )
                    ),
                    array(
                        'type'     => 'callback',
                        'function' => 'wp_ulike_pro_ajax_button_callback',
                        'args'     => array(
                            'title'  => esc_html__( 'Reset Counter Values', WP_ULIKE_PRO_DOMAIN),
                            'label'  => esc_html__( 'Reset Counter', WP_ULIKE_PRO_DOMAIN),
                            'desc'   => esc_html__( 'Reset all counters without affecting stats information.', WP_ULIKE_PRO_DOMAIN),
                            'type'   => 'post',
                            'action' => 'reset_counter'
                        )
                    )
                ),
            ),
            array(
                'id'     => 'opt-comments',
                'type'   => 'fieldset',
                'title'  => esc_html__( 'Comments', WP_ULIKE_PRO_DOMAIN),
                'fields' => array(
                    array(
                        'type'     => 'callback',
                        'function' => 'wp_ulike_pro_ajax_button_callback',
                        'args'     => array(
                            'title'  => esc_html__( 'Delete All Logs', WP_ULIKE_PRO_DOMAIN),
                            'label'  => esc_html__( 'Delete Records', WP_ULIKE_PRO_DOMAIN),
                            'desc'   => esc_html__( 'You Are About To Delete All Likes Logs. This Action Is Not Reversible.', WP_ULIKE_PRO_DOMAIN),
                            'type'   => 'comment',
                            'action' => 'truncate_table'
                        )
                    ),
                    array(
                        'type'     => 'callback',
                        'function' => 'wp_ulike_pro_ajax_button_callback',
                        'args'     => array(
                            'title'  => esc_html__( 'Delete Orphaned Rows', WP_ULIKE_PRO_DOMAIN),
                            'label'  => esc_html__( 'Delete Rows', WP_ULIKE_PRO_DOMAIN),
                            'desc'   => esc_html__( 'Drop all rows in the logs table where its related table row no longer exists. (Don\'t try this option if you\'re using custom IDs)', WP_ULIKE_PRO_DOMAIN),
                            'type'   => 'comment',
                            'action' => 'delete_orphaned_rows'
                        )
                    ),
                    array(
                        'type'     => 'callback',
                        'function' => 'wp_ulike_pro_ajax_button_callback',
                        'args'     => array(
                            'title'  => esc_html__( 'Delete Meta Counter Values', WP_ULIKE_PRO_DOMAIN),
                            'label'  => esc_html__( 'Delete Values', WP_ULIKE_PRO_DOMAIN),
                            'desc'   => esc_html__( 'You Are About To Delete All Meta Counter Data. This Action Is Not Reversible.', WP_ULIKE_PRO_DOMAIN),
                            'type'   => 'comment',
                            'action' => 'delete_meta_group'
                        )
                    ),
                    array(
                        'type'     => 'callback',
                        'function' => 'wp_ulike_pro_ajax_button_callback',
                        'args'     => array(
                            'title'  => esc_html__( 'Delete Dulicate Rows', WP_ULIKE_PRO_DOMAIN),
                            'label'  => esc_html__( 'Delete Rows', WP_ULIKE_PRO_DOMAIN),
                            'desc'   => esc_html__( 'Delete all duplicate rows generated by the spam users.', WP_ULIKE_PRO_DOMAIN),
                            'type'   => 'comment',
                            'action' => 'delete_duplicate_rows'
                        )
                    ),
                    array(
                        'type'     => 'callback',
                        'function' => 'wp_ulike_pro_ajax_button_callback',
                        'args'     => array(
                            'title'  => esc_html__( 'Optimize Table Overhead', WP_ULIKE_PRO_DOMAIN),
                            'label'  => esc_html__( 'Optimize Table', WP_ULIKE_PRO_DOMAIN),
                            'desc'   => esc_html__( 'Overhead is a temporary disk space that is used by your database to store queries. Over time, a table’s overhead will increase.', WP_ULIKE_PRO_DOMAIN),
                            'type'   => 'comment',
                            'action' => 'optimize_table'
                        )
                    ),
                    array(
                        'type'     => 'callback',
                        'function' => 'wp_ulike_pro_ajax_button_callback',
                        'args'     => array(
                            'title'  => esc_html__( 'Migrate Counter Metadata', WP_ULIKE_PRO_DOMAIN),
                            'label'  => esc_html__( 'Migrate Rows', WP_ULIKE_PRO_DOMAIN),
                            'desc'   => esc_html__( 'You are about to migrate counter values to wordpress meta table.', WP_ULIKE_PRO_DOMAIN),
                            'type'   => 'comment',
                            'action' => 'migrate_metadata'
                        )
                    ),
                    array(
                        'type'     => 'callback',
                        'function' => 'wp_ulike_pro_ajax_button_callback',
                        'args'     => array(
                            'title'  => esc_html__( 'Reset Counter Values', WP_ULIKE_PRO_DOMAIN),
                            'label'  => esc_html__( 'Reset Counter', WP_ULIKE_PRO_DOMAIN),
                            'desc'   => esc_html__( 'Reset all counters without affecting stats information.', WP_ULIKE_PRO_DOMAIN),
                            'type'   => 'comment',
                            'action' => 'reset_counter'
                        )
                    )
                ),
            ),
            array(
                'id'     => 'opt-activity',
                'type'   => 'fieldset',
                'title'  => esc_html__( 'BuddyPress', WP_ULIKE_PRO_DOMAIN),
                'fields' => array(
                    array(
                        'type'     => 'callback',
                        'function' => 'wp_ulike_pro_ajax_button_callback',
                        'args'     => array(
                            'title'  => esc_html__( 'Delete All Logs', WP_ULIKE_PRO_DOMAIN),
                            'label'  => esc_html__( 'Delete Records', WP_ULIKE_PRO_DOMAIN),
                            'desc'   => esc_html__( 'You Are About To Delete All Likes Logs. This Action Is Not Reversible.', WP_ULIKE_PRO_DOMAIN),
                            'type'   => 'activity',
                            'action' => 'truncate_table'
                        )
                    ),
                    array(
                        'type'     => 'callback',
                        'function' => 'wp_ulike_pro_ajax_button_callback',
                        'args'     => array(
                            'title'  => esc_html__( 'Delete Orphaned Rows', WP_ULIKE_PRO_DOMAIN),
                            'label'  => esc_html__( 'Delete Rows', WP_ULIKE_PRO_DOMAIN),
                            'desc'   => esc_html__( 'Drop all rows in the logs table where its related table row no longer exists. (Don\'t try this option if you\'re using custom IDs)', WP_ULIKE_PRO_DOMAIN),
                            'type'   => 'activity',
                            'action' => 'delete_orphaned_rows'
                        )
                    ),
                    array(
                        'type'     => 'callback',
                        'function' => 'wp_ulike_pro_ajax_button_callback',
                        'args'     => array(
                            'title'  => esc_html__( 'Delete Meta Counter Values', WP_ULIKE_PRO_DOMAIN),
                            'label'  => esc_html__( 'Delete Values', WP_ULIKE_PRO_DOMAIN),
                            'desc'   => esc_html__( 'You Are About To Delete All Meta Counter Data. This Action Is Not Reversible.', WP_ULIKE_PRO_DOMAIN),
                            'type'   => 'activity',
                            'action' => 'delete_meta_group'
                        )
                    ),
                    array(
                        'type'     => 'callback',
                        'function' => 'wp_ulike_pro_ajax_button_callback',
                        'args'     => array(
                            'title'  => esc_html__( 'Delete Dulicate Rows', WP_ULIKE_PRO_DOMAIN),
                            'label'  => esc_html__( 'Delete Rows', WP_ULIKE_PRO_DOMAIN),
                            'desc'   => esc_html__( 'Delete all duplicate rows generated by the spam users.', WP_ULIKE_PRO_DOMAIN),
                            'type'   => 'activity',
                            'action' => 'delete_duplicate_rows'
                        )
                    ),
                    array(
                        'type'     => 'callback',
                        'function' => 'wp_ulike_pro_ajax_button_callback',
                        'args'     => array(
                            'title'  => esc_html__( 'Optimize Table Overhead', WP_ULIKE_PRO_DOMAIN),
                            'label'  => esc_html__( 'Optimize Table', WP_ULIKE_PRO_DOMAIN),
                            'desc'   => esc_html__( 'Overhead is a temporary disk space that is used by your database to store queries. Over time, a table’s overhead will increase.', WP_ULIKE_PRO_DOMAIN),
                            'type'   => 'activity',
                            'action' => 'optimize_table'
                        )
                    ),
                    array(
                        'type'     => 'callback',
                        'function' => 'wp_ulike_pro_ajax_button_callback',
                        'args'     => array(
                            'title'  => esc_html__( 'Reset Counter Values', WP_ULIKE_PRO_DOMAIN),
                            'label'  => esc_html__( 'Reset Counter', WP_ULIKE_PRO_DOMAIN),
                            'desc'   => esc_html__( 'Reset all counters without affecting stats information.', WP_ULIKE_PRO_DOMAIN),
                            'type'   => 'activity',
                            'action' => 'reset_counter'
                        )
                    )
                ),
            ),
            array(
                'id'     => 'opt-topic',
                'type'   => 'fieldset',
                'title'  => esc_html__( 'bbPress', WP_ULIKE_PRO_DOMAIN),
                'fields' => array(
                    array(
                        'type'     => 'callback',
                        'function' => 'wp_ulike_pro_ajax_button_callback',
                        'args'     => array(
                            'title'  => esc_html__( 'Delete All Logs', WP_ULIKE_PRO_DOMAIN),
                            'label'  => esc_html__( 'Delete Records', WP_ULIKE_PRO_DOMAIN),
                            'desc'   => esc_html__( 'You Are About To Delete All Likes Logs. This Action Is Not Reversible.', WP_ULIKE_PRO_DOMAIN),
                            'type'   => 'topic',
                            'action' => 'truncate_table'
                        )
                    ),
                    array(
                        'type'     => 'callback',
                        'function' => 'wp_ulike_pro_ajax_button_callback',
                        'args'     => array(
                            'title'  => esc_html__( 'Delete Orphaned Rows', WP_ULIKE_PRO_DOMAIN),
                            'label'  => esc_html__( 'Delete Rows', WP_ULIKE_PRO_DOMAIN),
                            'desc'   => esc_html__( 'Drop all rows in the logs table where its related table row no longer exists. (Don\'t try this option if you\'re using custom IDs)', WP_ULIKE_PRO_DOMAIN),
                            'type'   => 'topic',
                            'action' => 'delete_orphaned_rows'
                        )
                    ),
                    array(
                        'type'     => 'callback',
                        'function' => 'wp_ulike_pro_ajax_button_callback',
                        'args'     => array(
                            'title'  => esc_html__( 'Delete Meta Counter Values', WP_ULIKE_PRO_DOMAIN),
                            'label'  => esc_html__( 'Delete Values', WP_ULIKE_PRO_DOMAIN),
                            'desc'   => esc_html__( 'You Are About To Delete All Meta Counter Data. This Action Is Not Reversible.', WP_ULIKE_PRO_DOMAIN),
                            'type'   => 'topic',
                            'action' => 'delete_meta_group'
                        )
                    ),
                    array(
                        'type'     => 'callback',
                        'function' => 'wp_ulike_pro_ajax_button_callback',
                        'args'     => array(
                            'title'  => esc_html__( 'Delete Dulicate Rows', WP_ULIKE_PRO_DOMAIN),
                            'label'  => esc_html__( 'Delete Rows', WP_ULIKE_PRO_DOMAIN),
                            'desc'   => esc_html__( 'Delete all duplicate rows generated by the spam users.', WP_ULIKE_PRO_DOMAIN),
                            'type'   => 'topic',
                            'action' => 'delete_duplicate_rows'
                        )
                    ),
                    array(
                        'type'     => 'callback',
                        'function' => 'wp_ulike_pro_ajax_button_callback',
                        'args'     => array(
                            'title'  => esc_html__( 'Optimize Table Overhead', WP_ULIKE_PRO_DOMAIN),
                            'label'  => esc_html__( 'Optimize Table', WP_ULIKE_PRO_DOMAIN),
                            'desc'   => esc_html__( 'Overhead is a temporary disk space that is used by your database to store queries. Over time, a table’s overhead will increase.', WP_ULIKE_PRO_DOMAIN),
                            'type'   => 'topic',
                            'action' => 'optimize_table'
                        )
                    ),
                    array(
                        'type'     => 'callback',
                        'function' => 'wp_ulike_pro_ajax_button_callback',
                        'args'     => array(
                            'title'  => esc_html__( 'Reset Counter Values', WP_ULIKE_PRO_DOMAIN),
                            'label'  => esc_html__( 'Reset Counter', WP_ULIKE_PRO_DOMAIN),
                            'desc'   => esc_html__( 'Reset all counters without affecting stats information.', WP_ULIKE_PRO_DOMAIN),
                            'type'   => 'topic',
                            'action' => 'reset_counter'
                        )
                    )
                ),
            ),
            array(
                'id'     => 'opt-general',
                'type'   => 'fieldset',
                'title'  => esc_html__( 'Other cases', WP_ULIKE_PRO_DOMAIN),
                'fields' => array(
                    array(
                        'type'     => 'callback',
                        'function' => 'wp_ulike_pro_ajax_button_callback',
                        'args'     => array(
                            'title'  => esc_html__( 'Delete All Meta User Status', WP_ULIKE_PRO_DOMAIN),
                            'label'  => esc_html__( 'Delete All Rows', WP_ULIKE_PRO_DOMAIN),
                            'desc'   => esc_html__( 'You Are About To Delete All Meta User Status.', WP_ULIKE_PRO_DOMAIN),
                            'type'   => 'user',
                            'action' => 'delete_meta_group'
                        )
                    ),
                    array(
                        'type'     => 'callback',
                        'function' => 'wp_ulike_pro_ajax_button_callback',
                        'args'     => array(
                            'title'  => esc_html__( 'Delete All Meta Statistics Values', WP_ULIKE_PRO_DOMAIN),
                            'label'  => esc_html__( 'Delete All Rows', WP_ULIKE_PRO_DOMAIN),
                            'desc'   => esc_html__( 'You Are About To Delete All Meta Stats Values.', WP_ULIKE_PRO_DOMAIN),
                            'type'   => 'statistics',
                            'action' => 'delete_meta_group'
                        )
                    ),
                    array(
                        'type'     => 'callback',
                        'function' => 'wp_ulike_pro_ajax_button_callback',
                        'args'     => array(
                            'title'  => esc_html__( 'Delete Empty Post Meta Rows', WP_ULIKE_PRO_DOMAIN),
                            'label'  => esc_html__( 'Delete Rows', WP_ULIKE_PRO_DOMAIN),
                            'desc'   => esc_html__( 'Drop all Metabox rows in the post meta table where its value is empty.', WP_ULIKE_PRO_DOMAIN),
                            'type'   => 'optimize',
                            'action' => 'optimize_post_meta'
                        )
                    ),
                    array(
                        'type'     => 'callback',
                        'function' => 'wp_ulike_pro_ajax_button_callback',
                        'args'     => array(
                            'title'  => esc_html__( 'Create default pages', WP_ULIKE_PRO_DOMAIN),
                            'label'  => esc_html__( 'Create Pages', WP_ULIKE_PRO_DOMAIN),
                            'desc'   => esc_html__( 'This tool will install all the missing pages. Pages already defined and set up will not be replaced.', WP_ULIKE_PRO_DOMAIN),
                            'type'   => 'create',
                            'action' => 'manage_default_pages'
                        )
                    ),
                    array(
                        'type'     => 'callback',
                        'function' => 'wp_ulike_pro_ajax_button_callback',
                        'args'     => array(
                            'title'  => esc_html__( 'Delete default pages', WP_ULIKE_PRO_DOMAIN),
                            'label'  => esc_html__( 'Delete Pages', WP_ULIKE_PRO_DOMAIN),
                            'desc'   => esc_html__( 'This tool will delete all the default pages', WP_ULIKE_PRO_DOMAIN),
                            'type'   => 'delete',
                            'action' => 'manage_default_pages'
                        )
                    ),
                ),
            )
        );
    }

    /**
     * Update share buttons section in setting panel
     *
     * @param array $options
     * @return array
     */
    public function social_share_section(  $options ){
        // Check license permission
        if( ! $this->has_permission ){
            return $this->get_permission_notice();
        }

        return array(
            array(
                'type'    => 'submessage',
                'style'   => 'info',
                'content' => esc_html__('You can create multiple share buttons as you wish and use them as shortocode or try auto display options. Just be sure to set the "Slug" option for each item. For example, if you want to use shortcode and set slug as "single_share", you can use the following shortcode to display social buttons:', WP_ULIKE_PRO_DOMAIN) .
                '<br><br><code>[wp_ulike_pro_social_share slug=single_share]</code>'
            ),
            array(
                'id'     => 'social_share',
                'type'   => 'group',
                'title'  => esc_html__('Add Share Items', WP_ULIKE_PRO_DOMAIN),
                'fields' => array(
                    array(
                        'id'       => 'slug',
                        'type'     => 'text',
                        'title'    => esc_html__( 'Slug',WP_ULIKE_PRO_DOMAIN) . ' *',
                    ),
                    array(
                        'id'     => 'buttons',
                        'type'   => 'group',
                        'title'  => esc_html__('Share Buttons ', WP_ULIKE_PRO_DOMAIN)  . ' *',
                        'fields' => array(
                            array(
                                'id'       => 'network',
                                'type'     => 'select',
                                'title'    => esc_html__( 'Network', WP_ULIKE_PRO_DOMAIN),
                                'chosen'   => true,
                                'multiple' => false,
                                'options'  => array(
                                    'facebook'      => esc_html__( 'facebook', WP_ULIKE_PRO_DOMAIN),
                                    'linkedin'      => esc_html__( 'linkedin', WP_ULIKE_PRO_DOMAIN),
                                    'twitter'       => esc_html__( 'twitter', WP_ULIKE_PRO_DOMAIN),
                                    'vkontakte'     => esc_html__( 'vkontakte', WP_ULIKE_PRO_DOMAIN),
                                    'odnoklassniki' => esc_html__( 'odnoklassniki', WP_ULIKE_PRO_DOMAIN),
                                    'tumblr'        => esc_html__( 'tumblr', WP_ULIKE_PRO_DOMAIN),
                                    'blogger'       => esc_html__( 'blogger', WP_ULIKE_PRO_DOMAIN),
                                    'pinterest'     => esc_html__( 'pinterest', WP_ULIKE_PRO_DOMAIN),
                                    'digg'          => esc_html__( 'digg', WP_ULIKE_PRO_DOMAIN),
                                    'evernote'      => esc_html__( 'evernote', WP_ULIKE_PRO_DOMAIN),
                                    'reddit'        => esc_html__( 'reddit', WP_ULIKE_PRO_DOMAIN),
                                    'delicious'     => esc_html__( 'delicious', WP_ULIKE_PRO_DOMAIN),
                                    'mix'           => esc_html__( 'mix', WP_ULIKE_PRO_DOMAIN),
                                    'xing'          => esc_html__( 'xing', WP_ULIKE_PRO_DOMAIN),
                                    'wordpress'     => esc_html__( 'wordpress', WP_ULIKE_PRO_DOMAIN),
                                    'baidu'         => esc_html__( 'baidu', WP_ULIKE_PRO_DOMAIN),
                                    'renren'        => esc_html__( 'renren', WP_ULIKE_PRO_DOMAIN),
                                    'weibo'         => esc_html__( 'weibo', WP_ULIKE_PRO_DOMAIN),
                                    'skype'         => esc_html__( 'skype', WP_ULIKE_PRO_DOMAIN),
                                    'telegram'      => esc_html__( 'telegram', WP_ULIKE_PRO_DOMAIN),
                                    'whatsapp'      => esc_html__( 'whatsapp', WP_ULIKE_PRO_DOMAIN),
                                    'wechat'        => esc_html__( 'wechat', WP_ULIKE_PRO_DOMAIN)
                                )
                            ),
                            array(
                                'id'      => 'label',
                                'type'    => 'text',
                                'title'   => esc_html__( 'Label',WP_ULIKE_PRO_DOMAIN),
                            ),
                        )
                    ),
                    array(
                        'id'      => 'view',
                        'type'    => 'button_set',
                        'default' => 'icon_text',
                        'title'   => esc_html__( 'View', WP_ULIKE_PRO_DOMAIN),
                        'options' => array(
                            'icon_text' => esc_html__( 'Icon & Text', WP_ULIKE_PRO_DOMAIN),
                            'icon'      => esc_html__( 'Icon', WP_ULIKE_PRO_DOMAIN),
                            'text'      => esc_html__( 'Text', WP_ULIKE_PRO_DOMAIN)
                        )
                    ),
                    array(
                        'id'      => 'skin',
                        'type'    => 'button_set',
                        'default' => 'gradient',
                        'title'   => esc_html__( 'Skin', WP_ULIKE_PRO_DOMAIN),
                        'options' => array(
                            'gradient' => esc_html__( 'Gradient', WP_ULIKE_PRO_DOMAIN),
                            'minimal'  => esc_html__( 'Minimal', WP_ULIKE_PRO_DOMAIN),
                            'framed'   => esc_html__( 'Framed', WP_ULIKE_PRO_DOMAIN),
                            'boxed'    => esc_html__( 'Boxed', WP_ULIKE_PRO_DOMAIN),
                            'flat'     => esc_html__( 'Flat', WP_ULIKE_PRO_DOMAIN)
                        )
                    ),
                    array(
                        'id'      => 'shape',
                        'type'    => 'button_set',
                        'title'   => esc_html__( 'Shape', WP_ULIKE_PRO_DOMAIN),
                        'default' => 'rounded',
                        'options' => array(
                            'square'  => esc_html__( 'Square', WP_ULIKE_PRO_DOMAIN),
                            'rounded' => esc_html__( 'Rounded', WP_ULIKE_PRO_DOMAIN),
                            'circle'  => esc_html__( 'Circle', WP_ULIKE_PRO_DOMAIN)
                        )
                    ),
                    array(
                        'id'      => 'color',
                        'type'    => 'button_set',
                        'default' => 'official',
                        'title'   => esc_html__( 'Color', WP_ULIKE_PRO_DOMAIN),
                        'options' => array(
                            'official' => esc_html__( 'Official', WP_ULIKE_PRO_DOMAIN),
                            'custom'   => esc_html__( 'Custom', WP_ULIKE_PRO_DOMAIN)
                        )
                    ),
                    array(
                        'id'            => 'before',
                        'type'          => 'wp_editor',
                        'tinymce'       => false,
                        'media_buttons' => false,
                        'quicktags'     => true,
                        'sanitize'      => false,
                        'title'         => esc_html__( 'Before Content',WP_ULIKE_PRO_DOMAIN)
                    ),
                    array(
                        'id'            => 'after',
                        'type'          => 'wp_editor',
                        'tinymce'       => false,
                        'media_buttons' => false,
                        'quicktags'     => true,
                        'sanitize'      => false,
                        'title'         => esc_html__( 'After Content',WP_ULIKE_PRO_DOMAIN)
                    ),
                    array(
                        'id'      => 'auto_display',
                        'type'    => 'radio',
                        'title'   => esc_html__( 'Auto Display', WP_ULIKE_PRO_DOMAIN),
                        'default' => 'none',
                        'options' => array(
                            'none'          => esc_html__( 'None', WP_ULIKE_PRO_DOMAIN),
                            'after_button'  => esc_html__( 'After Button', WP_ULIKE_PRO_DOMAIN),
                            'before_button' => esc_html__( 'Before Button', WP_ULIKE_PRO_DOMAIN),
                            'modal_display' => esc_html__( 'Modal After Vote', WP_ULIKE_PRO_DOMAIN),
                            'custom_hook'   => esc_html__( 'Cutom Hook', WP_ULIKE_PRO_DOMAIN)
                        )
                    ),
                    array(
                        'id'         => 'auto_custom_hook',
                        'type'       => 'text',
                        'title'      => esc_html__( 'Enter Hook Name',WP_ULIKE_PRO_DOMAIN),
                        'desc'       => esc_html__('Please enter your desired action name in this field so that the social buttons are automatically displayed there.', WP_ULIKE_PRO_DOMAIN),
                        'dependency' => array( 'auto_display', '==', 'custom_hook' ),
                    ),
                    array(
                        'id'          => 'auto_display_filter_status',
                        'type'        => 'select',
                        'title'       => esc_html__( 'Filter Status',WP_ULIKE_PRO_DOMAIN ),
                        'desc'        => esc_html__('By selecting any type, you can disable adding share buttons in that area.', WP_ULIKE_PRO_DOMAIN),
                        'chosen'      => true,
                        'multiple'    => true,
                        'options'     => array(
							'like'      => esc_html__( 'Like', WP_ULIKE_PRO_DOMAIN ),
							'dislike'   => esc_html__( 'Dislike', WP_ULIKE_PRO_DOMAIN ),
							'unlike'    => esc_html__( 'Unlike', WP_ULIKE_PRO_DOMAIN ),
							'undislike' => esc_html__( 'Undislike', WP_ULIKE_PRO_DOMAIN )
                        ),
                        'dependency' => array( 'auto_display', '==', 'modal_display' ),
                    ),
                    array(
                        'id'          => 'auto_display_filter_types',
                        'type'        => 'select',
                        'title'       => esc_html__( 'Filter Types',WP_ULIKE_PRO_DOMAIN ),
                        'desc'        => esc_html__('By selecting any type, you can disable adding share buttons in that area.', WP_ULIKE_PRO_DOMAIN),
                        'chosen'      => true,
                        'multiple'    => true,
                        'options'     => array(
                            'post'     => esc_html__('Posts', WP_ULIKE_PRO_DOMAIN),
                            'comment'  => esc_html__('Comments', WP_ULIKE_PRO_DOMAIN),
                            'activity' => esc_html__('Activities', WP_ULIKE_PRO_DOMAIN),
                            'topic'    => esc_html__('Topics', WP_ULIKE_PRO_DOMAIN)
                        ),
                        'dependency'=> array( 'auto_display', 'any', 'after_button,before_button,modal_display' ),
                    )
                )
            ),
        );

    }

    /**
     * Add email translations section
     *
     * @return void
     */
    public function register_email_translations_section(){
        if( ! class_exists( 'ULF' ) ){
            return;
        }

        /**
         * Translations Section
         */
        ULF::createSection( 'wp_ulike_settings', array(
            'title'  => esc_html__( 'Emails',WP_ULIKE_PRO_DOMAIN),
            'parent' => 'translations',
            'fields' => apply_filters( 'wp_ulike_panel_emails', array(
                array(
                    'type'    => 'submessage',
                    'style'   => 'info',
                    'content' => esc_html__('You can use the following variables in all templates:', WP_ULIKE_PRO_DOMAIN) .
                    '<br><br><code>{site_url}</code> <code>{site_name}</code> <code>{admin_email}</code> <code>{login_url}</code> <code>{profile_url}</code> <code>{logout_url}</code> <code>{display_name}</code> <code>{first_name}</code> <code>{last_name}</code> <code>{username}</code> <code>{email}</code> <code>{password_reset_link}</code>'
                ),
                array(
                    'id'     => 'welcome_email',
                    'type'   => 'fieldset',
                    'title'  => esc_html__( 'Account Welcome Email',WP_ULIKE_PRO_DOMAIN),
                    'fields' => array(
                        array(
                            'id'      => 'subject',
                            'type'    => 'text',
                            'title'   => esc_html__( 'Subject Line',WP_ULIKE_PRO_DOMAIN),
                            'default' => esc_html__( 'Welcome to {site_name}!',WP_ULIKE_PRO_DOMAIN),
                        ),
                        array(
                            'id'      => 'body',
                            'type'    => 'wp_editor',
                            'default' => WP_Ulike_Pro_Mail::get_template( 'welcome' ),
                            'title'   => esc_html__( 'Message Body',WP_ULIKE_PRO_DOMAIN)
                        ),
                    ),
                ),
                array(
                    'id'     => 'reset_password_email',
                    'type'   => 'fieldset',
                    'title'  => esc_html__( 'Password Reset Email',WP_ULIKE_PRO_DOMAIN),
                    'fields' => array(
                        array(
                            'id'      => 'subject',
                            'type'    => 'text',
                            'title'   => esc_html__( 'Subject Line',WP_ULIKE_PRO_DOMAIN),
                            'default' => esc_html__( 'Reset your password',WP_ULIKE_PRO_DOMAIN),
                        ),
                        array(
                            'id'      => 'body',
                            'type'    => 'wp_editor',
                            'default' => WP_Ulike_Pro_Mail::get_template( 'reset-password' ),
                            'title'   => esc_html__( 'Message Body',WP_ULIKE_PRO_DOMAIN),
                        ),
                    ),
                ),
                array(
                    'id'     => 'change_password_email',
                    'type'   => 'fieldset',
                    'title'  => esc_html__( 'Password Changed Email',WP_ULIKE_PRO_DOMAIN),
                    'fields' => array(
                        array(
                            'id'      => 'subject',
                            'type'    => 'text',
                            'title'   => esc_html__( 'Subject Line',WP_ULIKE_PRO_DOMAIN),
                            'default' => esc_html__( 'Your {site_name} password has been changed!',WP_ULIKE_PRO_DOMAIN),
                        ),
                        array(
                            'id'      => 'body',
                            'type'    => 'wp_editor',
                            'default' => WP_Ulike_Pro_Mail::get_template( 'change-password' ),
                            'title'   => esc_html__( 'Message Body',WP_ULIKE_PRO_DOMAIN),
                        ),
                    ),
                ),
                array(
                    'id'      => 'admin_email',
                    'type'    => 'text',
                    'title'   => esc_html__( 'Admin E-mail Address',WP_ULIKE_PRO_DOMAIN),
                    'default' => get_bloginfo('admin_email'),
                ),
                array(
                    'id'      => 'appears_from',
                    'type'    => 'text',
                    'title'   => esc_html__( 'Mail appears from',WP_ULIKE_PRO_DOMAIN),
                    'default' => get_bloginfo( 'name' ),
                ),
                array(
                    'id'      => 'appears_email',
                    'type'    => 'text',
                    'title'   => esc_html__( 'Mail appears from address',WP_ULIKE_PRO_DOMAIN),
                    'default' => get_bloginfo('admin_email'),
                ),
                array(
                    'id'      => 'enable_html_email',
                    'type'    => 'switcher',
                    'default' => true,
                    'title'   => esc_html__('Use HTML for E-mails?', WP_ULIKE_PRO_DOMAIN),
                )
            ) )
        ) );
    }

    /**
     * Register backup section in setting panel
     *
     * @return void
     */
    public function register_backup_section(){
        if( ! class_exists( 'ULF' ) ){
            return;
        }

        $backup_option = ! $this->has_permission ? $this->get_permission_notice() : array( array( 'type' => 'backup' ) );

        /**
         * Backup Section
         */
        ULF::createSection( 'wp_ulike_settings', array(
            'title'  => esc_html__( 'Backup',WP_ULIKE_PRO_DOMAIN),
            'icon'   => 'fa fa-shield',
            'fields' => $backup_option
        ) );
    }

    public function get_permission_notice(){
        return array(
            array(
                'type'    => 'notice',
                'style'   => 'danger',
                'content' => sprintf( '<p>%s</p><a class="button" href="%s">%s</a>', esc_html__( 'Features of the Pro version are only available once you have registered your license. If you don\'t yet have a license key, get WP ULike Pro now.' , WP_ULIKE_PRO_DOMAIN ), self_admin_url( 'admin.php?page=wp-ulike-pro-license' ), esc_html__( 'Activate License', WP_ULIKE_PRO_DOMAIN ) ),
            )
        );
    }
}