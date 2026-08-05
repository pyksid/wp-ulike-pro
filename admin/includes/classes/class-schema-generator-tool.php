<?php
/**
 * Schema Generator — Tools screen.
 *
 *
 * @package    wp-ulike-pro
 * @author     TechnoWich 2026
 * @link       https://wpulike.com
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

class WP_Ulike_Pro_Schema_Generator_Tool {

	/** Max posts returned per search request. */
	const SEARCH_RESULTS_LIMIT = 50;

	/** Max paginated search pages (prevents unbounded admin-ajax scans). */
	const SEARCH_MAX_PAGE = 100;

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'handle_save' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ), 100 );
	}

	/**
	 * JavaScript configuration for the schema tool UI.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_js_config() {
		return array(
			'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
			'searchNonce'   => wp_create_nonce( 'wp_ulike_pro_schema_search' ),
			'loadNonce'     => wp_create_nonce( 'wp_ulike_pro_schema_load' ),
			'saveNonce'     => wp_create_nonce( 'wp_ulike_pro_schema_save' ),
			'previewNonce'  => wp_create_nonce( 'wp_ulike_pro_schema_preview' ),
			'fieldRules'    => self::get_field_visibility_rules(),
			'requiredRules' => self::get_required_field_rules(),
			'initialPostId' => isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0,
			'i18n'          => array(
				'selectPost'   => esc_html__( 'Select a post from the list to edit its schema.', WP_ULIKE_PRO_DOMAIN ),
				'loading'      => esc_html__( 'Loading…', WP_ULIKE_PRO_DOMAIN ),
				'schemaActive' => esc_html__( 'Schema active', WP_ULIKE_PRO_DOMAIN ),
				'faqActive'    => esc_html__( 'FAQ active', WP_ULIKE_PRO_DOMAIN ),
				'configured'   => esc_html__( 'Draft data', WP_ULIKE_PRO_DOMAIN ),
				'noResults'    => esc_html__( 'No posts found. Try different filters.', WP_ULIKE_PRO_DOMAIN ),
				'searchFailed' => esc_html__( 'Search failed. Please try again.', WP_ULIKE_PRO_DOMAIN ),
				'loadFailed'   => esc_html__( 'Could not load post data.', WP_ULIKE_PRO_DOMAIN ),
				'addFaq'       => esc_html__( 'Add FAQ Item', WP_ULIKE_PRO_DOMAIN ),
				'addReview'    => esc_html__( 'Add Review', WP_ULIKE_PRO_DOMAIN ),
				'removeRow'    => esc_html__( 'Remove', WP_ULIKE_PRO_DOMAIN ),
				'resultsSummary' => esc_html__( 'Showing %1$d of %2$d results (max %3$d per page).', WP_ULIKE_PRO_DOMAIN ),
				'resultsExact'   => esc_html__( 'Showing %1$d results.', WP_ULIKE_PRO_DOMAIN ),
				'resultsPaged'   => esc_html__( 'Showing %1$d results. More matches are available.', WP_ULIKE_PRO_DOMAIN ),
				'loadMore'       => esc_html__( 'Load more', WP_ULIKE_PRO_DOMAIN ),
				'loadingMore'    => esc_html__( 'Loading more…', WP_ULIKE_PRO_DOMAIN ),
				'saveSuccess'    => esc_html__( 'Schema settings saved.', WP_ULIKE_PRO_DOMAIN ),
				'saveFailed'     => esc_html__( 'Could not save schema settings. Please try again.', WP_ULIKE_PRO_DOMAIN ),
				'saving'         => esc_html__( 'Saving…', WP_ULIKE_PRO_DOMAIN ),
				'requiredFields' => esc_html__( 'Please fill in the required fields.', WP_ULIKE_PRO_DOMAIN ),
				'schemaTypeRequired' => esc_html__( 'Schema type is required when schema is enabled.', WP_ULIKE_PRO_DOMAIN ),
				'ratingValueRequired' => esc_html__( 'Rating value is required when custom star rating is enabled.', WP_ULIKE_PRO_DOMAIN ),
				'faqItemRequired'    => esc_html__( 'Each FAQ item must include both a question and an answer.', WP_ULIKE_PRO_DOMAIN ),
				'questionLabel'      => esc_html__( 'Question', WP_ULIKE_PRO_DOMAIN ),
				'answerLabel'        => esc_html__( 'Answer', WP_ULIKE_PRO_DOMAIN ),
				'unsavedChanges'     => esc_html__( 'You have unsaved changes. Switch posts anyway?', WP_ULIKE_PRO_DOMAIN ),
				'selectImage'        => esc_html__( 'Select Image', WP_ULIKE_PRO_DOMAIN ),
				'addImages'          => esc_html__( 'Add Images', WP_ULIKE_PRO_DOMAIN ),
				'removeImage'        => esc_html__( 'Remove', WP_ULIKE_PRO_DOMAIN ),
				'addTrack'           => esc_html__( 'Add Track', WP_ULIKE_PRO_DOMAIN ),
				'addSupply'          => esc_html__( 'Add Supply', WP_ULIKE_PRO_DOMAIN ),
				'addTool'            => esc_html__( 'Add Tool', WP_ULIKE_PRO_DOMAIN ),
				'addStep'            => esc_html__( 'Add Step', WP_ULIKE_PRO_DOMAIN ),
				'addStepListItem'    => esc_html__( 'Add Direction', WP_ULIKE_PRO_DOMAIN ),
				'trackLabel'         => esc_html__( 'Track', WP_ULIKE_PRO_DOMAIN ),
				'supplyLabel'        => esc_html__( 'Supply', WP_ULIKE_PRO_DOMAIN ),
				'toolLabel'          => esc_html__( 'Tool', WP_ULIKE_PRO_DOMAIN ),
				'stepLabel'          => esc_html__( 'Step', WP_ULIKE_PRO_DOMAIN ),
				'directionLabel'     => esc_html__( 'Direction', WP_ULIKE_PRO_DOMAIN ),
				'ratingPreviewDisabled' => esc_html__( 'Star ratings are disabled for this post.', WP_ULIKE_PRO_DOMAIN ),
				'ratingPreviewEmpty'    => esc_html__( 'No likes or dislikes yet — ratings will appear once visitors vote.', WP_ULIKE_PRO_DOMAIN ),
				'ratingPreviewAuto'     => esc_html__( 'Estimated output: %1$s / %2$s from %3$d votes (%4$d likes, %5$d dislikes).', WP_ULIKE_PRO_DOMAIN ),
				'ratingPreviewAutoTime' => esc_html__( 'Estimated output: %1$s / %2$s using time-weighted likes (%3$d total votes).', WP_ULIKE_PRO_DOMAIN ),
				'ratingPreviewEngagementStar' => esc_html__( 'Output from star votes: %1$s / %2$s (%3$d ratings).', WP_ULIKE_PRO_DOMAIN ),
				'ratingPreviewEngagementStarEmpty' => esc_html__( 'No star ratings yet — ratings will appear once visitors rate.', WP_ULIKE_PRO_DOMAIN ),
				'ratingPreviewCustom'   => esc_html__( 'Manual rating: %1$s / %2$s (%3$d ratings).', WP_ULIKE_PRO_DOMAIN ),
				'ratingPreviewCustomEmpty' => esc_html__( 'Enter a rating value to preview manual output.', WP_ULIKE_PRO_DOMAIN ),
				'ratingPreviewUnavailable' => esc_html__( 'Select a post to preview star ratings.', WP_ULIKE_PRO_DOMAIN ),
				'viewPost'              => esc_html__( 'View Post', WP_ULIKE_PRO_DOMAIN ),
				'testRichResults'       => esc_html__( 'Test Rich Results', WP_ULIKE_PRO_DOMAIN ),
			),
			'searchLimit'       => self::SEARCH_RESULTS_LIMIT,
			'richResultsTestUrl' => 'https://search.google.com/test/rich-results',
		);
	}

	/**
	 * Enqueue schema tool assets on the Tools screen.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_assets( $hook ) {
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		$tab  = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : '';

		if ( 'wp-ulike-pro-tools' !== $page || 'schema-generator' !== $tab ) {
			return;
		}

		wp_enqueue_media();

		wp_ulike_add_inline_script_data(
			'wp_ulike_pro_admin_scripts',
			'UlikeProSchemaConfig',
			self::get_js_config()
		);
	}

	/**
	 * Handle schema form save.
	 *
	 * @return void
	 */
	public function handle_save() {
		if ( wp_doing_ajax() ) {
			return;
		}

		if ( ! isset( $_POST['wp_ulike_schema_generator_save'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wp_ulike_schema_generator_nonce'] ) ), 'wp_ulike_schema_generator' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( ! WP_Ulike_Pro_API::has_permission() ) {
			wp_die( esc_html__( 'You need an active license to save schema settings.', WP_ULIKE_PRO_DOMAIN ) );
		}

		$post_id = isset( $_POST['schema_post_id'] ) ? absint( $_POST['schema_post_id'] ) : 0;
		if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
			wp_die( esc_html__( 'Invalid post.', WP_ULIKE_PRO_DOMAIN ) );
		}

		$raw    = isset( $_POST['schema'] ) && is_array( $_POST['schema'] ) ? wp_unslash( $_POST['schema'] ) : array();
		$errors = self::validate_schema_submission( $raw );

		if ( ! empty( $errors ) ) {
			wp_die( esc_html( implode( ' ', $errors ) ) );
		}

		self::save_post_schema_data( $post_id, $raw );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'             => 'wp-ulike-pro-tools',
					'tab'              => 'schema-generator',
					'post_id'          => $post_id,
					'settings-updated' => 'true',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Normalize raw schema POST values for validation.
	 *
	 * @param array<string, mixed> $raw Raw schema values.
	 * @return array<string, mixed>
	 */
	public static function normalize_schema_submission( $raw ) {
		$values = array();

		foreach ( wp_ulike_pro_get_schema_meta_keys() as $key ) {
			if ( array_key_exists( $key, $raw ) ) {
				$values[ $key ] = $raw[ $key ];
			}
		}

		return $values;
	}

	/**
	 * Validate schema submission.
	 *
	 * @param array<string, mixed> $raw Raw schema values.
	 * @return array<int, string>
	 */
	public static function validate_schema_submission( $raw ) {
		$errors = array();
		$values = self::normalize_schema_submission( $raw );

		if ( wp_ulike_is_true( $values['enable_schema'] ?? '' ) && empty( $values['schema_type'] ) ) {
			$errors[] = esc_html__( 'Schema type is required when schema is enabled.', WP_ULIKE_PRO_DOMAIN );
		}

		if (
			wp_ulike_is_true( $values['enable_schema'] ?? '' )
			&& ! wp_ulike_is_true( $values['disable_star_ratings'] ?? '' )
			&& wp_ulike_is_true( $values['enable_custom_rating'] ?? '' )
			&& '' === trim( (string) ( $values['rating_value'] ?? '' ) )
		) {
			$errors[] = esc_html__( 'Rating value is required when custom star rating is enabled.', WP_ULIKE_PRO_DOMAIN );
		}

		if ( wp_ulike_is_true( $values['enable_faq'] ?? '' ) && ! empty( $values['faq'] ) && is_array( $values['faq'] ) ) {
			foreach ( $values['faq'] as $row ) {
				if ( ! is_array( $row ) ) {
					continue;
				}

				$question = trim( (string) ( $row['question'] ?? '' ) );
				$answer   = trim( wp_strip_all_tags( (string) ( $row['answer'] ?? '' ) ) );

				if ( ( $question && ! $answer ) || ( $answer && ! $question ) ) {
					$errors[] = esc_html__( 'Each FAQ item must include both a question and an answer.', WP_ULIKE_PRO_DOMAIN );
					break;
				}
			}
		}

		return $errors;
	}

	/**
	 * Save schema values for a post.
	 *
	 * @param int                  $post_id Post ID.
	 * @param array<string, mixed> $raw     Raw schema values.
	 * @return void
	 */
	public static function save_post_schema_data( $post_id, $raw ) {
		wp_ulike_pro_save_metabox_values( $post_id, self::normalize_schema_submission( $raw ), true );
	}

	/**
	 * Rating preview for admin UI (supports unsaved overrides).
	 *
	 * @param int                  $post_id Post ID.
	 * @param array<string, mixed> $raw     Optional schema field overrides.
	 * @return array<string, mixed>
	 */
	public static function get_rating_preview_for_post( $post_id, $raw = array() ) {
		$keys = array(
			'disable_star_ratings',
			'enable_custom_rating',
			'enable_time_factor_rating',
			'rating_value',
			'rating_count',
			'worst_rating',
			'best_rating',
		);
		$settings = array();

		foreach ( $keys as $key ) {
			if ( array_key_exists( $key, $raw ) ) {
				$settings[ $key ] = $raw[ $key ];
			}
		}

		return wp_ulike_pro_get_schema_rating_preview( $post_id, $settings );
	}

	/**
	 * Conditional required field rules for the schema UI.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_required_field_rules() {
		return array(
			array(
				'field'    => 'schema_type',
				'requires' => array( 'enable_schema' => array( 'true' ) ),
			),
			array(
				'field'    => 'rating_value',
				'requires' => array(
					'enable_schema'        => array( 'true' ),
					'enable_custom_rating' => array( 'true' ),
				),
				'requires_not' => array(
					'disable_star_ratings' => array( 'true' ),
				),
			),
		);
	}

	/**
	 * Schema type options.
	 *
	 * @return array<string, string>
	 */
	public static function get_schema_types() {
		return array(
			'Book'                => esc_html__( 'Book', WP_ULIKE_PRO_DOMAIN ),
			'Course'              => esc_html__( 'Course', WP_ULIKE_PRO_DOMAIN ),
			'HowTo'               => esc_html__( 'How-to', WP_ULIKE_PRO_DOMAIN ),
			'Event'               => esc_html__( 'Event', WP_ULIKE_PRO_DOMAIN ),
			'LocalBusiness'       => esc_html__( 'Local Business', WP_ULIKE_PRO_DOMAIN ),
			'Movie'               => esc_html__( 'Movie', WP_ULIKE_PRO_DOMAIN ),
			'Product'             => esc_html__( 'Product', WP_ULIKE_PRO_DOMAIN ),
			'SoftwareApplication' => esc_html__( 'Software App', WP_ULIKE_PRO_DOMAIN ),
			'CreativeWorkSeason'  => esc_html__( 'CreativeWork Season', WP_ULIKE_PRO_DOMAIN ),
			'CreativeWorkSeries'  => esc_html__( 'CreativeWork Series', WP_ULIKE_PRO_DOMAIN ),
			'Episode'             => esc_html__( 'Episode', WP_ULIKE_PRO_DOMAIN ),
			'Game'                => esc_html__( 'Game', WP_ULIKE_PRO_DOMAIN ),
			'MediaObject'         => esc_html__( 'Media Object', WP_ULIKE_PRO_DOMAIN ),
			'MusicPlaylist'       => esc_html__( 'Music Playlist', WP_ULIKE_PRO_DOMAIN ),
			'Organization'        => esc_html__( 'Organization', WP_ULIKE_PRO_DOMAIN ),
		);
	}

	/**
	 * Application category options.
	 *
	 * @return array<string, string>
	 */
	public static function get_application_categories() {
		return array(
			'GameApplication'               => 'GameApplication',
			'SocialNetworkingApplication'   => 'SocialNetworkingApplication',
			'TravelApplication'             => 'TravelApplication',
			'ShoppingApplication'           => 'ShoppingApplication',
			'SportsApplication'             => 'SportsApplication',
			'LifestyleApplication'          => 'LifestyleApplication',
			'BusinessApplication'           => 'BusinessApplication',
			'DesignApplication'             => 'DesignApplication',
			'DeveloperApplication'          => 'DeveloperApplication',
			'DriverApplication'             => 'DriverApplication',
			'EducationalApplication'        => 'EducationalApplication',
			'HealthApplication'             => 'HealthApplication',
			'FinanceApplication'            => 'FinanceApplication',
			'SecurityApplication'           => 'SecurityApplication',
			'BrowserApplication'            => 'BrowserApplication',
			'CommunicationApplication'      => 'CommunicationApplication',
			'DesktopEnhancementApplication' => 'DesktopEnhancementApplication',
			'EntertainmentApplication'      => 'EntertainmentApplication',
			'MultimediaApplication'         => 'MultimediaApplication',
			'HomeApplication'               => 'HomeApplication',
			'ReferenceApplication'          => 'ReferenceApplication',
			'UtilitiesApplication'          => 'UtilitiesApplication',
			'MedicalApplication'            => 'MedicalApplication',
			'OtherApplication'              => 'OtherApplication',
		);
	}

	/**
	 * Days of week options for LocalBusiness schema.
	 *
	 * @return array<string, string>
	 */
	public static function get_days_of_week() {
		return array(
			'Sunday'    => esc_html__( 'Sunday', WP_ULIKE_PRO_DOMAIN ),
			'Monday'    => esc_html__( 'Monday', WP_ULIKE_PRO_DOMAIN ),
			'Tuesday'   => esc_html__( 'Tuesday', WP_ULIKE_PRO_DOMAIN ),
			'Wednesday' => esc_html__( 'Wednesday', WP_ULIKE_PRO_DOMAIN ),
			'Thursday'  => esc_html__( 'Thursday', WP_ULIKE_PRO_DOMAIN ),
			'Friday'    => esc_html__( 'Friday', WP_ULIKE_PRO_DOMAIN ),
			'Saturday'  => esc_html__( 'Saturday', WP_ULIKE_PRO_DOMAIN ),
		);
	}

	/**
	 * Field visibility rules keyed by field id.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public static function get_field_visibility_rules() {
		return array(
			'schema_type'                 => array( 'requires' => array( 'enable_schema' => array( 'true' ) ) ),
			'title'                       => array( 'requires' => array( 'enable_schema' => array( 'true' ) ) ),
			'description'                 => array( 'requires' => array( 'enable_schema' => array( 'true' ) ) ),
			'name'                        => array( 'requires' => array( 'enable_schema' => array( 'true' ), 'schema_type' => array( 'Course', 'Product', 'CreativeWorkSeason', 'Episode', 'Organization', 'SoftwareApplication', 'HowTo' ) ) ),
			'author'                      => array( 'requires' => array( 'enable_schema' => array( 'true' ), 'schema_type' => array( 'Book', 'Event', 'Movie', 'CreativeWorkSeason', 'Episode', 'Product' ) ) ),
			'day_of_week'                 => array( 'requires' => array( 'enable_schema' => array( 'true' ), 'schema_type' => array( 'LocalBusiness' ) ) ),
			'opens'                       => array( 'requires' => array( 'enable_schema' => array( 'true' ), 'schema_type' => array( 'LocalBusiness' ) ) ),
			'closes'                      => array( 'requires' => array( 'enable_schema' => array( 'true' ), 'schema_type' => array( 'LocalBusiness' ) ) ),
			'location'                    => array( 'requires' => array( 'enable_schema' => array( 'true' ), 'schema_type' => array( 'Event' ) ) ),
			'address_group'               => array( 'requires' => array( 'enable_schema' => array( 'true' ), 'schema_type' => array( 'LocalBusiness', 'Event', 'Organization' ) ) ),
			'telephone'                   => array( 'requires' => array( 'enable_schema' => array( 'true' ), 'schema_type' => array( 'LocalBusiness', 'Organization' ) ) ),
			'price_range'                 => array( 'requires' => array( 'enable_schema' => array( 'true' ), 'schema_type' => array( 'LocalBusiness' ) ) ),
			'start_date'                  => array( 'requires' => array( 'enable_schema' => array( 'true' ), 'schema_type' => array( 'Event', 'CreativeWorkSeason', 'CreativeWorkSeries' ) ) ),
			'end_date'                    => array( 'requires' => array( 'enable_schema' => array( 'true' ), 'schema_type' => array( 'Event', 'CreativeWorkSeason', 'CreativeWorkSeries' ) ) ),
			'created_date'                => array( 'requires' => array( 'enable_schema' => array( 'true' ), 'schema_type' => array( 'Movie', 'Episode' ) ) ),
			'price_group'                 => array( 'requires' => array( 'enable_schema' => array( 'true' ), 'schema_type' => array( 'Event', 'Product', 'SoftwareApplication', 'Game', 'HowTo' ) ) ),
			'availability'                => array( 'requires' => array( 'enable_schema' => array( 'true' ), 'schema_type' => array( 'Event', 'Product', 'SoftwareApplication' ) ) ),
			'valid_date'                  => array( 'requires' => array( 'enable_schema' => array( 'true' ), 'schema_type' => array( 'Event', 'Product' ) ) ),
			'url'                         => array( 'requires' => array( 'enable_schema' => array( 'true' ), 'schema_type' => array( 'Book', 'Event', 'Product', 'Organization', 'MediaObject', 'SoftwareApplication' ) ) ),
			'sku'                         => array( 'requires' => array( 'enable_schema' => array( 'true' ), 'schema_type' => array( 'Product' ) ) ),
			'mpn'                         => array( 'requires' => array( 'enable_schema' => array( 'true' ), 'schema_type' => array( 'Product' ) ) ),
			'application_category'        => array( 'requires' => array( 'enable_schema' => array( 'true' ), 'schema_type' => array( 'SoftwareApplication' ) ) ),
			'operating_system'            => array( 'requires' => array( 'enable_schema' => array( 'true' ), 'schema_type' => array( 'SoftwareApplication' ) ) ),
			'software_version'            => array( 'requires' => array( 'enable_schema' => array( 'true' ), 'schema_type' => array( 'SoftwareApplication' ) ) ),
			'is_accessible_for_free'      => array( 'requires' => array( 'enable_schema' => array( 'true' ), 'schema_type' => array( 'SoftwareApplication' ) ) ),
			'issn'                        => array( 'requires' => array( 'enable_schema' => array( 'true' ), 'schema_type' => array( 'CreativeWorkSeries' ) ) ),
			'duration'                    => array( 'requires' => array( 'enable_schema' => array( 'true' ), 'schema_type' => array( 'MediaObject', 'HowTo' ) ) ),
			'encoding_format'             => array( 'requires' => array( 'enable_schema' => array( 'true' ), 'schema_type' => array( 'MediaObject' ) ) ),
			'num_tracks'                  => array( 'requires' => array( 'enable_schema' => array( 'true' ), 'schema_type' => array( 'MusicPlaylist' ) ) ),
			'image'                       => array( 'requires' => array( 'enable_schema' => array( 'true' ), 'schema_type' => array( 'Organization' ) ) ),
			'image_list'                  => array( 'requires' => array( 'enable_schema' => array( 'true' ), 'schema_type' => array( 'LocalBusiness', 'Event', 'Movie', 'Product', 'Episode', 'SoftwareApplication', 'HowTo' ) ) ),
			'tracks_section'              => array( 'requires' => array( 'enable_schema' => array( 'true' ), 'schema_type' => array( 'MusicPlaylist' ) ) ),
			'supply_section'              => array( 'requires' => array( 'enable_schema' => array( 'true' ), 'schema_type' => array( 'HowTo' ) ) ),
			'tool_section'                => array( 'requires' => array( 'enable_schema' => array( 'true' ), 'schema_type' => array( 'HowTo' ) ) ),
			'step_section'                => array( 'requires' => array( 'enable_schema' => array( 'true' ), 'schema_type' => array( 'HowTo' ) ) ),
			'rating_section'              => array( 'requires' => array( 'enable_schema' => array( 'true' ) ) ),
			'enable_time_factor_rating_row' => array(
				'requires'     => array( 'enable_schema' => array( 'true' ) ),
				'requires_not' => array( 'disable_star_ratings' => array( 'true' ) ),
			),
			'enable_custom_rating_row'      => array(
				'requires'     => array( 'enable_schema' => array( 'true' ) ),
				'requires_not' => array( 'disable_star_ratings' => array( 'true' ) ),
			),
			'custom_rating_section'       => array(
				'requires'     => array(
					'enable_schema'        => array( 'true' ),
					'enable_custom_rating' => array( 'true' ),
				),
				'requires_not' => array( 'disable_star_ratings' => array( 'true' ) ),
			),
			'custom_reviews_section'      => array( 'requires' => array( 'enable_schema' => array( 'true' ) ) ),
			'reviews_repeater'            => array(
				'requires' => array(
					'enable_schema'        => array( 'true' ),
					'enable_custom_reviews' => array( 'true' ),
				),
			),
			'faq_section'                 => array( 'requires' => array( 'enable_faq' => array( 'true' ) ) ),
		);
	}

	/**
	 * Load schema values for a post (admin).
	 *
	 * @param int $post_id Post ID.
	 * @return array<string, mixed>
	 */
	public static function get_post_schema_data( $post_id ) {
		$post_id = absint( $post_id );
		$data    = array();

		foreach ( wp_ulike_pro_get_schema_meta_keys() as $key ) {
			$value = wp_ulike_pro_get_metabox_value_raw( $key, $post_id );
			if ( $value !== '' && $value !== null && $value !== false ) {
				$data[ $key ] = wp_ulike_pro_prepare_schema_admin_value( $key, $value );
			}
		}

		return $data;
	}

	/**
	 * Search posts for the schema tool.
	 *
	 * @param array<string, mixed> $args Query args.
	 * @return array<string, mixed>
	 */
	public static function search_posts( $args ) {
		$post_type   = self::sanitize_search_post_type( $args['post_type'] ?? '' );
		$search      = self::sanitize_search_term( $args['search'] ?? '' );
		$schema_only = ! empty( $args['schema_only'] );
		$page        = isset( $args['page'] ) ? max( 1, min( (int) $args['page'], self::SEARCH_MAX_PAGE ) ) : 1;

		$query_args = array(
			'post_status'    => array( 'publish', 'draft', 'pending', 'future', 'private' ),
			'posts_per_page' => self::SEARCH_RESULTS_LIMIT,
			'paged'          => $page,
			'orderby'        => 'modified',
			'order'          => 'DESC',
			'no_found_rows'  => false,
		);

		if ( ! empty( $post_type ) ) {
			$query_args['post_type'] = $post_type;
		} else {
			$query_args['post_type'] = get_post_types( array( 'public' => true ) );
		}

		if ( '' !== $search ) {
			$query_args['s'] = $search;
		}

		if ( $schema_only ) {
			$query_args['wp_ulike_pro_schema_only'] = true;
			add_filter( 'posts_where', array( __CLASS__, 'filter_schema_only_where' ), 10, 2 );
		}

		$query = new WP_Query( $query_args );

		if ( $schema_only ) {
			remove_filter( 'posts_where', array( __CLASS__, 'filter_schema_only_where' ), 10 );
		}

		$post_ids = wp_list_pluck( $query->posts, 'ID' );
		$statuses = wp_ulike_pro_batch_get_post_schema_status( $post_ids );
		$posts    = array();

		foreach ( $query->posts as $post ) {
			$id     = (int) $post->ID;
			$status = $statuses[ $id ] ?? wp_ulike_pro_build_schema_status_from_meta( array() );

			if ( $schema_only && empty( $status['has_data'] ) ) {
				continue;
			}

			$posts[] = array(
				'id'          => $id,
				'title'       => $post->post_title ? $post->post_title : sprintf( esc_html__( '(no title) #%d', WP_ULIKE_PRO_DOMAIN ), $id ),
				'type'        => $post->post_type,
				'edit_link'   => current_user_can( 'edit_post', $id ) ? get_edit_post_link( $id, 'raw' ) : '',
				'has_schema'  => $status['has_data'],
				'status'      => $status,
				'modified'    => mysql2date( get_option( 'date_format' ), $post->post_modified ),
			);
		}

		return array(
			'posts'    => $posts,
			'total'    => (int) $query->found_posts,
			'page'     => $page,
			'limit'    => self::SEARCH_RESULTS_LIMIT,
			'has_more' => $page < (int) $query->max_num_pages,
		);
	}

	/**
	 * Validate post type filter for schema search.
	 *
	 * @param string $post_type Raw post type slug.
	 * @return string Sanitized slug or empty string.
	 */
	public static function sanitize_search_post_type( $post_type ) {
		$post_type = sanitize_key( (string) $post_type );
		if ( '' === $post_type ) {
			return '';
		}

		$public_types = get_post_types( array( 'public' => true ) );
		return in_array( $post_type, $public_types, true ) ? $post_type : '';
	}

	/**
	 * Normalize and bound schema search term length.
	 *
	 * @param string $search Raw search string.
	 * @return string
	 */
	public static function sanitize_search_term( $search ) {
		$search = sanitize_text_field( (string) $search );
		if ( '' === $search ) {
			return '';
		}

		if ( function_exists( 'mb_substr' ) ) {
			return mb_substr( $search, 0, 200 );
		}

		return substr( $search, 0, 200 );
	}

	/**
	 * Limit search to posts with schema/FAQ meta (single EXISTS, no meta_query JOINs).
	 *
	 * @param string    $where   SQL WHERE clause.
	 * @param \WP_Query $query Query object.
	 * @return string
	 */
	public static function filter_schema_only_where( $where, $query ) {
		if ( ! $query->get( 'wp_ulike_pro_schema_only' ) ) {
			return $where;
		}

		global $wpdb;

		$data_keys = array(
			'wp_ulike_pro_schema_type',
			'wp_ulike_pro_title',
			'wp_ulike_pro_description',
			'wp_ulike_pro_faq',
			'wp_ulike_pro_reviews',
		);
		$data_keys_sql = "'" . implode( "','", array_map( 'esc_sql', $data_keys ) ) . "'";

		$where .= " AND EXISTS (
			SELECT 1 FROM {$wpdb->postmeta} pm
			WHERE pm.post_id = {$wpdb->posts}.ID
			AND (
				( pm.meta_key = 'wp_ulike_pro_enable_schema' AND pm.meta_value IN ('true', '1', 'yes', 'on') )
				OR ( pm.meta_key = 'wp_ulike_pro_enable_faq' AND pm.meta_value IN ('true', '1', 'yes', 'on') )
				OR ( pm.meta_key IN ({$data_keys_sql}) AND pm.meta_value != '' AND pm.meta_value IS NOT NULL )
				OR (
					pm.meta_key = 'wp_ulike_pro_meta_box'
					AND (
						pm.meta_value LIKE '%s:13:\"enable_schema\";s:4:\"true\"%'
						OR pm.meta_value LIKE '%s:13:\"enable_schema\";b:1%'
						OR pm.meta_value LIKE '%s:10:\"enable_faq\";s:4:\"true\"%'
						OR pm.meta_value LIKE '%s:10:\"enable_faq\";b:1%'
						OR pm.meta_value LIKE '%s:11:\"schema_type\";s:%'
						OR pm.meta_value LIKE '%s:5:\"title\";s:%'
						OR pm.meta_value LIKE '%s:11:\"description\";s:%'
						OR pm.meta_value LIKE '%s:3:\"faq\";a:%'
						OR pm.meta_value LIKE '%s:7:\"reviews\";a:%'
					)
				)
			)
		)";

		return $where;
	}
}

