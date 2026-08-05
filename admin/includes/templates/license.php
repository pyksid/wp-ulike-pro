<?php
/**
 * License — WordPress-native admin screen.
 *
 * @package WP_ULike_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', WP_ULIKE_PRO_DOMAIN ) );
}

$data = WP_Ulike_Pro_License::get_license_view_data();
?>

<div
	class="wrap wp-ulike-about wp-ulike-about--license"
	id="wp-ulike-license-screen"
	data-nonce="<?php echo esc_attr( $data['ajax_nonce'] ); ?>"
>

	<h1 class="wp-ulike-about__title">
		<?php esc_html_e( 'License', WP_ULIKE_PRO_DOMAIN ); ?>
		<?php if ( ! empty( $data['pro_version'] ) ) : ?>
			<span class="wp-ulike-about__badge wp-ulike-about__badge--pro"><?php echo esc_html( 'Pro ' . $data['pro_version'] ); ?></span>
		<?php endif; ?>
	</h1>

	<p class="wp-ulike-about__lead" id="wp-ulike-license-lead"><?php echo esc_html( $data['lead_text'] ); ?></p>

	<div id="wp-ulike-license-feedback" class="wp-ulike-license-feedback" role="status" aria-live="polite" hidden></div>

	<?php if ( ! empty( $data['notice'] ) ) : ?>
		<div class="notice notice-<?php echo esc_attr( $data['notice_type'] ?: 'success' ); ?> is-dismissible">
			<p><?php echo esc_html( $data['notice'] ); ?></p>
		</div>
	<?php endif; ?>

	<div class="wp-ulike-about__layout" id="wp-ulike-license-panel">
		<?php echo WP_Ulike_Pro_License::get_panel_html( $data ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in template. ?>
	</div>
</div>

