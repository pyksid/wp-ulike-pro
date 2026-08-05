<?php
/**
 * Statistics page template
 *
 * @package    wp-ulike-pro
 * @author     TechnoWich 2026
 * @link       https://wpulike.com
 */

	// no direct access allowed
	if ( ! defined('ABSPATH') ) {
	    die();
	}

	if( ! WP_Ulike_Pro_API::has_permission() ) {
?>
<div class="wrap wp-ulike-container">
	<div class="wp-ulike-pro-license-empty-state">
		<div class="wp-ulike-pro-license-empty-state-content">
			<div class="wp-ulike-pro-license-empty-icon" aria-hidden="true">
				<span class="dashicons dashicons-privacy"></span>
			</div>

			<h1 class="wp-ulike-pro-license-empty-title">
				<?php esc_html_e( 'License Not Found!', WP_ULIKE_PRO_DOMAIN ); ?>
			</h1>

			<p class="wp-ulike-pro-license-empty-message">
				<?php esc_html_e( 'The license you provided is invalid or could not be found. Please verify your license key or purchase a new license to continue using the pro features.', WP_ULIKE_PRO_DOMAIN ); ?>
			</p>

			<div class="wp-ulike-pro-license-empty-actions">
				<a href="<?php echo esc_url( self_admin_url( 'admin.php?page=wp-ulike-pro-license' ) ); ?>" class="button button-primary">
					<span class="dashicons dashicons-admin-network" aria-hidden="true"></span>
					<?php esc_html_e( 'Activate License', WP_ULIKE_PRO_DOMAIN ); ?>
				</a>
				<a href="<?php echo esc_url( WP_Ulike_Pro_API::get_pricing_url( 'get-license', 'statistics' ) ); ?>" target="_blank" rel="noopener noreferrer" class="button button-secondary">
					<span class="dashicons dashicons-cart" aria-hidden="true"></span>
					<?php esc_html_e( 'Get License', WP_ULIKE_PRO_DOMAIN ); ?>
				</a>
			</div>

			<p class="wp-ulike-pro-license-empty-help">
				<?php esc_html_e( 'If you believe this is an error, please contact support or try refreshing the page.', WP_ULIKE_PRO_DOMAIN ); ?>
			</p>
		</div>
	</div>
</div>
<?php
		exit;
	}

?>
<noscript>You need to enable JavaScript to run this app.</noscript>
<div id="root"></div>

