<?php
/**
 * Pro license, troubleshooting tips, and Site Health fields for Overview.
 *
 * Module state lives in the Features switches — this class does not inject
 * status rows or shortcut cards.
 *
 * @package WP_ULike_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_Ulike_Pro_Overview' ) ) {

	/**
	 * Adds license sidebar meta, gated troubleshooting tips, and Site Health fields.
	 */
	class WP_Ulike_Pro_Overview {

		/**
		 * Constructor.
		 */
		public function __construct() {
			add_filter( 'wp_ulike_admin_pages', array( $this, 'remove_go_pro_menu' ), 20 );
			// Module state and shortcuts now live in the Features switches on
			// Overview, so mirroring them as status rows and cards only made the
			// screen longer. License stays in the sidebar, where it is looked up.
			add_filter( 'wp_ulike_about_sidebar_meta', array( $this, 'add_sidebar_meta' ), 10, 2 );
			add_filter( 'wp_ulike_about_summary', array( $this, 'filter_summary' ), 10, 2 );
			add_filter( 'wp_ulike_overview_troubleshooting_tips', array( $this, 'add_troubleshooting_tips' ), 10, 2 );
			add_filter( 'wp_ulike_site_health_info_fields', array( $this, 'add_site_health_info_fields' ) );
		}

		/**
		 * Pro rows for Tools → Site Health → Info.
		 *
		 * @param array $fields Free plugin debug fields.
		 * @return array
		 */
		public function add_site_health_info_fields( $fields ) {
			if ( ! is_array( $fields ) ) {
				$fields = array();
			}

			// English-only support dump (Site Health → Info). Not in PO catalog.
			$fields['pro_version'] = array(
				'label' => 'Pro version',
				'value' => defined( 'WP_ULIKE_PRO_VERSION' ) ? WP_ULIKE_PRO_VERSION : 'unknown',
			);

			$license_row   = $this->get_license_status_row();
			$license_value = is_array( $license_row ) ? wp_strip_all_tags( (string) ( $license_row['value'] ?? 'Not activated' ) ) : 'Not activated';

			$fields['pro_license'] = array(
				'label' => 'Pro license',
				'value' => $license_value,
			);

			$modules = array_map( 'wp_strip_all_tags', $this->get_active_module_labels() );
			$fields['pro_modules'] = array(
				'label' => 'Pro modules enabled',
				'value' => $modules ? implode( ', ', $modules ) : 'none detected',
			);

			return $fields;
		}

		/**
		 * Hide free Go Pro submenu when Pro is active.
		 *
		 * @param array $submenus Submenus.
		 * @return array
		 */
		public function remove_go_pro_menu( $submenus ) {
			if ( is_array( $submenus ) && isset( $submenus['go_pro'] ) ) {
				unset( $submenus['go_pro'] );
			}

			return $submenus;
		}

		/**
		 * Extra sidebar fields (license expiry, activations, etc.).
		 *
		 * @param array $meta   Meta rows.
		 * @param array $health Health.
		 * @return array
		 */
		public function add_sidebar_meta( $meta, $health ) {
			if ( ! class_exists( 'WP_Ulike_Pro_API' ) ) {
				return $meta;
			}

			$license_data = WP_Ulike_Pro_API::get_license_data( false );
			if ( ! is_array( $license_data ) ) {
				return $meta;
			}

			$status = $license_data['license'] ?? '';
			$value  = esc_html__( 'Active' );

			if ( WP_Ulike_Pro_API::STATUS_EXPIRED === $status ) {
				$value = esc_html__( 'Expired', 'wp-ulike-pro' );
			} elseif ( ! WP_Ulike_Pro_API::is_license_active() ) {
				$value = esc_html__( 'Needs activation', 'wp-ulike-pro' );
			}

			$meta[] = array(
				'label' => esc_html__( 'License', 'wp-ulike-pro' ),
				'value' => $value,
				'url'   => admin_url( 'admin.php?page=wp-ulike-pro-license' ),
			);

			if ( ! empty( $license_data['expires'] ) ) {
				$expires = $license_data['expires'];
				if ( 'lifetime' === $expires ) {
					$expires_label = esc_html__( 'Lifetime', 'wp-ulike-pro' );
				} else {
					$expires_label = date_i18n( get_option( 'date_format' ), strtotime( $expires ) );
				}

				$meta[] = array(
					'label' => esc_html__( 'License expires', 'wp-ulike-pro' ),
					'value' => $expires_label,
					'url'   => admin_url( 'admin.php?page=wp-ulike-pro-license' ),
				);
			}

			// Activation counts and site limits belong on the License page, not in
			// a sidebar people read for version numbers.
			return $meta;
		}

		/**
		 * Append Pro context to the Help summary line.
		 *
		 * @param string $summary Summary HTML/text.
		 * @param array  $health  Health report.
		 * @return string
		 */
		public function filter_summary( $summary, $health ) {
			if ( ! class_exists( 'WP_Ulike_Pro_API' ) || ! function_exists( 'wp_ulike_get_option' ) ) {
				return $summary;
			}

			// Only say something the reader can act on. Counting active modules
			// duplicated the Features switches directly below the summary.
			if ( WP_Ulike_Pro_API::is_license_active() ) {
				return $summary;
			}

			$suffix = esc_html__( 'Activate your Pro license to unlock profiles, forms, and tools.', 'wp-ulike-pro' );

			if ( empty( $summary ) ) {
				return $suffix;
			}

			return trim( $summary ) . ' ' . $suffix;
		}

		/**
		 * Pro troubleshooting tips and backup scope note.
		 *
		 * @param array $tips   Tips.
		 * @param array $health Health report.
		 * @return array
		 */
		public function add_troubleshooting_tips( $tips, $health ) {
			// A tip about a module the site does not run is noise, so each one
			// appears only when its module is switched on.
			if ( $this->feature_enabled( 'display_automation' ) ) {
				$tips[] = array(
					'text' => esc_html__( 'Display Automation (Tools) is separate from Automatic Display under Settings → Posts. Using both can show duplicate buttons—prefer one approach or disable Automatic Display when rules are active.', 'wp-ulike-pro' ),
					'url'  => admin_url( 'admin.php?page=wp-ulike-pro-tools&tab=display-automation' ),
					'link' => esc_html__( 'Display Automation', 'wp-ulike-pro' ),
				);
			}

			if ( $this->feature_enabled( 'login_signup' ) ) {
				$tips[] = array(
					'text' => esc_html__( 'Do not cache pages that use login, registration, password reset, edit account, or user profile shortcodes.', 'wp-ulike-pro' ),
					'url'  => $this->settings_tab_url( 'login-signup' ),
					'link' => esc_html__( 'Login & Signup', 'wp-ulike-pro' ),
				);
			}

			if ( $this->feature_enabled( 'profiles' ) && $this->is_option_enabled( 'enable_user_profiles' ) ) {
				$tips[] = array(
					'text' => esc_html__( 'User profile URLs depend on the page selected in Profiles settings—update both the page and the permalink base if you change slugs.', 'wp-ulike-pro' ),
					'url'  => $this->settings_tab_url( 'profiles' ),
					'link' => esc_html__( 'Profiles', 'wp-ulike-pro' ),
				);
			}

			$tips[] = array(
				'text' => esc_html__( 'Help → Settings backup saves settings, customizer values, display rules, and REST API configuration. Per-post schema/FAQ data and REST API keys are not included—review those in Pro Tools before you move.', 'wp-ulike-pro' ),
				'url'  => admin_url( 'admin.php?page=wp-ulike-pro-tools' ),
				'link' => esc_html__( 'Pro Tools', 'wp-ulike-pro' ),
			);

			return $tips;
		}

		/**
		 * Whether a Pro module is switched on.
		 *
		 * @param string $key Feature slug.
		 * @return bool
		 */
		private function feature_enabled( $key ) {
			return function_exists( 'wp_ulike_pro_feature_enabled' )
				? wp_ulike_pro_feature_enabled( $key )
				: true;
		}

		/**
		 * Settings screen deep link for a section tab.
		 *
		 * @param string $tab Section id.
		 * @return string
		 */
		private function settings_tab_url( $tab ) {
			return admin_url(
				'admin.php?page=wp-ulike-settings&settings-page=' . rawurlencode( $tab )
			);
		}

		/**
		 * Whether a stored option is enabled.
		 *
		 * @param string $option Option id.
		 * @return bool
		 */
		private function is_option_enabled( $option ) {
			return $this->is_nested_option_enabled( $option, false );
		}

		/**
		 * Whether a nested or flat option is enabled.
		 *
		 * @param string $option  Option id (supports pipe notation).
		 * @param mixed  $default Default when unset.
		 * @return bool
		 */
		private function is_nested_option_enabled( $option, $default = false ) {
			if ( ! function_exists( 'wp_ulike_get_option' ) || ! function_exists( 'wp_ulike_is_true' ) ) {
				return false;
			}

			return wp_ulike_is_true( wp_ulike_get_option( $option, $default ) );
		}

		/**
		 * Human-readable labels for enabled Pro modules (summary line).
		 *
		 * @return array<int, string>
		 */
		private function get_active_module_labels() {
			$labels = array();

			// A module that is configured but switched off is not running, and a
			// support ticket reading "Profiles enabled" for a site with Profiles
			// switched off sends whoever reads it the wrong way.
			if ( $this->feature_enabled( 'display_automation' ) && class_exists( 'WP_Ulike_Pro_Display_Automation' ) ) {
				$active = WP_Ulike_Pro_Display_Automation::get_active_rules();
				$count  = is_array( $active ) ? count( $active ) : 0;
				if ( $count > 0 ) {
					$labels[] = esc_html__( 'Display Automation', 'wp-ulike-pro' );
				}
			}

			if ( $this->feature_enabled( 'profiles' ) && $this->is_option_enabled( 'enable_user_profiles' ) ) {
				$labels[] = esc_html__( 'Profiles', 'wp-ulike-pro' );
			}

			if ( $this->feature_enabled( 'login_signup' ) && function_exists( 'wp_ulike_get_option' ) ) {
				$login_id  = (int) wp_ulike_get_option( 'login_core_page', 0 );
				$signup_id = (int) wp_ulike_get_option( 'signup_core_page', 0 );
				if ( $login_id > 0 || $signup_id > 0 ) {
					$labels[] = esc_html__( 'login forms', 'wp-ulike-pro' );
				}
			}

			if ( $this->feature_enabled( 'social_logins' ) && $this->is_option_enabled( 'enable_social_login' ) ) {
				$labels[] = esc_html__( 'Social login', 'wp-ulike-pro' );
			}

			if ( $this->feature_enabled( 'share_buttons' ) && $this->count_share_sets() > 0 ) {
				$labels[] = esc_html__( 'Share Buttons', 'wp-ulike-pro' );
			}

			if ( $this->get_dislike_using_labels() ) {
				$labels[] = esc_html__( 'Dislikes', 'wp-ulike-pro' );
			}

			$tracking = $this->get_view_tracking_types();
			if ( $this->feature_enabled( 'statistics' ) && ! empty( $tracking ) ) {
				$labels[] = esc_html__( 'View tracking', 'wp-ulike-pro' );
			}

			if ( class_exists( 'WP_Ulike_Pro_Tools' ) ) {
				$settings = WP_Ulike_Pro_Tools::get_rest_api_settings_data();
				if ( ! empty( $settings['enable_rest_api'] ) ) {
					$labels[] = esc_html__( 'REST API', 'wp-ulike-pro' );
				}
			}

			if ( $this->feature_enabled( 'schema' ) && $this->get_schema_configured_count() > 0 ) {
				$labels[] = esc_html__( 'Schema', 'wp-ulike-pro' );
			}

			return $labels;
		}

		/**
		 * License status row.
		 *
		 * @return array|null
		 */
		private function get_license_status_row() {
			$license_data = WP_Ulike_Pro_API::get_license_data( false );
			if ( ! is_array( $license_data ) || empty( $license_data['license'] ) ) {
				return array(
					'group' => 'pro',
					'label' => esc_html__( 'License', 'wp-ulike-pro' ),
					'value' => esc_html__( 'Not activated', 'wp-ulike-pro' ),
					'state' => 'bad',
				);
			}

			$status = $license_data['license'];
			$value  = esc_html__( 'Active' );
			$state  = 'good';

			if ( WP_Ulike_Pro_API::STATUS_EXPIRED === $status ) {
				$value = esc_html__( 'Expired', 'wp-ulike-pro' );
				$state = 'bad';
			} elseif ( ! WP_Ulike_Pro_API::is_license_active() ) {
				$value = esc_html__( 'Invalid' );
				$state = 'bad';
			} elseif ( WP_Ulike_Pro_API::is_license_about_to_expire() ) {
				$value = esc_html__( 'Renew soon', 'wp-ulike-pro' );
				$state = 'neutral';
			}

			return array(
				'group' => 'pro',
				'label' => esc_html__( 'License', 'wp-ulike-pro' ),
				'value' => $value,
				'state' => $state,
			);
		}

		/**
		 * Count posts with schema or FAQ configuration.
		 *
		 * @return int
		 */
		private function get_schema_configured_count() {
			if ( ! class_exists( 'WP_Ulike_Pro_Schema_Generator_Tool' ) ) {
				return 0;
			}

			add_filter( 'posts_where', array( 'WP_Ulike_Pro_Schema_Generator_Tool', 'filter_schema_only_where' ), 10, 2 );

			$query = new WP_Query(
				array(
					'post_type'                => get_post_types( array( 'public' => true ) ),
					'post_status'              => array( 'publish', 'draft', 'pending', 'future', 'private' ),
					'posts_per_page'           => 1,
					'fields'                   => 'ids',
					'wp_ulike_pro_schema_only' => true,
				)
			);

			remove_filter( 'posts_where', array( 'WP_Ulike_Pro_Schema_Generator_Tool', 'filter_schema_only_where' ), 10 );

			return (int) $query->found_posts;
		}

		/**
		 * Enabled view tracking type keys.
		 *
		 * @return array<int, string>
		 */
		private function get_view_tracking_types() {
			if ( ! function_exists( 'wp_ulike_get_option' ) ) {
				return array();
			}

			$types = wp_ulike_get_option( 'view_tracking_enabled_types', array( 'post' ) );

			return is_array( $types ) ? array_filter( $types ) : array();
		}

		/**
		 * Content types using the up/down (dislike) template.
		 *
		 * @return array<int, string> Translated labels.
		 */
		private function get_dislike_using_labels() {
			if ( ! function_exists( 'wp_ulike_get_option' ) ) {
				return array();
			}

			$using = array();

			$label_map = array(
				'posts_group|template'      => esc_html__( 'Posts' ),
				'comments_group|template'   => esc_html__( 'Comments' ),
				'buddypress_group|template' => esc_html__( 'BuddyPress', 'wp-ulike-pro' ),
				'bbpress_group|template'    => esc_html__( 'bbPress', 'wp-ulike-pro' ),
			);

			foreach ( $label_map as $option_key => $label ) {
				$template = wp_ulike_get_option( $option_key, '' );
				if ( 'updown-voting' === $template ) {
					$using[] = $label;
				}
			}

			return $using;
		}

		/**
		 * Count configured social share sets.
		 *
		 * @return int
		 */
		private function count_share_sets() {
			if ( ! function_exists( 'wp_ulike_get_option' ) ) {
				return 0;
			}

			$sets = wp_ulike_get_option( 'social_share', array() );

			if ( ! is_array( $sets ) ) {
				return 0;
			}

			$count = 0;
			foreach ( $sets as $set ) {
				if ( is_array( $set ) && ! empty( $set['slug'] ) ) {
					++$count;
				}
			}

			return $count;
		}
	}
}

