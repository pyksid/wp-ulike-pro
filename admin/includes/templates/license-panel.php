<?php
/**
 * License page panel (main + sidebar) — used for full render and AJAX refresh.
 *
 * @package WP_ULike_Pro
 * @var array $data View data from WP_Ulike_Pro_License::get_license_view_data().
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

$next_step = $data['next_step'] ?? array();
?>

<div class="wp-ulike-about__main">

	<?php if ( ! empty( $next_step['message'] ) ) : ?>
		<div class="wp-ulike-license-next wp-ulike-license-next--<?php echo esc_attr( $next_step['type'] ?? 'neutral' ); ?>">
			<h2 class="wp-ulike-license-next__title"><?php echo esc_html( $next_step['title'] ?? '' ); ?></h2>
			<p class="wp-ulike-license-next__message"><?php echo esc_html( $next_step['message'] ); ?></p>
		</div>
	<?php endif; ?>

	<?php if ( empty( $data['has_license'] ) ) : ?>

		<div class="wp-ulike-about-card">
			<h2 class="wp-ulike-about-card__title"><?php esc_html_e( 'How activation works', WP_ULIKE_PRO_DOMAIN ); ?></h2>
			<ol class="wp-ulike-license-steps">
				<li>
					<strong><?php esc_html_e( 'Open your account', WP_ULIKE_PRO_DOMAIN ); ?></strong>
					<span><?php esc_html_e( 'Log in at wpulike.com → Licenses and copy your key.', WP_ULIKE_PRO_DOMAIN ); ?></span>
				</li>
				<li>
					<strong><?php esc_html_e( 'Paste the key here', WP_ULIKE_PRO_DOMAIN ); ?></strong>
					<span><?php esc_html_e( 'One website = one activation. Moving sites? Deactivate the old site first.', WP_ULIKE_PRO_DOMAIN ); ?></span>
				</li>
				<li>
					<strong><?php esc_html_e( 'Click Activate', WP_ULIKE_PRO_DOMAIN ); ?></strong>
					<span><?php esc_html_e( 'Pro features and updates turn on right away—no extra steps.', WP_ULIKE_PRO_DOMAIN ); ?></span>
				</li>
			</ol>
		</div>

		<div class="wp-ulike-about-card wp-ulike-about-card--pro">
			<h2 class="wp-ulike-about-card__title"><?php esc_html_e( 'Activate license', WP_ULIKE_PRO_DOMAIN ); ?></h2>
			<form
				class="wp-ulike-license-form wp-ulike-license-ajax-form"
				method="post"
				action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
				data-license-action="activate"
			>
				<?php wp_nonce_field( 'wp-ulike-pro-license' ); ?>
				<input type="hidden" name="action" value="wp_ulike_pro_activate_license" />
				<p class="wp-ulike-license-form__field">
					<label for="wp-ulike-pro-license-key"><?php esc_html_e( 'License key', WP_ULIKE_PRO_DOMAIN ); ?></label>
					<input
						id="wp-ulike-pro-license-key"
						class="regular-text code"
						name="wp_ulike_pro_license_key"
						type="text"
						value=""
						placeholder="<?php esc_attr_e( 'Paste your license key here', WP_ULIKE_PRO_DOMAIN ); ?>"
						autocomplete="off"
						spellcheck="false"
						required
					/>
					<span class="description"><?php esc_html_e( 'Long code from your account (letters and numbers). No spaces needed.', WP_ULIKE_PRO_DOMAIN ); ?></span>
				</p>
				<p class="wp-ulike-about-tools">
					<button type="submit" class="button button-primary wp-ulike-license-submit">
						<?php esc_html_e( 'Activate license', WP_ULIKE_PRO_DOMAIN ); ?>
					</button>
				</p>
			</form>
		</div>

	<?php else : ?>

		<?php
		$license_data = $data['license_data'];
		$is_valid     = ! empty( $data['is_valid'] );
		$is_expired   = ! empty( $data['is_expired'] );
		?>

		<div class="wp-ulike-about-card">
			<div class="wp-ulike-about-card__header">
				<h2 class="wp-ulike-about-card__title"><?php esc_html_e( 'License status', WP_ULIKE_PRO_DOMAIN ); ?></h2>
				<button type="button" class="wp-ulike-about-card__link button-link wp-ulike-license-refresh">
					<?php esc_html_e( 'Refresh status', WP_ULIKE_PRO_DOMAIN ); ?>
				</button>
			</div>
			<div class="wp-ulike-about-status" role="list">
				<div class="wp-ulike-about-status__item wp-ulike-about-status__item--<?php echo esc_attr( $data['status_state'] ); ?>" role="listitem">
					<span class="wp-ulike-about-status__label"><?php esc_html_e( 'Status', WP_ULIKE_PRO_DOMAIN ); ?></span>
					<span class="wp-ulike-about-status__value"><?php echo esc_html( $data['status_label'] ); ?></span>
				</div>
				<?php if ( ! empty( $data['expires_label'] ) ) : ?>
					<div class="wp-ulike-about-status__item wp-ulike-about-status__item--<?php echo esc_attr( $is_expired ? 'bad' : ( $is_valid ? 'good' : 'neutral' ) ); ?>" role="listitem">
						<span class="wp-ulike-about-status__label"><?php esc_html_e( 'Expires', WP_ULIKE_PRO_DOMAIN ); ?></span>
						<span class="wp-ulike-about-status__value"><?php echo esc_html( $data['expires_label'] ); ?></span>
						<?php if ( ! empty( $data['expires_hint'] ) ) : ?>
							<span class="wp-ulike-about-status__hint"><?php echo esc_html( $data['expires_hint'] ); ?></span>
						<?php endif; ?>
					</div>
				<?php endif; ?>
				<?php if ( ! empty( $data['sites_label'] ) ) : ?>
					<div class="wp-ulike-about-status__item wp-ulike-about-status__item--<?php echo esc_attr( ( isset( $data['activations_left'] ) && is_int( $data['activations_left'] ) && $data['activations_left'] < 1 ) ? 'bad' : 'neutral' ); ?>" role="listitem">
						<span class="wp-ulike-about-status__label"><?php esc_html_e( 'Sites', WP_ULIKE_PRO_DOMAIN ); ?></span>
						<span class="wp-ulike-about-status__value"><?php echo esc_html( $data['sites_label'] ); ?></span>
						<?php if ( isset( $data['activations_left'] ) && is_int( $data['activations_left'] ) ) : ?>
							<span class="wp-ulike-about-status__hint">
								<?php
								printf(
									esc_html( _n( '%d activation left', '%d activations left', $data['activations_left'], WP_ULIKE_PRO_DOMAIN ) ),
									$data['activations_left']
								);
								?>
							</span>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>
		</div>

		<?php if ( $is_expired && ! empty( $data['renew_url'] ) ) : ?>
			<div class="wp-ulike-about-card wp-ulike-about-card--pro wp-ulike-license-action-card">
				<h2 class="wp-ulike-about-card__title"><?php esc_html_e( 'Renew your license', WP_ULIKE_PRO_DOMAIN ); ?></h2>
				<p class="wp-ulike-about-card__hint wp-ulike-about-card__hint--warn">
					<?php esc_html_e( 'Your license has expired. Renew to restore automatic updates, support, and Pro features.', WP_ULIKE_PRO_DOMAIN ); ?>
				</p>
				<div class="wp-ulike-license-action-card__actions">
					<a class="button button-primary" href="<?php echo esc_url( $data['renew_url'] ); ?>" target="_blank" rel="noopener noreferrer">
						<?php esc_html_e( 'Renew license', WP_ULIKE_PRO_DOMAIN ); ?>
					</a>
					<span class="wp-ulike-license-action-card__note"><?php esc_html_e( 'Opens wpulike.com in a new tab', WP_ULIKE_PRO_DOMAIN ); ?></span>
				</div>
			</div>
		<?php elseif ( ! empty( $data['needs_reactivate'] ) ) : ?>
			<div class="wp-ulike-about-card wp-ulike-about-card--pro wp-ulike-license-action-card">
				<h2 class="wp-ulike-about-card__title"><?php esc_html_e( 'Fix domain mismatch', WP_ULIKE_PRO_DOMAIN ); ?></h2>
				<p class="wp-ulike-about-transfer__intro">
					<?php esc_html_e( 'Your key is fine—the website address changed (new domain, HTTPS, or staging URL). Reactivate once for this address.', WP_ULIKE_PRO_DOMAIN ); ?>
				</p>
				<form class="wp-ulike-about-tools wp-ulike-license-ajax-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-license-action="reactivate">
					<?php wp_nonce_field( 'wp-ulike-pro-license' ); ?>
					<input type="hidden" name="action" value="wp_ulike_pro_activate_license" />
					<input type="hidden" name="wp_ulike_pro_license_key" value="<?php echo esc_attr( $data['license_key'] ); ?>" />
					<button type="submit" class="button button-primary wp-ulike-license-submit">
						<?php esc_html_e( 'Reactivate for this site', WP_ULIKE_PRO_DOMAIN ); ?>
					</button>
				</form>
			</div>
		<?php elseif ( ! empty( $data['is_cancelled'] ) ) : ?>
			<div class="wp-ulike-about-card wp-ulike-about-card--pro wp-ulike-license-action-card">
				<h2 class="wp-ulike-about-card__title"><?php esc_html_e( 'License cancelled', WP_ULIKE_PRO_DOMAIN ); ?></h2>
				<p class="wp-ulike-about-transfer__intro">
					<?php esc_html_e( 'This key was cancelled (for example after a refund) and cannot be reactivated. Purchase a new license, then enter the new key below.', WP_ULIKE_PRO_DOMAIN ); ?>
				</p>
				<p class="wp-ulike-about-tools">
					<a class="button button-primary" href="<?php echo esc_url( $data['pricing_url'] ); ?>" target="_blank" rel="noopener noreferrer">
						<?php esc_html_e( 'Get a new license', WP_ULIKE_PRO_DOMAIN ); ?>
					</a>
				</p>
				<form class="wp-ulike-license-form wp-ulike-license-ajax-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-license-action="activate">
					<?php wp_nonce_field( 'wp-ulike-pro-license' ); ?>
					<input type="hidden" name="action" value="wp_ulike_pro_activate_license" />
					<p class="wp-ulike-license-form__field">
						<label for="wp-ulike-pro-license-key-replace"><?php esc_html_e( 'New license key', WP_ULIKE_PRO_DOMAIN ); ?></label>
						<input
							id="wp-ulike-pro-license-key-replace"
							class="regular-text code"
							name="wp_ulike_pro_license_key"
							type="text"
							value=""
							placeholder="<?php esc_attr_e( 'Paste your new license key', WP_ULIKE_PRO_DOMAIN ); ?>"
							autocomplete="off"
							spellcheck="false"
							required
						/>
						<span class="description"><?php esc_html_e( 'Optional: deactivate the cancelled key below first, then activate your new key here.', WP_ULIKE_PRO_DOMAIN ); ?></span>
					</p>
					<p class="wp-ulike-about-tools">
						<button type="submit" class="button button-secondary wp-ulike-license-submit">
							<?php esc_html_e( 'Activate new license', WP_ULIKE_PRO_DOMAIN ); ?>
						</button>
					</p>
				</form>
			</div>
		<?php elseif ( ! empty( $data['error_detail'] ) && ! $is_valid ) : ?>
			<div class="wp-ulike-about-card">
				<h2 class="wp-ulike-about-card__title"><?php echo esc_html( $data['error_detail']['title'] ); ?></h2>
				<p class="wp-ulike-about-summary"><?php echo esc_html( $data['error_detail']['description'] ); ?></p>
				<?php if ( ! empty( $data['error_detail']['button_url'] ) ) : ?>
					<?php
					$btn_target = ! empty( $data['error_detail']['button_target'] ) ? $data['error_detail']['button_target'] : '_self';
					$btn_rel    = '_blank' === $btn_target ? 'noopener noreferrer' : '';
					?>
					<p class="wp-ulike-about-tools">
						<a
							class="button button-primary"
							href="<?php echo esc_url( $data['error_detail']['button_url'] ); ?>"
							target="<?php echo esc_attr( $btn_target ); ?>"
							<?php echo $btn_rel ? 'rel="' . esc_attr( $btn_rel ) . '"' : ''; ?>
						>
							<?php echo esc_html( $data['error_detail']['button_text'] ); ?>
						</a>
					</p>
				<?php endif; ?>
			</div>
		<?php elseif ( ! empty( $data['is_expiring_soon'] ) && ! empty( $data['renew_url'] ) ) : ?>
			<?php
			$renewal_card_class = 'wp-ulike-about-card wp-ulike-about-card--pro wp-ulike-license-action-card wp-ulike-license-renewal-card';
			if ( ! empty( $data['license_has_auto_renewal'] ) ) {
				$renewal_card_class .= ' wp-ulike-license-renewal-card--subscription';
			}
			?>
			<div class="<?php echo esc_attr( $renewal_card_class ); ?>">
				<h2 class="wp-ulike-about-card__title">
					<?php
					echo esc_html(
						! empty( $data['license_has_auto_renewal'] )
							? __( 'Expiry date coming up', WP_ULIKE_PRO_DOMAIN )
							: __( 'Renew before it expires', WP_ULIKE_PRO_DOMAIN )
					);
					?>
				</h2>
				<?php if ( ! empty( $data['expires_label'] ) ) : ?>
					<p class="wp-ulike-license-renewal-card__date">
						<?php
						printf(
							/* translators: %s: formatted expiry date */
							esc_html__( 'Expires on %s', WP_ULIKE_PRO_DOMAIN ),
							esc_html( $data['expires_label'] )
						);
						?>
						<?php if ( ! empty( $data['expires_hint'] ) ) : ?>
							<span class="wp-ulike-license-renewal-card__countdown">(<?php echo esc_html( $data['expires_hint'] ); ?>)</span>
						<?php endif; ?>
					</p>
				<?php endif; ?>
				<?php if ( ! empty( $data['license_has_auto_renewal'] ) ) : ?>
					<p class="wp-ulike-about-transfer__intro">
						<?php esc_html_e( 'Your subscription at wpulike.com should renew this license automatically before the date above. No action is needed on this site.', WP_ULIKE_PRO_DOMAIN ); ?>
					</p>
					<p class="wp-ulike-license-renewal-card__fine-print">
						<?php
						echo wp_kses(
							sprintf(
								/* translators: %s: renew URL */
								__( 'Cancelled auto-pay, one-time billing, or renewing early? <a href="%s" target="_blank" rel="noopener noreferrer">Renew manually at wpulike.com</a>.', WP_ULIKE_PRO_DOMAIN ),
								esc_url( $data['renew_url'] )
							),
							array(
								'a' => array(
									'href'   => array(),
									'target' => array(),
									'rel'    => array(),
								),
							)
						);
						?>
					</p>
				<?php else : ?>
					<p class="wp-ulike-about-card__hint wp-ulike-about-card__hint--warn">
						<?php esc_html_e( 'Renew to keep automatic updates, support, and Pro features on this site.', WP_ULIKE_PRO_DOMAIN ); ?>
						<?php if ( ! empty( $data['renewal_discount'] ) ) : ?>
							<?php
							printf(
								' %s',
								sprintf(
									/* translators: %d: renewal discount percent */
									esc_html__( 'A %d%% renewal discount may apply at checkout.', WP_ULIKE_PRO_DOMAIN ),
									(int) $data['renewal_discount']
								)
							);
							?>
						<?php endif; ?>
					</p>
				<?php endif; ?>
				<div class="wp-ulike-license-action-card__actions">
					<?php if ( empty( $data['license_has_auto_renewal'] ) ) : ?>
						<a
							class="button button-primary"
							href="<?php echo esc_url( $data['renew_url'] ); ?>"
							target="_blank"
							rel="noopener noreferrer"
						>
							<?php esc_html_e( 'Renew at wpulike.com', WP_ULIKE_PRO_DOMAIN ); ?>
						</a>
					<?php endif; ?>
					<a
						class="button <?php echo ! empty( $data['license_has_auto_renewal'] ) ? 'button-primary' : 'button-secondary'; ?>"
						href="<?php echo esc_url( $data['account_url'] ); ?>"
						target="_blank"
						rel="noopener noreferrer"
					>
						<?php esc_html_e( 'Manage billing', WP_ULIKE_PRO_DOMAIN ); ?>
					</a>
					<span class="wp-ulike-license-action-card__note"><?php esc_html_e( 'Opens in a new tab', WP_ULIKE_PRO_DOMAIN ); ?></span>
				</div>
			</div>
		<?php endif; ?>

		<details class="wp-ulike-about-card wp-ulike-about-card--details wp-ulike-license-deactivate">
			<summary class="wp-ulike-about-card__title"><?php esc_html_e( 'Moving to another domain?', WP_ULIKE_PRO_DOMAIN ); ?></summary>
			<div class="wp-ulike-about-card__body wp-ulike-license-deactivate__body">
				<p class="wp-ulike-license-deactivate__intro">
					<?php esc_html_e( 'This removes Pro from this website only. Your license key stays valid and you can activate it on another site.', WP_ULIKE_PRO_DOMAIN ); ?>
				</p>
				<div class="wp-ulike-license-deactivate__notice" role="note">
					<?php esc_html_e( 'Not for fixing license errors—use Refresh status or the action cards above instead.', WP_ULIKE_PRO_DOMAIN ); ?>
				</div>
				<div class="wp-ulike-license-deactivate__actions">
					<form
						class="wp-ulike-license-ajax-form"
						method="post"
						action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
						data-license-action="deactivate"
						data-confirm="<?php echo esc_attr__( 'Remove WP ULike Pro from this site only? Pro features will turn off here. Your license key stays valid and you can activate it on another site later.', WP_ULIKE_PRO_DOMAIN ); ?>"
					>
						<?php wp_nonce_field( 'wp-ulike-pro-license' ); ?>
						<input type="hidden" name="action" value="wp_ulike_pro_deactivate_license" />
						<button type="submit" class="button wp-ulike-license-deactivate__submit wp-ulike-license-submit">
							<?php esc_html_e( 'Deactivate on this site only', WP_ULIKE_PRO_DOMAIN ); ?>
						</button>
					</form>
				</div>
			</div>
		</details>

	<?php endif; ?>

	<?php if ( ! empty( $data['support_rows'] ) ) : ?>
		<div class="wp-ulike-about-card wp-ulike-license-support-card">
			<div class="wp-ulike-about-card__header">
				<h2 class="wp-ulike-about-card__title"><?php esc_html_e( 'Details for support', WP_ULIKE_PRO_DOMAIN ); ?></h2>
				<button type="button" class="button button-secondary wp-ulike-license-copy-support">
					<?php esc_html_e( 'Copy for support', WP_ULIKE_PRO_DOMAIN ); ?>
				</button>
			</div>
			<p class="wp-ulike-about-transfer__intro">
				<?php esc_html_e( 'If you contact support, click Copy and paste into your ticket. Summary below; expand for server and connection details. No passwords or full license keys.', WP_ULIKE_PRO_DOMAIN ); ?>
			</p>
			<?php
			$support_summary   = $data['support_rows_summary'] ?? array();
			$support_technical = $data['support_rows_technical'] ?? array();
			?>
			<?php if ( ! empty( $support_summary ) ) : ?>
				<dl class="wp-ulike-license-support-meta">
					<?php foreach ( $support_summary as $row ) : ?>
						<div>
							<dt><?php echo esc_html( $row['label'] ); ?></dt>
							<dd>
								<?php if ( ! empty( $row['mono'] ) ) : ?>
									<code class="wp-ulike-license-key"><?php echo esc_html( $row['value'] ); ?></code>
								<?php else : ?>
									<?php echo esc_html( $row['value'] ); ?>
								<?php endif; ?>
							</dd>
						</div>
					<?php endforeach; ?>
				</dl>
			<?php endif; ?>
			<?php if ( ! empty( $support_technical ) ) : ?>
				<details class="wp-ulike-license-support-more">
					<summary class="wp-ulike-license-support-more__summary">
						<?php esc_html_e( 'Technical & connection details', WP_ULIKE_PRO_DOMAIN ); ?>
					</summary>
					<dl class="wp-ulike-license-support-meta wp-ulike-license-support-meta--technical">
						<?php foreach ( $support_technical as $row ) : ?>
							<div>
								<dt><?php echo esc_html( $row['label'] ); ?></dt>
								<dd>
									<?php if ( ! empty( $row['mono'] ) ) : ?>
										<code class="wp-ulike-license-key"><?php echo esc_html( $row['value'] ); ?></code>
									<?php else : ?>
										<?php echo esc_html( $row['value'] ); ?>
									<?php endif; ?>
								</dd>
							</div>
						<?php endforeach; ?>
					</dl>
				</details>
			<?php endif; ?>
			<textarea class="wp-ulike-license-support-export" readonly hidden aria-hidden="true"><?php echo esc_textarea( $data['support_export'] ?? '' ); ?></textarea>
		</div>
	<?php endif; ?>

	<details class="wp-ulike-about-card wp-ulike-about-card--details">
		<summary class="wp-ulike-about-card__title"><?php esc_html_e( 'Common questions', WP_ULIKE_PRO_DOMAIN ); ?></summary>
		<div class="wp-ulike-about-card__body">
			<ul class="wp-ulike-about-troubleshoot__list">
				<li>
					<strong><?php esc_html_e( 'Where is my license key?', WP_ULIKE_PRO_DOMAIN ); ?></strong>
					<?php
					echo wp_kses(
						sprintf(
							/* translators: %s: account URL link */
							__( 'Sign in at <a href="%s" target="_blank" rel="noopener noreferrer">wpulike.com/user</a> → Licenses. Copy the full key for this product.', WP_ULIKE_PRO_DOMAIN ),
							esc_url( $data['account_url'] )
						),
						array(
							'a' => array(
								'href'   => array(),
								'target' => array(),
								'rel'    => array(),
							),
						)
					);
					?>
				</li>
				<li>
					<strong><?php esc_html_e( 'I changed my domain or enabled HTTPS', WP_ULIKE_PRO_DOMAIN ); ?></strong>
					<?php esc_html_e( 'Click Reactivate for this site. You usually do not need to buy a new license.', WP_ULIKE_PRO_DOMAIN ); ?>
				</li>
				<li>
					<strong><?php esc_html_e( 'No activations left', WP_ULIKE_PRO_DOMAIN ); ?></strong>
					<?php esc_html_e( 'Deactivate the license on an old/staging site first, or upgrade your plan for more sites.', WP_ULIKE_PRO_DOMAIN ); ?>
				</li>
				<li>
					<strong><?php esc_html_e( 'Updates not showing', WP_ULIKE_PRO_DOMAIN ); ?></strong>
					<?php esc_html_e( 'Make sure status is Active, then click Refresh status. Visit Dashboard → Updates afterward.', WP_ULIKE_PRO_DOMAIN ); ?>
				</li>
				<li>
					<strong><?php esc_html_e( 'Firewall or security plugin blocking activation', WP_ULIKE_PRO_DOMAIN ); ?></strong>
					<?php esc_html_e( 'Allow outbound HTTPS to wpulike.com (or temporarily disable the firewall). Copy “Details for support” — it lists detected WAF/CDN and security plugins.', WP_ULIKE_PRO_DOMAIN ); ?>
				</li>
				<li>
					<strong><?php esc_html_e( 'I pay monthly or yearly (subscription)', WP_ULIKE_PRO_DOMAIN ); ?></strong>
					<?php esc_html_e( 'If billing is still active at wpulike.com, your license usually renews on its own. You only need “Renew at wpulike.com” if you cancelled billing or bought a one-time license.', WP_ULIKE_PRO_DOMAIN ); ?>
				</li>
				<li>
					<strong><?php esc_html_e( 'Moving this site to another domain', WP_ULIKE_PRO_DOMAIN ); ?></strong>
					<?php esc_html_e( 'Open “Moving to another domain?” above, deactivate here, then activate the same key on the new site.', WP_ULIKE_PRO_DOMAIN ); ?>
				</li>
			</ul>
		</div>
	</details>

</div>

<aside class="wp-ulike-about__aside" aria-label="<?php esc_attr_e( 'License resources', WP_ULIKE_PRO_DOMAIN ); ?>">
	<div class="wp-ulike-about-card">
		<h2 class="wp-ulike-about-card__title"><?php esc_html_e( 'Quick links', WP_ULIKE_PRO_DOMAIN ); ?></h2>
		<ul class="wp-ulike-about-help">
			<li>
				<a href="<?php echo esc_url( $data['account_url'] ); ?>" target="_blank" rel="noopener noreferrer">
					<span class="dashicons dashicons-admin-users" aria-hidden="true"></span>
					<span class="wp-ulike-about-help__text">
						<strong><?php esc_html_e( 'My account', WP_ULIKE_PRO_DOMAIN ); ?></strong>
						<span><?php esc_html_e( 'Copy license keys & invoices', WP_ULIKE_PRO_DOMAIN ); ?></span>
					</span>
				</a>
			</li>
			<li>
				<a href="<?php echo esc_url( $data['pricing_url'] ); ?>" target="_blank" rel="noopener noreferrer">
					<span class="dashicons dashicons-cart" aria-hidden="true"></span>
					<span class="wp-ulike-about-help__text">
						<strong><?php esc_html_e( 'Plans & pricing', WP_ULIKE_PRO_DOMAIN ); ?></strong>
						<span><?php esc_html_e( 'More sites or renewals', WP_ULIKE_PRO_DOMAIN ); ?></span>
					</span>
				</a>
			</li>
			<li>
				<a href="<?php echo esc_url( $data['help_url'] ); ?>">
					<span class="dashicons dashicons-sos" aria-hidden="true"></span>
					<span class="wp-ulike-about-help__text">
						<strong><?php esc_html_e( 'Help', WP_ULIKE_PRO_DOMAIN ); ?></strong>
						<span><?php esc_html_e( 'Plugin overview', WP_ULIKE_PRO_DOMAIN ); ?></span>
					</span>
				</a>
			</li>
		</ul>
	</div>
</aside>

