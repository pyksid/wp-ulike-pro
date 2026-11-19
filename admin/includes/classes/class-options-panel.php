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
        add_filter( 'wp_ulike_content_type_field_order', array( $this, 'content_type_field_order' ), 10, 1 );

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
    //             'title'      => esc_html__('Custom PHP Template','wp-ulike-pro'),
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
                    'title'   => esc_html__('Like Buttons on Images', 'wp-ulike-pro'),
                    'desc'    => esc_html__('Add like buttons to image attachments displayed on your site. Requires WordPress 5.6+ and a theme that uses the standard WordPress attachment image function.', 'wp-ulike-pro'),
                ),
                'filter_attachment_class' => array(
                    'id'         => 'filter_attachment_class',
                    'type'       => 'repeater',
                    'fields'     => array(
                        array(
                            'id'    => 'name',
                            'type'  => 'text',
                            'title' => esc_html__( 'Class name' ),
                            'desc'  => esc_html__('Enter the CSS class name that identifies the attachment image.', 'wp-ulike-pro')
                        ),
                    ),
                    'title'      => esc_html__('Filter By Class Name', 'wp-ulike-pro'),
                    'dependency' => array( 'enable_attachments', '==', 'true' ),
                    'desc'       => esc_html__('Show like buttons only on images with specific CSS classes. Add one class name per line (e.g., attachment-full, attachment-large).', 'wp-ulike-pro')
                ),
                'filter_attachment_size' => array(
                    'id'         => 'filter_attachment_size',
                    'type'       => 'repeater',
                    'fields'     => array(
                        array(
                            'id'    => 'name',
                            'type'  => 'text',
                            'title' => esc_html__( 'Image Size' ),
                            'desc'  => esc_html__('Enter the WordPress image size name (e.g., thumbnail, medium, large, full).', 'wp-ulike-pro')
                        ),
                    ),
                    'title'      => esc_html__('Filter By Attachment Size', 'wp-ulike-pro'),
                    'dependency' => array( 'enable_attachments', '==', 'true' ),
                    'desc'       => esc_html__('Show like buttons only on images displayed at specific sizes. Add one size name per line (e.g., large, thumbnail, medium).', 'wp-ulike-pro')
                ),
            )
        );

        return $this->insert_metadata_option(
            $options,
            esc_html__( 'If you already have votes, turn this on and then go to Tools → Maintenance → Advanced → Move Post Counters.', 'wp-ulike-pro' )
        );
    }

    /**
     * Enable meta options
     *
     * @param array $options
     * @return array
     */
    public function comment_type_options( $options ){
        return $this->insert_metadata_option(
            $options,
            esc_html__( 'If you already have votes, turn this on and then go to Tools → Maintenance → Advanced → Move Comment Counters.', 'wp-ulike-pro' )
        );
    }

    /**
     * Keep Pro fields in the same Content Types groups as free.
     *
     * @param string[] $order
     * @return string[]
     */
    public function content_type_field_order( $order ) {
        if ( ! is_array( $order ) ) {
            return $order;
        }

        if ( ! in_array( 'heading_data', $order, true ) ) {
            $meta_index = array_search( 'enable_metadata', $order, true );
            if ( false !== $meta_index ) {
                array_splice( $order, $meta_index, 0, array( 'heading_data' ) );
            }
        }

        return $order;
    }

    /**
     * Place WordPress meta storage after Likers, not inside vote rules.
     *
     * @param array  $options
     * @param string $help
     * @return array
     */
    protected function insert_metadata_option( $options, $help ) {
        $after = isset( $options['likers_count'] ) ? 'likers_count' : 'vote_limit_number';

        return wp_ulike_array_insert_after(
            $options,
            $after,
            array(
                'heading_data' => array(
                    'id'      => 'heading_data',
                    'type'    => 'heading',
                    'content' => esc_html__( 'Data storage', 'wp-ulike-pro' ),
                ),
                'enable_metadata' => array(
                    'id'      => 'enable_metadata',
                    'type'    => 'switcher',
                    'default' => true,
                    'title'   => esc_html__( 'Also store counts in WordPress', 'wp-ulike-pro' ),
                    'desc'    => esc_html__( 'Keep totals in normal WordPress meta so themes and plugins can read them with get_post_meta().', 'wp-ulike-pro' ) . ' ' . esc_html__( 'Keys:', 'wp-ulike-pro' ) . ' <code>like_amount</code>, <code>dislike_amount</code>, <code>net_votes</code>',
                    'help'    => $help,
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
                'title'   => sprintf( esc_html__('%s Prefix','wp-ulike-pro'), esc_html__('Dislike', 'wp-ulike-pro') ),
                'desc'    => esc_html__('Text shown before the count (e.g., "-" displays as "-125").', 'wp-ulike-pro')
            );
            $options[] = array(
                'id'      => 'undislike_prefix',
                'type'    => 'text',
                'default' => '-',
                'title'   => sprintf( esc_html__('%s Prefix','wp-ulike-pro'), esc_html__('Undislike', 'wp-ulike-pro') ),
                'desc'    => esc_html__('Text shown before the count (e.g., "-" displays as "-125").', 'wp-ulike-pro')
            );
        }

        if( $type === 'postfix' ){
            $options[] = array(
                'id'      => 'dislike_postfix',
                'type'    => 'text',
                'title'   => sprintf( esc_html__('%s Suffix','wp-ulike-pro'), esc_html__('Dislike', 'wp-ulike-pro') ),
                'desc'    => esc_html__('Text shown after the count (e.g., " dislikes" displays as "125 dislikes").', 'wp-ulike-pro')
            );
            $options[] = array(
                'id'      => 'undislike_postfix',
                'type'    => 'text',
                'title'   => sprintf( esc_html__('%s Suffix','wp-ulike-pro'), esc_html__('Undislike', 'wp-ulike-pro') ),
                'desc'    => esc_html__('Text shown after the count (e.g., " dislikes" displays as "125 dislikes").', 'wp-ulike-pro')
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
        $statistics_on   = wp_ulike_pro_feature_enabled( 'statistics' );

        $fields = array();

        if ( $statistics_on ) {
            $fields[] = array(
                'id'       => 'statistics_display_roles',
                'type'     => 'select',
                'title'    => esc_html__( 'Statistics Page Access', 'wp-ulike-pro'),
                'desc'     => esc_html__( 'Choose which user roles can access the Statistics page in the admin menu.','wp-ulike-pro' ),
                'chosen'   => true,
                'multiple' => true,
                'options'  => $user_roles_list
            );
        }

        $fields[] = array(
            'id'       => 'enable_meta_box',
            'type'     => 'select',
            'title'    => esc_html__( 'Show Display Panel In Post Editor', 'wp-ulike-pro' ),
            'desc'     => wp_ulike_pro_feature_enabled( 'schema' )
                ? esc_html__( 'Add a lightweight sidebar panel on selected post types for per-post button display overrides. Schema and FAQ markup is configured under Tools → Schema Generator.', 'wp-ulike-pro' )
                : esc_html__( 'Add a lightweight sidebar panel on selected post types for per-post button display overrides.', 'wp-ulike-pro' ),
            'chosen'   => true,
            'multiple' => true,
            'default'  => array('post', 'page'),
            'options'  => 'post_types'
        );

        if ( $statistics_on ) {
            $fields[] = array(
                'id'       => 'view_tracking_enabled_types',
                'type'     => 'checkbox',
                'title'    => esc_html__( 'Enable View Tracking', 'wp-ulike-pro' ),
                'desc'     => esc_html__( 'Track button views for accurate engagement rate calculations. Select which content types should have view tracking enabled.', 'wp-ulike-pro' ),
                'options'  => array(
                    'post'     => esc_html__( 'Posts' ),
                    'comment'  => esc_html__( 'Comments' ),
                    'activity' => esc_html__( 'Activities', 'wp-ulike-pro' ),
                    'topic'    => esc_html__( 'Topics', 'wp-ulike-pro' )
                ),
                'default'  => array( 'post' )
            );
        }

        $fields[] = array(
            'id'         => 'enable_serialize',
            'type'       => 'switcher',
            'default'    => true,
            'title'      => esc_html__('Enable Serialized Data Storage', 'wp-ulike-pro'),
            'desc'       => esc_html__('Store meta box data in a more efficient format, reducing database size and improving performance. If you\'re upgrading from an older version, enable this option first, then run "Convert to Serialized Format" under Tools → Maintenance → Advanced.', 'wp-ulike-pro')
        );

        if ( $statistics_on ) {
            // Registered here rather than in the free plugin because only Pro
            // records device, OS, and browser in the first place.
            $fields[] = array(
                'id'         => 'enable_pulse_meta_purge',
                'type'       => 'switcher',
                'default'    => false,
                'title'      => esc_html__('Delete Old Device Details', 'wp-ulike-pro'),
                'desc'       => esc_html__('Clear the device, OS, and browser recorded with each vote once it passes the age below. Votes, counters, and totals are never touched, and country is always kept — it costs almost nothing to store. Only the device and browser reports stop covering that period. Runs daily in the background.', 'wp-ulike-pro')
            );
            $fields[] = array(
                'id'         => 'pulse_meta_purge_days',
                'type'       => 'number',
                'default'    => 90,
                'min'        => 30,
                'title'      => esc_html__('Keep Device Details For', 'wp-ulike-pro'),
                'desc'       => esc_html__('Age in days before those details are cleared. Minimum 30.', 'wp-ulike-pro'),
                'dependency' => array( 'enable_pulse_meta_purge', '==', 'true' )
            );
        }

        // Append Pro general controls after blacklist entries.
        return wp_ulike_array_insert_after( $options, 'blacklist_entries', $fields );
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
                'title'     => esc_html__('Dislike','wp-ulike-pro'),
                'fields'    => array(
                    array(
                        'id'      => 'dislike',
                        'type'    => 'text',
                        'title'   => esc_html__('Button label','wp-ulike-pro'),
                        'desc'    => sprintf( esc_html__('Text displayed on the %s (e.g., "Dislike", "👎", "Downvote").', 'wp-ulike-pro'), esc_html__('Dislike Button', 'wp-ulike-pro') ),
                        'default' => 'Dislike'
                    ),
                )
            );
            $options['text_group']['tabs'][] = array(
                'title'     => esc_html__('Undislike','wp-ulike-pro'),
                'fields'    => array(
                    array(
                        'id'      => 'undislike',
                        'type'    => 'text',
                        'title'   => esc_html__('Button label','wp-ulike-pro'),
                        'desc'    => sprintf( esc_html__('Text displayed on the button %s (e.g., "Disliked", "👎", "Remove Dislike").', 'wp-ulike-pro'), esc_html__('after disliking', 'wp-ulike-pro') ),
                        'default' => 'Disliked'
                    ),
                )
            );
        }
        if( isset( $options['image_group']['tabs'] ) ){
            $options['image_group']['tabs'][] = array(
                'title'     => esc_html__('Dislike','wp-ulike-pro'),
                'fields'    => array(
                    array(
                        'id'           => 'dislike',
                        'type'         => 'upload',
                        'title'        => esc_html__('Button Image','wp-ulike-pro'),
                        'desc'         => esc_html__('Upload an image icon for the button state.', 'wp-ulike-pro'),
                        'library'      => 'image',
                        'placeholder'  => 'http://'
                    ),
                )
            );
            $options['image_group']['tabs'][] = array(
                'title'     => esc_html__('Undislike','wp-ulike-pro'),
                'fields'    => array(
                    array(
                        'id'           => 'undislike',
                        'type'         => 'upload',
                        'title'        => esc_html__('Button Image','wp-ulike-pro'),
                        'desc'         => esc_html__('Upload an image icon for the button state.', 'wp-ulike-pro'),
                        'library'      => 'image',
                        'placeholder'  => 'http://'
                    ),
                )
            );
        }

        // Add modal option
        if( isset( $options['logged_out_display_type']['options'] ) && wp_ulike_pro_feature_enabled( 'login_signup' ) ){
            $options['logged_out_display_type']['options']['modal'] = esc_html__('Login popup', 'wp-ulike-pro');
            $options['logged_out_display_type']['desc']            = esc_html__( 'Login prompt: a message with a login link. Button: the like button, click asks them to log in. Login popup: a form on this page (recommended).', 'wp-ulike-pro' );
        }

        // Add modal option
        if( isset( $options['likers_style']['options'] ) ){
            $options['likers_style']['options']['pile'] = esc_html__('Pile + Modal', 'wp-ulike-pro');
        }

        // Engagement templates (emoji / star) reuse these classic options — keep copy accurate.
        if ( isset( $options['counter_display_condition']['desc'] ) ) {
            $options['counter_display_condition']['desc'] = esc_html__(
                'Control when the counter is shown. For Star Rating this is the average and rating count; for Emoji Reactions it is the reaction totals.',
                'wp-ulike-pro'
            );
        }
        if ( isset( $options['hide_zero_counter']['desc'] ) ) {
            $options['hide_zero_counter']['desc'] = esc_html__(
                'Hide the counter when there are no votes, reactions, or ratings yet.',
                'wp-ulike-pro'
            );
        }
        if ( isset( $options['enable_likers_box']['desc'] ) ) {
            $options['enable_likers_box']['desc'] = esc_html__(
                'Show who engaged with this item — likers, reactors, or raters depending on the selected template.',
                'wp-ulike-pro'
            );
        }
        if ( isset( $options['likers_style']['desc'] ) ) {
            $options['likers_style']['desc'] = esc_html__(
                'Inline: show avatars next to the button. Popover: show avatars on hover. Pile + Modal: compact avatar stack that opens a full list.',
                'wp-ulike-pro'
            );
        }

        // Add percentage option
        $percentage_list = wp_ulike_pro_get_templates_list_by_attribute( 'is_percentage_support' );
        $options = wp_ulike_array_insert_after( $options, 'hide_zero_counter', array(
            'enable_percentage_values' => array(
                'id'         => 'enable_percentage_values',
                'type'       => 'switcher',
                'title'      => esc_html__('Show counts as percentages', 'wp-ulike-pro'),
                'desc'       => esc_html__('Display vote counts as percentages instead of numbers. Shows the ratio of likes to total votes (e.g., "75%" instead of "75").', 'wp-ulike-pro'),
                'dependency' => array( 'counter_display_condition|template', '!=|any', 'hidden|' . $percentage_list )
            )
        ) );

        // Add modal login template
        if ( wp_ulike_pro_feature_enabled( 'login_signup' ) ) {
            $options = wp_ulike_array_insert_after( $options, 'login_template', array(
                'modal_template' => array(
                    'id'         => 'modal_template',
                    'type'       => 'wp_editor',
                    'height'     => '100px',
                    'default'    => '[wp_ulike_pro_login_form ajax_toggle=1 redirect_to="current_page"]',
                    'title'      => esc_html__('Login popup content', 'wp-ulike-pro'),
                    'desc'       => esc_html__('What guests see in the popup. Shortcodes are allowed — the login form is already included.', 'wp-ulike-pro'),
                    'dependency' => array( 'logged_out_display_type|enable_only_logged_in_users', '==|==', 'modal|logged_in' )
                )
            ) );
        }

        // Pile modal title
        $options = wp_ulike_array_insert_after( $options, 'likers_style', array(
            'likers_modal_title' => array(
                'id'         => 'likers_modal_title',
                'type'       => 'text',
                'default' => __('Likers', 'wp-ulike-pro'),
                'title'      => esc_html__('Likers Modal Title', 'wp-ulike-pro'),
                'desc'       => esc_html__('Heading text displayed at the top of the likers modal popup.', 'wp-ulike-pro'),
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
                'title'      => esc_html__('Likers Modal Template', 'wp-ulike-pro'),
                'desc'       => esc_html__('Allowed Variables:', 'wp-ulike-pro') . ' <code>{up_profile_url}</code>, <code>{bp_profile_url}</code>, <code>{um_profile_url}</code>, <code>{avatar_url}</code>, <code>{display_name}</code>, <code>{first_name}</code>, <code>{last_name}</code>, <code>{username}</code>, <code>{email}</code>, <code>{user_id}</code>, <code>{user_status}</code>',
                'dependency' => array( 'enable_likers_box|likers_style', '==|any', 'true|pile'  ),
            )
        ) );

        if ( isset( $options['enable_auto_display'] ) && wp_ulike_pro_feature_enabled( 'display_automation' ) ) {
            $tools_url = admin_url( 'admin.php?page=wp-ulike-pro-tools&tab=display-automation' );
            $after     = isset( $options['auto_display_filter_post_types'] )
                ? 'auto_display_filter_post_types'
                : 'enable_auto_display';
            $options   = wp_ulike_array_insert_after(
                $options,
                $after,
                array(
                    'display_automation_notice' => array(
                        'id'      => 'display_automation_notice',
                        'type'    => 'submessage',
                        'style'   => 'info',
                        'content' => wp_kses_post(
                            sprintf(
                                /* translators: 1: opening anchor, 2: closing anchor */
                                esc_html__( 'Need WooCommerce reviews or per-page rules? Use %1$sDisplay Automation%2$s in Tools. Turn Automatic Display off there if you want to avoid a second button.', 'wp-ulike-pro' ),
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
        // Place under free "Vote Notices" (after ajax_error), not after Ultimate Member.
        $vote_notices = array(
            array(
                'id'      => 'dislike_notice',
                'type'    => 'text',
                'default' => __('Sorry! You Disliked This.','wp-ulike-pro'),
                'title'   => sprintf( esc_html__( '%s Notice Message', 'wp-ulike-pro'), esc_html__('Dislike', 'wp-ulike-pro') ),
                'desc'    => esc_html__( 'Confirmation message shown after a user action.', 'wp-ulike-pro')
            ),
            array(
                'id'      => 'undislike_notice',
                'type'    => 'text',
                'default' => __('Thanks! You Undisliked This.','wp-ulike-pro'),
                'title'   => sprintf( esc_html__( '%s Notice Message', 'wp-ulike-pro'), esc_html__('Undislike', 'wp-ulike-pro') ),
                'desc'    => esc_html__( 'Confirmation message shown after a user action.', 'wp-ulike-pro')
            ),
        );

        $merged   = array();
        $inserted = false;
        foreach ( (array) $options as $field ) {
            $merged[] = $field;
            if ( ! $inserted && ( $field['id'] ?? '' ) === 'ajax_error_notice' ) {
                foreach ( $vote_notices as $vote_notice ) {
                    $merged[] = $vote_notice;
                }
                $inserted = true;
            }
        }
        if ( ! $inserted ) {
            $merged = array_merge( $merged, $vote_notices );
        }
        $options = $merged;

        // Engagement Notices
        $options[] = array(
            'type'    => 'heading',
            'content' => esc_html__( 'Engagement Notices', 'wp-ulike-pro')
        );

        $options[] = array(
            'id'      => 'reaction_success_notice',
            'type'    => 'text',
            'default' => __( 'Thanks! You reacted with %s.', 'wp-ulike-pro' ),
            'title'   => esc_html__( 'Reaction Success', 'wp-ulike-pro' ),
            'desc'    => esc_html__( 'Use %s for the reaction label.', 'wp-ulike-pro' )
        );

        $options[] = array(
            'id'      => 'reaction_removed_notice',
            'type'    => 'text',
            'default' => __( 'Your reaction was removed.', 'wp-ulike-pro' ),
            'title'   => esc_html__( 'Reaction Removed', 'wp-ulike-pro' )
        );

        $options[] = array(
            'id'      => 'rated_star_notice',
            'type'    => 'text',
            'default' => __( 'Thanks! You rated this %d star.', 'wp-ulike-pro' ),
            'title'   => esc_html__( 'Rating Success (Singular)', 'wp-ulike-pro' ),
            'desc'    => esc_html__( 'Use %d for the star value. Shown when the user rates 1 star.', 'wp-ulike-pro' )
        );

        $options[] = array(
            'id'      => 'rated_stars_notice',
            'type'    => 'text',
            'default' => __( 'Thanks! You rated this %d stars.', 'wp-ulike-pro' ),
            'title'   => esc_html__( 'Rating Success (Plural)', 'wp-ulike-pro' ),
            'desc'    => esc_html__( 'Use %d for the star value. Shown when the user rates more than 1 star.', 'wp-ulike-pro' )
        );

        $options[] = array(
            'id'      => 'rating_removed_notice',
            'type'    => 'text',
            'default' => __( 'Your rating was removed.', 'wp-ulike-pro' ),
            'title'   => esc_html__( 'Rating Removed', 'wp-ulike-pro' )
        );

        $options[] = array(
            'id'      => 'engagement_feedback_notice',
            'type'    => 'text',
            'default' => __( 'Thanks for your feedback!', 'wp-ulike-pro' ),
            'title'   => esc_html__( 'Engagement Feedback', 'wp-ulike-pro' )
        );

        $options[] = array(
            'id'      => 'reaction_limit_notice',
            'type'    => 'text',
            'default' => __( 'You have reached the limit of %d reactions for this item.', 'wp-ulike-pro' ),
            'title'   => esc_html__( 'Reaction Limit Reached', 'wp-ulike-pro' ),
            'desc'    => esc_html__( 'Use %d for the maximum reactions allowed.', 'wp-ulike-pro' )
        );

        $options[] = array(
            'id'      => 'reaction_failed_notice',
            'type'    => 'text',
            'default' => __( 'Your reaction could not be saved. Please refresh and try again.', 'wp-ulike-pro' ),
            'title'   => esc_html__( 'Reaction Failed', 'wp-ulike-pro' )
        );

        $options[] = array(
            'id'      => 'no_engagers_notice',
            'type'    => 'text',
            'default' => __( 'No engagers yet.', 'wp-ulike-pro' ),
            'title'   => esc_html__( 'No Engagers', 'wp-ulike-pro' )
        );

        $options[] = array(
            'id'      => 'engagers_login_required_notice',
            'type'    => 'text',
            'default' => __( 'Please log in to view engagers.', 'wp-ulike-pro' ),
            'title'   => esc_html__( 'Engagers Login Required', 'wp-ulike-pro' )
        );

        // Engagement Labels (visible UI text)
        $options[] = array(
            'type'    => 'heading',
            'content' => esc_html__( 'Engagement Labels', 'wp-ulike-pro' )
        );

        $options[] = array(
            'id'      => 'engagement_rating_status',
            'type'    => 'text',
            'default' => __( 'Your rating: %1$d of %2$d', 'wp-ulike-pro' ),
            'title'   => esc_html__( 'Your Rating Status', 'wp-ulike-pro' ),
            'desc'    => esc_html__( 'Visible status under star ratings. Use %1$d for the user rating and %2$d for the max stars.', 'wp-ulike-pro' )
        );

        $options[] = array(
            'id'      => 'engagement_no_rating',
            'type'    => 'text',
            'default' => __( 'No rating', 'wp-ulike-pro' ),
            'title'   => esc_html__( 'No rating', 'wp-ulike-pro' )
        );

        // Notices
        $options[] = array(
            'type'    => 'heading',
            'content' => esc_html__( 'Forms Notices', 'wp-ulike-pro')
        );

        $options[] = array(
            'id'      => 'required_fields_notice',
            'type'    => 'text',
            'default' => __( 'Please enter required fields', 'wp-ulike-pro' ),
            'title'   => esc_html__( 'Please enter required fields', 'wp-ulike-pro')
        );

        $options[] = array(
            'id'      => 'permission_denied_notice',
            'type'    => 'text',
            'default' => __( 'Something went wrong. Please try again or contact the admin.', 'wp-ulike-pro' ),
            'title'   => esc_html__( 'Permission Denied', 'wp-ulike-pro')
        );

        $options[] = array(
            'id'      => 'login_success_notice',
            'type'    => 'text',
            'default' => __( 'Login successful', 'wp-ulike-pro' ),
            'title'   => esc_html__( 'Login successful', 'wp-ulike-pro')
        );

        $options[] = array(
            'id'      => 'login_failed_notice',
            'type'    => 'text',
            'default' => __( 'Invalid username or incorrect password!', 'wp-ulike-pro' ),
            'title'   => esc_html__( 'Login Failed', 'wp-ulike-pro')
        );

        $options[] = array(
            'id'      => 'password_reset_notice',
            'type'    => 'text',
            'default' => __( 'Your password has been reset.' ),
            'title'   => esc_html__( 'Password Reset' )
        );

        $options[] = array(
            'id'      => 'password_match_notice',
            'type'    => 'text',
            'default' => __( 'Oops! Password did not match! Try again.', 'wp-ulike-pro' ),
            'title'   => esc_html__( 'Password Match', 'wp-ulike-pro')
        );

        $options[] = array(
            'id'      => 'empty_username_notice',
            'type'    => 'text',
            'default' => __( 'Enter a username or email address.', 'wp-ulike-pro' ),
            'title'   => esc_html__( 'Empty Username', 'wp-ulike-pro')
        );

        $options[] = array(
            'id'      => 'invalidcombo_notice',
            'type'    => 'text',
            'default' => __( 'There is no account with that username or email address.', 'wp-ulike-pro' ),
            'title'   => esc_html__( 'Invalid Combo', 'wp-ulike-pro')
        );

        $options[] = array(
            'id'      => 'email_error_notice',
            'type'    => 'text',
            'default' => __( 'The email could not be sent.', 'wp-ulike-pro' ),
            'title'   => esc_html__( 'Email Error', 'wp-ulike-pro')
        );

        $options[] = array(
            'id'      => 'fill_signup_form_notice',
            'type'    => 'text',
            'default' => __( 'Error Occured please fill up the signup form carefully.', 'wp-ulike-pro' ),
            'title'   => esc_html__( 'Fill Signup Form', 'wp-ulike-pro')
        );

        $options[] = array(
            'id'      => 'error_occurred_notice',
            'type'    => 'text',
            'default' => __( 'An error has occurred! Please try again later.', 'wp-ulike-pro' ),
            'title'   => esc_html__( 'Error occurred', 'wp-ulike-pro')
        );

        $options[] = array(
            'id'      => 'signup_success_notice',
            'type'    => 'text',
            'default' => __( 'Signup successful', 'wp-ulike-pro' ),
            'title'   => esc_html__( 'Signup successful', 'wp-ulike-pro')
        );

        $options[] = array(
            'id'      => 'email_verification_notice',
            'type'    => 'text',
            'default' => __( 'Please check your email to activate your account.', 'wp-ulike-pro' ),
            'title'   => esc_html__( 'Email Verification Required', 'wp-ulike-pro' )
        );

        $options[] = array(
            'id'      => 'success_verification_notice',
            'type'    => 'text',
            'default' => __( 'Your email has been successfully verified. You can now log in.', 'wp-ulike-pro' ),
            'title'   => esc_html__( 'Email Successfully Verified', 'wp-ulike-pro' )
        );

        $options[] = array(
            'id'      => 'failed_verification_notice',
            'type'    => 'text',
            'default' => __( 'Verification failed. The link might be expired or invalid.', 'wp-ulike-pro' ),
            'title'   => esc_html__( 'Email Verification Failed', 'wp-ulike-pro' )
        );

        $options[] = array(
            'id'      => 'account_not_verified_notice',
            'type'    => 'text',
            'default' => __( 'Your account is not yet verified. Please check your email for the verification link.', 'wp-ulike-pro' ),
            'title'   => esc_html__( 'Account Not Verified', 'wp-ulike-pro' )
        );

        $options[] = array(
            'id'      => 'disabled_registration_notice',
            'type'    => 'text',
            'default' => __( 'Registration is currently disabled.', 'wp-ulike-pro' ),
            'title'   => esc_html__( 'Disabled Registration', 'wp-ulike-pro')
        );

        $options[] = array(
            'id'      => 'invalid_email_notice',
            'type'    => 'text',
            'default' => __( 'Email address is not valid!', 'wp-ulike-pro' ),
            'title'   => esc_html__( 'Invalid email address.' )
        );

        $options[] = array(
            'id'      => 'email_exist_notice',
            'type'    => 'text',
            'default' => __( 'Sorry, that email address is already used!' ),
            'title'   => esc_html__( 'Email Exist', 'wp-ulike-pro')
        );

        $options[] = array(
            'id'      => 'username_exist_notice',
            'type'    => 'text',
            'default' => __( 'Sorry, that username is already used!', 'wp-ulike-pro' ),
            'title'   => esc_html__( 'Username Exist', 'wp-ulike-pro')
        );

        $options[] = array(
            'id'      => 'profile_success_notice',
            'type'    => 'text',
            'default' => __( 'Profile data updated.', 'wp-ulike-pro' ),
            'title'   => esc_html__( 'Profile updated.' )
        );

        $options[] = array(
            'id'      => 'rate_limit_exceeded_notice',
            'type'    => 'text',
            'default' => __( 'Too many attempts. Please try again in %d minute(s).', 'wp-ulike-pro' ),
            'title'   => esc_html__( 'Rate Limit Exceeded', 'wp-ulike-pro' ),
            'desc'    => esc_html__( 'Use %d for the remaining minutes.', 'wp-ulike-pro' )
        );

        // Avatar
        $options[] = array(
            'type'    => 'heading',
            'content' => esc_html__( 'Upload Avatar Labels', 'wp-ulike-pro')
        );

        $options[] = array(
            'id'      => 'user_not_found',
            'type'    => 'text',
            'default' => __( 'Not found!','wp-ulike-pro'),
            'title'   => esc_html__( 'User Not Found Message', 'wp-ulike-pro')
        );

        $options[] = array(
            'id'      => 'avatar_upload_text',
            'type'    => 'text',
            'default' => __( 'Upload' ),
            'title'   => sprintf( esc_html__( 'Avatar %s Text', 'wp-ulike-pro'), esc_html__( 'Upload' ) )
        );

        $options[] = array(
            'id'      => 'avatar_edit_text',
            'type'    => 'text',
            'default' => __( 'Edit' ),
            'title'   => sprintf( esc_html__( 'Avatar %s Text', 'wp-ulike-pro'), esc_html__( 'Edit' ) )
        );

        $options[] = array(
            'id'      => 'avatar_delete_text',
            'type'    => 'text',
            'default' => __( 'Delete' ),
            'title'   => sprintf( esc_html__( 'Avatar %s Text', 'wp-ulike-pro'), esc_html__( 'Delete' ) )
        );

        $options[] = array(
            'id'      => 'avatar_logout_text',
            'type'    => 'text',
            'default' => __( 'Log Out' ),
            'title'   => esc_html__( 'Profile Log Out Text', 'wp-ulike-pro')
        );

        $options[] = array(
            'id'      => 'avatar_choose_picture',
            'type'    => 'text',
            'default' => __( 'Select' ),
            'title'   => esc_html__( 'Select' )
        );

        $options[] = array(
            'id'      => 'avatar_confirm_remove',
            'type'    => 'text',
            'default' => __( 'Are you sure you want to remove your avatar?', 'wp-ulike-pro' ),
            'title'   => esc_html__( 'Confirm Avatar Remove', 'wp-ulike-pro' )
        );

        $options[] = array(
            'id'      => 'avatar_please_select',
            'type'    => 'text',
            'default' => __( 'Please select an image file', 'wp-ulike-pro' ),
            'title'   => esc_html__( 'Please Select Image', 'wp-ulike-pro' )
        );

        $options[] = array(
            'id'      => 'avatar_file_size',
            'type'    => 'text',
            'default' => __( 'File size must be less than %sMB', 'wp-ulike-pro' ),
            'title'   => esc_html__( 'File Size Limit', 'wp-ulike-pro' ),
            'desc'    => esc_html__( 'Use %s for the max file size in MB.', 'wp-ulike-pro' )
        );

        $options[] = array(
            'id'      => 'avatar_min_size',
            'type'    => 'text',
            'default' => __( 'Image must be at least %sx%s pixels', 'wp-ulike-pro' ),
            'title'   => esc_html__( 'Minimum Image Size', 'wp-ulike-pro' ),
            'desc'    => esc_html__( 'Use %s for width and height.', 'wp-ulike-pro' )
        );

        $options[] = array(
            'id'      => 'avatar_uploaded_success',
            'type'    => 'text',
            'default' => __( 'Avatar uploaded successfully', 'wp-ulike-pro' ),
            'title'   => esc_html__( 'Avatar Uploaded', 'wp-ulike-pro' )
        );

        $options[] = array(
            'id'      => 'avatar_removed_success',
            'type'    => 'text',
            'default' => __( 'Avatar removed', 'wp-ulike-pro' ),
            'title'   => esc_html__( 'Avatar removed', 'wp-ulike-pro' )
        );

        $options[] = array(
            'id'      => 'avatar_upload_failed',
            'type'    => 'text',
            'default' => __( 'Upload failed.' ),
            'title'   => esc_html__( 'Avatar Upload Failed', 'wp-ulike-pro' )
        );

        $options[] = array(
            'id'      => 'avatar_save_text',
            'type'    => 'text',
            'default' => __( 'Save' ),
            'title'   => esc_html__( 'Avatar Save Text', 'wp-ulike-pro' )
        );

        $options[] = array(
            'id'      => 'avatar_cancel_text',
            'type'    => 'text',
            'default' => __( 'Cancel' ),
            'title'   => esc_html__( 'Avatar Cancel Text', 'wp-ulike-pro' )
        );

        $options[] = array(
            'id'      => 'profile_edit_text',
            'type'    => 'text',
            'default' => __( 'Edit Profile' ),
            'title'   => esc_html__( 'Edit Profile Text', 'wp-ulike-pro' )
        );

        $options[] = array(
            'id'      => 'profile_empty_tab',
            'type'    => 'text',
            'default' => __( 'This tab is looking a little empty!', 'wp-ulike-pro' ),
            'title'   => esc_html__( 'Empty Profile Tab', 'wp-ulike-pro' )
        );

        // login
        $options[] = array(
            'type'    => 'heading',
            'content' => esc_html__( 'Login Form Labels', 'wp-ulike-pro')
        );

        $options[] = array(
            'id'      => 'login_username',
            'type'    => 'text',
            'default' => __( 'Username' ),
            'title'   => esc_html__( 'Username' )
        );

        $options[] = array(
            'id'      => 'login_password',
            'type'    => 'text',
            'default' => __( 'Password' ),
            'title'   => esc_html__( 'Password' )
        );

        $options[] = array(
            'id'      => 'login_remember',
            'type'    => 'text',
            'default' => __( 'Remember Me' ),
            'title'   => esc_html__( 'Remember Me' )
        );

        $options[] = array(
            'id'      => 'login_button',
            'type'    => 'text',
            'default' => __( 'Log in' ),
            'title'   => esc_html__( 'Log in' )
        );

        $options[] = array(
            'id'      => 'login_reset_password',
            'type'    => 'text',
            'default' => __( 'Lost your password?' ),
            'title'   => esc_html__( 'Lost your password?' )
        );

        $options[] = array(
            'id'      => 'login_signup_message',
            'type'    => 'text',
            'default' => __( 'Don\'t have an account?', 'wp-ulike-pro' ),
            'title'   => esc_html__( 'Don\'t have an account?', 'wp-ulike-pro' )
        );

        $options[] = array(
            'id'      => 'login_signup_text',
            'type'    => 'text',
            'default' => __( 'Register' ),
            'title'   => esc_html__( 'Register' )
        );

        // signup
        $options[] = array(
            'type'    => 'heading',
            'content' => esc_html__( 'Signup Form Labels', 'wp-ulike-pro')
        );

        $options[] = array(
            'id'      => 'signup_username',
            'type'    => 'text',
            'default' => __( 'Username' ),
            'title'   => esc_html__( 'Username' )
        );

        $options[] = array(
            'id'      => 'signup_firstname',
            'type'    => 'text',
            'default' => __( 'First Name' ),
            'title'   => esc_html__( 'First Name' )
        );

        $options[] = array(
            'id'      => 'signup_lastname',
            'type'    => 'text',
            'default' => __( 'Last Name' ),
            'title'   => esc_html__( 'Last Name' )
        );

        $options[] = array(
            'id'      => 'signup_email',
            'type'    => 'text',
            'default' => __( 'Email Address', 'wp-ulike-pro' ),
            'title'   => esc_html__( 'Email Address', 'wp-ulike-pro' )
        );

        $options[] = array(
            'id'      => 'signup_password',
            'type'    => 'text',
            'default' => __( 'Password' ),
            'title'   => esc_html__( 'Password' )
        );

        $options[] = array(
            'id'      => 'signup_button',
            'type'    => 'text',
            'default' => __( 'Register' ),
            'title'   => esc_html__( 'Register' )
        );

        $options[] = array(
            'id'      => 'signup_login_message',
            'type'    => 'text',
            'default' => __( 'Already have an account?', 'wp-ulike-pro' ),
            'title'   => esc_html__( 'Already have an account?', 'wp-ulike-pro' )
        );

        $options[] = array(
            'id'      => 'signup_login_text',
            'type'    => 'text',
            'default' => __( 'Log In' ),
            'title'   => esc_html__( 'Log In' )
        );

        // reset password
        $options[] = array(
            'type'    => 'heading',
            'content' => esc_html__( 'Reset Password Form Labels', 'wp-ulike-pro')
        );

        $options[] = array(
            'id'      => 'rp_reset_message',
            'type'    => 'text',
            'default' => __( 'To reset your password, please enter your email address or username below', 'wp-ulike-pro' ),
            'title'   => esc_html__( 'To reset your password...', 'wp-ulike-pro' )
        );

        $options[] = array(
            'id'      => 'rp_change_message',
            'type'    => 'text',
            'default' => __( 'Enter your new password below.', 'wp-ulike-pro' ),
            'title'   => esc_html__( 'Enter your new password below.', 'wp-ulike-pro' )
        );

        $options[] = array(
            'id'      => 'rp_mail_message',
            'type'    => 'text',
            'default' => __( 'Check your e-mail address linked to the account for the confirmation link, including the spam or junk folder.
				', 'wp-ulike-pro' ),
            'title'   => esc_html__( 'Email Confirmation', 'wp-ulike-pro' )
        );

        $options[] = array(
            'id'      => 'rp_username',
            'type'    => 'text',
            'default' => __( 'Username or Email Address' ),
            'title'   => esc_html__( 'Username or Email Address' )
        );

        $options[] = array(
            'id'      => 'rp_new_pass',
            'type'    => 'text',
            'default' => __( 'New Password' ),
            'title'   => esc_html__( 'New Password' )
        );

        $options[] = array(
            'id'      => 'rp_re_new_pass',
            'type'    => 'text',
            'default' => __( 'Confirm new password' ),
            'title'   => esc_html__( 'Confirm new password' )
        );

        $options[] = array(
            'id'      => 'rp_invalidkey',
            'type'    => 'text',
            'default' => __( 'Your password reset link appears to be invalid. Please request a new link below.', 'wp-ulike-pro' ),
            'title'   => esc_html__( 'Your password reset link appears to be invalid', 'wp-ulike-pro' )
        );

        $options[] = array(
            'id'      => 'rp_expiredkey',
            'type'    => 'text',
            'default' => __( 'Your password reset link has expired. Please request a new link below.', 'wp-ulike-pro' ),
            'title'   => esc_html__( 'Your password reset link has expired', 'wp-ulike-pro' )
        );

        $options[] = array(
            'id'      => 'rp_reset_button',
            'type'    => 'text',
            'default' => __( 'Get New Password' ),
            'title'   => esc_html__( 'Get New Password' )
        );

        $options[] = array(
            'id'      => 'rp_change_button',
            'type'    => 'text',
            'default' => __( 'Reset Password' ),
            'title'   => esc_html__( 'Reset Password' )
        );

        $options[] = array(
            'id'      => 'rp_login_message',
            'type'    => 'text',
            'default' => __( 'Go to login page', 'wp-ulike-pro' ),
            'title'   => esc_html__( 'Go to login page', 'wp-ulike-pro' )
        );

        // edit account
        $options[] = array(
            'type'    => 'heading',
            'content' => esc_html__( 'Edit Account Form Labels', 'wp-ulike-pro')
        );

        $options[] = array(
            'id'      => 'ea_firstname',
            'type'    => 'text',
            'default' => __( 'First Name' ),
            'title'   =>esc_html__( 'First Name' )
        );

        $options[] = array(
            'id'      => 'ea_lastname',
            'type'    => 'text',
            'default' => __( 'Last Name' ),
            'title'   =>esc_html__( 'Last Name' )
        );

        $options[] = array(
            'id'      => 'ea_website',
            'type'    => 'text',
            'default' => __( 'Website' ),
            'title'   => esc_html__( 'Website' )
        );

        $options[] = array(
            'id'      => 'ea_description',
            'type'    => 'text',
            'default' => __( 'Biographical Info' ),
            'title'   => esc_html__( 'Biographical Info' )
        );

        $options[] = array(
            'id'      => 'ea_email',
            'type'    => 'text',
            'default' => __( 'Email Address', 'wp-ulike-pro' ),
            'title'   => esc_html__( 'Email Address', 'wp-ulike-pro' )
        );

        $options[] = array(
            'id'      => 'ea_avatar',
            'type'    => 'text',
            'default' => __( 'Upload' ),
            'title'   => esc_html__( 'Upload' )
        );

        $options[] = array(
            'id'      => 'ea_button',
            'type'    => 'text',
            'default' => __( 'Submit' ),
            'title'   => esc_html__( 'Submit' )
        );

        $options[] = array(
            'id'      => 'ea_permission_message',
            'type'    => 'text',
            'default' => __( 'You don\'t have access to edit the information on this page!', 'wp-ulike-pro' ),
            'title'   => esc_html__( 'You don\'t have access Message', 'wp-ulike-pro' )
        );

        // Two Factor
        $options[] = array(
            'type'    => 'heading',
            'content' => esc_html__( 'Two Factor Notices', 'wp-ulike-pro')
        );

        $options[] = array(
            'id'      => 'two_factor_field_title',
            'type'    => 'text',
            'default' => __( 'Enter the six-digit code from the application', 'wp-ulike-pro' ),
            'title'   => esc_html__( 'Two Factor Title', 'wp-ulike-pro')
        );

        $options[] = array(
            'id'      => 'incorrect_tfa_notice',
            'type'    => 'text',
            'default' => __( 'The one-time password (TFA code) you entered was incorrect', 'wp-ulike-pro' ),
            'title'   => esc_html__( 'Incorrect one-time password message', 'wp-ulike-pro')
        );

        // Recaptcha
        $options[] = array(
            'type'    => 'heading',
            'content' => esc_html__( 'Recaptcha Notices', 'wp-ulike-pro')
        );

        $options[] = array(
            'id'      => 'missing_input_secret_notice',
            'type'    => 'text',
            'default' => __( 'The secret parameter is missing.', 'wp-ulike-pro' ),
            'title'   => esc_html__( 'Recaptcha Missing Secret.', 'wp-ulike-pro')
        );

        $options[] = array(
            'id'      => 'invalid_input_secret_notice',
            'type'    => 'text',
            'default' => __( 'The parameter is invalid or malformed.', 'wp-ulike-pro' ),
            'title'   => esc_html__( 'Recaptcha Invalid Secret.', 'wp-ulike-pro')
        );

        $options[] = array(
            'id'      => 'missing_input_response_notice',
            'type'    => 'text',
            'default' => __( 'Please confirm you are not a robot', 'wp-ulike-pro' ),
            'title'   => esc_html__( 'Recaptcha Missing Input.', 'wp-ulike-pro')
        );

        $options[] = array(
            'id'      => 'invalid_input_response_notice',
            'type'    => 'text',
            'default' => __( 'The parameter is invalid or malformed.', 'wp-ulike-pro' ),
            'title'   => esc_html__( 'Recaptcha Invalid Input.', 'wp-ulike-pro')
        );

        $options[] = array(
            'id'      => 'bad_request_notice',
            'type'    => 'text',
            'default' => __( 'The request is invalid or malformed.', 'wp-ulike-pro' ),
            'title'   => esc_html__( 'Recaptcha Bad Request.', 'wp-ulike-pro')
        );

        $options[] = array(
            'id'      => 'timeout_or_duplicate_notice',
            'type'    => 'text',
            'default' => __( 'The response is no longer valid: either is too old or has been used previously.', 'wp-ulike-pro' ),
            'title'   => esc_html__( 'The response is no longer valid.', 'wp-ulike-pro')
        );

        $options[] = array(
            'id'      => 'undefined_notice',
            'type'    => 'text',
            'default' => __( 'Undefined reCAPTCHA error.', 'wp-ulike-pro' ),
            'title'   => esc_html__( 'Undefined reCAPTCHA error.', 'wp-ulike-pro')
        );

        $options[] = array(
            'id'      => 'score_notice',
            'type'    => 'text',
            'default' => __( 'It is very likely a bot.', 'wp-ulike-pro' ),
            'title'   => esc_html__( 'It is very likely a bot.', 'wp-ulike-pro')
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
                'content' => esc_html__('Warning: Please never cache pages "Login", "Password Reset", "Register", "Edit Account", "User Profiles". These pages may work wrong if cached.', 'wp-ulike-pro')
            ),
            array(
                'id'      => 'login_core_page',
                'type'    => 'select',
                'chosen'  => true,
                'ajax'    => true,
                'title'   => sprintf( esc_html__('Select %s Page', 'wp-ulike-pro'), esc_html__( 'Log In' ) ),
                'desc'    => esc_html__('Choose the page that contains your login form shortcode. This page will be used for user authentication.', 'wp-ulike-pro'),
                'options' => 'pages'
            ),
            array(
                'id'         => 'login_custom_redirect',
                'type'       => 'text',
                'title'      => esc_html__( 'Login Redirect URL', 'wp-ulike-pro'),
                'desc'       => sprintf( esc_html__('URL where users are redirected after %s. Leave empty to redirect to the %s.', 'wp-ulike-pro'), esc_html__('successful login', 'wp-ulike-pro'), esc_html__( 'Previous page' ) ),
                'dependency' => array( 'login_core_page', '!=', '' )
            ),
            array(
                'id'         => 'logout_custom_redirect',
                'type'       => 'text',
                'title'      => esc_html__( 'Logout Redirect URL', 'wp-ulike-pro'),
                'desc'       => sprintf( esc_html__('URL where users are redirected after %s. Leave empty to redirect to the %s.', 'wp-ulike-pro'), esc_html__('logging out', 'wp-ulike-pro'), esc_html__('home page', 'wp-ulike-pro') ),
                'dependency' => array( 'login_core_page', '!=', '' )
            ),
            array(
                'id'         => 'enable_wp_login_redirect',
                'type'       => 'switcher',
                'default'    => false,
                'title'      => esc_html__('Redirect WordPress Default Login', 'wp-ulike-pro'),
                'desc'       => esc_html__( 'You can easily replace default WordPress login page with your own custom login page.', 'wp-ulike-pro' ),
                'dependency' => array( 'login_core_page', '!=', '' )
            ),
            array(
                'id'      => 'signup_core_page',
                'type'    => 'select',
                'chosen'  => true,
                'ajax'    => true,
                'title'   => sprintf( esc_html__('Select %s Page', 'wp-ulike-pro'), esc_html__( 'Sign up' ) ),
                'desc'    => esc_html__('Choose the page that contains your form shortcode.', 'wp-ulike-pro'),
                'options' => 'pages'
            ),
            array(
                'id'         => 'signup_custom_redirect',
                'type'       => 'text',
                'title'      => esc_html__( 'Signup Redirect URL', 'wp-ulike-pro'),
                'desc'       => sprintf( esc_html__('URL where users are redirected after %s. Leave empty to redirect to the %s.', 'wp-ulike-pro'), esc_html__('successful registration', 'wp-ulike-pro'), esc_html__('login page', 'wp-ulike-pro') ),
                'dependency' => array( 'signup_core_page', '!=', '' )
            ),
            array(
                'id'      => 'signup_status',
                'type'    => 'select',
                'title'   => esc_html__('Signup Status', 'wp-ulike-pro'),
                'desc'    => esc_html__('Choose whether new users are automatically approved or must verify their email address first.', 'wp-ulike-pro'),
                'default' => 'approved',
                'options' => [
                    'approved'	=> __( 'Auto Approve', 'wp-ulike-pro' ),
                    'checkmail' => __( 'Require Email Activation', 'wp-ulike-pro' )
                ],
                'dependency' => array( 'signup_core_page', '!=', '' )
            ),
            array(
                'id'         => 'signup_enable_auto_login',
                'type'       => 'switcher',
                'default'    => false,
                'title'      => esc_html__('Enable Auto Login After Signup', 'wp-ulike-pro'),
                'desc'       => esc_html__('Automatically log users in immediately after they complete registration. Only works when signup status is set to Auto Approve.', 'wp-ulike-pro'),
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
                'title'   => esc_html__('Select Reset Password Page', 'wp-ulike-pro'),
                'desc'    => esc_html__('Choose the page that contains your form shortcode.', 'wp-ulike-pro'),
                'options' => 'pages'
            ),
            array(
                'id'      => 'edit_account_core_page',
                'type'    => 'select',
                'chosen'  => true,
                'ajax'    => true,
                'title'   => esc_html__('Select Edit Account Page', 'wp-ulike-pro'),
                'desc'    => esc_html__('Choose the page that contains your account edit form shortcode where users can update their profile information.', 'wp-ulike-pro'),
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
                'title'         => esc_html__( 'Logged In Message', 'wp-ulike-pro'),
                'desc'          => esc_html__('Custom message displayed to logged-in users. You can use variables like {display_name}, {avatar_url}, {profile_url}, {logout_url}.', 'wp-ulike-pro')
            ),
            array(
                'id'      => 'enable_2fa',
                'type'    => 'switcher',
                'default' => false,
                'title'   => esc_html__('Enable 2-factor Authentication', 'wp-ulike-pro'),
                'desc'    => esc_html__( 'Increase security for user accounts by using multiple authentication steps.', 'wp-ulike-pro' )
                . '<br>'  . sprintf( '<em>' .  esc_html__( 'After activating this option, put the %s shortcode in the profile tabs or any another page.', 'wp-ulike-pro' ) . '</em>', "<code>[wp_ulike_pro_two_factor_setup]</code>")
            ),
            array(
                'id'      => 'enable_recaptcha',
                'type'    => 'switcher',
                'default' => false,
                'title'   => esc_html__('Enable Google reCAPTCHA', 'wp-ulike-pro'),
                'desc'    => esc_html__( 'Turn on or off your Google reCAPTCHA on your site registration, login and reset password forms by default.', 'wp-ulike-pro' ),
            ),
            array(
                'id'         => 'global_recaptcha',
                'type'       => 'switcher',
                'default'    => false,
                'title'      => esc_html__('Enable Global reCAPTCHA Scripts', 'wp-ulike-pro'),
                'desc'       => esc_html__( 'Load reCAPTCHA scripts on all pages. Enable this if you use reCAPTCHA in modal login forms or other areas outside the standard login/register pages.', 'wp-ulike-pro' ),
                'dependency' => array( 'enable_recaptcha', '==', 'true' )
            ),
            array(
                'id'      => 'recaptcha_version',
                'type'    => 'select',
                'desc'    => esc_html__( 'Choose the type of reCAPTCHA for this site key. A site key only works with a single reCAPTCHA site type.', 'wp-ulike-pro' ),
                'default' => 'v3',
                'title'   => esc_html__('reCAPTCHA type', 'wp-ulike-pro'),
                'options' => array(
                    'v2' => esc_html__( 'reCAPTCHA v2', 'wp-ulike-pro' ),
                    'v3' => esc_html__( 'reCAPTCHA v3', 'wp-ulike-pro' ),
                ),
                'dependency' => array( 'enable_recaptcha', '==', 'true' )
            ),

            /* reCAPTCHA v3 */
            array(
                'id'         => 'v3_recaptcha_sitekey',
                'type'       => 'text',
                'title'      => esc_html__( 'Site Key', 'wp-ulike-pro' ),
                'desc'       => __( 'You can register your site and generate a site key via <a href="https://www.google.com/recaptcha/">Google reCAPTCHA</a>', 'wp-ulike-pro' ),
                'dependency' => array( 'recaptcha_version|enable_recaptcha', '==|==', 'v3|true' )
            ),
            array(
                'id'         => 'v3_recaptcha_secretkey',
                'type'       => 'text',
                'title'      => esc_html__( 'Secret Key', 'wp-ulike-pro' ),
                'desc'       => __( 'Keep this a secret. You can get your secret key via <a href="https://www.google.com/recaptcha/">Google reCAPTCHA</a>', 'wp-ulike-pro' ),
                'dependency' => array( 'recaptcha_version|enable_recaptcha', '==|==', 'v3|true' )
            ),

            /* reCAPTCHA v2 */

            array(
                'id'         => 'v2_recaptcha_sitekey',
                'type'       => 'text',
                'title'      => esc_html__( 'Site Key', 'wp-ulike-pro' ),
                'desc'       => __( 'You can register your site and generate a site key via <a href="https://www.google.com/recaptcha/">Google reCAPTCHA</a>', 'wp-ulike-pro' ),
                'dependency' => array( 'recaptcha_version|enable_recaptcha', '==|==', 'v2|true' )
            ),
            array(
                'id'         => 'v2_recaptcha_secretkey',
                'type'       => 'text',
                'title'      => esc_html__( 'Secret Key', 'wp-ulike-pro' ),
                'desc'       => __( 'Keep this a secret. You can get your secret key via <a href="https://www.google.com/recaptcha/">Google reCAPTCHA</a>', 'wp-ulike-pro' ),
                'dependency' => array( 'recaptcha_version|enable_recaptcha', '==|==', 'v2|true' )
            ),
            array(
                'id'      => 'v2_recaptcha_type',
                'type'    => 'select',
                'default' => 'image',
                'title'   => esc_html__( 'Type' ),
                'desc'    => esc_html__( 'The type of reCAPTCHA to serve.', 'wp-ulike-pro' ),
                'options' => array(
                    'audio' => esc_html__( 'Audio' ),
                    'image' => esc_html__( 'Image' ),
                ),
                'dependency' => array( 'recaptcha_version|enable_recaptcha', '==|==', 'v2|true' )
            ),
            array(
                'id'      => 'v2_recaptcha_language_code',
                'type'    => 'select',
                'default' => 'en',
                'title'   => esc_html__( 'Language' ),
                'desc'    => esc_html__( 'Select the language to be used in your reCAPTCHA.', 'wp-ulike-pro' ),
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
                'title'   => esc_html__( 'Theme' ),
                'desc'    => esc_html__( 'Select a color theme of the widget.', 'wp-ulike-pro' ),
                'options' => array(
                    'dark'  => esc_html__( 'Dark' ),
                    'light' => esc_html__( 'Light' ),
                ),
                'dependency' => array( 'recaptcha_version|enable_recaptcha', '==|==', 'v2|true' )
            ),
            array(
                'id'      => 'v2_recaptcha_size',
                'type'    => 'select',
                'default' => 'normal',
                'title'   => esc_html__( 'Size' ),
                'desc'    => esc_html__( 'Choose the size of the reCAPTCHA widget. Compact is smaller and suitable for mobile devices.', 'wp-ulike-pro' ),
                'options' => array(
                    'compact'   => esc_html__( 'Compact', 'wp-ulike-pro' ),
                    'normal'    => esc_html__( 'Normal', 'wp-ulike-pro' )
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
                'content' => esc_html__('Enhance your user experience by integrating seamless social logins on your platform. Whether you prefer to auto-display login options or manually place them with our specialized following shortcode, this panel provides a streamlined configuration process to get you set up swiftly. Dive in and optimize your user onboarding today!', 'wp-ulike-pro') .
                '<br><br><code>[wp_ulike_pro_social_login]</code>'
            ),
            array(
                'id'         => 'enable_social_login',
                'type'       => 'switcher',
                'default'    => false,
                'title'      => esc_html__('Enable Social Logins', 'wp-ulike-pro'),
                'desc'       => esc_html__('Allow users to log in or register using their social media accounts instead of creating a new account.', 'wp-ulike-pro')
            ),
            array(
                'id'         => 'social_logins',
                'type'       => 'group',
                'title'      => esc_html__('Social networks', 'wp-ulike-pro'),
                'dependency' => array( 'enable_social_login', '==', 'true' ),
                'fields'     => array(
                    array(
                        'id'       => 'network',
                        'type'     => 'select',
                        'title'    => esc_html__( 'Network' ),
                        'settings'   => array(
                            'width' => '50%'
                        ),
                        'chosen'   => true,
                        'multiple' => false,
                        'options'  => array(
                            'Facebook'  => esc_html__( 'Facebook' ),
                            'GitHub'    => esc_html__( 'GitHub' ),
                            'Google'    => esc_html__( 'Google' ),
                            'Twitter'   => esc_html__( 'X' ),
                            'Amazon'    => esc_html__( 'Amazon' ),
                            'LinkedIn'  => esc_html__( 'LinkedIn' ),
                            'Apple'     => esc_html__( 'Apple', 'wp-ulike-pro'),
                            'WordPress' => esc_html__( 'WordPress' ),
                            'Yahoo'     => esc_html__( 'Yahoo', 'wp-ulike-pro'),
                            'Slack'     => esc_html__( 'Slack', 'wp-ulike-pro'),
                            'Medium'    => esc_html__( 'Medium' ),
                            'Dribbble'  => esc_html__( 'Dribbble' ),
                            'Paypal'    => esc_html__( 'Paypal', 'wp-ulike-pro')
                        )
                    ),
                    array(
                        'id'    => 'login_label',
                        'type'  => 'text',
                        'title' => esc_html__( 'Login Button Text','wp-ulike-pro'),
                        'desc'  => esc_html__( 'Controls the text displayed on the button.','wp-ulike-pro')
                    ),
                    array(
                        'id'    => 'link_label',
                        'type'  => 'text',
                        'title' => esc_html__( 'Link Button Text','wp-ulike-pro'),
                        'desc'  => esc_html__( 'Controls the text displayed on the button.','wp-ulike-pro')
                    ),
                    array(
                        'id'    => 'key',
                        'type'  => 'text',
                        'title' => esc_html__( 'Client ID','wp-ulike-pro'),
                        'desc'  => esc_html__('Your app ID', 'wp-ulike-pro')
                    ),
                    array(
                        'id'    => 'secret',
                        'type'  => 'text',
                        'title' => esc_html__( 'Client Secret', 'wp-ulike-pro' ),
                        'desc'  => esc_html__('Your app secret', 'wp-ulike-pro')
                    ),
                    array(
                        'id'      => 'disable',
                        'type'    => 'switcher',
                        'default' => false,
                        'title'   => esc_html__('Disable This Network', 'wp-ulike-pro'),
                        'desc'    => esc_html__('Temporarily disable this social network without deleting its configuration.', 'wp-ulike-pro'),
                    ),
                    array(
                        'type'       => 'submessage',
                        'style'      => 'normal',
                        'content'    => esc_html__( 'The redirect URI is:', 'wp-ulike-pro') . ' <code>' . WP_Ulike_Pro_Permalinks::get_social_login_callback_url( 'Facebook' ) . '</code>',
                        'dependency' => array( 'network', '==', 'Facebook' )
                    ),
                    array(
                        'type'       => 'submessage',
                        'style'      => 'normal',
                        'content'    => esc_html__( 'The redirect URI is:', 'wp-ulike-pro') . ' <code>' . WP_Ulike_Pro_Permalinks::get_social_login_callback_url( 'GitHub' ) . '</code>',
                        'dependency' => array( 'network', '==', 'GitHub' )
                    ),
                    array(
                        'type'       => 'submessage',
                        'style'      => 'normal',
                        'content'    => esc_html__( 'The redirect URI is:', 'wp-ulike-pro') . ' <code>' . WP_Ulike_Pro_Permalinks::get_social_login_callback_url( 'Google' ) . '</code>',
                        'dependency' => array( 'network', '==', 'Google' )
                    ),
                    array(
                        'type'       => 'submessage',
                        'style'      => 'normal',
                        'content'    => esc_html__( 'The redirect URI is:', 'wp-ulike-pro') . ' <code>' . WP_Ulike_Pro_Permalinks::get_social_login_callback_url( 'Twitter' ) . '</code>',
                        'dependency' => array( 'network', '==', 'Twitter' )
                    ),
                    array(
                        'type'       => 'submessage',
                        'style'      => 'normal',
                        'content'    => esc_html__( 'The redirect URI is:', 'wp-ulike-pro') . ' <code>' . WP_Ulike_Pro_Permalinks::get_social_login_callback_url( 'Amazon' ) . '</code>',
                        'dependency' => array( 'network', '==', 'Amazon' )
                    ),
                    array(
                        'type'       => 'submessage',
                        'style'      => 'normal',
                        'content'    => esc_html__( 'The redirect URI is:', 'wp-ulike-pro') . ' <code>' . WP_Ulike_Pro_Permalinks::get_social_login_callback_url( 'LinkedIn' ) . '</code>',
                        'dependency' => array( 'network', '==', 'LinkedIn' )
                    ),
                    array(
                        'type'       => 'submessage',
                        'style'      => 'normal',
                        'content'    => esc_html__( 'The redirect URI is:', 'wp-ulike-pro') . ' <code>' . WP_Ulike_Pro_Permalinks::get_social_login_callback_url( 'Paypal' ) . '</code>',
                        'dependency' => array( 'network', '==', 'Paypal' )
                    ),
                    array(
                        'type'       => 'submessage',
                        'style'      => 'normal',
                        'content'    => esc_html__( 'The redirect URI is:', 'wp-ulike-pro') . ' <code>' . WP_Ulike_Pro_Permalinks::get_social_login_callback_url( 'Apple' ) . '</code>',
                        'dependency' => array( 'network', '==', 'Apple' )
                    ),
                    array(
                        'type'       => 'submessage',
                        'style'      => 'normal',
                        'content'    => esc_html__( 'The redirect URI is:', 'wp-ulike-pro') . ' <code>' . WP_Ulike_Pro_Permalinks::get_social_login_callback_url( 'WordPress' ) . '</code>',
                        'dependency' => array( 'network', '==', 'WordPress' )
                    ),
                    array(
                        'type'       => 'submessage',
                        'style'      => 'normal',
                        'content'    => esc_html__( 'The redirect URI is:', 'wp-ulike-pro') . ' <code>' . WP_Ulike_Pro_Permalinks::get_social_login_callback_url( 'Yahoo' ) . '</code>',
                        'dependency' => array( 'network', '==', 'Yahoo' )
                    ),
                    array(
                        'type'       => 'submessage',
                        'style'      => 'normal',
                        'content'    => esc_html__( 'The redirect URI is:', 'wp-ulike-pro') . ' <code>' . WP_Ulike_Pro_Permalinks::get_social_login_callback_url( 'Slack' ) . '</code>',
                        'dependency' => array( 'network', '==', 'Slack' )
                    ),
                    array(
                        'type'       => 'submessage',
                        'style'      => 'normal',
                        'content'    => esc_html__( 'The redirect URI is:', 'wp-ulike-pro') . ' <code>' . WP_Ulike_Pro_Permalinks::get_social_login_callback_url( 'Medium' ) . '</code>',
                        'dependency' => array( 'network', '==', 'Medium' )
                    ),
                    array(
                        'type'       => 'submessage',
                        'style'      => 'normal',
                        'content'    => esc_html__( 'The redirect URI is:', 'wp-ulike-pro') . ' <code>' . WP_Ulike_Pro_Permalinks::get_social_login_callback_url( 'Dribbble' ) . '</code>',
                        'dependency' => array( 'network', '==', 'Dribbble' )
                    ),
                )
            ),
            array(
                'id'      => 'social_login_view',
                'type'    => 'button_set',
                'default' => 'icon_text',
                'title'   => esc_html__( 'Button Display Style', 'wp-ulike-pro'),
                'desc'    => esc_html__('Choose how buttons are displayed: with icon and text, icon only, or text only.', 'wp-ulike-pro'),
                'options' => array(
                    'icon_text' => esc_html__( 'Icon & Text', 'wp-ulike-pro'),
                    'icon'      => esc_html__( 'Icon' ),
                    'text'      => esc_html__( 'Text' )
                ),
                'dependency' => array( 'enable_social_login', '==', 'true' )
            ),
            array(
                'id'      => 'social_login_skin',
                'type'    => 'button_set',
                'default' => 'gradient',
                'title'   => esc_html__( 'Button Style', 'wp-ulike-pro'),
                'desc'    => esc_html__('Choose the visual style for buttons.', 'wp-ulike-pro'),
                'options' => array(
                    'gradient' => esc_html__( 'Gradient', 'wp-ulike-pro'),
                    'minimal'  => esc_html__( 'Minimal', 'wp-ulike-pro'),
                    'framed'   => esc_html__( 'Framed', 'wp-ulike-pro'),
                    'boxed'    => esc_html__( 'Boxed', 'wp-ulike-pro'),
                    'flat'     => esc_html__( 'Flat', 'wp-ulike-pro')
                ),
                'dependency' => array( 'enable_social_login', '==', 'true' )
            ),
            array(
                'id'      => 'social_login_shape',
                'type'    => 'button_set',
                'title'   => esc_html__( 'Button Shape', 'wp-ulike-pro'),
                'desc'    => esc_html__('Choose the corner style for social login buttons.', 'wp-ulike-pro'),
                'default' => 'rounded',
                'options' => array(
                    'square'  => esc_html__( 'Square' ),
                    'rounded' => esc_html__( 'Rounded' ),
                    'circle'  => esc_html__( 'Circle' )
                ),
                'dependency' => array( 'enable_social_login', '==', 'true' )
            ),
            array(
                'id'      => 'social_login_color',
                'type'    => 'button_set',
                'default' => 'official',
                'title'   => esc_html__( 'Button Colors', 'wp-ulike-pro'),
                'desc'    => esc_html__('Use official brand colors for each social network, or apply custom colors to all buttons.', 'wp-ulike-pro'),
                'options' => array(
                    'official' => esc_html__( 'Official', 'wp-ulike-pro'),
                    'custom'   => esc_html__( 'Custom' )
                ),
                'dependency' => array( 'enable_social_login', '==', 'true' )
            ),
            array(
                'id'         => 'social_login_layout',
                'type'       => 'fieldset',
                'title'      => esc_html__('Button Layout', 'wp-ulike-pro'),
                'desc'       => esc_html__('Control the width of social login buttons across different screen sizes using a 12-column grid system.', 'wp-ulike-pro'),
                'fields'     => $this->responsive_width_fields( '12', '12', '12' ),
                'dependency' => array( 'enable_social_login', '==', 'true' )
            ),
            array(
                'id'      => 'social_login_auto_display',
                'type'    => 'radio',
                'title'   => esc_html__( 'Auto Display', 'wp-ulike-pro'),
                'desc'    => esc_html__('Automatically show social login buttons on your login form. Choose where they appear or use a custom hook.', 'wp-ulike-pro'),
                'default' => 'after_login_form',
                'options' => array(
                    'none'              => esc_html__( 'None' ),
                    'after_login_form'  => esc_html__( 'After Login Form', 'wp-ulike-pro'),
                    'before_login_form' => esc_html__( 'Before Login Form', 'wp-ulike-pro'),
                    'custom_hook'       => esc_html__( 'Custom Hook', 'wp-ulike-pro')
                ),
                'dependency' => array( 'enable_social_login', '==', 'true' )
            ),
            array(
                'id'         => 'social_login_auto_custom_hook',
                'type'       => 'text',
                'title'      => esc_html__( 'Enter Hook Name','wp-ulike-pro'),
                'desc'       => esc_html__('Enter a WordPress action hook name where you want social buttons to appear automatically.', 'wp-ulike-pro'),
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
                'title'         => esc_html__( 'Before Content','wp-ulike-pro'),
                'desc'          => esc_html__('HTML content displayed before the buttons. Useful for adding separators or text.', 'wp-ulike-pro'),
                'dependency'    => array( 'enable_social_login', '==', 'true' )
            ),
            array(
                'id'            => 'social_login_after',
                'type'          => 'wp_editor',
                'tinymce'       => false,
                'media_buttons' => false,
                'quicktags'     => true,
                'sanitize'      => false,
                'title'         => esc_html__( 'After Content','wp-ulike-pro'),
                'desc'          => esc_html__('HTML content displayed after the buttons.', 'wp-ulike-pro'),
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
                    esc_html__( 'Read More' )
                ),
            ),
            array(
                'id'      => 'enable_user_profiles',
                'type'    => 'switcher',
                'default' => false,
                'title'   => sprintf( esc_html__( 'Enable %s' ), esc_html__('User Profiles', 'wp-ulike-pro') ),
                'desc'    => esc_html__('Create custom user profile pages where users can view and manage their information, activity, and preferences.', 'wp-ulike-pro'),
            ),
            array(
                'id'         => 'user_profiles_core_page',
                'type'       => 'select',
                'chosen'     => true,
                'ajax'       => true,
                'title'      => sprintf( esc_html__('Select %s Page', 'wp-ulike-pro'), esc_html__( 'Profile' ) ),
                'desc'       => esc_html__('Choose the page that contains the profile shortcode. This page will serve as the base URL for all user profiles.', 'wp-ulike-pro'),
                'options'    => 'pages',
                'dependency' => array( 'enable_user_profiles', '==', 'true' )
            ),
            array(
                'id'         => 'user_profiles_permalink_base',
                'type'       => 'select',
                'default'    => 'user_login',
                'desc'       => esc_html__('Here you can control the permalink structure of the user profile URL globally.', 'wp-ulike-pro'),
                'title'      => esc_html__('Profile Permalink Base', 'wp-ulike-pro'),
                'options'    => array(
                    'user_login' => esc_html__( 'Username' ),
                    'name'       => esc_html__( 'First and Last Name with \'.\'', 'wp-ulike-pro' ),
                    'name_dash'  => esc_html__( 'First and Last Name with \'-\'', 'wp-ulike-pro' ),
                    'name_plus'  => esc_html__( 'First and Last Name with \'+\'', 'wp-ulike-pro' ),
                    'user_id'    => esc_html__( 'User ID' )
                ),
                'dependency' => array( 'enable_user_profiles', '==', 'true' )
            ),
            array(
                'id'         => 'enable_author_redirect',
                'type'       => 'switcher',
                'default'    => false,
                'title'      => esc_html__('Redirect Author Pages to Profiles', 'wp-ulike-pro'),
                'desc'       => esc_html__('Automatically redirect WordPress default author archive pages to the corresponding user profile page.', 'wp-ulike-pro'),
                'dependency' => array( 'enable_user_profiles', '==', 'true' )
            ),
            array(
                'id'         => 'user_profiles_access',
                'type'       => 'select',
                'default'    => 'everyone',
                'title'      => esc_html__('User Profile Access', 'wp-ulike-pro'),
                'desc'       => esc_html__('Control who can view user profile pages. Choose whether profiles are public or restricted to logged-in users only.', 'wp-ulike-pro'),
                'options'    => array(
                    'everyone'        => esc_html__('Everyone', 'wp-ulike-pro'),
                    'logged_in_users' => esc_html__('Logged In Users', 'wp-ulike-pro')
                ),
                'dependency' => array( 'enable_user_profiles', '==', 'true' )
            ),
            array(
                'id'         => 'user_custom_redirect',
                'type'       => 'text',
                'default'    => home_url(),
                'title'      => esc_html__( 'Custom Redirect URL', 'wp-ulike-pro'),
                'desc'       => esc_html__('URL where unauthorized users are redirected when trying to access restricted profiles.', 'wp-ulike-pro'),
                'dependency' => array( 'enable_user_profiles|user_profiles_access', '==|==', 'true|logged_in_users' )
            ),
            array(
                'id'         => 'user_restrict_profile_owner',
                'type'       => 'switcher',
                'default'    => false,
                'title'      => esc_html__('Show only for profile owner', 'wp-ulike-pro'),
                'desc'       => esc_html__('Restrict access to profiles only for the account holder. (In case of discrepancies, users will be redirected to their own profile.)', 'wp-ulike-pro'),
                'dependency' => array( 'enable_user_profiles|user_profiles_access', '==|==', 'true|logged_in_users' )
            ),
            array(
                'id'         => 'user_restrict_exclusive_roles',
                'type'       => 'checkbox',
                'title'      => esc_html__('Exclusive Roles', 'wp-ulike-pro'),
                'desc'       => esc_html__('Specify all user roles that can access the profile except the account holder.', 'wp-ulike-pro'),
                'options'    => wp_ulike_pro_get_user_roles_list( array( 'Subscriber' ) ),
                'default'    => array( 'administrator' ),
                'dependency' => array( 'enable_user_profiles|user_profiles_access|user_restrict_profile_owner', '==|==|==', 'true|logged_in_users|true' )
            ),
            array(
                'id'            => 'user_profiles_appearance',
                'type'          => 'tabbed',
                'title'         => esc_html__('Profile Appearance', 'wp-ulike-pro'),
                'dependency'    => array( 'enable_user_profiles', '==', 'true' ),
                'tabs'          => array(
                    array(
                    'title'     => esc_html__('User Info', 'wp-ulike-pro'),
                    'fields'    => array(
                            array(
                                'id'      => 'display_avatar',
                                'type'    => 'switcher',
                                'default' => true,
                                'title'   => esc_html__('Show User Avatar', 'wp-ulike-pro'),
                                'desc'    => esc_html__('Display the user\'s profile picture on their profile page.', 'wp-ulike-pro'),
                            ),
                            array(
                                'id'         => 'avatar_size',
                                'type'       => 'spinner',
                                'title'      => esc_html__('Avatar Dimension', 'wp-ulike-pro'),
                                'desc'       => esc_html__('Set the width and height of the avatar image in pixels.', 'wp-ulike-pro'),
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
                                'title'   => esc_html__('Show User Info Section', 'wp-ulike-pro'),
                                'desc'    => esc_html__('Display the user information section containing name, bio, and other details.', 'wp-ulike-pro'),
                            ),
                            array(
                                'id'         => 'display_name',
                                'type'       => 'switcher',
                                'default'    => true,
                                'title'      => esc_html__('Show Display Name', 'wp-ulike-pro'),
                                'desc'       => esc_html__('Display the user\'s display name in the profile header.', 'wp-ulike-pro'),
                                'dependency' => array( 'display_info', '==', 'true' )
                            ),
                            array(
                                'id'         => 'display_bio',
                                'type'       => 'switcher',
                                'default'    => true,
                                'title'      => esc_html__('Show User Description', 'wp-ulike-pro'),
                                'desc'       => esc_html__('Display the user\'s biographical information (bio) on their profile.', 'wp-ulike-pro'),
                                'dependency' => array( 'display_info', '==', 'true' )
                            ),
                            array(
                                'id'         => 'display_custom_message',
                                'type'       => 'switcher',
                                'default'    => false,
                                'title'      => esc_html__('Show Custom Message When Bio is Empty', 'wp-ulike-pro'),
                                'desc'       => esc_html__('Display a custom message instead of leaving the bio section blank when the user hasn\'t added a description.', 'wp-ulike-pro'),
                                'dependency' => array( 'display_bio|display_info', '==|==', 'true|true' )
                            ),
                            array(
                                'id'            => 'custom_message',
                                'type'          => 'wp_editor',
                                'title'         => esc_html__('Custom Message', 'wp-ulike-pro'),
                                'desc'          => esc_html__('Message displayed when the user bio is empty. You can use HTML and shortcodes here.', 'wp-ulike-pro'),
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
                                'title'    => esc_html__( 'Custom HTML' ),
                                'desc'     => esc_html__( 'A Custom HTML structure where you can display it at the bottom of the Info section. (This option also supports shortcode)', 'wp-ulike-pro'),
                                'dependency'=> array( 'display_info', '==', 'true' ),
                            ),
                        )
                    ),
                    array(
                    'title'     => esc_html__('Badges', 'wp-ulike-pro'),
                    'fields'    => array(
                        array(
                            'id'      => 'display_badges',
                            'type'    => 'switcher',
                            'default' => true,
                            'title'   => esc_html__('Show Badges Section', 'wp-ulike-pro'),
                            'desc'    => esc_html__('Display a section showing user statistics and achievements as badge items.', 'wp-ulike-pro'),
                        ),
                        array(
                            'id'        => 'badges',
                            'type'      => 'group',
                            'title'     => esc_html__( 'Add Profile Badges', 'wp-ulike-pro' ),
                            'fields'    => array(
                                array(
                                    'id'         => 'badge_type',
                                    'type'       => 'button_set',
                                    'title'      => esc_html__( 'Badge Type', 'wp-ulike-pro'),
                                    'desc'       => esc_html__('Choose between a pre-built badge with image and text, or create a custom HTML badge.', 'wp-ulike-pro'),
                                    'options'    => array(
                                        'default' => __( 'Default' ),
                                        'custom'  => esc_html__( 'Custom' )
                                    ),
                                    'default'    => 'default'
                                ),
                                array(
                                    'id'            => 'title',
                                    'type'          => 'wp_editor',
                                    'title'         => esc_html__('Badge Title', 'wp-ulike-pro'),
                                    'desc'          => esc_html__('Main text displayed on the badge. You can use shortcodes here to show dynamic content like like counts.', 'wp-ulike-pro'),
                                    'height'        => '85px',
                                    'dependency'    => array( 'badge_type', '==', 'default' ),
                                ),
                                array(
                                    'id'         => 'subtitle',
                                    'type'       => 'text',
                                    'title'      => esc_html__('Badge Subtitle', 'wp-ulike-pro'),
                                    'desc'       => esc_html__('Secondary text displayed below the title, typically used for descriptions.', 'wp-ulike-pro'),
                                    'dependency' => array( 'badge_type', '==', 'default' ),
                                ),
                                array(
                                    'id'         => 'image',
                                    'type'       => 'media',
                                    'title'      => esc_html__('Badge Image', 'wp-ulike-pro'),
                                    'desc'       => esc_html__('Icon or image displayed on the badge. Recommended size: 64x64 pixels.', 'wp-ulike-pro'),
                                    'dependency' => array( 'badge_type', '==', 'default' ),
                                ),
                                array(
                                    'id'       => 'custom',
                                    'type'     => 'code_editor',
                                    'settings' => array(
                                        'theme' => 'shadowfox',
                                        'mode'  => 'htmlmixed',
                                    ),
                                    'title'      => esc_html__( 'Custom HTML' ),
                                    'desc'       => esc_html__( 'A Custom HTML structure where you can display it as a badge item. (This option also supports shortcode)', 'wp-ulike-pro'),
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
                    'title'     => esc_html__('Tabs', 'wp-ulike-pro'),
                    'fields'    => array(
                        array(
                            'id'      => 'display_tabs',
                            'type'    => 'switcher',
                            'default' => true,
                            'title'   => esc_html__('Show tabs section', 'wp-ulike-pro'),
                        ),
                        array(
                            'id'         => 'tabs_side',
                            'type'       => 'button_set',
                            'title'      => sprintf( esc_html__( 'Select %s' ), esc_html__('Tabs Side', 'wp-ulike-pro') ),
                            'default'    => 'top',
                            'options'    => array(
                                'top'   => esc_html__( 'Top' ),
                                'left'  => esc_html__( 'Left' ),
                                'right' => esc_html__( 'Right' )
                            )
                        ),
                        array(
                            'id'        => 'tabs',
                            'type'      => 'group',
                            'title'     => esc_html__( 'Add Profile Tabs', 'wp-ulike-pro' ),
                            'fields'    => array(
                                array(
                                    'id'      => 'title',
                                    'type'    => 'text',
                                    'title'   => esc_html__('Tab Title', 'wp-ulike-pro'),
                                    'desc'    => esc_html__('Text displayed on the tab button in the profile navigation.', 'wp-ulike-pro')
                                ),
                                array(
                                    'id'     => 'content',
                                    'type'   => 'wp_editor',
                                    'title'  => esc_html__('Tab Content', 'wp-ulike-pro'),
                                    'height' => '100px',
                                    'desc'   => esc_html__( 'Content displayed when this tab is active. You can use HTML, text, and shortcodes here.', 'wp-ulike-pro'),
                                ),
                                array(
                                    'id'      => 'has_link',
                                    'type'    => 'link',
                                    'title'   => esc_html__( 'Create Link Tab', 'wp-ulike-pro' ),
                                    'desc'    => esc_html__('Make this tab link to an external URL instead of showing content. Useful for linking to external pages or resources.', 'wp-ulike-pro'),
                                ),
                                array(
                                    'id'      => 'restrict',
                                    'type'    => 'switcher',
                                    'default' => false,
                                    'title'   => esc_html__('Show only for profile owner', 'wp-ulike-pro'),
                                    'desc'    => esc_html__('Hide this tab from visitors and only show it to the profile owner when viewing their own profile.', 'wp-ulike-pro'),
                                )
                            ),
                            'dependency' => array( 'display_tabs', '==', 'true' ),
                            'default'    => array(
                                array(
                                    'title'   => 'Recent Posts',
                                    'content' => '[wp_ulike_pro_items type="post" status="like" limit="5" empty_text="No results found."]',
                                ),
                                array(
                                    'title'   => 'Recent Comments',
                                    'content' => '[wp_ulike_pro_items type="comment" status="like" limit="5" empty_text="No results found."]',
                                )
                            ),
                        ),
                        )
                    ),
                    array(
                    'title'     => esc_html__( 'Appearance' ),
                    'fields'    => array(
                            array(
                                'type'    => 'subheading',
                                'content' => esc_html__('Wrapper', 'wp-ulike-pro'),
                            ),
                            array(
                                'id'         => 'header_wrapper_width',
                                'type'       => 'fieldset',
                                'title'      => esc_html__('Header column width', 'wp-ulike-pro'),
                                'fields'     => $this->responsive_width_fields( '12', '12', '12' )
                            ),
                            array(
                                'id'         => 'tabs_wrapper_width',
                                'type'       => 'fieldset',
                                'title'      => esc_html__('Tabs column width', 'wp-ulike-pro'),
                                'fields'     => $this->responsive_width_fields( '12', '12', '12' )
                            ),
                            array(
                                'type'    => 'subheading',
                                'content' => esc_html__( 'Heading' ),
                            ),
                            array(
                                'id'         => 'header_info_width',
                                'type'       => 'fieldset',
                                'title'      => esc_html__('Header info width', 'wp-ulike-pro'),
                                'fields'     => $this->responsive_width_fields( '12', '12', '12' )
                            ),
                            array(
                                'id'         => 'header_bagdes_width',
                                'type'       => 'fieldset',
                                'title'      => esc_html__('Header badges width', 'wp-ulike-pro'),
                                'fields'     => $this->responsive_width_fields( '12', '12', '12' )
                            ),
                            array(
                                'type'    => 'subheading',
                                'content' => esc_html__('User Info', 'wp-ulike-pro'),
                            ),
                            array(
                                'id'         => 'avatar_width',
                                'type'       => 'fieldset',
                                'title'      => esc_html__('Avatar column width', 'wp-ulike-pro'),
                                'fields'     => $this->responsive_width_fields( '3', '3', '12' )
                            ),
                            array(
                                'id'         => 'info_width',
                                'type'       => 'fieldset',
                                'title'      => esc_html__('User info column width', 'wp-ulike-pro'),
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
                'title'   => esc_html__('Enable Upload Local Avatar', 'wp-ulike-pro'),
                'desc'    => esc_html__('Allow users to upload and manage their own profile pictures instead of using only Gravatar.', 'wp-ulike-pro'),
            ),
            array(
                'id'         => 'use_gravatars',
                'type'       => 'switcher',
                'default'    => true,
                'desc'       => esc_html__('Do you want to use gravatars instead of the default plugin profile photo (If the user did not upload a custom profile photo / avatar)', 'wp-ulike-pro'),
                'title'      => esc_html__('Use Gravatars?', 'wp-ulike-pro'),
                'dependency' => array( 'enable_local_avatars', '==', 'true' )
            ),
            array(
                'id'         => 'default_avatar',
                'type'       => 'media',
                'title'      => esc_html__( 'Default Avatar' ),
                'desc'       => esc_html__('Default profile picture shown when a user hasn\'t uploaded a custom avatar and Gravatars are disabled.', 'wp-ulike-pro'),
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
                'title'      => esc_html__( 'Avatar Size', 'wp-ulike-pro'),
                'desc'       => esc_html__('Maximum file size allowed when users upload avatar images. Larger files will be rejected.', 'wp-ulike-pro'),
                'default'    => 2,
                'max'        => 50,
                'step'       => 1,
                'unit'       => 'MB',
                'dependency' => array( 'enable_local_avatars', '==', 'true' ),
            ),
            array(
                'id'         => 'max_avatar_width',
                'type'       => 'slider',
                'title'      => esc_html__( 'Avatar Maximum Width', 'wp-ulike-pro'),
                'desc'       => esc_html__('Maximum width in pixels for uploaded avatars. Images wider than this will be automatically resized.', 'wp-ulike-pro'),
                'default'    => 512,
                'unit'       => 'px',
                'dependency' => array( 'enable_local_avatars', '==', 'true' ),
            ),
            array(
                'id'         => 'image_quality',
                'type'       => 'slider',
                'title'      => esc_html__( 'Image Quality', 'wp-ulike-pro'),
                'desc'       => esc_html__( 'Quality is used to determine quality of image uploads, and ranges from 0 (worst quality, smaller file) to 100 (best quality, biggest file). The default range is 60.', 'wp-ulike-pro'),
                'min'        => 0,
                'max'        => 100,
                'default'    => 60,
                'dependency' => array( 'enable_local_avatars', '==', 'true' ),
            ),
            array(
                'id'      => 'enable_admin_limit_access',
                'type'    => 'switcher',
                'default' => false,
                'title'   => esc_html__('Restrict WordPress Dashboard Access', 'wp-ulike-pro'),
                'desc'    => esc_html__('Limit access to the WordPress admin dashboard based on user roles. Users without access will be redirected.', 'wp-ulike-pro'),
            ),
            array(
                'id'         => 'hide_admin_bar',
                'type'       => 'switcher',
                'default'    => true,
                'title'      => esc_html__('Hide Admin Bar', 'wp-ulike-pro'),
                'desc'       => esc_html__('Hide the WordPress admin bar from the front-end for users who don\'t have dashboard access.', 'wp-ulike-pro'),
                'dependency' => array( 'enable_admin_limit_access', '==', 'true' )
            ),
            array(
                'id'         => 'dashboard_access_roles',
                'type'       => 'checkbox',
                'title'      => esc_html__('Dashboard Access Roles', 'wp-ulike-pro'),
                'desc'       => esc_html__('Select which user roles can access the WordPress admin dashboard. Users with other roles will be redirected.', 'wp-ulike-pro'),
                'options'    => wp_ulike_pro_get_user_roles_list( array( 'Administrator', 'Subscriber' ) ),
                'dependency' => array( 'enable_admin_limit_access', '==', 'true' )
            ),
            array(
                'id'         => 'dashboard_custom_redirect',
                'type'       => 'text',
                'default'    => home_url(),
                'title'      => esc_html__( 'Dashboard Redirect URL', 'wp-ulike-pro'),
                'desc'       => esc_html__('URL where users without dashboard access are redirected when they try to access the admin area.', 'wp-ulike-pro'),
                'dependency' => array( 'enable_admin_limit_access', '==', 'true' )
            ),
        );
    }

    public function responsive_width_fields( $desktop = '3', $tablet = '4', $mobile = '12' ){
        return array(
            array(
                'id'          => 'desktop',
                'type'        => 'select',
                'title'       => esc_html__( 'Desktop' ),
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
                'title'       => esc_html__( 'Tablet' ),
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
                'title'       => esc_html__( 'Mobile' ),
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
                'content' => esc_html__('You can create multiple share buttons as you wish and use them as shortocode or try auto display options. Just be sure to set the "Slug" option for each item. For example, if you want to use shortcode and set slug as "single_share", you can use the following shortcode to display social buttons:', 'wp-ulike-pro') .
                '<br><br><code>[wp_ulike_pro_social_share slug=single_share]</code>'
            ),
            array(
                'id'     => 'social_share',
                'type'   => 'group',
                'title'  => esc_html__( 'Add Share Items', 'wp-ulike-pro' ),
                'fields' => array(
                    array(
                        'id'       => 'slug',
                        'type'     => 'text',
                        'title'    => esc_html__( 'Slug' ) . ' *',
                        'desc'     => esc_html__('A unique identifier for this share button set. Used in the shortcode to display these buttons (e.g., slug="my_share").', 'wp-ulike-pro'),
                    ),
                    array(
                        'id'     => 'buttons',
                        'type'   => 'group',
                        'title'  => esc_html__('Share Buttons', 'wp-ulike-pro')  . ' *',
                        'desc'   => esc_html__('Add one or more social networks to include in this share button set.', 'wp-ulike-pro'),
                        'fields' => array(
                            array(
                                'id'       => 'network',
                                'type'     => 'select',
                                'title'    => esc_html__( 'Social networks', 'wp-ulike-pro'),
                                'desc'     => esc_html__('Choose which social media platform this button will share to.', 'wp-ulike-pro'),
                                'chosen'   => true,
                                'settings'   => array(
                                    'width' => '50%'
                                ),
                                'multiple' => false,
                                'options'  => array(
                                    'facebook'      => esc_html__( 'Facebook' ),
                                    'linkedin'      => esc_html__( 'LinkedIn' ),
                                    'twitter'       => esc_html__( 'X' ),
                                    'vkontakte'     => esc_html__( 'vkontakte', 'wp-ulike-pro'),
                                    'odnoklassniki' => esc_html__( 'odnoklassniki', 'wp-ulike-pro'),
                                    'tumblr'        => esc_html__( 'Tumblr' ),
                                    'blogger'       => esc_html__( 'Blogger' ),
                                    'pinterest'     => esc_html__( 'Pinterest' ),
                                    'digg'          => esc_html__( 'digg', 'wp-ulike-pro'),
                                    'evernote'      => esc_html__( 'evernote', 'wp-ulike-pro'),
                                    'reddit'        => esc_html__( 'Reddit' ),
                                    'delicious'     => esc_html__( 'delicious', 'wp-ulike-pro'),
                                    'mix'           => esc_html__( 'mix', 'wp-ulike-pro'),
                                    'xing'          => esc_html__( 'xing', 'wp-ulike-pro'),
                                    'wordpress'     => esc_html__( 'WordPress' ),
                                    'baidu'         => esc_html__( 'baidu', 'wp-ulike-pro'),
                                    'renren'        => esc_html__( 'renren', 'wp-ulike-pro'),
                                    'weibo'         => esc_html__( 'weibo', 'wp-ulike-pro'),
                                    'skype'         => esc_html__( 'Skype' ),
                                    'telegram'      => esc_html__( 'Telegram' ),
                                    'whatsapp'      => esc_html__( 'WhatsApp' ),
                                    'wechat'        => esc_html__( 'wechat', 'wp-ulike-pro')
                                )
                            ),
                            array(
                                'id'      => 'label',
                                'type'    => 'text',
                                'title'   => esc_html__( 'Button label','wp-ulike-pro'),
                                'desc'    => sprintf( esc_html__('Custom text displayed on the %s. Leave empty to use the default network name.', 'wp-ulike-pro'), esc_html__('share button', 'wp-ulike-pro') ),
                            ),
                        )
                    ),
                    array(
                        'id'      => 'view',
                        'type'    => 'button_set',
                        'default' => 'icon_text',
                        'title'   => esc_html__( 'Button Display Style', 'wp-ulike-pro'),
                        'desc'    => esc_html__('Choose how buttons are displayed: with icon and text, icon only, or text only.', 'wp-ulike-pro'),
                        'options' => array(
                            'icon_text' => esc_html__( 'Icon & Text', 'wp-ulike-pro'),
                            'icon'      => esc_html__( 'Icon' ),
                            'text'      => esc_html__( 'Text' )
                        )
                    ),
                    array(
                        'id'      => 'skin',
                        'type'    => 'button_set',
                        'default' => 'gradient',
                        'title'   => esc_html__( 'Button Style', 'wp-ulike-pro'),
                        'desc'    => esc_html__('Choose the visual style for buttons.', 'wp-ulike-pro'),
                        'options' => array(
                            'gradient' => esc_html__( 'Gradient', 'wp-ulike-pro'),
                            'minimal'  => esc_html__( 'Minimal', 'wp-ulike-pro'),
                            'framed'   => esc_html__( 'Framed', 'wp-ulike-pro'),
                            'boxed'    => esc_html__( 'Boxed', 'wp-ulike-pro'),
                            'flat'     => esc_html__( 'Flat', 'wp-ulike-pro')
                        )
                    ),
                    array(
                        'id'      => 'shape',
                        'type'    => 'button_set',
                        'title'   => esc_html__( 'Button Shape', 'wp-ulike-pro'),
                        'desc'    => esc_html__('Choose the corner style for share buttons.', 'wp-ulike-pro'),
                        'default' => 'rounded',
                        'options' => array(
                            'square'  => esc_html__( 'Square' ),
                            'rounded' => esc_html__( 'Rounded' ),
                            'circle'  => esc_html__( 'Circle' )
                        )
                    ),
                    array(
                        'id'      => 'color',
                        'type'    => 'button_set',
                        'default' => 'official',
                        'title'   => esc_html__( 'Button Colors', 'wp-ulike-pro'),
                        'desc'    => esc_html__('Use official brand colors for each social network, or apply custom colors to all buttons.', 'wp-ulike-pro'),
                        'options' => array(
                            'official' => esc_html__( 'Official', 'wp-ulike-pro'),
                            'custom'   => esc_html__( 'Custom' )
                        )
                    ),
                    array(
                        'id'            => 'before',
                        'type'          => 'wp_editor',
                        'tinymce'       => false,
                        'media_buttons' => false,
                        'quicktags'     => true,
                        'sanitize'      => false,
                        'title'         => esc_html__( 'Before Content','wp-ulike-pro'),
                        'desc'          => esc_html__('HTML content displayed before the buttons. Useful for adding separators or text.', 'wp-ulike-pro')
                    ),
                    array(
                        'id'            => 'after',
                        'type'          => 'wp_editor',
                        'tinymce'       => false,
                        'media_buttons' => false,
                        'quicktags'     => true,
                        'sanitize'      => false,
                        'title'         => esc_html__( 'After Content','wp-ulike-pro'),
                        'desc'          => esc_html__('HTML content displayed after the buttons.', 'wp-ulike-pro')
                    ),
                    array(
                        'id'      => 'auto_display',
                        'type'    => 'radio',
                        'title'   => esc_html__( 'Auto Display', 'wp-ulike-pro'),
                        'desc'    => esc_html__('Automatically show share buttons in relation to the like button, or use a custom hook for placement.', 'wp-ulike-pro'),
                        'default' => 'none',
                        'options' => array(
                            'none'          => esc_html__( 'None' ),
                            'after_button'  => esc_html__( 'After Button', 'wp-ulike-pro'),
                            'before_button' => esc_html__( 'Before Button', 'wp-ulike-pro'),
                            'modal_display' => esc_html__( 'Modal After Vote', 'wp-ulike-pro'),
                            'custom_hook'   => esc_html__( 'Custom Hook', 'wp-ulike-pro')
                        )
                    ),
                    array(
                        'id'         => 'auto_custom_hook',
                        'type'       => 'text',
                        'title'      => esc_html__( 'Enter Hook Name','wp-ulike-pro'),
                        'desc'       => esc_html__('Please enter your desired action name in this field so that the social buttons are automatically displayed there.', 'wp-ulike-pro'),
                        'dependency' => array( 'auto_display', '==', 'custom_hook' ),
                    ),
                    array(
                        'id'          => 'auto_display_filter_status',
                        'type'        => 'select',
                        'title'       => esc_html__( 'Filter Status','wp-ulike-pro' ),
                        'desc'        => esc_html__('By selecting any type, you can disable adding share buttons in that area.', 'wp-ulike-pro'),
                        'chosen'      => true,
                        'multiple'    => true,
                        'options'     => array(
							'like'      => esc_html__( 'Like', 'wp-ulike-pro' ),
							'dislike'   => esc_html__( 'Dislike', 'wp-ulike-pro' ),
							'unlike'    => esc_html__( 'Unlike', 'wp-ulike-pro' ),
							'undislike' => esc_html__( 'Undislike', 'wp-ulike-pro' )
                        ),
                        'dependency' => array( 'auto_display', '==', 'modal_display' ),
                    ),
                    array(
                        'id'          => 'auto_display_filter_types',
                        'type'        => 'select',
                        'title'       => esc_html__( 'Filter Types','wp-ulike-pro' ),
                        'desc'        => esc_html__('By selecting any type, you can disable adding share buttons in that area.', 'wp-ulike-pro'),
                        'chosen'      => true,
                        'multiple'    => true,
                        'options'     => array(
                            'post'     => esc_html__( 'Posts' ),
                            'comment'  => esc_html__( 'Comments' ),
                            'activity' => esc_html__('Activities', 'wp-ulike-pro'),
                            'topic'    => esc_html__('Topics', 'wp-ulike-pro')
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
                'content' => esc_html__('You can use the following variables in all templates:', 'wp-ulike-pro') .
                '<br><br><code>{site_url}</code> <code>{site_name}</code> <code>{admin_email}</code> <code>{login_url}</code> <code>{profile_url}</code> <code>{logout_url}</code> <code>{display_name}</code> <code>{first_name}</code> <code>{last_name}</code> <code>{username}</code> <code>{email}</code> <code>{password_reset_link}</code> <code>{account_activation_link}</code>'
            ),
            array(
                'id'     => 'welcome_email',
                'type'   => 'fieldset',
                'title'  => esc_html__( 'Account Welcome Email','wp-ulike-pro'),
                'fields' => array(
                    array(
                        'id'      => 'subject',
                        'type'    => 'text',
                        'title'   => esc_html__( 'Subject Line','wp-ulike-pro'),
                        'default' => __( 'Welcome to {site_name}!','wp-ulike-pro'),
                    ),
                    array(
                        'id'      => 'body',
                        'type'    => 'wp_editor',
                        'default' => WP_Ulike_Pro_Mail::get_template( 'welcome' ),
                        'title'   => esc_html__( 'Message Body','wp-ulike-pro')
                    ),
                ),
            ),
            array(
                'id'     => 'reset_password_email',
                'type'   => 'fieldset',
                'title'  => esc_html__( 'Password Reset Email','wp-ulike-pro'),
                'fields' => array(
                    array(
                        'id'      => 'subject',
                        'type'    => 'text',
                        'title'   => esc_html__( 'Subject Line','wp-ulike-pro'),
                        'default' => __( 'Reset your password','wp-ulike-pro'),
                    ),
                    array(
                        'id'      => 'body',
                        'type'    => 'wp_editor',
                        'default' => WP_Ulike_Pro_Mail::get_template( 'reset-password' ),
                        'title'   => esc_html__( 'Message Body','wp-ulike-pro'),
                    ),
                ),
            ),
            array(
                'id'     => 'change_password_email',
                'type'   => 'fieldset',
                'title'  => esc_html__( 'Password Changed Email','wp-ulike-pro'),
                'fields' => array(
                    array(
                        'id'      => 'subject',
                        'type'    => 'text',
                        'title'   => esc_html__( 'Subject Line','wp-ulike-pro'),
                        'default' => __( 'Your {site_name} password has been changed!','wp-ulike-pro'),
                    ),
                    array(
                        'id'      => 'body',
                        'type'    => 'wp_editor',
                        'default' => WP_Ulike_Pro_Mail::get_template( 'change-password' ),
                        'title'   => esc_html__( 'Message Body','wp-ulike-pro'),
                    ),
                ),
            ),
            array(
                'id'     => 'checkmail_email',
                'type'   => 'fieldset',
                'title'  => esc_html__( 'Account Welcome Email','wp-ulike-pro'),
                'fields' => array(
                    array(
                        'id'      => 'subject',
                        'type'    => 'text',
                        'title'   => esc_html__( 'Subject Line','wp-ulike-pro'),
                        'default' => __( 'Please check your email to activate your account.','wp-ulike-pro'),
                    ),
                    array(
                        'id'      => 'body',
                        'type'    => 'wp_editor',
                        'default' => WP_Ulike_Pro_Mail::get_template( 'checkmail' ),
                        'title'   => esc_html__( 'Message Body','wp-ulike-pro')
                    ),
                ),
            ),
            array(
                'id'     => 'approved_email',
                'type'   => 'fieldset',
                'title'  => esc_html__( 'Account Welcome Email','wp-ulike-pro'),
                'fields' => array(
                    array(
                        'id'      => 'subject',
                        'type'    => 'text',
                        'title'   => esc_html__( 'Subject Line','wp-ulike-pro'),
                        'default' => __( 'Your account at {site_name} is now active','wp-ulike-pro'),
                    ),
                    array(
                        'id'      => 'body',
                        'type'    => 'wp_editor',
                        'default' => WP_Ulike_Pro_Mail::get_template( 'approved' ),
                        'title'   => esc_html__( 'Message Body','wp-ulike-pro')
                    ),
                ),
            ),
            array(
                'id'      => 'admin_email',
                'type'    => 'text',
                'title'   => esc_html__( 'Admin E-mail Address','wp-ulike-pro'),
                'default' => get_bloginfo('admin_email'),
            ),
            array(
                'id'      => 'appears_from',
                'type'    => 'text',
                'title'   => esc_html__( 'Mail appears from','wp-ulike-pro'),
                'default' => get_bloginfo( 'name' ),
            ),
            array(
                'id'      => 'appears_email',
                'type'    => 'text',
                'title'   => esc_html__( 'Mail appears from','wp-ulike-pro'),
                'default' => get_bloginfo('admin_email'),
            ),
            array(
                'id'      => 'enable_html_email',
                'type'    => 'switcher',
                'default' => true,
                'title'   => esc_html__('Use HTML for E-mails?', 'wp-ulike-pro'),
            )
        );
    }


    public function get_permission_notice(){
        return array(
            array(
                'type'    => 'submessage',
                'style'   => 'info',
                'content' => sprintf( '<p>%s</p><a class="button" href="%s">%s</a>', esc_html__( 'Features of the Pro version are only available once you have registered your license. If you don\'t yet have a license key, get WP ULike Pro now.' , 'wp-ulike-pro' ), self_admin_url( 'admin.php?page=wp-ulike-pro-license' ), esc_html__( 'Activate' ) ),
            )
        );
    }
}