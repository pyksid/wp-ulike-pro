<?php
/**
 * Option panel
 *
 *
 * @package    wp-ulike-pro
 * @author     TechnoWich 2026
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
        add_filter( 'wp_ulike_panel_profiles', array( $this, 'profiles_section' ), 10, 1 );
        add_filter( 'wp_ulike_panel_share_buttons', array( $this, 'social_share_section' ), 10, 1 );
        add_filter( 'wp_ulike_panel_forms', array( $this, 'forms_section' ), 10, 1 );
        add_filter( 'wp_ulike_panel_social_logins', array( $this, 'social_login' ), 10, 1 );
        add_filter( 'wp_ulike_panel_translations', array( $this, 'translations_section' ), 10, 1 );
        add_filter( 'wp_ulike_panel_emails', array( $this, 'register_email_translations_section' ), 10, 1 );
        add_filter( 'wp_ulike_panel_post_type_options', array( $this, 'post_type_options' ), 10, 1 );
        add_filter( 'wp_ulike_panel_comment_type_options', array( $this, 'comment_type_options' ), 10, 1 );

        // add_filter( 'wp_ulike_panel_content_options', array( $this, 'update_content_options' ), 10, 1 );
        // Custom options
        add_filter( 'wp_ulike_filter_counter_options', array( $this, 'filter_counter_options' ), 10, 2 );


        // Hooks
        add_action( 'wp_ulike_settings_saved', array( $this, 'options_saved' ) );
        add_action( 'admin_init', array( $this, 'maybe_migrate_profile_tabs_has_link' ), 5 );
    }

    /** One-time migration: remove invalid has_link values from profile tabs (legacy object vs string format). */
    public function maybe_migrate_profile_tabs_has_link() {
        if ( get_option( 'wp_ulike_pro_profile_tabs_has_link_migrated' ) ) {
            return;
        }

        $settings = get_option( 'wp_ulike_settings', array() );
        $tabs     = isset( $settings['user_profiles_appearance']['tabs'] ) ? $settings['user_profiles_appearance']['tabs'] : array();
        if ( empty( $tabs ) || ! is_array( $tabs ) ) {
            update_option( 'wp_ulike_pro_profile_tabs_has_link_migrated', true );
            return;
        }

        foreach ( $tabs as $key => $tab ) {
            if ( empty( $tab['has_link'] ) ) {
                continue;
            }
            $url = is_string( $tab['has_link'] ) ? trim( $tab['has_link'] ) : trim( (string) ( $tab['has_link']['url'] ?? '' ) );
            if ( empty( $url ) || ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
                unset( $tabs[ $key ]['has_link'] );
            }
        }

        $settings['user_profiles_appearance']['tabs'] = $tabs;
        update_option( 'wp_ulike_settings', $settings );
        update_option( 'wp_ulike_pro_profile_tabs_has_link_migrated', true );
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
     * Enable meta options
     *
     * @param array $options
     * @return array
     */
    public function post_type_options( $options ){
        $options = wp_ulike_array_insert_after(
            $options,
            'auto_display_filter_post_types',
            array(
                'enable_attachments' => array(
                    'id'      => 'enable_attachments',
                    'type'    => 'switcher',
                    'default' => false,
                    'title'   => esc_html__('Enable Attachments', WP_ULIKE_PRO_DOMAIN),
                    'desc'    => esc_html__('Add like buttons to image attachments displayed on your site. Requires WordPress 5.6+ and a theme that uses the standard WordPress attachment image function.', WP_ULIKE_PRO_DOMAIN),
                ),
                'filter_attachment_class' => array(
                    'id'         => 'filter_attachment_class',
                    'type'       => 'repeater',
                    'fields'     => array(
                        array(
                            'id'    => 'name',
                            'type'  => 'text',
                            'title' => esc_html__('Class Name', WP_ULIKE_PRO_DOMAIN),
                            'desc'  => esc_html__('Enter the CSS class name that identifies the attachment image.', WP_ULIKE_PRO_DOMAIN)
                        ),
                    ),
                    'title'      => esc_html__('Filter By Class Name', WP_ULIKE_PRO_DOMAIN),
                    'dependency' => array( 'enable_attachments', '==', 'true' ),
                    'desc'       => esc_html__('Show like buttons only on images with specific CSS classes. Add one class name per line (e.g., attachment-full, attachment-large).', WP_ULIKE_PRO_DOMAIN)
                ),
                'filter_attachment_size' => array(
                    'id'         => 'filter_attachment_size',
                    'type'       => 'repeater',
                    'fields'     => array(
                        array(
                            'id'    => 'name',
                            'type'  => 'text',
                            'title' => esc_html__('Image Size', WP_ULIKE_PRO_DOMAIN),
                            'desc'  => esc_html__('Enter the WordPress image size name (e.g., thumbnail, medium, large, full).', WP_ULIKE_PRO_DOMAIN)
                        ),
                    ),
                    'title'      => esc_html__('Filter By Attachment Size', WP_ULIKE_PRO_DOMAIN),
                    'dependency' => array( 'enable_attachments', '==', 'true' ),
                    'desc'       => esc_html__('Show like buttons only on images displayed at specific sizes. Add one size name per line (e.g., large, thumbnail, medium).', WP_ULIKE_PRO_DOMAIN)
                ),
            )
        );

        $options = wp_ulike_array_insert_after(
            $options,
            'vote_limit_number',
            array(
                'enable_metadata' => array(
                    'id'      => 'enable_metadata',
                    'type'    => 'switcher',
                    'default' => true,
                    'title'   => esc_html__('Enable Standard Meta Data', WP_ULIKE_PRO_DOMAIN),
                    'desc'    => esc_html__('Store vote counts in WordPress standard meta tables, making it easier to query and display likes using standard WordPress functions.', WP_ULIKE_PRO_DOMAIN) . ' ' . esc_html__('Meta Keys:', WP_ULIKE_PRO_DOMAIN) . ' <code>like_amount</code>, <code>dislike_amount</code>, <code>net_votes</code>',
                    'help'    => 'If you are an old user, after activating this option, go to Tools → Maintenance → Advanced and click "Move Post Counters" to move counter values from the wp_ulike_meta table to the meta table.'
                ),
            )
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
        return wp_ulike_array_insert_after(
            $options,
            'vote_limit_number',
            array(
                'enable_metadata' => array(
                    'id'      => 'enable_metadata',
                    'type'    => 'switcher',
                    'default' => true,
                    'title'   => esc_html__('Enable Standard Meta Data', WP_ULIKE_PRO_DOMAIN),
                    'desc'    => esc_html__('Store vote counts in WordPress standard meta tables, making it easier to query and display likes using standard WordPress functions.', WP_ULIKE_PRO_DOMAIN) . ' ' . esc_html__('Meta Keys:', WP_ULIKE_PRO_DOMAIN) . ' <code>like_amount</code>, <code>dislike_amount</code>, <code>net_votes</code>',
                    'help'    => 'If you are an old user, after activating this option, go to Tools → Maintenance → Advanced and click "Move Comment Counters" to move counter values from the wp_ulike_meta table to the meta table.'
                ),
            )
        );
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
                'title'   => sprintf( esc_html__('%s Prefix',WP_ULIKE_PRO_DOMAIN), esc_html__('Dislike', WP_ULIKE_PRO_DOMAIN) ),
                'desc'    => esc_html__('Text shown before the count (e.g., "-" displays as "-125").', WP_ULIKE_PRO_DOMAIN)
            );
            $options[] = array(
                'id'      => 'undislike_prefix',
                'type'    => 'text',
                'default' => '-',
                'title'   => sprintf( esc_html__('%s Prefix',WP_ULIKE_PRO_DOMAIN), esc_html__('Undislike', WP_ULIKE_PRO_DOMAIN) ),
                'desc'    => esc_html__('Text shown before the count (e.g., "-" displays as "-125").', WP_ULIKE_PRO_DOMAIN)
            );
        }

        if( $type === 'postfix' ){
            $options[] = array(
                'id'      => 'dislike_postfix',
                'type'    => 'text',
                'title'   => sprintf( esc_html__('%s Suffix',WP_ULIKE_PRO_DOMAIN), esc_html__('Dislike', WP_ULIKE_PRO_DOMAIN) ),
                'desc'    => esc_html__('Text shown after the count (e.g., " dislikes" displays as "125 dislikes").', WP_ULIKE_PRO_DOMAIN)
            );
            $options[] = array(
                'id'      => 'undislike_postfix',
                'type'    => 'text',
                'title'   => sprintf( esc_html__('%s Suffix',WP_ULIKE_PRO_DOMAIN), esc_html__('Undislike', WP_ULIKE_PRO_DOMAIN) ),
                'desc'    => esc_html__('Text shown after the count (e.g., " dislikes" displays as "125 dislikes").', WP_ULIKE_PRO_DOMAIN)
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

        $options = wp_ulike_array_insert_after(
            $options,
            'enable_admin_posts_columns',
            array(
                array(
                    'id'       => 'statistics_display_roles',
                    'type'     => 'select',
                    'title'    => esc_html__( 'Statistics Page Access', WP_ULIKE_PRO_DOMAIN),
                    'desc'     => esc_html__( 'Choose which user roles can access the Statistics page in the admin menu.',WP_ULIKE_PRO_DOMAIN ),
                    'chosen'   => true,
                    'multiple' => true,
                    'options'  => $user_roles_list
                ),
            )
        );

        $options[] = array(
            'id'       => 'enable_meta_box',
            'type'     => 'select',
            'title'    => esc_html__( 'Show Display Panel In Post Editor', WP_ULIKE_PRO_DOMAIN ),
            'desc'     => esc_html__( 'Add a lightweight sidebar panel on selected post types for per-post button display overrides. Schema and FAQ markup is configured under Tools → Schema Generator.', WP_ULIKE_PRO_DOMAIN ),
            'chosen'   => true,
            'multiple' => true,
            'default'  => array('post', 'page'),
            'options'  => 'post_types'
        );
        $options[] = array(
            'id'       => 'view_tracking_enabled_types',
            'type'     => 'checkbox',
            'title'    => esc_html__( 'Enable View Tracking', WP_ULIKE_PRO_DOMAIN ),
            'desc'     => esc_html__( 'Track button views for accurate engagement rate calculations. Select which content types should have view tracking enabled.', WP_ULIKE_PRO_DOMAIN ),
            'options'  => array(
                'post'     => esc_html__( 'Posts', WP_ULIKE_PRO_DOMAIN ),
                'comment'  => esc_html__( 'Comments', WP_ULIKE_PRO_DOMAIN ),
                'activity' => esc_html__( 'Activities', WP_ULIKE_PRO_DOMAIN ),
                'topic'    => esc_html__( 'Topics', WP_ULIKE_PRO_DOMAIN )
            ),
            'default'  => array( 'post' )
        );
        $options[] = array(
            'id'         => 'enable_serialize',
            'type'       => 'switcher',
            'default'    => true,
            'title'      => esc_html__('Enable Serialized Data Storage', WP_ULIKE_PRO_DOMAIN),
            'desc'       => esc_html__('Store meta box data in a more efficient format, reducing database size and improving performance. If you\'re upgrading from an older version, enable this option first, then run "Convert to Serialized Format" under Tools → Maintenance → Advanced.', WP_ULIKE_PRO_DOMAIN)
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
                        'title'   => esc_html__('Button label',WP_ULIKE_PRO_DOMAIN),
                        'desc'    => sprintf( esc_html__('Text displayed on the %s (e.g., "Dislike", "👎", "Downvote").', WP_ULIKE_PRO_DOMAIN), esc_html__('dislike button', WP_ULIKE_PRO_DOMAIN) ),
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
                        'title'   => esc_html__('Button label',WP_ULIKE_PRO_DOMAIN),
                        'desc'    => sprintf( esc_html__('Text displayed on the button %s (e.g., "Disliked", "👎", "Remove Dislike").', WP_ULIKE_PRO_DOMAIN), esc_html__('after disliking', WP_ULIKE_PRO_DOMAIN) ),
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
                        'desc'         => esc_html__('Upload an image icon for the button state.', WP_ULIKE_PRO_DOMAIN),
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
                        'desc'         => esc_html__('Upload an image icon for the button state.', WP_ULIKE_PRO_DOMAIN),
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

        // Engagement templates (emoji / star) reuse these classic options — keep copy accurate.
        if ( isset( $options['counter_display_condition']['desc'] ) ) {
            $options['counter_display_condition']['desc'] = esc_html__(
                'Control when the counter is shown. For Star Rating this is the average and rating count; for Emoji Reactions it is the reaction totals.',
                WP_ULIKE_PRO_DOMAIN
            );
        }
        if ( isset( $options['hide_zero_counter']['desc'] ) ) {
            $options['hide_zero_counter']['desc'] = esc_html__(
                'Hide the counter when there are no votes, reactions, or ratings yet.',
                WP_ULIKE_PRO_DOMAIN
            );
        }
        if ( isset( $options['enable_likers_box']['desc'] ) ) {
            $options['enable_likers_box']['desc'] = esc_html__(
                'Show who engaged with this item — likers, reactors, or raters depending on the selected template.',
                WP_ULIKE_PRO_DOMAIN
            );
        }
        if ( isset( $options['likers_style']['desc'] ) ) {
            $options['likers_style']['desc'] = esc_html__(
                'Inline: show avatars next to the button. Popover: show avatars on hover. Pile + Modal: compact avatar stack that opens a full list.',
                WP_ULIKE_PRO_DOMAIN
            );
        }

        // Add percentage option
        $percentage_list = wp_ulike_pro_get_templates_list_by_attribute( 'is_percentage_support' );
        $options = wp_ulike_array_insert_after( $options, 'hide_zero_counter', array(
            'enable_percentage_values' => array(
                'id'         => 'enable_percentage_values',
                'type'       => 'switcher',
                'title'      => esc_html__('Enable Percentage Values', WP_ULIKE_PRO_DOMAIN),
                'desc'       => esc_html__('Display vote counts as percentages instead of numbers. Shows the ratio of likes to total votes (e.g., "75%" instead of "75").', WP_ULIKE_PRO_DOMAIN),
                'dependency' => array( 'counter_display_condition|template', '!=|any', 'hidden|' . $percentage_list )
            )
        ) );

        // Add modal login template
        $options = wp_ulike_array_insert_after( $options, 'login_template', array(
            'modal_template' => array(
                'id'         => 'modal_template',
                'type'       => 'wp_editor',
                'height'     => '100px',
                'default'    => '[wp_ulike_pro_login_form ajax_toggle=1 redirect_to="current_page"]',
                'title'      => esc_html__('Modal Template', WP_ULIKE_PRO_DOMAIN),
                'desc'       => esc_html__('Content displayed in the login modal popup when logged-out users try to vote. You can use shortcodes here.', WP_ULIKE_PRO_DOMAIN),
                'dependency' => array( 'logged_out_display_type|enable_only_logged_in_users', '==|==', 'modal|true' )
            )
        ) );

        // Pile modal title
        $options = wp_ulike_array_insert_after( $options, 'likers_style', array(
            'likers_modal_title' => array(
                'id'         => 'likers_modal_title',
                'type'       => 'text',
                'default'    => esc_html__('Likers', WP_ULIKE_PRO_DOMAIN),
                'title'      => esc_html__('Likers Modal Title', WP_ULIKE_PRO_DOMAIN),
                'desc'       => esc_html__('Heading text displayed at the top of the likers modal popup.', WP_ULIKE_PRO_DOMAIN),
                'dependency' => array( 'enable_likers_box|likers_style', '==|any', 'true|pile'  ),
            )
        ) );

        // Pile modal template
        $options = wp_ulike_array_insert_after( $options, 'likers_modal_title', array(
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
                'desc'       => esc_html__('Allowed Variables:', WP_ULIKE_PRO_DOMAIN) . ' <code>{up_profile_url}</code>, <code>{bp_profile_url}</code>, <code>{um_profile_url}</code>, <code>{avatar_url}</code>, <code>{display_name}</code>, <code>{first_name}</code>, <code>{last_name}</code>, <code>{username}</code>, <code>{email}</code>, <code>{user_id}</code>, <code>{user_status}</code>',
                'dependency' => array( 'enable_likers_box|likers_style', '==|any', 'true|pile'  ),
            )
        ) );

        if ( isset( $options['enable_auto_display'] ) ) {
            $tools_url = admin_url( 'admin.php?page=wp-ulike-pro-tools&tab=display-automation' );
            $options   = wp_ulike_array_insert_after(
                $options,
                'enable_auto_display',
                array(
                    'display_automation_notice' => array(
                        'id'      => 'display_automation_notice',
                        'type'    => 'submessage',
                        'style'   => 'info',
                        'content' => wp_kses_post(
                            sprintf(
                                /* translators: 1: opening anchor, 2: closing anchor */
                                esc_html__( 'Need more control? Use %1$sDisplay Automation%2$s in Tools for WooCommerce reviews, BuddyPress, page filters, and advanced placement rules. You can keep Automatic Display enabled for simple setups, or turn it off when using Display Automation to avoid duplicate buttons.', WP_ULIKE_PRO_DOMAIN ),
                                '<a href="' . esc_url( $tools_url ) . '"><strong>',
                                '</strong></a>'
                            )
                        ),
                    ),
                )
            );
        }

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
            'title'   => sprintf( esc_html__( '%s Notice Message', WP_ULIKE_PRO_DOMAIN), esc_html__('Dislike', WP_ULIKE_PRO_DOMAIN) ),
            'desc'    => esc_html__( 'Confirmation message shown after a user action.', WP_ULIKE_PRO_DOMAIN)
        );
        $options[] = array(
            'id'      => 'undislike_notice',
            'type'    => 'text',
            'default' => esc_html__('Thanks! You Undisliked This.',WP_ULIKE_PRO_DOMAIN),
            'title'   => sprintf( esc_html__( '%s Notice Message', WP_ULIKE_PRO_DOMAIN), esc_html__('Undislike', WP_ULIKE_PRO_DOMAIN) ),
            'desc'    => esc_html__( 'Confirmation message shown after a user action.', WP_ULIKE_PRO_DOMAIN)
        );
        $options[] = array(
            'id'      => 'dislike_button_aria_label',
            'type'    => 'text',
            'default' => esc_html__( 'Dislike Button',WP_ULIKE_PRO_DOMAIN),
            'title'   => esc_html__( 'Dislike Button Aria Label', WP_ULIKE_PRO_DOMAIN),
            'desc'    => esc_html__( 'Accessibility label for screen readers. Helps visually impaired users understand what the button does.', WP_ULIKE_PRO_DOMAIN)
        );

        // Notices
        $options[] = array(
            'type'    => 'heading',
            'content' => esc_html__( 'Forms Notices', WP_ULIKE_PRO_DOMAIN)
        );

        $options[] = array(
            'id'      => 'required_fields_notice',
            'type'    => 'text',
            'default' => esc_html__( 'Please enter required fields', WP_ULIKE_PRO_DOMAIN ),
            'title'   => esc_html__( 'Please enter required fields', WP_ULIKE_PRO_DOMAIN)
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
            'default' => esc_html__( 'Login successful', WP_ULIKE_PRO_DOMAIN ),
            'title'   => esc_html__( 'Login successful', WP_ULIKE_PRO_DOMAIN)
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
            'title'   => esc_html__( 'Error occurred', WP_ULIKE_PRO_DOMAIN)
        );

        $options[] = array(
            'id'      => 'signup_success_notice',
            'type'    => 'text',
            'default' => esc_html__( 'Signup successful', WP_ULIKE_PRO_DOMAIN ),
            'title'   => esc_html__( 'Signup successful', WP_ULIKE_PRO_DOMAIN)
        );

        $options[] = array(
            'id'      => 'email_verification_notice',
            'type'    => 'text',
            'default' => esc_html__( 'Please check your email to activate your account.', WP_ULIKE_PRO_DOMAIN ),
            'title'   => esc_html__( 'Email Verification Required', WP_ULIKE_PRO_DOMAIN )
        );

        $options[] = array(
            'id'      => 'success_verification_notice',
            'type'    => 'text',
            'default' => esc_html__( 'Your email has been successfully verified. You can now log in.', WP_ULIKE_PRO_DOMAIN ),
            'title'   => esc_html__( 'Email Successfully Verified', WP_ULIKE_PRO_DOMAIN )
        );

        $options[] = array(
            'id'      => 'failed_verification_notice',
            'type'    => 'text',
            'default' => esc_html__( 'Verification failed. The link might be expired or invalid.', WP_ULIKE_PRO_DOMAIN ),
            'title'   => esc_html__( 'Email Verification Failed', WP_ULIKE_PRO_DOMAIN )
        );

        $options[] = array(
            'id'      => 'account_not_verified_notice',
            'type'    => 'text',
            'default' => esc_html__( 'Your account is not yet verified. Please check your email for the verification link.', WP_ULIKE_PRO_DOMAIN ),
            'title'   => esc_html__( 'Account Not Verified', WP_ULIKE_PRO_DOMAIN )
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
            'default' => esc_html__( 'Not found!',WP_ULIKE_PRO_DOMAIN),
            'title'   => esc_html__( 'User Not Found Message', WP_ULIKE_PRO_DOMAIN)
        );

        $options[] = array(
            'id'      => 'avatar_upload_text',
            'type'    => 'text',
            'default' => esc_html__( 'Upload', WP_ULIKE_PRO_DOMAIN ),
            'title'   => sprintf( esc_html__( 'Avatar %s Text', WP_ULIKE_PRO_DOMAIN), esc_html__('Upload', WP_ULIKE_PRO_DOMAIN) )
        );

        $options[] = array(
            'id'      => 'avatar_edit_text',
            'type'    => 'text',
            'default' => esc_html__( 'Edit', WP_ULIKE_PRO_DOMAIN ),
            'title'   => sprintf( esc_html__( 'Avatar %s Text', WP_ULIKE_PRO_DOMAIN), esc_html__('Edit', WP_ULIKE_PRO_DOMAIN) )
        );

        $options[] = array(
            'id'      => 'avatar_delete_text',
            'type'    => 'text',
            'default' => esc_html__( 'Delete', WP_ULIKE_PRO_DOMAIN ),
            'title'   => sprintf( esc_html__( 'Avatar %s Text', WP_ULIKE_PRO_DOMAIN), esc_html__('Delete', WP_ULIKE_PRO_DOMAIN) )
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
            'title'   => esc_html__( 'Your password reset link appears to be invalid', WP_ULIKE_PRO_DOMAIN )
        );

        $options[] = array(
            'id'      => 'rp_expiredkey',
            'type'    => 'text',
            'default' => esc_html__( 'Your password reset link has expired. Please request a new link below.', WP_ULIKE_PRO_DOMAIN ),
            'title'   => esc_html__( 'Your password reset link has expired', WP_ULIKE_PRO_DOMAIN )
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
            'default' => esc_html__( 'The parameter is invalid or malformed.', WP_ULIKE_PRO_DOMAIN ),
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
            'default' => esc_html__( 'The parameter is invalid or malformed.', WP_ULIKE_PRO_DOMAIN ),
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
                'title'   => sprintf( esc_html__('Select %s Page', WP_ULIKE_PRO_DOMAIN), esc_html__('Login', WP_ULIKE_PRO_DOMAIN) ),
                'desc'    => esc_html__('Choose the page that contains your login form shortcode. This page will be used for user authentication.', WP_ULIKE_PRO_DOMAIN),
                'options' => 'pages'
            ),
            array(
                'id'         => 'login_custom_redirect',
                'type'       => 'text',
                'title'      => esc_html__( 'Login Redirect URL', WP_ULIKE_PRO_DOMAIN),
                'desc'       => sprintf( esc_html__('URL where users are redirected after %s. Leave empty to redirect to the %s.', WP_ULIKE_PRO_DOMAIN), esc_html__('successful login', WP_ULIKE_PRO_DOMAIN), esc_html__('previous page', WP_ULIKE_PRO_DOMAIN) ),
                'dependency' => array( 'login_core_page', '!=', '' )
            ),
            array(
                'id'         => 'logout_custom_redirect',
                'type'       => 'text',
                'title'      => esc_html__( 'Logout Redirect URL', WP_ULIKE_PRO_DOMAIN),
                'desc'       => sprintf( esc_html__('URL where users are redirected after %s. Leave empty to redirect to the %s.', WP_ULIKE_PRO_DOMAIN), esc_html__('logging out', WP_ULIKE_PRO_DOMAIN), esc_html__('home page', WP_ULIKE_PRO_DOMAIN) ),
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
                'title'   => sprintf( esc_html__('Select %s Page', WP_ULIKE_PRO_DOMAIN), esc_html__('Signup', WP_ULIKE_PRO_DOMAIN) ),
                'desc'    => esc_html__('Choose the page that contains your form shortcode.', WP_ULIKE_PRO_DOMAIN),
                'options' => 'pages'
            ),
            array(
                'id'         => 'signup_custom_redirect',
                'type'       => 'text',
                'title'      => esc_html__( 'Signup Redirect URL', WP_ULIKE_PRO_DOMAIN),
                'desc'       => sprintf( esc_html__('URL where users are redirected after %s. Leave empty to redirect to the %s.', WP_ULIKE_PRO_DOMAIN), esc_html__('successful registration', WP_ULIKE_PRO_DOMAIN), esc_html__('login page', WP_ULIKE_PRO_DOMAIN) ),
                'dependency' => array( 'signup_core_page', '!=', '' )
            ),
            array(
                'id'      => 'signup_status',
                'type'    => 'select',
                'title'   => esc_html__('Signup Status', WP_ULIKE_PRO_DOMAIN),
                'desc'    => esc_html__('Choose whether new users are automatically approved or must verify their email address first.', WP_ULIKE_PRO_DOMAIN),
                'default' => 'approved',
                'options' => [
                    'approved'	=> __( 'Auto Approve', WP_ULIKE_PRO_DOMAIN ),
                    'checkmail' => __( 'Require Email Activation', WP_ULIKE_PRO_DOMAIN )
                ],
                'dependency' => array( 'signup_core_page', '!=', '' )
            ),
            array(
                'id'         => 'signup_enable_auto_login',
                'type'       => 'switcher',
                'default'    => false,
                'title'      => esc_html__('Enable Auto Login After Signup', WP_ULIKE_PRO_DOMAIN),
                'desc'       => esc_html__('Automatically log users in immediately after they complete registration. Only works when signup status is set to Auto Approve.', WP_ULIKE_PRO_DOMAIN),
                'dependency' => array(
                    array( 'signup_core_page', '!=', '' ),
                    array( 'signup_status', '==', 'approved' )
                ),
            ),
            array(
                'id'      => 'reset_password_core_page',
                'type'    => 'select',
                'chosen'  => true,
                'ajax'    => true,
                'title'   => esc_html__('Select Reset Password Page', WP_ULIKE_PRO_DOMAIN),
                'desc'    => esc_html__('Choose the page that contains your form shortcode.', WP_ULIKE_PRO_DOMAIN),
                'options' => 'pages'
            ),
            array(
                'id'      => 'edit_account_core_page',
                'type'    => 'select',
                'chosen'  => true,
                'ajax'    => true,
                'title'   => esc_html__('Select Edit Account Page', WP_ULIKE_PRO_DOMAIN),
                'desc'    => esc_html__('Choose the page that contains your account edit form shortcode where users can update their profile information.', WP_ULIKE_PRO_DOMAIN),
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
                'title'         => esc_html__( 'Logged In Message', WP_ULIKE_PRO_DOMAIN),
                'desc'          => esc_html__('Custom message displayed to logged-in users. You can use variables like {display_name}, {avatar_url}, {profile_url}, {logout_url}.', WP_ULIKE_PRO_DOMAIN)
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
                'desc'       => esc_html__( 'Load reCAPTCHA scripts on all pages. Enable this if you use reCAPTCHA in modal login forms or other areas outside the standard login/register pages.', WP_ULIKE_PRO_DOMAIN ),
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
                'desc'    => esc_html__( 'Choose the size of the reCAPTCHA widget. Compact is smaller and suitable for mobile devices.', WP_ULIKE_PRO_DOMAIN ),
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
                'title'      => esc_html__('Enable Social Logins', WP_ULIKE_PRO_DOMAIN),
                'desc'       => esc_html__('Allow users to log in or register using their social media accounts instead of creating a new account.', WP_ULIKE_PRO_DOMAIN)
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
                        'settings'   => array(
                            'width' => '50%'
                        ),
                        'chosen'   => true,
                        'multiple' => false,
                        'options'  => array(
                            'Facebook'  => esc_html__( 'Facebook', WP_ULIKE_PRO_DOMAIN),
                            'GitHub'    => esc_html__( 'GitHub', WP_ULIKE_PRO_DOMAIN),
                            'Google'    => esc_html__( 'Google', WP_ULIKE_PRO_DOMAIN),
                            'Twitter'   => esc_html__( 'X', WP_ULIKE_PRO_DOMAIN),
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
                        'desc'  => esc_html__( 'Controls the text displayed on the button.',WP_ULIKE_PRO_DOMAIN)
                    ),
                    array(
                        'id'    => 'link_label',
                        'type'  => 'text',
                        'title' => esc_html__( 'Link Button Text',WP_ULIKE_PRO_DOMAIN),
                        'desc'  => esc_html__( 'Controls the text displayed on the button.',WP_ULIKE_PRO_DOMAIN)
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
                        'title'   => esc_html__('Disable This Network', WP_ULIKE_PRO_DOMAIN),
                        'desc'    => esc_html__('Temporarily disable this social network without deleting its configuration.', WP_ULIKE_PRO_DOMAIN),
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
                'title'   => esc_html__( 'Button Display Style', WP_ULIKE_PRO_DOMAIN),
                'desc'    => esc_html__('Choose how buttons are displayed: with icon and text, icon only, or text only.', WP_ULIKE_PRO_DOMAIN),
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
                'title'   => esc_html__( 'Button Style', WP_ULIKE_PRO_DOMAIN),
                'desc'    => esc_html__('Choose the visual style for buttons.', WP_ULIKE_PRO_DOMAIN),
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
                'title'   => esc_html__( 'Button Shape', WP_ULIKE_PRO_DOMAIN),
                'desc'    => esc_html__('Choose the corner style for social login buttons.', WP_ULIKE_PRO_DOMAIN),
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
                'title'   => esc_html__( 'Button Colors', WP_ULIKE_PRO_DOMAIN),
                'desc'    => esc_html__('Use official brand colors for each social network, or apply custom colors to all buttons.', WP_ULIKE_PRO_DOMAIN),
                'options' => array(
                    'official' => esc_html__( 'Official', WP_ULIKE_PRO_DOMAIN),
                    'custom'   => esc_html__( 'Custom', WP_ULIKE_PRO_DOMAIN)
                ),
                'dependency' => array( 'enable_social_login', '==', 'true' )
            ),
            array(
                'id'         => 'social_login_layout',
                'type'       => 'fieldset',
                'title'      => esc_html__('Button Layout', WP_ULIKE_PRO_DOMAIN),
                'desc'       => esc_html__('Control the width of social login buttons across different screen sizes using a 12-column grid system.', WP_ULIKE_PRO_DOMAIN),
                'fields'     => $this->responsive_width_fields( '12', '12', '12' ),
                'dependency' => array( 'enable_social_login', '==', 'true' )
            ),
            array(
                'id'      => 'social_login_auto_display',
                'type'    => 'radio',
                'title'   => esc_html__( 'Auto Display', WP_ULIKE_PRO_DOMAIN),
                'desc'    => esc_html__('Automatically show social login buttons on your login form. Choose where they appear or use a custom hook.', WP_ULIKE_PRO_DOMAIN),
                'default' => 'after_login_form',
                'options' => array(
                    'none'              => esc_html__( 'None', WP_ULIKE_PRO_DOMAIN),
                    'after_login_form'  => esc_html__( 'After Login Form', WP_ULIKE_PRO_DOMAIN),
                    'before_login_form' => esc_html__( 'Before Login Form', WP_ULIKE_PRO_DOMAIN),
                    'custom_hook'       => esc_html__( 'Custom Hook', WP_ULIKE_PRO_DOMAIN)
                ),
                'dependency' => array( 'enable_social_login', '==', 'true' )
            ),
            array(
                'id'         => 'social_login_auto_custom_hook',
                'type'       => 'text',
                'title'      => esc_html__( 'Enter Hook Name',WP_ULIKE_PRO_DOMAIN),
                'desc'       => esc_html__('Enter a WordPress action hook name where you want social buttons to appear automatically.', WP_ULIKE_PRO_DOMAIN),
                'dependency' => array( 'enable_social_login|social_login_auto_display', '==|==', 'true|custom_hook' )
            ),
            array(
                'id'            => 'social_login_before',
                'type'          => 'wp_editor',
                'tinymce'       => false,
                'media_buttons' => false,
                'quicktags'     => true,
                'sanitize'      => false,
                'default'       => '<div style="display: flex; align-items: center; justify-content: center; width: 100%; margin: 30px 0;"> <div style="border-top: 1px solid #b2b2b2; flex: 1 1 auto;"></div> <div style="margin: 0 15px; color: #666; font-weight: 500; white-space: nowrap;">OR</div> <div style="border-top: 1px solid #b2b2b2; flex: 1 1 auto;"></div> </div>',
                'title'         => esc_html__( 'Before Content',WP_ULIKE_PRO_DOMAIN),
                'desc'          => esc_html__('HTML content displayed before the buttons. Useful for adding separators or text.', WP_ULIKE_PRO_DOMAIN),
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
                'desc'          => esc_html__('HTML content displayed after the buttons.', WP_ULIKE_PRO_DOMAIN),
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
                    esc_url(
                        add_query_arg(
                            array(
                                'utm_source'   => 'settings',
                                'utm_medium'   => 'wp-dash',
                                'utm_campaign' => 'profiles-settings',
                            ),
                            'https://docs.wpulike.com/article/16-profiles-settings'
                        )
                    ),
                    esc_html__( 'Read More', WP_ULIKE_PRO_DOMAIN )
                ),
            ),
            array(
                'id'      => 'enable_user_profiles',
                'type'    => 'switcher',
                'default' => false,
                'title'   => sprintf( esc_html__('Enable %s', WP_ULIKE_PRO_DOMAIN), esc_html__('User Profiles', WP_ULIKE_PRO_DOMAIN) ),
                'desc'    => esc_html__('Create custom user profile pages where users can view and manage their information, activity, and preferences.', WP_ULIKE_PRO_DOMAIN),
            ),
            array(
                'id'         => 'user_profiles_core_page',
                'type'       => 'select',
                'chosen'     => true,
                'ajax'       => true,
                'title'      => sprintf( esc_html__('Select %s Page', WP_ULIKE_PRO_DOMAIN), esc_html__('Profile', WP_ULIKE_PRO_DOMAIN) ),
                'desc'       => esc_html__('Choose the page that contains the profile shortcode. This page will serve as the base URL for all user profiles.', WP_ULIKE_PRO_DOMAIN),
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
                'title'      => esc_html__('Redirect Author Pages to Profiles', WP_ULIKE_PRO_DOMAIN),
                'desc'       => esc_html__('Automatically redirect WordPress default author archive pages to the corresponding user profile page.', WP_ULIKE_PRO_DOMAIN),
                'dependency' => array( 'enable_user_profiles', '==', 'true' )
            ),
            array(
                'id'         => 'user_profiles_access',
                'type'       => 'select',
                'default'    => 'everyone',
                'title'      => esc_html__('User Profile Access', WP_ULIKE_PRO_DOMAIN),
                'desc'       => esc_html__('Control who can view user profile pages. Choose whether profiles are public or restricted to logged-in users only.', WP_ULIKE_PRO_DOMAIN),
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
                'desc'       => esc_html__('URL where unauthorized users are redirected when trying to access restricted profiles.', WP_ULIKE_PRO_DOMAIN),
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
                                'title'   => esc_html__('Show User Avatar', WP_ULIKE_PRO_DOMAIN),
                                'desc'    => esc_html__('Display the user\'s profile picture on their profile page.', WP_ULIKE_PRO_DOMAIN),
                            ),
                            array(
                                'id'         => 'avatar_size',
                                'type'       => 'spinner',
                                'title'      => esc_html__('Avatar Dimension', WP_ULIKE_PRO_DOMAIN),
                                'desc'       => esc_html__('Set the width and height of the avatar image in pixels.', WP_ULIKE_PRO_DOMAIN),
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
                                'title'   => esc_html__('Show User Info Section', WP_ULIKE_PRO_DOMAIN),
                                'desc'    => esc_html__('Display the user information section containing name, bio, and other details.', WP_ULIKE_PRO_DOMAIN),
                            ),
                            array(
                                'id'         => 'display_name',
                                'type'       => 'switcher',
                                'default'    => true,
                                'title'      => esc_html__('Show Display Name', WP_ULIKE_PRO_DOMAIN),
                                'desc'       => esc_html__('Display the user\'s display name in the profile header.', WP_ULIKE_PRO_DOMAIN),
                                'dependency' => array( 'display_info', '==', 'true' )
                            ),
                            array(
                                'id'         => 'display_bio',
                                'type'       => 'switcher',
                                'default'    => true,
                                'title'      => esc_html__('Show User Description', WP_ULIKE_PRO_DOMAIN),
                                'desc'       => esc_html__('Display the user\'s biographical information (bio) on their profile.', WP_ULIKE_PRO_DOMAIN),
                                'dependency' => array( 'display_info', '==', 'true' )
                            ),
                            array(
                                'id'         => 'display_custom_message',
                                'type'       => 'switcher',
                                'default'    => false,
                                'title'      => esc_html__('Show Custom Message When Bio is Empty', WP_ULIKE_PRO_DOMAIN),
                                'desc'       => esc_html__('Display a custom message instead of leaving the bio section blank when the user hasn\'t added a description.', WP_ULIKE_PRO_DOMAIN),
                                'dependency' => array( 'display_bio|display_info', '==|==', 'true|true' )
                            ),
                            array(
                                'id'            => 'custom_message',
                                'type'          => 'wp_editor',
                                'title'         => esc_html__('Custom Message', WP_ULIKE_PRO_DOMAIN),
                                'desc'          => esc_html__('Message displayed when the user bio is empty. You can use HTML and shortcodes here.', WP_ULIKE_PRO_DOMAIN),
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
                            'title'   => esc_html__('Show Badges Section', WP_ULIKE_PRO_DOMAIN),
                            'desc'    => esc_html__('Display a section showing user statistics and achievements as badge items.', WP_ULIKE_PRO_DOMAIN),
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
                                    'desc'       => esc_html__('Choose between a pre-built badge with image and text, or create a custom HTML badge.', WP_ULIKE_PRO_DOMAIN),
                                    'options'    => array(
                                        'default' => esc_html__('Default', WP_ULIKE_PRO_DOMAIN),
                                        'custom'  => esc_html__('Custom', WP_ULIKE_PRO_DOMAIN)
                                    ),
                                    'default'    => 'default'
                                ),
                                array(
                                    'id'            => 'title',
                                    'type'          => 'wp_editor',
                                    'title'         => esc_html__('Badge Title', WP_ULIKE_PRO_DOMAIN),
                                    'desc'          => esc_html__('Main text displayed on the badge. You can use shortcodes here to show dynamic content like like counts.', WP_ULIKE_PRO_DOMAIN),
                                    'height'        => '85px',
                                    'dependency'    => array( 'badge_type', '==', 'default' ),
                                ),
                                array(
                                    'id'         => 'subtitle',
                                    'type'       => 'text',
                                    'title'      => esc_html__('Badge Subtitle', WP_ULIKE_PRO_DOMAIN),
                                    'desc'       => esc_html__('Secondary text displayed below the title, typically used for descriptions.', WP_ULIKE_PRO_DOMAIN),
                                    'dependency' => array( 'badge_type', '==', 'default' ),
                                ),
                                array(
                                    'id'         => 'image',
                                    'type'       => 'media',
                                    'title'      => esc_html__('Badge Image', WP_ULIKE_PRO_DOMAIN),
                                    'desc'       => esc_html__('Icon or image displayed on the badge. Recommended size: 64x64 pixels.', WP_ULIKE_PRO_DOMAIN),
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
                                    ),
                                    array(
                                        'badge_type' => 'default',
                                        'title'      => '[wp_ulike_pro_user_info status=dislike] Dislikes',
                                        'subtitle'   => 'Total down votes',
                                    ),
                                    array(
                                        'badge_type' => 'default',
                                        'title'      => '[wp_ulike_pro_user_info type=last_activity after_text=ago empty_text=Inactive]',
                                        'subtitle'   => 'Last Activity',
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
                            'title'      => sprintf( esc_html__( 'Select %s', WP_ULIKE_PRO_DOMAIN), esc_html__('Tabs Side', WP_ULIKE_PRO_DOMAIN) ),
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
                                    'title'   => esc_html__('Tab Title', WP_ULIKE_PRO_DOMAIN),
                                    'desc'    => esc_html__('Text displayed on the tab button in the profile navigation.', WP_ULIKE_PRO_DOMAIN)
                                ),
                                array(
                                    'id'     => 'content',
                                    'type'   => 'wp_editor',
                                    'title'  => esc_html__('Tab Content', WP_ULIKE_PRO_DOMAIN),
                                    'height' => '100px',
                                    'desc'   => esc_html__( 'Content displayed when this tab is active. You can use HTML, text, and shortcodes here.', WP_ULIKE_PRO_DOMAIN),
                                ),
                                array(
                                    'id'      => 'has_link',
                                    'type'    => 'link',
                                    'title'   => esc_html__('Create Link Tab', WP_ULIKE_PRO_DOMAIN),
                                    'desc'    => esc_html__('Make this tab link to an external URL instead of showing content. Useful for linking to external pages or resources.', WP_ULIKE_PRO_DOMAIN),
                                ),
                                array(
                                    'id'      => 'restrict',
                                    'type'    => 'switcher',
                                    'default' => false,
                                    'title'   => esc_html__('Show only for profile owner', WP_ULIKE_PRO_DOMAIN),
                                    'desc'    => esc_html__('Hide this tab from visitors and only show it to the profile owner when viewing their own profile.', WP_ULIKE_PRO_DOMAIN),
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
                'desc'    => esc_html__('Allow users to upload and manage their own profile pictures instead of using only Gravatar.', WP_ULIKE_PRO_DOMAIN),
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
                'desc'       => esc_html__('Default profile picture shown when a user hasn\'t uploaded a custom avatar and Gravatars are disabled.', WP_ULIKE_PRO_DOMAIN),
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
                'title'      => esc_html__( 'Avatar size', WP_ULIKE_PRO_DOMAIN),
                'desc'       => esc_html__('Maximum file size allowed when users upload avatar images. Larger files will be rejected.', WP_ULIKE_PRO_DOMAIN),
                'default'    => 2,
                'max'        => 50,
                'step'       => 1,
                'unit'       => 'MB',
                'dependency' => array( 'enable_local_avatars', '==', 'true' ),
            ),
            array(
                'id'         => 'max_avatar_width',
                'type'       => 'slider',
                'title'      => esc_html__( 'Avatar Maximum Width', WP_ULIKE_PRO_DOMAIN),
                'desc'       => esc_html__('Maximum width in pixels for uploaded avatars. Images wider than this will be automatically resized.', WP_ULIKE_PRO_DOMAIN),
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
                'title'   => esc_html__('Restrict WordPress Dashboard Access', WP_ULIKE_PRO_DOMAIN),
                'desc'    => esc_html__('Limit access to the WordPress admin dashboard based on user roles. Users without access will be redirected.', WP_ULIKE_PRO_DOMAIN),
            ),
            array(
                'id'         => 'hide_admin_bar',
                'type'       => 'switcher',
                'default'    => true,
                'title'      => esc_html__('Hide Admin Bar', WP_ULIKE_PRO_DOMAIN),
                'desc'       => esc_html__('Hide the WordPress admin bar from the front-end for users who don\'t have dashboard access.', WP_ULIKE_PRO_DOMAIN),
                'dependency' => array( 'enable_admin_limit_access', '==', 'true' )
            ),
            array(
                'id'         => 'dashboard_access_roles',
                'type'       => 'checkbox',
                'title'      => esc_html__('Dashboard Access Roles', WP_ULIKE_PRO_DOMAIN),
                'desc'       => esc_html__('Select which user roles can access the WordPress admin dashboard. Users with other roles will be redirected.', WP_ULIKE_PRO_DOMAIN),
                'options'    => wp_ulike_pro_get_user_roles_list( array( 'Administrator', 'Subscriber' ) ),
                'dependency' => array( 'enable_admin_limit_access', '==', 'true' )
            ),
            array(
                'id'         => 'dashboard_custom_redirect',
                'type'       => 'text',
                'default'    => home_url(),
                'title'      => esc_html__( 'Dashboard Redirect URL', WP_ULIKE_PRO_DOMAIN),
                'desc'       => esc_html__('URL where users without dashboard access are redirected when they try to access the admin area.', WP_ULIKE_PRO_DOMAIN),
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
                        'desc'     => esc_html__('A unique identifier for this share button set. Used in the shortcode to display these buttons (e.g., slug="my_share").', WP_ULIKE_PRO_DOMAIN),
                    ),
                    array(
                        'id'     => 'buttons',
                        'type'   => 'group',
                        'title'  => esc_html__('Share buttons', WP_ULIKE_PRO_DOMAIN)  . ' *',
                        'desc'   => esc_html__('Add one or more social networks to include in this share button set.', WP_ULIKE_PRO_DOMAIN),
                        'fields' => array(
                            array(
                                'id'       => 'network',
                                'type'     => 'select',
                                'title'    => esc_html__( 'Social networks', WP_ULIKE_PRO_DOMAIN),
                                'desc'     => esc_html__('Choose which social media platform this button will share to.', WP_ULIKE_PRO_DOMAIN),
                                'chosen'   => true,
                                'settings'   => array(
                                    'width' => '50%'
                                ),
                                'multiple' => false,
                                'options'  => array(
                                    'facebook'      => esc_html__( 'facebook', WP_ULIKE_PRO_DOMAIN),
                                    'linkedin'      => esc_html__( 'linkedin', WP_ULIKE_PRO_DOMAIN),
                                    'twitter'       => esc_html__( 'x', WP_ULIKE_PRO_DOMAIN),
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
                                'title'   => esc_html__( 'Button Label',WP_ULIKE_PRO_DOMAIN),
                                'desc'    => sprintf( esc_html__('Custom text displayed on the %s. Leave empty to use the default network name.', WP_ULIKE_PRO_DOMAIN), esc_html__('share button', WP_ULIKE_PRO_DOMAIN) ),
                            ),
                        )
                    ),
                    array(
                        'id'      => 'view',
                        'type'    => 'button_set',
                        'default' => 'icon_text',
                        'title'   => esc_html__( 'Button Display Style', WP_ULIKE_PRO_DOMAIN),
                        'desc'    => esc_html__('Choose how buttons are displayed: with icon and text, icon only, or text only.', WP_ULIKE_PRO_DOMAIN),
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
                        'title'   => esc_html__( 'Button Style', WP_ULIKE_PRO_DOMAIN),
                        'desc'    => esc_html__('Choose the visual style for buttons.', WP_ULIKE_PRO_DOMAIN),
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
                        'title'   => esc_html__( 'Button Shape', WP_ULIKE_PRO_DOMAIN),
                        'desc'    => esc_html__('Choose the corner style for share buttons.', WP_ULIKE_PRO_DOMAIN),
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
                        'title'   => esc_html__( 'Button Colors', WP_ULIKE_PRO_DOMAIN),
                        'desc'    => esc_html__('Use official brand colors for each social network, or apply custom colors to all buttons.', WP_ULIKE_PRO_DOMAIN),
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
                        'title'         => esc_html__( 'Before Content',WP_ULIKE_PRO_DOMAIN),
                        'desc'          => esc_html__('HTML content displayed before the buttons. Useful for adding separators or text.', WP_ULIKE_PRO_DOMAIN)
                    ),
                    array(
                        'id'            => 'after',
                        'type'          => 'wp_editor',
                        'tinymce'       => false,
                        'media_buttons' => false,
                        'quicktags'     => true,
                        'sanitize'      => false,
                        'title'         => esc_html__( 'After Content',WP_ULIKE_PRO_DOMAIN),
                        'desc'          => esc_html__('HTML content displayed after the buttons.', WP_ULIKE_PRO_DOMAIN)
                    ),
                    array(
                        'id'      => 'auto_display',
                        'type'    => 'radio',
                        'title'   => esc_html__( 'Auto Display', WP_ULIKE_PRO_DOMAIN),
                        'desc'    => esc_html__('Automatically show share buttons in relation to the like button, or use a custom hook for placement.', WP_ULIKE_PRO_DOMAIN),
                        'default' => 'none',
                        'options' => array(
                            'none'          => esc_html__( 'None', WP_ULIKE_PRO_DOMAIN),
                            'after_button'  => esc_html__( 'After Button', WP_ULIKE_PRO_DOMAIN),
                            'before_button' => esc_html__( 'Before Button', WP_ULIKE_PRO_DOMAIN),
                            'modal_display' => esc_html__( 'Modal After Vote', WP_ULIKE_PRO_DOMAIN),
                            'custom_hook'   => esc_html__( 'Custom Hook', WP_ULIKE_PRO_DOMAIN)
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
     * Converted from ULF to array-based structure for Optiwich
     *
     * @param array $fields Existing fields (pro lock field from free version)
     * @return array Email section fields
     */
    public function register_email_translations_section( $fields ){
        // Check license permission
        if( ! $this->has_permission ){
            return $this->get_permission_notice();
        }

        return array(
            array(
                'type'    => 'submessage',
                'style'   => 'info',
                'content' => esc_html__('You can use the following variables in all templates:', WP_ULIKE_PRO_DOMAIN) .
                '<br><br><code>{site_url}</code> <code>{site_name}</code> <code>{admin_email}</code> <code>{login_url}</code> <code>{profile_url}</code> <code>{logout_url}</code> <code>{display_name}</code> <code>{first_name}</code> <code>{last_name}</code> <code>{username}</code> <code>{email}</code> <code>{password_reset_link}</code> <code>{account_activation_link}</code>'
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
                'id'     => 'checkmail_email',
                'type'   => 'fieldset',
                'title'  => esc_html__( 'Account Welcome Email',WP_ULIKE_PRO_DOMAIN),
                'fields' => array(
                    array(
                        'id'      => 'subject',
                        'type'    => 'text',
                        'title'   => esc_html__( 'Subject Line',WP_ULIKE_PRO_DOMAIN),
                        'default' => esc_html__( 'Please check your email to activate your account.',WP_ULIKE_PRO_DOMAIN),
                    ),
                    array(
                        'id'      => 'body',
                        'type'    => 'wp_editor',
                        'default' => WP_Ulike_Pro_Mail::get_template( 'checkmail' ),
                        'title'   => esc_html__( 'Message Body',WP_ULIKE_PRO_DOMAIN)
                    ),
                ),
            ),
            array(
                'id'     => 'approved_email',
                'type'   => 'fieldset',
                'title'  => esc_html__( 'Account Welcome Email',WP_ULIKE_PRO_DOMAIN),
                'fields' => array(
                    array(
                        'id'      => 'subject',
                        'type'    => 'text',
                        'title'   => esc_html__( 'Subject Line',WP_ULIKE_PRO_DOMAIN),
                        'default' => esc_html__( 'Your account at {site_name} is now active',WP_ULIKE_PRO_DOMAIN),
                    ),
                    array(
                        'id'      => 'body',
                        'type'    => 'wp_editor',
                        'default' => WP_Ulike_Pro_Mail::get_template( 'approved' ),
                        'title'   => esc_html__( 'Message Body',WP_ULIKE_PRO_DOMAIN)
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
                'title'   => esc_html__( 'Mail appears from',WP_ULIKE_PRO_DOMAIN),
                'default' => get_bloginfo('admin_email'),
            ),
            array(
                'id'      => 'enable_html_email',
                'type'    => 'switcher',
                'default' => true,
                'title'   => esc_html__('Use HTML for E-mails?', WP_ULIKE_PRO_DOMAIN),
            )
        );
    }


    public function get_permission_notice(){
        return array(
            array(
                'type'    => 'submessage',
                'style'   => 'info',
                'content' => sprintf( '<p>%s</p><a class="button" href="%s">%s</a>', esc_html__( 'Features of the Pro version are only available once you have registered your license. If you don\'t yet have a license key, get WP ULike Pro now.' , WP_ULIKE_PRO_DOMAIN ), self_admin_url( 'admin.php?page=wp-ulike-pro-license' ), esc_html__( 'Activate License', WP_ULIKE_PRO_DOMAIN ) ),
            )
        );
    }
}