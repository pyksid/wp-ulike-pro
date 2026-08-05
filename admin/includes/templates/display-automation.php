<?php
/**
 * Display Automation tab template.
 *
 *
 * @package    wp-ulike-pro
 * @author     TechnoWich 2026
 * @link       https://wpulike.com
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

$config           = WP_Ulike_Pro_Display_Automation::get_admin_config();
$rules            = $config['rules'];
$has_rules        = ! empty( $rules );
$has_saved_rules  = $config['has_saved_rules'];
$show_save_footer = $has_rules || $has_saved_rules;
$placement_groups = $config['placement_groups'];
$context_options  = $config['context_options'];
$post_types       = $config['post_types'];
$product_types    = $config['product_types'];
$is_woocommerce   = $config['is_woocommerce'];
$is_edd           = $config['is_edd'];
$is_buddypress    = ! empty( $config['is_buddypress'] );
$is_bbpress       = ! empty( $config['is_bbpress'] );
$bbpress_forums   = $config['bbpress_forums'] ?? array();
$button_templates = $config['button_templates'] ?? array();
$quick_start_presets = WP_Ulike_Pro_Display_Automation::get_quick_start_presets();
$has_quick_start     = ! empty( $quick_start_presets );
?>

<?php if ( isset( $_GET['settings-updated'] ) && 'true' === $_GET['settings-updated'] ) : ?>
	<div class="notice notice-success is-dismissible wp-ulike-pro-tools-notice">
		<p><?php esc_html_e( 'Display rules saved successfully.', WP_ULIKE_PRO_DOMAIN ); ?></p>
	</div>
<?php endif; ?>

<?php
$active_auto_display = WP_Ulike_Pro_Display_Automation::get_active_basic_auto_display_labels();
if ( ! empty( $active_auto_display ) ) :
	$settings_url = WP_Ulike_Pro_Display_Automation::get_content_types_settings_url();
	?>
	<div class="notice notice-warning wp-ulike-pro-tools-notice">
		<p>
			<?php
			echo wp_kses_post(
				sprintf(
					/* translators: 1: comma-separated content types, 2: opening anchor, 3: closing anchor */
					__( 'Basic Automatic Display is enabled for %1$s. To avoid duplicate buttons, turn it off under %2$sSettings → Configuration → Content Types%3$s for those types. If multiple rules target the same placement, only keep one active rule or use different filters.', WP_ULIKE_PRO_DOMAIN ),
					'<strong>' . esc_html( implode( ', ', $active_auto_display ) ) . '</strong>',
					'<a href="' . esc_url( $settings_url ) . '">',
					'</a>'
				)
			);
			?>
		</p>
	</div>
<?php endif; ?>

<div class="wp-ulike-pro-tools-card">
	<div class="wp-ulike-pro-tools-card-header">
		<h2 class="wp-ulike-about-card__title"><?php esc_html_e( 'Display Automation', WP_ULIKE_PRO_DOMAIN ); ?></h2>
	</div>
	<div class="wp-ulike-pro-tools-card-content">
		<p class="wp-ulike-tools-panel__intro wp-ulike-pro-display-bidi-auto"><?php esc_html_e( 'Automatically place like buttons on your site without writing code. Each rule controls one button location — create multiple rules if you need the button in more than one place (for example product page and shop archive). Works with custom post types, category filters, and custom theme hooks for page builders. Steps 1–3 are required; steps 4–6 add optional template, filter, and advanced settings.', WP_ULIKE_PRO_DOMAIN ); ?></p>

		<form method="post" action="" id="wp-ulike-pro-display-automation-form" class="wp-ulike-pro-display-automation-form"<?php echo is_rtl() ? ' dir="rtl"' : ''; ?>>
			<?php wp_nonce_field( 'wp_ulike_display_automation_settings', 'wp_ulike_display_automation_nonce' ); ?>
			<input type="hidden" name="wp_ulike_display_automation_save" value="1">

			<div class="wp-ulike-pro-display-automation-panel">
				<div class="wp-ulike-pro-display-automation-toolbar" <?php echo $has_rules ? '' : 'style="display:none;"'; ?>>
					<div class="wp-ulike-pro-display-automation-toolbar-actions">
						<button type="button" class="button button-secondary" id="wp-ulike-pro-add-display-rule">
							<span class="dashicons dashicons-plus-alt2"></span>
							<?php esc_html_e( 'Add Blank Rule', WP_ULIKE_PRO_DOMAIN ); ?>
						</button>
						<?php if ( $has_quick_start ) : ?>
							<button type="button"
									class="button button-secondary"
									id="wp-ulike-pro-toggle-display-presets"
									aria-expanded="false"
									aria-controls="wp-ulike-pro-display-preset-picker-toolbar">
								<span class="dashicons dashicons-lightbulb" aria-hidden="true"></span>
								<?php esc_html_e( 'Quick Start', WP_ULIKE_PRO_DOMAIN ); ?>
							</button>
						<?php endif; ?>
					</div>
					<p class="description wp-ulike-pro-display-automation-toolbar-help wp-ulike-pro-display-bidi-auto">
						<?php esc_html_e( 'One rule = one button location. Use Quick Start for ready-made rules, or add a blank rule to build your own.', WP_ULIKE_PRO_DOMAIN ); ?>
					</p>
				</div>

				<?php if ( $has_quick_start ) : ?>
					<div id="wp-ulike-pro-display-preset-picker-toolbar"
						 class="wp-ulike-pro-display-preset-picker wp-ulike-pro-display-preset-picker--toolbar"
						 hidden>
						<p class="description wp-ulike-pro-display-bidi-auto"><?php esc_html_e( 'Pick a preset to add another ready-made rule. Each adds one location — review it, adjust if needed, then save.', WP_ULIKE_PRO_DOMAIN ); ?></p>
						<?php include __DIR__ . '/display-automation-preset-picker.php'; ?>
					</div>
				<?php endif; ?>

				<div id="wp-ulike-pro-display-onboarding" class="wp-ulike-pro-display-onboarding" <?php echo $has_rules ? 'hidden' : ''; ?>>
					<div id="wp-ulike-pro-display-empty"
						 class="wp-ulike-pro-display-empty">
						<div class="wp-ulike-pro-display-empty-icon" aria-hidden="true">
							<span class="dashicons dashicons-location-alt"></span>
						</div>
						<h3><?php esc_html_e( 'No display rules yet', WP_ULIKE_PRO_DOMAIN ); ?></h3>
						<p class="wp-ulike-pro-display-bidi-auto"><?php esc_html_e( 'Pick a quick start below or build a custom rule from scratch. Each preset adds one ready-to-edit rule — review the steps, adjust filters if needed, then save.', WP_ULIKE_PRO_DOMAIN ); ?></p>
						<?php if ( $has_quick_start ) : ?>
							<?php include __DIR__ . '/display-automation-preset-picker.php'; ?>
						<?php endif; ?>
						<div class="wp-ulike-pro-display-empty-actions">
							<button type="button" class="button button-secondary" id="wp-ulike-pro-add-first-rule">
								<?php esc_html_e( 'Start from Scratch', WP_ULIKE_PRO_DOMAIN ); ?>
							</button>
						</div>
					</div>
				</div>

				<div id="wp-ulike-pro-display-rules"
					 class="wp-ulike-pro-display-rules"
					 data-config="<?php echo esc_attr( wp_json_encode( $config ) ); ?>">

					<?php foreach ( $rules as $index => $rule ) : ?>
						<?php include __DIR__ . '/display-automation-rule.php'; ?>
					<?php endforeach; ?>
				</div>

				<div class="wp-ulike-pro-display-automation-footer submit" <?php echo $show_save_footer ? '' : 'style="display:none;"'; ?>>
					<div id="wp-ulike-pro-display-automation-unsaved-bar"
						 class="wp-ulike-pro-display-automation-unsaved-bar"
						 hidden
						 role="status"
						 aria-live="polite">
						<span class="wp-ulike-pro-display-automation-unsaved-indicator" aria-hidden="true"></span>
						<span class="wp-ulike-pro-display-automation-unsaved-text"><?php esc_html_e( 'You have unsaved changes', WP_ULIKE_PRO_DOMAIN ); ?></span>
					</div>
					<input type="submit"
						   class="button button-primary wp-ulike-pro-display-automation-save-btn"
						   value="<?php esc_attr_e( 'Save Display Rules', WP_ULIKE_PRO_DOMAIN ); ?>">
				</div>
			</div>
		</form>
	</div>
</div>

<template id="wp-ulike-pro-display-rule-template">
	<?php
	$index = '__INDEX__';
	$rule  = WP_Ulike_Pro_Display_Automation::get_blank_rule();
	$rule['enabled'] = true;
	include __DIR__ . '/display-automation-rule.php';
	?>
</template>

<?php if ( $has_quick_start ) : ?>
<template id="wp-ulike-pro-display-starter-rules">
	<?php
	foreach ( $quick_start_presets as $preset ) :
		$index     = 'preset_' . $preset['id'];
		$preset_id = $preset['id'];
		$rule      = WP_Ulike_Pro_Display_Automation::merge_preset_rule( $preset['rule'] );
		include __DIR__ . '/display-automation-rule.php';
	endforeach;
	?>
</template>
<?php endif; ?>

