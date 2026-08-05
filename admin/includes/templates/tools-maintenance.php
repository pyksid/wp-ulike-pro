<?php
/**
 * Tools — Maintenance panel (section switcher + grouped actions).
 *
 * @package WP_ULike_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

$maintenance_sections = WP_Ulike_Pro_Tools::get_maintenance_sections();
$risk_labels          = WP_Ulike_Pro_Tools::get_maintenance_risk_labels();
$maintenance_notices  = WP_Ulike_Pro_Tools::get_maintenance_admin_notices();
$section_keys         = array_keys( $maintenance_sections );
$default_section      = ! empty( $section_keys ) ? $section_keys[0] : '';
?>

<div class="wp-ulike-pro-maintenance" data-default-section="<?php echo esc_attr( $default_section ); ?>">
	<p class="wp-ulike-pro-maintenance__lead">
		<?php esc_html_e( 'Choose what you want to work on, then run one action at a time. Your posts and comments stay untouched unless you choose a removal tool.', WP_ULIKE_PRO_DOMAIN ); ?>
	</p>

	<?php foreach ( $maintenance_notices as $notice ) : ?>
		<div class="notice notice-<?php echo esc_attr( $notice['type'] ); ?> wp-ulike-pro-tools-notice">
			<p><?php echo esc_html( $notice['message'] ); ?></p>
		</div>
	<?php endforeach; ?>

	<div class="wp-ulike-tools-tabs wp-ulike-pro-maintenance-switcher" role="tablist" aria-label="<?php esc_attr_e( 'Maintenance categories', WP_ULIKE_PRO_DOMAIN ); ?>">
		<div class="wp-ulike-tools-tabs__scroll">
			<?php foreach ( $maintenance_sections as $section_key => $section ) : ?>
				<?php
				$is_active = $section_key === $default_section;
				$classes   = 'wp-ulike-tools-tabs__link wp-ulike-pro-maintenance-switcher__btn' . ( $is_active ? ' is-active' : '' );

				if ( ! empty( $section['is_advanced'] ) ) {
					$classes .= ' is-advanced';
				}
				?>
				<button
					type="button"
					class="<?php echo esc_attr( $classes ); ?>"
					role="tab"
					id="wp-ulike-maintenance-tab-<?php echo esc_attr( $section_key ); ?>"
					aria-selected="<?php echo $is_active ? 'true' : 'false'; ?>"
					aria-controls="wp-ulike-maintenance-panel-<?php echo esc_attr( $section_key ); ?>"
					data-section="<?php echo esc_attr( $section_key ); ?>"
				>
					<?php echo esc_html( $section['label'] ); ?>
				</button>
			<?php endforeach; ?>
		</div>
	</div>

	<?php foreach ( $maintenance_sections as $section_key => $section ) : ?>
		<?php
		$is_active = $section_key === $default_section;
		$panel_class = 'wp-ulike-pro-maintenance-panel';

		if ( ! empty( $section['is_advanced'] ) ) {
			$panel_class .= ' is-advanced';
		}
		?>
		<div
			class="<?php echo esc_attr( $panel_class ); ?>"
			id="wp-ulike-maintenance-panel-<?php echo esc_attr( $section_key ); ?>"
			role="tabpanel"
			aria-labelledby="wp-ulike-maintenance-tab-<?php echo esc_attr( $section_key ); ?>"
			data-section="<?php echo esc_attr( $section_key ); ?>"
			<?php echo $is_active ? '' : ' hidden'; ?>
		>
			<div class="wp-ulike-pro-tools-card">
				<div class="wp-ulike-pro-tools-card-header">
					<h2 class="wp-ulike-about-card__title"><?php echo esc_html( $section['label'] ); ?></h2>
				</div>
				<div class="wp-ulike-pro-tools-card-content">
					<p class="wp-ulike-tools-panel__intro"><?php echo esc_html( $section['description'] ); ?></p>

					<?php foreach ( $section['risk_groups'] as $risk_key => $risk_group ) : ?>
						<div class="wp-ulike-pro-maintenance-group wp-ulike-pro-maintenance-group--<?php echo esc_attr( $risk_key ); ?>">
							<h3 class="wp-ulike-pro-maintenance-group__title"><?php echo esc_html( $risk_group['label'] ); ?></h3>

							<div class="wp-ulike-pro-tools-list">
								<?php foreach ( $risk_group['tools'] as $tool ) : ?>
									<?php
									$tool_risk    = isset( $tool['risk'] ) ? $tool['risk'] : 'caution';
									$is_purge_ui  = isset( $tool['ui'] ) && 'purge' === $tool['ui'];
									$button_class = 'button button-secondary wp-ulike-pro-ajax-button-field wp-ulike-pro-tools-action-btn';
									$item_class   = 'wp-ulike-pro-tool-item';

									if ( 'destructive' === $tool_risk ) {
										$button_class .= ' wp-ulike-pro-tools-action-btn--destructive';
									}

									if ( $is_purge_ui ) {
										$item_class .= ' wp-ulike-pro-tool-item--purge';
									}
									?>
									<div class="<?php echo esc_attr( $item_class ); ?>" data-risk="<?php echo esc_attr( $tool_risk ); ?>">
										<div class="wp-ulike-pro-tool-content">
											<div class="wp-ulike-pro-tool-heading">
												<h4 class="wp-ulike-pro-tool-title"><?php echo esc_html( $tool['title'] ); ?></h4>
												<?php if ( isset( $risk_labels[ $tool_risk ] ) ) : ?>
													<span class="wp-ulike-pro-maintenance-risk wp-ulike-pro-maintenance-risk--<?php echo esc_attr( $tool_risk ); ?>">
														<?php echo esc_html( $risk_labels[ $tool_risk ] ); ?>
													</span>
												<?php endif; ?>
											</div>
											<p class="wp-ulike-pro-tool-desc"><?php echo esc_html( $tool['summary'] ); ?></p>

											<?php if ( $is_purge_ui && ! empty( $tool['filters'] ) && is_array( $tool['filters'] ) ) : ?>
												<div class="wp-ulike-pro-purge-filters">
													<?php foreach ( $tool['filters'] as $filter_key => $filter ) : ?>
														<label class="wp-ulike-pro-purge-filters__field">
															<span class="wp-ulike-pro-purge-filters__label"><?php echo esc_html( $filter['label'] ); ?></span>
															<select
																class="wp-ulike-pro-purge-filters__select"
																data-filter="<?php echo esc_attr( $filter_key ); ?>"
															>
																<?php foreach ( (array) $filter['options'] as $option ) : ?>
																	<option
																		value="<?php echo esc_attr( $option['value'] ); ?>"
																		<?php selected( (string) $filter['default'], (string) $option['value'] ); ?>
																	>
																		<?php echo esc_html( $option['label'] ); ?>
																	</option>
																<?php endforeach; ?>
															</select>
														</label>
													<?php endforeach; ?>
													<p class="wp-ulike-pro-purge-filters__count" data-purge-count aria-live="polite">
														<?php esc_html_e( 'Change filters or run purge to count matching rows.', WP_ULIKE_PRO_DOMAIN ); ?>
													</p>
												</div>
											<?php endif; ?>
										</div>
										<div class="wp-ulike-pro-tool-action">
											<button
												type="button"
												class="<?php echo esc_attr( $button_class ); ?>"
												data-type="<?php echo esc_attr( $tool['type'] ); ?>"
												data-action="<?php echo esc_attr( $tool['action'] ); ?>"
												data-risk="<?php echo esc_attr( $tool_risk ); ?>"
												<?php if ( ! empty( $tool['confirm'] ) ) : ?>
													data-confirm="<?php echo esc_attr( $tool['confirm'] ); ?>"
												<?php endif; ?>
												data-nonce="<?php echo esc_attr( wp_create_nonce( 'wp_ulike_pro_ajax_button_field' ) ); ?>"
											>
												<?php echo esc_html( $tool['label'] ); ?>
											</button>
										</div>
									</div>
								<?php endforeach; ?>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
	<?php endforeach; ?>
</div>

