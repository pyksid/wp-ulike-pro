<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class WP_Ulike_Pro_License {

	const PAGE_ID = 'wp-ulike-pro-license';

	public static function get_errors_details() {
		$license_page_link = self::get_url();

		return [
			WP_Ulike_Pro_API::STATUS_EXPIRED => [
				'title'       => esc_html__( 'Your License Has Expired', WP_ULIKE_PRO_DOMAIN ),
				'description' => esc_html__( 'Want to keep creating better marketing and high-performing websites? Renew your subscription to regain access to all of the new pro features, templates, updates & more', WP_ULIKE_PRO_DOMAIN ),
				'button_text' => esc_html__( 'Renew License', WP_ULIKE_PRO_DOMAIN ),
				'button_url'  => WP_Ulike_Pro_API::PRICING_URL,
			],
			WP_Ulike_Pro_API::STATUS_DISABLED => [
				'title'       => esc_html__( 'Your License Is Inactive', WP_ULIKE_PRO_DOMAIN ),
				'description' => sprintf(
					/* translators: 1: Bold text opening tag, 2: Bold text closing tag. */
					esc_html__( '%1$sYour license key has been cancelled%2$s (most likely due to a refund request). Please consider acquiring a new license.', WP_ULIKE_PRO_DOMAIN ),
					'<strong>',
					'</strong>'
				),
				'button_text' => esc_html__( 'Activate License', WP_ULIKE_PRO_DOMAIN ),
				'button_url'  => $license_page_link,
			],
			WP_Ulike_Pro_API::STATUS_INVALID => [
				'title'       => esc_html__( 'License Invalid', WP_ULIKE_PRO_DOMAIN ),
				'description' => sprintf(
					/* translators: 1: Bold text opening tag, 2: Bold text closing tag. */
					esc_html__( '%1$sYour license key doesn\'t match your current domain%2$s. This is most likely due to a change in the domain URL of your site (including HTTPS/SSL migration). Please deactivate the license and then reactivate it again.', WP_ULIKE_PRO_DOMAIN ),
					'<strong>',
					'</strong>'
				),
				'button_text' => esc_html__( 'Reactivate License', WP_ULIKE_PRO_DOMAIN ),
				'button_url'  => $license_page_link,
			],
			WP_Ulike_Pro_API::STATUS_SITE_INACTIVE => [
				'title'       => esc_html__( 'License Mismatch', WP_ULIKE_PRO_DOMAIN ),
				'description' => sprintf(
					/* translators: 1: Bold text opening tag, 2: Bold text closing tag. */
					esc_html__( '%1$sYour license key doesn\'t match your current domain%2$s. This is most likely due to a change in the domain URL. Please deactivate the license and then reactivate it again.', WP_ULIKE_PRO_DOMAIN ),
					'<strong>',
					'</strong>'
				),
				'button_text' => esc_html__( 'Reactivate License', WP_ULIKE_PRO_DOMAIN ),
				'button_url'  => $license_page_link,
			],
		];
	}

	public static function deactivate() {
		WP_Ulike_Pro_API::deactivate_license();

		delete_option( 'wp_ulike_pro_license_key' );
		delete_transient( 'wp_ulike_pro_license_data' );
		delete_transient( 'wp_ulike_pro_license_data_fallback' );
	}

	public static function get_hidden_license_key() {
		$input_string = self::get_license_key();

		$start = 5;
		$length = mb_strlen( $input_string ) - $start - 5;

		$mask_string = preg_replace( '/\S/', 'X', $input_string );
		$mask_string = mb_substr( $mask_string, $start, $length );
		$input_string = substr_replace( $input_string, $mask_string, $start, $length );

		return $input_string;
	}

	public static function get_license_key() {
		return trim( get_option( 'wp_ulike_pro_license_key' ) );
	}

	public static function set_license_key( $license_key ) {
		return update_option( 'wp_ulike_pro_license_key', $license_key );
	}

	public function action_activate_license() {
		check_admin_referer( 'wp-ulike-pro-license' );

		$license_key = wp_ulike_pro_unstable_get_super_global_value( $_POST, 'wp_ulike_pro_license_key' );

		if ( ! $license_key ) {
			wp_die( esc_html__( 'Please enter your license key.', WP_ULIKE_PRO_DOMAIN ), esc_html__( 'WP ULike Pro', WP_ULIKE_PRO_DOMAIN ), [
				'back_link' => true,
			] );
		}

		$data = WP_Ulike_Pro_API::activate_license( $license_key );

		if ( is_wp_error( $data ) ) {
			wp_die( sprintf( '%s (%s) ', $data->get_error_message(), $data->get_error_code() ), esc_html__( 'WP ULike Pro', WP_ULIKE_PRO_DOMAIN ), [
				'back_link' => true,
			] );
		}

		if ( WP_Ulike_Pro_API::STATUS_VALID !== $data['license'] ) {
			$error_msg = WP_Ulike_Pro_API::get_error_message( $data['error'] );
			wp_die( $error_msg, esc_html__( 'WP ULike Pro', WP_ULIKE_PRO_DOMAIN ), [
				'back_link' => true,
			] );
		}

		self::set_license_key( $license_key );
		WP_Ulike_Pro_API::set_license_data( $data );

		$this->safe_redirect( wp_ulike_pro_unstable_get_super_global_value( $_POST, '_wp_http_referer' ) );
		die;
	}

	protected function safe_redirect( $url ) {
		wp_safe_redirect( $url );
		die;
	}

	public function action_deactivate_license() {
		check_admin_referer( 'wp-ulike-pro-license' );

		$this->deactivate();

		$this->safe_redirect( wp_ulike_pro_unstable_get_super_global_value( $_POST, '_wp_http_referer' ) );
		die;
	}

	public static function get_url() {
		return admin_url( 'admin.php?page=' . self::PAGE_ID );
	}

	public static function render_part_license_status_header( $license_data ) {
		$license_errors = [
			WP_Ulike_Pro_API::STATUS_EXPIRED        => esc_html__( 'Expired',WP_ULIKE_PRO_DOMAIN ),
			WP_Ulike_Pro_API::STATUS_SITE_INACTIVE  => esc_html__( 'Mismatch',WP_ULIKE_PRO_DOMAIN ),
			WP_Ulike_Pro_API::STATUS_INVALID        => esc_html__( 'Invalid',WP_ULIKE_PRO_DOMAIN ),
			WP_Ulike_Pro_API::STATUS_DISABLED       => esc_html__( 'Cancelled',WP_ULIKE_PRO_DOMAIN ),
			WP_Ulike_Pro_API::STATUS_HTTP_ERROR     => esc_html__( 'HTTP Error',WP_ULIKE_PRO_DOMAIN ),
			WP_Ulike_Pro_API::STATUS_MISSING        => esc_html__( 'Missing',WP_ULIKE_PRO_DOMAIN ),
			WP_Ulike_Pro_API::STATUS_REQUEST_LOCKED => esc_html__( 'Request Locked',WP_ULIKE_PRO_DOMAIN ),
		];

		echo esc_html__( 'Status',WP_ULIKE_PRO_DOMAIN ); ?>:
		<?php if ( $license_data['license'] === WP_Ulike_Pro_API::STATUS_VALID ) : ?>
			<span style="color: #008000; font-style: italic;"><?php echo esc_html__( 'Active',WP_ULIKE_PRO_DOMAIN ); ?></span>
		<?php else : ?>
			<span style="color: #ff0000; font-style: italic;">
				<?php
				echo isset( $license_data['license'], $license_errors[ $license_data['license'] ] )
					? esc_html( $license_errors[ $license_data['license'] ] )
					: esc_html__( 'Unknown',WP_ULIKE_PRO_DOMAIN ) . ' (' . esc_html( $license_data['license'] ) . ')';
				?>
			</span>
		<?php endif;
	}

	private function is_block_editor_page() {
		$current_screen = get_current_screen();

		if ( method_exists( $current_screen, 'is_block_editor' ) && $current_screen->is_block_editor() ) {
			return true;
		}

		if ( function_exists( 'is_gutenberg_page' ) && is_gutenberg_page() ) {
			return true;
		}

		return false;
	}

	public function admin_license_details() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( $this->is_block_editor_page() ) {
			return;
		}

		$license_key = self::get_license_key();

		if ( empty( $license_key ) ) {
			echo wp_ulike_get_notice_render([
				'id'          => 'wp_ulike_license_activate',
				'title'       => esc_html__( 'Welcome to WP ULike PRO!', WP_ULIKE_PRO_DOMAIN ),
				'description' => esc_html__( 'Please activate your license to get automatic updates, premium support, and unlimited access to our pro features.' , WP_ULIKE_PRO_DOMAIN ),
				'skin'        => 'error',
				'has_close'   => false,
				'buttons'     => array(
					array(
						'label'      => esc_html__( 'Activate License', WP_ULIKE_PRO_DOMAIN ),
						'link'       => self_admin_url( 'admin.php?page=wp-ulike-pro-license' ),
						'target'     => '_self'
					)
				),
				'image'     => [
					'width' => '100',
					'src'   => WP_ULIKE_PRO_PUBLIC_URL . '/assets/img/svg/admin/banner-license-not-exist.svg'
				]
			]);
			return;
		}

		$license_data = WP_Ulike_Pro_API::get_license_data();
		if ( empty( $license_data['license'] ) ) {
			return;
		}

		$errors = self::get_errors_details();

		if ( isset( $errors[ $license_data['license'] ] ) ) {
			$error_data  = $errors[ $license_data['license'] ];
			echo wp_ulike_get_notice_render([
				'id'          => 'wp_ulike_license_' . $license_data['license'],
				'title'       => $error_data['title'],
				'description' => $error_data['description'],
				'skin'        => 'info',
				'has_close'   => false,
				'buttons'     => array(
					array(
						'label'      => $error_data['button_text'],
						'link'       => $error_data['button_url'],
						'target'     => '_self'
					),
					array(
						'label'      => esc_html__('Remind Me Later', WP_ULIKE_PRO_DOMAIN),
						'type'       => 'skip',
						'color_name' => 'info',
						'expiration' => DAY_IN_SECONDS * 3
					)
				),
				'image'     => [
					'width' => '100',
					'src'   => WP_ULIKE_PRO_PUBLIC_URL . '/assets/img/svg/admin/banner-license-status.svg'
				]
			]);
			return;
		}

		if ( WP_Ulike_Pro_API::is_license_active() && WP_Ulike_Pro_API::is_license_about_to_expire() ) {
			$renew_url = add_query_arg( array(
				'edd_license_key' => $license_key,
				'download_id'     => WP_Ulike_Pro_API::PRODUCT_ID
			), WP_Ulike_Pro_API::RENEW_URL );

			$title       = sprintf( esc_html__( 'Your License Will Expire in %s.', WP_ULIKE_PRO_DOMAIN ), human_time_diff( current_time( 'timestamp' ), strtotime( $license_data['expires'] ) ) );
			$description = esc_html__( 'Your WP ULike Pro license is about to expire. Renew now and get updates, support, Pro templates for another year.', WP_ULIKE_PRO_DOMAIN );

			if ( isset( $license_data['renewal_discount'] ) && 0 < $license_data['renewal_discount'] ) {
				$description = sprintf(
					/* translators: %s: Renewal discount. */
					esc_html__( 'Your WP ULike Pro license is about to expire. Renew now and get an exclusive, time-limited %s discount.', WP_ULIKE_PRO_DOMAIN ),
					$license_data['renewal_discount'] . '&#37;'
				);
			}

			echo wp_ulike_get_notice_render([
				'id'          => 'wp_ulike_license_renewal',
				'title'       => $title,
				'description' => $description,
				'has_close'   => false,
				'buttons'     => array(
					array(
						'label'      => esc_html__( 'Renew License', WP_ULIKE_PRO_DOMAIN ),
						'link'       => $renew_url,
						'target'     => '_self'
					),
					array(
						'label'      => esc_html__('Remind Me Later', WP_ULIKE_PRO_DOMAIN),
						'type'       => 'skip',
						'color_name' => 'info',
						'expiration' => DAY_IN_SECONDS * 3
					)
				),
				'image'     => [
					'width' => '100',
					'src'   => WP_ULIKE_PRO_PUBLIC_URL . '/assets/img/svg/admin/banner-license-renewal.svg'
				]
			]);
		}
	}

	public function plugin_action_links( $links ) {
		$license_key = self::get_license_key();

		if ( empty( $license_key ) ) {
			$links['active_license'] = sprintf( '<a href="%s" class="wp-ulike-plugins-gopro">%s</a>', self::get_url(), esc_html__( 'Activate License', WP_ULIKE_PRO_DOMAIN ) );
		}

		return $links;
	}

	public function register_page( $submenus ) {
		$license_submenu_page = array( 'license' => array(
			'title'       => esc_html__( 'License', WP_ULIKE_PRO_DOMAIN ),
			'parent_slug' => 'wp-ulike-settings',
			'capability'  => 'manage_options',
			'path'        => WP_ULIKE_PRO_ADMIN_DIR . '/includes/templates/license.php',
			'menu_slug'   => self::PAGE_ID,
			'load_screen' => false
		) );
		array_splice( $submenus, 5, 0, $license_submenu_page  );

		return $submenus;
	}

	private function update_old_license_info(){
		$old_license_info = get_site_option( 'wp_ulike_pro_license_info' );
		if( ! empty( $old_license_info['purchase_code'] ) ){
			self::set_license_key( $old_license_info['purchase_code'] );
			delete_site_option( 'wp_ulike_pro_license_info' );
		}
	}

	public function __construct() {
		// Update old license info to the new value
		$this->update_old_license_info();

		add_filter( 'wp_ulike_admin_pages', [ $this, 'register_page' ], 10, 15 );
		add_action( 'admin_post_wp_ulike_pro_activate_license', [ $this, 'action_activate_license' ] );
		add_action( 'admin_post_wp_ulike_pro_deactivate_license', [ $this, 'action_deactivate_license' ] );

		add_action( 'admin_notices', [ $this, 'admin_license_details' ], 25 );

		add_filter( 'plugin_action_links_' . WP_ULIKE_PRO_BASENAME, [ $this, 'plugin_action_links' ], 50 );
	}
}