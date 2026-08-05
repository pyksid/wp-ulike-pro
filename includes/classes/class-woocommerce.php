<?php
/**
 * WooCommerce integration helpers.
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

if ( ! class_exists( 'WP_Ulike_Pro_WooCommerce' ) ) {

	/**
	 * WooCommerce compatibility and display condition helpers.
	 */
	class WP_Ulike_Pro_WooCommerce {

		/**
		 * Register WooCommerce feature compatibility declarations.
		 *
		 * @return void
		 */
		public static function init() {
			add_action( 'before_woocommerce_init', array( __CLASS__, 'declare_feature_compatibility' ) );
		}

		/**
		 * Declare compatibility with WooCommerce HPOS and related features.
		 *
		 * @return void
		 */
		public static function declare_feature_compatibility() {
			if ( ! class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
				return;
			}

			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
				'custom_order_tables',
				WP_ULIKE_PRO__FILE__,
				true
			);

			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
				'cart_checkout_blocks',
				WP_ULIKE_PRO__FILE__,
				true
			);
		}

		/**
		 * Whether WooCommerce is active.
		 *
		 * @return bool
		 */
		public static function is_active() {
			return class_exists( 'WooCommerce' );
		}

		/**
		 * Extended page context map for display automation.
		 *
		 * @return array<string, bool>
		 */
		public static function get_context_map() {
			if ( ! self::is_active() ) {
				return array();
			}

			return array(
				'woocommerce'          => function_exists( 'is_woocommerce' ) && is_woocommerce(),
				'woocommerce_shop'     => function_exists( 'is_shop' ) && is_shop(),
				'woocommerce_product'  => function_exists( 'is_product' ) && is_product(),
				'woocommerce_category' => function_exists( 'is_product_category' ) && is_product_category(),
				'woocommerce_tag'      => function_exists( 'is_product_tag' ) && is_product_tag(),
				'woocommerce_cart'     => function_exists( 'is_cart' ) && is_cart(),
				'woocommerce_checkout' => function_exists( 'is_checkout' ) && is_checkout(),
				'woocommerce_account'  => function_exists( 'is_account_page' ) && is_account_page(),
			);
		}

		/**
		 * Evaluate WooCommerce-specific product conditions.
		 *
		 * @param array $conditions Rule conditions.
		 * @param int   $post_id    Current post/product ID.
		 * @return bool
		 */
		public static function matches_product_conditions( $conditions, $post_id = 0 ) {
			if ( ! self::is_active() ) {
				return true;
			}

			$post_id = $post_id ? absint( $post_id ) : absint( get_the_ID() );
			if ( ! $post_id || 'product' !== get_post_type( $post_id ) ) {
				return true;
			}

			$product = wc_get_product( $post_id );
			if ( ! $product ) {
				return false;
			}

			$wc_conditions = isset( $conditions['woocommerce'] ) && is_array( $conditions['woocommerce'] )
				? $conditions['woocommerce']
				: array();

			if ( ! empty( $wc_conditions['on_sale'] ) ) {
				$is_on_sale = $product->is_on_sale();
				if ( 'yes' === $wc_conditions['on_sale'] && ! $is_on_sale ) {
					return false;
				}
				if ( 'no' === $wc_conditions['on_sale'] && $is_on_sale ) {
					return false;
				}
			}

			if ( ! empty( $wc_conditions['featured'] ) ) {
				$is_featured = $product->is_featured();
				if ( 'yes' === $wc_conditions['featured'] && ! $is_featured ) {
					return false;
				}
				if ( 'no' === $wc_conditions['featured'] && $is_featured ) {
					return false;
				}
			}

			if ( ! empty( $wc_conditions['product_type'] ) && is_array( $wc_conditions['product_type'] ) ) {
				if ( ! in_array( $product->get_type(), $wc_conditions['product_type'], true ) ) {
					return false;
				}
			}

			return true;
		}

		/**
		 * Get WooCommerce product type options.
		 *
		 * @return array<string, string>
		 */
		public static function get_product_types() {
			if ( ! self::is_active() ) {
				return array();
			}

			return array(
				'simple'   => esc_html__( 'Simple', WP_ULIKE_PRO_DOMAIN ),
				'variable' => esc_html__( 'Variable', WP_ULIKE_PRO_DOMAIN ),
				'grouped'  => esc_html__( 'Grouped', WP_ULIKE_PRO_DOMAIN ),
				'external' => esc_html__( 'External/Affiliate', WP_ULIKE_PRO_DOMAIN ),
			);
		}

		/**
		 * Bootstrap meta for the React stats navigation.
		 *
		 * @return array{active:bool,report_available:bool,product_likes:bool,review_likes:bool}
		 */
		public static function get_stats_nav_meta() {
			static $cache = null;

			if ( null !== $cache ) {
				return $cache;
			}

			if ( ! self::is_active() ) {
				$cache = array(
					'active'           => false,
					'report_available' => false,
					'product_likes'    => false,
					'review_likes'     => false,
				);
				return $cache;
			}

			$product_likes = self::product_likes_enabled();
			$review_likes  = self::review_likes_enabled();

			$cache = array(
				'active'           => true,
				'report_available' => $product_likes || $review_likes || self::has_recent_orders( 30 ),
				'product_likes'    => $product_likes,
				'review_likes'     => $review_likes,
			);

			return $cache;
		}

		/**
		 * Whether product like buttons are enabled or have collected votes.
		 *
		 * @return bool
		 */
		public static function product_likes_enabled() {
			if ( ! self::is_active() ) {
				return false;
			}

			if ( function_exists( 'wp_ulike_get_option' ) ) {
				$post_types = wp_ulike_get_option( 'posts_group|auto_display_filter_post_types', array() );
				if ( in_array( 'product', (array) $post_types, true ) ) {
					return true;
				}
			}

			if ( self::has_product_engagement() ) {
				return true;
			}

			return self::has_active_display_rule( 'post', 'woocommerce' );
		}

		/**
		 * Whether review like buttons are enabled or have collected votes.
		 *
		 * @return bool
		 */
		public static function review_likes_enabled() {
			if ( ! self::is_active() ) {
				return false;
			}

			if ( self::has_review_engagement() ) {
				return true;
			}

			return self::has_active_display_rule( 'product_review', 'woocommerce' );
		}

		/**
		 * Resolve report period bounds.
		 *
		 * @param string|null $start_date Y-m-d.
		 * @param string|null $end_date   Y-m-d.
		 * @return array
		 */
		public static function resolve_report_period( $start_date = null, $end_date = null ) {
			if ( ! empty( $start_date ) && ! empty( $end_date ) ) {
				return array(
					'start'      => sanitize_text_field( $start_date ) . ' 00:00:00',
					'end'        => sanitize_text_field( $end_date ) . ' 23:59:59',
					'start_date' => sanitize_text_field( $start_date ),
					'end_date'   => sanitize_text_field( $end_date ),
				);
			}

			return array(
				'start'      => gmdate( 'Y-m-d 00:00:00', strtotime( '-29 days', current_time( 'timestamp' ) ) ),
				'end'        => current_time( 'Y-m-d 23:59:59' ),
				'start_date' => gmdate( 'Y-m-d', strtotime( '-29 days', current_time( 'timestamp' ) ) ),
				'end_date'   => current_time( 'Y-m-d' ),
			);
		}

		/**
		 * Previous period of equal length for growth comparisons.
		 *
		 * @param array $period Current period from resolve_report_period().
		 * @return array
		 */
		public static function resolve_previous_period( $period ) {
			$start_ts = strtotime( $period['start_date'] . ' 00:00:00' );
			$end_ts   = strtotime( $period['end_date'] . ' 23:59:59' );
			$days     = max( 1, (int) floor( ( $end_ts - $start_ts ) / DAY_IN_SECONDS ) + 1 );

			$previous_end_ts   = strtotime( '-1 day', $start_ts );
			$previous_start_ts = strtotime( '-' . ( $days - 1 ) . ' days', $previous_end_ts );

			return array(
				'start'      => gmdate( 'Y-m-d 00:00:00', $previous_start_ts ),
				'end'        => gmdate( 'Y-m-d 23:59:59', $previous_end_ts ),
				'start_date' => gmdate( 'Y-m-d', $previous_start_ts ),
				'end_date'   => gmdate( 'Y-m-d', $previous_end_ts ),
			);
		}

		/**
		 * Aggregate store sales for a period.
		 *
		 * @param string $start MySQL datetime.
		 * @param string $end   MySQL datetime.
		 * @return array{orders_count:int,units_sold:int,revenue:float,revenue_formatted:string}
		 */
		public static function get_sales_summary( $start, $end ) {
			$empty = array(
				'orders_count'       => 0,
				'units_sold'         => 0,
				'revenue'            => 0.0,
				'revenue_formatted'  => self::format_money( 0 ),
			);

			if ( ! self::is_active() ) {
				return $empty;
			}

			if ( self::order_lookup_table_exists() ) {
				global $wpdb;
				$table = $wpdb->prefix . 'wc_order_product_lookup';
				$row   = $wpdb->get_row(
					$wpdb->prepare(
						"SELECT COUNT(DISTINCT order_id) AS orders_count,
							SUM(product_qty) AS units_sold,
							SUM(product_net_revenue) AS net_revenue
						FROM {$table}
						WHERE date_created >= %s AND date_created <= %s",
						$start,
						$end
					)
				);

				if ( $row ) {
					$revenue = (float) ( $row->net_revenue ?? 0 );
					return array(
						'orders_count'      => absint( $row->orders_count ?? 0 ),
						'units_sold'        => absint( $row->units_sold ?? 0 ),
						'revenue'           => $revenue,
						'revenue_formatted' => self::format_money( $revenue ),
					);
				}
			}

			return self::get_sales_summary_from_orders( $start, $end );
		}

		/**
		 * Per-product sales totals keyed by product ID.
		 *
		 * @param string $start MySQL datetime.
		 * @param string $end   MySQL datetime.
		 * @return array<int,array{units_sold:int,revenue:float,orders_count:int}>
		 */
		public static function get_product_sales_map( $start, $end ) {
			if ( ! self::is_active() ) {
				return array();
			}

			if ( self::order_lookup_table_exists() ) {
				global $wpdb;
				$table = $wpdb->prefix . 'wc_order_product_lookup';
				$rows  = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT product_id,
							SUM(product_qty) AS units_sold,
							SUM(product_net_revenue) AS revenue,
							COUNT(DISTINCT order_id) AS orders_count
						FROM {$table}
						WHERE date_created >= %s AND date_created <= %s
						GROUP BY product_id",
						$start,
						$end
					)
				);

				$map = array();
				foreach ( (array) $rows as $row ) {
					$product_id = absint( $row->product_id ?? 0 );
					if ( ! $product_id ) {
						continue;
					}
					$map[ $product_id ] = array(
						'units_sold'   => absint( $row->units_sold ?? 0 ),
						'revenue'      => (float) ( $row->revenue ?? 0 ),
						'orders_count' => absint( $row->orders_count ?? 0 ),
					);
				}

				return $map;
			}

			return self::get_product_sales_map_from_orders( $start, $end );
		}

		/**
		 * Daily sales trend for charts.
		 *
		 * @param string $start MySQL datetime.
		 * @param string $end   MySQL datetime.
		 * @return array<int,array{date:string,orders_count:int,units_sold:int,revenue:float}>
		 */
		public static function get_daily_sales_trend( $start, $end ) {
			if ( ! self::is_active() ) {
				return array();
			}

			if ( self::order_lookup_table_exists() ) {
				global $wpdb;
				$table = $wpdb->prefix . 'wc_order_product_lookup';
				$rows  = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT DATE(date_created) AS sale_date,
							COUNT(DISTINCT order_id) AS orders_count,
							SUM(product_qty) AS units_sold,
							SUM(product_net_revenue) AS revenue
						FROM {$table}
						WHERE date_created >= %s AND date_created <= %s
						GROUP BY sale_date
						ORDER BY sale_date ASC",
						$start,
						$end
					)
				);

				$data = array();
				foreach ( (array) $rows as $row ) {
					$data[] = array(
						'date'         => (string) $row->sale_date,
						'orders_count' => absint( $row->orders_count ?? 0 ),
						'units_sold'   => absint( $row->units_sold ?? 0 ),
						'revenue'      => (float) ( $row->revenue ?? 0 ),
					);
				}

				return $data;
			}

			return array();
		}

		/**
		 * Product category sales correlated with engagement inputs.
		 *
		 * @param string $start MySQL datetime.
		 * @param string $end   MySQL datetime.
		 * @param int    $limit Max categories.
		 * @return array<int,array{id:int,name:string,revenue:float,units_sold:int}>
		 */
		public static function get_category_sales( $start, $end, $limit = 8 ) {
			if ( ! self::is_active() || ! self::order_lookup_table_exists() ) {
				return array();
			}

			global $wpdb;
			$lookup = $wpdb->prefix . 'wc_order_product_lookup';

			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT t.term_id, t.name,
						SUM(l.product_qty) AS units_sold,
						SUM(l.product_net_revenue) AS revenue
					FROM {$lookup} AS l
					INNER JOIN {$wpdb->term_relationships} AS tr ON l.product_id = tr.object_id
					INNER JOIN {$wpdb->term_taxonomy} AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
					INNER JOIN {$wpdb->terms} AS t ON tt.term_id = t.term_id
					WHERE tt.taxonomy = 'product_cat'
					AND l.date_created >= %s AND l.date_created <= %s
					GROUP BY t.term_id, t.name
					ORDER BY revenue DESC
					LIMIT %d",
					$start,
					$end,
					absint( $limit )
				)
			);

			$data = array();
			foreach ( (array) $rows as $row ) {
				$data[] = array(
					'id'         => (int) $row->term_id,
					'name'       => (string) $row->name,
					'units_sold' => absint( $row->units_sold ?? 0 ),
					'revenue'    => (float) ( $row->revenue ?? 0 ),
				);
			}

			return $data;
		}

		/**
		 * Plain text for JSON/API output — strips tags and decodes entities.
		 *
		 * @param string $value Raw value.
		 * @return string
		 */
		public static function plain_text( $value ) {
			if ( ! is_string( $value ) || '' === $value ) {
				return '';
			}

			return html_entity_decode(
				wp_strip_all_tags( $value ),
				ENT_QUOTES | ENT_HTML5,
				'UTF-8'
			);
		}

		/**
		 * @param float $amount Amount.
		 * @return string
		 */
		public static function format_money( $amount ) {
			$amount   = (float) $amount;
			$decimals = function_exists( 'wc_get_price_decimals' ) ? wc_get_price_decimals() : 2;

			if ( function_exists( 'wc_price' ) ) {
				$formatted = wc_price(
					$amount,
					array(
						'in_span' => false,
					)
				);

				$plain = self::plain_text( $formatted );

				return trim( preg_replace( '/\s+/u', ' ', $plain ) );
			}

			return number_format_i18n( $amount, $decimals );
		}

		/**
		 * @param int $days Lookback window.
		 * @return bool
		 */
		private static function has_recent_orders( $days = 30 ) {
			if ( ! self::is_active() ) {
				return false;
			}

			$period = self::resolve_report_period(
				gmdate( 'Y-m-d', strtotime( '-' . absint( $days ) . ' days', current_time( 'timestamp' ) ) ),
				current_time( 'Y-m-d' )
			);
			$summary = self::get_sales_summary( $period['start'], $period['end'] );

			return ( $summary['orders_count'] ?? 0 ) > 0;
		}

		/**
		 * @return bool
		 */
		private static function has_product_engagement() {
			return ! empty(
				wp_ulike_get_popular_items_ids(
					array(
						'type'     => 'post',
						'rel_type' => 'product',
						'limit'    => 1,
						'period'   => 'all',
					)
				)
			);
		}

		/**
		 * @return bool
		 */
		private static function has_review_engagement() {
			return self::count_product_review_votes( '' ) > 0;
		}

		/**
		 * Count product-review vote rows from Pulse.
		 *
		 * @param string $date_sql Optional SQL date fragment (uses p.date_time).
		 * @return int
		 */
		private static function count_product_review_votes( $date_sql = '' ) {
			global $wpdb;

			$pulse_table = esc_sql( wp_ulike_pro_pulse_table() );
			$kind_vote   = esc_sql( WP_Ulike_Pulse_Registry::KIND_VOTE );
			$date_sql    = str_replace( 't.date_time', 'p.date_time', $date_sql );

			$total = (int) $wpdb->get_var(
				"SELECT COUNT(*) FROM `{$pulse_table}` AS p
				INNER JOIN {$wpdb->comments} AS c ON p.item_id = c.comment_ID
				INNER JOIN {$wpdb->posts} AS po ON c.comment_post_ID = po.ID
				WHERE p.item_type = 'comment'
				AND p.engagement_kind = '{$kind_vote}'
				AND p.status = 'active'
				AND c.comment_type = 'review'
				AND po.post_type = 'product'
				{$date_sql}"
			);

			return $total;
		}

		/**
		 * @param string $content_type post|product_review.
		 * @param string $group        woocommerce.
		 * @return bool
		 */
		private static function has_active_display_rule( $content_type, $group ) {
			if ( ! class_exists( 'WP_Ulike_Pro_Display_Automation' ) ) {
				return false;
			}

			$rules = get_option( WP_Ulike_Pro_Display_Automation::OPTION_KEY, array() );
			if ( empty( $rules ) || ! is_array( $rules ) ) {
				return false;
			}

			foreach ( $rules as $rule ) {
				if ( empty( $rule['enabled'] ) ) {
					continue;
				}
				if ( ( $rule['content_type'] ?? '' ) !== $content_type ) {
					continue;
				}
				if ( ( $rule['placement_group'] ?? '' ) !== $group ) {
					continue;
				}
				return true;
			}

			return false;
		}

		/**
		 * @return bool
		 */
		private static function order_lookup_table_exists() {
			global $wpdb;
			$table = $wpdb->prefix . 'wc_order_product_lookup';

			return $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table;
		}

		/**
		 * Fallback sales summary via wc_get_orders().
		 *
		 * @param string $start MySQL datetime.
		 * @param string $end   MySQL datetime.
		 * @return array
		 */
		private static function get_sales_summary_from_orders( $start, $end ) {
			if ( ! function_exists( 'wc_get_orders' ) ) {
				return array(
					'orders_count'      => 0,
					'units_sold'        => 0,
					'revenue'           => 0.0,
					'revenue_formatted' => self::format_money( 0 ),
				);
			}

			$orders = wc_get_orders(
				array(
					'status'       => array( 'wc-completed', 'wc-processing' ),
					'date_created' => $start . '...' . $end,
					'limit'        => -1,
					'return'       => 'objects',
				)
			);

			$orders_count = 0;
			$units_sold   = 0;
			$revenue      = 0.0;

			foreach ( (array) $orders as $order ) {
				++$orders_count;
				$revenue += (float) $order->get_total();
				foreach ( $order->get_items() as $item ) {
					$units_sold += absint( $item->get_quantity() );
				}
			}

			return array(
				'orders_count'      => $orders_count,
				'units_sold'        => $units_sold,
				'revenue'           => $revenue,
				'revenue_formatted' => self::format_money( $revenue ),
			);
		}

		/**
		 * Fallback per-product sales via wc_get_orders().
		 *
		 * @param string $start MySQL datetime.
		 * @param string $end   MySQL datetime.
		 * @return array
		 */
		private static function get_product_sales_map_from_orders( $start, $end ) {
			if ( ! function_exists( 'wc_get_orders' ) ) {
				return array();
			}

			$orders = wc_get_orders(
				array(
					'status'       => array( 'wc-completed', 'wc-processing' ),
					'date_created' => $start . '...' . $end,
					'limit'        => -1,
					'return'       => 'objects',
				)
			);

			$map = array();
			foreach ( (array) $orders as $order ) {
				$order_id = $order->get_id();
				foreach ( $order->get_items() as $item ) {
					$product_id = absint( $item->get_product_id() );
					if ( ! $product_id ) {
						continue;
					}
					if ( ! isset( $map[ $product_id ] ) ) {
						$map[ $product_id ] = array(
							'units_sold'   => 0,
							'revenue'      => 0.0,
							'orders_count' => 0,
						);
					}
					$map[ $product_id ]['units_sold']   += absint( $item->get_quantity() );
					$map[ $product_id ]['revenue']      += (float) $item->get_total();
					$map[ $product_id ]['orders_count'] += 1;
				}
			}

			return $map;
		}
	}

	WP_Ulike_Pro_WooCommerce::init();
}

