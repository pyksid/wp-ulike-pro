<?php
/**
 * Shortcode Generator
 * 
 * @package    wp-ulike-pro
 * @author     TechnoWich 2024
 * @link       https://wpulike.com
*/

// no direct access allowed
if ( ! defined('ABSPATH') ) {
    die();
}

if ( ! class_exists( 'WP_Ulike_Shortcoder' ) ) {
    class WP_Ulike_Pro_Shortcoder{

        protected $prefix = 'wp_ulike_pro_shortcodes';

		/**
		 * __construct
		 */
		function __construct() {
			add_action( 'ulf_loaded', array( $this, 'register_shortcode' ) );
        }

        /**
         * Register setting panel
         *
         * @return void
         */
        public function register_shortcode(){
			// Check display condition
			if( ! wp_ulike_is_true( wp_ulike_get_option( 'enable_shortcoder', true ) ) ){
				return;
			}

            // Create shortcode
			ULF::createShortcoder( $this->prefix, array(
				'button_title' => esc_html__( 'WP ULike Shortcode', WP_ULIKE_PRO_DOMAIN ),
				'gutenberg'    => array(
					'title'          => esc_html__( 'WP ULike Shortcode', WP_ULIKE_PRO_DOMAIN ),
					'description'    => esc_html__( 'WP ULike Shortcode Block', WP_ULIKE_PRO_DOMAIN )
				)
			) );

			ULF::createSection( $this->prefix, array(
				'title'     => esc_html__( 'Display Button', WP_ULIKE_PRO_DOMAIN ),
				'view'      => 'normal',
				'shortcode' => 'wp_ulike',
				'fields'    => array(
					array(
						'id'          => 'for',
						'type'        => 'select',
						'title'       => esc_html__( 'Select content type', WP_ULIKE_PRO_DOMAIN ),
						'placeholder' => 'Select an option',
						'options'     => array(
							'post'     => esc_html__( 'Post', WP_ULIKE_PRO_DOMAIN ),
							'comment'  => esc_html__( 'Comment', WP_ULIKE_PRO_DOMAIN ),
							'activity' => esc_html__( 'Activity', WP_ULIKE_PRO_DOMAIN ),
							'topic'    => esc_html__( 'Topic', WP_ULIKE_PRO_DOMAIN ),
						),
						'default'     => 'post'
					),
					array(
						'id'         => 'id',
						'title'      => esc_html__('Enter Custom ID', WP_ULIKE_PRO_DOMAIN),
						'type'       => 'number',
					),
					array(
						'id'      => 'style',
						'type'    => 'image_select',
						'title'   => esc_html__( 'Select a Template',WP_ULIKE_PRO_DOMAIN),
						'desc'    => sprintf( '%s <a target="_blank" href="%s" title="Click">%s</a>', esc_html__( 'Display online preview',WP_ULIKE_PRO_DOMAIN),  WP_ULIKE_PLUGIN_URI . 'templates/?utm_source=settings-page&utm_campaign=plugin-uri&utm_medium=wp-dash',esc_html__( 'Here',WP_ULIKE_PRO_DOMAIN) ),
						'options' => $this->get_templates_option_array(),
						'default' => 'wpulike-default',
						'class'   => 'wp-ulike-visual-select',
					),
					array(
						'id'      => 'wrapper_class',
						'type'    => 'text',
						'title'   => esc_html__( 'Wrapper Class',WP_ULIKE_PRO_DOMAIN)
					)
				)
			) );

			ULF::createSection( $this->prefix, array(
				'title'     => esc_html__( 'Get Counter Value', WP_ULIKE_PRO_DOMAIN ),
				'view'      => 'normal',
				'shortcode' => 'wp_ulike_counter',
				'fields'    => array(
					array(
						'id'         => 'id',
						'title'      => esc_html__('Enter Custom ID', WP_ULIKE_PRO_DOMAIN),
						'type'       => 'number',
					),
					array(
						'id'          => 'type',
						'type'        => 'select',
						'title'       => esc_html__( 'Select content type', WP_ULIKE_PRO_DOMAIN ),
						'placeholder' => 'Select an option',
						'options'     => array(
							'post'     => esc_html__( 'Post', WP_ULIKE_PRO_DOMAIN ),
							'comment'  => esc_html__( 'Comment', WP_ULIKE_PRO_DOMAIN ),
							'activity' => esc_html__( 'Activity', WP_ULIKE_PRO_DOMAIN ),
							'topic'    => esc_html__( 'Topic', WP_ULIKE_PRO_DOMAIN ),
						),
						'default'     => 'post'
					),
					array(
						'id'          => 'status',
						'type'        => 'select',
						'title'       => esc_html__( 'Select Status', WP_ULIKE_PRO_DOMAIN ),
						'placeholder' => 'Select a type',
						'options'     => array(
							'like'      => esc_html__( 'Like', WP_ULIKE_PRO_DOMAIN ),
							'dislike'   => esc_html__( 'Dislike', WP_ULIKE_PRO_DOMAIN )
						)
					)
				)
			) );

			ULF::createSection( $this->prefix, array(
				'title'     => esc_html__( 'Display User Info', WP_ULIKE_PRO_DOMAIN ),
				'view'      => 'normal',
				'shortcode' => 'wp_ulike_pro_user_info',
				'fields'    => array(
					array(
						'id'          => 'user_id',
						'type'        => 'select',
						'chosen'     => true,
						'ajax'       => true,
						'title'       => esc_html__('Enter Custom User ID', WP_ULIKE_PRO_DOMAIN),
						'placeholder' => esc_html__('Select a user', WP_ULIKE_PRO_DOMAIN),
						'desc'        => esc_html__( 'Don\'t use Custom ID for user profiles section.',WP_ULIKE_PRO_DOMAIN ),
						'options'     => 'users'
					),
					array(
						'id'          => 'type',
						'type'        => 'select',
						'title'       => esc_html__( 'Select output type', WP_ULIKE_PRO_DOMAIN ),
						'placeholder' => 'Select a type',
						'options'     => array(
							'data_counter'  => esc_html__( 'Data Counter', WP_ULIKE_PRO_DOMAIN ),
							'last_activity' => esc_html__( 'Last Activity', WP_ULIKE_PRO_DOMAIN ),
							'last_status'   => esc_html__( 'Last Status', WP_ULIKE_PRO_DOMAIN )
						),
						'default'     => 'data_counter'
					),
					array(
						'id'          => 'table',
						'type'        => 'select',
						'title'       => esc_html__( 'Select Table Type', WP_ULIKE_PRO_DOMAIN ),
						'placeholder' => 'Select a type',
						'options'     => array(
							'post'     => esc_html__( 'Post', WP_ULIKE_PRO_DOMAIN ),
							'comment'  => esc_html__( 'Comment', WP_ULIKE_PRO_DOMAIN ),
							'activity' => esc_html__( 'Activity', WP_ULIKE_PRO_DOMAIN ),
							'topic'    => esc_html__( 'Topic', WP_ULIKE_PRO_DOMAIN ),
						),
						'dependency'  => array( 'type', '==', 'data_counter' )
					),
					array(
						'id'          => 'status',
						'type'        => 'select',
						'title'       => esc_html__( 'Select Status', WP_ULIKE_PRO_DOMAIN ),
						'placeholder' => 'Select a type',
						'options'     => array(
							'like'      => esc_html__( 'Like', WP_ULIKE_PRO_DOMAIN ),
							'dislike'   => esc_html__( 'Dislike', WP_ULIKE_PRO_DOMAIN ),
							'unlike'    => esc_html__( 'Unlike', WP_ULIKE_PRO_DOMAIN ),
							'undislike' => esc_html__( 'Undislike', WP_ULIKE_PRO_DOMAIN )
						),
						'dependency'  => array( 'type', '==', 'data_counter' )
					),
					array(
						'id'         => 'before_text',
						'type'       => 'text',
						'title'      => esc_html__('Before Text', WP_ULIKE_PRO_DOMAIN)
					),
					array(
						'id'         => 'after_text',
						'type'       => 'text',
						'title'      => esc_html__('After Text', WP_ULIKE_PRO_DOMAIN)
					),
					array(
						'id'         => 'empty_text',
						'type'       => 'text',
						'title'      => esc_html__('Empty message Text', WP_ULIKE_PRO_DOMAIN)
					)
				)
			) );

			ULF::createSection( $this->prefix, array(
				'title'     => esc_html__( 'Get Items List', WP_ULIKE_PRO_DOMAIN ),
				'view'      => 'normal',
				'shortcode' => 'wp_ulike_pro_items',
				'fields'    => array(
					array(
						'id'          => 'user_id',
						'type'        => 'select',
						'chosen'     => true,
						'ajax'       => true,
						'title'       => esc_html__('Enter Custom User ID', WP_ULIKE_PRO_DOMAIN),
						'placeholder' => esc_html__('Select a user', WP_ULIKE_PRO_DOMAIN),
						'desc'        => esc_html__( 'Don\'t use Custom ID for user profiles section.',WP_ULIKE_PRO_DOMAIN ),
						'options'     => 'users'
					),
					array(
						'id'    => 'anonymize_user',
						'type'  => 'switcher',
						'title' => esc_html__( 'Enable Anonymize User', WP_ULIKE_PRO_DOMAIN )
					),
					array(
						'id'          => 'type',
						'type'        => 'select',
						'title'       => esc_html__( 'Select Table Type', WP_ULIKE_PRO_DOMAIN ),
						'options'     => array(
							'post'     => esc_html__( 'Post', WP_ULIKE_PRO_DOMAIN ),
							'comment'  => esc_html__( 'Comment', WP_ULIKE_PRO_DOMAIN ),
							'activity' => esc_html__( 'Activity', WP_ULIKE_PRO_DOMAIN ),
							'topic'    => esc_html__( 'Topic', WP_ULIKE_PRO_DOMAIN ),
						)
					),
					array(
						'id'          => 'rel_type',
						'type'        => 'select',
						'title'       => esc_html__( 'Select Post Type', WP_ULIKE_PRO_DOMAIN ),
						'placeholder' => 'Select an item',
						'options'     => 'post_types',
						'dependency'  => array( 'type', '==', 'post' )
					),
					array(
						'id'          => 'status',
						'type'        => 'select',
						'title'       => esc_html__( 'Select status', WP_ULIKE_PRO_DOMAIN ),
						'placeholder' => 'Select a type',
						'options'     => array(
							'like'      => esc_html__( 'Like', WP_ULIKE_PRO_DOMAIN ),
							'dislike'   => esc_html__( 'Dislike', WP_ULIKE_PRO_DOMAIN ),
							'unlike'    => esc_html__( 'Unlike', WP_ULIKE_PRO_DOMAIN ),
							'undislike' => esc_html__( 'Undislike', WP_ULIKE_PRO_DOMAIN )
						)
					),
					array(
						'id'    => 'is_popular',
						'type'  => 'switcher',
						'title' => esc_html__( 'Display Popular Items', WP_ULIKE_PRO_DOMAIN )
					),
					array(
						'id'    => 'limit',
						'type'  => 'number',
						'title' => esc_html__( 'Limit', WP_ULIKE_PRO_DOMAIN ),
					),
					array(
						'id'         => 'empty_text',
						'type'       => 'text',
						'title'      => esc_html__( 'Empty message Text', WP_ULIKE_PRO_DOMAIN )
					),
					array(
						'id'      => 'desktop_column',
						'type'    => 'slider',
						'title'   => esc_html__( 'Desktop Column', WP_ULIKE_PRO_DOMAIN ),
						'min'     => 1,
						'max'     => 12,
						'step'    => 1
					),
					array(
						'id'      => 'tablet_column',
						'type'    => 'slider',
						'title'   => esc_html__( 'Tablet Column', WP_ULIKE_PRO_DOMAIN ),
						'min'     => 1,
						'max'     => 12,
						'step'    => 1
					),
					array(
						'id'      => 'mobile_column',
						'type'    => 'slider',
						'title'   => esc_html__( 'Mobile Column', WP_ULIKE_PRO_DOMAIN ),
						'min'     => 1,
						'max'     => 12,
						'step'    => 1
					),
					array(
						'id'          => 'exclude',
						'type'        => 'select',
						'title'       => esc_html__( 'Exclude', WP_ULIKE_PRO_DOMAIN ),
						'chosen'      => true,
						'multiple'    => true,
						'placeholder' => esc_html__( 'Select options to hide in the list', WP_ULIKE_PRO_DOMAIN ),
						'options'     => array(
							'thumbnail'   => esc_html__( 'Thumbnail', WP_ULIKE_PRO_DOMAIN ),
							'title'       => esc_html__( 'Title', WP_ULIKE_PRO_DOMAIN ),
							'description' => esc_html__( 'Description', WP_ULIKE_PRO_DOMAIN ),
							'date'        => esc_html__( 'Date Info', WP_ULIKE_PRO_DOMAIN ),
							'author'      => esc_html__( 'Author Info', WP_ULIKE_PRO_DOMAIN ),
							'votes'       => esc_html__( 'Likes/Dislikes Info', WP_ULIKE_PRO_DOMAIN ),
						),
						'default'     => 'thumbnail'
					),
				)
			) );

			ULF::createSection( $this->prefix, array(
				'title'     => esc_html__( 'User Profile', WP_ULIKE_PRO_DOMAIN ),
				'view'      => 'normal',
				'shortcode' => 'wp_ulike_pro_completeness_profile',
				'fields'    => array(
					array(
						'id'          => 'user_id',
						'type'        => 'select',
						'chosen'     => true,
						'ajax'       => true,
						'title'       => esc_html__('Enter Custom User ID', WP_ULIKE_PRO_DOMAIN),
						'placeholder' => esc_html__('Select a user', WP_ULIKE_PRO_DOMAIN),
						'desc'        => esc_html__( 'Don\'t use Custom ID for user profiles section.',WP_ULIKE_PRO_DOMAIN ),
						'options'     => 'users'
					)
				)
			) );

			ULF::createSection( $this->prefix, array(
				'title'     => esc_html__( 'Login Form', WP_ULIKE_PRO_DOMAIN ),
				'view'      => 'normal',
				'shortcode' => 'wp_ulike_pro_login_form',
				'fields'    => array(
					array(
						'id'    => 'form_id',
						'title' => esc_html__('Enter Form ID', WP_ULIKE_PRO_DOMAIN),
						'type'  => 'number',
					),
					array(
						'id'    => 'username',
						'type'  => 'text',
						'title' => esc_html__( 'Username label', WP_ULIKE_PRO_DOMAIN )
					),
					array(
						'id'    => 'password',
						'type'  => 'text',
						'title' => esc_html__( 'Password label', WP_ULIKE_PRO_DOMAIN )
					),
					array(
						'id'    => 'remember',
						'type'  => 'text',
						'title' => esc_html__( 'Remember Me label', WP_ULIKE_PRO_DOMAIN )
					),
					array(
						'id'    => 'button',
						'type'  => 'text',
						'title' => esc_html__( 'Button label', WP_ULIKE_PRO_DOMAIN )
					),
					array(
						'id'    => 'reset_password',
						'type'  => 'text',
						'title' => esc_html__( 'Reset password text', WP_ULIKE_PRO_DOMAIN )
					),
					array(
						'id'    => 'reset_url',
						'type'  => 'text',
						'title' => esc_html__( 'Reset password URL', WP_ULIKE_PRO_DOMAIN )
					),
					array(
						'id'    => 'signup_message',
						'type'  => 'text',
						'title' => esc_html__( 'Signup Message', WP_ULIKE_PRO_DOMAIN )
					),
					array(
						'id'    => 'signup_text',
						'type'  => 'text',
						'title' => esc_html__( 'Signup URL text', WP_ULIKE_PRO_DOMAIN )
					),
					array(
						'id'    => 'redirect_to',
						'type'  => 'text',
						'title' => esc_html__( 'Redirect URL', WP_ULIKE_PRO_DOMAIN )
					),
					array(
						'id'    => 'ajax_toggle',
						'type'  => 'switcher',
						'title' => esc_html__( 'Enable AJAX Toggle', WP_ULIKE_PRO_DOMAIN )
					)
				)
			) );

			ULF::createSection( $this->prefix, array(
				'title'     => esc_html__( 'Signup Form', WP_ULIKE_PRO_DOMAIN ),
				'view'      => 'normal',
				'shortcode' => 'wp_ulike_pro_signup_form',
				'fields'    => array(
					array(
						'id'    => 'form_id',
						'title' => esc_html__('Enter Form ID', WP_ULIKE_PRO_DOMAIN),
						'type'  => 'number',
					),
					array(
						'id'    => 'username',
						'type'  => 'text',
						'title' => esc_html__( 'Username label', WP_ULIKE_PRO_DOMAIN )
					),
					array(
						'id'    => 'password',
						'type'  => 'text',
						'title' => esc_html__( 'Password label', WP_ULIKE_PRO_DOMAIN )
					),
					array(
						'id'    => 'firstname',
						'type'  => 'text',
						'title' => esc_html__( 'First name label', WP_ULIKE_PRO_DOMAIN )
					),
					array(
						'id'    => 'lastname',
						'type'  => 'text',
						'title' => esc_html__( 'Last name label', WP_ULIKE_PRO_DOMAIN )
					),
					array(
						'id'    => 'email',
						'type'  => 'text',
						'title' => esc_html__( 'Email label', WP_ULIKE_PRO_DOMAIN )
					),
					array(
						'id'    => 'button',
						'type'  => 'text',
						'title' => esc_html__( 'Button label', WP_ULIKE_PRO_DOMAIN )
					),
					array(
						'id'    => 'login_message',
						'type'  => 'text',
						'title' => esc_html__( 'Login Message', WP_ULIKE_PRO_DOMAIN )
					),
					array(
						'id'    => 'login_text',
						'type'  => 'text',
						'title' => esc_html__( 'Login URL text', WP_ULIKE_PRO_DOMAIN )
					),
					array(
						'id'    => 'redirect_to',
						'type'  => 'text',
						'title' => esc_html__( 'Redirect URL', WP_ULIKE_PRO_DOMAIN )
					),
					array(
						'id'    => 'ajax_toggle',
						'type'  => 'switcher',
						'title' => esc_html__( 'Enable AJAX Toggle', WP_ULIKE_PRO_DOMAIN )
					)
				)
			) );

			ULF::createSection( $this->prefix, array(
				'title'     => esc_html__( 'Reset Password Form', WP_ULIKE_PRO_DOMAIN ),
				'view'      => 'normal',
				'shortcode' => 'wp_ulike_pro_reset_password_form',
				'fields'    => array(
					array(
						'id'    => 'form_id',
						'title' => esc_html__('Enter Form ID', WP_ULIKE_PRO_DOMAIN),
						'type'  => 'number',
					),
					array(
						'id'    => 'reset_message',
						'type'  => 'text',
						'title' => esc_html__( 'Reset your password message', WP_ULIKE_PRO_DOMAIN )
					),
					array(
						'id'    => 'change_message',
						'type'  => 'text',
						'title' => esc_html__( 'Enter new password message', WP_ULIKE_PRO_DOMAIN )
					),
					array(
						'id'    => 'mail_message',
						'type'  => 'text',
						'title' => esc_html__( 'Check your email message', WP_ULIKE_PRO_DOMAIN )
					),
					array(
						'id'    => 'username',
						'type'  => 'text',
						'title' => esc_html__( 'Username or Email label', WP_ULIKE_PRO_DOMAIN )
					),
					array(
						'id'    => 'new_pass',
						'type'  => 'text',
						'title' => esc_html__( 'New password label', WP_ULIKE_PRO_DOMAIN )
					),
					array(
						'id'    => 're_new_pass',
						'type'  => 'text',
						'title' => esc_html__( 'Re-enter new password label', WP_ULIKE_PRO_DOMAIN )
					),
					array(
						'id'    => 'invalidkey',
						'type'  => 'text',
						'title' => esc_html__( 'Invalid key message', WP_ULIKE_PRO_DOMAIN )
					),
					array(
						'id'    => 'expiredkey',
						'type'  => 'text',
						'title' => esc_html__( 'Expired key message', WP_ULIKE_PRO_DOMAIN )
					),
					array(
						'id'    => 'reset_button',
						'type'  => 'text',
						'title' => esc_html__( 'Get new password label', WP_ULIKE_PRO_DOMAIN )
					),
					array(
						'id'    => 'change_button',
						'type'  => 'text',
						'title' => esc_html__( 'Reset password label', WP_ULIKE_PRO_DOMAIN )
					),
					array(
						'id'    => 'login_message',
						'type'  => 'text',
						'title' => esc_html__( 'Go to login URL text', WP_ULIKE_PRO_DOMAIN )
					),
					array(
						'id'    => 'ajax_toggle',
						'type'  => 'switcher',
						'title' => esc_html__( 'Enable AJAX Toggle', WP_ULIKE_PRO_DOMAIN )
					)
				)
			) );

			ULF::createSection( $this->prefix, array(
				'title'     => esc_html__( 'Edit Account Form', WP_ULIKE_PRO_DOMAIN ),
				'view'      => 'normal',
				'shortcode' => 'wp_ulike_pro_account_form',
				'fields'    => array(
					array(
						'id'    => 'form_id',
						'title' => esc_html__('Enter Form ID', WP_ULIKE_PRO_DOMAIN),
						'type'  => 'number',
					),
					array(
						'id'    => 'firstname',
						'type'  => 'text',
						'title' => esc_html__( 'First name label', WP_ULIKE_PRO_DOMAIN )
					),
					array(
						'id'    => 'lastname',
						'type'  => 'text',
						'title' => esc_html__( 'Last name label', WP_ULIKE_PRO_DOMAIN )
					),
					array(
						'id'    => 'website',
						'type'  => 'text',
						'title' => esc_html__( 'Website label', WP_ULIKE_PRO_DOMAIN )
					),
					array(
						'id'    => 'description',
						'type'  => 'text',
						'title' => esc_html__( 'Biographical info label', WP_ULIKE_PRO_DOMAIN )
					),
					array(
						'id'    => 'email',
						'type'  => 'text',
						'title' => esc_html__( 'Email label', WP_ULIKE_PRO_DOMAIN )
					),
					array(
						'id'    => 'avatar',
						'type'  => 'text',
						'title' => esc_html__( 'Upload avatar label', WP_ULIKE_PRO_DOMAIN )
					),
					array(
						'id'    => 'button',
						'type'  => 'text',
						'title' => esc_html__( 'Button label', WP_ULIKE_PRO_DOMAIN )
					),
					array(
						'id'    => 'permission_message',
						'type'  => 'text',
						'title' => esc_html__( 'Permission denied message', WP_ULIKE_PRO_DOMAIN )
					)
				)
			) );


			ULF::createSection( $this->prefix, array(
				'title'     => esc_html__( 'Share buttons', WP_ULIKE_PRO_DOMAIN ),
				'view'      => 'normal',
				'shortcode' => 'wp_ulike_pro_social_share',
				'fields'    => array(
					array(
						'id'    => 'slug',
						'title' => esc_html__('Slug', WP_ULIKE_PRO_DOMAIN) . ' *',
						'type'  => 'text',
					),
					array(
						'id'    => 'data-url',
						'type'  => 'text',
						'title' => esc_html__( 'URL', WP_ULIKE_PRO_DOMAIN )
					),
					array(
						'id'    => 'data-title',
						'type'  => 'text',
						'title' => esc_html__( 'Title', WP_ULIKE_PRO_DOMAIN )
					),
					array(
						'id'    => 'data-description',
						'type'  => 'text',
						'title' => esc_html__( 'Description', WP_ULIKE_PRO_DOMAIN )
					),
					array(
						'id'    => 'data-image',
						'type'  => 'text',
						'title' => esc_html__( 'Image', WP_ULIKE_PRO_DOMAIN )
					)
				)
			) );

			ULF::createSection( $this->prefix, array(
				'title'     => esc_html__( 'Two factor setup', WP_ULIKE_PRO_DOMAIN ),
				'view'      => 'normal',
				'shortcode' => 'wp_ulike_pro_two_factor_setup',
				'fields'    => array(
					array(
						'id'    => 'title',
						'type'  => 'text',
						'title' => esc_html__( 'Title', WP_ULIKE_PRO_DOMAIN )
					),
					array(
						'id'    => 'description',
						'type'  => 'text',
						'title' => esc_html__( 'Description', WP_ULIKE_PRO_DOMAIN )
					),
					array(
						'id'    => 'accounts_title',
						'type'  => 'text',
						'title' => esc_html__( 'Accounts Title', WP_ULIKE_PRO_DOMAIN )
					),
					array(
						'id'    => 'app_name',
						'type'  => 'text',
						'title' => esc_html__( 'App Name', WP_ULIKE_PRO_DOMAIN )
					),
					array(
						'id'    => 'ago_text',
						'type'  => 'text',
						'title' => esc_html__( 'Ago text', WP_ULIKE_PRO_DOMAIN )
					),
					array(
						'id'    => 'limit_accounts',
						'type'  => 'number',
						'title' => esc_html__( 'Limit accounts', WP_ULIKE_PRO_DOMAIN )
					),
					array(
						'id'    => 'limit_message',
						'type'  => 'text',
						'title' => esc_html__( 'Limit message', WP_ULIKE_PRO_DOMAIN )
					),
					array(
						'id'    => 'qrcode_size',
						'type'  => 'number',
						'title' => esc_html__( 'Qrcode image size', WP_ULIKE_PRO_DOMAIN )
					),
					array(
						'id'    => 'button',
						'type'  => 'text',
						'title' => esc_html__( 'Button label', WP_ULIKE_PRO_DOMAIN )
					),
					array(
						'id'    => 'permission_message',
						'type'  => 'text',
						'title' => esc_html__( 'Permission denied message', WP_ULIKE_PRO_DOMAIN )
					)
				)
			) );

			ULF::createSection( $this->prefix, array(
				'title'     => esc_html__( 'Social logins', WP_ULIKE_PRO_DOMAIN ),
				'view'      => 'normal',
				'shortcode' => 'wp_ulike_pro_social_login',
				'fields'    => array(
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
					)
				)
			) );

		}


        /**
         * Get templates option array
         *
         * @return array
         */
        public function get_templates_option_array(){
            $options = wp_ulike_generate_templates_list();
            $output  = array();

            if( !empty( $options ) ){
                foreach ($options as $key => $args) {
                    $output[$key] = $args['symbol'];
                }
            }

            return $output;
		}

	}
}