<?php
/**
 * License template
 * 
 * @package    wp-ulike-pro
 * @author     TechnoWich 2025
 * @link       https://wpulike.com
 */

	// no direct access allowed
	if ( ! defined('ABSPATH') ) {
	    die();
	}

	$license_key = WP_Ulike_Pro_License::get_license_key();
?>

<div class="wrap wp-ulike-pro-admin-page-license">
	<h2><?php esc_html_e( 'License Settings', WP_ULIKE_PRO_DOMAIN ); ?></h2>

	<form class="wp-ulike-pro-license-box" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'wp-ulike-pro-license' ); ?>

		<h3>
			<?php esc_html_e( 'WP ULike Pro License', WP_ULIKE_PRO_DOMAIN ); ?>
		</h3>

		<?php if ( empty( $license_key ) ) : ?>

			<p><?php esc_html_e( 'Enter your license key here, to activate WP ULike Pro, and get automatic updates, premium support, and unlimited access to the template library.', WP_ULIKE_PRO_DOMAIN ); ?></p>

			<ol>
				<li><?php printf(
					/* translators: 1: Link opening tag, 2: Link closing tag. */
					esc_html__( 'Log in to %1$syour account%2$s to get your license key.', WP_ULIKE_PRO_DOMAIN ),
					'<a href="https://wpulike.com/user/?utm_source=license-page&utm_campaign=get-license&utm_medium=wp-dash" target="_blank">',
					'</a>'
				); ?></li>
				<li>
					<?php printf(
						/* translators: 1: Link opening tag, 2: Link closing tag. */
						esc_html__( 'If you don\'t yet have a license key, %1$sget WP ULike Pro now%2$s.', WP_ULIKE_PRO_DOMAIN ),
						'<a href="https://wpulike.com/pricing/?utm_source=license-page&utm_campaign=gopro&utm_medium=wp-dash" target="_blank">',
						'</a>'
					); ?>
				</li>
				<li><?php esc_html_e( 'Copy the license key from your account and paste it below.', WP_ULIKE_PRO_DOMAIN ); ?></li>
			</ol>

			<input type="hidden" name="action" value="wp_ulike_pro_activate_license"/>

			<label for="wp-ulike-pro-license-key"><?php esc_html_e( 'Your License Key', WP_ULIKE_PRO_DOMAIN ); ?></label>

			<input id="wp-ulike-pro-license-key" class="regular-text code" name="wp_ulike_pro_license_key" type="text" value="" placeholder="<?php esc_attr_e( 'Please enter your license key here', WP_ULIKE_PRO_DOMAIN ); ?>"/>

			<input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Activate', WP_ULIKE_PRO_DOMAIN ); ?>"/>

			<p class="description"><?php printf( esc_html__( 'Your license key should look something like this: %s', WP_ULIKE_PRO_DOMAIN ), '<code>fb351f05958872E193feb37a505a84be</code>' ); ?></p>

		<?php else :
			$license_data = WP_Ulike_Pro_API::get_license_data( true ); ?>
			<input type="hidden" name="action" value="wp_ulike_pro_deactivate_license"/>

			<label for="wp-ulike-pro-license-key"><?php esc_html_e( 'Your License Key', WP_ULIKE_PRO_DOMAIN ); ?>:</label>

			<input id="wp-ulike-pro-license-key" class="regular-text code" type="text" value="<?php echo esc_attr( WP_Ulike_Pro_License::get_hidden_license_key() ); ?>" disabled/>

			<input type="submit" class="button" value="<?php esc_attr_e( 'Deactivate', WP_ULIKE_PRO_DOMAIN ); ?>"/>

			<p><?php WP_Ulike_Pro_License::render_part_license_status_header( $license_data ); ?></p>

			<?php if ( WP_Ulike_Pro_API::STATUS_EXPIRED === $license_data['license'] ) : ?>
			<p class="wp-ulike-pro-admin-alert wp-ulike-pro-alert-danger"><?php printf( __( '<strong>Your License Has Expired.</strong> <a href="%s" target="_blank">Renew your license today</a> to keep getting feature updates, premium support and unlimited access to the template library.', WP_ULIKE_PRO_DOMAIN ), 'https://wpulike.com/pricing/?utm_source=license-page&utm_campaign=renewal&utm_medium=wp-dash' ); ?></p>
		<?php endif; ?>

			<?php if ( WP_Ulike_Pro_API::STATUS_SITE_INACTIVE === $license_data['license'] ) : ?>
			<p class="wp-ulike-pro-admin-alert wp-ulike-pro-alert-danger"><?php echo __( '<strong>Your license key doesn\'t match your current domain</strong>. This is most likely due to a change in the domain URL of your site (including HTTPS/SSL migration). Please deactivate the license and then reactivate it again.', WP_ULIKE_PRO_DOMAIN ); ?></p>
		<?php endif; ?>

			<?php if ( WP_Ulike_Pro_API::STATUS_INVALID === $license_data['license'] ) : ?>
			<p class="wp-ulike-pro-admin-alert wp-ulike-pro-alert-info"><?php echo __( '<strong>Your license key doesn\'t match your current domain</strong>. This is most likely due to a change in the domain URL of your site (including HTTPS/SSL migration). Please deactivate the license and then reactivate it again.', WP_ULIKE_PRO_DOMAIN ); ?></p>
		<?php endif; ?>

		<?php endif; ?>
	</form>
</div>