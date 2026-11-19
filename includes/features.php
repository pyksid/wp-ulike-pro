<?php
/**
 * Feature switches for Pro modules.
 *
 * Registers each module with the free plugin's feature registry, which renders
 * the switches on Overview.
 *
 * A switch means "do I use this module at all" and defaults to on, so every tab
 * stays exactly where it was until someone chooses otherwise. It is deliberately
 * not wired to settings like `enable_user_profiles`: those record whether the
 * module is *configured*, and reading them here would show the switch off on a
 * site that simply has not set profiles up yet — then hide the very settings
 * needed to set them up. The module's own settings still decide behaviour; this
 * only decides whether the module is present.
 *
 *
 * @package    wp-ulike-pro
 * @author     TechnoWich 2026
 * @link       https://wpulike.com
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'wp_ulike_pro_settings_tab_url' ) ) {
	/**
	 * URL of a settings tab.
	 *
	 * @param string $tab Settings page id.
	 * @return string
	 */
	function wp_ulike_pro_settings_tab_url( $tab ) {
		return admin_url( 'admin.php?page=wp-ulike-settings&settings-page=' . rawurlencode( $tab ) );
	}
}

if ( ! function_exists( 'wp_ulike_pro_register_features' ) ) {
	/**
	 * Add Pro modules to the feature registry.
	 *
	 * @param array $features Registered features.
	 * @return array
	 */
	function wp_ulike_pro_register_features( $features ) {
		if ( ! is_array( $features ) ) {
			$features = array();
		}

		$features['display_automation'] = array(
			'label'       => __( 'Display Automation', 'wp-ulike-pro' ),
			'description' => __( 'Start here to place like buttons automatically—no shortcodes needed.', 'wp-ulike-pro' ),
			'icon'        => 'layout',
			'badge'       => __( 'Pro', 'wp-ulike-pro' ),
			'url'         => admin_url( 'admin.php?page=wp-ulike-pro-tools&tab=display-automation' ),
		);

		$features['profiles'] = array(
			'label'       => __( 'User Profiles', 'wp-ulike-pro' ),
			'description' => __( 'Public profile pages, permalinks, and author redirects.', 'wp-ulike-pro' ),
			'icon'        => 'admin-users',
			'badge'       => __( 'Pro', 'wp-ulike-pro' ),
			'url'         => wp_ulike_pro_settings_tab_url( 'profiles' ),
			// Profile URLs are rewrite rules. With the module off they are not
			// registered, so links already published to those pages 404.
			'warning'     => __( 'Turning User Profiles off removes the profile page URLs. Any links already published to a profile will stop working until it is switched back on. Turn it off?', 'wp-ulike-pro' ),
		);

		$features['login_signup'] = array(
			'label'       => __( 'Login & Signup', 'wp-ulike-pro' ),
			'description' => __( 'Assign login and signup pages, then add forms with shortcodes.', 'wp-ulike-pro' ),
			'icon'        => 'privacy',
			'badge'       => __( 'Pro', 'wp-ulike-pro' ),
			'url'         => wp_ulike_pro_settings_tab_url( 'login-signup' ),
			// The custom pages render nothing without the forms, so a site that
			// sends visitors there needs to know before the forms disappear.
			'warning'     => __( 'Turning Login & Signup off stops the custom login, signup, and account forms from rendering. Pages built around those shortcodes will come up empty, and wp-login.php takes over sign-in again. Turn it off?', 'wp-ulike-pro' ),
		);

		$features['social_logins'] = array(
			'label'       => __( 'Social Logins', 'wp-ulike-pro' ),
			'description' => __( 'Optional OAuth sign-in alongside your login forms.', 'wp-ulike-pro' ),
			'icon'        => 'share',
			'badge'       => __( 'Pro', 'wp-ulike-pro' ),
			'url'         => wp_ulike_pro_settings_tab_url( 'social-logins' ),
		);

		$features['share_buttons'] = array(
			'label'       => __( 'Share Buttons', 'wp-ulike-pro' ),
			'description' => __( 'Optional social share sets via shortcode or auto display.', 'wp-ulike-pro' ),
			'icon'        => 'share-alt2',
			'badge'       => __( 'Pro', 'wp-ulike-pro' ),
			'url'         => wp_ulike_pro_settings_tab_url( 'share-buttons' ),
		);

		$features['schema'] = array(
			'label'       => __( 'Schema markup', 'wp-ulike-pro' ),
			'description' => __( 'Rating and FAQ structured data for search engines.', 'wp-ulike-pro' ),
			'icon'        => 'search',
			'badge'       => __( 'Pro', 'wp-ulike-pro' ),
			'url'         => admin_url( 'admin.php?page=wp-ulike-pro-tools&tab=schema-generator' ),
		);

		return $features;
	}
	add_filter( 'wp_ulike_features', 'wp_ulike_pro_register_features' );
}

if ( ! function_exists( 'wp_ulike_pro_feature_enabled' ) ) {
	/**
	 * Whether a Pro module is switched on.
	 *
	 * Falls back to enabled when the free plugin predates the feature registry,
	 * so an older free release never silently disables Pro modules.
	 *
	 * @param string $key Feature slug.
	 * @return bool
	 */
	function wp_ulike_pro_feature_enabled( $key ) {
		if ( ! function_exists( 'wp_ulike_is_feature_enabled' ) ) {
			return true;
		}

		return wp_ulike_is_feature_enabled( $key );
	}
}

if ( ! function_exists( 'wp_ulike_pro_add_feature_shortcode' ) ) {
	/**
	 * Register a shortcode that belongs to a switchable module.
	 *
	 * A disabled module still registers its tags, mapped to an empty callback:
	 * leaving them unregistered would print the raw `[shortcode]` text on the
	 * page instead of quietly rendering nothing.
	 *
	 * @param string          $feature  Feature slug.
	 * @param string          $tag      Shortcode tag.
	 * @param callable|string $callback Shortcode callback.
	 * @return void
	 */
	function wp_ulike_pro_add_feature_shortcode( $feature, $tag, $callback ) {
		add_shortcode( $tag, wp_ulike_pro_feature_enabled( $feature ) ? $callback : '__return_empty_string' );
	}
}

if ( ! function_exists( 'wp_ulike_pro_flush_rewrite_on_feature_change' ) ) {
	/**
	 * Queue a permalink rebuild when the Profiles switch changes.
	 *
	 * Flushing here would persist whatever rules init registered from the old
	 * switch. The next request adds (or omits) rules first, then flushes.
	 *
	 * @param array $changed Feature keys whose state changed.
	 * @return void
	 */
	function wp_ulike_pro_flush_rewrite_on_feature_change( $changed ) {
		if ( ! is_array( $changed ) || ! in_array( 'profiles', $changed, true ) ) {
			return;
		}

		// Do not flush on this request. init already registered rules from the
		// old switch; flushing now would persist that stale set. Flag the next
		// request so WP_Ulike_Pro_Rewrite can add (or omit) rules first.
		if ( function_exists( 'wp_ulike_pro_reset_rules' ) ) {
			wp_ulike_pro_reset_rules();
		} else {
			update_option( 'wp_ulike_pro_flush_rewrite_rules', 1 );
		}
	}
	add_action( 'wp_ulike_features_saved', 'wp_ulike_pro_flush_rewrite_on_feature_change' );
}

