<?php
/**
 * Engagement frontend renderer.
 *
 * @package WP ULike Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WP_Ulike_Pro_Engagement_Display {

	/**
	 * Render engagement widget.
	 *
	 * @param int    $item_id   Item ID.
	 * @param string $item_type Content type slug.
	 * @return string
	 */
	public static function render( $item_id, $item_type ) {
		if ( ! $item_id || ! WP_Ulike_Pro_Engagement_Settings::is_enabled( $item_type ) ) {
			return '';
		}

		$mode = WP_Ulike_Pro_Engagement_Settings::get_mode( $item_type );

		if ( 'emoji' === $mode ) {
			$html = self::render_emoji( $item_id, $item_type );
		} elseif ( 'star' === $mode ) {
			$html = self::render_star( $item_id, $item_type );
		} else {
			$html = '';
		}

		return apply_filters( 'wp_ulike_pro_engagement_display', $html, $item_id, $item_type, $mode );
	}

	/**
	 * Emoji reactions markup.
	 *
	 * @param int    $item_id   Item ID.
	 * @param string $item_type Content type.
	 * @return string
	 */
	public static function render_emoji( $item_id, $item_type ) {
		$reactions    = WP_Ulike_Pro_Engagement_Registry::get_enabled_reactions( $item_type );
		$counts       = WP_Ulike_Pro_Engagement_Counter::get_all_reaction_counts( $item_id, $item_type );
		$user_id      = is_user_logged_in() ? (string) get_current_user_id() : (string) wp_ulike_generate_user_id( wp_ulike_get_user_ip() );
		$user_state   = WP_Ulike_Pro_Engagement_Process::get_user_state( $item_id, $item_type, 'emoji', $user_id );
		$active_key   = ( ! empty( $user_state['status'] ) && 'active' === $user_state['status'] ) ? $user_state['engagement_key'] : '';
		$total        = WP_Ulike_Pro_Engagement_Counter::get_total_reactions( $item_id, $item_type, $counts );
		$counters_on  = WP_Ulike_Pro_Engagement_Settings::counters_available( $item_type );
		$show_total   = WP_Ulike_Pro_Engagement_Settings::show_counters( $item_type, $total );
		$show_users   = WP_Ulike_Pro_Engagement_Settings::show_engagers( $item_type );
		$likers_style = WP_Ulike_Pro_Engagement_Settings::get_engagers_style( $item_type );
		$template     = WP_Ulike_Pro_Engagement_Settings::get_template_slug( $item_type );
		$nonce        = wp_create_nonce( WP_Ulike_Pro_Engagement_Settings::get_vote_nonce_action( $item_type, $item_id, $template ) );
		$picker_style = WP_Ulike_Pro_Engagement_Settings::get_picker_style( $item_type );
		// wp-exclude-emoji: keep native glyphs; WP Twemoji <img> CDN often breaks.
		$root_class   = 'wpulike-engagements wpulike-engagements-emoji wp-exclude-emoji ulp-picker-style-' . $picker_style;

		if ( $counters_on ) {
			$root_class .= ' ulp-has-counters';
		}

		if ( $show_users && 'popover' === $likers_style ) {
			$root_class .= ' ulp-has-engagers-popover';
		}

		$inline_layout  = 'inline' === $picker_style;
		$reactions_html = self::render_emoji_reaction_buttons(
			$reactions,
			$counts,
			$active_key,
			$item_type,
			$inline_layout ? 'inline' : 'picker'
		);

		$first_reaction   = reset( $reactions );
		$default_emoji    = $first_reaction ? $first_reaction['emoji'] : '😊';
		$trigger_emoji    = $default_emoji;
		$trigger_label    = esc_attr__( 'Add reaction', WP_ULIKE_PRO_DOMAIN );
		$trigger_reacted  = false;

		if ( $active_key && isset( $reactions[ $active_key ] ) ) {
			$trigger_emoji   = $reactions[ $active_key ]['emoji'];
			$trigger_reacted = true;
			$trigger_label   = sprintf(
				/* translators: %s: reaction label */
				__( 'You reacted with %s', WP_ULIKE_PRO_DOMAIN ),
				wp_strip_all_tags( $reactions[ $active_key ]['label'] )
			);
		}

		ob_start();
		?>
		<div class="<?php echo esc_attr( $root_class ); ?>"
			data-ulp-engagement-id="<?php echo esc_attr( $item_id ); ?>"
			data-ulp-engagement-type="<?php echo esc_attr( $item_type ); ?>"
			data-ulp-engagement-kind="emoji"
			data-ulp-engagement-template="<?php echo esc_attr( $template ); ?>"
			data-ulp-engagement-nonce="<?php echo esc_attr( $nonce ); ?>"
			data-ulp-picker-style="<?php echo esc_attr( $picker_style ); ?>"
			<?php echo $show_users ? ' data-ulp-likers-style="' . esc_attr( $likers_style ) . '"' : ''; ?>
			<?php echo $show_users ? ' data-ulp-engagers-url="' . esc_url( WP_Ulike_Pro_Engagement_Engagers::get_engagers_ajax_url( $item_id, $item_type, 'emoji', 'markup' ) ) . '"' : ''; ?>
			<?php echo ( $show_users && 'popover' === $likers_style ) ? ' data-ulp-engagers-popover-url="' . esc_url( WP_Ulike_Pro_Engagement_Engagers::get_popover_ajax_url( $item_id, $item_type, 'emoji' ) ) . '"' : ''; ?>
			<?php echo $counters_on ? ' data-ulp-show-counters="1"' : ''; ?>
			<?php echo WP_Ulike_Pro_Engagement_Settings::hide_zero_counters( $item_type ) ? ' data-ulp-hide-zero="1"' : ''; ?>>
			<div class="ulp-engagement-main"<?php echo ( $show_users && 'popover' === $likers_style ) ? ' data-ulp-engagers-popover-trigger="1"' : ''; ?>>
			<?php if ( 'inline' === $picker_style ) : ?>
				<div class="ulp-engagement-inline-bar" role="group" aria-label="<?php esc_attr_e( 'Pick a reaction', WP_ULIKE_PRO_DOMAIN ); ?>">
					<div class="ulp-engagement-reactions">
						<?php echo $reactions_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>
				</div>
			<?php else : ?>
				<div class="ulp-engagement-anchor">
					<button type="button" class="ulp-engagement-trigger<?php echo $trigger_reacted ? ' is-reacted' : ''; ?>" aria-expanded="false" aria-haspopup="true" aria-label="<?php echo esc_attr( $trigger_label ); ?>">
						<span class="ulp-engagement-trigger-icon" aria-hidden="true" data-ulp-default-emoji="<?php echo esc_attr( $default_emoji ); ?>"><?php echo esc_html( $trigger_emoji ); ?></span>
						<?php if ( $counters_on ) : ?>
							<span class="ulp-engagement-trigger-count<?php echo $show_total ? '' : ' is-zero'; ?>" data-ulp-engagement-total><?php echo esc_html( wp_ulike_pro_format_engagement_count( $total ) ); ?></span>
						<?php endif; ?>
					</button>
					<div class="ulp-engagement-picker">
						<div class="ulp-engagement-reactions" role="group" aria-label="<?php esc_attr_e( 'Pick a reaction', WP_ULIKE_PRO_DOMAIN ); ?>">
							<?php echo $reactions_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</div>
					</div>
				</div>
			<?php endif; ?>
			</div>
			<?php if ( $show_users && 'popover' !== $likers_style ) : ?>
				<?php echo WP_Ulike_Pro_Engagement_Engagers::render( $item_id, $item_type, 'emoji' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Emoji reaction button markup.
	 *
	 * @param array  $reactions  Enabled reactions.
	 * @param array  $counts     Reaction counts.
	 * @param string $active_key Active reaction slug for current user.
	 * @param string $item_type Content type slug.
	 * @param string $layout    inline|picker.
	 * @return string
	 */
	private static function render_emoji_reaction_buttons( $reactions, $counts, $active_key, $item_type, $layout = 'picker' ) {
		$html        = '';
		$counters_on = WP_Ulike_Pro_Engagement_Settings::counters_available( $item_type );
		$show_counts = $counters_on && in_array( $layout, array( 'inline', 'picker' ), true );
		$tab_set     = false;

		foreach ( $reactions as $slug => $reaction ) {
			$is_active = $active_key === $slug;
			$count     = isset( $counts[ $slug ] ) ? (int) $counts[ $slug ] : 0;
			$classes   = 'ulp-engagement-reaction' . ( $is_active ? ' is-active' : '' );
			// Roving tabindex: active reaction, else first button.
			$is_tab    = $is_active || ( ! $active_key && ! $tab_set );
			if ( $is_tab ) {
				$tab_set = true;
			}

			$count_markup = '';
			if ( $show_counts ) {
				// Always mark zeros; CSS decides visibility (setting and/or mobile).
				$count_classes = 'ulp-engagement-count' . ( 0 === $count ? ' is-zero' : '' );
				$count_markup  = sprintf(
					'<span class="%1$s" data-ulp-count-key="%2$s">%3$s</span>',
					esc_attr( $count_classes ),
					esc_attr( $slug ),
					esc_html( wp_ulike_pro_format_engagement_count( $count ) )
				);
			}

			$aria_label = $reaction['label'];
			if ( $show_counts && $count > 0 ) {
				$aria_label = sprintf(
					/* translators: 1: reaction label, 2: count */
					__( '%1$s, %2$s reactions', WP_ULIKE_PRO_DOMAIN ),
					wp_strip_all_tags( $reaction['label'] ),
					wp_ulike_pro_format_engagement_count( $count )
				);
			}

			$html .= sprintf(
				'<button type="button" class="%1$s" data-ulp-engagement-key="%2$s" data-ulp-reaction-label="%3$s" aria-pressed="%4$s" aria-label="%5$s" title="%6$s" tabindex="%7$s">
					<span class="ulp-engagement-emoji" aria-hidden="true">%8$s</span>
					<span class="ulp-engagement-reaction-label">%9$s</span>%10$s
				</button>',
				esc_attr( $classes ),
				esc_attr( $slug ),
				esc_attr( $reaction['label'] ),
				$is_active ? 'true' : 'false',
				esc_attr( $aria_label ),
				esc_attr( $reaction['label'] ),
				$is_tab ? '0' : '-1',
				esc_html( $reaction['emoji'] ),
				esc_html( $reaction['label'] ),
				$count_markup
			);
		}

		return $html;
	}

	/**
	 * Star rating markup.
	 *
	 * @param int    $item_id   Item ID.
	 * @param string $item_type Content type.
	 * @return string
	 */
	public static function render_star( $item_id, $item_type ) {
		$config     = WP_Ulike_Pro_Engagement_Registry::get_star_config( $item_type );
		$max        = max( 1, (int) $config['max'] );
		$aggregates = WP_Ulike_Pro_Engagement_Counter::get_star_aggregates( $item_id, $item_type );
		$user_id    = is_user_logged_in() ? (string) get_current_user_id() : (string) wp_ulike_generate_user_id( wp_ulike_get_user_ip() );
		$user_state = WP_Ulike_Pro_Engagement_Process::get_user_state( $item_id, $item_type, 'star', $user_id );
		$user_value = ( ! empty( $user_state['status'] ) && 'active' === $user_state['status'] ) ? (int) $user_state['value'] : 0;
		$counters_on  = WP_Ulike_Pro_Engagement_Settings::counters_available( $item_type );
		$show_count   = WP_Ulike_Pro_Engagement_Settings::show_counters( $item_type, (int) $aggregates['count'] );
		$show_users   = WP_Ulike_Pro_Engagement_Settings::show_engagers( $item_type );
		$likers_style = WP_Ulike_Pro_Engagement_Settings::get_engagers_style( $item_type );
		$template     = WP_Ulike_Pro_Engagement_Settings::get_template_slug( $item_type );
		$nonce        = wp_create_nonce( WP_Ulike_Pro_Engagement_Settings::get_vote_nonce_action( $item_type, $item_id, $template ) );
		$root_class   = 'wpulike-engagements wpulike-engagements-star';
		$live_status  = $user_value > 0
			? sprintf(
				/* translators: 1: user rating value, 2: max stars */
				__( 'Your rating: %1$d of %2$d', WP_ULIKE_PRO_DOMAIN ),
				$user_value,
				$max
			)
			: __( 'No rating', WP_ULIKE_PRO_DOMAIN );

		// Match emoji: expose counter availability for CSS/JS (hide-zero, Elementor, automation).
		if ( $counters_on ) {
			$root_class .= ' ulp-has-counters';
		}

		if ( $show_users && 'popover' === $likers_style ) {
			$root_class .= ' ulp-has-engagers-popover';
		}

		ob_start();
		?>
		<div class="<?php echo esc_attr( $root_class ); ?>"
			data-ulp-engagement-id="<?php echo esc_attr( $item_id ); ?>"
			data-ulp-engagement-type="<?php echo esc_attr( $item_type ); ?>"
			data-ulp-engagement-kind="star"
			data-ulp-engagement-template="<?php echo esc_attr( $template ); ?>"
			data-ulp-engagement-nonce="<?php echo esc_attr( $nonce ); ?>"
			data-ulp-star-max="<?php echo esc_attr( $max ); ?>"
			data-ulp-user-value="<?php echo esc_attr( $user_value ); ?>"
			<?php echo $show_users ? ' data-ulp-likers-style="' . esc_attr( $likers_style ) . '"' : ''; ?>
			<?php echo $show_users ? ' data-ulp-engagers-url="' . esc_url( WP_Ulike_Pro_Engagement_Engagers::get_engagers_ajax_url( $item_id, $item_type, 'star', 'markup' ) ) . '"' : ''; ?>
			<?php echo ( $show_users && 'popover' === $likers_style ) ? ' data-ulp-engagers-popover-url="' . esc_url( WP_Ulike_Pro_Engagement_Engagers::get_popover_ajax_url( $item_id, $item_type, 'star' ) ) . '"' : ''; ?>
			<?php echo $counters_on ? ' data-ulp-show-counters="1"' : ''; ?>
			<?php echo WP_Ulike_Pro_Engagement_Settings::hide_zero_counters( $item_type ) ? ' data-ulp-hide-zero="1"' : ''; ?>>
			<div class="ulp-engagement-main"<?php echo ( $show_users && 'popover' === $likers_style ) ? ' data-ulp-engagers-popover-trigger="1"' : ''; ?>>
			<div class="ulp-engagement-stars" role="radiogroup" aria-label="<?php esc_attr_e( 'Rate this item', WP_ULIKE_PRO_DOMAIN ); ?>">
				<?php
				// Soft rounded star path (shared across slots).
				$star_path = 'M12 2.85c.42 0 .8.25.97.64l2.12 5.05 5.45.5a1.05 1.05 0 0 1 .6 1.84l-4.15 3.6 1.27 5.3a1.05 1.05 0 0 1-1.57 1.14L12 18.1l-4.69 2.82a1.05 1.05 0 0 1-1.57-1.14l1.27-5.3-4.15-3.6a1.05 1.05 0 0 1 .6-1.84l5.45-.5 2.12-5.05c.17-.39.55-.64.97-.64z';
				for ( $i = 1; $i <= $max; $i++ ) :
					$filled   = $user_value >= $i;
					$selected = $user_value === $i;
					$classes  = 'ulp-engagement-star' . ( $filled ? ' is-active' : '' );
					$tabindex = ( $selected || ( 0 === $user_value && 1 === $i ) ) ? '0' : '-1';
					?>
					<button type="button"
						class="<?php echo esc_attr( $classes ); ?>"
						role="radio"
						data-ulp-engagement-value="<?php echo esc_attr( $i ); ?>"
						aria-checked="<?php echo $selected ? 'true' : 'false'; ?>"
						tabindex="<?php echo esc_attr( $tabindex ); ?>"
						aria-label="<?php echo esc_attr( sprintf( _n( '%d star', '%d stars', $i, WP_ULIKE_PRO_DOMAIN ), $i ) ); ?>">
						<span class="ulp-star-icon" aria-hidden="true">
							<span class="ulp-star-dot"></span>
							<svg class="ulp-star-shape" viewBox="0 0 24 24" focusable="false">
								<path fill="currentColor" d="<?php echo esc_attr( $star_path ); ?>"/>
							</svg>
						</span>
					</button>
				<?php endfor; ?>
				<span class="screen-reader-text" data-ulp-star-live aria-live="polite"><?php echo esc_html( $live_status ); ?></span>
			</div>
			<?php if ( $counters_on ) : ?>
				<span class="ulp-engagement-rating-summary<?php echo $show_count ? '' : ' is-zero'; ?>" data-ulp-rating-summary<?php echo $show_count ? '' : ' aria-hidden="true"'; ?>>
					<strong class="ulp-engagement-average" data-ulp-engagement-average><?php echo esc_html( number_format_i18n( $aggregates['average'], 1 ) ); ?></strong>
					<span class="ulp-engagement-rating-count" data-ulp-engagement-rating-count>(<?php echo esc_html( wp_ulike_pro_format_engagement_count( $aggregates['count'] ) ); ?>)</span>
				</span>
			<?php endif; ?>
			</div>
			<?php if ( $show_users && 'popover' !== $likers_style ) : ?>
				<?php echo WP_Ulike_Pro_Engagement_Engagers::render( $item_id, $item_type, 'star' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}
}

