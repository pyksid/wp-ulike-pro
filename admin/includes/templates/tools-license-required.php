<?php
/**
 * License gate for Pro Tools tabs.
 *
 * @package WP_ULike_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

$license_message = ! empty( $license_message )
	? $license_message
	: esc_html__( 'You need an active license to use this tool. Open the License page to activate or refresh your key.', WP_ULIKE_PRO_DOMAIN );

$license_url = class_exists( 'WP_Ulike_Pro_License' )
	? WP_Ulike_Pro_License::get_url()
	: admin_url( 'admin.php?page=wp-ulike-pro-license' );
?>

<div class="wp-ulike-about-card wp-ulike-about-card--pro">
	<h2 class="wp-ulike-about-card__title"><?php esc_html_e( 'License required', WP_ULIKE_PRO_DOMAIN ); ?></h2>
	<p class="wp-ulike-about-summary"><?php echo esc_html( $license_message ); ?></p>
	<p class="wp-ulike-about-tools">
		<a class="button button-primary" href="<?php echo esc_url( $license_url ); ?>">
			<?php esc_html_e( 'Go to License', WP_ULIKE_PRO_DOMAIN ); ?>
		</a>
	</p>
</div>

