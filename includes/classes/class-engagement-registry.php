<?php
/**
 * Engagement reaction registry and defaults.
 *
 * @package WP ULike Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WP_Ulike_Pro_Engagement_Registry {

	/**
	 * Default LinkedIn-style emoji reactions (fixed slugs).
	 *
	 * @return array<string, array{slug:string,emoji:string,label:string,weight:int}>
	 */
	public static function get_default_reactions() {
		$reactions = array(
			'like'       => array(
				'slug'   => 'like',
				'emoji'  => "\xF0\x9F\x91\x8D",
				'label'  => esc_html__( 'Like', WP_ULIKE_PRO_DOMAIN ),
				'weight' => 1,
			),
			'love'       => array(
				'slug'   => 'love',
				'emoji'  => "\xE2\x9D\xA4\xEF\xB8\x8F",
				'label'  => esc_html__( 'Love', WP_ULIKE_PRO_DOMAIN ),
				'weight' => 2,
			),
			'celebrate'  => array(
				'slug'   => 'celebrate',
				'emoji'  => "\xF0\x9F\x8E\x89",
				'label'  => esc_html__( 'Celebrate', WP_ULIKE_PRO_DOMAIN ),
				'weight' => 2,
			),
			'insightful' => array(
				'slug'   => 'insightful',
				'emoji'  => "\xF0\x9F\x92\xA1",
				'label'  => esc_html__( 'Insightful', WP_ULIKE_PRO_DOMAIN ),
				'weight' => 1,
			),
			'curious'    => array(
				'slug'   => 'curious',
				'emoji'  => "\xF0\x9F\xA4\x94",
				'label'  => esc_html__( 'Curious', WP_ULIKE_PRO_DOMAIN ),
				'weight' => 0,
			),
			'support'    => array(
				'slug'   => 'support',
				'emoji'  => "\xF0\x9F\x99\x8C",
				'label'  => esc_html__( 'Support', WP_ULIKE_PRO_DOMAIN ),
				'weight' => 1,
			),
			'funny'      => array(
				'slug'   => 'funny',
				'emoji'  => "\xF0\x9F\x98\x82",
				'label'  => esc_html__( 'Funny', WP_ULIKE_PRO_DOMAIN ),
				'weight' => 1,
			),
			'wow'        => array(
				'slug'   => 'wow',
				'emoji'  => "\xF0\x9F\x98\xAE",
				'label'  => esc_html__( 'Wow', WP_ULIKE_PRO_DOMAIN ),
				'weight' => 1,
			),
			'fire'       => array(
				'slug'   => 'fire',
				'emoji'  => "\xF0\x9F\x94\xA5",
				'label'  => esc_html__( 'Fire', WP_ULIKE_PRO_DOMAIN ),
				'weight' => 1,
			),
			'sad'        => array(
				'slug'   => 'sad',
				'emoji'  => "\xF0\x9F\x98\xA2",
				'label'  => esc_html__( 'Sad', WP_ULIKE_PRO_DOMAIN ),
				'weight' => 0,
			),
			'angry'      => array(
				'slug'   => 'angry',
				'emoji'  => "\xF0\x9F\x98\xA1",
				'label'  => esc_html__( 'Angry', WP_ULIKE_PRO_DOMAIN ),
				'weight' => 0,
			),
			'clap'       => array(
				'slug'   => 'clap',
				'emoji'  => "\xF0\x9F\x91\x8F",
				'label'  => esc_html__( 'Clap', WP_ULIKE_PRO_DOMAIN ),
				'weight' => 1,
			),
			'hundred'    => array(
				'slug'   => 'hundred',
				'emoji'  => "\xF0\x9F\x92\xAF",
				'label'  => esc_html__( '100', WP_ULIKE_PRO_DOMAIN ),
				'weight' => 0,
			),
			'rocket'     => array(
				'slug'   => 'rocket',
				'emoji'  => "\xF0\x9F\x9A\x80",
				'label'  => esc_html__( 'Rocket', WP_ULIKE_PRO_DOMAIN ),
				'weight' => 1,
			),
			'heart_eyes' => array(
				'slug'   => 'heart_eyes',
				'emoji'  => "\xF0\x9F\x98\x8D",
				'label'  => esc_html__( 'Heart Eyes', WP_ULIKE_PRO_DOMAIN ),
				'weight' => 1,
			),
			'thanks'     => array(
				'slug'   => 'thanks',
				'emoji'  => "\xF0\x9F\x99\x8F",
				'label'  => esc_html__( 'Thanks', WP_ULIKE_PRO_DOMAIN ),
				'weight' => 1,
			),
			'cool'       => array(
				'slug'   => 'cool',
				'emoji'  => "\xF0\x9F\x98\x8E",
				'label'  => esc_html__( 'Cool', WP_ULIKE_PRO_DOMAIN ),
				'weight' => 1,
			),
			'mind_blown' => array(
				'slug'   => 'mind_blown',
				'emoji'  => "\xF0\x9F\xA4\xAF",
				'label'  => esc_html__( 'Mind Blown', WP_ULIKE_PRO_DOMAIN ),
				'weight' => 1,
			),
			'party'      => array(
				'slug'   => 'party',
				'emoji'  => "\xF0\x9F\xA5\xB3",
				'label'  => esc_html__( 'Party', WP_ULIKE_PRO_DOMAIN ),
				'weight' => 1,
			),
			'hug'        => array(
				'slug'   => 'hug',
				'emoji'  => "\xF0\x9F\xA4\x97",
				'label'  => esc_html__( 'Hug', WP_ULIKE_PRO_DOMAIN ),
				'weight' => 1,
			),
			'muscle'     => array(
				'slug'   => 'muscle',
				'emoji'  => "\xF0\x9F\x92\xAA",
				'label'  => esc_html__( 'Strong', WP_ULIKE_PRO_DOMAIN ),
				'weight' => 1,
			),
			'star'       => array(
				'slug'   => 'star',
				'emoji'  => "\xE2\xAD\x90",
				'label'  => esc_html__( 'Star', WP_ULIKE_PRO_DOMAIN ),
				'weight' => 1,
			),
			'eyes'       => array(
				'slug'   => 'eyes',
				'emoji'  => "\xF0\x9F\x91\x80",
				'label'  => esc_html__( 'Eyes', WP_ULIKE_PRO_DOMAIN ),
				'weight' => 0,
			),
			'check'      => array(
				'slug'   => 'check',
				'emoji'  => "\xE2\x9C\x85",
				'label'  => esc_html__( 'Check', WP_ULIKE_PRO_DOMAIN ),
				'weight' => 0,
			),
			'thumbs_down' => array(
				'slug'   => 'thumbs_down',
				'emoji'  => "\xF0\x9F\x91\x8E",
				'label'  => esc_html__( 'Dislike', WP_ULIKE_PRO_DOMAIN ),
				'weight' => 0,
			),
			'broken_heart' => array(
				'slug'   => 'broken_heart',
				'emoji'  => "\xF0\x9F\x92\x94",
				'label'  => esc_html__( 'Broken Heart', WP_ULIKE_PRO_DOMAIN ),
				'weight' => 0,
			),
			'sparkles'   => array(
				'slug'   => 'sparkles',
				'emoji'  => "\xE2\x9C\xA8",
				'label'  => esc_html__( 'Sparkles', WP_ULIKE_PRO_DOMAIN ),
				'weight' => 1,
			),
			'trophy'     => array(
				'slug'   => 'trophy',
				'emoji'  => "\xF0\x9F\x8F\x86",
				'label'  => esc_html__( 'Trophy', WP_ULIKE_PRO_DOMAIN ),
				'weight' => 1,
			),
			'pray'       => array(
				'slug'   => 'pray',
				'emoji'  => "\xF0\x9F\x99\x8F",
				'label'  => esc_html__( 'Pray', WP_ULIKE_PRO_DOMAIN ),
				'weight' => 1,
			),
			'ok'         => array(
				'slug'   => 'ok',
				'emoji'  => "\xF0\x9F\x91\x8C",
				'label'  => esc_html__( 'OK', WP_ULIKE_PRO_DOMAIN ),
				'weight' => 0,
			),
		);

		return apply_filters( 'wp_ulike_pro_engagement_default_reactions', $reactions );
	}

	/**
	 * Enabled reactions for a content type (subset of defaults by slug).
	 *
	 * @param string $item_type Content type slug.
	 * @return array
	 */
	public static function get_enabled_reactions( $item_type ) {
		$configured = WP_Ulike_Pro_Engagement_Settings::get_configured_reactions( $item_type );
		$enabled    = array();

		$defaults = self::get_default_reactions();

		foreach ( $configured as $reaction ) {
			if ( empty( $reaction['slug'] ) || empty( $reaction['emoji'] ) ) {
				continue;
			}

			$slug = $reaction['slug'];
			$enabled[ $slug ] = array(
				'slug'   => $slug,
				'emoji'  => $reaction['emoji'],
				'label'  => $reaction['label'],
				'weight' => isset( $defaults[ $slug ]['weight'] ) ? (int) $defaults[ $slug ]['weight'] : 0,
			);
		}

		if ( empty( $enabled ) ) {
			foreach ( array_slice( self::get_default_reactions(), 0, 5, true ) as $slug => $reaction ) {
				$enabled[ $slug ] = $reaction;
			}
		}

		return apply_filters( 'wp_ulike_pro_engagement_enabled_reactions', $enabled, $item_type );
	}

	/**
	 * Single reaction definition.
	 *
	 * @param string $slug Reaction slug.
	 * @param string $item_type Content type slug.
	 * @return array|null
	 */
	public static function get_reaction( $slug, $item_type = 'post' ) {
		$slug      = wp_ulike_pro_sanitize_engagement_key( (string) $slug );
		$reactions = self::get_enabled_reactions( $item_type );

		if ( isset( $reactions[ $slug ] ) ) {
			return $reactions[ $slug ];
		}

		$defaults = self::get_default_reactions();

		return isset( $defaults[ $slug ] ) ? $defaults[ $slug ] : null;
	}

	/**
	 * Star rating configuration.
	 *
	 * @param string $item_type Content type slug.
	 * @return array{max:int,key:string}
	 */
	public static function get_star_config( $item_type ) {
		$config = array(
			'max' => 5,
			'key' => 'overall',
		);

		return apply_filters( 'wp_ulike_pro_engagement_star_config', $config, $item_type );
	}

	/**
	 * Sentiment polarity for an emoji reaction slug.
	 *
	 * Uses catalog weight (>0 = positive) plus an explicit negative-slug list
	 * for reactions that carry weight 0 but are clearly negative.
	 *
	 * @param string $slug Reaction slug.
	 * @return string positive|negative|neutral
	 */
	public static function get_reaction_polarity( $slug ) {
		$slug = function_exists( 'wp_ulike_pro_sanitize_engagement_key' )
			? wp_ulike_pro_sanitize_engagement_key( (string) $slug )
			: sanitize_key( (string) $slug );

		if ( '' === $slug ) {
			return 'neutral';
		}

		$negative_slugs = apply_filters(
			'wp_ulike_pro_engagement_negative_reaction_slugs',
			array( 'angry', 'sad', 'thumbs_down', 'broken_heart' )
		);

		if ( in_array( $slug, (array) $negative_slugs, true ) ) {
			return 'negative';
		}

		$defaults = self::get_default_reactions();
		if ( isset( $defaults[ $slug ] ) ) {
			$weight = isset( $defaults[ $slug ]['weight'] ) ? (int) $defaults[ $slug ]['weight'] : 0;
			if ( $weight > 0 ) {
				return 'positive';
			}

			return 'neutral';
		}

		/**
		 * Polarity for custom / unknown reaction slugs.
		 *
		 * @param string $polarity Default positive.
		 * @param string $slug     Reaction slug.
		 */
		return apply_filters( 'wp_ulike_pro_engagement_reaction_polarity', 'positive', $slug );
	}

	/**
	 * Sentiment polarity for a star rating value.
	 *
	 * Defaults (max=5): 4–5 positive, 1–2 negative, 3 neutral.
	 *
	 * @param int $value Star value.
	 * @param int $max   Scale maximum.
	 * @return string positive|negative|neutral
	 */
	public static function get_star_polarity( $value, $max = 5 ) {
		$value = absint( $value );
		$max   = max( 1, absint( $max ) );

		if ( $value <= 0 ) {
			return 'neutral';
		}

		$positive_min = (int) apply_filters(
			'wp_ulike_pro_star_positive_min',
			(int) ceil( $max * 0.8 ),
			$max
		);
		$negative_max = (int) apply_filters(
			'wp_ulike_pro_star_negative_max',
			(int) floor( $max * 0.4 ),
			$max
		);

		$positive_min = max( 1, min( $max, $positive_min ) );
		$negative_max = max( 0, min( $max, $negative_max ) );

		if ( $value >= $positive_min ) {
			return 'positive';
		}

		if ( $value <= $negative_max ) {
			return 'negative';
		}

		return 'neutral';
	}
}

