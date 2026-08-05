<?php
/**
 * Quick-start preset cards for Display Automation.
 *
 *
 * @package    wp-ulike-pro
 * @author     TechnoWich 2026
 * @link       https://wpulike.com
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( empty( $quick_start_presets ) || ! is_array( $quick_start_presets ) ) {
	return;
}
?>
<div class="wp-ulike-pro-display-empty-presets" role="list">
	<?php foreach ( $quick_start_presets as $preset ) : ?>
		<button type="button"
				class="wp-ulike-pro-display-preset-card"
				data-preset-id="<?php echo esc_attr( $preset['id'] ); ?>"
				role="listitem">
			<span class="wp-ulike-pro-display-preset-card-icon" aria-hidden="true">
				<span class="dashicons <?php echo esc_attr( $preset['icon'] ?? 'dashicons-admin-generic' ); ?>"></span>
			</span>
			<span class="wp-ulike-pro-display-preset-card-body">
				<strong class="wp-ulike-pro-display-preset-card-label wp-ulike-pro-display-bidi-auto"><?php echo esc_html( $preset['label'] ); ?></strong>
				<span class="wp-ulike-pro-display-preset-card-desc wp-ulike-pro-display-bidi-auto"><?php echo esc_html( $preset['description'] ); ?></span>
			</span>
		</button>
	<?php endforeach; ?>
</div>

