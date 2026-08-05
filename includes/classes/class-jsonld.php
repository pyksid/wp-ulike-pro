<?php
/**
 * Lightweight JSON-LD builder for Schema.org structured data.
 *
 *
 * @package    wp-ulike-pro
 * @author     TechnoWich 2026
 * @link       https://wpulike.com
 */

// no direct access allowed
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Builds valid Schema.org JSON-LD without external dependencies.
 */
class WP_Ulike_Pro_JsonLd implements JsonSerializable {

	/**
	 * Schema.org vocabulary context URL.
	 */
	const CONTEXT = 'https://schema.org';

	/**
	 * Root @type value.
	 *
	 * @var string
	 */
	private $type;

	/**
	 * Entity properties (excluding @context and @type).
	 *
	 * @var array<string, mixed>
	 */
	private $properties = array();

	/**
	 * Whether @context is included in toArray() output.
	 *
	 * @var bool
	 */
	private $include_context = true;

	/**
	 * Create a root JSON-LD entity.
	 *
	 * @param string $type Schema.org type name.
	 */
	public function __construct( $type ) {
		$this->type = (string) $type;
	}

	/**
	 * Create a root JSON-LD entity.
	 *
	 * @param string $type Schema.org type name.
	 * @return self
	 */
	public static function create( $type ) {
		return new self( $type );
	}

	/**
	 * Create a nested typed entity (no @context).
	 *
	 * @param string               $type       Schema.org type name.
	 * @param array<string, mixed> $properties Property map.
	 * @return self
	 */
	public static function nested( $type, $properties = array() ) {
		$entity = new self( $type );
		$entity->include_context = false;

		foreach ( $properties as $key => $value ) {
			$entity->set( $key, $value );
		}

		return $entity;
	}

	/**
	 * Person helper.
	 *
	 * @param mixed $name Person name.
	 * @return self|null
	 */
	public static function person( $name = null ) {
		$name = self::stringValue( $name );
		if ( '' === $name ) {
			return null;
		}

		return self::nested( 'Person', array( 'name' => $name ) );
	}

	/**
	 * Organization helper.
	 *
	 * @param mixed $name Organization name.
	 * @return self|null
	 */
	public static function organization( $name = null ) {
		$name = self::stringValue( $name );
		if ( '' === $name ) {
			return null;
		}

		return self::nested( 'Organization', array( 'name' => $name ) );
	}

	/**
	 * Brand helper (recommended for Product.brand).
	 *
	 * @param mixed $name Brand name.
	 * @return self|null
	 */
	public static function brand( $name = null ) {
		$name = self::stringValue( $name );
		if ( '' === $name ) {
			return null;
		}

		return self::nested( 'Brand', array( 'name' => $name ) );
	}

	/**
	 * PostalAddress helper.
	 *
	 * @param array<string, mixed> $fields Address fields.
	 * @return self|null
	 */
	public static function buildPostalAddress( $fields ) {
		$address = self::nested(
			'PostalAddress',
			array(
				'streetAddress'     => self::stringValue( $fields['street_address'] ?? $fields['streetAddress'] ?? '' ),
				'addressLocality'   => self::stringValue( $fields['address_locality'] ?? $fields['addressLocality'] ?? '' ),
				'addressRegion'     => self::stringValue( $fields['address_region'] ?? $fields['addressRegion'] ?? '' ),
				'postalCode'        => self::stringValue( $fields['postal_code'] ?? $fields['postalCode'] ?? '' ),
				'addressCountry'    => self::stringValue( $fields['address_country'] ?? $fields['addressCountry'] ?? '' ),
			)
		);

		return $address->hasProperties() ? $address : null;
	}

	/**
	 * Offer helper with enumeration normalization.
	 *
	 * @param array<string, mixed> $fields Offer fields.
	 * @return self|null
	 */
	public static function buildOffer( $fields ) {
		$properties = array();

		if ( isset( $fields['price'] ) && '' !== self::stringValue( $fields['price'] ) ) {
			$properties['price'] = self::stringValue( $fields['price'] );
		}

		if ( isset( $fields['priceCurrency'] ) && '' !== self::stringValue( $fields['priceCurrency'] ) ) {
			$properties['priceCurrency'] = self::stringValue( $fields['priceCurrency'] );
		}

		if ( isset( $fields['price_currency'] ) && '' !== self::stringValue( $fields['price_currency'] ) ) {
			$properties['priceCurrency'] = self::stringValue( $fields['price_currency'] );
		}

		if ( isset( $fields['url'] ) && '' !== self::stringValue( $fields['url'] ) ) {
			$properties['url'] = esc_url_raw( self::stringValue( $fields['url'] ) );
		}

		if ( isset( $fields['validFrom'] ) && '' !== self::stringValue( $fields['validFrom'] ) ) {
			$properties['validFrom'] = self::stringValue( $fields['validFrom'] );
		}

		if ( isset( $fields['valid_from'] ) && '' !== self::stringValue( $fields['valid_from'] ) ) {
			$properties['validFrom'] = self::stringValue( $fields['valid_from'] );
		}

		if ( isset( $fields['priceValidUntil'] ) && '' !== self::stringValue( $fields['priceValidUntil'] ) ) {
			$properties['priceValidUntil'] = self::stringValue( $fields['priceValidUntil'] );
		}

		if ( isset( $fields['price_valid_until'] ) && '' !== self::stringValue( $fields['price_valid_until'] ) ) {
			$properties['priceValidUntil'] = self::stringValue( $fields['price_valid_until'] );
		}

		if ( isset( $fields['availability'] ) && '' !== self::stringValue( $fields['availability'] ) ) {
			$properties['availability'] = self::schemaUrl( self::stringValue( $fields['availability'] ) );
		}

		if ( empty( $properties ) ) {
			return null;
		}

		return self::nested( 'Offer', $properties );
	}

	/**
	 * AggregateRating helper.
	 *
	 * @param array<string, mixed> $fields Rating fields.
	 * @return self|null
	 */
	public static function buildAggregateRating( $fields ) {
		$properties = array();

		if ( isset( $fields['worstRating'] ) && '' !== self::stringValue( $fields['worstRating'] ) ) {
			$properties['worstRating'] = self::numericValue( $fields['worstRating'] );
		}

		if ( isset( $fields['worst'] ) && '' !== self::stringValue( $fields['worst'] ) ) {
			$properties['worstRating'] = self::numericValue( $fields['worst'] );
		}

		if ( isset( $fields['bestRating'] ) && '' !== self::stringValue( $fields['bestRating'] ) ) {
			$properties['bestRating'] = self::numericValue( $fields['bestRating'] );
		}

		if ( isset( $fields['best'] ) && '' !== self::stringValue( $fields['best'] ) ) {
			$properties['bestRating'] = self::numericValue( $fields['best'] );
		}

		if ( isset( $fields['ratingValue'] ) && '' !== self::stringValue( $fields['ratingValue'] ) ) {
			$properties['ratingValue'] = self::numericValue( $fields['ratingValue'] );
		}

		if ( isset( $fields['value'] ) && '' !== self::stringValue( $fields['value'] ) ) {
			$properties['ratingValue'] = self::numericValue( $fields['value'] );
		}

		if ( isset( $fields['ratingCount'] ) && '' !== self::stringValue( $fields['ratingCount'] ) ) {
			$properties['ratingCount'] = (int) $fields['ratingCount'];
		}

		if ( isset( $fields['count'] ) && '' !== self::stringValue( $fields['count'] ) ) {
			$properties['ratingCount'] = (int) $fields['count'];
		}

		if ( empty( $properties ) ) {
			return null;
		}

		return self::nested( 'AggregateRating', $properties );
	}

	/**
	 * Rating helper (used inside Review.reviewRating).
	 *
	 * @param array<string, mixed> $fields Rating fields.
	 * @return self|null
	 */
	public static function buildRating( $fields ) {
		$properties = array();

		$rating_value = $fields['ratingValue'] ?? $fields['rating_value'] ?? '';
		if ( '' === self::stringValue( $rating_value ) ) {
			return null;
		}

		$properties['ratingValue'] = self::numericValue( $rating_value );

		$worst = $fields['worstRating'] ?? $fields['worst_rating'] ?? $fields['worst'] ?? 1;
		$best  = $fields['bestRating'] ?? $fields['best_rating'] ?? $fields['best'] ?? 5;

		$properties['worstRating'] = self::numericValue( $worst );
		$properties['bestRating']    = self::numericValue( $best );

		return self::nested( 'Rating', $properties );
	}

	/**
	 * Review helper.
	 *
	 * @param array<string, mixed> $fields Review fields.
	 * @return self|null
	 */
	public static function buildReview( $fields ) {
		$properties = array();

		$author = self::person( $fields['author'] ?? '' );
		if ( $author ) {
			$properties['author'] = $author;
		}

		$name = self::stringValue( $fields['name'] ?? '' );
		if ( '' !== $name ) {
			$properties['name'] = $name;
		}

		$review_body = self::stringValue( $fields['review_body'] ?? $fields['reviewBody'] ?? '' );
		if ( '' !== $review_body ) {
			$properties['reviewBody'] = $review_body;
		}

		$date = self::stringValue( $fields['published_date'] ?? $fields['datePublished'] ?? '' );
		if ( '' !== $date ) {
			$properties['datePublished'] = $date;
		}

		$rating = self::buildRating(
			array(
				'rating_value' => $fields['rating_value'] ?? $fields['ratingValue'] ?? '',
				'worst_rating' => $fields['worst_rating'] ?? $fields['worstRating'] ?? $fields['worst'] ?? '',
				'best_rating'  => $fields['best_rating'] ?? $fields['bestRating'] ?? $fields['best'] ?? '',
			)
		);
		if ( $rating ) {
			$properties['reviewRating'] = $rating;
		}

		if ( empty( $properties ) ) {
			return null;
		}

		return self::nested( 'Review', $properties );
	}

	/**
	 * OpeningHoursSpecification helper.
	 *
	 * @param string $day    Day of week.
	 * @param string $opens  Opening time.
	 * @param string $closes Closing time.
	 * @return self|null
	 */
	public static function buildOpeningHoursSpecification( $day, $opens = '', $closes = '' ) {
		$day = self::stringValue( $day );
		if ( '' === $day ) {
			return null;
		}

		$properties = array(
			'dayOfWeek' => self::schemaUrl( $day ),
		);

		$opens = self::stringValue( $opens );
		if ( '' !== $opens ) {
			$properties['opens'] = $opens;
		}

		$closes = self::stringValue( $closes );
		if ( '' !== $closes ) {
			$properties['closes'] = $closes;
		}

		return self::nested( 'OpeningHoursSpecification', $properties );
	}

	/**
	 * Set a property value.
	 *
	 * @param string $key   Property name.
	 * @param mixed  $value Property value.
	 * @return self
	 */
	public function set( $key, $value ) {
		$key = (string) $key;
		if ( '' === $key ) {
			return $this;
		}

		$normalized = self::normalizeValue( $value );
		if ( self::isEmptyValue( $normalized ) ) {
			unset( $this->properties[ $key ] );
			return $this;
		}

		$this->properties[ $key ] = $normalized;
		return $this;
	}

	/**
	 * Fluent property setter (Schema.org camelCase property names).
	 *
	 * @param string $method Method name.
	 * @param array  $args   Method arguments.
	 * @return self
	 */
	public function __call( $method, $args ) {
		if ( empty( $args ) ) {
			return $this;
		}

		return $this->set( $method, $args[0] );
	}

	/**
	 * Whether the entity has any properties set.
	 *
	 * @return bool
	 */
	public function hasProperties() {
		return ! empty( $this->properties );
	}

	/**
	 * Nested entity array (no @context).
	 *
	 * @return array<string, mixed>
	 */
	public function toNestedArray() {
		$data = array(
			'@type' => $this->type,
		);

		foreach ( $this->properties as $key => $value ) {
			$data[ $key ] = $value;
		}

		return $data;
	}

	/**
	 * Full JSON-LD document array.
	 *
	 * @return array<string, mixed>
	 */
	public function toArray() {
		$data = array(
			'@type' => $this->type,
		);

		if ( $this->include_context ) {
			$data = array(
				'@context' => self::CONTEXT,
				'@type'    => $this->type,
			);
		}

		foreach ( $this->properties as $key => $value ) {
			$data[ $key ] = $value;
		}

		return $data;
	}

	/**
	 * JSON-LD script tag.
	 *
	 * @return string
	 */
	public function toScript() {
		return '<script type="application/ld+json">' . wp_json_encode(
			$this->toArray(),
			JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
		) . '</script>';
	}

	/**
	 * @return array<string, mixed>
	 */
	#[\ReturnTypeWillChange]
	public function jsonSerialize() {
		return $this->toArray();
	}

	/**
	 * @return string
	 */
	public function __toString() {
		return $this->toScript();
	}

	/**
	 * Normalize values for JSON encoding.
	 *
	 * @param mixed $value Raw value.
	 * @return mixed
	 */
	private static function normalizeValue( $value ) {
		if ( $value instanceof self ) {
			return $value->toNestedArray();
		}

		if ( is_array( $value ) ) {
			$normalized = array();

			foreach ( $value as $item ) {
				$item = self::normalizeValue( $item );
				if ( ! self::isEmptyValue( $item ) ) {
					$normalized[] = $item;
				}
			}

			return $normalized;
		}

		if ( is_bool( $value ) || is_int( $value ) || is_float( $value ) ) {
			return $value;
		}

		if ( is_string( $value ) ) {
			return trim( $value );
		}

		return $value;
	}

	/**
	 * Whether a normalized value should be omitted.
	 *
	 * @param mixed $value Normalized value.
	 * @return bool
	 */
	private static function isEmptyValue( $value ) {
		if ( null === $value ) {
			return true;
		}

		if ( is_string( $value ) && '' === $value ) {
			return true;
		}

		if ( is_array( $value ) && empty( $value ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Convert enumeration token to schema.org URL.
	 *
	 * @param string $token Enumeration token or URL.
	 * @return string
	 */
	public static function schemaUrl( $token ) {
		$token = trim( (string) $token );
		if ( '' === $token ) {
			return '';
		}

		if ( preg_match( '#^https?://#i', $token ) ) {
			return $token;
		}

		return self::CONTEXT . '/' . ltrim( $token, '/' );
	}

	/**
	 * Normalize application category to schema.org URL when applicable.
	 *
	 * @param mixed $value Stored category value.
	 * @return string
	 */
	public static function normalizeApplicationCategory( $value ) {
		$value = self::stringValue( $value );
		if ( '' === $value ) {
			return '';
		}

		if ( preg_match( '#^https?://#i', $value ) ) {
			return $value;
		}

		if ( preg_match( '/Application$/', $value ) ) {
			return self::schemaUrl( $value );
		}

		return $value;
	}

	/**
	 * @param mixed $value Raw value.
	 * @return string
	 */
	private static function stringValue( $value ) {
		if ( ! is_string( $value ) && ! is_numeric( $value ) ) {
			return '';
		}

		return trim( (string) $value );
	}

	/**
	 * @param mixed $value Raw value.
	 * @return float|int
	 */
	private static function numericValue( $value ) {
		if ( is_int( $value ) || is_float( $value ) ) {
			return $value;
		}

		$string = self::stringValue( $value );
		if ( '' === $string ) {
			return 0;
		}

		if ( false !== strpos( $string, '.' ) ) {
			return (float) $string;
		}

		return (int) $string;
	}
}

