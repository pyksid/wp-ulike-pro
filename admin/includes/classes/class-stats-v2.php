<?php
/**
 * Stats V2 facade — delegates domain logic to traits/services.
 *
 * @package    wp-ulike-pro
 * @author     TechnoWich 2026
 * @link       https://wpulike.com
 */
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

require_once __DIR__ . '/stats-traits/stats-trait-query.php';
require_once __DIR__ . '/stats-traits/stats-trait-trends.php';
require_once __DIR__ . '/stats-traits/stats-trait-tops.php';
require_once __DIR__ . '/stats-traits/stats-trait-geo.php';
require_once __DIR__ . '/stats-traits/stats-trait-activity.php';
require_once __DIR__ . '/stats-traits/stats-trait-intelligence.php';
require_once __DIR__ . '/stats-traits/stats-trait-woocommerce.php';
require_once __DIR__ . '/stats-traits/stats-trait-pulse.php';
require_once __DIR__ . '/stats-traits/stats-trait-engagement-bridge.php';
require_once __DIR__ . '/class-stats-type-resolver.php';
require_once __DIR__ . '/class-stats-engagement-reader.php';

if ( ! class_exists( 'WP_Ulike_Pro_Stats_V2' ) ) {

	class WP_Ulike_Pro_Stats_V2 {
		use WP_Ulike_Pro_Stats_Trait_Query;
		use WP_Ulike_Pro_Stats_Trait_Trends;
		use WP_Ulike_Pro_Stats_Trait_Tops;
		use WP_Ulike_Pro_Stats_Trait_Geo;
		use WP_Ulike_Pro_Stats_Trait_Activity;
		use WP_Ulike_Pro_Stats_Trait_Intelligence;
		use WP_Ulike_Pro_Stats_Trait_WooCommerce;
		use WP_Ulike_Pro_Stats_Trait_Pulse;
		use WP_Ulike_Pro_Stats_Trait_Engagement_Bridge;

		private $wpdb, $tables, $dateRange, $selectedStatus, $button_views, $engagement_reader;
		protected static $instance = null;

		/**
		 * Constructor.
		 */
		public function __construct() {
			global $wpdb;
			$this->wpdb   = $wpdb;
			$this->tables = WP_Ulike_Pro_Stats_Type_Resolver::get_tables_map();
			if ( class_exists( 'WP_Ulike_Pro_Views' ) ) {
				$this->button_views = WP_Ulike_Pro_Views::get_instance();
			}
		}

		/**
		 * Singleton accessor.
		 *
		 * @return self
		 */
		public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		public function get_tables(){
			$get_tables = $this->tables;

			foreach ( $get_tables as $type => $table ) {
				if ( ! WP_Ulike_Pro_Stats_Type_Resolver::is_type_available( $type ) ) {
					unset( $get_tables[ $type ] );
					continue;
				}

				if ( ! $this->stats_type_has_activity( $type ) ) {
					unset( $get_tables[ $type ] );
				}
			}

			return $get_tables;
		}

		/**
		 * Public wrapper for mode-aware counts (REST/legacy stats).
		 *
		 * @param string $type_key posts|comments|activities|topics.
		 * @param mixed  $date     Period filter.
		 * @return int
		 */
		public function count_for_content_type( $type_key, $date = 'all' ) {
			return (int) $this->count_logs_for_stats_type( $type_key, $date );
		}

		public function get_api_data() {
			$tables = $this->get_tables();
			$meta   = WP_Ulike_Pro_Stats_Meta::get_site_stats_meta( array_keys( $tables ) );

			return array(
				'overview' => $this->get_overview_data_enriched(),
				'meta'     => array_merge(
					array(
						'build'              => 'pro',
						'content_types'      => array_keys( $tables ),
						'date_limit_default' => (int) apply_filters( 'wp_ulike_stats_data_limit', 30 ),
						'woocommerce'        => class_exists( 'WP_Ulike_Pro_WooCommerce' )
							? WP_Ulike_Pro_WooCommerce::get_stats_nav_meta()
							: array(
								'active'           => false,
								'report_available' => false,
								'product_likes'    => false,
								'review_likes'     => false,
							),
					),
					$meta
				),
			);
		}

		/**
		 * Overview page payload — loaded separately for performance.
		 *
		 * @return array
		 */
	public function get_overview_api_data() {
		// Assemble fresh on every call. Each sub-query is itself cached via
		// WP_Ulike_Query_Cache::remember_stats() (versioned, auto-invalidated
		// on any vote/engagement bump), so a stale whole-payload cache can
		// never mask a code fix again. Removes the 120s payload trap.
		$intelligence  = $this->get_site_intelligence();
		$schedule      = $this->get_activity_schedule( array( 'interval' => '30 DAY', 'summary_only' => true ) );
		$categories    = $this->get_category_performance( 6 );
		$commerce      = $this->get_commerce_highlights();
		$current_end   = current_time( 'Y-m-d' );
		$current_start = gmdate( 'Y-m-d', strtotime( '-29 days' ) );
		$previous_end  = gmdate( 'Y-m-d', strtotime( '-30 days' ) );
		$previous_start = gmdate( 'Y-m-d', strtotime( '-59 days' ) );

		return array(
			'overview'      => $this->get_overview_data_enriched(),
			'reports'       => array(
				'monthly_data'              => $this->get_aggregated_data_by_month( '12 MONTH' ),
				'daily_data'                => $this->get_aggregated_data_by_date( '30 DAY' ),
				'previous_daily_data'       => $this->get_aggregated_data_by_date_range( $previous_start, $previous_end ),
				'daily_views_data'          => $this->get_daily_views_by_date_range( $current_start, $current_end ),
				'previous_daily_views_data' => $this->get_daily_views_by_date_range( $previous_start, $previous_end ),
			),
			'intelligence'  => $intelligence,
			'peak_hours'    => $schedule['hours'] ?? array(),
			'schedule'      => $schedule,
			'categories'    => $categories,
			'commerce'      => $commerce,
			'top_countries' => $this->get_top_countries_preview( 5 ),
			'growth_tips'   => $this->get_intelligence_tips(
				$intelligence,
				array(
					'schedule'   => $schedule,
					'categories' => $categories,
					'commerce'   => $commerce,
				)
			),
		);
	}

		/**
		 * Week-over-week metrics only — lighter than full get_count_logs().
		 *
		 * @return array
		 */
		public function get_metrics_summary() {
			$tables  = $this->get_tables();
			$summary = array();

			foreach ( $tables as $type => $table ) {
				if ( ! function_exists( 'is_bbpress' ) && $type === 'topics' ) {
					continue;
				}
				if ( ! defined( 'BP_VERSION' ) && $type === 'activities' ) {
					continue;
				}

				$summary[ $type ] = array(
					'week'      => $this->count_logs_for_stats_type( $type, 'week' ),
					'last_week' => $this->count_logs_for_stats_type( $type, 'last_week' ),
				);
			}

			return $summary;
		}

		/**
		 * Top N countries for overview preview.
		 *
		 * @param int $limit Max countries.
		 * @return array
		 */
		public function get_top_countries_preview( $limit = 5 ) {
			$countries = $this->count_country_codes();

			if ( ! is_array( $countries ) || empty( $countries ) ) {
				return array();
			}

			uasort(
				$countries,
				function( $a, $b ) {
					$a_total = isset( $a['total'] ) ? (int) $a['total'] : 0;
					$b_total = isset( $b['total'] ) ? (int) $b['total'] : 0;
					return $b_total <=> $a_total;
				}
			);

			return array_slice( $countries, 0, max( 1, (int) $limit ), true );
		}

		/**
		 * Lightweight top content preview for overview.
		 *
		 * @return array
		 */
		public function get_overview_highlights() {
			$defaults = array(
				'type'     => 'post',
				'limit'    => 5,
				'offset'   => 1,
				'status'   => array( 'like', 'dislike' ),
				'is_popular' => true,
			);

			$posts = $this->get_top( array_merge( $defaults, array( 'type' => 'post' ) ) );
			$comments = $this->get_top( array_merge( $defaults, array( 'type' => 'comment' ) ) );
			$engagers = $this->get_top( array_merge( $defaults, array( 'type' => 'engagers' ) ) );

			return array(
				'posts'    => isset( $posts['items'] ) ? array_slice( (array) $posts['items'], 0, 5 ) : array(),
				'comments' => isset( $comments['items'] ) ? array_slice( (array) $comments['items'], 0, 5 ) : array(),
				'engagers' => isset( $engagers['items'] ) ? array_slice( (array) $engagers['items'], 0, 5 ) : array(),
			);
		}

		/**
		 * Category / taxonomy options for post top filters.
		 *
		 * @param string $post_type     Post type slug.
		 * @param string $taxonomy_name Optional taxonomy slug.
		 * @return array
		 */
		public function get_post_filter_meta( $post_type = 'post', $taxonomy_name = '' ) {
			$post_type     = sanitize_key( $post_type ?: 'post' );
			$taxonomy_name = sanitize_key( $taxonomy_name );
			$cache_key     = 'post_filter_meta_' . $post_type . '_' . ( $taxonomy_name ?: 'auto' );
			$cached        = wp_cache_get( $cache_key, WP_ULIKE_PRO_DOMAIN );

			if ( false !== $cached ) {
				return $cached;
			}

			$taxonomies           = get_object_taxonomies( $post_type, 'objects' );
			$categories           = array();
			$available_taxonomies = array();
			$label                = esc_html__( 'Category', 'wp-ulike-pro' );
			$taxonomy             = '';

			foreach ( $taxonomies as $tax ) {
				if ( $tax->hierarchical && $tax->public ) {
					$available_taxonomies[] = array(
						'name'  => $tax->name,
						'label' => $tax->label,
					);
				}
			}

			if ( ! empty( $taxonomy_name ) && taxonomy_exists( $taxonomy_name ) ) {
				$taxonomy_obj = get_taxonomy( $taxonomy_name );
				if ( $taxonomy_obj && in_array( $post_type, (array) $taxonomy_obj->object_type, true ) ) {
					$taxonomy = $taxonomy_name;
				}
			}

			if ( empty( $taxonomy ) && ! empty( $available_taxonomies ) ) {
				$taxonomy = $available_taxonomies[0]['name'];
			}

			if ( ! empty( $taxonomy ) ) {
				$taxonomy_obj = get_taxonomy( $taxonomy );
				if ( $taxonomy_obj ) {
					$label = $taxonomy_obj->label;
				}

				$terms = get_terms(
					array(
						'taxonomy'   => $taxonomy,
						'hide_empty' => false,
					)
				);

				if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
					foreach ( $terms as $term ) {
						$categories[] = array(
							'id'    => $term->term_id,
							'name'  => $term->name,
							'count' => $term->count,
						);
					}
				}
			}

			$result = array(
				'categories'           => $categories,
				'category_label'       => $label,
				'taxonomy'             => $taxonomy,
				'available_taxonomies' => $available_taxonomies,
			);

			wp_cache_set( $cache_key, $result, WP_ULIKE_PRO_DOMAIN, 300 );

			return $result;
		}

		/**
		 * Adapt metrics summary for growth tip calculations.
		 *
		 * @param array $summary Metrics summary.
		 * @return array
		 */
		private function metrics_summary_for_tips( $summary ) {
			$adapted = array();
			foreach ( $summary as $type => $data ) {
				$adapted[ $type ] = array(
					'week'      => (int) ( $data['week'] ?? 0 ),
					'last_week' => (int) ( $data['last_week'] ?? 0 ),
				);
			}
			return $adapted;
		}

		/**
		 * Single content-type engagement metrics.
		 *
		 * @param string $type Content type key.
		 * @return array|null
		 */
		public function get_overview_data_enriched() {
			$week      = (int) $this->count_all_logs( 'week' );
			$last_week = (int) $this->count_all_logs( 'last_week' );
			$month     = (int) $this->count_all_logs( 'month' );
			$last_month = (int) $this->count_all_logs( 'last_month' );

			return array(
				'total'        => (int) $this->count_all_logs( 'all' ),
				'today'        => (int) $this->count_all_logs( 'today' ),
				'yesterday'    => (int) $this->count_all_logs( 'yesterday' ),
				'week'         => $week,
				'last_week'    => $last_week,
				'week_growth'  => $this->calculate_growth_percent( $week, $last_week ),
				'month'        => $month,
				'last_month'   => $last_month,
				'month_growth' => $this->calculate_growth_percent( $month, $last_month ),
			);
		}

		/**
		 * Actionable growth insights for the overview page.
		 *
		 * @param array $metrics Per-type metrics.
		 * @return array
		 */
		public function get_growth_tips( $metrics = array() ) {
			if ( empty( $metrics ) ) {
				$metrics = $this->get_count_logs();
			}

			$overview = $this->get_overview_data_enriched();
			$tips     = array();

			if ( 0 === $overview['today'] && $overview['yesterday'] > 0 ) {
				$tips[] = array(
					'type'    => 'warning',
					'message' => sprintf(
						/* translators: %d: interaction count */
						esc_html__( 'No interactions today — yesterday had %d.', 'wp-ulike-pro' ),
						$overview['yesterday']
					),
				);
			}

			if ( $overview['week_growth'] > 15 ) {
				$tips[] = array(
					'type'    => 'positive',
					'message' => sprintf(
						/* translators: %s: growth percentage */
						esc_html__( 'Engagement is up %s%% compared to last week.', 'wp-ulike-pro' ),
						number_format_i18n( $overview['week_growth'], 1 )
					),
				);
			} elseif ( $overview['week_growth'] < -10 ) {
				$tips[] = array(
					'type'    => 'warning',
					'message' => sprintf(
						/* translators: %s: decline percentage */
						esc_html__( 'Engagement dropped %s%% this week — review recent content.', 'wp-ulike-pro' ),
						number_format_i18n( abs( $overview['week_growth'] ), 1 )
					),
				);
			}

			$best_type    = '';
			$best_growth  = -9999;
			$best_week    = 0;

			foreach ( $metrics as $type => $data ) {
				$growth = $this->calculate_growth_percent( (int) $data['week'], (int) $data['last_week'] );
				if ( $growth > $best_growth ) {
					$best_growth = $growth;
					$best_type   = $type;
					$best_week   = (int) $data['week'];
				}
			}

			if ( $best_type && $best_week > 0 ) {
				$tips[] = array(
					'type'    => 'info',
					'message' => sprintf(
						/* translators: 1: content type, 2: growth percentage */
						esc_html__( '%1$s leads this week with %2$s%% growth.', 'wp-ulike-pro' ),
						ucfirst( $best_type ),
						number_format_i18n( $best_growth, 1 )
					),
				);
			}

			if ( empty( $tips ) && $overview['total'] > 0 ) {
				$tips[] = array(
					'type'    => 'info',
					'message' => esc_html__( 'Open Reports in the sidebar to explore engagement by content type, geography, and device.', 'wp-ulike-pro' ),
				);
			}

			return $tips;
		}

		/**
		 * @param int $current Current period value.
		 * @param int $previous Previous period value.
		 * @return float
		 */
		private function calculate_growth_percent( $current, $previous ) {
			if ( $previous > 0 ) {
				return round( ( ( $current - $previous ) / $previous ) * 100, 2 );
			}

			return $current > 0 ? 100 : 0;
		}
	}
}

