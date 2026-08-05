<?php
/**
 * Pulse-only SQL helpers for Pro statistics (ulike_pulse + ulike_views).
 *
 * @package WP_Ulike_Pro
 */
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

trait WP_Ulike_Pro_Stats_Trait_Pulse {

	/**
	 * Escaped ulike_pulse table name.
	 *
	 * @return string
	 */
	private function stats_pulse_table_esc() {
		return esc_sql( wp_ulike_pro_pulse_table() );
	}

	/**
	 * SQL fragment for active vote rows on pulse.
	 *
	 * @param string $alias Optional table alias.
	 * @return string
	 */
	private function stats_pulse_vote_rows_sql( $alias = '' ) {
		$prefix = $alias ? esc_sql( $alias ) . '.' : '';
		$kind   = esc_sql( WP_Ulike_Pulse_Registry::KIND_VOTE );

		return sprintf(
			"%sengagement_kind = '%s' AND %sstatus = 'active'",
			$prefix,
			$kind,
			$prefix
		);
	}

	/**
	 * SQL fragment for active emoji/star rows on pulse.
	 *
	 * @param string $alias Optional table alias.
	 * @return string
	 */
	private function stats_pulse_pro_rows_sql( $alias = '' ) {
		$prefix   = $alias ? esc_sql( $alias ) . '.' : '';
		$kind_col = $alias ? $alias . '.engagement_kind' : 'engagement_kind';

		return sprintf(
			'%sstatus = \'active\' AND %s',
			$prefix,
			wp_ulike_pro_pulse_pro_kinds_sql( $kind_col )
		);
	}

	/**
	 * SQL fragment for ALL active rows on pulse (vote + emoji + star).
	 *
	 * Used by aggregate paths (totals, charts, geo, unique users) that must
	 * reflect every interaction kind regardless of the type's current template.
	 *
	 * @param string $alias Optional table alias.
	 * @return string
	 */
	private function stats_pulse_all_active_rows_sql( $alias = '' ) {
		$prefix = $alias ? esc_sql( $alias ) . '.' : '';

		return sprintf( "%sstatus = 'active'", $prefix );
	}

	/**
	 * Distinct actor expression for geo/device counts.
	 *
	 * Registered users key by user_id; guests (user_id 0/empty) key by fingerprint
	 * so they are not collapsed into a single "0" bucket.
	 *
	 * @param string $alias Optional table alias.
	 * @return string SQL expression (may evaluate to NULL).
	 */
	private function stats_pulse_distinct_actor_sql( $alias = '' ) {
		$prefix = $alias ? esc_sql( $alias ) . '.' : '';

		// CONVERT(... USING utf8mb4) so legacy and pulse arms of a UNION share one
		// collation. CONCAT() inherits the column's collation and old legacy tables
		// often differ from the newer pulse table -> "Illegal mix of collations"
		// kills the whole query and the metric silently reads 0. An explicit
		// COLLATE utf8mb4_* must NOT be used: it errors on utf8mb3 legacy tables.
		return "CONVERT(CASE
			WHEN {$prefix}user_id IS NOT NULL AND CAST({$prefix}user_id AS CHAR) NOT IN ('', '0') THEN CONCAT('u:', {$prefix}user_id)
			WHEN {$prefix}fingerprint IS NOT NULL AND CAST({$prefix}fingerprint AS CHAR) NOT IN ('', '0') THEN CONCAT('f:', {$prefix}fingerprint)
			ELSE NULL
		END USING utf8mb4)";
	}

	/**
	 * Map pulse vote rows to legacy status keys in SQL (like/unlike/dislike/undislike).
	 *
	 * @param string $alias Optional table alias.
	 * @return string
	 */
	private function stats_pulse_legacy_status_key_sql( $alias = '' ) {
		$prefix = $alias ? esc_sql( $alias ) . '.' : '';

		return "CASE
			WHEN {$prefix}engagement_key = 'dislike' AND {$prefix}status = 'active' THEN 'dislike'
			WHEN {$prefix}engagement_key = 'dislike' AND {$prefix}status = 'removed' THEN 'undislike'
			WHEN {$prefix}engagement_key = 'like' AND {$prefix}status = 'removed' THEN 'unlike'
			ELSE 'like'
		END";
	}

	/**
	 * Row filter for a stats content type (votes vs emoji/star).
	 *
	 * @param string $type_key posts|comments|activities|topics.
	 * @param string $alias    Optional table alias.
	 * @return string
	 */
	private function stats_pulse_rows_sql_for_type_key( $type_key, $alias = '' ) {
		if ( WP_Ulike_Pro_Stats_Type_Resolver::stats_type_uses_engagement_table( $type_key ) ) {
			return $this->stats_pulse_pro_rows_sql( $alias );
		}

		return $this->stats_pulse_vote_rows_sql( $alias );
	}

	/**
	 * Map legacy vote status filters to pulse WHERE SQL.
	 *
	 * @param string[] $legacy_statuses like|dislike|unlike|undislike.
	 * @return string Empty string when no filter.
	 */
	private function stats_pulse_legacy_status_sql( $legacy_statuses ) {
		if ( empty( $legacy_statuses ) || ! class_exists( 'WP_Ulike_Pulse_Vote_Map' ) ) {
			return '';
		}

		$filter = WP_Ulike_Pulse_Vote_Map::pulse_filter_from_legacy_statuses( $legacy_statuses );

		if ( empty( $filter['keys'] ) ) {
			return '';
		}

		$key_in = "'" . implode( "','", array_map( 'esc_sql', $filter['keys'] ) ) . "'";

		if ( $filter['active_only'] ) {
			return " AND engagement_key IN ({$key_in}) AND status = 'active'";
		}

		if ( $filter['include_removed'] ) {
			return " AND engagement_key IN ({$key_in}) AND status = 'removed'";
		}

		$parts = array();
		foreach ( WP_Ulike_Pulse_Vote_Map::normalize_status_filter( $legacy_statuses ) as $status ) {
			$row     = WP_Ulike_Pulse_Vote_Map::legacy_to_row( $status );
			$parts[] = $this->wpdb->prepare(
				'(engagement_key = %s AND status = %s)',
				$row['engagement_key'],
				$row['status']
			);
		}

		return empty( $parts ) ? '' : ' AND (' . implode( ' OR ', $parts ) . ')';
	}

	/**
	 * Vote log count for a stats type key (mode-aware: dual/pulse).
	 *
	 * Emoji/star types read the engagement rows directly (no legacy counterpart).
	 * Vote types delegate to the free plugin's WP_Ulike_Pulse_Query so dual-mode
	 * sites include legacy rows that have not been migrated yet.
	 *
	 * @param string $type_key posts|comments|activities|topics.
	 * @param mixed  $period   Period filter.
	 * @return int
	 */
	private function stats_pulse_count_for_type_key( $type_key, $period = 'all' ) {
		if ( ! WP_Ulike_Pro_Stats_Type_Resolver::is_type_available( $type_key ) ) {
			return 0;
		}

		$item_type = WP_Ulike_Pro_Stats_Type_Resolver::map_stats_type_to_item_type( $type_key );

		// Classic votes (vote-only, all statuses, mode-aware) + active emoji
		// + active star. Vote-only slice prevents double-counting emoji/star
		// that count_logs_for_type() already includes. Mirrors
		// count_logs_for_stats_type().
		$total  = (int) WP_Ulike_Pulse_Query::count_vote_logs_for_type( $item_type, $period );
		$total += (int) $this->engagement()->count_logs( $item_type, $period, 'emoji' );
		$total += (int) $this->engagement()->count_logs( $item_type, $period, 'star' );

		return $total;
	}

	/**
	 * Vote log count by legacy table suffix (mode-aware: dual/pulse).
	 *
	 * @param string $table_suffix Legacy suffix.
	 * @param mixed  $period       Period filter.
	 * @return int
	 */
	private function stats_pulse_count_for_table_suffix( $table_suffix, $period = 'all' ) {
		$type_key = WP_Ulike_Pro_Stats_Type_Resolver::table_to_stats_type( $table_suffix );
		if ( $type_key ) {
			return $this->stats_pulse_count_for_type_key( $type_key, $period );
		}

		return (int) WP_Ulike_Pulse_Query::count_logs_for_table( $table_suffix, $period );
	}

	/**
	 * Vote status count by legacy table suffix (mode-aware: dual/pulse).
	 *
	 * @param string $table_suffix   ulike|ulike_comments|…
	 * @param string $legacy_status  like|dislike|unlike|undislike.
	 * @param mixed  $period         Period filter.
	 * @return int
	 */
	private function stats_pulse_count_status_for_table_suffix( $table_suffix, $legacy_status, $period = 'all' ) {
		return (int) WP_Ulike_Pulse_Query::count_status_for_table( $table_suffix, $legacy_status, $period );
	}

	/**
	 * Distinct voters for a legacy table suffix (mode-aware: dual/pulse).
	 *
	 * @param string $table_suffix ulike|ulike_comments|…
	 * @param mixed  $period       Period filter.
	 * @return int
	 */
	private function stats_pulse_count_unique_voters_for_table_suffix( $table_suffix, $period = 'all' ) {
		$type_key = WP_Ulike_Pro_Stats_Type_Resolver::table_to_stats_type( $table_suffix );

		if ( $type_key ) {
			// Count distinct users across ALL engagement kinds (vote + emoji +
			// star) regardless of the type's current template mode.
			return (int) WP_Ulike_Pulse_Query::count_unique_interactors_for_type(
				WP_Ulike_Pro_Stats_Type_Resolver::map_stats_type_to_item_type( $type_key ),
				$period
			);
		}

		return (int) WP_Ulike_Pulse_Query::count_unique_voters_for_table( $table_suffix, $period );
	}

	/**
	 * SELECT date_time UNION part for trends/activity charts (mode-aware).
	 *
	 * Vote types in legacy/merged mode union legacy date_time rows so dual sites
	 * include pre-cutover activity. Emoji/star types stay pulse-only.
	 *
	 * @param string $type_key  posts|comments|activities|topics.
	 * @param string $extra_sql Additional AND conditions (already sanitized, date_time-bound).
	 * @return string
	 */
	private function stats_pulse_date_time_select_sql( $type_key, $extra_sql = '' ) {
		if ( ! WP_Ulike_Pro_Stats_Type_Resolver::is_type_available( $type_key ) ) {
			return '';
		}

	$pulse     = $this->stats_pulse_table_esc();
	$item_type = WP_Ulike_Pro_Stats_Type_Resolver::map_stats_type_to_item_type( $type_key );
	// Total-activity charts include every interaction kind (vote + emoji + star)
	// regardless of the type's current template, so display automation and
	// historical engagement data are reflected in trends/activity.
	$rows_sql  = $this->stats_pulse_all_active_rows_sql();

		// Emoji/star types have no legacy counterpart.
		if ( WP_Ulike_Pro_Stats_Type_Resolver::stats_type_uses_engagement_table( $type_key ) ) {
			return $this->wpdb->prepare(
				"SELECT date_time FROM `{$pulse}` WHERE item_type = %s AND {$rows_sql} {$extra_sql}",
				$item_type
			);
		}

		$mode = $this->stats_pulse_read_mode();

		if ( 'pulse' === $mode ) {
			return $this->wpdb->prepare(
				"SELECT date_time FROM `{$pulse}` WHERE item_type = %s AND {$rows_sql} {$extra_sql}",
				$item_type
			);
		}

		$legacy_sql = $this->stats_pulse_legacy_date_time_select_sql( $item_type, $extra_sql );
		$pro_rows   = $this->stats_pulse_pro_rows_sql();
		$pro_part   = $this->stats_pulse_table_available()
			? $this->wpdb->prepare(
				"SELECT date_time FROM `{$pulse}` WHERE item_type = %s AND {$pro_rows} {$extra_sql}",
				$item_type
			)
			: '';

		if ( 'legacy' === $mode ) {
			// Legacy votes + pulse emoji/star (no legacy counterpart for Pro kinds).
			if ( '' === $legacy_sql ) {
				return $pro_part;
			}
			return '' === $pro_part ? $legacy_sql : $legacy_sql . ' UNION ALL ' . $pro_part;
		}

		// Merged: pulse votes since cutover + all emoji/star + legacy votes.
		$since_sql  = $this->stats_pulse_all_kinds_since_sql();
		$pulse_part = $this->wpdb->prepare(
			"SELECT date_time FROM `{$pulse}` WHERE item_type = %s AND {$rows_sql}{$since_sql} {$extra_sql}",
			$item_type
		);

		if ( '' === $legacy_sql ) {
			return $pulse_part;
		}

		return $pulse_part . ' UNION ALL ' . $legacy_sql;
	}

	/**
	 * Whether the shared pulse table physically exists.
	 *
	 * read_mode() reports 'legacy' both when the site has not cut over AND when
	 * the pulse table is missing entirely, so every legacy-mode branch that adds
	 * a pulse UNION arm must check this first — an unresolvable arm fails the
	 * whole UNION and blanks the report, including its legacy data.
	 *
	 * @return bool
	 */
	private function stats_pulse_table_available() {
		return class_exists( 'WP_Ulike_Pulse_Schema' ) && WP_Ulike_Pulse_Schema::table_exists();
	}

	/**
	 * Current pulse read mode (legacy|merged|pulse). Defaults to pulse when the
	 * free router is unavailable so Pro degrades to its previous behavior.
	 *
	 * @return string
	 */
	private function stats_pulse_read_mode() {
		if ( ! class_exists( 'WP_Ulike_Pulse_Query' ) || ! method_exists( 'WP_Ulike_Pulse_Query', 'read_mode' ) ) {
			return 'pulse';
		}
		return WP_Ulike_Pulse_Query::read_mode();
	}

	/**
	 * SQL fragment: AND date_time >= dual_since (merged mode only).
	 *
	 * @return string
	 */
	private function stats_pulse_dual_since_sql() {
		if ( 'merged' !== $this->stats_pulse_read_mode() || ! class_exists( 'WP_Ulike_Pulse_Config' ) ) {
			return '';
		}
		$since = WP_Ulike_Pulse_Config::dual_since();
		return $since ? $this->wpdb->prepare( ' AND date_time >= %s', $since ) : '';
	}

	/**
	 * Dual-since fragment scoped to vote rows only for all-kind queries.
	 *
	 * In merged mode, legacy tables hold pre-cutover votes, so pulse vote rows
	 * are scoped to dual_since to avoid double-counting. Emoji/star have no
	 * legacy counterpart, so they are never since-filtered.
	 *
	 * @param string $alias Optional table alias for date_time/engagement_kind.
	 * @return string
	 */
	private function stats_pulse_all_kinds_since_sql( $alias = '' ) {
		if ( 'merged' !== $this->stats_pulse_read_mode() || ! class_exists( 'WP_Ulike_Pulse_Config' ) ) {
			return '';
		}
		$since = WP_Ulike_Pulse_Config::dual_since();
		if ( ! $since ) {
			return '';
		}

		$dt   = $alias ? esc_sql( $alias ) . '.date_time' : 'date_time';
		$kind = $alias ? esc_sql( $alias ) . '.engagement_kind' : 'engagement_kind';

		return $this->wpdb->prepare(
			" AND ( {$kind} IN ('emoji','star') OR ( {$kind} = %s AND {$dt} >= %s ) )",
			WP_Ulike_Pulse_Registry::KIND_VOTE,
			$since
		);
	}

	/**
	 * Legacy date_time SELECT for a vote item type (active like/dislike rows).
	 *
	 * @param string $item_type  Canonical item type.
	 * @param string $extra_sql  Extra AND conditions referencing date_time.
	 * @return string Empty when no legacy table exists.
	 */
	private function stats_pulse_legacy_date_time_select_sql( $item_type, $extra_sql = '' ) {
		if ( ! class_exists( 'WP_Ulike_Pulse_Registry' ) ) {
			return '';
		}

		$source = WP_Ulike_Pulse_Registry::legacy_source_for_type( $item_type );
		if ( ! $source || ! WP_Ulike_Pulse_Registry::table_exists( $source['table'] ) ) {
			return '';
		}

		$table = esc_sql( $source['table'] );
		return "SELECT date_time FROM `{$table}` WHERE status IN ('like','dislike') {$extra_sql}";
	}

	/**
	 * Actor SELECT fragments for one stats type (country geo UNION parts).
	 *
	 * Used by stats-trait-geo to UNION across content types before DISTINCT,
	 * so multi-type voters are not inflated.
	 *
	 * @param string   $type_key         posts|comments|…
	 * @param string   $date_condition   SQL date fragment.
	 * @param string[] $selected_status  Legacy status filter (classic votes).
	 * @param array    $filters          {engagement_keys?: string[], values?: int[]} emoji/star.
	 * @return string[]
	 */
	private function stats_pulse_country_actor_parts_for_type( $type_key, $date_condition, $selected_status = array(), $filters = array() ) {
		$pulse     = $this->stats_pulse_table_esc();
		$item_type = esc_sql( WP_Ulike_Pro_Stats_Type_Resolver::map_stats_type_to_item_type( $type_key ) );
		$mode      = $this->stats_pulse_read_mode();
		$actor_sql = $this->stats_pulse_distinct_actor_sql();
		$parts     = array();

		// Reaction/rating filters are engagement-scoped — ignore vote status.
		if ( ! empty( $filters['engagement_keys'] ) || ! empty( $filters['values'] ) ) {
			$selected_status = array();
		}

		// Dimension key for breakdown columns (total | legacy status | reaction | rating).
		if ( ! empty( $filters['engagement_keys'] ) ) {
			$map_key_sql = 'engagement_key';
		} elseif ( ! empty( $filters['values'] ) ) {
			$map_key_sql = 'CAST(value AS UNSIGNED)';
		} elseif ( ! empty( $selected_status ) ) {
			$map_key_sql = $this->stats_pulse_legacy_status_key_sql();
		} else {
			$map_key_sql = "'total'";
		}

		$filter_sql = $this->stats_pulse_engagement_filter_sql( $filters );

		// Pulse slice (pulse/merged): all kinds. Do not force status='active' when
		// a vote-status filter is present — unlike/undislike live on removed rows.
		if ( 'pulse' === $mode || 'merged' === $mode ) {
			$rows_sql   = empty( $selected_status ) ? $this->stats_pulse_all_active_rows_sql() : '1=1';
			$since_sql  = $this->stats_pulse_all_kinds_since_sql();
			$status_sql = $this->stats_pulse_legacy_status_sql( $selected_status );

			$parts[] = "
				SELECT CONVERT(country_code USING utf8mb4) AS country_code, {$actor_sql} AS actor, CONVERT({$map_key_sql} USING utf8mb4) AS map_key
				FROM `{$pulse}`
				WHERE {$date_condition}
				AND item_type = '{$item_type}'
				AND {$rows_sql}
				{$since_sql}
				{$filter_sql}
				{$status_sql}
				AND country_code IS NOT NULL
				AND country_code != ''
			";
		}

		// Legacy vote geo (legacy/merged). Skip for reaction/rating filters.
		if ( ( 'legacy' === $mode || 'merged' === $mode )
			&& empty( $filters['engagement_keys'] )
			&& empty( $filters['values'] ) ) {
			$legacy_select = $this->stats_pulse_legacy_country_actor_select(
				WP_Ulike_Pro_Stats_Type_Resolver::map_stats_type_to_item_type( $type_key ),
				$date_condition,
				$selected_status
			);
			if ( $legacy_select ) {
				$parts[] = $legacy_select;
			}
		}

		// Legacy mode: pulse emoji/star only (no legacy counterpart, no double-count).
		if ( 'legacy' === $mode && $this->stats_pulse_table_available() ) {
			$rows_sql = $this->stats_pulse_pro_rows_sql();
			if ( ! empty( $filters['engagement_keys'] ) ) {
				$legacy_map_key = 'engagement_key';
			} elseif ( ! empty( $filters['values'] ) ) {
				$legacy_map_key = 'CAST(value AS UNSIGNED)';
			} else {
				$legacy_map_key = "'total'";
			}

			$parts[] = "
				SELECT CONVERT(country_code USING utf8mb4) AS country_code, {$actor_sql} AS actor, CONVERT({$legacy_map_key} USING utf8mb4) AS map_key
				FROM `{$pulse}`
				WHERE {$date_condition}
				AND item_type = '{$item_type}'
				AND {$rows_sql}
				{$filter_sql}
				AND country_code IS NOT NULL
				AND country_code != ''
			";
		}

		return $parts;
	}

	/**
	 * Collapse country actor UNION parts into [country => [key => count]].
	 *
	 * @param string[] $parts SELECT fragments with country_code, actor, map_key.
	 * @return array<string,array<string,int>>
	 */
	private function stats_pulse_country_counts_from_parts( array $parts ) {
		if ( empty( $parts ) ) {
			return array();
		}

		$query = '
			SELECT country_code, map_key, COUNT(DISTINCT actor) AS count
			FROM ( ' . implode( ' UNION ', $parts ) . ' ) AS geo_actors
			WHERE actor IS NOT NULL AND map_key IS NOT NULL AND map_key != \'\'
			GROUP BY country_code, map_key
		';

		$results = $this->wpdb->get_results( $query, ARRAY_A );
		$counts  = array();

		foreach ( (array) $results as $row ) {
			$country_code = $row['country_code'];
			$key          = (string) $row['map_key'];
			if ( '' === $key ) {
				continue;
			}
			if ( ! isset( $counts[ $country_code ] ) ) {
				$counts[ $country_code ] = array();
			}
			$counts[ $country_code ][ $key ] = (int) $row['count'];
		}

		return $counts;
	}

	/**
	 * Country counts from pulse for one stats type.
	 *
	 * Builds a UNION of actor identities (user_id or fingerprint) so merged
	 * pulse+legacy rows and guest voters are not double-/under-counted.
	 *
	 * @param string   $type_key         posts|comments|…
	 * @param string   $date_condition   SQL date fragment.
	 * @param string[] $selected_status  Legacy status filter (classic votes).
	 * @param array    $filters          {engagement_keys?: string[], values?: int[]} emoji/star.
	 * @return array<string,array<string,int>>
	 */
	private function stats_pulse_country_counts_for_type( $type_key, $date_condition, $selected_status = array(), $filters = array() ) {
		return $this->stats_pulse_country_counts_from_parts(
			$this->stats_pulse_country_actor_parts_for_type( $type_key, $date_condition, $selected_status, $filters )
		);
	}

	/**
	 * Legacy country actor SELECT (country_code, actor, map_key) for UNION.
	 *
	 * @param string $item_type       Canonical item type.
	 * @param string $date_condition  SQL date fragment (date_time-bound).
	 * @param array  $selected_status Legacy status filter; empty = total only.
	 * @return string Empty when unavailable.
	 */
	private function stats_pulse_legacy_country_actor_select( $item_type, $date_condition, $selected_status = array() ) {
		if ( ! class_exists( 'WP_Ulike_Pulse_Registry' ) ) {
			return '';
		}

		$source = WP_Ulike_Pulse_Registry::legacy_source_for_type( $item_type );
		if ( ! $source || ! WP_Ulike_Pulse_Registry::table_exists( $source['table'] ) ) {
			return '';
		}

		if ( ! $this->legacy_geo_column_exists( $source['table'], 'country_code' ) ) {
			return '';
		}

		$table     = esc_sql( $source['table'] );
		$statuses  = $selected_status ? array_values( (array) $selected_status ) : array( 'like', 'dislike' );
		$status_in = "'" . implode( "','", array_map( 'esc_sql', $statuses ) ) . "'";
		$map_key   = empty( $selected_status ) ? "'total'" : 'status';

		// Prefer fingerprint for guests when the Pro upgrade added the column.
		if ( $this->legacy_geo_column_exists( $source['table'], 'fingerprint' ) ) {
			$actor_sql = $this->stats_pulse_distinct_actor_sql();
		} else {
			// CONVERT: keep this arm's collation aligned with the pulse arm (see
			// stats_pulse_distinct_actor_sql()).
			$actor_sql = "CONVERT(CASE
				WHEN user_id IS NOT NULL AND CAST(user_id AS CHAR) NOT IN ('', '0') THEN CONCAT('u:', user_id)
				ELSE NULL
			END USING utf8mb4)";
		}

		return "
			SELECT CONVERT(country_code USING utf8mb4) AS country_code, {$actor_sql} AS actor, CONVERT({$map_key} USING utf8mb4) AS map_key
			FROM `{$table}`
			WHERE {$date_condition}
			AND status IN ({$status_in})
			AND country_code IS NOT NULL
			AND country_code != ''
		";
	}

	/**
	 * AND clauses for engagement_key (emoji) / value (star) filters on pulse.
	 *
	 * @param array $filters {engagement_keys?: string[], values?: int[]}.
	 * @return string
	 */
	private function stats_pulse_engagement_filter_sql( $filters = array() ) {
		$sql    = '';
		$keys   = ! empty( $filters['engagement_keys'] )
			? array_values( array_filter( array_map( 'strval', (array) $filters['engagement_keys'] ) ) )
			: array();
		$values = ! empty( $filters['values'] )
			? array_values( array_filter( array_map( 'absint', (array) $filters['values'] ) ) )
			: array();

		if ( $keys && $values ) {
			// Both filters: match either emoji keys or star values.
			$k_ph = implode( ',', array_fill( 0, count( $keys ), '%s' ) );
			$v_ph = implode( ',', array_fill( 0, count( $values ), '%d' ) );
			$sql .= $this->wpdb->prepare(
				" AND ( ( engagement_kind = 'emoji' AND engagement_key IN ({$k_ph}) ) OR ( engagement_kind = 'star' AND value IN ({$v_ph}) ) )",
				...array_merge( $keys, $values )
			);
		} elseif ( $keys ) {
			$placeholders = implode( ',', array_fill( 0, count( $keys ), '%s' ) );
			$sql         .= $this->wpdb->prepare(
				" AND engagement_kind = 'emoji' AND engagement_key IN ({$placeholders})",
				...$keys
			);
		} elseif ( $values ) {
			$placeholders = implode( ',', array_fill( 0, count( $values ), '%d' ) );
			$sql         .= $this->wpdb->prepare(
				" AND engagement_kind = 'star' AND value IN ({$placeholders})",
				...$values
			);
		}

		return $sql;
	}

	/**
	 * Cached check for whether a geo/device column exists on a legacy vote table.
	 *
	 * The country_code/device/os/browser columns are Pro-ensured on legacy
	 * tables, but sites that never ran the Pro upgrade routine (or never had
	 * Pro) will not have them. Stats queries must guard against their absence
	 * to avoid SQL errors.
	 *
	 * @param string $table  Full legacy table name.
	 * @param string $column Column name.
	 * @return bool
	 */
	private $legacy_geo_columns_cache = array();

	private function legacy_geo_column_exists( $table, $column ) {
		if ( ! isset( $this->legacy_geo_columns_cache[ $table ] ) ) {
			$present = array();
			$cols    = $this->wpdb->get_results(
				$this->wpdb->prepare(
					"SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
					WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s
					AND COLUMN_NAME IN ('country_code','device','os','browser','fingerprint')",
					DB_NAME,
					$table
				),
				ARRAY_A
			);
			foreach ( (array) $cols as $c ) {
				$present[ $c['COLUMN_NAME'] ] = true;
			}
			$this->legacy_geo_columns_cache[ $table ] = $present;
		}
		return isset( $this->legacy_geo_columns_cache[ $table ][ $column ] );
	}
}

