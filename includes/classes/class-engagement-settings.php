<?php
/**
 * Engagement settings helper.
 *
 * @package WP ULike Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WP_Ulike_Pro_Engagement_Settings {

	const TEMPLATE_EMOJI = 'wp-ulike-pro-emoji-reactions';
	const TEMPLATE_STAR  = 'wp-ulike-pro-star-rating';

	/**
	 * Per-request render overrides (display automation, template context).
	 *
	 * @var array<string, array<int, array<string, mixed>>>
	 */
	private static $context_stack = array();

	/**
	 * Map item type to settings group key.
	 *
	 * @param string $item_type Content type slug.
	 * @return string
	 */
	public static function get_option_group( $item_type ) {
		$map = array(
			'post'     => 'posts_group',
			'comment'  => 'comments_group',
			'activity' => 'buddypress_group',
			'topic'    => 'bbpress_group',
		);

		return isset( $map[ $item_type ] ) ? $map[ $item_type ] : '';
	}

	/**
	 * Engagement templates mapped to mode.
	 *
	 * @return array<string,string>
	 */
	public static function get_engagement_template_map() {
		return array(
			self::TEMPLATE_EMOJI => 'emoji',
			self::TEMPLATE_STAR  => 'star',
		);
	}

	/**
	 * Get option value for a content type.
	 *
	 * @param string $item_type Content type slug.
	 * @param string $key       Option key without group prefix.
	 * @param mixed  $default   Default value.
	 * @return mixed
	 */
	public static function get_type_option( $item_type, $key, $default = '' ) {
		$context = self::get_active_context( $item_type );

		if ( isset( $context[ $key ] ) && '' !== $context[ $key ] && null !== $context[ $key ] ) {
			return $context[ $key ];
		}

		$group = self::get_option_group( $item_type );

		if ( empty( $group ) ) {
			return $default;
		}

		return wp_ulike_get_option( $group . '|' . $key, $default );
	}

	/**
	 * Push temporary engagement settings for the current render.
	 *
	 * @param string               $item_type Content type slug.
	 * @param array<string, mixed> $context   Override keys (template, engagement_reactions, engagement_picker_style, display_counters, display_likers, likers_style).
	 * @return void
	 */
	public static function push_context( $item_type, $context ) {
		$item_type = sanitize_key( $item_type );

		if ( ! isset( self::$context_stack[ $item_type ] ) ) {
			self::$context_stack[ $item_type ] = array();
		}

		self::$context_stack[ $item_type ][] = is_array( $context ) ? $context : array();
	}

	/**
	 * Pop the latest render override stack entry.
	 *
	 * @param string $item_type Content type slug.
	 * @return void
	 */
	public static function pop_context( $item_type ) {
		$item_type = sanitize_key( $item_type );

		if ( empty( self::$context_stack[ $item_type ] ) ) {
			return;
		}

		array_pop( self::$context_stack[ $item_type ] );
	}

	/**
	 * Whether a render override context is currently active for the type.
	 *
	 * @param string $item_type Content type slug.
	 * @return bool
	 */
	public static function has_active_context( $item_type ) {
		$item_type = sanitize_key( $item_type );

		return ! empty( self::$context_stack[ $item_type ] );
	}

	/**
	 * Whether display automation scopes engagement templates for this type.
	 *
	 * When an enabled rule sets an emoji/star template, the global engagement
	 * template must not bleed into other page contexts (e.g. blog archives).
	 *
	 * @param string $item_type Content type slug.
	 * @return bool
	 */
	public static function automation_scopes_engagement( $item_type ) {
		static $cache = array();

		$item_type = sanitize_key( $item_type );
		if ( isset( $cache[ $item_type ] ) ) {
			return $cache[ $item_type ];
		}

		$cache[ $item_type ] = false;

		if ( ! class_exists( 'WP_Ulike_Pro_Display_Automation' ) ) {
			return false;
		}

		foreach ( WP_Ulike_Pro_Display_Automation::get_active_rules() as $rule ) {
			$rule_type = WP_Ulike_Pro_Display_Automation::map_rule_item_type( $rule['content_type'] ?? 'post' );
			if ( $rule_type !== $item_type ) {
				continue;
			}

			$template = sanitize_key( (string) ( $rule['template'] ?? '' ) );
			if ( $template && self::is_engagement_template( $template ) ) {
				$cache[ $item_type ] = true;
				break;
			}
		}

		return $cache[ $item_type ];
	}

	/**
	 * @param string $item_type Content type slug.
	 * @return array<string, mixed>
	 */
	private static function get_active_context( $item_type ) {
		$item_type = sanitize_key( $item_type );

		if ( empty( self::$context_stack[ $item_type ] ) ) {
			return array();
		}

		$merged = array();

		foreach ( self::$context_stack[ $item_type ] as $layer ) {
			if ( ! is_array( $layer ) ) {
				continue;
			}

			foreach ( $layer as $key => $value ) {
				if ( '' === $value || null === $value ) {
					continue;
				}

				$merged[ $key ] = $value;
			}
		}

		return $merged;
	}

	/**
	 * Selected button template for a content type.
	 *
	 * @param string $item_type Content type slug.
	 * @return string
	 */
	public static function get_template_slug( $item_type ) {
		$context = self::get_active_context( $item_type );

		if ( ! empty( $context['template'] ) ) {
			return sanitize_key( (string) $context['template'] );
		}

		$slug = (string) self::get_type_option( $item_type, 'template', 'wpulike-default' );

		return $slug ? $slug : 'wpulike-default';
	}

	/**
	 * Whether a template slug is an engagement template.
	 *
	 * @param string $template_slug Template key.
	 * @return bool
	 */
	public static function is_engagement_template( $template_slug ) {
		return isset( self::get_engagement_template_map()[ $template_slug ] );
	}

	/**
	 * Engagement mode: none, emoji, star.
	 *
	 * @param string $item_type Content type slug.
	 * @return string
	 */
	public static function get_mode( $item_type ) {
		$template = self::get_template_slug( $item_type );
		$map      = self::get_engagement_template_map();

		if ( isset( $map[ $template ] ) ) {
			return (string) apply_filters( 'wp_ulike_pro_engagement_mode', $map[ $template ], $item_type );
		}

		return (string) apply_filters( 'wp_ulike_pro_engagement_mode', 'none', $item_type );
	}

	/**
	 * Whether engagements are enabled for a content type.
	 *
	 * @param string $item_type Content type slug.
	 * @return bool
	 */
	public static function is_enabled( $item_type ) {
		return self::get_mode( $item_type ) !== 'none';
	}

	/**
	 * Whether an engagement kind is allowed for a content type.
	 *
	 * Global settings may use a classic template while display automation / shortcodes
	 * render emoji/star. AJAX must call bootstrap_ajax_context() first so get_mode()
	 * reflects the posted engagement_template. Without a bootstrapped engagement
	 * mode, requests are denied (fail closed). Vote/engagers nonces are bound to the
	 * template so a client cannot enable a different engagement mode by spoofing it.
	 *
	 * @param string $item_type Content type slug.
	 * @param string $kind      emoji|star.
	 * @return bool
	 */
	public static function allows_engagement_kind( $item_type, $kind ) {
		$kind = wp_ulike_pro_sanitize_engagement_kind( (string) $kind );
		if ( ! $kind ) {
			return false;
		}

		$mode = self::get_mode( $item_type );

		return 'none' !== $mode && $kind === $mode;
	}

	/**
	 * Nonce action for engagement votes (bound to item + template).
	 *
	 * @param string $item_type Content type slug.
	 * @param int    $item_id   Item ID.
	 * @param string $template  Engagement template slug.
	 * @return string
	 */
	public static function get_vote_nonce_action( $item_type, $item_id, $template ) {
		return 'engagement_' . sanitize_key( $item_type ) . absint( $item_id ) . '_' . sanitize_key( $template );
	}

	/**
	 * Nonce action for engagers AJAX (bound to item + kind + template).
	 *
	 * @param string $item_type Content type slug.
	 * @param int    $item_id   Item ID.
	 * @param string $kind      emoji|star.
	 * @param string $template  Engagement template slug.
	 * @return string
	 */
	public static function get_engagers_nonce_action( $item_type, $item_id, $kind, $template ) {
		return 'engagement_engagers_' . sanitize_key( $item_type ) . absint( $item_id ) . '_' . sanitize_key( $kind ) . '_' . sanitize_key( $template );
	}

	/**
	 * Restore render overrides for a stateless AJAX request (display automation).
	 *
	 * Posted engagement_template is only accepted when it is a known engagement
	 * template and, if a kind is present, when that template maps to the kind.
	 * Callers must still verify a template-bound nonce for votes/engagers.
	 *
	 * @param string               $item_type Content type slug.
	 * @param array<string, mixed> $request   Request fields (engagement_template, …).
	 * @return bool Whether context was pushed (caller must pop).
	 */
	public static function bootstrap_ajax_context( $item_type, $request ) {
		$item_type = sanitize_key( $item_type );
		$context   = array();

		if ( ! empty( $request['engagement_template'] ) ) {
			$template = sanitize_key( (string) $request['engagement_template'] );
			$map      = self::get_engagement_template_map();

			if ( isset( $map[ $template ] ) ) {
				$kind = ! empty( $request['engagement_kind'] )
					? wp_ulike_pro_sanitize_engagement_kind( (string) $request['engagement_kind'] )
					: '';

				// Reject template/kind mismatches (e.g. star template with emoji kind).
				if ( ! $kind || $map[ $template ] === $kind ) {
					$context['template'] = $template;
				}
			}
		}

		if ( ! empty( $request['engagement_picker_style'] ) ) {
			$style = sanitize_key( (string) $request['engagement_picker_style'] );
			if ( 'click' === $style ) {
				$style = 'hover';
			}
			if ( in_array( $style, array( 'hover', 'inline' ), true ) ) {
				$context['engagement_picker_style'] = $style;
			}
		}

		if ( ! empty( $request['engagement_reactions'] ) && is_array( $request['engagement_reactions'] ) ) {
			$context['engagement_reactions'] = self::sanitize_reaction_slugs( $request['engagement_reactions'] );
		}

		if ( empty( $context ) ) {
			return false;
		}

		self::push_context( $item_type, $context );

		return true;
	}

	/**
	 * Default reaction slugs for new installs, quick-add presets, and pickers.
	 *
	 * @return string[]
	 */
	public static function get_default_reaction_slugs() {
		return array( 'like', 'love', 'celebrate', 'insightful', 'curious', 'support', 'funny', 'wow', 'fire', 'clap' );
	}

	/**
	 * Default reactions config for emoji_repeater (emoji + label; slug generated on save).
	 *
	 * @return array<int, array{slug:string,emoji:string,label:string}>
	 */
	public static function get_default_reactions_config() {
		$config = array();

		foreach ( self::get_default_reaction_slugs() as $slug ) {
			$reaction = WP_Ulike_Pro_Engagement_Registry::get_default_reactions()[ $slug ] ?? null;
			if ( ! $reaction ) {
				continue;
			}

			$config[] = array(
				'slug'  => $reaction['slug'],
				'emoji' => $reaction['emoji'],
				'label' => wp_strip_all_tags( $reaction['label'] ),
			);
		}

		return $config;
	}

	/**
	 * Normalized reactions for a content type (supports legacy button_set slug arrays).
	 *
	 * @param string $item_type Content type slug.
	 * @return array<int, array{slug:string,emoji:string,label:string}>
	 */
	public static function get_configured_reactions( $item_type ) {
		$config  = self::normalize_reactions_config(
			self::get_type_option( $item_type, 'engagement_reactions', self::get_default_reactions_config() )
		);
		$context = self::get_active_context( $item_type );

		if ( empty( $context['engagement_reactions'] ) || ! is_array( $context['engagement_reactions'] ) ) {
			return $config;
		}

		$override = $context['engagement_reactions'];

		// Display automation stores a slug subset.
		if ( ! is_array( reset( $override ) ) ) {
			$allowed  = array_flip( self::sanitize_reaction_slugs( $override ) );
			$filtered = array();

			foreach ( $config as $reaction ) {
				if ( ! empty( $reaction['slug'] ) && isset( $allowed[ $reaction['slug'] ] ) ) {
					$filtered[] = $reaction;
				}
			}

			return $filtered ? $filtered : $config;
		}

		return self::normalize_reactions_config( $override );
	}

	/**
	 * Selected reaction slugs for a content type.
	 *
	 * @param string $item_type Content type slug.
	 * @return string[]
	 */
	public static function get_selected_reaction_slugs( $item_type ) {
		$reactions = self::get_configured_reactions( $item_type );
		$slugs     = array();

		foreach ( $reactions as $reaction ) {
			if ( ! empty( $reaction['slug'] ) ) {
				$slugs[] = $reaction['slug'];
			}
		}

		return ! empty( $slugs ) ? $slugs : self::get_default_reaction_slugs();
	}

	/**
	 * Whether counters should be visible (uses Counter Display Condition + hide zero).
	 *
	 * Honors display-automation / shortcode overrides via render context.
	 *
	 * @param string   $item_type Content type slug.
	 * @param int|null $count     Optional current count to evaluate hide-zero.
	 * @return bool
	 */
	public static function show_counters( $item_type, $count = null ) {
		if ( ! self::counters_available( $item_type ) ) {
			return false;
		}

		if ( null !== $count && self::hide_zero_counters( $item_type ) && (int) $count === 0 ) {
			return false;
		}

		return (bool) apply_filters( 'wp_ulike_pro_engagement_show_counters', true, $item_type, $count );
	}

	/**
	 * Whether counter UI is allowed (Counter Display Condition + render overrides).
	 *
	 * @param string $item_type Content type slug.
	 * @return bool
	 */
	public static function counters_available( $item_type ) {
		$context = self::get_active_context( $item_type );

		if ( array_key_exists( 'display_counters', $context ) ) {
			return (bool) $context['display_counters'];
		}

		if ( ! class_exists( 'wp_ulike_setting_repo' ) ) {
			return true;
		}

		return wp_ulike_setting_repo::isCounterBoxVisible( $item_type );
	}

	/**
	 * Whether zero counts should be hidden in the UI.
	 *
	 * @param string $item_type Content type slug.
	 * @return bool
	 */
	public static function hide_zero_counters( $item_type ) {
		if ( ! class_exists( 'wp_ulike_setting_repo' ) ) {
			return false;
		}

		return wp_ulike_setting_repo::isCounterZeroHidden( $item_type );
	}

	/**
	 * Whether to show who reacted / rated (uses Enable Likers Box).
	 *
	 * Honors display-automation / shortcode overrides and anonymous likers restrict,
	 * matching classic like/dislike SSR behavior.
	 *
	 * @param string $item_type Content type slug.
	 * @return bool
	 */
	public static function show_engagers( $item_type ) {
		$context = self::get_active_context( $item_type );

		if ( array_key_exists( 'display_likers', $context ) ) {
			$enabled = wp_ulike_is_true( $context['display_likers'] );
		} else {
			$enabled = wp_ulike_is_true( self::get_type_option( $item_type, 'enable_likers_box', false ) );
		}

		if (
			$enabled
			&& class_exists( 'wp_ulike_setting_repo' )
			&& wp_ulike_setting_repo::restrictLikersBox( $item_type )
			&& ! is_user_logged_in()
		) {
			$enabled = false;
		}

		return (bool) apply_filters( 'wp_ulike_pro_engagement_show_engagers', $enabled, $item_type );
	}

	/**
	 * Likers box style from content-type settings (default|popover|pile).
	 *
	 * @param string $item_type Content type slug.
	 * @return string
	 */
	public static function get_likers_style( $item_type ) {
		$context = self::get_active_context( $item_type );

		if ( ! empty( $context['likers_style'] ) ) {
			$style = sanitize_key( (string) $context['likers_style'] );
		} else {
			$style = sanitize_key( (string) self::get_type_option( $item_type, 'likers_style', 'default' ) );
		}

		return in_array( $style, array( 'default', 'popover', 'pile' ), true ) ? $style : 'default';
	}

	/**
	 * Engagers display style for emoji/star templates.
	 *
	 * Reuses Enable Likers Box + Likers Style (default, popover, pile).
	 * Popover attaches to the engagers zone — not the reaction trigger — so it
	 * does not conflict with the emoji hover picker.
	 *
	 * @param string $item_type Content type slug.
	 * @return string default|popover|pile
	 */
	public static function get_engagers_style( $item_type ) {
		return (string) apply_filters( 'wp_ulike_pro_engagement_engagers_style', self::get_likers_style( $item_type ), $item_type );
	}

	/**
	 * Reaction picker interaction: hover (desktop hover / mobile long-press) or inline.
	 *
	 * @param string $item_type Content type slug.
	 * @return string hover|inline
	 */
	public static function get_picker_style( $item_type ) {
		$context = self::get_active_context( $item_type );

		if ( ! empty( $context['engagement_picker_style'] ) ) {
			$style = sanitize_key( (string) $context['engagement_picker_style'] );
		} else {
			$style = sanitize_key( (string) self::get_type_option( $item_type, 'engagement_picker_style', 'hover' ) );
		}

		// Legacy value from earlier builds.
		if ( 'click' === $style ) {
			$style = 'hover';
		}

		return in_array( $style, array( 'hover', 'inline' ), true ) ? $style : 'hover';
	}

	/**
	 * Normalized reactions config (emoji + label + auto slug).
	 *
	 * @param mixed $stored Raw option value.
	 * @return array<int, array{slug:string,emoji:string,label:string}>
	 */
	public static function normalize_reactions_config( $stored ) {
		if ( ! is_array( $stored ) ) {
			return self::get_default_reactions_config();
		}

		$registry = WP_Ulike_Pro_Engagement_Registry::get_default_reactions();

		// Legacy button_set stored plain slug strings.
		if ( ! empty( $stored ) && ! is_array( reset( $stored ) ) ) {
			$legacy = array();
			foreach ( (array) $stored as $slug ) {
				$slug = wp_ulike_pro_sanitize_engagement_key( (string) $slug );
				if ( ! $slug || ! isset( $registry[ $slug ] ) ) {
					continue;
				}
				$legacy[] = array(
					'slug'  => $slug,
					'emoji' => $registry[ $slug ]['emoji'],
					'label' => wp_strip_all_tags( $registry[ $slug ]['label'] ),
				);
			}
			return $legacy ? $legacy : self::get_default_reactions_config();
		}

		if ( empty( $stored ) ) {
			return self::get_default_reactions_config();
		}

		$out  = array();
		$used = array();

		foreach ( $stored as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$emoji = trim( wp_strip_all_tags( (string) ( $row['emoji'] ?? '' ) ) );
			// Stored as HTML entities on utf8mb3-safe saves (see wp_encode_emoji on save).
			if ( false !== strpos( $emoji, '&#' ) ) {
				$emoji = html_entity_decode( $emoji, ENT_QUOTES, 'UTF-8' );
			}
			$label = sanitize_text_field( (string) ( $row['label'] ?? '' ) );

			if ( '' === $emoji && '' === $label ) {
				continue;
			}

			$slug = ! empty( $row['slug'] ) ? wp_ulike_pro_sanitize_engagement_key( (string) $row['slug'] ) : '';
			if ( '' === $slug ) {
				$slug = self::resolve_reaction_slug( $emoji, $label, $registry, $used );
			} elseif ( isset( $registry[ $slug ] ) ) {
				$emoji = $registry[ $slug ]['emoji'];
			}

			while ( in_array( $slug, $used, true ) ) {
				$slug = self::resolve_reaction_slug( $emoji, $label, $registry, $used ) . '_' . ( count( $used ) + 1 );
				$slug = wp_ulike_pro_sanitize_engagement_key( $slug );
			}

			if ( '' === $emoji && isset( $registry[ $slug ] ) ) {
				$emoji = $registry[ $slug ]['emoji'];
			}
			if ( '' === $label ) {
				$label = isset( $registry[ $slug ] )
					? wp_strip_all_tags( $registry[ $slug ]['label'] )
					: sprintf(
						/* translators: %d: reaction number */
						esc_html__( 'Reaction %d', WP_ULIKE_PRO_DOMAIN ),
						count( $out ) + 1
					);
			}

			$used[] = $slug;
			$out[]  = array(
				'slug'  => $slug,
				'emoji' => function_exists( 'mb_substr' ) ? mb_substr( $emoji, 0, 8 ) : substr( $emoji, 0, 24 ),
				'label' => $label,
			);
		}

		return $out ? $out : self::get_default_reactions_config();
	}

	/**
	 * Sanitize selected reaction slugs (display automation subset).
	 *
	 * @param mixed $stored Raw stored value.
	 * @return string[]
	 */
	public static function sanitize_reaction_slugs( $stored ) {
		if ( is_array( $stored ) && ! empty( $stored ) && ! is_array( reset( $stored ) ) ) {
			return array_values(
				array_filter(
					array_map( 'wp_ulike_pro_sanitize_engagement_key', $stored )
				)
			);
		}

		$slugs = array();
		foreach ( self::normalize_reactions_config( $stored ) as $row ) {
			if ( ! empty( $row['slug'] ) ) {
				$slugs[] = $row['slug'];
			}
		}

		return $slugs ? $slugs : self::get_default_reaction_slugs();
	}

	/**
	 * Derive a stable reaction slug from label / catalog (emoji is secondary).
	 *
	 * @param string               $emoji    Reaction emoji.
	 * @param string               $label    Reaction label.
	 * @param array<string, mixed> $registry Default reaction catalog.
	 * @param string[]             $used     Slugs already used in this list.
	 * @return string
	 */
	private static function resolve_reaction_slug( $emoji, $label, $registry, $used ) {
		if ( $label ) {
			$label_key = sanitize_title( $label );
			if ( $label_key && isset( $registry[ $label_key ] ) ) {
				return $label_key;
			}

			foreach ( $registry as $slug => $def ) {
				if ( 0 === strcasecmp( wp_strip_all_tags( (string) ( $def['label'] ?? '' ) ), $label ) ) {
					return $slug;
				}
			}
		}

		if ( $emoji ) {
			foreach ( $registry as $slug => $def ) {
				if ( ( $def['emoji'] ?? '' ) === $emoji ) {
					return $slug;
				}
			}
		}

		$base = $label ? sanitize_title( $label ) : 'reaction';
		$slug = wp_ulike_pro_sanitize_engagement_key( $base ) ?: 'reaction';
		$n    = 2;

		while ( in_array( $slug, $used, true ) ) {
			$slug = wp_ulike_pro_sanitize_engagement_key( $base . '_' . $n ) ?: 'reaction_' . $n;
			++$n;
		}

		return $slug;
	}

	public static function get_panel_fields() {
		return array(
			'engagement_reactions' => array(
				'id'         => 'engagement_reactions',
				'type'       => 'emoji_repeater',
				'title'      => esc_html__( 'Reactions', WP_ULIKE_PRO_DOMAIN ),
				'desc'       => esc_html__( 'Pick emojis from the catalog and optional labels. Slugs are generated automatically for stats and logs.', WP_ULIKE_PRO_DOMAIN ),
				'default'    => self::get_default_reactions_config(),
				'min'        => 1,
				'max'        => 16,
				'dependency' => array( 'template', '==', self::TEMPLATE_EMOJI ),
			),
			'engagement_picker_style' => array(
				'id'         => 'engagement_picker_style',
				'type'       => 'button_set',
				'title'      => esc_html__( 'Reaction Picker', WP_ULIKE_PRO_DOMAIN ),
				'desc'       => esc_html__( 'Hover shows a LinkedIn-style bar on the trigger (hover on desktop, long-press on mobile). Inline displays all reactions in a row at all times.', WP_ULIKE_PRO_DOMAIN ),
				'default'     => 'hover',
				'allow_empty' => false,
				'options'    => array(
					'hover'  => esc_html__( 'Hover', WP_ULIKE_PRO_DOMAIN ),
					'inline' => esc_html__( 'Inline', WP_ULIKE_PRO_DOMAIN ),
				),
				'dependency' => array( 'template', '==', self::TEMPLATE_EMOJI ),
			),
		);
	}
}

