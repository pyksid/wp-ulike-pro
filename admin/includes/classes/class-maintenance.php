<?php
/**
 * Pro maintenance actions — Pulse-native.
 *
 * Pro maintains counters, emoji/star rows, views, Pro tables, and filtered Pulse log purges.
 * Legacy table drops remain in WP ULike → Pulse.
 *
 * @package WP_Ulike_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'WP_Ulike_Pro_Maintenance' ) ) {

	final class WP_Ulike_Pro_Maintenance {

		const CONTENT_TYPES = array( 'post', 'comment', 'activity', 'topic' );

		/**
		 * @param string $type Content type or "all".
		 * @return string|false
		 */
		public static function validate_content_type( $type ) {
			if ( ! current_user_can( 'manage_options' ) ) {
				return false;
			}

			if ( ! in_array( $type, self::CONTENT_TYPES, true ) ) {
				return false;
			}

			return $type;
		}

		/**
		 * Rebuild vote + emoji/star counter meta from ulike_pulse for one content type.
		 *
		 * @param string $type Content type or "all".
		 * @return array|false
		 */
		public static function sync_counters( $type ) {
			if ( ! current_user_can( 'manage_options' ) ) {
				return false;
			}

			if ( 'all' === $type ) {
				$total = 0;
				foreach ( self::CONTENT_TYPES as $content_type ) {
					if ( ! self::is_content_type_available( $content_type ) ) {
						continue;
					}
					$result = self::sync_counters( $content_type );
					if ( is_array( $result ) && ! empty( $result['success'] ) ) {
						$total += (int) ( $result['rows_affected'] ?? 0 );
					}
				}

				self::bump_query_cache();

				return self::result(
					true,
					$total,
					sprintf(
						/* translators: %d: number of items updated */
						esc_html__( 'Synced counters for %d items across all content types.', WP_ULIKE_PRO_DOMAIN ),
						$total
					)
				);
			}

			$type = self::validate_content_type( $type );
			if ( ! $type ) {
				return false;
			}

			$votes       = self::sync_vote_counters( $type );
			$engagements = self::sync_engagement_counters( $type );
			$total       = (int) ( $votes['rows'] ?? 0 ) + (int) ( $engagements['rows'] ?? 0 );
			$partial     = ! empty( $votes['partial'] ) || ! empty( $engagements['partial'] );

			self::bump_query_cache();

			$message = sprintf(
				/* translators: 1: number of items, 2: content type */
				esc_html__( 'Synced counters for %1$d %2$s items.', WP_ULIKE_PRO_DOMAIN ),
				$total,
				ucfirst( $type )
			);

			if ( $partial ) {
				$message .= ' ' . esc_html__( 'Operation was partially completed due to time limits. Please run again if needed.', WP_ULIKE_PRO_DOMAIN );
			}

			return self::result( true, $total, $message );
		}

		/**
		 * Remove invalid and duplicate emoji/star Pulse rows for a content type.
		 *
		 * @param string $type Content type.
		 * @return array|false
		 */
		public static function repair_records( $type ) {
			$type = self::validate_content_type( $type );
			if ( ! $type ) {
				return false;
			}

			$orphans = self::remove_orphan_engagement_rows( $type );
			$dupes   = self::remove_duplicate_engagement_rows( $type );
			$total   = (int) ( $orphans['rows'] ?? 0 ) + (int) ( $dupes['rows'] ?? 0 );

			self::bump_query_cache();

			if ( $total <= 0 ) {
				return self::result(
					true,
					0,
					sprintf(
						esc_html__( 'No invalid or duplicate records found for %s.', WP_ULIKE_PRO_DOMAIN ),
						ucfirst( $type )
					)
				);
			}

			return self::result(
				true,
				$total,
				sprintf(
					esc_html__( 'Repaired %d invalid or duplicate records for %s.', WP_ULIKE_PRO_DOMAIN ),
					$total,
					ucfirst( $type )
				)
			);
		}

		/**
		 * @param string $type Content type.
		 * @return array|false
		 */
		public static function delete_views( $type ) {
			if ( ! current_user_can( 'manage_options' ) ) {
				return false;
			}

			return WP_Ulike_Pro_Views::get_instance()->delete_views_by_type( $type );
		}

		/**
		 * Count Pulse rows matching purge filters (preview).
		 *
		 * @param string               $type    Content type.
		 * @param array<string,mixed>  $filters kind + older_than.
		 * @return array|false
		 */
		public static function count_pulse_logs( $type, $filters = array() ) {
			global $wpdb;

			$type = self::validate_content_type( $type );
			if ( ! $type ) {
				return false;
			}

			$parsed = self::parse_pulse_purge_filters( $filters );
			if ( ! $parsed ) {
				return false;
			}

			$item_type = WP_Ulike_Pulse_Registry::normalize_item_type( $type );
			$table     = esc_sql( wp_ulike_pro_pulse_table() );
			$where     = self::build_pulse_purge_where( $item_type, $parsed );
			$count     = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM `{$table}` WHERE {$where['sql']}",
					$where['args']
				)
			);

			return array(
				'success'       => true,
				'rows_affected' => $count,
				'count'         => $count,
				'message'       => sprintf(
					/* translators: %d: matching row count */
					esc_html__( '%d matching Pulse log row(s).', WP_ULIKE_PRO_DOMAIN ),
					$count
				),
			);
		}

		/**
		 * Permanently delete Pulse rows matching filters, then rebuild counters.
		 *
		 * @param string               $type    Content type.
		 * @param array<string,mixed>  $filters kind + older_than.
		 * @return array|false
		 */
		public static function purge_pulse_logs( $type, $filters = array() ) {
			global $wpdb;

			$type = self::validate_content_type( $type );
			if ( ! $type ) {
				return false;
			}

			// Refuse unless storage has fully cut over to Pulse. In legacy/dual
			// mode the pulse table is NOT a redundant copy: every vote cast after
			// dual_since lives only here (legacy tables are frozen at the cutover),
			// so deleting these rows is unrecoverable. Likewise refuse while a
			// migration is in flight -- it is actively writing into this table.
			if ( class_exists( 'WP_Ulike_Pulse_Config' ) ) {
				if ( WP_Ulike_Pulse_Config::MODE_PULSE !== WP_Ulike_Pulse_Config::mode() ) {
					return self::result(
						false,
						0,
						esc_html__( 'Purging Pulse logs is only available after the storage upgrade has fully completed. While old and new storage are both in use, these rows are the only copy of recent votes. Finish the upgrade in WP ULike → Storage Upgrade first.', WP_ULIKE_PRO_DOMAIN )
					);
				}

				if ( method_exists( 'WP_Ulike_Pulse_Config', 'migration_running' ) && WP_Ulike_Pulse_Config::migration_running() ) {
					return self::result(
						false,
						0,
						esc_html__( 'A storage migration is currently running. Wait for it to finish before purging Pulse logs.', WP_ULIKE_PRO_DOMAIN )
					);
				}
			}

			$parsed = self::parse_pulse_purge_filters( $filters );
			if ( ! $parsed ) {
				return false;
			}

			$start_time      = time();
			$item_type       = WP_Ulike_Pulse_Registry::normalize_item_type( $type );
			$table           = esc_sql( wp_ulike_pro_pulse_table() );
			$where           = self::build_pulse_purge_where( $item_type, $parsed );
			$affected_items  = array();
			$total_deleted   = 0;
			$counter_partial = false;

			foreach ( (array) $wpdb->get_col(
				$wpdb->prepare(
					"SELECT DISTINCT `item_id` FROM `{$table}` WHERE {$where['sql']}",
					$where['args']
				)
			) as $item_id ) {
				$item_id = absint( $item_id );
				if ( $item_id ) {
					$affected_items[] = $item_id;
				}
			}

			$match_count = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM `{$table}` WHERE {$where['sql']}",
					$where['args']
				)
			);

			if ( $match_count > 0 ) {
				$batch_size = 1000;
				while ( $total_deleted < $match_count && self::can_continue_operation( $start_time ) ) {
					$result = $wpdb->query(
						$wpdb->prepare(
							"DELETE FROM `{$table}` WHERE {$where['sql']} LIMIT %d",
							array_merge( $where['args'], array( $batch_size ) )
						)
					);

					if ( false === $result || 0 === $result ) {
						break;
					}

					$total_deleted += (int) $result;
				}
			}

			if ( $total_deleted > 0 && ! empty( $affected_items ) ) {
				$kind = $parsed['kind'];

				if ( in_array( $kind, array( 'vote', 'all' ), true ) ) {
					$counter_partial = self::sync_affected_item_counters(
						array( $type => $affected_items ),
						$start_time
					) || $counter_partial;
				}

				if (
					in_array( $kind, array( 'engagement', 'all' ), true )
					&& class_exists( 'WP_Ulike_Pro_Engagement_Counter' )
				) {
					foreach ( $affected_items as $item_id ) {
						if ( ! self::can_continue_operation( $start_time ) ) {
							$counter_partial = true;
							break;
						}
						WP_Ulike_Pro_Engagement_Counter::rebuild_item_counters( $item_id, $type );
					}
				}

				// A raw DELETE is not a write path, so the incrementally-maintained
				// all-time statistics meta never self-corrects (Query_Cache::bump()
				// documents that it deliberately leaves those rows alone). Rebuild
				// them, or site-wide totals keep reporting pre-purge numbers forever.
				self::flush_stats_cache();
			}

			$age_label = 0 === $parsed['older_than']
				? esc_html__( 'all time', WP_ULIKE_PRO_DOMAIN )
				: sprintf(
					/* translators: %d: number of days */
					esc_html__( 'older than %d days', WP_ULIKE_PRO_DOMAIN ),
					$parsed['older_than']
				);

			$message = sprintf(
				/* translators: 1: deleted rows, 2: content type, 3: kind label, 4: age label */
				esc_html__( 'Permanently removed %1$d Pulse log row(s) for %2$s (%3$s, %4$s).', WP_ULIKE_PRO_DOMAIN ),
				$total_deleted,
				ucfirst( $type ),
				$parsed['kind_label'],
				$age_label
			);

			if ( ( ! self::can_continue_operation( $start_time ) || $counter_partial ) && $total_deleted > 0 ) {
				$message .= ' ' . esc_html__( 'Operation was partially completed due to time limits. Run Sync Counters from Tools → Maintenance if counts need updating.', WP_ULIKE_PRO_DOMAIN );
			} elseif ( 0 === $total_deleted ) {
				$message = sprintf(
					/* translators: %s: content type */
					esc_html__( 'No matching Pulse log rows found for %s.', WP_ULIKE_PRO_DOMAIN ),
					ucfirst( $type )
				);
			}

			return self::result( true, $total_deleted, $message );
		}

		/**
		 * @param array<string,mixed> $filters Raw filters.
		 * @return array{kind:string,older_than:int,kind_label:string}|false
		 */
		private static function parse_pulse_purge_filters( $filters ) {
			if ( ! is_array( $filters ) ) {
				$filters = array();
			}

			$kind = isset( $filters['kind'] ) ? sanitize_key( $filters['kind'] ) : 'vote';
			if ( ! in_array( $kind, array( 'vote', 'engagement', 'all' ), true ) ) {
				return false;
			}

			$older_than = isset( $filters['older_than'] ) ? absint( $filters['older_than'] ) : 90;
			if ( $older_than > 3650 ) {
				$older_than = 3650;
			}

			$labels = array(
				'vote'        => esc_html__( 'votes', WP_ULIKE_PRO_DOMAIN ),
				'engagement'  => esc_html__( 'emoji & star', WP_ULIKE_PRO_DOMAIN ),
				'all'         => esc_html__( 'all kinds', WP_ULIKE_PRO_DOMAIN ),
			);

			return array(
				'kind'        => $kind,
				'older_than'  => $older_than,
				'kind_label'  => $labels[ $kind ],
			);
		}

		/**
		 * @param string                              $item_type Canonical pulse item type.
		 * @param array{kind:string,older_than:int}   $parsed    Parsed filters.
		 * @return array{sql:string,args:array<int,mixed>}
		 */
		private static function build_pulse_purge_where( $item_type, $parsed ) {
			$sql  = '`item_type` = %s';
			$args = array( $item_type );

			if ( 'vote' === $parsed['kind'] ) {
				$sql   .= ' AND `engagement_kind` = %s';
				$args[] = WP_Ulike_Pulse_Registry::KIND_VOTE;
			} elseif ( 'engagement' === $parsed['kind'] ) {
				$sql .= ' AND ' . wp_ulike_pro_pulse_pro_kinds_sql();
			}

			if ( $parsed['older_than'] > 0 ) {
				$sql   .= ' AND `date_time` < DATE_SUB( NOW(), INTERVAL %d DAY )';
				$args[] = $parsed['older_than'];
			}

			return array(
				'sql'  => $sql,
				'args' => $args,
			);
		}

		/**
		 * @param string $group_name Meta group (user, statistics, …).
		 * @return array|false
		 */
		public static function delete_meta_group( $group_name ) {
			global $wpdb;

			if ( ! current_user_can( 'manage_options' ) ) {
				return false;
			}

			$allowed_groups = array( 'user', 'statistics' );
			if ( ! in_array( $group_name, $allowed_groups, true ) ) {
				return false;
			}

			$table_name = $wpdb->prefix . 'ulike_meta';
			$data       = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT * FROM `' . esc_sql( $table_name ) . '` WHERE `meta_group` = %s',
					$group_name
				)
			);

			$count = 0;
			foreach ( (array) $data as $row ) {
				wp_ulike_delete_meta_data( $group_name, (int) $row->item_id, $row->meta_key );
				++$count;
			}

			if ( 'statistics' === $group_name && class_exists( 'WP_Ulike_Query_Cache' ) ) {
				WP_Ulike_Query_Cache::flush_stats();
			}

			$label = 'user' === $group_name
				? esc_html__( 'User Status', WP_ULIKE_PRO_DOMAIN )
				: esc_html__( 'Statistics', WP_ULIKE_PRO_DOMAIN );

			return self::result(
				true,
				$count,
				sprintf(
					esc_html__( 'Successfully deleted %1$d cached entries for %2$s.', WP_ULIKE_PRO_DOMAIN ),
					$count,
					$label
				)
			);
		}

		/**
		 * Delete empty plugin post meta rows.
		 *
		 * @param string $group_name Action key (optimize).
		 * @return array|false
		 */
		public static function optimize_post_meta( $group_name ) {
			global $wpdb;

			if ( ! current_user_can( 'manage_options' ) ) {
				return false;
			}

			if ( 'optimize' !== $group_name ) {
				return false;
			}

			$postmeta_table = esc_sql( $wpdb->postmeta );
			$like_pattern   = $wpdb->esc_like( 'wp_ulike_pro' ) . '%';

			$count = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM `" . $postmeta_table . "` WHERE `meta_key` LIKE %s AND `meta_value` = ''",
					$like_pattern
				)
			);

			if ( false === $wpdb->query(
				$wpdb->prepare(
					"DELETE FROM `" . $postmeta_table . "` WHERE `meta_key` LIKE %s AND `meta_value` = ''",
					$like_pattern
				)
			) ) {
				return false;
			}

			return self::result(
				true,
				$count,
				sprintf(
					esc_html__( 'Successfully deleted %d empty post meta rows.', WP_ULIKE_PRO_DOMAIN ),
					$count
				)
			);
		}

		/**
		 * Create or delete plugin default pages.
		 *
		 * @param string $action create|delete.
		 * @return array|false
		 */
		public static function manage_default_pages( $action ) {
			if ( ! current_user_can( 'manage_options' ) ) {
				return false;
			}

			if ( 'create' === $action ) {
				if ( ! class_exists( 'WP_Ulike_Pro_Activator' ) ) {
					require_once WP_ULIKE_PRO_DIR . 'public/class-activator.php';
				}

				$result = WP_Ulike_Pro_Core_Pages::install();
				$count  = is_numeric( $result ) ? (int) $result : 0;

				return self::result(
					true,
					$count,
					sprintf(
						esc_html__( 'Successfully created %d default pages.', WP_ULIKE_PRO_DOMAIN ),
						$count
					)
				);
			}

			$pages = get_posts(
				array(
					'post_type'      => 'page',
					'posts_per_page' => -1,
					'meta_query'     => array(
						array(
							'key'     => '_wp_ulike_pro_core',
							'compare' => 'EXISTS',
						),
					),
				)
			);

			$count = 0;
			if ( ! empty( $pages ) ) {
				$count = count( $pages );
				foreach ( $pages as $page ) {
					wp_delete_post( $page->ID, true );
				}
			}

			return self::result(
				true,
				$count,
				sprintf(
					esc_html__( 'Successfully deleted %d default pages.', WP_ULIKE_PRO_DOMAIN ),
					$count
				)
			);
		}

		/**
		 * @param string $type Unused.
		 * @return array|false
		 */
		public static function clear_all_cache( $type = '' ) {
			if ( ! current_user_can( 'manage_options' ) ) {
				return false;
			}

			if ( class_exists( 'WP_Ulike_Query_Cache' ) ) {
				WP_Ulike_Query_Cache::flush();
			}

			if ( function_exists( 'wp_cache_flush_group' ) ) {
				wp_cache_flush_group( WP_ULIKE_PRO_DOMAIN );
				wp_cache_flush_group( 'ulike_session_id' );
			}

			$plugin_list = '';
			if ( class_exists( 'wp_ulike_purge_cache' ) ) {
				( new wp_ulike_purge_cache() )->purgeAll();
				$detected = array();
				if ( class_exists( 'WP_Rocket' ) ) {
					$detected[] = 'WP Rocket';
				}
				if ( class_exists( 'W3_TotalCache' ) ) {
					$detected[] = 'W3 Total Cache';
				}
				if ( defined( 'LSCWP_V' ) ) {
					$detected[] = 'LiteSpeed Cache';
				}
				if ( class_exists( 'WP_Super_Cache' ) ) {
					$detected[] = 'WP Super Cache';
				}
				if ( ! empty( $detected ) ) {
					$plugin_list = ' (' . implode( ', ', $detected ) . ')';
				}
			}

			return self::result(
				true,
				0,
				esc_html__( 'Successfully cleared all plugin caches.', WP_ULIKE_PRO_DOMAIN ) . $plugin_list
			);
		}

		/**
		 * @param string $type Unused.
		 * @return array|false
		 */
		public static function clear_transients( $type = '' ) {
			global $wpdb;

			if ( ! current_user_can( 'manage_options' ) ) {
				return false;
			}

			$options_table = esc_sql( $wpdb->options );
			$patterns      = array(
				'_transient_wp_ulike%',
				'_transient_timeout_wp_ulike%',
				'_transient_ulp_rate_limit_%',
				'_transient_timeout_ulp_rate_limit_%',
				'_transient_wp-ulike-%',
				'_transient_timeout_wp-ulike-%',
			);

			$count = 0;
			foreach ( $patterns as $pattern ) {
				$count += (int) $wpdb->get_var(
					$wpdb->prepare(
						'SELECT COUNT(*) FROM `' . $options_table . '` WHERE option_name LIKE %s',
						$pattern
					)
				);
				$wpdb->query(
					$wpdb->prepare(
						'DELETE FROM `' . $options_table . '` WHERE option_name LIKE %s',
						$pattern
					)
				);
			}

			delete_option( 'public_server_ip' );

			return self::result(
				true,
				$count,
				sprintf(
					esc_html__( 'Successfully cleared %d transient entries.', WP_ULIKE_PRO_DOMAIN ),
					$count
				)
			);
		}

		/**
		 * @param string $type Unused.
		 * @return array|false
		 */
		public static function cleanup_sessions( $type = '' ) {
			global $wpdb;

			if ( ! current_user_can( 'manage_options' ) || ! class_exists( 'WP_Ulike_Pro_Session_Handler' ) ) {
				return false;
			}

			$table_name  = $wpdb->prefix . 'ulike_sessions';
			$count       = (int) $wpdb->get_var(
				$wpdb->prepare(
					'SELECT COUNT(*) FROM `%1$s` WHERE session_expiry < %2$d',
					esc_sql( $table_name ),
					time()
				)
			);
			$handler = new WP_Ulike_Pro_Session_Handler();
			$handler->cleanup_sessions();

			return self::result(
				true,
				$count,
				sprintf(
					esc_html__( 'Successfully cleaned up %d expired sessions.', WP_ULIKE_PRO_DOMAIN ),
					$count
				)
			);
		}


		/**
		 * Remove all vote and engagement records for user ID(s) — GDPR.
		 *
		 * @param string|array $user_ids Comma-separated string or array of user IDs.
		 * @return array|false
		 */
		public static function remove_user_votes( $user_ids ) {
			global $wpdb;

			if ( ! current_user_can( 'manage_options' ) ) {
				return false;
			}

			if ( is_string( $user_ids ) ) {
				$user_ids = array_map( 'trim', explode( ',', $user_ids ) );
			}

			if ( ! is_array( $user_ids ) || empty( $user_ids ) ) {
				return false;
			}

			$user_ids = array_values( array_filter( array_map( 'absint', $user_ids ) ) );
			if ( empty( $user_ids ) ) {
				return false;
			}

			$start_time         = time();
			$total_deleted      = 0;
			$tables_processed   = array();
			$affected_items     = array();
			$engagement_affected = array();
			$counter_partial    = false;

			if ( self::can_continue_operation( $start_time ) ) {
				$pulse_table     = wp_ulike_pro_pulse_table();
				$user_id_strings = array_map( 'strval', $user_ids );
				$placeholders    = implode( ',', array_fill( 0, count( $user_id_strings ), '%s' ) );
				$pro_kinds_sql   = ' AND ' . wp_ulike_pro_pulse_pro_kinds_sql();

				foreach ( (array) $wpdb->get_results(
					$wpdb->prepare(
						"SELECT DISTINCT `item_id`, `item_type` FROM `" . esc_sql( $pulse_table ) . "` WHERE `user_id` IN ($placeholders){$pro_kinds_sql}",
						$user_id_strings
					)
				) as $row ) {
					$item_type = sanitize_key( $row->item_type );
					$item_id   = absint( $row->item_id );
					if ( $item_id && $item_type ) {
						$engagement_affected[ $item_type ][ $item_id ] = $item_id;
					}
				}

				foreach ( (array) $wpdb->get_results(
					$wpdb->prepare(
						"SELECT DISTINCT `item_id`, `item_type` FROM `" . esc_sql( $pulse_table ) . "`
						WHERE `user_id` IN ($placeholders) AND `engagement_kind` = %s",
						array_merge( $user_id_strings, array( WP_Ulike_Pulse_Registry::KIND_VOTE ) )
					)
				) as $row ) {
					$item_type = sanitize_key( $row->item_type );
					$item_id   = absint( $row->item_id );
					if ( $item_id && $item_type ) {
						$affected_items[ $item_type ]   = $affected_items[ $item_type ] ?? array();
						$affected_items[ $item_type ][] = $item_id;
						$affected_items[ $item_type ]   = array_unique( array_map( 'absint', $affected_items[ $item_type ] ) );
					}
				}

				$pulse_count = (int) $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(*) FROM `" . esc_sql( $pulse_table ) . "` WHERE `user_id` IN ($placeholders)",
						$user_id_strings
					)
				);

				if ( $pulse_count > 0 ) {
					$pulse_deleted = 0;
					$batch_size    = 1000;
					while ( $pulse_deleted < $pulse_count && self::can_continue_operation( $start_time ) ) {
						$result = $wpdb->query(
							$wpdb->prepare(
								"DELETE FROM `" . esc_sql( $pulse_table ) . "` WHERE `user_id` IN ($placeholders) LIMIT %d",
								array_merge( $user_id_strings, array( $batch_size ) )
							)
						);
						if ( false === $result || 0 === $result ) {
							break;
						}
						$pulse_deleted += (int) $result;
					}
					$total_deleted += $pulse_deleted;
					$tables_processed['ulike_pulse'] = $pulse_deleted;
				}
			}

			$meta_table = $wpdb->prefix . 'ulike_meta';
			if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $meta_table ) ) === $meta_table && self::can_continue_operation( $start_time ) ) {
				$placeholders = implode( ',', array_fill( 0, count( $user_ids ), '%d' ) );
				$meta_entries = $wpdb->get_results(
					$wpdb->prepare(
						'SELECT * FROM `' . esc_sql( $meta_table ) . '` WHERE `meta_group` = %s AND `item_id` IN (' . $placeholders . ')',
						array_merge( array( 'user' ), $user_ids )
					)
				);
				$meta_count = 0;
				foreach ( (array) $meta_entries as $meta_entry ) {
					wp_ulike_delete_meta_data( 'user', (int) $meta_entry->item_id, $meta_entry->meta_key );
					++$meta_count;
				}
				if ( $meta_count > 0 ) {
					$tables_processed['ulike_meta'] = $meta_count;
				}
			}

			if ( ! empty( $affected_items ) ) {
				$counter_partial = self::sync_affected_item_counters( $affected_items, $start_time );
			}

			if ( ! empty( $engagement_affected ) && class_exists( 'WP_Ulike_Pro_Engagement_Counter' ) ) {
				foreach ( $engagement_affected as $item_type => $item_ids ) {
					foreach ( $item_ids as $item_id ) {
						if ( ! self::can_continue_operation( $start_time ) ) {
							$counter_partial = true;
							break 2;
						}
						WP_Ulike_Pro_Engagement_Counter::rebuild_item_counters( $item_id, $item_type );
					}
				}
			}

			if ( $total_deleted > 0 ) {
				self::bump_query_cache();
			}

			$user_names = array();
			foreach ( $user_ids as $user_id ) {
				$user = get_user_by( 'id', $user_id );
				$user_names[] = $user ? $user->display_name . ' (ID: ' . $user_id . ')' : 'ID: ' . $user_id;
			}

			$message = sprintf(
				esc_html__( 'Successfully removed %d record(s) for %d user(s): %s.', WP_ULIKE_PRO_DOMAIN ),
				$total_deleted,
				count( $user_ids ),
				implode( ', ', $user_names )
			);

			if ( ( ! self::can_continue_operation( $start_time ) || $counter_partial ) && $total_deleted > 0 ) {
				$message .= ' ' . esc_html__( 'Operation was partially completed due to time limits. Run Sync Counters from Tools → Maintenance if counts need updating.', WP_ULIKE_PRO_DOMAIN );
			}

			return array(
				'success'         => true,
				'rows_affected'   => $total_deleted,
				'message'         => $message,
				'users_processed' => count( $user_ids ),
				'tables'          => $tables_processed,
			);
		}

		public static function repair_tables( $type = '' ) {
			return self::run_table_operation( 'REPAIR TABLE', esc_html__( 'repaired', WP_ULIKE_PRO_DOMAIN ) );
		}

		/**
		 * @param string $type Unused.
		 * @return array|false
		 */
		public static function analyze_tables( $type = '' ) {
			return self::run_table_operation( 'ANALYZE TABLE', esc_html__( 'analyzed', WP_ULIKE_PRO_DOMAIN ) );
		}

		/**
		 * @param string $type Unused.
		 * @return array|false
		 */
		public static function sync_indexes( $type = '' ) {
			global $wpdb;

			if ( ! current_user_can( 'manage_options' ) ) {
				return false;
			}

			unset( $type );

			$max_index_length = 191;
			$synced_count     = 0;
			$indexes_added    = 0;
			$errors           = array();

			$expected_indexes = array(
				'ulike_meta'     => array(
					'item_id'                     => 'KEY `item_id` (`item_id`)',
					'meta_key'                    => 'KEY `meta_key` (`meta_key`(' . $max_index_length . '))',
					'item_id_meta_group'          => 'KEY `item_id_meta_group` (`item_id`, `meta_group`)',
					'meta_group_meta_key_item_id' => 'KEY `meta_group_meta_key_item_id` (`meta_group`, `meta_key`, `item_id`)',
				),
				'ulike_sessions' => array(
					'session_key'    => 'UNIQUE KEY `session_key` (`session_key`)',
					'session_expiry' => 'KEY `session_expiry` (`session_expiry`)',
				),
				'ulike_views'    => array(
					'unique_view'   => 'UNIQUE KEY `unique_view` (`item_id`, `type`, `view_date`)',
					'idx_item_type' => 'KEY `idx_item_type` (`item_id`, `type`)',
					'idx_view_date' => 'KEY `idx_view_date` (`view_date`)',
					'idx_type_date' => 'KEY `idx_type_date` (`type`, `view_date`)',
					'idx_item_date' => 'KEY `idx_item_date` (`item_id`, `view_date`)',
				),
			);

			$tables = array( 'ulike_meta', 'ulike_sessions', 'ulike_views' );

			foreach ( $tables as $table ) {
				$table_name = $wpdb->prefix . $table;
				$exists     = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) === $table_name;

				if ( ! $exists || ! isset( $expected_indexes[ $table ] ) ) {
					continue;
				}

				$existing_columns = array();
				$wpdb->last_error = '';
				$column_results   = $wpdb->get_results( sprintf( 'SHOW COLUMNS FROM `%s`', esc_sql( $table_name ) ), ARRAY_A );

				if ( ! empty( $wpdb->last_error ) ) {
					$errors[] = sprintf(
						esc_html__( 'Failed to get columns for table %s: %s', WP_ULIKE_PRO_DOMAIN ),
						$table,
						$wpdb->last_error
					);
					continue;
				}

				foreach ( (array) $column_results as $column ) {
					$field_name = isset( $column['Field'] ) ? $column['Field'] : '';
					if ( $field_name ) {
						$existing_columns[ $field_name ] = true;
					}
				}

				$existing_indexes = array();
				$wpdb->last_error = '';
				$index_results    = $wpdb->get_results( sprintf( 'SHOW INDEX FROM `%s`', esc_sql( $table_name ) ), ARRAY_A );

				if ( ! empty( $wpdb->last_error ) ) {
					$errors[] = sprintf(
						esc_html__( 'Failed to get indexes for table %s: %s', WP_ULIKE_PRO_DOMAIN ),
						$table,
						$wpdb->last_error
					);
					continue;
				}

				foreach ( (array) $index_results as $index ) {
					$key_name = isset( $index['Key_name'] ) ? $index['Key_name'] : '';
					if ( $key_name && 'PRIMARY' !== $key_name ) {
						$existing_indexes[ $key_name ] = true;
					}
				}

				$table_synced = false;

				foreach ( $expected_indexes[ $table ] as $index_name => $index_definition ) {
					if ( isset( $existing_indexes[ $index_name ] ) ) {
						continue;
					}

					preg_match( '/\(([^)]+)\)/', $index_definition, $matches );
					if ( ! empty( $matches[1] ) ) {
						$index_columns = array_map( 'trim', explode( ',', $matches[1] ) );
						$columns_exist = true;

						foreach ( $index_columns as $col ) {
							$col_name = trim( $col, '`' );
							$col_name = preg_replace( '/\s*\([^)]*\)\s*$/', '', $col_name );
							$col_name = trim( $col_name );

							if ( ! $col_name || ! isset( $existing_columns[ $col_name ] ) ) {
								$columns_exist = false;
								break;
							}
						}

						if ( ! $columns_exist ) {
							continue;
						}
					}

					$query            = sprintf( 'ALTER TABLE `%s` ADD %s', esc_sql( $table_name ), $index_definition );
					$wpdb->last_error = '';
					$result           = $wpdb->query( $query );
					$error_msg        = $wpdb->last_error;

					if ( false !== $result && empty( $error_msg ) ) {
						$indexes_added++;
						$table_synced                      = true;
						$existing_indexes[ $index_name ] = true;
						continue;
					}

					if ( $error_msg ) {
						$error_lower  = strtolower( $error_msg );
						$is_duplicate = (
							strpos( $error_lower, 'duplicate key name' ) !== false
							|| strpos( $error_lower, 'already exists' ) !== false
							|| strpos( $error_lower, 'duplicate entry' ) !== false
							|| strpos( $error_lower, 'duplicate' ) !== false
						);

						if ( ! $is_duplicate ) {
							$errors[] = sprintf(
								esc_html__( 'Failed to add index %1$s to table %2$s: %3$s', WP_ULIKE_PRO_DOMAIN ),
								$index_name,
								$table,
								$error_msg
							);
						}
					}
				}

				if ( $table_synced ) {
					++$synced_count;
				}
			}

			if ( $indexes_added > 0 ) {
				$message = sprintf(
					esc_html__( 'Successfully synced indexes for %1$d tables. Added %2$d new indexes.', WP_ULIKE_PRO_DOMAIN ),
					$synced_count,
					$indexes_added
				);
			} else {
				$message = esc_html__( 'All indexes are already in sync. No changes were needed.', WP_ULIKE_PRO_DOMAIN );
			}

			if ( ! empty( $errors ) ) {
				$message = esc_html__( 'Index sync completed with errors:', WP_ULIKE_PRO_DOMAIN ) . ' ' . implode( '; ', $errors );
				if ( $indexes_added > 0 ) {
					$message .= ' ' . sprintf(
						esc_html__( 'However, %d indexes were successfully added.', WP_ULIKE_PRO_DOMAIN ),
						$indexes_added
					);
				}
			}

			return array(
				'success'       => empty( $errors ),
				'rows_affected' => $indexes_added,
				'message'       => $message,
				'errors'        => $errors,
			);
		}

		/**
		 * @param string|array $args Meta box args.
		 * @return array|false
		 */
		public static function post_metabox_truncate( $args ) {
			global $wpdb;

			if ( ! current_user_can( 'manage_options' ) ) {
				return false;
			}

			if ( is_string( $args ) ) {
				$args = json_decode( $args, true );
			}

			if ( ! is_array( $args ) || empty( $args['method'] ) || empty( $args['id'] ) ) {
				return false;
			}

			$method  = sanitize_text_field( $args['method'] );
			$post_id = absint( $args['id'] );

			if ( ! in_array( $method, array( 'meta', 'logs' ), true ) || $post_id <= 0 ) {
				return false;
			}

			if ( 'meta' === $method ) {
				$deleted = $wpdb->delete(
					$wpdb->prefix . 'ulike_meta',
					array(
						'item_id'    => $post_id,
						'meta_group' => 'post',
					)
				);
			} else {
				$deleted = $wpdb->query(
					$wpdb->prepare(
						'DELETE FROM `' . esc_sql( WP_Ulike_Pulse_Schema::table() ) . '` WHERE item_id = %d AND item_type = %s AND engagement_kind = %s',
						$post_id,
						'post',
						WP_Ulike_Pulse_Registry::KIND_VOTE
					)
				);

			}

			if ( false === $deleted ) {
				return false;
			}

			self::bump_query_cache();

			$method_label = 'meta' === $method
				? esc_html__( 'Meta Counter Data', WP_ULIKE_PRO_DOMAIN )
				: esc_html__( 'Likes Logs', WP_ULIKE_PRO_DOMAIN );

			return self::result(
				true,
				(int) $deleted,
				sprintf(
					esc_html__( 'Successfully removed %1$d %2$s record(s) for post ID %3$d.', WP_ULIKE_PRO_DOMAIN ),
					(int) $deleted,
					$method_label,
					$post_id
				)
			);
		}

		/**
		 * Rebuild vote counter meta for one item from Pulse.
		 *
		 * @param int    $item_id Item ID.
		 * @param string $type    Content type.
		 * @return void
		 */
		private static function sync_item_vote_counters( $item_id, $type ) {
			global $wpdb;

			$item_id = absint( $item_id );
			$type    = sanitize_key( $type );
			if ( ! $item_id || ! $type ) {
				return;
			}

			$is_distinct     = wp_ulike_setting_repo::isDistinct( $type );
			$meta_key_prefix = $is_distinct ? 'count_distinct_' : 'count_total_';

			$like_count    = (int) WP_Ulike_Pro_Pulse_Reader::count_item_votes( $item_id, $type, 'like', $is_distinct );
			$dislike_count = (int) WP_Ulike_Pro_Pulse_Reader::count_item_votes( $item_id, $type, 'dislike', $is_distinct );
			$likers        = (array) WP_Ulike_Pro_Pulse_Reader::rebuild_likers_list( $item_id, $type, 99999 );

			wp_ulike_update_meta_data( $item_id, $type, $meta_key_prefix . 'like', $like_count );
			wp_ulike_update_meta_data( $item_id, $type, $meta_key_prefix . 'dislike', $dislike_count );
			wp_ulike_update_meta_data( $item_id, $type, 'likers_list', array_map( 'absint', $likers ) );

			if ( in_array( $type, array( 'post', 'comment' ), true ) ) {
				update_metadata( $type, $item_id, 'like_amount', $like_count );
				update_metadata( $type, $item_id, 'dislike_amount', $dislike_count );
				update_metadata( $type, $item_id, 'net_votes', $like_count - $dislike_count );
			}
		}

		/**
		 * Rebuild vote counters for items affected by bulk deletion.
		 *
		 * @param array<string,array<int>> $affected_items Map of type => item IDs.
		 * @param int                      $start_time     Operation start timestamp.
		 * @return bool True when stopped early due to time limits.
		 */
		private static function sync_affected_item_counters( array $affected_items, $start_time ) {
			foreach ( $affected_items as $type => $item_ids ) {
				foreach ( (array) $item_ids as $item_id ) {
					if ( ! self::can_continue_operation( $start_time ) ) {
						return true;
					}
					self::sync_item_vote_counters( $item_id, $type );
				}
			}

			return false;
		}

		/**
		 * @param string $type Content type.
		 * @return array{rows:int,partial:bool}
		 */
		private static function sync_vote_counters( $type ) {
			global $wpdb;

			$meta_table = $wpdb->prefix . 'ulike_meta';
			$items         = array_map(
				'absint',
				(array) WP_Ulike_Pro_Pulse_Reader::distinct_voted_item_ids( $type )
			);

			if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $meta_table ) ) === $meta_table ) {
				$meta_items = $wpdb->get_col(
					$wpdb->prepare(
						'SELECT DISTINCT `item_id` FROM `' . esc_sql( $meta_table ) . '` WHERE `meta_group` = %s AND ( `meta_key` LIKE %s OR `meta_key` = %s )',
						$type,
						'%' . $wpdb->esc_like( 'count_' ) . '%',
						'likers_list'
					)
				);
				$items = array_unique( array_merge( $items, array_map( 'absint', (array) $meta_items ) ) );
			}

			$items = array_values( array_filter( $items ) );
			if ( empty( $items ) ) {
				return array(
					'rows'    => 0,
					'partial' => false,
				);
			}

			$start_time = time();
			$synced          = 0;
			$partial         = false;

			foreach ( $items as $item_id ) {
				if ( ! self::can_continue_operation( $start_time ) ) {
					$partial = true;
					break;
				}

				self::sync_item_vote_counters( absint( $item_id ), $type );
				++$synced;
			}

			return array(
				'rows'    => $synced,
				'partial' => $partial,
			);
		}

		/**
		 * @param string $type Content type.
		 * @return array{rows:int,partial:bool}
		 */
		private static function sync_engagement_counters( $type ) {
			global $wpdb;

			if ( ! class_exists( 'WP_Ulike_Pro_Engagement_Counter' ) ) {
				return array(
					'rows'    => 0,
					'partial' => false,
				);
			}

			if ( 'none' === wp_ulike_pro_get_engagement_mode_for_type( $type ) ) {
				return array(
					'rows'    => 0,
					'partial' => false,
				);
			}

			$table       = esc_sql( wp_ulike_pro_pulse_table() );
			$meta_table  = $wpdb->prefix . 'ulike_meta';
			$meta_prefix = WP_Ulike_Pro_Engagement_Counter::META_PREFIX;
			$kinds_sql   = ' AND ' . wp_ulike_pro_pulse_pro_kinds_sql();

			$items = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT DISTINCT `item_id` FROM `{$table}` WHERE `item_type` = %s{$kinds_sql}",
					$type
				)
			);

			if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $meta_table ) ) === $meta_table ) {
				$meta_items = $wpdb->get_col(
					$wpdb->prepare(
						'SELECT DISTINCT `item_id` FROM `' . esc_sql( $meta_table ) . '` WHERE `meta_group` = %s AND `meta_key` LIKE %s',
						$type,
						$meta_prefix . '%'
					)
				);
				$items = array_unique( array_merge( (array) $items, (array) $meta_items ) );
			}

			$items = array_values( array_filter( array_map( 'absint', (array) $items ) ) );
			if ( empty( $items ) ) {
				return array(
					'rows'    => 0,
					'partial' => false,
				);
			}

			$start_time = time();
			$synced     = 0;
			$partial    = false;

			foreach ( $items as $item_id ) {
				if ( ! self::can_continue_operation( $start_time ) ) {
					$partial = true;
					break;
				}

				WP_Ulike_Pro_Engagement_Counter::rebuild_item_counters( $item_id, $type );
				++$synced;
			}

			return array(
				'rows'    => $synced,
				'partial' => $partial,
			);
		}

		/**
		 * @param string $type Content type.
		 * @return array{rows:int}
		 */
		private static function remove_orphan_engagement_rows( $type ) {
			global $wpdb;

			$join = self::orphan_join_for_type( $type );
			if ( ! $join ) {
				return array( 'rows' => 0 );
			}

			$table     = esc_sql( wp_ulike_pro_pulse_table() );
			$kinds_sql = ' AND ' . wp_ulike_pro_pulse_pro_kinds_sql( 'e.engagement_kind' );

			$count = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM `{$table}` e
					LEFT JOIN `{$join['related_table']}` dt ON e.`item_id` = dt.`{$join['related_column']}`
					WHERE e.`item_type` = %s AND dt.`{$join['related_column']}` IS NULL{$kinds_sql}",
					$type
				)
			);

			if ( $count <= 0 ) {
				return array( 'rows' => 0 );
			}

			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM `{$table}`
					WHERE `id` IN (
						SELECT `id` FROM (
							SELECT e.`id` FROM `{$table}` e
							LEFT JOIN `{$join['related_table']}` dt ON e.`item_id` = dt.`{$join['related_column']}`
							WHERE e.`item_type` = %s AND dt.`{$join['related_column']}` IS NULL{$kinds_sql}
						) AS temp
					)",
					$type
				)
			);

			return array( 'rows' => $count );
		}

		/**
		 * @param string $type Content type.
		 * @return array{rows:int}
		 */
		private static function remove_duplicate_engagement_rows( $type ) {
			global $wpdb;

			$table     = esc_sql( wp_ulike_pro_pulse_table() );
			$kinds_sql = ' AND ' . wp_ulike_pro_pulse_pro_kinds_sql();

			$total_count = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM `{$table}` WHERE `item_type` = %s{$kinds_sql}",
					$type
				)
			);

			$unique_count = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM (
						SELECT MAX(`id`) AS max_id FROM `{$table}`
						WHERE `item_type` = %s{$kinds_sql}
						GROUP BY `user_id`, `item_id`, `engagement_kind`
					) AS unique_rows",
					$type
				)
			);

			$duplicate_count = max( 0, $total_count - $unique_count );
			if ( $duplicate_count <= 0 ) {
				return array( 'rows' => 0 );
			}

			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM `{$table}`
					WHERE `item_type` = %s{$kinds_sql}
					AND `id` IN (
						SELECT `id` FROM (
							SELECT t1.`id` FROM `{$table}` t1
							INNER JOIN `{$table}` t2
								ON t1.`user_id` = t2.`user_id`
								AND t1.`item_id` = t2.`item_id`
								AND t1.`engagement_kind` = t2.`engagement_kind`
								AND t1.`item_type` = t2.`item_type`
								AND t1.`id` < t2.`id`
							WHERE t1.`item_type` = %s{$kinds_sql}
						) AS temp
					)",
					$type,
					$type
				)
			);

			return array( 'rows' => $duplicate_count );
		}

		/**
		 * @param string $type Content type.
		 * @return array{related_table:string,related_column:string}|false
		 */
		private static function orphan_join_for_type( $type ) {
			$info = wp_ulike_get_table_info( $type );
			if ( empty( $info['related_table_prefix'] ) || empty( $info['related_column'] ) ) {
				return false;
			}

			return array(
				'related_table'  => esc_sql( $info['related_table_prefix'] ),
				'related_column' => esc_sql( $info['related_column'] ),
			);
		}

		/**
		 * @param string $sql_prefix REPAIR TABLE|ANALYZE TABLE.
		 * @param string $verb       Message verb.
		 * @return array|false
		 */
		private static function run_table_operation( $sql_prefix, $verb ) {
			global $wpdb;

			if ( ! current_user_can( 'manage_options' ) ) {
				return false;
			}

			$count = 0;
			foreach ( array( 'ulike_meta', 'ulike_sessions', 'ulike_views' ) as $table ) {
				$table_name = $wpdb->prefix . $table;
				if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) !== $table_name ) {
					continue;
				}
				$wpdb->query( $sql_prefix . ' `' . esc_sql( $table_name ) . '`' );
				++$count;
			}

			return self::result(
				true,
				$count,
				sprintf(
					/* translators: 1: number of tables, 2: operation verb */
					esc_html__( 'Successfully %2$s %1$d database tables.', WP_ULIKE_PRO_DOMAIN ),
					$count,
					$verb
				)
			);
		}

		/**
		 * @param string $type Content type.
		 * @return bool
		 */
		private static function is_content_type_available( $type ) {
			if ( 'activity' === $type ) {
				return defined( 'BP_VERSION' );
			}
			if ( 'topic' === $type ) {
				return function_exists( 'is_bbpress' );
			}
			return true;
		}

		/**
		 * @return void
		 */
		private static function bump_query_cache() {
			if ( class_exists( 'WP_Ulike_Query_Cache' ) ) {
				WP_Ulike_Query_Cache::bump();
			}
		}

		/**
		 * Invalidate cached queries AND rebuild persisted statistics meta.
		 *
		 * Required after bulk row deletion: bump() only versions the query cache
		 * and leaves the incrementally-maintained all-time counters untouched,
		 * because those are normally corrected by the write path.
		 *
		 * @return void
		 */
		private static function flush_stats_cache() {
			if ( ! class_exists( 'WP_Ulike_Query_Cache' ) ) {
				return;
			}

			if ( method_exists( 'WP_Ulike_Query_Cache', 'flush_stats' ) ) {
				WP_Ulike_Query_Cache::flush_stats();
				return;
			}

			WP_Ulike_Query_Cache::bump();
		}

		/**
		 * @param bool   $success Success flag.
		 * @param int    $rows    Rows affected.
		 * @param string $message Message.
		 * @return array<string,mixed>
		 */

		/**
		 * @param int $start_time Unix timestamp when the operation started.
		 * @param int $max_execution_time Max seconds before stopping.
		 * @return bool
		 */
		private static function can_continue_operation( $start_time, $max_execution_time = 25 ) {
			if ( ( time() - $start_time ) >= $max_execution_time ) {
				return false;
			}

			$memory_limit   = self::get_memory_limit();
			$current_memory = memory_get_usage( true );
			if ( $memory_limit > 0 && ( $current_memory / $memory_limit ) > 0.9 ) {
				return false;
			}

			return true;
		}

		/**
		 * @return int Memory limit in bytes, 0 if unlimited.
		 */
		private static function get_memory_limit() {
			$limit = ini_get( 'memory_limit' );
			if ( '-1' === $limit ) {
				return 0;
			}

			$limit = trim( $limit );
			$last  = strtolower( $limit[ strlen( $limit ) - 1 ] );
			$value = (int) $limit;

			switch ( $last ) {
				case 'g':
					$value *= 1024;
				case 'm':
					$value *= 1024;
				case 'k':
					$value *= 1024;
			}

			return $value;
		}

		private static function result( $success, $rows, $message ) {
			return array(
				'success'       => (bool) $success,
				'rows_affected' => (int) $rows,
				'message'       => $message,
			);
		}
	}
}

