<?php
/**
 * Help screen integrations (WP ULike free About page).
 *
 * @package WP_ULike_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_Ulike_Pro_Overview' ) ) {

	/**
	 * Adds Pro-specific rows, cards, and tips to the free plugin Overview page.
	 */
	class WP_Ulike_Pro_Overview {

		/**
		 * Constructor.
		 */
		public function __construct() {
			add_filter( 'wp_ulike_admin_pages', array( $this, 'remove_go_pro_menu' ), 20 );
			add_filter( 'wp_ulike_about_status_rows', array( $this, 'add_status_rows' ), 10, 2 );
			add_filter( 'wp_ulike_about_pro_modules', array( $this, 'add_pro_modules' ), 10, 2 );
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
		 * Pro status rows for Help.
		 *
		 * @param array $rows   Rows.
		 * @param array $health Health report.
		 * @return array
		 */
		public function add_status_rows( $rows, $health ) {
			if ( ! class_exists( 'WP_Ulike_Pro_API' ) ) {
				return $rows;
			}

			$pro_rows = array(
				$this->get_license_status_row(),
				$this->get_display_automation_row(),
				$this->get_schema_generator_row(),
				$this->get_user_profiles_row(),
				$this->get_login_forms_row(),
				$this->get_view_tracking_row(),
				$this->get_dislikes_row(),
				$this->get_social_login_row(),
				$this->get_share_buttons_row(),
				$this->get_rest_api_row(),
			);

			foreach ( $pro_rows as $row ) {
				if ( ! empty( $row ) ) {
					$rows[] = $row;
				}
			}

			return $rows;
		}

		/**
		 * Pro module shortcuts on Help.
		 *
		 * @param array $modules Modules.
		 * @param array $health  Health.
		 * @return array
		 */
		public function add_pro_modules( $modules, $health ) {
			if ( ! empty( $modules ) ) {
				return $modules;
			}

			$tools = admin_url( 'admin.php?page=wp-ulike-pro-tools' );

			$modules = array(
				array(
					'title'       => esc_html__( 'Display Automation', WP_ULIKE_PRO_DOMAIN ),
					'description' => esc_html__( 'Start here to place like buttons automatically—no shortcodes needed.', WP_ULIKE_PRO_DOMAIN ),
					'url'         => admin_url( 'admin.php?page=wp-ulike-pro-tools&tab=display-automation' ),
					'icon'        => 'layout',
				),
				array(
					'title'       => esc_html__( 'Pro Statistics', WP_ULIKE_PRO_DOMAIN ),
					'description' => esc_html__( 'Charts, filters, and exports for your like activity.', WP_ULIKE_PRO_DOMAIN ),
					'url'         => admin_url( 'admin.php?page=wp-ulike-statistics' ),
					'icon'        => 'chart-area',
				),
				array(
					'title'       => esc_html__( 'User Profiles', WP_ULIKE_PRO_DOMAIN ),
					'description' => esc_html__( 'Public profile pages, permalinks, and author redirects.', WP_ULIKE_PRO_DOMAIN ),
					'url'         => $this->settings_tab_url( 'profiles' ),
					'icon'        => 'admin-users',
				),
				array(
					'title'       => esc_html__( 'Login & Signup', WP_ULIKE_PRO_DOMAIN ),
					'description' => esc_html__( 'Assign login and signup pages, then add forms with shortcodes.', WP_ULIKE_PRO_DOMAIN ),
					'url'         => $this->settings_tab_url( 'login-signup' ),
					'icon'        => 'privacy',
				),
				array(
					'title'       => esc_html__( 'Social Logins', WP_ULIKE_PRO_DOMAIN ),
					'description' => esc_html__( 'Optional OAuth sign-in alongside your login forms.', WP_ULIKE_PRO_DOMAIN ),
					'url'         => $this->settings_tab_url( 'social-logins' ),
					'icon'        => 'share',
				),
				array(
					'title'       => esc_html__( 'Share Buttons', WP_ULIKE_PRO_DOMAIN ),
					'description' => esc_html__( 'Optional social share sets via shortcode or auto display.', WP_ULIKE_PRO_DOMAIN ),
					'url'         => $this->settings_tab_url( 'share-buttons' ),
					'icon'        => 'share-alt2',
				),
				array(
					'title'       => esc_html__( 'Emails', WP_ULIKE_PRO_DOMAIN ),
					'description' => esc_html__( 'Customize welcome, reset, and verification emails.', WP_ULIKE_PRO_DOMAIN ),
					'url'         => $this->settings_tab_url( 'emails' ),
					'icon'        => 'email',
				),
				array(
					'title'       => esc_html__( 'Pro Tools', WP_ULIKE_PRO_DOMAIN ),
					'description' => esc_html__( 'Schema & FAQ markup, maintenance, bulk edits, REST API, GDPR, and debug info.', WP_ULIKE_PRO_DOMAIN ),
					'url'         => $tools,
					'icon'        => 'admin-tools',
				),
				array(
					'title'       => esc_html__( 'License', WP_ULIKE_PRO_DOMAIN ),
					'description' => esc_html__( 'Activation, renewals, and site limits.', WP_ULIKE_PRO_DOMAIN ),
					'url'         => admin_url( 'admin.php?page=wp-ulike-pro-license' ),
					'icon'        => 'admin-network',
				),
			);

			return $modules;
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
			$value  = esc_html__( 'Active', WP_ULIKE_PRO_DOMAIN );

			if ( WP_Ulike_Pro_API::STATUS_EXPIRED === $status ) {
				$value = esc_html__( 'Expired', WP_ULIKE_PRO_DOMAIN );
			} elseif ( ! WP_Ulike_Pro_API::is_license_active() ) {
				$value = esc_html__( 'Needs activation', WP_ULIKE_PRO_DOMAIN );
			}

			$meta[] = array(
				'label' => esc_html__( 'License', WP_ULIKE_PRO_DOMAIN ),
				'value' => $value,
				'url'   => admin_url( 'admin.php?page=wp-ulike-pro-license' ),
			);

			if ( ! empty( $license_data['expires'] ) ) {
				$expires = $license_data['expires'];
				if ( 'lifetime' === $expires ) {
					$expires_label = esc_html__( 'Lifetime', WP_ULIKE_PRO_DOMAIN );
				} else {
					$expires_label = date_i18n( get_option( 'date_format' ), strtotime( $expires ) );
				}

				$meta[] = array(
					'label' => esc_html__( 'License expires', WP_ULIKE_PRO_DOMAIN ),
					'value' => $expires_label,
					'url'   => admin_url( 'admin.php?page=wp-ulike-pro-license' ),
				);
			}

			$activations_left = isset( $license_data['activations_left'] ) ? $license_data['activations_left'] : '';
			if ( '' !== $activations_left && 'unlimited' !== $activations_left ) {
				$meta[] = array(
					'label' => esc_html__( 'Activations left', WP_ULIKE_PRO_DOMAIN ),
					'value' => is_numeric( $activations_left ) ? number_format_i18n( (int) $activations_left ) : esc_html( (string) $activations_left ),
					'url'   => admin_url( 'admin.php?page=wp-ulike-pro-license' ),
				);
			}

			if ( ! empty( $license_data['site_count'] ) && ! empty( $license_data['license_limit'] ) && '0' !== (string) $license_data['license_limit'] ) {
				$meta[] = array(
					'label' => esc_html__( 'Sites in use', WP_ULIKE_PRO_DOMAIN ),
					'value' => sprintf(
						'%s / %s',
						number_format_i18n( (int) $license_data['site_count'] ),
						number_format_i18n( (int) $license_data['license_limit'] )
					),
					'url'   => admin_url( 'admin.php?page=wp-ulike-pro-license' ),
				);
			}

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

			$bits = array();

			if ( ! WP_Ulike_Pro_API::is_license_active() ) {
				$bits[] = esc_html__( 'Activate your Pro license to unlock profiles, forms, and tools.', WP_ULIKE_PRO_DOMAIN );
			} else {
				$active_count = count( $this->get_active_module_labels() );
				if ( $active_count > 0 ) {
					$bits[] = sprintf(
						/* translators: %d: number of active Pro features */
						esc_html( _n( '%d Pro feature is active—see the status table below for details.', '%d Pro features are active—see the status table below for details.', $active_count, WP_ULIKE_PRO_DOMAIN ) ),
						$active_count
					);
				} else {
					$bits[] = esc_html__( 'Start with Display Automation or Pro Tools below to set up your site.', WP_ULIKE_PRO_DOMAIN );
				}
			}

			if ( empty( $bits ) ) {
				return $summary;
			}

			$suffix = implode( ' ', $bits );

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
			$tools_url    = admin_url( 'admin.php?page=wp-ulike-pro-tools&tab=display-automation' );
			$profiles_url = $this->settings_tab_url( 'profiles' );

			$tips[] = array(
				'text' => esc_html__( 'Display Automation (Tools) is separate from Automatic Display under Settings → Posts. Using both can show duplicate buttons—prefer one approach or disable Automatic Display when rules are active.', WP_ULIKE_PRO_DOMAIN ),
				'url'  => $tools_url,
				'link' => esc_html__( 'Display Automation', WP_ULIKE_PRO_DOMAIN ),
			);

			$tips[] = array(
				'text' => esc_html__( 'Do not cache pages that use login, registration, password reset, edit account, or user profile shortcodes.', WP_ULIKE_PRO_DOMAIN ),
				'url'  => $this->settings_tab_url( 'login-signup' ),
				'link' => esc_html__( 'Login & Signup', WP_ULIKE_PRO_DOMAIN ),
			);

			if ( $this->is_option_enabled( 'enable_user_profiles' ) ) {
				$tips[] = array(
					'text' => esc_html__( 'User profile URLs depend on the page selected in Profiles settings—update both the page and the permalink base if you change slugs.', WP_ULIKE_PRO_DOMAIN ),
					'url'  => $profiles_url,
					'link' => esc_html__( 'Profiles', WP_ULIKE_PRO_DOMAIN ),
				);
			}

			$tips[] = array(
				'text' => esc_html__( 'Help → Settings backup saves settings, customizer values, display rules, and REST API configuration. Per-post schema/FAQ data and REST API keys are not included—review those in Pro Tools before you move.', WP_ULIKE_PRO_DOMAIN ),
				'url'  => admin_url( 'admin.php?page=wp-ulike-pro-tools' ),
				'link' => esc_html__( 'Pro Tools', WP_ULIKE_PRO_DOMAIN ),
			);

			$tips[] = array(
				'text' => esc_html__( 'Need to search users or remove vote logs for compliance? Pro Tools → GDPR works alongside WordPress privacy export and erase.', WP_ULIKE_PRO_DOMAIN ),
				'url'  => admin_url( 'admin.php?page=wp-ulike-pro-tools&tab=gdpr' ),
				'link' => esc_html__( 'GDPR tools', WP_ULIKE_PRO_DOMAIN ),
			);

			return $tips;
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

			if ( class_exists( 'WP_Ulike_Pro_Display_Automation' ) ) {
				$active = WP_Ulike_Pro_Display_Automation::get_active_rules();
				$count  = is_array( $active ) ? count( $active ) : 0;
				if ( $count > 0 ) {
					$labels[] = esc_html__( 'display automation', WP_ULIKE_PRO_DOMAIN );
				}
			}

			if ( $this->is_option_enabled( 'enable_user_profiles' ) ) {
				$labels[] = esc_html__( 'profiles', WP_ULIKE_PRO_DOMAIN );
			}

			if ( function_exists( 'wp_ulike_get_option' ) ) {
				$login_id  = (int) wp_ulike_get_option( 'login_core_page', 0 );
				$signup_id = (int) wp_ulike_get_option( 'signup_core_page', 0 );
				if ( $login_id > 0 || $signup_id > 0 ) {
					$labels[] = esc_html__( 'login forms', WP_ULIKE_PRO_DOMAIN );
				}
			}

			if ( $this->is_option_enabled( 'enable_social_login' ) ) {
				$labels[] = esc_html__( 'social login', WP_ULIKE_PRO_DOMAIN );
			}

			$share_count = $this->count_share_sets();
			if ( $share_count > 0 ) {
				$labels[] = esc_html__( 'share buttons', WP_ULIKE_PRO_DOMAIN );
			}

			if ( $this->get_dislike_using_labels() ) {
				$labels[] = esc_html__( 'dislikes', WP_ULIKE_PRO_DOMAIN );
			}

			$tracking = $this->get_view_tracking_types();
			if ( ! empty( $tracking ) ) {
				$labels[] = esc_html__( 'view tracking', WP_ULIKE_PRO_DOMAIN );
			}

			if ( class_exists( 'WP_Ulike_Pro_Tools' ) ) {
				$settings = WP_Ulike_Pro_Tools::get_rest_api_settings_data();
				if ( ! empty( $settings['enable_rest_api'] ) ) {
					$labels[] = esc_html__( 'REST API', WP_ULIKE_PRO_DOMAIN );
				}
			}

			if ( $this->get_schema_configured_count() > 0 ) {
				$labels[] = esc_html__( 'schema', WP_ULIKE_PRO_DOMAIN );
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
					'label' => esc_html__( 'License', WP_ULIKE_PRO_DOMAIN ),
					'value' => esc_html__( 'Not activated', WP_ULIKE_PRO_DOMAIN ),
					'state' => 'bad',
				);
			}

			$status = $license_data['license'];
			$value  = esc_html__( 'Active', WP_ULIKE_PRO_DOMAIN );
			$state  = 'good';

			if ( WP_Ulike_Pro_API::STATUS_EXPIRED === $status ) {
				$value = esc_html__( 'Expired', WP_ULIKE_PRO_DOMAIN );
				$state = 'bad';
			} elseif ( ! WP_Ulike_Pro_API::is_license_active() ) {
				$value = esc_html__( 'Invalid', WP_ULIKE_PRO_DOMAIN );
				$state = 'bad';
			} elseif ( WP_Ulike_Pro_API::is_license_about_to_expire() ) {
				$value = esc_html__( 'Renew soon', WP_ULIKE_PRO_DOMAIN );
				$state = 'neutral';
			}

			return array(
				'group' => 'pro',
				'label' => esc_html__( 'License', WP_ULIKE_PRO_DOMAIN ),
				'value' => $value,
				'state' => $state,
			);
		}

		/**
		 * Schema Generator row.
		 *
		 * @return array|null
		 */
		private function get_schema_generator_row() {
			if ( ! class_exists( 'WP_Ulike_Pro_Schema_Generator_Tool' ) ) {
				return null;
			}

			$count = $this->get_schema_configured_count();

			return array(
				'group' => 'pro',
				'label' => esc_html__( 'Schema markup', WP_ULIKE_PRO_DOMAIN ),
				'value' => $count > 0
					? sprintf(
						/* translators: %d: number of posts with schema or FAQ data */
						esc_html( _n( '%d post configured', '%d posts configured', $count, WP_ULIKE_PRO_DOMAIN ) ),
						$count
					)
					: esc_html__( 'Not configured', WP_ULIKE_PRO_DOMAIN ),
				'state' => $count > 0 ? 'good' : 'neutral',
				'hint'  => esc_html__( 'Open Pro Tools → Schema Generator. Uses star votes when the Star Rating template is active, or likes and dislikes otherwise. Emoji reactions are not included.', WP_ULIKE_PRO_DOMAIN ),
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
		 * Display automation rules row.
		 *
		 * @return array|null
		 */
		private function get_display_automation_row() {
			if ( ! class_exists( 'WP_Ulike_Pro_Display_Automation' ) ) {
				return null;
			}

			$active = WP_Ulike_Pro_Display_Automation::get_active_rules();
			$count  = is_array( $active ) ? count( $active ) : 0;

			return array(
				'group' => 'pro',
				'label' => esc_html__( 'Display automation', WP_ULIKE_PRO_DOMAIN ),
				'value' => $count > 0
					? sprintf(
						/* translators: %d: number of active rules */
						esc_html( _n( '%d active rule', '%d active rules', $count, WP_ULIKE_PRO_DOMAIN ) ),
						$count
					)
					: esc_html__( 'No rules yet', WP_ULIKE_PRO_DOMAIN ),
				'state' => $count > 0 ? 'good' : 'neutral',
				'hint'  => esc_html__( 'Separate from Settings → Posts “Automatic Display”. Avoid using both without planning—duplicate buttons can appear.', WP_ULIKE_PRO_DOMAIN ),
			);
		}

		/**
		 * View tracking row.
		 *
		 * @return array|null
		 */
		private function get_view_tracking_row() {
			if ( ! function_exists( 'wp_ulike_get_option' ) ) {
				return null;
			}

			$types  = $this->get_view_tracking_types();
			$labels = $this->format_view_tracking_labels( $types );

			return array(
				'group' => 'pro',
				'label' => esc_html__( 'View tracking', WP_ULIKE_PRO_DOMAIN ),
				'value' => ! empty( $labels )
					? implode( ', ', $labels )
					: esc_html__( 'Off', WP_ULIKE_PRO_DOMAIN ),
				'state' => ! empty( $labels ) ? 'good' : 'neutral',
			);
		}

		/**
		 * Dislikes (up/down template) row.
		 *
		 * @return array|null
		 */
		private function get_dislikes_row() {
			$using = $this->get_dislike_using_labels();

			if ( empty( $using ) ) {
				return null;
			}

			return array(
				'group' => 'pro',
				'label' => esc_html__( 'Dislikes', WP_ULIKE_PRO_DOMAIN ),
				'value' => implode( ', ', $using ),
				'state' => 'good',
			);
		}

		/**
		 * Login and signup pages row.
		 *
		 * @return array|null
		 */
		private function get_login_forms_row() {
			if ( ! function_exists( 'wp_ulike_get_option' ) ) {
				return null;
			}

			$login_id  = (int) wp_ulike_get_option( 'login_core_page', 0 );
			$signup_id = (int) wp_ulike_get_option( 'signup_core_page', 0 );
			$assigned  = 0;

			if ( $login_id > 0 ) {
				++$assigned;
			}
			if ( $signup_id > 0 ) {
				++$assigned;
			}

			if ( 0 === $assigned ) {
				return array(
					'group' => 'pro',
					'label' => esc_html__( 'Login & forms', WP_ULIKE_PRO_DOMAIN ),
					'value' => esc_html__( 'Pages not assigned', WP_ULIKE_PRO_DOMAIN ),
					'state' => 'neutral',
					'hint'  => esc_html__( 'Assign login and signup pages under Settings → Login & Signup, or place shortcodes on your own pages.', WP_ULIKE_PRO_DOMAIN ),
				);
			}

			$value = 2 === $assigned
				? esc_html__( 'Login and signup pages set', WP_ULIKE_PRO_DOMAIN )
				: ( $login_id > 0
					? esc_html__( 'Login page set', WP_ULIKE_PRO_DOMAIN )
					: esc_html__( 'Signup page set', WP_ULIKE_PRO_DOMAIN ) );

			return array(
				'group' => 'pro',
				'label' => esc_html__( 'Login & forms', WP_ULIKE_PRO_DOMAIN ),
				'value' => $value,
				'state' => 2 === $assigned ? 'good' : 'neutral',
				'hint'  => 2 === $assigned
					? ''
					: esc_html__( 'Assign the missing page under Settings → Login & Signup for a complete login flow.', WP_ULIKE_PRO_DOMAIN ),
			);
		}

		/**
		 * User profiles row.
		 *
		 * @return array|null
		 */
		private function get_user_profiles_row() {
			$enabled = $this->is_option_enabled( 'enable_user_profiles' );

			if ( ! $enabled ) {
				return array(
					'group' => 'pro',
					'label' => esc_html__( 'User profiles', WP_ULIKE_PRO_DOMAIN ),
					'value' => esc_html__( 'Off', WP_ULIKE_PRO_DOMAIN ),
					'state' => 'neutral',
				);
			}

			$page_id = function_exists( 'wp_ulike_get_option' ) ? (int) wp_ulike_get_option( 'user_profiles_core_page', 0 ) : 0;
			$value   = esc_html__( 'On', WP_ULIKE_PRO_DOMAIN );

			if ( $page_id > 0 ) {
				$title = get_the_title( $page_id );
				if ( $title ) {
					$value = $title;
				}
			}

			return array(
				'group' => 'pro',
				'label' => esc_html__( 'User profiles', WP_ULIKE_PRO_DOMAIN ),
				'value' => $value,
				'state' => 'good',
			);
		}

		/**
		 * Social login row.
		 *
		 * @return array|null
		 */
		private function get_social_login_row() {
			if ( ! $this->is_option_enabled( 'enable_social_login' ) ) {
				return null;
			}

			$networks = wp_ulike_get_option( 'social_logins', array() );
			$count    = 0;

			if ( is_array( $networks ) ) {
				foreach ( $networks as $network ) {
					if ( is_array( $network ) && empty( $network['disable'] ) ) {
						++$count;
					}
				}
			}

			return array(
				'group' => 'pro',
				'label' => esc_html__( 'Social login', WP_ULIKE_PRO_DOMAIN ),
				'value' => $count > 0
					? sprintf(
						/* translators: %d: number of networks */
						esc_html( _n( 'On (%d network)', 'On (%d networks)', $count, WP_ULIKE_PRO_DOMAIN ) ),
						$count
					)
					: esc_html__( 'On (no networks configured)', WP_ULIKE_PRO_DOMAIN ),
				'state' => $count > 0 ? 'good' : 'neutral',
			);
		}

		/**
		 * Share button sets row.
		 *
		 * @return array|null
		 */
		private function get_share_buttons_row() {
			$count = $this->count_share_sets();

			if ( $count < 1 ) {
				return null;
			}

			return array(
				'group' => 'pro',
				'label' => esc_html__( 'Share buttons', WP_ULIKE_PRO_DOMAIN ),
				'value' => $count > 0
					? sprintf(
						/* translators: %d: number of share sets */
						esc_html( _n( '%d set configured', '%d sets configured', $count, WP_ULIKE_PRO_DOMAIN ) ),
						$count
					)
					: esc_html__( 'None configured', WP_ULIKE_PRO_DOMAIN ),
				'state' => $count > 0 ? 'good' : 'neutral',
			);
		}

		/**
		 * REST API row.
		 *
		 * @return array|null
		 */
		private function get_rest_api_row() {
			if ( ! class_exists( 'WP_Ulike_Pro_Tools' ) ) {
				return null;
			}

			$settings = WP_Ulike_Pro_Tools::get_rest_api_settings_data();
			$enabled  = ! empty( $settings['enable_rest_api'] );

			if ( ! $enabled ) {
				return null;
			}

			$keys       = get_option( 'wp_ulike_rest_api_keys', array() );
			$key_count  = is_array( $keys ) ? count( $keys ) : 0;
			$value_text = $key_count > 0
				? sprintf(
					/* translators: %d: API key count */
					esc_html( _n( 'Enabled (%d key)', 'Enabled (%d keys)', $key_count, WP_ULIKE_PRO_DOMAIN ) ),
					$key_count
				)
				: esc_html__( 'Enabled (no API keys yet)', WP_ULIKE_PRO_DOMAIN );

			return array(
				'group' => 'pro',
				'label' => esc_html__( 'REST API', WP_ULIKE_PRO_DOMAIN ),
				'value' => $value_text,
				'state' => 'good',
			);
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
		 * Translated labels for view tracking types.
		 *
		 * @param array $types Type keys.
		 * @return array<int, string>
		 */
		private function format_view_tracking_labels( $types ) {
			$map = array(
				'post'     => esc_html__( 'Posts', WP_ULIKE_PRO_DOMAIN ),
				'comment'  => esc_html__( 'Comments', WP_ULIKE_PRO_DOMAIN ),
				'activity' => esc_html__( 'Activities', WP_ULIKE_PRO_DOMAIN ),
				'topic'    => esc_html__( 'Topics', WP_ULIKE_PRO_DOMAIN ),
			);

			$labels = array();

			foreach ( $types as $type ) {
				if ( isset( $map[ $type ] ) ) {
					$labels[] = $map[ $type ];
				}
			}

			return $labels;
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
				'posts_group|template'      => esc_html__( 'Posts', WP_ULIKE_PRO_DOMAIN ),
				'comments_group|template'   => esc_html__( 'Comments', WP_ULIKE_PRO_DOMAIN ),
				'buddypress_group|template' => esc_html__( 'BuddyPress', WP_ULIKE_PRO_DOMAIN ),
				'bbpress_group|template'    => esc_html__( 'bbPress', WP_ULIKE_PRO_DOMAIN ),
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

