<?php
/**
 * Single display automation rule row.
 *
 *
 * @package    wp-ulike-pro
 * @author     TechnoWich 2026
 * @link       https://wpulike.com
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

$conditions        = isset( $rule['conditions'] ) && is_array( $rule['conditions'] ) ? $rule['conditions'] : array();
$wc                = isset( $conditions['woocommerce'] ) && is_array( $conditions['woocommerce'] ) ? $conditions['woocommerce'] : array();
$edd               = isset( $conditions['edd'] ) && is_array( $conditions['edd'] ) ? $conditions['edd'] : array();
$bbp               = isset( $conditions['bbpress'] ) && is_array( $conditions['bbpress'] ) ? $conditions['bbpress'] : array();
$is_edd            = isset( $is_edd ) ? (bool) $is_edd : false;
$is_bbpress        = isset( $is_bbpress ) ? (bool) $is_bbpress : false;
$bbpress_forums    = isset( $bbpress_forums ) && is_array( $bbpress_forums ) ? $bbpress_forums : array();
$button_templates  = isset( $button_templates ) && is_array( $button_templates ) ? $button_templates : array();
$bbp_topic_ids     = ! empty( $bbp['topic_ids'] ) ? implode( ', ', array_map( 'absint', (array) $bbp['topic_ids'] ) ) : '';
$group_key         = $rule['placement_group'] ?? 'wordpress';
$taxonomy          = $conditions['taxonomy'] ?? '';
$content_types     = WP_Ulike_Pro_Display_Automation::get_content_types_for_group( $group_key );
$content_type      = $rule['content_type'] ?? (string) array_key_first( $content_types );
if ( ! isset( $content_types[ $content_type ] ) ) {
	$content_type = (string) array_key_first( $content_types );
}
$group_placements  = WP_Ulike_Pro_Display_Automation::get_placements_for_group( $group_key, $content_type );
$current_placement = $rule['placement'] ?? (string) array_key_first( $group_placements );
if ( ! isset( $group_placements[ $current_placement ] ) ) {
	$current_placement = (string) array_key_first( $group_placements );
}
$placement_help    = $group_placements[ $current_placement ]['description'] ?? '';
$ui_copy           = WP_Ulike_Pro_Display_Automation::get_ui_copy_profiles();
$step_copy         = $ui_copy[ $content_type ] ?? $ui_copy['post'];
$context_options   = WP_Ulike_Pro_Display_Automation::get_context_options();
$is_comment_like   = in_array( $content_type, array( 'comment', 'product_review' ), true );
$show_content_pos  = WP_Ulike_Pro_Display_Automation::placement_uses_content_position( $current_placement );
$show_wc_filters   = $is_woocommerce && 'woocommerce' === $group_key && 'post' === $content_type;
$show_edd_filters  = false;
$show_bbpress_filters = $is_bbpress && 'bbpress' === $group_key && 'topic' === $content_type;
$rule_is_collapsed = is_numeric( $index ) && (int) $index > 0;
$group_label       = $placement_groups[ $group_key ]['label'] ?? ucfirst( $group_key );
$content_label     = $content_types[ $content_type ] ?? $content_type;
$placement_label   = $group_placements[ $current_placement ]['label'] ?? $current_placement;
$rule_summary      = implode( ' · ', array_filter( array( $group_label, $content_label, $placement_label ) ) );
$current_template  = $rule['template'] ?? '';
$display_counter   = $rule['display_counter'] ?? '';
$display_likers    = $rule['display_likers'] ?? '';
$likers_style      = $rule['likers_style'] ?? '';
$engagement_reactions    = isset( $rule['engagement_reactions'] ) && is_array( $rule['engagement_reactions'] ) ? $rule['engagement_reactions'] : array();
$engagement_picker_style = $rule['engagement_picker_style'] ?? '';
$emoji_template_id       = class_exists( 'WP_Ulike_Pro_Engagement_Settings' ) ? WP_Ulike_Pro_Engagement_Settings::TEMPLATE_EMOJI : 'wp-ulike-pro-emoji-reactions';
$reaction_item_type      = class_exists( 'WP_Ulike_Pro_Display_Automation' ) ? WP_Ulike_Pro_Display_Automation::map_rule_item_type( $content_type ) : 'post';
$configured_reactions    = class_exists( 'WP_Ulike_Pro_Engagement_Settings' ) ? WP_Ulike_Pro_Engagement_Settings::get_configured_reactions( $reaction_item_type ) : array();
$show_engagement_options = ( $current_template === $emoji_template_id );
$template_label          = '';
if ( '' !== $current_template ) {
	foreach ( $button_templates as $template_item ) {
		if ( ( $template_item['id'] ?? '' ) === $current_template ) {
			$template_label = $template_item['name'] ?? '';
			break;
		}
	}
}
$appearance_step_collapsed = '' === $current_template
	&& '' === $display_counter
	&& '' === $display_likers
	&& empty( $engagement_reactions )
	&& '' === $engagement_picker_style;
$show_likers_style         = 'yes' === $display_likers;
$rule_body_id              = 'wp-ulike-pro-display-rule-body-' . preg_replace( '/[^a-z0-9_-]/i', '', (string) $index );
$preset_id                 = isset( $preset_id ) ? sanitize_key( (string) $preset_id ) : '';
?>
<div class="wp-ulike-pro-display-rule<?php echo $rule_is_collapsed ? ' is-collapsed' : ''; ?>" data-index="<?php echo esc_attr( $index ); ?>" data-content-type="<?php echo esc_attr( $content_type ); ?>"<?php echo $preset_id ? ' data-preset-id="' . esc_attr( $preset_id ) . '"' : ''; ?>>
	<div class="wp-ulike-pro-display-rule-header">
		<button type="button" class="wp-ulike-pro-display-rule-toggle" aria-expanded="<?php echo $rule_is_collapsed ? 'false' : 'true'; ?>" aria-controls="<?php echo esc_attr( $rule_body_id ); ?>" aria-label="<?php esc_attr_e( 'Toggle rule details', WP_ULIKE_PRO_DOMAIN ); ?>">
			<span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span>
		</button>
		<div class="wp-ulike-pro-display-rule-heading">
			<input type="text"
				   name="display_rules[<?php echo esc_attr( $index ); ?>][title]"
				   class="wp-ulike-pro-display-rule-title"
				   value="<?php echo esc_attr( $rule['title'] ?? '' ); ?>"
				   placeholder="<?php esc_attr_e( 'Rule name (optional)', WP_ULIKE_PRO_DOMAIN ); ?>"
				   aria-label="<?php esc_attr_e( 'Rule name', WP_ULIKE_PRO_DOMAIN ); ?>">
			<span class="wp-ulike-pro-display-rule-summary wp-ulike-pro-display-bidi-auto"><?php echo esc_html( $rule_summary ); ?></span>
		</div>
		<div class="wp-ulike-pro-display-rule-header-meta">
			<span class="wp-ulike-pro-display-rule-same-location-badge"
				  aria-hidden="true"
				  title="">
				<?php esc_html_e( 'Same location', WP_ULIKE_PRO_DOMAIN ); ?>
			</span>
			<label class="wp-ulike-pro-display-rule-status" title="<?php esc_attr_e( 'Enable or disable this rule', WP_ULIKE_PRO_DOMAIN ); ?>">
				<input type="checkbox"
					   class="wp-ulike-pro-display-rule-enabled-input"
					   name="display_rules[<?php echo esc_attr( $index ); ?>][enabled]"
					   value="1"
					   <?php checked( ! empty( $rule['enabled'] ) ); ?>>
				<span class="wp-ulike-pro-display-rule-status-switch" aria-hidden="true"></span>
				<span class="wp-ulike-pro-display-rule-status-label"><?php esc_html_e( 'Active', WP_ULIKE_PRO_DOMAIN ); ?></span>
			</label>
			<div class="wp-ulike-pro-display-rule-actions">
				<button type="button" class="wp-ulike-pro-display-icon-btn wp-ulike-pro-display-rule-move-up" title="<?php esc_attr_e( 'Move up', WP_ULIKE_PRO_DOMAIN ); ?>">
					<span class="dashicons dashicons-arrow-up-alt2" aria-hidden="true"></span>
					<span class="screen-reader-text"><?php esc_html_e( 'Move up', WP_ULIKE_PRO_DOMAIN ); ?></span>
				</button>
				<button type="button" class="wp-ulike-pro-display-icon-btn wp-ulike-pro-display-rule-move-down" title="<?php esc_attr_e( 'Move down', WP_ULIKE_PRO_DOMAIN ); ?>">
					<span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span>
					<span class="screen-reader-text"><?php esc_html_e( 'Move down', WP_ULIKE_PRO_DOMAIN ); ?></span>
				</button>
				<button type="button" class="wp-ulike-pro-display-icon-btn wp-ulike-pro-display-rule-duplicate" title="<?php esc_attr_e( 'Duplicate rule', WP_ULIKE_PRO_DOMAIN ); ?>">
					<span class="dashicons dashicons-admin-page" aria-hidden="true"></span>
					<span class="screen-reader-text"><?php esc_html_e( 'Duplicate rule', WP_ULIKE_PRO_DOMAIN ); ?></span>
				</button>
				<button type="button" class="wp-ulike-pro-display-icon-btn wp-ulike-pro-display-icon-btn-danger wp-ulike-pro-display-rule-remove" title="<?php esc_attr_e( 'Remove rule', WP_ULIKE_PRO_DOMAIN ); ?>">
					<span class="dashicons dashicons-trash" aria-hidden="true"></span>
					<span class="screen-reader-text"><?php esc_html_e( 'Remove rule', WP_ULIKE_PRO_DOMAIN ); ?></span>
				</button>
			</div>
		</div>
	</div>

	<div class="wp-ulike-pro-display-rule-body" id="<?php echo esc_attr( $rule_body_id ); ?>">
		<input type="hidden" name="display_rules[<?php echo esc_attr( $index ); ?>][id]" value="<?php echo esc_attr( $rule['id'] ?? 'rule_' . ( is_numeric( $index ) ? ( (int) $index + 1 ) : 1 ) ); ?>">
		<input type="hidden" class="wp-ulike-pro-display-rule-priority" name="display_rules[<?php echo esc_attr( $index ); ?>][priority]" value="<?php echo esc_attr( $rule['priority'] ?? ( is_numeric( $index ) ? ( (int) $index + 1 ) * 10 : 10 ) ); ?>">

		<div class="wp-ulike-pro-display-steps">
			<div class="wp-ulike-pro-display-step">
				<div class="wp-ulike-pro-display-step-heading">
					<span class="wp-ulike-pro-display-step-number" aria-hidden="true">1</span>
					<div class="wp-ulike-pro-display-step-intro">
						<h4><?php esc_html_e( 'Choose a platform', WP_ULIKE_PRO_DOMAIN ); ?></h4>
						<p class="wp-ulike-pro-display-bidi-auto"><?php esc_html_e( 'Where does your site show the content you want people to like?', WP_ULIKE_PRO_DOMAIN ); ?></p>
					</div>
				</div>
				<div class="wp-ulike-pro-display-step-content">
					<div class="wp-ulike-pro-display-integration-cards" role="radiogroup" aria-label="<?php esc_attr_e( 'Platform', WP_ULIKE_PRO_DOMAIN ); ?>">
						<?php foreach ( $placement_groups as $group_id => $group ) : ?>
							<label class="wp-ulike-pro-display-integration-card">
								<input type="radio"
									   name="display_rules[<?php echo esc_attr( $index ); ?>][placement_group]"
									   value="<?php echo esc_attr( $group_id ); ?>"
									   class="wp-ulike-pro-display-rule-group"
									   <?php checked( $group_key, $group_id ); ?>>
								<span class="wp-ulike-pro-display-integration-card-inner">
									<span class="dashicons <?php echo esc_attr( $group['icon'] ?? 'dashicons-admin-site' ); ?>" aria-hidden="true"></span>
									<span class="wp-ulike-pro-display-integration-card-label"><?php echo esc_html( $group['label'] ); ?></span>
									<span class="wp-ulike-pro-display-integration-card-desc wp-ulike-pro-display-bidi-auto"><?php echo esc_html( $group['description'] ); ?></span>
								</span>
							</label>
						<?php endforeach; ?>
					</div>
				</div>
			</div>

			<div class="wp-ulike-pro-display-step wp-ulike-pro-display-step-content-type">
				<div class="wp-ulike-pro-display-step-heading">
					<span class="wp-ulike-pro-display-step-number" aria-hidden="true">2</span>
					<div class="wp-ulike-pro-display-step-intro">
						<h4 class="wp-ulike-pro-display-step-title-dynamic" data-key="step_content_title"><?php echo esc_html( $step_copy['step_content_title'] ); ?></h4>
						<p class="wp-ulike-pro-display-step-desc-dynamic wp-ulike-pro-display-bidi-auto" data-key="step_content_desc"><?php echo esc_html( $step_copy['step_content_desc'] ); ?></p>
					</div>
				</div>
				<div class="wp-ulike-pro-display-step-content">
					<div class="wp-ulike-pro-display-field-row">
						<label class="wp-ulike-pro-display-field-label wp-ulike-pro-display-content-type-label-dynamic" for="display-rule-content-type-<?php echo esc_attr( $index ); ?>" data-key="content_type_label"><?php echo esc_html( $step_copy['content_type_label'] ?? __( 'Apply to', WP_ULIKE_PRO_DOMAIN ) ); ?></label>
						<select id="display-rule-content-type-<?php echo esc_attr( $index ); ?>" name="display_rules[<?php echo esc_attr( $index ); ?>][content_type]" class="wp-ulike-pro-display-rule-content-type">
							<?php foreach ( $content_types as $type_key => $type_label ) : ?>
								<option value="<?php echo esc_attr( $type_key ); ?>" <?php selected( $content_type, $type_key ); ?>>
									<?php echo esc_html( $type_label ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<p class="description wp-ulike-pro-display-content-type-help wp-ulike-pro-display-bidi-auto"></p>
					</div>
				</div>
			</div>

			<div class="wp-ulike-pro-display-step wp-ulike-pro-display-step-placement">
				<div class="wp-ulike-pro-display-step-heading">
					<span class="wp-ulike-pro-display-step-number" aria-hidden="true">3</span>
					<div class="wp-ulike-pro-display-step-intro">
						<h4 class="wp-ulike-pro-display-step-title-dynamic" data-key="step_placement_title"><?php echo esc_html( $step_copy['step_placement_title'] ); ?></h4>
						<p class="wp-ulike-pro-display-step-desc-dynamic wp-ulike-pro-display-bidi-auto" data-key="step_placement_desc"><?php echo esc_html( $step_copy['step_placement_desc'] ); ?></p>
					</div>
				</div>
				<div class="wp-ulike-pro-display-step-content">
					<div class="wp-ulike-pro-display-field-row">
						<label class="wp-ulike-pro-display-field-label wp-ulike-pro-display-placement-label-dynamic" for="display-rule-placement-<?php echo esc_attr( $index ); ?>" data-key="placement_label"><?php echo esc_html( $step_copy['placement_label'] ); ?></label>
						<select id="display-rule-placement-<?php echo esc_attr( $index ); ?>" name="display_rules[<?php echo esc_attr( $index ); ?>][placement]" class="wp-ulike-pro-display-rule-placement">
							<?php foreach ( $group_placements as $placement_key => $placement ) : ?>
								<option value="<?php echo esc_attr( $placement_key ); ?>"
										data-description="<?php echo esc_attr( $placement['description'] ?? '' ); ?>"
										<?php selected( $current_placement, $placement_key ); ?>>
									<?php echo esc_html( $placement['label'] ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<p class="description wp-ulike-pro-display-placement-help wp-ulike-pro-display-bidi-auto"><?php echo esc_html( $placement_help ); ?></p>
						<p class="description wp-ulike-pro-display-placement-multi-hint wp-ulike-pro-display-bidi-auto"><?php esc_html_e( 'Each rule = one location. Add another rule for shop, product page, reviews, etc.', WP_ULIKE_PRO_DOMAIN ); ?></p>
					</div>

					<div class="wp-ulike-pro-display-field-row wp-ulike-pro-display-content-position" <?php echo $show_content_pos ? '' : 'style="display:none;"'; ?>>
						<label class="wp-ulike-pro-display-field-label" for="display-rule-position-<?php echo esc_attr( $index ); ?>">
							<?php echo $is_comment_like ? esc_html__( 'Inside each item', WP_ULIKE_PRO_DOMAIN ) : esc_html__( 'Inside content', WP_ULIKE_PRO_DOMAIN ); ?>
						</label>
						<select id="display-rule-position-<?php echo esc_attr( $index ); ?>" name="display_rules[<?php echo esc_attr( $index ); ?>][position]" class="wp-ulike-pro-display-rule-position">
							<option value="top" <?php selected( $rule['position'] ?? 'bottom', 'top' ); ?>><?php esc_html_e( 'Top', WP_ULIKE_PRO_DOMAIN ); ?></option>
							<option value="bottom" <?php selected( $rule['position'] ?? 'bottom', 'bottom' ); ?>><?php esc_html_e( 'Bottom', WP_ULIKE_PRO_DOMAIN ); ?></option>
							<option value="top_bottom" <?php selected( $rule['position'] ?? '', 'top_bottom' ); ?>><?php esc_html_e( 'Top and bottom', WP_ULIKE_PRO_DOMAIN ); ?></option>
						</select>
						<p class="description wp-ulike-pro-display-content-position-help">
							<?php
							if ( $is_comment_like ) {
								esc_html_e( 'Controls whether the button appears above or below the comment or review text.', WP_ULIKE_PRO_DOMAIN );
							} else {
								esc_html_e( 'Only applies when the button is inserted inside post or page content.', WP_ULIKE_PRO_DOMAIN );
							}
							?>
						</p>
					</div>

					<div class="wp-ulike-pro-display-field-row wp-ulike-pro-display-custom-hook" <?php echo ( 'custom' === $current_placement ) ? '' : 'style="display:none;"'; ?>>
						<label class="wp-ulike-pro-display-field-label" for="display-rule-hook-<?php echo esc_attr( $index ); ?>"><?php esc_html_e( 'Custom hook name', WP_ULIKE_PRO_DOMAIN ); ?> <span class="required">*</span></label>
						<input type="text"
							   id="display-rule-hook-<?php echo esc_attr( $index ); ?>"
							   name="display_rules[<?php echo esc_attr( $index ); ?>][custom_hook]"
							   class="regular-text wp-ulike-pro-display-custom-hook-input wp-ulike-pro-display-ltr"
							   value="<?php echo esc_attr( $rule['custom_hook'] ?? '' ); ?>"
							   placeholder="<?php esc_attr_e( 'e.g. woocommerce_share', WP_ULIKE_PRO_DOMAIN ); ?>"
							   aria-required="true">
						<p class="description"><?php esc_html_e( 'For developers: enter a theme or plugin hook name. Leave blank if you are not sure.', WP_ULIKE_PRO_DOMAIN ); ?></p>
					</div>
				</div>
			</div>

			<div class="wp-ulike-pro-display-step wp-ulike-pro-display-step-optional wp-ulike-pro-display-appearance-step<?php echo $appearance_step_collapsed ? ' is-collapsed' : ''; ?>" data-step-key="button-appearance">
				<button type="button" class="wp-ulike-pro-display-step-toggle" aria-expanded="<?php echo $appearance_step_collapsed ? 'false' : 'true'; ?>">
					<span class="wp-ulike-pro-display-step-number" aria-hidden="true">4</span>
					<span class="wp-ulike-pro-display-step-toggle-text">
						<strong><?php esc_html_e( 'Button appearance (optional)', WP_ULIKE_PRO_DOMAIN ); ?></strong>
						<span class="wp-ulike-pro-display-optional-step-desc wp-ulike-pro-display-appearance-step-desc wp-ulike-pro-display-bidi-auto">
							<?php
							if ( $template_label ) {
								echo esc_html(
									sprintf(
										/* translators: %s: template name */
										__( 'Template: %s', WP_ULIKE_PRO_DOMAIN ),
										$template_label
									)
								);
							} else {
								esc_html_e( 'Template, counter, and likers box — inherits Settings when left as Default.', WP_ULIKE_PRO_DOMAIN );
							}
							?>
						</span>
					</span>
					<span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span>
				</button>
				<div class="wp-ulike-pro-display-step-content">
					<p class="description wp-ulike-pro-display-step-note wp-ulike-pro-display-bidi-auto"><?php esc_html_e( 'Customize how buttons look for this rule. Useful when shop archives should hide counters but single product pages should show them.', WP_ULIKE_PRO_DOMAIN ); ?></p>

					<div class="wp-ulike-pro-display-field-row">
						<label class="wp-ulike-pro-display-field-label"><?php esc_html_e( 'Button template', WP_ULIKE_PRO_DOMAIN ); ?></label>
					</div>

					<div class="wp-ulike-pro-display-template-cards-wrap">
					<div class="wp-ulike-pro-display-template-cards" role="radiogroup" aria-label="<?php esc_attr_e( 'Button template', WP_ULIKE_PRO_DOMAIN ); ?>">
						<label class="wp-ulike-pro-display-template-card">
							<input type="radio"
								   name="display_rules[<?php echo esc_attr( $index ); ?>][template]"
								   value=""
								   class="wp-ulike-pro-display-rule-template"
								   <?php checked( $current_template, '' ); ?>>
							<span class="wp-ulike-pro-display-template-card-inner">
								<span class="wp-ulike-pro-display-template-card-preview wp-ulike-pro-display-template-card-preview--default" aria-hidden="true">
									<span class="dashicons dashicons-admin-appearance"></span>
								</span>
								<span class="wp-ulike-pro-display-template-card-label"><?php esc_html_e( 'Default', WP_ULIKE_PRO_DOMAIN ); ?></span>
							</span>
						</label>
						<?php foreach ( $button_templates as $template_item ) : ?>
							<?php
							$template_id = $template_item['id'] ?? '';
							if ( '' === $template_id ) {
								continue;
							}
							?>
							<label class="wp-ulike-pro-display-template-card">
								<input type="radio"
									   name="display_rules[<?php echo esc_attr( $index ); ?>][template]"
									   value="<?php echo esc_attr( $template_id ); ?>"
									   class="wp-ulike-pro-display-rule-template"
									   <?php checked( $current_template, $template_id ); ?>>
								<span class="wp-ulike-pro-display-template-card-inner">
									<span class="wp-ulike-pro-display-template-card-preview" aria-hidden="true">
										<?php if ( ! empty( $template_item['symbol'] ) ) : ?>
											<img src="<?php echo esc_url( $template_item['symbol'] ); ?>" alt="" loading="lazy" width="48" height="48">
										<?php else : ?>
											<span class="dashicons dashicons-heart"></span>
										<?php endif; ?>
									</span>
									<span class="wp-ulike-pro-display-template-card-label"><?php echo esc_html( $template_item['name'] ?? $template_id ); ?></span>
								</span>
							</label>
						<?php endforeach; ?>
					</div>
					</div>

					<div class="wp-ulike-pro-display-engagement-options" data-emoji-template="<?php echo esc_attr( $emoji_template_id ); ?>" <?php echo $show_engagement_options ? '' : 'style="display:none;"'; ?>>
						<div class="wp-ulike-pro-display-field-row">
							<label class="wp-ulike-pro-display-field-label"><?php esc_html_e( 'Reactions', WP_ULIKE_PRO_DOMAIN ); ?></label>
							<p class="description wp-ulike-pro-display-bidi-auto"><?php esc_html_e( 'Same reactions as Settings for this content type. Tap to limit this rule — none selected uses all.', WP_ULIKE_PRO_DOMAIN ); ?></p>
						</div>
						<div class="wp-ulike-pro-display-reaction-chips wp-ulike-pro-display-engagement-reactions wp-exclude-emoji" role="group" aria-label="<?php esc_attr_e( 'Reactions', WP_ULIKE_PRO_DOMAIN ); ?>">
							<?php if ( empty( $configured_reactions ) ) : ?>
								<p class="description wp-ulike-pro-display-reaction-chips-empty"><?php esc_html_e( 'No reactions configured yet. Choose the Emoji Reactions template in Settings first.', WP_ULIKE_PRO_DOMAIN ); ?></p>
							<?php else : ?>
								<?php foreach ( $configured_reactions as $reaction ) : ?>
									<?php
									$reaction_slug  = $reaction['slug'] ?? '';
									$reaction_emoji = $reaction['emoji'] ?? '';
									$reaction_label = wp_strip_all_tags( (string) ( $reaction['label'] ?? '' ) );
									if ( ! $reaction_slug || ! $reaction_emoji ) {
										continue;
									}
									$is_selected = in_array( $reaction_slug, $engagement_reactions, true );
									?>
									<label class="wp-ulike-pro-display-reaction-chip<?php echo $is_selected ? ' is-selected' : ''; ?>" title="<?php echo esc_attr( $reaction_label ? $reaction_label : $reaction_slug ); ?>">
										<input type="checkbox"
											name="display_rules[<?php echo esc_attr( $index ); ?>][engagement_reactions][]"
											value="<?php echo esc_attr( $reaction_slug ); ?>"
											class="wp-ulike-pro-display-rule-engagement-reaction"
											<?php checked( $is_selected ); ?>>
										<span class="wp-ulike-pro-display-reaction-chip-inner">
											<span class="wp-ulike-pro-display-reaction-chip-emoji" aria-hidden="true"><?php echo esc_html( $reaction_emoji ); ?></span>
											<span class="wp-ulike-pro-display-reaction-chip-label"><?php echo esc_html( $reaction_label ? $reaction_label : $reaction_slug ); ?></span>
										</span>
									</label>
								<?php endforeach; ?>
							<?php endif; ?>
						</div>

						<div class="wp-ulike-pro-display-field-row">
							<label class="wp-ulike-pro-display-field-label"><?php esc_html_e( 'Reaction Picker', WP_ULIKE_PRO_DOMAIN ); ?></label>
							<div class="wp-ulike-pro-display-button-set" role="radiogroup" aria-label="<?php esc_attr_e( 'Reaction Picker', WP_ULIKE_PRO_DOMAIN ); ?>">
								<label class="wp-ulike-pro-display-button-set-option">
									<input type="radio"
										name="display_rules[<?php echo esc_attr( $index ); ?>][engagement_picker_style]"
										value=""
										class="wp-ulike-pro-display-rule-engagement-picker-style"
										<?php checked( $engagement_picker_style, '' ); ?>>
									<span class="wp-ulike-pro-display-button-set-label"><?php esc_html_e( 'Default', WP_ULIKE_PRO_DOMAIN ); ?></span>
								</label>
								<label class="wp-ulike-pro-display-button-set-option">
									<input type="radio"
										name="display_rules[<?php echo esc_attr( $index ); ?>][engagement_picker_style]"
										value="hover"
										class="wp-ulike-pro-display-rule-engagement-picker-style"
										<?php checked( $engagement_picker_style, 'hover' ); ?>>
									<span class="wp-ulike-pro-display-button-set-label"><?php esc_html_e( 'Hover', WP_ULIKE_PRO_DOMAIN ); ?></span>
								</label>
								<label class="wp-ulike-pro-display-button-set-option">
									<input type="radio"
										name="display_rules[<?php echo esc_attr( $index ); ?>][engagement_picker_style]"
										value="inline"
										class="wp-ulike-pro-display-rule-engagement-picker-style"
										<?php checked( $engagement_picker_style, 'inline' ); ?>>
									<span class="wp-ulike-pro-display-button-set-label"><?php esc_html_e( 'Inline', WP_ULIKE_PRO_DOMAIN ); ?></span>
								</label>
							</div>
							<p class="description wp-ulike-pro-display-bidi-auto"><?php esc_html_e( 'Default inherits from Settings. Hover shows a popup bar; Inline shows all reactions in a row.', WP_ULIKE_PRO_DOMAIN ); ?></p>
						</div>
					</div>

					<div class="wp-ulike-pro-display-field-grid wp-ulike-pro-display-button-options">
						<div class="wp-ulike-pro-display-field-row">
							<label class="wp-ulike-pro-display-field-label" for="display-rule-counter-<?php echo esc_attr( $index ); ?>"><?php esc_html_e( 'Counter', WP_ULIKE_PRO_DOMAIN ); ?></label>
							<select id="display-rule-counter-<?php echo esc_attr( $index ); ?>" name="display_rules[<?php echo esc_attr( $index ); ?>][display_counter]" class="wp-ulike-pro-display-rule-display-counter">
								<option value="" <?php selected( $display_counter, '' ); ?>><?php esc_html_e( 'Default (from Settings)', WP_ULIKE_PRO_DOMAIN ); ?></option>
								<option value="no" <?php selected( $display_counter, 'no' ); ?>><?php esc_html_e( 'Hidden', WP_ULIKE_PRO_DOMAIN ); ?></option>
							</select>
							<p class="description wp-ulike-pro-display-bidi-auto"><?php esc_html_e( 'Hide the count summary (likes, reaction totals, or star average) — handy for compact shop or archive layouts.', WP_ULIKE_PRO_DOMAIN ); ?></p>
						</div>

						<div class="wp-ulike-pro-display-field-row">
							<label class="wp-ulike-pro-display-field-label" for="display-rule-likers-<?php echo esc_attr( $index ); ?>"><?php esc_html_e( 'Engagers box', WP_ULIKE_PRO_DOMAIN ); ?></label>
							<select id="display-rule-likers-<?php echo esc_attr( $index ); ?>" name="display_rules[<?php echo esc_attr( $index ); ?>][display_likers]" class="wp-ulike-pro-display-rule-display-likers">
								<option value="" <?php selected( $display_likers, '' ); ?>><?php esc_html_e( 'Default (from Settings)', WP_ULIKE_PRO_DOMAIN ); ?></option>
								<option value="yes" <?php selected( $display_likers, 'yes' ); ?>><?php esc_html_e( 'Show', WP_ULIKE_PRO_DOMAIN ); ?></option>
								<option value="no" <?php selected( $display_likers, 'no' ); ?>><?php esc_html_e( 'Hidden', WP_ULIKE_PRO_DOMAIN ); ?></option>
							</select>
							<p class="description wp-ulike-pro-display-bidi-auto"><?php esc_html_e( 'Shows who engaged with the item — likers, reactors, or raters (avatar list, popover, or pile).', WP_ULIKE_PRO_DOMAIN ); ?></p>
						</div>

						<div class="wp-ulike-pro-display-field-row wp-ulike-pro-display-likers-style" <?php echo $show_likers_style ? '' : 'style="display:none;"'; ?>>
							<label class="wp-ulike-pro-display-field-label" for="display-rule-likers-style-<?php echo esc_attr( $index ); ?>"><?php esc_html_e( 'Likers display', WP_ULIKE_PRO_DOMAIN ); ?></label>
							<select id="display-rule-likers-style-<?php echo esc_attr( $index ); ?>" name="display_rules[<?php echo esc_attr( $index ); ?>][likers_style]" class="wp-ulike-pro-display-rule-likers-style">
								<option value="" <?php selected( $likers_style, '' ); ?>><?php esc_html_e( 'Default (from Settings)', WP_ULIKE_PRO_DOMAIN ); ?></option>
								<option value="default" <?php selected( $likers_style, 'default' ); ?>><?php esc_html_e( 'Default list', WP_ULIKE_PRO_DOMAIN ); ?></option>
								<option value="popover" <?php selected( $likers_style, 'popover' ); ?>><?php esc_html_e( 'Popover', WP_ULIKE_PRO_DOMAIN ); ?></option>
								<option value="pile" <?php selected( $likers_style, 'pile' ); ?>><?php esc_html_e( 'Pile + Modal', WP_ULIKE_PRO_DOMAIN ); ?></option>
							</select>
						</div>
					</div>
				</div>
			</div>

			<div class="wp-ulike-pro-display-step wp-ulike-pro-display-step-optional wp-ulike-pro-display-limit-step is-collapsed" data-step-key="limit-filters">
				<button type="button" class="wp-ulike-pro-display-step-toggle" aria-expanded="false">
					<span class="wp-ulike-pro-display-step-number" aria-hidden="true">5</span>
					<span class="wp-ulike-pro-display-step-toggle-text">
						<strong><?php esc_html_e( 'Limit where this shows (optional)', WP_ULIKE_PRO_DOMAIN ); ?></strong>
						<span class="wp-ulike-pro-display-optional-step-desc wp-ulike-pro-display-bidi-auto"><?php esc_html_e( 'Skip this if you want the button everywhere on the chosen platform.', WP_ULIKE_PRO_DOMAIN ); ?></span>
					</span>
					<span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span>
				</button>
				<div class="wp-ulike-pro-display-step-content">
					<p class="description wp-ulike-pro-display-step-note wp-ulike-pro-display-bidi-auto"><?php esc_html_e( 'All fields below are optional. Leave them empty to show on every matching page.', WP_ULIKE_PRO_DOMAIN ); ?></p>

					<div class="wp-ulike-pro-display-field-grid wp-ulike-pro-display-limit-context-grid">
						<div class="wp-ulike-pro-display-field-row" data-filter-scope="page-context">
							<label class="wp-ulike-pro-display-field-label"><?php esc_html_e( 'Only show on', WP_ULIKE_PRO_DOMAIN ); ?></label>
							<div class="wp-ulike-pro-display-checkbox-list wp-ulike-pro-display-context-show">
								<?php foreach ( $context_options as $context_key => $context_label ) : ?>
									<label class="wp-ulike-pro-display-checkbox-item">
										<input type="checkbox"
											   name="display_rules[<?php echo esc_attr( $index ); ?>][conditions][show_on][]"
											   value="<?php echo esc_attr( $context_key ); ?>"
											   <?php checked( in_array( $context_key, (array) ( $conditions['show_on'] ?? array() ), true ) ); ?>>
										<?php echo esc_html( $context_label ); ?>
									</label>
								<?php endforeach; ?>
							</div>
							<p class="description wp-ulike-pro-display-filter-help wp-ulike-pro-display-filter-help-show" data-for="page-context"><?php esc_html_e( 'Example: only single posts, or only product pages.', WP_ULIKE_PRO_DOMAIN ); ?></p>
						</div>

						<div class="wp-ulike-pro-display-field-row" data-filter-scope="page-context">
							<label class="wp-ulike-pro-display-field-label"><?php esc_html_e( 'Hide on', WP_ULIKE_PRO_DOMAIN ); ?></label>
							<div class="wp-ulike-pro-display-checkbox-list wp-ulike-pro-display-context-hide">
								<?php foreach ( $context_options as $context_key => $context_label ) : ?>
									<label class="wp-ulike-pro-display-checkbox-item">
										<input type="checkbox"
											   name="display_rules[<?php echo esc_attr( $index ); ?>][conditions][hide_on][]"
											   value="<?php echo esc_attr( $context_key ); ?>"
											   <?php checked( in_array( $context_key, (array) ( $conditions['hide_on'] ?? array() ), true ) ); ?>>
										<?php echo esc_html( $context_label ); ?>
									</label>
								<?php endforeach; ?>
							</div>
							<p class="description wp-ulike-pro-display-filter-help wp-ulike-pro-display-filter-help-hide" data-for="page-context"><?php esc_html_e( 'Example: hide on search results or shop archives.', WP_ULIKE_PRO_DOMAIN ); ?></p>
						</div>

						<div class="wp-ulike-pro-display-field-row" data-filter-scope="post-type">
							<label class="wp-ulike-pro-display-field-label wp-ulike-pro-display-post-type-label"><?php echo $is_comment_like ? esc_html__( 'Parent content types', WP_ULIKE_PRO_DOMAIN ) : esc_html__( 'Content types', WP_ULIKE_PRO_DOMAIN ); ?></label>
							<div class="wp-ulike-pro-display-checkbox-list wp-ulike-pro-display-post-types">
								<?php foreach ( $post_types as $post_type => $label ) : ?>
									<label class="wp-ulike-pro-display-checkbox-item">
										<input type="checkbox"
											   name="display_rules[<?php echo esc_attr( $index ); ?>][conditions][post_types][]"
											   value="<?php echo esc_attr( $post_type ); ?>"
											   <?php checked( in_array( $post_type, (array) ( $conditions['post_types'] ?? array() ), true ) ); ?>>
										<?php echo esc_html( $label ); ?>
									</label>
								<?php endforeach; ?>
							</div>
							<p class="description wp-ulike-pro-display-filter-help" data-for="post-type"><?php echo $is_comment_like ? esc_html__( 'Example: only comments on blog posts, or only reviews on products.', WP_ULIKE_PRO_DOMAIN ) : esc_html__( 'Example: only blog posts, only pages, or only products.', WP_ULIKE_PRO_DOMAIN ); ?></p>
						</div>
					</div>

					<div class="wp-ulike-pro-display-field-grid wp-ulike-pro-display-field-grid-secondary" data-filter-scope="taxonomy">
						<div class="wp-ulike-pro-display-field-row wp-ulike-pro-display-taxonomy-group"
							 data-nonce="<?php echo esc_attr( wp_create_nonce( 'wp_ulike_pro_get_categories' ) ); ?>">
							<label class="wp-ulike-pro-display-field-label"><?php esc_html_e( 'Category or taxonomy', WP_ULIKE_PRO_DOMAIN ); ?></label>
							<div class="wp-ulike-pro-display-select-wrapper">
								<select name="display_rules[<?php echo esc_attr( $index ); ?>][conditions][taxonomy]" class="wp-ulike-pro-display-taxonomy" data-placeholder="<?php esc_attr_e( 'Any', WP_ULIKE_PRO_DOMAIN ); ?>">
									<option value=""><?php esc_html_e( 'Any', WP_ULIKE_PRO_DOMAIN ); ?></option>
								</select>
								<span class="spinner wp-ulike-pro-inline-spinner wp-ulike-pro-display-field-spinner" aria-hidden="true"></span>
							</div>
							<p class="description wp-ulike-pro-display-taxonomy-help"
							   data-default-wp="<?php esc_attr_e( 'Limit by category or taxonomy. Choose content types above first if you filter posts and pages differently.', WP_ULIKE_PRO_DOMAIN ); ?>"
							   data-default-wc="<?php esc_attr_e( 'Pick a product category, tag, or custom taxonomy.', WP_ULIKE_PRO_DOMAIN ); ?>"
							   data-default-edd="<?php esc_attr_e( 'Pick a download category, tag, or custom taxonomy.', WP_ULIKE_PRO_DOMAIN ); ?>">
								<?php esc_html_e( 'Limit by category or taxonomy. Choose content types above first if you filter posts and pages differently.', WP_ULIKE_PRO_DOMAIN ); ?>
							</p>
						</div>

						<div class="wp-ulike-pro-display-field-row wp-ulike-pro-display-terms-group">
							<label class="wp-ulike-pro-display-field-label"><?php esc_html_e( 'Specific terms', WP_ULIKE_PRO_DOMAIN ); ?></label>
							<div class="wp-ulike-pro-display-select-wrapper">
								<div class="wp-ulike-pro-display-checkbox-list wp-ulike-pro-display-term-ids"
									 data-name="display_rules[<?php echo esc_attr( $index ); ?>][conditions][term_ids][]"
									 data-selected="<?php echo esc_attr( wp_json_encode( array_map( 'absint', (array) ( $conditions['term_ids'] ?? array() ) ) ) ); ?>"
									 data-taxonomy="<?php echo esc_attr( $taxonomy ); ?>">
								</div>
								<span class="spinner wp-ulike-pro-inline-spinner wp-ulike-pro-display-field-spinner" aria-hidden="true"></span>
							</div>
							<p class="description"><?php esc_html_e( 'Example: only posts in “News” or products in “Shoes”.', WP_ULIKE_PRO_DOMAIN ); ?></p>
						</div>
					</div>

					<?php if ( $is_woocommerce ) : ?>
						<div class="wp-ulike-pro-display-woocommerce-filters" data-filter-scope="woocommerce" <?php echo $show_wc_filters ? '' : 'style="display:none;"'; ?>>
							<h5><?php esc_html_e( 'WooCommerce product filters', WP_ULIKE_PRO_DOMAIN ); ?></h5>
							<div class="wp-ulike-pro-display-field-grid">
								<div class="wp-ulike-pro-display-field-row">
									<label class="wp-ulike-pro-display-field-label"><?php esc_html_e( 'On sale', WP_ULIKE_PRO_DOMAIN ); ?></label>
									<select name="display_rules[<?php echo esc_attr( $index ); ?>][conditions][woocommerce][on_sale]">
										<option value="" <?php selected( $wc['on_sale'] ?? '', '' ); ?>><?php esc_html_e( 'Any product', WP_ULIKE_PRO_DOMAIN ); ?></option>
										<option value="yes" <?php selected( $wc['on_sale'] ?? '', 'yes' ); ?>><?php esc_html_e( 'On sale only', WP_ULIKE_PRO_DOMAIN ); ?></option>
										<option value="no" <?php selected( $wc['on_sale'] ?? '', 'no' ); ?>><?php esc_html_e( 'Not on sale', WP_ULIKE_PRO_DOMAIN ); ?></option>
									</select>
								</div>

								<div class="wp-ulike-pro-display-field-row">
									<label class="wp-ulike-pro-display-field-label"><?php esc_html_e( 'Featured', WP_ULIKE_PRO_DOMAIN ); ?></label>
									<select name="display_rules[<?php echo esc_attr( $index ); ?>][conditions][woocommerce][featured]">
										<option value="" <?php selected( $wc['featured'] ?? '', '' ); ?>><?php esc_html_e( 'Any product', WP_ULIKE_PRO_DOMAIN ); ?></option>
										<option value="yes" <?php selected( $wc['featured'] ?? '', 'yes' ); ?>><?php esc_html_e( 'Featured only', WP_ULIKE_PRO_DOMAIN ); ?></option>
										<option value="no" <?php selected( $wc['featured'] ?? '', 'no' ); ?>><?php esc_html_e( 'Not featured', WP_ULIKE_PRO_DOMAIN ); ?></option>
									</select>
								</div>

								<div class="wp-ulike-pro-display-field-row">
									<label class="wp-ulike-pro-display-field-label"><?php esc_html_e( 'Product type', WP_ULIKE_PRO_DOMAIN ); ?></label>
									<div class="wp-ulike-pro-display-checkbox-list wp-ulike-pro-display-product-types">
										<?php foreach ( $product_types as $type_key => $type_label ) : ?>
											<label class="wp-ulike-pro-display-checkbox-item">
												<input type="checkbox"
													   name="display_rules[<?php echo esc_attr( $index ); ?>][conditions][woocommerce][product_type][]"
													   value="<?php echo esc_attr( $type_key ); ?>"
													   <?php checked( in_array( $type_key, (array) ( $wc['product_type'] ?? array() ), true ) ); ?>>
												<?php echo esc_html( $type_label ); ?>
											</label>
										<?php endforeach; ?>
									</div>
								</div>
							</div>
						</div>
					<?php endif; ?>

					<?php if ( $is_edd ) : ?>
						<div class="wp-ulike-pro-display-edd-filters" data-filter-scope="edd" <?php echo $show_edd_filters ? '' : 'style="display:none;"'; ?>>
							<h5><?php esc_html_e( 'EDD download filters', WP_ULIKE_PRO_DOMAIN ); ?></h5>
							<div class="wp-ulike-pro-display-field-grid">
								<div class="wp-ulike-pro-display-field-row">
									<label class="wp-ulike-pro-display-field-label"><?php esc_html_e( 'Free download', WP_ULIKE_PRO_DOMAIN ); ?></label>
									<select name="display_rules[<?php echo esc_attr( $index ); ?>][conditions][edd][free]">
										<option value="" <?php selected( $edd['free'] ?? '', '' ); ?>><?php esc_html_e( 'Any download', WP_ULIKE_PRO_DOMAIN ); ?></option>
										<option value="yes" <?php selected( $edd['free'] ?? '', 'yes' ); ?>><?php esc_html_e( 'Free only', WP_ULIKE_PRO_DOMAIN ); ?></option>
										<option value="no" <?php selected( $edd['free'] ?? '', 'no' ); ?>><?php esc_html_e( 'Paid only', WP_ULIKE_PRO_DOMAIN ); ?></option>
									</select>
								</div>

								<div class="wp-ulike-pro-display-field-row">
									<label class="wp-ulike-pro-display-field-label"><?php esc_html_e( 'Variable pricing', WP_ULIKE_PRO_DOMAIN ); ?></label>
									<select name="display_rules[<?php echo esc_attr( $index ); ?>][conditions][edd][variable_pricing]">
										<option value="" <?php selected( $edd['variable_pricing'] ?? '', '' ); ?>><?php esc_html_e( 'Any download', WP_ULIKE_PRO_DOMAIN ); ?></option>
										<option value="yes" <?php selected( $edd['variable_pricing'] ?? '', 'yes' ); ?>><?php esc_html_e( 'Variable pricing only', WP_ULIKE_PRO_DOMAIN ); ?></option>
										<option value="no" <?php selected( $edd['variable_pricing'] ?? '', 'no' ); ?>><?php esc_html_e( 'Single price only', WP_ULIKE_PRO_DOMAIN ); ?></option>
									</select>
								</div>
							</div>
						</div>
					<?php endif; ?>

					<?php if ( $is_bbpress ) : ?>
						<div class="wp-ulike-pro-display-bbpress-filters" data-filter-scope="bbpress" <?php echo $show_bbpress_filters ? '' : 'style="display:none;"'; ?>>
							<h5><?php esc_html_e( 'bbPress filters', WP_ULIKE_PRO_DOMAIN ); ?></h5>
							<div class="wp-ulike-pro-display-field-grid">
								<div class="wp-ulike-pro-display-field-row">
									<label class="wp-ulike-pro-display-field-label"><?php esc_html_e( 'Item type', WP_ULIKE_PRO_DOMAIN ); ?></label>
									<select name="display_rules[<?php echo esc_attr( $index ); ?>][conditions][bbpress][item_type]">
										<option value="" <?php selected( $bbp['item_type'] ?? '', '' ); ?>><?php esc_html_e( 'Topics and replies', WP_ULIKE_PRO_DOMAIN ); ?></option>
										<option value="topic" <?php selected( $bbp['item_type'] ?? '', 'topic' ); ?>><?php esc_html_e( 'Topics only', WP_ULIKE_PRO_DOMAIN ); ?></option>
										<option value="reply" <?php selected( $bbp['item_type'] ?? '', 'reply' ); ?>><?php esc_html_e( 'Replies only', WP_ULIKE_PRO_DOMAIN ); ?></option>
									</select>
									<p class="description wp-ulike-pro-display-bidi-auto"><?php esc_html_e( 'Use separate rules with different placements for topics vs replies when needed.', WP_ULIKE_PRO_DOMAIN ); ?></p>
								</div>

								<?php if ( ! empty( $bbpress_forums ) ) : ?>
									<div class="wp-ulike-pro-display-field-row">
										<label class="wp-ulike-pro-display-field-label"><?php esc_html_e( 'Forums', WP_ULIKE_PRO_DOMAIN ); ?></label>
										<div class="wp-ulike-pro-display-checkbox-list">
											<?php foreach ( $bbpress_forums as $forum_id => $forum_label ) : ?>
												<label class="wp-ulike-pro-display-checkbox-item">
													<input type="checkbox"
														   name="display_rules[<?php echo esc_attr( $index ); ?>][conditions][bbpress][forum_ids][]"
														   value="<?php echo esc_attr( $forum_id ); ?>"
														   <?php checked( in_array( (int) $forum_id, array_map( 'absint', (array) ( $bbp['forum_ids'] ?? array() ) ), true ) ); ?>>
													<?php echo esc_html( $forum_label ); ?>
												</label>
											<?php endforeach; ?>
										</div>
									</div>
								<?php endif; ?>

								<div class="wp-ulike-pro-display-field-row">
									<label class="wp-ulike-pro-display-field-label" for="display-rule-bbp-topics-<?php echo esc_attr( $index ); ?>"><?php esc_html_e( 'Specific topic IDs', WP_ULIKE_PRO_DOMAIN ); ?></label>
									<input type="text"
										   id="display-rule-bbp-topics-<?php echo esc_attr( $index ); ?>"
										   class="regular-text wp-ulike-pro-display-ltr"
										   name="display_rules[<?php echo esc_attr( $index ); ?>][conditions][bbpress][topic_ids]"
										   value="<?php echo esc_attr( $bbp_topic_ids ); ?>"
										   placeholder="<?php esc_attr_e( 'e.g. 12, 45, 102', WP_ULIKE_PRO_DOMAIN ); ?>">
									<p class="description wp-ulike-pro-display-bidi-auto"><?php esc_html_e( 'Optional. Comma-separated topic IDs. Replies inherit the parent topic.', WP_ULIKE_PRO_DOMAIN ); ?></p>
								</div>
							</div>
						</div>
					<?php endif; ?>
				</div>
			</div>

			<div class="wp-ulike-pro-display-step wp-ulike-pro-display-step-optional is-collapsed">
				<button type="button" class="wp-ulike-pro-display-step-toggle" aria-expanded="false">
					<span class="wp-ulike-pro-display-step-number" aria-hidden="true">6</span>
					<span class="wp-ulike-pro-display-step-toggle-text">
						<strong><?php esc_html_e( 'Advanced settings (optional)', WP_ULIKE_PRO_DOMAIN ); ?></strong>
						<span><?php esc_html_e( 'Technical options — most users can skip this.', WP_ULIKE_PRO_DOMAIN ); ?></span>
					</span>
					<span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span>
				</button>
				<div class="wp-ulike-pro-display-step-content">
					<div class="wp-ulike-pro-display-field-row">
						<label class="wp-ulike-pro-display-field-label" for="display-rule-priority-<?php echo esc_attr( $index ); ?>"><?php esc_html_e( 'Hook priority', WP_ULIKE_PRO_DOMAIN ); ?></label>
						<input type="number"
							   id="display-rule-priority-<?php echo esc_attr( $index ); ?>"
							   name="display_rules[<?php echo esc_attr( $index ); ?>][hook_priority]"
							   class="small-text"
							   min="1"
							   max="100"
							   value="<?php echo esc_attr( $rule['hook_priority'] ?? 10 ); ?>">
						<p class="description"><?php esc_html_e( 'Controls the WordPress hook execution order on the same action. Lower numbers run earlier. This is separate from the rule list order above.', WP_ULIKE_PRO_DOMAIN ); ?></p>
					</div>
					<label class="wp-ulike-pro-display-rule-override">
						<input type="checkbox"
							   name="display_rules[<?php echo esc_attr( $index ); ?>][override_default]"
							   value="1"
							   <?php checked( ! empty( $rule['override_default'] ) ); ?>>
						<?php esc_html_e( 'Replace basic Automatic Display for matching content when this rule uses content or comment filters (not custom theme hooks).', WP_ULIKE_PRO_DOMAIN ); ?>
					</label>
				</div>
			</div>
		</div>
	</div>
</div>

