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

	// Check permissions
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', WP_ULIKE_PRO_DOMAIN ) );
	}

	$license_key = WP_Ulike_Pro_License::get_license_key();

	// Check if user wants to refresh license data
	$force_refresh = isset( $_GET['refresh'] ) && '1' === $_GET['refresh'];

	// Show success/error messages
	$notice = '';
	$notice_type = '';
	if ( isset( $_GET['activated'] ) && '1' === $_GET['activated'] ) {
		$notice = esc_html__( 'Your license has been activated successfully!', WP_ULIKE_PRO_DOMAIN );
		$notice_type = 'success';
	} elseif ( isset( $_GET['deactivated'] ) && '1' === $_GET['deactivated'] ) {
		$notice = esc_html__( 'Your license has been deactivated successfully.', WP_ULIKE_PRO_DOMAIN );
		$notice_type = 'info';
	}
?>

<div class="wrap wp-ulike-pro-admin-page-license">
	<div class="wp-ulike-pro-license-hero">
		<h1><?php esc_html_e( 'License Management', WP_ULIKE_PRO_DOMAIN ); ?></h1>
		<p class="wp-ulike-pro-license-subtitle">
			<?php esc_html_e( 'Manage your WP ULike Pro license to unlock premium features and support', WP_ULIKE_PRO_DOMAIN ); ?>
		</p>
	</div>

	<?php if ( $notice ) : ?>
		<div class="wp-ulike-pro-notice-toast wp-ulike-pro-notice-<?php echo esc_attr( $notice_type ); ?>">
			<span class="wp-ulike-pro-notice-icon">
				<?php if ( 'success' === $notice_type ) : ?>
					<span class="dashicons dashicons-yes-alt"></span>
				<?php else : ?>
					<span class="dashicons dashicons-info"></span>
				<?php endif; ?>
			</span>
			<span class="wp-ulike-pro-notice-message"><?php echo esc_html( $notice ); ?></span>
		</div>
	<?php endif; ?>

	<div class="wp-ulike-pro-license-container">
		<?php if ( empty( $license_key ) ) : ?>
			<!-- Activation Form -->
			<div class="wp-ulike-pro-license-card wp-ulike-pro-card-activate">
				<div class="wp-ulike-pro-card-header">
					<div class="wp-ulike-pro-card-icon">
						<span class="dashicons dashicons-admin-network"></span>
					</div>
					<div class="wp-ulike-pro-card-title">
						<h2><?php esc_html_e( 'Activate License', WP_ULIKE_PRO_DOMAIN ); ?></h2>
						<p><?php esc_html_e( 'Unlock all premium features with your license key', WP_ULIKE_PRO_DOMAIN ); ?></p>
					</div>
				</div>

				<div class="wp-ulike-pro-license-quick-links">
					<a href="https://wpulike.com/user/?utm_source=license-page&utm_campaign=get-license&utm_medium=wp-dash"
						target="_blank"
						rel="noopener noreferrer"
						class="wp-ulike-pro-quick-link">
						<span class="dashicons dashicons-admin-users"></span>
						<span><?php esc_html_e( 'Get License Key', WP_ULIKE_PRO_DOMAIN ); ?></span>
					</a>
					<a href="https://wpulike.com/pricing/?utm_source=license-page&utm_campaign=gopro&utm_medium=wp-dash"
						target="_blank"
						rel="noopener noreferrer"
						class="wp-ulike-pro-quick-link">
						<span class="dashicons dashicons-cart"></span>
						<span><?php esc_html_e( 'Purchase License', WP_ULIKE_PRO_DOMAIN ); ?></span>
					</a>
				</div>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="wp-ulike-pro-activation-form">
					<?php wp_nonce_field( 'wp-ulike-pro-license' ); ?>
					<input type="hidden" name="action" value="wp_ulike_pro_activate_license"/>

					<div class="wp-ulike-pro-input-wrapper">
						<label for="wp-ulike-pro-license-key">
							<?php esc_html_e( 'License Key', WP_ULIKE_PRO_DOMAIN ); ?>
						</label>
						<div class="wp-ulike-pro-input-group">
							<span class="wp-ulike-pro-input-icon">
								<span class="dashicons dashicons-admin-network"></span>
							</span>
							<input
								id="wp-ulike-pro-license-key"
								class="wp-ulike-pro-license-input"
								name="wp_ulike_pro_license_key"
								type="text"
								value=""
								placeholder="<?php esc_attr_e( 'Paste your license key here', WP_ULIKE_PRO_DOMAIN ); ?>"
								autocomplete="off"
								required
							/>
						</div>
						<p class="wp-ulike-pro-input-hint">
							<span class="dashicons dashicons-info-outline"></span>
							<?php printf(
								esc_html__( 'Format: %s', WP_ULIKE_PRO_DOMAIN ),
								'<code>fb351f05958872E193feb37a505a84be</code>'
							); ?>
						</p>
					</div>

					<button type="submit" class="wp-ulike-pro-btn wp-ulike-pro-btn-primary wp-ulike-pro-btn-large">
						<span class="wp-ulike-pro-btn-icon">
							<span class="dashicons dashicons-yes-alt"></span>
						</span>
						<span><?php esc_html_e( 'Activate License', WP_ULIKE_PRO_DOMAIN ); ?></span>
					</button>
				</form>
			</div>

		<?php else : ?>
			<!-- License Status -->
			<?php
			// Clear cache and lock if refresh is requested
			if ( $force_refresh ) {
				delete_option( 'wp_ulike_pro_license_data' );
				WP_Ulike_Pro_API::clear_request_lock( 'get_license_data' );
			}

			// Use cached data by default (no server request unless refresh requested)
			$license_data = WP_Ulike_Pro_API::get_license_data( $force_refresh );

			// Ensure license_data is an array
			if ( ! is_array( $license_data ) ) {
				$license_data = [
					'license' => WP_Ulike_Pro_API::STATUS_HTTP_ERROR,
					'success' => false,
				];
			}

			$is_valid = WP_Ulike_Pro_API::STATUS_VALID === $license_data['license'];
			$is_expired = WP_Ulike_Pro_API::STATUS_EXPIRED === $license_data['license'];
			$status_class = $is_valid ? 'valid' : ( $is_expired ? 'expired' : 'invalid' );
			?>

			<!-- Status Card -->
			<div class="wp-ulike-pro-license-card wp-ulike-pro-card-status wp-ulike-pro-status-<?php echo esc_attr( $status_class ); ?>">
				<div class="wp-ulike-pro-status-header-wrapper">
					<div class="wp-ulike-pro-status-badge">
						<?php if ( $is_valid ) : ?>
							<span class="wp-ulike-pro-badge-icon wp-ulike-pro-badge-success">
								<span class="dashicons dashicons-yes-alt"></span>
							</span>
						<?php elseif ( $is_expired ) : ?>
							<span class="wp-ulike-pro-badge-icon wp-ulike-pro-badge-warning">
								<span class="dashicons dashicons-warning"></span>
							</span>
						<?php else : ?>
							<span class="wp-ulike-pro-badge-icon wp-ulike-pro-badge-error">
								<span class="dashicons dashicons-dismiss"></span>
							</span>
						<?php endif; ?>
					</div>
					<?php if ( $is_valid ) : ?>
						<span class="wp-ulike-pro-status-pill wp-ulike-pro-status-pill-success">
							<span class="dashicons dashicons-yes-alt"></span>
							<?php esc_html_e( 'Active', WP_ULIKE_PRO_DOMAIN ); ?>
						</span>
					<?php elseif ( $is_expired ) : ?>
						<span class="wp-ulike-pro-status-pill wp-ulike-pro-status-pill-expired">
							<span class="dashicons dashicons-warning"></span>
							<?php esc_html_e( 'Expired', WP_ULIKE_PRO_DOMAIN ); ?>
						</span>
					<?php else : ?>
						<span class="wp-ulike-pro-status-pill wp-ulike-pro-status-pill-invalid">
							<span class="dashicons dashicons-dismiss"></span>
							<?php esc_html_e( 'Invalid', WP_ULIKE_PRO_DOMAIN ); ?>
						</span>
					<?php endif; ?>
				</div>

				<div class="wp-ulike-pro-status-main">
					<div class="wp-ulike-pro-status-header">
						<h2>
							<?php WP_Ulike_Pro_License::render_part_license_status_header( $license_data ); ?>
						</h2>
						<a href="<?php echo esc_url( add_query_arg( 'refresh', '1' ) ); ?>"
							class="wp-ulike-pro-refresh-btn"
							title="<?php esc_attr_e( 'Refresh license status', WP_ULIKE_PRO_DOMAIN ); ?>">
							<span class="dashicons dashicons-update"></span>
						</a>
					</div>

					<?php if ( $is_valid && isset( $license_data['expires'] ) && 'lifetime' !== $license_data['expires'] ) : ?>
						<?php
						$expires_date = strtotime( $license_data['expires'] );
						$expires_human = human_time_diff( current_time( 'timestamp' ), $expires_date );
						$days_remaining = ( $expires_date - current_time( 'timestamp' ) ) / DAY_IN_SECONDS;
						$total_days = 365; // Assuming 1 year license
						$progress_percent = min( 100, max( 0, ( $days_remaining / $total_days ) * 100 ) );
						$progress_color = $days_remaining > 60 ? '#28a745' : ( $days_remaining > 30 ? '#ffc107' : '#dc3232' );
						?>
						<div class="wp-ulike-pro-expiry-info">
							<div class="wp-ulike-pro-expiry-header">
								<div class="wp-ulike-pro-expiry-label">
									<span class="dashicons dashicons-calendar-alt"></span>
									<?php esc_html_e( 'Expires in', WP_ULIKE_PRO_DOMAIN ); ?>
								</div>
								<div class="wp-ulike-pro-expiry-value">
									<?php if ( $expires_date > current_time( 'timestamp' ) ) : ?>
										<strong><?php echo esc_html( $expires_human ); ?></strong>
										<?php if ( $days_remaining <= 30 ) : ?>
											<span class="wp-ulike-pro-expiry-badge wp-ulike-pro-expiry-urgent">
												<?php esc_html_e( 'Renew Soon', WP_ULIKE_PRO_DOMAIN ); ?>
											</span>
										<?php elseif ( $days_remaining <= 60 ) : ?>
											<span class="wp-ulike-pro-expiry-badge wp-ulike-pro-expiry-warning">
												<?php esc_html_e( 'Expiring', WP_ULIKE_PRO_DOMAIN ); ?>
											</span>
										<?php endif; ?>
									<?php else : ?>
										<strong class="wp-ulike-pro-expired"><?php esc_html_e( 'Expired', WP_ULIKE_PRO_DOMAIN ); ?></strong>
									<?php endif; ?>
								</div>
							</div>
							<?php if ( $expires_date > current_time( 'timestamp' ) ) : ?>
								<div class="wp-ulike-pro-expiry-progress">
									<div class="wp-ulike-pro-progress-bar">
										<div class="wp-ulike-pro-progress-fill" style="width: <?php echo esc_attr( $progress_percent ); ?>%; background-color: <?php echo esc_attr( $progress_color ); ?>;"></div>
									</div>
									<div class="wp-ulike-pro-progress-text">
										<span><?php printf( esc_html__( '%d days remaining', WP_ULIKE_PRO_DOMAIN ), max( 0, (int) $days_remaining ) ); ?></span>
										<span><?php echo esc_html( wp_date( get_option( 'date_format' ), $expires_date ) ); ?></span>
									</div>
								</div>
							<?php endif; ?>
						</div>
					<?php elseif ( $is_valid && isset( $license_data['expires'] ) && 'lifetime' === $license_data['expires'] ) : ?>
						<div class="wp-ulike-pro-expiry-info wp-ulike-pro-expiry-info-simple">
							<div class="wp-ulike-pro-expiry-label">
								<span class="dashicons dashicons-awards"></span>
								<span><?php esc_html_e( 'License Type', WP_ULIKE_PRO_DOMAIN ); ?></span>
							</div>
							<div class="wp-ulike-pro-expiry-value">
								<strong class="wp-ulike-pro-lifetime"><?php esc_html_e( 'Lifetime', WP_ULIKE_PRO_DOMAIN ); ?></strong>
							</div>
						</div>
					<?php endif; ?>
				</div>
			</div>

			<!-- License Details Card -->
			<div class="wp-ulike-pro-license-card wp-ulike-pro-card-details">
				<div class="wp-ulike-pro-card-header">
					<h3>
						<span class="dashicons dashicons-info-outline"></span>
						<?php esc_html_e( 'License Information', WP_ULIKE_PRO_DOMAIN ); ?>
					</h3>
				</div>

				<div class="wp-ulike-pro-license-info-grid">
					<div class="wp-ulike-pro-info-item">
						<label><?php esc_html_e( 'License Key', WP_ULIKE_PRO_DOMAIN ); ?></label>
						<code class="wp-ulike-pro-license-key-display"><?php echo esc_html( WP_Ulike_Pro_License::get_hidden_license_key() ); ?></code>
					</div>

					<?php if ( ! empty( $license_data['payment_id'] ) && '0' !== $license_data['payment_id'] ) : ?>
						<div class="wp-ulike-pro-info-item">
							<label><?php esc_html_e( 'Payment ID', WP_ULIKE_PRO_DOMAIN ); ?></label>
							<span class="wp-ulike-pro-info-value">#<?php echo esc_html( $license_data['payment_id'] ); ?></span>
						</div>
					<?php endif; ?>

					<?php if ( isset( $license_data['license_limit'] ) && '0' !== $license_data['license_limit'] ) : ?>
						<div class="wp-ulike-pro-info-item">
							<label><?php esc_html_e( 'License Limit', WP_ULIKE_PRO_DOMAIN ); ?></label>
							<span class="wp-ulike-pro-info-value">
								<?php
								$site_count = isset( $license_data['site_count'] ) ? (int) $license_data['site_count'] : 0;
								$license_limit = (int) $license_data['license_limit'];
								$activations_left = isset( $license_data['activations_left'] ) ? (int) $license_data['activations_left'] : ( $license_limit - $site_count );
								printf(
									esc_html__( '%d of %d sites', WP_ULIKE_PRO_DOMAIN ),
									$site_count,
									$license_limit
								);
								?>
								<?php if ( $activations_left > 0 ) : ?>
									<span class="wp-ulike-pro-info-badge"><?php printf( esc_html__( '%d left', WP_ULIKE_PRO_DOMAIN ), $activations_left ); ?></span>
								<?php else : ?>
									<span class="wp-ulike-pro-info-badge wp-ulike-pro-info-badge-warning"><?php esc_html_e( 'No activations left', WP_ULIKE_PRO_DOMAIN ); ?></span>
								<?php endif; ?>
							</span>
						</div>
					<?php endif; ?>
				</div>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="wp-ulike-pro-deactivate-form">
					<?php wp_nonce_field( 'wp-ulike-pro-license' ); ?>
					<input type="hidden" name="action" value="wp_ulike_pro_deactivate_license"/>
					<button type="submit"
						class="wp-ulike-pro-btn wp-ulike-pro-btn-secondary"
						onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to deactivate this license?', WP_ULIKE_PRO_DOMAIN ) ); ?>');">
						<span class="dashicons dashicons-dismiss"></span>
						<?php esc_html_e( 'Deactivate License', WP_ULIKE_PRO_DOMAIN ); ?>
					</button>
				</form>
			</div>

			<!-- Benefits Card (only for valid licenses) -->
			<?php if ( $is_valid ) : ?>
				<div class="wp-ulike-pro-license-card wp-ulike-pro-card-benefits">
					<div class="wp-ulike-pro-card-header">
						<h3>
							<span class="dashicons dashicons-star-filled"></span>
							<?php esc_html_e( 'Premium Benefits', WP_ULIKE_PRO_DOMAIN ); ?>
						</h3>
					</div>
					<div class="wp-ulike-pro-benefits-grid">
						<div class="wp-ulike-pro-benefit-item">
							<span class="wp-ulike-pro-benefit-icon">
								<span class="dashicons dashicons-update"></span>
							</span>
							<strong><?php esc_html_e( 'Automatic Updates', WP_ULIKE_PRO_DOMAIN ); ?></strong>
							<p><?php esc_html_e( 'Get the latest features and security updates automatically', WP_ULIKE_PRO_DOMAIN ); ?></p>
						</div>
						<div class="wp-ulike-pro-benefit-item">
							<span class="wp-ulike-pro-benefit-icon">
								<span class="dashicons dashicons-sos"></span>
							</span>
							<strong><?php esc_html_e( 'Premium Support', WP_ULIKE_PRO_DOMAIN ); ?></strong>
							<p><?php esc_html_e( 'Priority support from our expert team', WP_ULIKE_PRO_DOMAIN ); ?></p>
						</div>
						<div class="wp-ulike-pro-benefit-item">
							<span class="wp-ulike-pro-benefit-icon">
								<span class="dashicons dashicons-admin-plugins"></span>
							</span>
							<strong><?php esc_html_e( 'All Features', WP_ULIKE_PRO_DOMAIN ); ?></strong>
							<p><?php esc_html_e( 'Access to all premium features and modules', WP_ULIKE_PRO_DOMAIN ); ?></p>
						</div>
					</div>
				</div>
			<?php endif; ?>

			<!-- Alerts -->
			<?php if ( $is_expired ) : ?>
				<?php
				// Build renewal URL with license key
				$renew_url = add_query_arg( array(
					'edd_license_key' => $license_key,
					'download_id'     => WP_Ulike_Pro_API::PRODUCT_ID,
					'edd_action'      => 'renew'
				), WP_Ulike_Pro_API::RENEW_URL );
				?>
				<div class="wp-ulike-pro-license-card wp-ulike-pro-card-alert wp-ulike-pro-alert-expired">
					<div class="wp-ulike-pro-alert-icon">
						<span class="dashicons dashicons-warning"></span>
					</div>
					<div class="wp-ulike-pro-alert-content">
						<div class="wp-ulike-pro-alert-badge">
							<span class="wp-ulike-pro-discount-badge">
								<span class="wp-ulike-pro-discount-percent">30%</span>
								<span class="wp-ulike-pro-discount-text"><?php esc_html_e( 'OFF', WP_ULIKE_PRO_DOMAIN ); ?></span>
							</span>
							<span class="wp-ulike-pro-limited-time">
								<span class="dashicons dashicons-clock"></span>
								<span><?php esc_html_e( 'Limited Time', WP_ULIKE_PRO_DOMAIN ); ?></span>
							</span>
						</div>
						<h3><?php esc_html_e( 'Your License Has Expired', WP_ULIKE_PRO_DOMAIN ); ?></h3>
						<p class="wp-ulike-pro-alert-description"><?php esc_html_e( 'Renew now to continue receiving automatic updates, premium support, and access to all pro features. Special 30% discount available for a limited time!', WP_ULIKE_PRO_DOMAIN ); ?></p>
						<div class="wp-ulike-pro-alert-features">
							<div class="wp-ulike-pro-feature-item">
								<span class="wp-ulike-pro-feature-check">
									<span class="dashicons dashicons-yes-alt"></span>
								</span>
								<div class="wp-ulike-pro-feature-content">
									<strong><?php esc_html_e( 'Automatic Updates', WP_ULIKE_PRO_DOMAIN ); ?></strong>
									<span><?php esc_html_e( 'Latest features & security patches', WP_ULIKE_PRO_DOMAIN ); ?></span>
								</div>
							</div>
							<div class="wp-ulike-pro-feature-item">
								<span class="wp-ulike-pro-feature-check">
									<span class="dashicons dashicons-yes-alt"></span>
								</span>
								<div class="wp-ulike-pro-feature-content">
									<strong><?php esc_html_e( 'Premium Support', WP_ULIKE_PRO_DOMAIN ); ?></strong>
									<span><?php esc_html_e( 'Priority help from experts', WP_ULIKE_PRO_DOMAIN ); ?></span>
								</div>
							</div>
							<div class="wp-ulike-pro-feature-item">
								<span class="wp-ulike-pro-feature-check">
									<span class="dashicons dashicons-yes-alt"></span>
								</span>
								<div class="wp-ulike-pro-feature-content">
									<strong><?php esc_html_e( 'All Features', WP_ULIKE_PRO_DOMAIN ); ?></strong>
									<span><?php esc_html_e( 'Unlock everything', WP_ULIKE_PRO_DOMAIN ); ?></span>
								</div>
							</div>
						</div>
						<div class="wp-ulike-pro-renewal-cta">
							<a href="<?php echo esc_url( $renew_url ); ?>"
								target="_blank"
								rel="noopener noreferrer"
								class="wp-ulike-pro-btn wp-ulike-pro-btn-renew">
								<span class="wp-ulike-pro-btn-icon">
									<span class="dashicons dashicons-update"></span>
								</span>
								<span class="wp-ulike-pro-btn-text">
									<strong><?php esc_html_e( 'Renew License', WP_ULIKE_PRO_DOMAIN ); ?></strong>
									<small><?php esc_html_e( 'Get 30% discount', WP_ULIKE_PRO_DOMAIN ); ?></small>
								</span>
							</a>
							<p class="wp-ulike-pro-renewal-note">
								<span class="dashicons dashicons-info-outline"></span>
								<?php esc_html_e( 'Renewal is quick and secure. Your license will be activated immediately.', WP_ULIKE_PRO_DOMAIN ); ?>
							</p>
						</div>
					</div>
				</div>
			<?php endif; ?>

			<?php if ( WP_Ulike_Pro_API::STATUS_SITE_INACTIVE === $license_data['license'] || WP_Ulike_Pro_API::STATUS_INVALID === $license_data['license'] ) : ?>
				<div class="wp-ulike-pro-license-card wp-ulike-pro-card-alert wp-ulike-pro-alert-danger">
					<div class="wp-ulike-pro-alert-icon">
						<span class="dashicons dashicons-admin-site"></span>
					</div>
					<div class="wp-ulike-pro-alert-content">
						<h3><?php esc_html_e( 'Domain Mismatch', WP_ULIKE_PRO_DOMAIN ); ?></h3>
						<p><?php esc_html_e( 'Your license key doesn\'t match your current domain. This usually happens after changing your site URL or migrating to HTTPS.', WP_ULIKE_PRO_DOMAIN ); ?></p>
						<div class="wp-ulike-pro-alert-actions">
							<p>
								<strong><?php esc_html_e( 'Solution:', WP_ULIKE_PRO_DOMAIN ); ?></strong>
								<?php esc_html_e( 'Click the button below to automatically reactivate your license for this domain.', WP_ULIKE_PRO_DOMAIN ); ?>
							</p>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top: 15px;">
								<?php wp_nonce_field( 'wp-ulike-pro-license' ); ?>
								<input type="hidden" name="action" value="wp_ulike_pro_activate_license"/>
								<input type="hidden" name="wp_ulike_pro_license_key" value="<?php echo esc_attr( $license_key ); ?>"/>
								<button type="submit" class="wp-ulike-pro-btn wp-ulike-pro-btn-primary">
									<span class="wp-ulike-pro-btn-icon">
										<span class="dashicons dashicons-update"></span>
									</span>
									<span><?php esc_html_e( 'Reactivate License', WP_ULIKE_PRO_DOMAIN ); ?></span>
								</button>
							</form>
						</div>
					</div>
				</div>
			<?php endif; ?>
		<?php endif; ?>
	</div>
</div>

<script>
(function($) {
	'use strict';

	// Auto-hide toast notifications
	setTimeout(function() {
		$('.wp-ulike-pro-notice-toast').fadeOut(300, function() {
			$(this).remove();
		});
	}, 5000);

	// Smooth scroll to top on refresh
	if (window.location.search.indexOf('refresh=1') !== -1) {
		$('html, body').animate({
			scrollTop: $('.wp-ulike-pro-license-container').offset().top - 20
		}, 500);
	}
})(jQuery);
</script>