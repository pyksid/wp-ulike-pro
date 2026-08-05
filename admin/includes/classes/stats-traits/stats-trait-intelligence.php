<?php
/**
 * @package WP_Ulike_Pro
 */
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

trait WP_Ulike_Pro_Stats_Trait_Intelligence {
		public function get_site_intelligence() {
			$current_period = array(
				'interval_value' => 30,
				'interval_unit'  => 'DAY',
			);
			$previous_period = array(
				'start' => gmdate( 'Y-m-d', strtotime( '-60 days' ) ),
				'end'   => gmdate( 'Y-m-d', strtotime( '-31 days' ) ),
			);

			$current_engagements  = $this->count_engagements_all_tables( $current_period );
			$previous_engagements = $this->count_engagements_all_tables( $previous_period );
			$current_views        = $this->count_views_for_period( $current_period );
			$previous_views       = $this->count_views_for_period( $previous_period );

			$engagement_rate = 0;
			if ( $current_views > 0 ) {
				$engagement_rate = round( ( $current_engagements / $current_views ) * 100, 2 );
			}

			$previous_rate = 0;
			if ( $previous_views > 0 ) {
				$previous_rate = round( ( $previous_engagements / $previous_views ) * 100, 2 );
			}

			$unique_engagers  = $this->count_unique_engagers_all_tables( $current_period );
			$prev_engagers    = $this->count_unique_engagers_all_tables( $previous_period );
			$unique_voters    = $this->count_unique_voters_all_tables( $current_period );
			$prev_voters      = $this->count_unique_voters_all_tables( $previous_period );
			$sentiment        = $this->get_sentiment_breakdown( $current_period );
			$views_tracking   = $this->button_views && $this->is_any_view_tracking_enabled();
			$stats_meta       = WP_Ulike_Pro_Stats_Meta::get_site_stats_meta( array_keys( $this->tables ) );
			$vote_engagements = 0;
			if ( class_exists( 'WP_Ulike_Pulse_Query' ) ) {
				foreach ( array_keys( $this->tables ) as $type_key ) {
					if ( ! WP_Ulike_Pro_Stats_Type_Resolver::is_type_available( $type_key ) ) {
						continue;
					}
					$item_type         = WP_Ulike_Pro_Stats_Type_Resolver::map_stats_type_to_item_type( $type_key );
					$vote_engagements += (int) WP_Ulike_Pulse_Query::count_vote_logs_for_type( $item_type, $current_period );
				}
			}

			$engagement_summary = array(
				'emoji_total'        => 0,
				'star_ratings_total' => 0,
				'site_star_average'  => 0,
				'has_votes'          => $vote_engagements > 0,
				'has_emoji'          => false,
				'has_star'           => false,
			);

		if ( class_exists( 'WP_Ulike_Pro_Stats_Engagement_Reader' ) ) {
			$reader     = WP_Ulike_Pro_Stats_Engagement_Reader::get_instance();
			$star_sum   = 0;
			$star_count = 0;

			foreach ( array_keys( $this->tables ) as $type_key ) {
				if ( ! WP_Ulike_Pro_Stats_Type_Resolver::is_type_available( $type_key ) ) {
					continue;
				}
				$item_type = WP_Ulike_Pro_Stats_Type_Resolver::map_stats_type_to_item_type( $type_key );
				// Always count emoji + star regardless of the type's current
				// template mode so display-automation renders and historical
				// engagement data are reflected in the engagement summary.
				$engagement_summary['emoji_total'] += (int) $reader->count_logs( $item_type, $current_period, 'emoji' );

				// Period-aligned star summary (same window as emoji / rate).
				$stars       = $reader->aggregate_stars_for_type( $item_type, $current_period );
				$star_count += (int) $stars['count'];
				$star_sum   += (int) $stars['sum'];
			}

			$engagement_summary['star_ratings_total'] = $star_count;
			$engagement_summary['has_emoji']          = $engagement_summary['emoji_total'] > 0;
			$engagement_summary['has_star']           = $star_count > 0;
			if ( $star_count > 0 ) {
				$engagement_summary['site_star_average'] = round( $star_sum / $star_count, 1 );
			}
		}

			$has_negative_signal = ! empty( $sentiment['negative'] ) || ! empty( $sentiment['dislikes'] );
			if ( empty( $stats_meta['dislikes_enabled'] ) && ! $has_negative_signal ) {
				$sentiment['dislikes_available'] = false;
				// Like-only / all-positive period — keep ratio at 100% when any positive exists.
				$sentiment['like_ratio'] = ( ! empty( $sentiment['positive'] ) || ! empty( $sentiment['likes'] ) ) ? 100 : 0;
			} else {
				$sentiment['dislikes_available'] = ! empty( $stats_meta['dislikes_enabled'] ) || $has_negative_signal;
			}

			return array_merge(
				array(
					'engagement_rate'         => $engagement_rate,
					'engagement_rate_growth'  => $this->calculate_growth_percent( $engagement_rate, $previous_rate ),
					'unique_engagers'         => $unique_engagers,
					'unique_engagers_growth'  => $this->calculate_growth_percent( $unique_engagers, $prev_engagers ),
					'unique_voters'           => $unique_voters,
					'unique_voters_growth'    => $this->calculate_growth_percent( $unique_voters, $prev_voters ),
					'total_engagements'       => $current_engagements,
					'engagements_growth'      => $this->calculate_growth_percent( $current_engagements, $previous_engagements ),
					'total_views'             => $current_views,
					'views_growth'            => $this->calculate_growth_percent( $current_views, $previous_views ),
					'sentiment'               => $sentiment,
					'views_tracking_enabled'  => $views_tracking,
					'engagement_summary'      => $engagement_summary,
				),
				$stats_meta
			);
		}

		/**
		 * Content intelligence report payload.
		 *
		 * @param string|null $start_date Start date Y-m-d.
		 * @param string|null $end_date   End date Y-m-d.
		 * @return array
		 */
		public function get_intelligence_api_data( $start_date = null, $end_date = null ) {
			$date_filter = $this->resolve_intelligence_date_filter( $start_date, $end_date );

			return array(
				'schedule'   => $this->get_activity_schedule( $date_filter ),
				'categories' => $this->get_category_performance( 12, $date_filter ),
			);
		}

		/**
		 * WooCommerce commerce intelligence report.
		 *
		 * @param string|null $start_date Start date (Y-m-d).
		 * @param string|null $end_date   End date (Y-m-d).
		 * @return array
		 */
		private function resolve_intelligence_date_filter( $start_date = null, $end_date = null ) {
			if ( ! empty( $start_date ) && ! empty( $end_date ) ) {
				return array(
					'start' => sanitize_text_field( $start_date ),
					'end'   => sanitize_text_field( $end_date ),
				);
			}

			return array( 'interval' => '30 DAY' );
		}

		/**
		 * Activity schedule — heatmap, time windows, and publishing insights.
		 *
		 * @param array $args Date filter and options.
		 * @return array
		 */
		public function get_category_performance( $limit = 6, $date_filter = null ) {
			if ( null === $date_filter ) {
				$date_filter = array( 'interval' => '30 DAY' );
			}

			$cache_key = 'category_performance_' . md5( wp_json_encode( array( $limit, $date_filter ) ) );
			$cached    = wp_cache_get( $cache_key, WP_ULIKE_PRO_DOMAIN );

			if ( false !== $cached ) {
				return $cached;
			}

			$segments = array();

			if ( $this->stats_type_has_activity( 'posts' ) ) {
				$segments[] = array(
					'taxonomy' => $this->get_primary_post_taxonomy(),
					'label'    => $this->get_taxonomy_label( $this->get_primary_post_taxonomy() ),
					'post_types' => array( 'post', 'page' ),
				);
			}

			if ( class_exists( 'WooCommerce' ) && $this->has_post_type_engagement( 'product' ) ) {
				$segments[] = array(
					'taxonomy'   => 'product_cat',
					'label'      => esc_html__( 'Product categories', 'wp-ulike-pro' ),
					'post_types' => array( 'product' ),
				);
			}

			if ( function_exists( 'edd_get_download' ) && $this->has_post_type_engagement( 'download' ) ) {
				$segments[] = array(
					'taxonomy'   => 'download_category',
					'label'      => esc_html__( 'Download categories', 'wp-ulike-pro' ),
					'post_types' => array( 'download' ),
				);
			}

			$categories  = array();
			$per_segment = max( 2, (int) ceil( $limit / max( 1, count( $segments ) ) ) );

			foreach ( $segments as $segment ) {
				$rows = $this->query_taxonomy_engagement(
					$segment['taxonomy'],
					$segment['post_types'],
					$per_segment,
					$date_filter
				);

				foreach ( $rows as $row ) {
					$categories[] = array_merge(
						$row,
						array(
							'taxonomy'       => $segment['taxonomy'],
							'taxonomy_label' => $segment['label'],
							'post_type'      => $segment['post_types'][0] ?? 'post',
						)
					);
				}
			}

			usort(
				$categories,
				function( $a, $b ) {
					return ( $b['count'] ?? 0 ) <=> ( $a['count'] ?? 0 );
				}
			);

			$categories = array_slice( $categories, 0, max( 1, (int) $limit ) );
			$total      = array_sum( array_column( $categories, 'count' ) );

			foreach ( $categories as $index => $category ) {
				$categories[ $index ]['share'] = $total > 0
					? round( ( $category['count'] / $total ) * 100, 1 )
					: 0;
			}

			wp_cache_set( $cache_key, $categories, WP_ULIKE_PRO_DOMAIN, 10 );
			return $categories;
		}

		/**
		 * Commerce spotlight — top products or downloads when a shop is present.
		 *
		 * @return array|null
		 */
		public function get_commerce_highlights() {
			$commerce_types = array();

			if ( class_exists( 'WooCommerce' ) && $this->has_post_type_engagement( 'product' ) ) {
				$commerce_types['product'] = esc_html__( 'Products', 'wp-ulike-pro' );
			}

			if ( function_exists( 'edd_get_download' ) && $this->has_post_type_engagement( 'download' ) ) {
				$commerce_types['download'] = esc_html__( 'Downloads', 'wp-ulike-pro' );
			}

			if ( empty( $commerce_types ) ) {
				return null;
			}

			$spotlight = null;
			$max_count = 0;

			foreach ( $commerce_types as $post_type => $label ) {
				$count = $this->count_post_type_engagement( $post_type, 'month' );
				if ( $count > $max_count ) {
					$max_count = $count;
					$spotlight = array(
						'post_type' => $post_type,
						'label'     => $label,
						'period_total' => $count,
					);
				}
			}

			if ( ! $spotlight ) {
				return null;
			}

			$top = $this->get_top(
				array(
					'type'       => 'post',
					'rel_type'   => $spotlight['post_type'],
					'limit'      => 5,
					'offset'     => 1,
					'status'     => array( 'like', 'dislike' ),
					'is_popular' => true,
					'period'     => 'month',
				)
			);

			$spotlight['items'] = isset( $top['items'] ) ? array_slice( (array) $top['items'], 0, 5 ) : array();

			return $spotlight;
		}

		/**
		 * Actionable tips derived from intelligence data.
		 *
		 * @param array $intelligence Site intelligence payload.
		 * @param array $context      Optional preloaded overview data (schedule, categories, commerce).
		 * @return array
		 */
		public function get_intelligence_tips( $intelligence = array(), $context = array() ) {
			$tips = array();

			if ( ! empty( $intelligence['views_tracking_enabled'] ) && $intelligence['engagement_rate'] > 0 ) {
				if ( $intelligence['engagement_rate_growth'] > 10 ) {
					$tips[] = array(
						'type'    => 'positive',
						'message' => sprintf(
							/* translators: %s: engagement rate percentage */
							esc_html__( 'Engagement rate rose to %s%% — your content is converting better.', 'wp-ulike-pro' ),
							number_format_i18n( $intelligence['engagement_rate'], 1 )
						),
					);
				} elseif ( $intelligence['engagement_rate'] < 2 && $intelligence['total_views'] > 50 ) {
					$tips[] = array(
						'type'    => 'warning',
						'message' => esc_html__( 'Engagement rate is low — test button placement or stronger calls-to-action.', 'wp-ulike-pro' ),
					);
				}
			} elseif ( empty( $intelligence['views_tracking_enabled'] ) ) {
				$tips[] = array(
					'type'    => 'info',
					'message' => esc_html__( 'Enable view tracking in Pro settings to measure engagement rate and conversion.', 'wp-ulike-pro' ),
				);
			}

			$peak = ! empty( $context['schedule']['peak_hour'] )
				? $context['schedule']['peak_hour']
				: $this->get_peak_hour_slot();

			if ( $peak && ( $peak['count'] ?? 0 ) > 0 ) {
				$tips[] = array(
					'type'    => 'info',
					'message' => sprintf(
						/* translators: %s: time label e.g. 2 PM */
						esc_html__( 'Peak engagement happens around %s — schedule posts and campaigns then.', 'wp-ulike-pro' ),
						$peak['label']
					),
				);
			}

			$categories = ! empty( $context['categories'] )
				? $context['categories']
				: $this->get_category_performance( 1 );

			if ( ! empty( $categories[0]['name'] ) ) {
				$tips[] = array(
					'type'    => 'positive',
					'message' => sprintf(
						/* translators: 1: category name, 2: engagement count */
						esc_html__( '%1$s is your top category with %2$s engagements this month.', 'wp-ulike-pro' ),
						$categories[0]['name'],
						number_format_i18n( $categories[0]['count'] ?? 0 )
					),
				);
			}

			$commerce = array_key_exists( 'commerce', $context )
				? $context['commerce']
				: $this->get_commerce_highlights();

			if ( ! empty( $commerce['items'][0]['title'] ) ) {
				$tips[] = array(
					'type'    => 'info',
					'message' => sprintf(
						/* translators: 1: item type label, 2: product title */
						esc_html__( 'Top %1$s: %2$s — double down on similar offers.', 'wp-ulike-pro' ),
						strtolower( $commerce['label'] ?? '' ),
						$commerce['items'][0]['title']
					),
				);
			}

			if ( ! empty( $intelligence['sentiment']['dislikes_available'] ) && ! empty( $intelligence['sentiment']['like_ratio'] ) && $intelligence['sentiment']['like_ratio'] < 70 && $intelligence['sentiment']['total'] > 20 ) {
				$tips[] = array(
					'type'    => 'warning',
					'message' => esc_html__( 'Negative sentiment is rising — review content that receives negative reactions.', 'wp-ulike-pro' ),
				);
			}

			if ( empty( $tips ) ) {
				return $this->get_growth_tips();
			}

			return array_slice( $tips, 0, 5 );
		}

		/**
		 * Map stats panel content key to engagement item_type slug.
		 *
		 * @param string $type_key posts|comments|activities|topics.
		 * @return string
		 */
	private function count_engagements_all_tables( $date = 'month' ) {
		$cache_key = sanitize_key(
			'engagements_all_' . ( is_array( $date ) ? wp_json_encode( $date ) : $date )
		);

		return (int) WP_Ulike_Query_Cache::remember_stats(
			$cache_key,
			function () use ( $date ) {
				$total = 0;

				foreach ( $this->tables as $type_key => $table ) {
					if ( ! WP_Ulike_Pro_Stats_Type_Resolver::is_type_available( $type_key ) ) {
						continue;
					}

					$total += $this->stats_pulse_count_for_type_key( $type_key, $date );
				}

				return $total;
			}
		);
	}

		/**
		 * Count distinct registered members across all tables.
		 *
		 * @param mixed $date Period filter.
		 * @return int
		 */
	private function count_unique_engagers_all_tables( $date = 'month' ) {
		$cache_key = sanitize_key(
			'unique_engagers_' . ( is_array( $date ) ? wp_json_encode( $date ) : $date )
		);

		return (int) WP_Ulike_Query_Cache::remember_stats(
			$cache_key,
			function () use ( $date ) {
				$union_parts = array();
				$pulse       = $this->stats_pulse_table_esc();
				$mode        = $this->stats_pulse_read_mode();
				$period      = wp_ulike_get_period_limit_sql( $date );

	foreach ( $this->tables as $type_key => $table ) {
			if ( ! WP_Ulike_Pro_Stats_Type_Resolver::is_type_available( $type_key ) ) {
				continue;
			}

			$item_type = WP_Ulike_Pro_Stats_Type_Resolver::map_stats_type_to_item_type( $type_key );

			// Pulse slice: all engagement kinds (vote + emoji + star) regardless
			// of the type's current template mode. Vote rows are scoped to
			// dual_since in merged mode; emoji/star are never since-filtered.
			if ( 'legacy' !== $this->stats_pulse_read_mode()
				|| WP_Ulike_Pulse_Schema::table_exists() ) {
				$rows_sql      = $this->stats_pulse_all_active_rows_sql( 'v' );
				$scoped_since  = $this->stats_pulse_all_kinds_since_sql( 'v' );
				$union_parts[] = $this->wpdb->prepare(
					"SELECT CAST(v.user_id AS UNSIGNED) AS member_id FROM `{$pulse}` v
					INNER JOIN {$this->wpdb->users} u ON u.ID = CAST(v.user_id AS UNSIGNED)
					WHERE v.item_type = %s AND {$rows_sql}{$scoped_since}
					AND v.user_id > 0 AND v.user_id NOT LIKE 't\\_%%'",
					$item_type
				) . $period;
			}

			// Legacy registered members for vote types on dual/legacy sites.
			if ( ( 'legacy' === $mode || 'merged' === $mode )
				&& class_exists( 'WP_Ulike_Pulse_Registry' ) ) {
				$source = WP_Ulike_Pulse_Registry::legacy_source_for_type( $item_type );
				if ( $source && WP_Ulike_Pulse_Registry::table_exists( $source['table'] ) ) {
					$lt = esc_sql( $source['table'] );
					$union_parts[] = "SELECT CAST(l.user_id AS UNSIGNED) AS member_id FROM `{$lt}` l
						INNER JOIN {$this->wpdb->users} u ON u.ID = CAST(l.user_id AS UNSIGNED)
						WHERE l.status IN ('like','dislike')
						AND l.user_id > 0 AND l.user_id NOT LIKE 't\\_%'" . $period;
				}
			}
		}

			if ( empty( $union_parts ) ) {
				return 0;
			}

			$query = sprintf(
				'SELECT COUNT(DISTINCT member_id) FROM ( %s ) AS engagers',
				implode( ' UNION ', $union_parts )
			);
			$count = absint( $this->wpdb->get_var( $query ) );

			return $count;
			}
		);
	}

	/**
	 * Count distinct registered members for a single table.
	 *
	 * @param string $table Table name.
	 * @param mixed  $date  Period filter.
	 * @return int
	 */
	private function count_unique_members_table( $table, $date = 'week' ) {
			$cache_key = sanitize_key(
				sprintf(
					'unique_members_%s_%s',
					$table,
					is_array( $date ) ? wp_json_encode( $date ) : $date
				)
			);
			$cached    = wp_cache_get( $cache_key, WP_ULIKE_PRO_DOMAIN );

			if ( false !== $cached ) {
				return (int) $cached;
			}

			$type_key = WP_Ulike_Pro_Stats_Type_Resolver::table_to_stats_type( $table );
			if ( ! $type_key || ! WP_Ulike_Pro_Stats_Type_Resolver::is_type_available( $type_key ) ) {
				return 0;
			}

		$pulse         = $this->stats_pulse_table_esc();
		$item_type     = WP_Ulike_Pro_Stats_Type_Resolver::map_stats_type_to_item_type( $type_key );
		$rows_sql      = $this->stats_pulse_all_active_rows_sql( 'v' );
		$mode          = $this->stats_pulse_read_mode();
		$period        = wp_ulike_get_period_limit_sql( $date );
		$since         = $this->stats_pulse_all_kinds_since_sql( 'v' );

		$union_parts = array();

		if ( 'legacy' !== $mode || WP_Ulike_Pulse_Schema::table_exists() ) {
			$union_parts[] = $this->wpdb->prepare(
				"SELECT CAST(v.user_id AS UNSIGNED) AS member_id FROM `{$pulse}` v
				INNER JOIN {$this->wpdb->users} u ON u.ID = CAST(v.user_id AS UNSIGNED)
				WHERE v.item_type = %s AND {$rows_sql}{$since}
				AND v.user_id > 0 AND v.user_id NOT LIKE 't\\_%%'",
				$item_type
			) . $period;
		}

		if ( ( 'legacy' === $mode || 'merged' === $mode )
			&& class_exists( 'WP_Ulike_Pulse_Registry' ) ) {
			$source = WP_Ulike_Pulse_Registry::legacy_source_for_type( $item_type );
			if ( $source && WP_Ulike_Pulse_Registry::table_exists( $source['table'] ) ) {
				$lt = esc_sql( $source['table'] );
				$union_parts[] = "SELECT CAST(l.user_id AS UNSIGNED) AS member_id FROM `{$lt}` l
					INNER JOIN {$this->wpdb->users} u ON u.ID = CAST(l.user_id AS UNSIGNED)
					WHERE l.status IN ('like','dislike')
					AND l.user_id > 0 AND l.user_id NOT LIKE 't\\_%'" . $period;
			}
		}

			if ( empty( $union_parts ) ) {
				return 0;
			}

			$query = sprintf(
				'SELECT COUNT(DISTINCT member_id) FROM ( %s ) AS members',
				implode( ' UNION ', $union_parts )
			);
			$count = absint( $this->wpdb->get_var( $query ) );

			wp_cache_set( $cache_key, $count, WP_ULIKE_PRO_DOMAIN, 300 );
			return $count;
		}

		/**
		 * Count distinct voters (members + guests) across all tables.
		 *
		 * @param mixed $date Period filter.
		 * @return int
		 */
	private function count_unique_voters_all_tables( $date = 'month' ) {
		$cache_key = sanitize_key(
			'unique_voters_' . ( is_array( $date ) ? wp_json_encode( $date ) : $date )
		);

		return (int) WP_Ulike_Query_Cache::remember_stats(
			$cache_key,
			function () use ( $date ) {
				// Build one UNION of per-type sub-selects and let MySQL do the
				// dedup via COUNT(DISTINCT ...), instead of pulling every
				// voter_id into PHP per type and dedup'ing with array_flip --
				// same pattern as count_unique_engagers_all_tables()/
				// count_unique_members_table() below.
				$union_parts = array();
				$pulse       = $this->stats_pulse_table_esc();
				$mode        = $this->stats_pulse_read_mode();
				$period      = wp_ulike_get_period_limit_sql( $date );
				$actor_sql   = $this->stats_pulse_distinct_actor_sql();

				foreach ( $this->tables as $type_key => $table ) {
					if ( ! WP_Ulike_Pro_Stats_Type_Resolver::is_type_available( $type_key ) ) {
						continue;
					}

					$item_type = WP_Ulike_Pro_Stats_Type_Resolver::map_stats_type_to_item_type( $type_key );

					// Pulse slice — all engagement kinds regardless of template mode.
					// Vote rows are scoped to dual_since in merged mode; emoji/star
					// are never since-filtered (no legacy counterpart).
					if ( 'legacy' !== $mode || WP_Ulike_Pulse_Schema::table_exists() ) {
						$rows_sql      = $this->stats_pulse_all_active_rows_sql();
						$scoped        = $this->stats_pulse_all_kinds_since_sql();
						$union_parts[] = $this->wpdb->prepare(
							"SELECT {$actor_sql} AS actor FROM `{$pulse}` WHERE item_type = %s AND {$rows_sql}{$scoped}",
							$item_type
						) . $period;
					}

					// Legacy slice for vote types — pre-cutover voters on dual/legacy sites.
					if ( ( 'legacy' === $mode || 'merged' === $mode )
						&& class_exists( 'WP_Ulike_Pulse_Registry' ) ) {
						$source = WP_Ulike_Pulse_Registry::legacy_source_for_type( $item_type );
						if ( $source && WP_Ulike_Pulse_Registry::table_exists( $source['table'] ) ) {
							$lt          = esc_sql( $source['table'] );
							$legacy_actor = $this->legacy_geo_column_exists( $source['table'], 'fingerprint' )
								? $this->stats_pulse_distinct_actor_sql()
								: "CONVERT(CASE WHEN user_id IS NOT NULL AND CAST(user_id AS CHAR) NOT IN ('', '0') THEN CONCAT('u:', user_id) ELSE NULL END USING utf8mb4)";
							$union_parts[] = "SELECT {$legacy_actor} AS actor FROM `{$lt}`
								WHERE status IN ('like','dislike')" . $period;
						}
					}
				}

				if ( empty( $union_parts ) ) {
					return 0;
				}

				$query = sprintf(
					'SELECT COUNT(DISTINCT actor) FROM ( %s ) AS voters WHERE actor IS NOT NULL',
					implode( ' UNION ', $union_parts )
				);

				return absint( $this->wpdb->get_var( $query ) );
			}
		);
	}

	/**
	 * Positive vs negative sentiment for a period.
	 *
	 * Classic likes/dislikes plus emoji polarity (weight / negative slugs)
	 * and star thresholds (default 4–5 positive, 1–2 negative on a 5-star scale).
	 * Neutral reactions/ratings are tracked but excluded from the ratio.
	 *
	 * @param mixed $date Period filter.
	 * @return array
	 */
	private function get_sentiment_breakdown( $date = 'month' ) {
		$cache_key = sanitize_key(
			'sentiment_v2_' . ( is_array( $date ) ? wp_json_encode( $date ) : $date )
		);

		return WP_Ulike_Query_Cache::remember_stats(
			$cache_key,
			function () use ( $date ) {
				$positive = 0;
				$negative = 0;
				$neutral  = 0;
				$breakdown = array(
					'vote_likes'     => 0,
					'vote_dislikes'  => 0,
					'emoji_positive' => 0,
					'emoji_negative' => 0,
					'emoji_neutral'  => 0,
					'star_positive'  => 0,
					'star_negative'  => 0,
					'star_neutral'   => 0,
				);

				$reader = class_exists( 'WP_Ulike_Pro_Stats_Engagement_Reader' )
					? $this->engagement()
					: null;

				foreach ( $this->tables as $type_key => $table ) {
					if ( ! WP_Ulike_Pro_Stats_Type_Resolver::is_type_available( $type_key ) ) {
						continue;
					}

					$item_type = WP_Ulike_Pro_Stats_Type_Resolver::map_stats_type_to_item_type( $type_key );

					// Classic like/dislike votes — always counted regardless of template mode.
					$vote_likes    = (int) $this->stats_pulse_count_status_for_table_suffix( $table, 'like', $date );
					$vote_dislikes = (int) $this->stats_pulse_count_status_for_table_suffix( $table, 'dislike', $date );

					$breakdown['vote_likes']    += $vote_likes;
					$breakdown['vote_dislikes'] += $vote_dislikes;
					$positive                   += $vote_likes;
					$negative                   += $vote_dislikes;

					if ( ! $reader || ! class_exists( 'WP_Ulike_Pro_Engagement_Registry' ) ) {
						continue;
					}

					foreach ( $reader->count_logs_grouped( $item_type, $date, 'emoji' ) as $slug => $count ) {
						$count    = (int) $count;
						$polarity = WP_Ulike_Pro_Engagement_Registry::get_reaction_polarity( (string) $slug );

						if ( 'negative' === $polarity ) {
							$breakdown['emoji_negative'] += $count;
							$negative                    += $count;
						} elseif ( 'neutral' === $polarity ) {
							$breakdown['emoji_neutral'] += $count;
							$neutral                    += $count;
						} else {
							$breakdown['emoji_positive'] += $count;
							$positive                    += $count;
						}
					}

					$star_max = (int) WP_Ulike_Pro_Engagement_Registry::get_star_config( $item_type )['max'];
					foreach ( $reader->count_logs_grouped( $item_type, $date, 'star' ) as $value => $count ) {
						$count    = (int) $count;
						$polarity = WP_Ulike_Pro_Engagement_Registry::get_star_polarity( (int) $value, $star_max );

						if ( 'negative' === $polarity ) {
							$breakdown['star_negative'] += $count;
							$negative                   += $count;
						} elseif ( 'neutral' === $polarity ) {
							$breakdown['star_neutral'] += $count;
							$neutral                   += $count;
						} else {
							$breakdown['star_positive'] += $count;
							$positive                   += $count;
						}
					}
				}

				$polarized  = $positive + $negative;
				$like_ratio = $polarized > 0 ? round( ( $positive / $polarized ) * 100, 1 ) : 0;

				return array(
					// BC keys used by existing UI / tips (now polarity-aware).
					'likes'      => $positive,
					'dislikes'   => $negative,
					'total'      => $polarized,
					'like_ratio' => $like_ratio,
					// Explicit polarity fields.
					'positive'   => $positive,
					'negative'   => $negative,
					'neutral'    => $neutral,
					'breakdown'  => $breakdown,
					'has_polarity' => $polarized > 0 && $negative > 0,
				);
			}
		);
	}

	/**
	 * Sum button views for enabled content types in a period.
	 *
	 * @param mixed $date Period filter.
	 * @return int
	 */
	private function count_views_for_period( $date = 'month' ) {
			if ( ! $this->button_views || ! $this->is_any_view_tracking_enabled() ) {
				return 0;
			}

			global $wpdb;
			$table = $wpdb->prefix . 'ulike_views';
			$start = gmdate( 'Y-m-d', strtotime( '-30 days' ) );
			$end   = current_time( 'Y-m-d' );

			if ( is_array( $date ) && isset( $date['interval_value'] ) ) {
				$start = gmdate( 'Y-m-d', strtotime( '-' . absint( $date['interval_value'] ) . ' days' ) );
			} elseif ( is_array( $date ) && isset( $date['start'] ) ) {
				$start = $date['start'];
				$end   = $date['end'];
			}

			$cache_key = sanitize_key(
				'views_period_' . md5( $start . '_' . $end )
			);
			$cached = wp_cache_get( $cache_key, WP_ULIKE_PRO_DOMAIN );
			if ( false !== $cached ) {
				return (int) $cached;
			}

			$count = absint(
				$wpdb->get_var(
					$wpdb->prepare(
						"SELECT COALESCE(SUM(view_count), 0) FROM {$table} WHERE view_date >= %s AND view_date <= %s",
						$start,
						$end
					)
				)
			);

			wp_cache_set( $cache_key, $count, WP_ULIKE_PRO_DOMAIN, 10 );

			return $count;
		}

		/**
		 * Whether view tracking is enabled for any content type.
		 *
		 * @return bool
		 */
		private function is_any_view_tracking_enabled() {
			if ( ! $this->button_views ) {
				return false;
			}

			foreach ( array( 'post', 'comment', 'activity', 'topic' ) as $type ) {
				if ( $this->button_views->is_tracking_enabled( $type ) ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Hour slot with the highest engagement in the last 30 days.
		 *
		 * @return array|null
		 */
		private function get_peak_hour_slot() {
			$hours = $this->get_peak_hours( '30 DAY' );
			if ( empty( $hours ) ) {
				return null;
			}

			$peak = null;
			foreach ( $hours as $slot ) {
				if ( ! $peak || ( $slot['count'] ?? 0 ) > ( $peak['count'] ?? 0 ) ) {
					$peak = $slot;
				}
			}

			return ( $peak && ( $peak['count'] ?? 0 ) > 0 ) ? $peak : null;
		}

		/**
		 * Format hour integer as localized time label.
		 *
		 * @param int $hour Hour 0-23.
		 * @return string
		 */
		private function get_primary_post_taxonomy() {
			return 'category';
		}

		/**
		 * Human-readable taxonomy label.
		 *
		 * @param string $taxonomy Taxonomy slug.
		 * @return string
		 */
		private function get_taxonomy_label( $taxonomy ) {
			$object = get_taxonomy( $taxonomy );
			return $object && ! empty( $object->labels->name )
				? $object->labels->name
				: esc_html__( 'Categories', 'wp-ulike-pro' );
		}

		/**
		 * Whether a post type has engagement in the last 30 days.
		 *
		 * @param string $post_type Post type slug.
		 * @return bool
		 */
		private function has_post_type_engagement( $post_type ) {
			return $this->count_post_type_engagement( $post_type, 'month' ) > 0;
		}

		/**
		 * Count engagements for a specific post type.
		 *
		 * @param string $post_type Post type slug.
		 * @param mixed  $date      Period filter.
		 * @return int
		 */
		private function count_post_type_engagement( $post_type, $date = 'month' ) {
			$cache_key = sanitize_key( 'post_type_eng_' . $post_type . '_' . ( is_array( $date ) ? wp_json_encode( $date ) : $date ) );
			$cached    = wp_cache_get( $cache_key, WP_ULIKE_PRO_DOMAIN );

			if ( false !== $cached ) {
				return (int) $cached;
			}

			$period_sql = wp_ulike_get_period_limit_sql( $date );
		$pulse      = $this->stats_pulse_table_esc();
		$mode       = $this->stats_pulse_read_mode();
		$since      = $this->stats_pulse_all_kinds_since_sql( 'p' );

		$union = array();

		if ( 'legacy' !== $mode ) {
			$rows_sql = $this->stats_pulse_all_active_rows_sql( 'p' );
			$union[]  = $this->wpdb->prepare(
				"SELECT 1 FROM `{$pulse}` AS p
				INNER JOIN {$this->wpdb->posts} AS po ON p.item_id = po.ID
				WHERE p.item_type = 'post'
				AND {$rows_sql}{$since}
				AND po.post_type = %s",
				$post_type
			) . $period_sql;
		} elseif ( $this->stats_pulse_table_available() ) {
			// Legacy mode still needs pulse emoji/star (no legacy counterpart).
			$pro_rows = $this->stats_pulse_pro_rows_sql( 'p' );
			$union[]  = $this->wpdb->prepare(
				"SELECT 1 FROM `{$pulse}` AS p
				INNER JOIN {$this->wpdb->posts} AS po ON p.item_id = po.ID
				WHERE p.item_type = 'post'
				AND {$pro_rows}
				AND po.post_type = %s",
				$post_type
			) . $period_sql;
		}

			if ( 'legacy' === $mode || 'merged' === $mode ) {
				$source = class_exists( 'WP_Ulike_Pulse_Registry' )
					? WP_Ulike_Pulse_Registry::legacy_source_for_type( 'post' )
					: null;
				if ( $source && WP_Ulike_Pulse_Registry::table_exists( $source['table'] ) ) {
					$lt   = esc_sql( $source['table'] );
					$col  = esc_sql( $source['column'] );
					$union[] = $this->wpdb->prepare(
						"SELECT 1 FROM `{$lt}` AS l
						INNER JOIN {$this->wpdb->posts} AS po ON l.`{$col}` = po.ID
						WHERE l.status IN ('like','dislike')
						AND po.post_type = %s",
						$post_type
					) . $period_sql;
				}
			}

			$count = 0;
			if ( ! empty( $union ) ) {
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- fragments built from prepared statements.
				$count = (int) $this->wpdb->get_var(
					'SELECT COUNT(*) FROM (' . implode( ' UNION ALL ', $union ) . ') AS combined'
				);
			}

			wp_cache_set( $cache_key, $count, WP_ULIKE_PRO_DOMAIN, 10 );

			return $count;
		}

		/**
		 * Top taxonomy terms by engagement count.
		 *
		 * @param string $taxonomy   Taxonomy slug.
		 * @param array  $post_types Allowed post types.
		 * @param int    $limit      Max results.
		 * @return array
		 */
		private function query_taxonomy_engagement( $taxonomy, $post_types, $limit = 3, $date_filter = null ) {
			if ( empty( $post_types ) || ! taxonomy_exists( $taxonomy ) ) {
				return array();
			}

			if ( null === $date_filter ) {
				$date_filter = array( 'interval' => '30 DAY' );
			}

		$date_sql          = $this->build_activity_date_where( $date_filter );
		$post_placeholders = implode( ', ', array_fill( 0, count( $post_types ), '%s' ) );
		$pulse_table       = $this->stats_pulse_table_esc();
		$mode              = $this->stats_pulse_read_mode();
		$since             = $this->stats_pulse_all_kinds_since_sql( 'p' );

		$union = array();

		if ( 'legacy' !== $mode ) {
			$rows_sql = $this->stats_pulse_all_active_rows_sql( 'p' );
			$union[]  = $this->wpdb->prepare(
				"SELECT t.term_id, t.name
				FROM `{$pulse_table}` AS p
				INNER JOIN {$this->wpdb->posts} AS po ON p.item_id = po.ID
				INNER JOIN {$this->wpdb->term_relationships} AS tr ON po.ID = tr.object_id
				INNER JOIN {$this->wpdb->term_taxonomy} AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
				INNER JOIN {$this->wpdb->terms} AS t ON tt.term_id = t.term_id
				WHERE tt.taxonomy = %s
				AND po.post_type IN ({$post_placeholders})
				AND p.item_type = 'post'
				AND {$rows_sql}{$since}",
				array_merge( array( $taxonomy ), $post_types )
			) . $date_sql;
		} elseif ( $this->stats_pulse_table_available() ) {
			$pro_rows = $this->stats_pulse_pro_rows_sql( 'p' );
			$union[]  = $this->wpdb->prepare(
				"SELECT t.term_id, t.name
				FROM `{$pulse_table}` AS p
				INNER JOIN {$this->wpdb->posts} AS po ON p.item_id = po.ID
				INNER JOIN {$this->wpdb->term_relationships} AS tr ON po.ID = tr.object_id
				INNER JOIN {$this->wpdb->term_taxonomy} AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
				INNER JOIN {$this->wpdb->terms} AS t ON tt.term_id = t.term_id
				WHERE tt.taxonomy = %s
				AND po.post_type IN ({$post_placeholders})
				AND p.item_type = 'post'
				AND {$pro_rows}",
				array_merge( array( $taxonomy ), $post_types )
			) . $date_sql;
		}

			if ( 'legacy' === $mode || 'merged' === $mode ) {
				$source = class_exists( 'WP_Ulike_Pulse_Registry' )
					? WP_Ulike_Pulse_Registry::legacy_source_for_type( 'post' )
					: null;
				if ( $source && WP_Ulike_Pulse_Registry::table_exists( $source['table'] ) ) {
					$lt  = esc_sql( $source['table'] );
					$col = esc_sql( $source['column'] );
					$union[] = $this->wpdb->prepare(
						"SELECT t.term_id, t.name
						FROM `{$lt}` AS l
						INNER JOIN {$this->wpdb->posts} AS po ON l.`{$col}` = po.ID
						INNER JOIN {$this->wpdb->term_relationships} AS tr ON po.ID = tr.object_id
						INNER JOIN {$this->wpdb->term_taxonomy} AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
						INNER JOIN {$this->wpdb->terms} AS t ON tt.term_id = t.term_id
						WHERE tt.taxonomy = %s
						AND po.post_type IN ({$post_placeholders})
						AND l.status IN ('like','dislike')",
						array_merge( array( $taxonomy ), $post_types )
					) . $date_sql;
				}
			}

			$data = array();
			if ( empty( $union ) ) {
				return $data;
			}

			// phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPreparedSQL
			$rows = $this->wpdb->get_results(
				$this->wpdb->prepare(
					"SELECT term_id, name, COUNT(*) AS engagement_count
					FROM ( " . implode( ' UNION ALL ', $union ) . " ) AS combined
					GROUP BY term_id, name
					ORDER BY engagement_count DESC
					LIMIT %d",
					absint( $limit )
				)
			);

			if ( ! empty( $rows ) ) {
				foreach ( $rows as $row ) {
					$data[] = array(
						'id'    => (int) $row->term_id,
						'name'  => $row->name,
						'count' => absint( $row->engagement_count ),
					);
				}
			}

			return $data;
		}

		/**
		 * Get translated role name
		 *
		 * @param string $role
		 * @return string
		 */
}

