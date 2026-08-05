<?php
/**
 * @package WP_Ulike_Pro
 */
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

trait WP_Ulike_Pro_Stats_Trait_WooCommerce {
		public function get_woocommerce_api_data( $start_date = null, $end_date = null ) {
			if ( ! class_exists( 'WP_Ulike_Pro_WooCommerce' ) || ! WP_Ulike_Pro_WooCommerce::is_active() ) {
				return array(
					'available' => false,
				);
			}

			$nav_meta = WP_Ulike_Pro_WooCommerce::get_stats_nav_meta();
			if ( empty( $nav_meta['report_available'] ) ) {
				return array(
					'available' => false,
					'woocommerce' => $nav_meta,
				);
			}

			$cache_key = 'woocommerce_api_' . md5(
				wp_json_encode(
					array(
						'start' => $start_date,
						'end'   => $end_date,
					)
				)
			);
			$cached = wp_cache_get( $cache_key, WP_ULIKE_PRO_DOMAIN );
			if ( false !== $cached && is_array( $cached ) ) {
				return $cached;
			}

			$period          = WP_Ulike_Pro_WooCommerce::resolve_report_period( $start_date, $end_date );
			$previous_period = WP_Ulike_Pro_WooCommerce::resolve_previous_period( $period );
			$date_filter     = array(
				'start' => $period['start_date'],
				'end'   => $period['end_date'],
			);

			$product_engagement  = $this->count_post_type_engagement( 'product', $date_filter );
			$previous_product_engagement = $this->count_post_type_engagement(
				'product',
				array(
					'start' => $previous_period['start_date'],
					'end'   => $previous_period['end_date'],
				)
			);
			$review_engagement = $this->count_product_review_engagement( $date_filter );
			$previous_review_engagement = $this->count_product_review_engagement(
				array(
					'start' => $previous_period['start_date'],
					'end'   => $previous_period['end_date'],
				)
			);

			$sales_summary          = WP_Ulike_Pro_WooCommerce::get_sales_summary( $period['start'], $period['end'] );
			$previous_sales_summary = WP_Ulike_Pro_WooCommerce::get_sales_summary(
				$previous_period['start'],
				$previous_period['end']
			);

			$ranked_products = $this->get_woocommerce_product_rows( $date_filter, 50 );
			$products        = array_slice( $ranked_products, 0, 12 );
			$opportunities   = $this->build_woocommerce_opportunities( $ranked_products );
			$categories      = $this->get_woocommerce_category_correlation(
				$period['start'],
				$period['end'],
				$date_filter,
				8
			);

			$engagement_total = $product_engagement + $review_engagement;
			$summary          = array(
				'product_engagement'         => $product_engagement,
				'product_engagement_growth'  => $this->calculate_period_growth( $product_engagement, $previous_product_engagement ),
				'review_engagement'          => $review_engagement,
				'review_engagement_growth'   => $this->calculate_period_growth( $review_engagement, $previous_review_engagement ),
				'orders_count'               => $sales_summary['orders_count'],
				'orders_growth'              => $this->calculate_period_growth(
					$sales_summary['orders_count'],
					$previous_sales_summary['orders_count']
				),
				'units_sold'                 => $sales_summary['units_sold'],
				'units_sold_growth'          => $this->calculate_period_growth(
					$sales_summary['units_sold'],
					$previous_sales_summary['units_sold']
				),
				'revenue'                    => $sales_summary['revenue'],
				'revenue_formatted'          => $sales_summary['revenue_formatted'],
				'revenue_growth'             => $this->calculate_period_growth(
					$sales_summary['revenue'],
					$previous_sales_summary['revenue']
				),
				'engagement_per_order'       => $sales_summary['orders_count'] > 0
					? round( $engagement_total / $sales_summary['orders_count'], 2 )
					: null,
				'revenue_per_engagement'     => $engagement_total > 0
					? round( $sales_summary['revenue'] / $engagement_total, 2 )
					: null,
				'revenue_per_engagement_fmt' => $engagement_total > 0
					? WP_Ulike_Pro_WooCommerce::format_money( $sales_summary['revenue'] / $engagement_total )
					: null,
				'average_order_value'         => $sales_summary['orders_count'] > 0
					? round( $sales_summary['revenue'] / $sales_summary['orders_count'], 2 )
					: null,
				'average_order_value_formatted' => $sales_summary['orders_count'] > 0
					? WP_Ulike_Pro_WooCommerce::format_money(
						$sales_summary['revenue'] / $sales_summary['orders_count']
					)
					: null,
				'average_order_value_growth'  => $this->calculate_period_growth(
					$sales_summary['orders_count'] > 0
						? $sales_summary['revenue'] / $sales_summary['orders_count']
						: 0,
					$previous_sales_summary['orders_count'] > 0
						? $previous_sales_summary['revenue'] / $previous_sales_summary['orders_count']
						: 0
				),
			);

			$payload = array(
				'available'    => true,
				'woocommerce'  => $nav_meta,
				'period'       => array(
					'start' => $period['start_date'],
					'end'   => $period['end_date'],
				),
				'summary'      => $summary,
				'products'     => $products,
				'categories'   => $categories,
				'opportunities'=> $opportunities,
				'daily_trend'  => array(
					'engagement' => $this->get_woocommerce_daily_engagement_trend( $date_filter ),
					'sales'      => WP_Ulike_Pro_WooCommerce::get_daily_sales_trend( $period['start'], $period['end'] ),
				),
				'insights'     => $this->get_woocommerce_insights(
					$summary,
					$products,
					$opportunities,
					$nav_meta
				),
			);

			$payload = apply_filters( 'wp_ulike_pro_woocommerce_api_data', $payload, $period, $date_filter );
			wp_cache_set( $cache_key, $payload, WP_ULIKE_PRO_DOMAIN, 60 );

			return $payload;
		}

		/**
		 * Count likes/dislikes on WooCommerce product reviews.
		 *
		 * @param mixed $date Period filter.
		 * @return int
		 */
		private function count_product_review_engagement( $date = 'month' ) {
			$cache_key = sanitize_key(
				'product_review_eng_' . ( is_array( $date ) ? wp_json_encode( $date ) : $date )
			);
			$cached = wp_cache_get( $cache_key, WP_ULIKE_PRO_DOMAIN );

			if ( false !== $cached ) {
				return (int) $cached;
			}

			$date_sql = is_array( $date )
				? $this->build_activity_date_where( $date )
				: wp_ulike_get_period_limit_sql( $date );

			$pulse_table = $this->stats_pulse_table_esc();
			$mode        = $this->stats_pulse_read_mode();
			$since       = $this->stats_pulse_all_kinds_since_sql( 'p' );

			$union = array();

			if ( 'legacy' !== $mode ) {
				$rows_sql = $this->stats_pulse_all_active_rows_sql( 'p' );
				$union[]  = "SELECT 1 FROM `{$pulse_table}` AS p
					INNER JOIN {$this->wpdb->comments} AS c ON p.item_id = c.comment_ID
					INNER JOIN {$this->wpdb->posts} AS po ON c.comment_post_ID = po.ID
					WHERE p.item_type = 'comment'
					AND {$rows_sql}{$since}
					AND c.comment_type = 'review'
					AND po.post_type = 'product'
					{$date_sql}";
			} elseif ( $this->stats_pulse_table_available() ) {
				$pro_rows = $this->stats_pulse_pro_rows_sql( 'p' );
				$union[]  = "SELECT 1 FROM `{$pulse_table}` AS p
					INNER JOIN {$this->wpdb->comments} AS c ON p.item_id = c.comment_ID
					INNER JOIN {$this->wpdb->posts} AS po ON c.comment_post_ID = po.ID
					WHERE p.item_type = 'comment'
					AND {$pro_rows}
					AND c.comment_type = 'review'
					AND po.post_type = 'product'
					{$date_sql}";
			}

			if ( 'legacy' === $mode || 'merged' === $mode ) {
				$source = class_exists( 'WP_Ulike_Pulse_Registry' )
					? WP_Ulike_Pulse_Registry::legacy_source_for_type( 'comment' )
					: null;
				if ( $source && WP_Ulike_Pulse_Registry::table_exists( $source['table'] ) ) {
					$lt  = esc_sql( $source['table'] );
					$col = esc_sql( $source['column'] );
					$union[] = "SELECT 1 FROM `{$lt}` AS l
						INNER JOIN {$this->wpdb->comments} AS c ON l.`{$col}` = c.comment_ID
						INNER JOIN {$this->wpdb->posts} AS po ON c.comment_post_ID = po.ID
						WHERE l.status IN ('like','dislike')
						AND c.comment_type = 'review'
						AND po.post_type = 'product'
						{$date_sql}";
				}
			}

			$count = 0;
			if ( ! empty( $union ) ) {
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- fragments built internally.
				$count = (int) $this->wpdb->get_var(
					'SELECT COUNT(*) FROM (' . implode( ' UNION ALL ', $union ) . ') AS combined'
				);
			}

			wp_cache_set( $cache_key, $count, WP_ULIKE_PRO_DOMAIN, 10 );

			return $count;
		}

		/**
		 * Top products with engagement and sales metrics.
		 *
		 * @param array $date_filter Date filter.
		 * @param int   $limit       Max products.
		 * @return array
		 */
		private function get_woocommerce_product_rows( $date_filter, $limit = 12 ) {
			$period = is_array( $date_filter ) && ! empty( $date_filter['start'] ) && ! empty( $date_filter['end'] )
				? WP_Ulike_Pro_WooCommerce::resolve_report_period( $date_filter['start'], $date_filter['end'] )
				: WP_Ulike_Pro_WooCommerce::resolve_report_period();

			$sales_map_cache_key = 'wc_product_sales_' . md5( $period['start'] . '|' . $period['end'] );
			$sales_map           = wp_cache_get( $sales_map_cache_key, WP_ULIKE_PRO_DOMAIN );
			if ( false === $sales_map ) {
				$sales_map = WP_Ulike_Pro_WooCommerce::get_product_sales_map( $period['start'], $period['end'] );
				wp_cache_set( $sales_map_cache_key, $sales_map, WP_ULIKE_PRO_DOMAIN, 60 );
			}

			$top = $this->get_top(
				array(
					'type'                  => 'post',
					'rel_type'              => 'product',
					'limit'                 => max( 1, absint( $limit ) ),
					'offset'                => 1,
					'status'                => array( 'like', 'dislike' ),
					'is_popular'            => true,
					'period'                => $date_filter,
					'include_engaged_users' => false,
				)
			);

			$rows = array();

			foreach ( (array) ( $top['items'] ?? array() ) as $item ) {
				$product_id = absint( $item['id'] ?? 0 );
				if ( ! $product_id ) {
					continue;
				}

				$sales = $sales_map[ $product_id ] ?? array(
					'units_sold'   => 0,
					'revenue'      => 0.0,
					'orders_count' => 0,
				);

				$engagement_count = absint( $item['likes_count'] ?? 0 )
				+ absint( $item['dislikes_count'] ?? 0 )
				+ absint( $item['engagement']['emoji_count'] ?? 0 )
				+ absint( $item['engagement']['star_count'] ?? 0 );

				$rows[] = array(
					'id'               => $product_id,
					'title'            => class_exists( 'WP_Ulike_Pro_WooCommerce' )
						? WP_Ulike_Pro_WooCommerce::plain_text( $item['title'] ?? '' )
						: ( $item['title'] ?? '' ),
					'permalink'        => $item['permalink'] ?? '',
					'image'            => $item['image'] ?? '',
					'likes_count'      => absint( $item['likes_count'] ?? 0 ),
					'dislikes_count'   => absint( $item['dislikes_count'] ?? 0 ),
					'engagement_count' => $engagement_count,
					'engagement'       => $item['engagement'] ?? array(),
					'emoji_breakdown'  => $item['emoji_breakdown'] ?? array(),
					'units_sold'       => absint( $sales['units_sold'] ?? 0 ),
					'orders_count'     => absint( $sales['orders_count'] ?? 0 ),
					'revenue'          => (float) ( $sales['revenue'] ?? 0 ),
					'revenue_formatted'=> WP_Ulike_Pro_WooCommerce::format_money( $sales['revenue'] ?? 0 ),
					'engagement_score' => $this->calculate_engagement_sales_score(
						$engagement_count,
						(float) ( $sales['revenue'] ?? 0 ),
						absint( $sales['units_sold'] ?? 0 )
					),
				);
			}

			return $rows;
		}

		/**
		 * Products worth attention — engagement vs sales gaps.
		 *
		 * @param array $date_filter Date filter.
		 * @return array
		 */
		private function get_woocommerce_opportunities( $date_filter ) {
			return $this->build_woocommerce_opportunities(
				$this->get_woocommerce_product_rows( $date_filter, 50 )
			);
		}

		/**
		 * Build opportunity lists from preloaded product rows.
		 *
		 * @param array $products Ranked product rows.
		 * @return array
		 */
		private function build_woocommerce_opportunities( $products ) {
			if ( count( $products ) < 3 ) {
				return array(
					'high_engagement_low_sales' => array(),
					'high_sales_low_engagement' => array(),
				);
			}

			$engagements = array_column( $products, 'engagement_count' );
			$revenues    = array_column( $products, 'revenue' );
			sort( $engagements );
			sort( $revenues );

			$engagement_median = $engagements[ (int) floor( count( $engagements ) / 2 ) ];
			$revenue_median    = $revenues[ (int) floor( count( $revenues ) / 2 ) ];

			$high_engagement_low_sales = array();
			$high_sales_low_engagement = array();

			foreach ( $products as $product ) {
				if ( $product['engagement_count'] >= $engagement_median && $product['revenue'] <= $revenue_median ) {
					$high_engagement_low_sales[] = $product;
				}
				if ( $product['revenue'] >= $revenue_median && $product['engagement_count'] <= $engagement_median ) {
					$high_sales_low_engagement[] = $product;
				}
			}

			usort(
				$high_engagement_low_sales,
				function ( $a, $b ) {
					return $b['engagement_count'] <=> $a['engagement_count'];
				}
			);
			usort(
				$high_sales_low_engagement,
				function ( $a, $b ) {
					return $b['revenue'] <=> $a['revenue'];
				}
			);

			return array(
				'high_engagement_low_sales' => array_slice( $high_engagement_low_sales, 0, 5 ),
				'high_sales_low_engagement' => array_slice( $high_sales_low_engagement, 0, 5 ),
			);
		}

		/**
		 * Product categories with both engagement and revenue.
		 *
		 * @param string $sales_start   MySQL datetime.
		 * @param string $sales_end     MySQL datetime.
		 * @param array  $date_filter   Engagement date filter.
		 * @param int    $limit         Max categories.
		 * @return array
		 */
		private function get_woocommerce_category_correlation( $sales_start, $sales_end, $date_filter, $limit = 8 ) {
			$engagement_rows = $this->query_taxonomy_engagement(
				'product_cat',
				array( 'product' ),
				$limit,
				$date_filter
			);
			$sales_rows = WP_Ulike_Pro_WooCommerce::get_category_sales( $sales_start, $sales_end, $limit );

			$sales_by_id = array();
			foreach ( $sales_rows as $row ) {
				$sales_by_id[ (int) $row['id'] ] = $row;
			}

			$merged = array();
			foreach ( $engagement_rows as $row ) {
				$id    = (int) $row['id'];
				$sales = $sales_by_id[ $id ] ?? array(
					'units_sold' => 0,
					'revenue'    => 0.0,
				);

				$merged[] = array(
					'id'               => $id,
					'name'             => $row['name'],
					'engagement_count' => absint( $row['count'] ?? 0 ),
					'units_sold'       => absint( $sales['units_sold'] ?? 0 ),
					'revenue'          => (float) ( $sales['revenue'] ?? 0 ),
					'revenue_formatted'=> WP_Ulike_Pro_WooCommerce::format_money( $sales['revenue'] ?? 0 ),
				);
			}

			usort(
				$merged,
				function ( $a, $b ) {
					return $b['engagement_count'] <=> $a['engagement_count'];
				}
			);

			return array_slice( $merged, 0, $limit );
		}

		/**
		 * Actionable insights for shop managers.
		 *
		 * @param array $summary       Summary metrics.
		 * @param array $products      Top products.
		 * @param array $opportunities Opportunity lists.
		 * @param array $nav_meta      WooCommerce nav meta.
		 * @return array
		 */
		private function get_woocommerce_insights( $summary, $products, $opportunities, $nav_meta ) {
			$tips = array();

			if ( ! empty( $opportunities['high_engagement_low_sales'][0]['title'] ) ) {
				$item = $opportunities['high_engagement_low_sales'][0];
				$tips[] = array(
					'type'    => 'warning',
					'message' => sprintf(
						/* translators: 1: product title, 2: engagement count */
						esc_html__( '%1$s gets strong interest (%2$s engagements) but lower sales — review pricing, photos, stock, or checkout friction.', 'wp-ulike-pro' ),
						$item['title'],
						number_format_i18n( $item['engagement_count'] ?? 0 )
					),
				);
			}

			if ( ! empty( $opportunities['high_sales_low_engagement'][0]['title'] ) ) {
				$item = $opportunities['high_sales_low_engagement'][0];
				$tips[] = array(
					'type'    => 'info',
					'message' => sprintf(
						/* translators: %s: product title */
						esc_html__( '%s sells well with few likes — add or highlight like buttons to capture social proof.', 'wp-ulike-pro' ),
						$item['title']
					),
				);
			}

			if ( ! empty( $summary['engagement_per_order'] ) && $summary['engagement_per_order'] >= 1 ) {
				$tips[] = array(
					'type'    => 'positive',
					'message' => sprintf(
						/* translators: %s: engagements per order */
						esc_html__( 'Shoppers engage before buying — about %s reactions per order in this period.', 'wp-ulike-pro' ),
						number_format_i18n( $summary['engagement_per_order'], 1 )
					),
				);
			}

			if ( empty( $nav_meta['review_likes'] ) && ! empty( $nav_meta['product_likes'] ) ) {
				$tips[] = array(
					'type'    => 'info',
					'message' => esc_html__( 'Enable likes on product reviews to learn which feedback drives purchase decisions.', 'wp-ulike-pro' ),
				);
			} elseif ( ( $summary['review_engagement'] ?? 0 ) > 0 ) {
				$tips[] = array(
					'type'    => 'positive',
					'message' => sprintf(
						/* translators: %s: review engagement count */
						esc_html__( 'Review reactions are active — %s engagements on customer reviews this period.', 'wp-ulike-pro' ),
						number_format_i18n( $summary['review_engagement'] )
					),
				);
			}

			if ( ! empty( $products[0]['title'] ) ) {
				$leader = $products[0];
				$tips[] = array(
					'type'    => 'positive',
					'message' => sprintf(
						/* translators: 1: product title, 2: likes count, 3: revenue formatted */
						esc_html__( '%1$s leads engagement with %2$s likes and %3$s in sales — double down in campaigns.', 'wp-ulike-pro' ),
						$leader['title'],
						number_format_i18n( $leader['likes_count'] ?? 0 ),
						$leader['revenue_formatted'] ?? WP_Ulike_Pro_WooCommerce::format_money( 0 )
					),
				);
			}

			return array_slice( $tips, 0, 5 );
		}

		/**
		 * Daily product engagement counts.
		 *
		 * @param array $date_filter Date filter.
		 * @return array
		 */
		private function get_woocommerce_daily_engagement_trend( $date_filter ) {
			$date_sql    = $this->build_activity_date_where( $date_filter );
			$pulse_table = $this->stats_pulse_table_esc();
			$mode        = $this->stats_pulse_read_mode();
			$since       = $this->stats_pulse_all_kinds_since_sql( 'p' );

			$union = array();

			if ( 'legacy' !== $mode ) {
				$rows_sql = $this->stats_pulse_all_active_rows_sql( 'p' );
				$union[]  = "SELECT DATE(p.date_time) AS vote_date
					FROM `{$pulse_table}` AS p
					INNER JOIN {$this->wpdb->posts} AS po ON p.item_id = po.ID
					WHERE p.item_type = 'post'
					AND {$rows_sql}{$since}
					AND po.post_type = 'product'
					{$date_sql}";
			} elseif ( $this->stats_pulse_table_available() ) {
				$pro_rows = $this->stats_pulse_pro_rows_sql( 'p' );
				$union[]  = "SELECT DATE(p.date_time) AS vote_date
					FROM `{$pulse_table}` AS p
					INNER JOIN {$this->wpdb->posts} AS po ON p.item_id = po.ID
					WHERE p.item_type = 'post'
					AND {$pro_rows}
					AND po.post_type = 'product'
					{$date_sql}";
			}

			if ( 'legacy' === $mode || 'merged' === $mode ) {
				$source = class_exists( 'WP_Ulike_Pulse_Registry' )
					? WP_Ulike_Pulse_Registry::legacy_source_for_type( 'post' )
					: null;
				if ( $source && WP_Ulike_Pulse_Registry::table_exists( $source['table'] ) ) {
					$lt  = esc_sql( $source['table'] );
					$col = esc_sql( $source['column'] );
					$union[] = "SELECT DATE(l.date_time) AS vote_date
						FROM `{$lt}` AS l
						INNER JOIN {$this->wpdb->posts} AS po ON l.`{$col}` = po.ID
						WHERE l.status IN ('like','dislike')
						AND po.post_type = 'product'
						{$date_sql}";
				}
			}

			$data = array();
			if ( empty( $union ) ) {
				return $data;
			}

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- fragments built internally.
			$rows = $this->wpdb->get_results(
				'SELECT vote_date, COUNT(*) AS total_count FROM ('
				. implode( ' UNION ALL ', $union )
				. ') AS combined GROUP BY vote_date ORDER BY vote_date ASC'
			);

			foreach ( (array) $rows as $row ) {
				$data[] = array(
					'date'  => (string) $row->vote_date,
					'count' => absint( $row->total_count ?? 0 ),
				);
			}

			return $data;
		}

		/**
		 * Normalized score combining engagement and sales (higher = stronger match).
		 *
		 * @param int   $engagement Engagement count.
		 * @param float $revenue    Revenue amount.
		 * @param int   $units      Units sold.
		 * @return float
		 */
		private function calculate_engagement_sales_score( $engagement, $revenue, $units ) {
			if ( $engagement <= 0 && $revenue <= 0 && $units <= 0 ) {
				return 0.0;
			}

			$engagement_score = min( 100, $engagement * 5 );
			$sales_score      = min( 100, ( $units * 10 ) + min( 50, $revenue / 10 ) );

			if ( $engagement <= 0 || $revenue <= 0 ) {
				return round( max( $engagement_score, $sales_score ) * 0.45, 1 );
			}

			return round( sqrt( $engagement_score * $sales_score ), 1 );
		}

		/**
		 * Percent growth between two values.
		 *
		 * @param float|int $current  Current value.
		 * @param float|int $previous Previous value.
		 * @return float|null
		 */
		private function calculate_period_growth( $current, $previous ) {
			$current  = (float) $current;
			$previous = (float) $previous;

			if ( $previous <= 0 ) {
				return $current > 0 ? 100.0 : null;
			}

			return round( ( ( $current - $previous ) / $previous ) * 100, 1 );
		}

		/**
		 * Resolve date filter for intelligence queries.
		 *
		 * @param string|null $start_date Start date.
		 * @param string|null $end_date   End date.
		 * @return array
		 */
}

