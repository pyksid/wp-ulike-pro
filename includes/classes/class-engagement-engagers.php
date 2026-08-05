<?php
/**
 * Engagement engagers (who reacted / rated).
 *
 * @package WP ULike Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WP_Ulike_Pro_Engagement_Engagers {

	/**
	 * Get logged-in engagers for an item.
	 *
	 * @param int         $item_id   Item ID.
	 * @param string      $item_type Content type slug.
	 * @param string|null $kind      Optional engagement kind filter.
	 * @param int|null    $limit     Max rows.
	 * @return array<int,array{user_id:int,engagement_key:string,value:?int,emoji:string,label:string}>
	 */
	public static function get_engagers( $item_id, $item_type, $kind = null, $limit = 12 ) {
		global $wpdb;

		$table = wp_ulike_pro_engagement_table();
		if ( empty( $table ) || ! $item_id ) {
			return array();
		}

		// Latest row per user (one avatar per user, most recent engagement wins).
		// Filters by kind when provided; spans emoji + star otherwise.
		if ( $kind ) {
			$inner = $wpdb->prepare(
				"SELECT MAX(id) AS max_id
				FROM `{$table}`
				WHERE item_id = %d AND item_type = %s AND engagement_kind = %s AND status = %s
				AND user_id REGEXP '^[0-9]+$'
				GROUP BY user_id",
				$item_id,
				$item_type,
				$kind,
				'active'
			);
		} else {
			$kinds_sql = ' AND ' . wp_ulike_pro_pulse_pro_kinds_sql();
			$inner = $wpdb->prepare(
				"SELECT MAX(id) AS max_id
				FROM `{$table}`
				WHERE item_id = %d AND item_type = %s AND status = %s{$kinds_sql}
				AND user_id REGEXP '^[0-9]+$'
				GROUP BY user_id",
				$item_id,
				$item_type,
				'active'
			);
		}

		$sql = "SELECT e.user_id, e.engagement_kind, e.engagement_key, e.value
			FROM `{$table}` e
			INNER JOIN ({$inner}) latest ON e.id = latest.max_id
			ORDER BY e.id DESC";

		if ( $limit > 0 ) {
			$sql .= $wpdb->prepare( ' LIMIT %d', (int) $limit );
		}

		$rows     = $wpdb->get_results( $sql, ARRAY_A );
		$engagers = array();

		foreach ( (array) $rows as $row ) {
			$user_id = (int) $row['user_id'];
			if ( $user_id < 1 || ! get_userdata( $user_id ) ) {
				continue;
			}

			$key      = (string) $row['engagement_key'];
			$reaction = 'emoji' === $row['engagement_kind']
				? WP_Ulike_Pro_Engagement_Registry::get_reaction( $key, $item_type )
				: null;

			$engagers[] = array(
				'user_id'        => $user_id,
				'engagement_key' => $key,
				'value'          => is_null( $row['value'] ) ? null : (int) $row['value'],
				'emoji'          => $reaction ? $reaction['emoji'] : ( 'star' === $row['engagement_kind'] ? '★' : '' ),
				'label'          => $reaction ? wp_strip_all_tags( $reaction['label'] ) : '',
			);
		}

		return apply_filters( 'wp_ulike_pro_engagement_engagers', $engagers, $item_id, $item_type, $kind );
	}

	/**
	 * Render engagers UI (respects likers_style: default, popover, pile).
	 *
	 * @param int    $item_id   Item ID.
	 * @param string $item_type Content type slug.
	 * @param string $kind      Engagement kind.
	 * @return string
	 */
	public static function render( $item_id, $item_type, $kind ) {
		$style = WP_Ulike_Pro_Engagement_Settings::get_engagers_style( $item_type );

		if ( 'pile' === $style ) {
			return self::render_pile( $item_id, $item_type, $kind );
		}

		if ( 'popover' === $style ) {
			return '';
		}

		return self::render_default( $item_id, $item_type, $kind );
	}

	/**
	 * Popover AJAX URL for a given item/kind.
	 *
	 * @param int    $item_id   Item ID.
	 * @param string $item_type Content type slug.
	 * @param string $kind      Engagement kind.
	 * @return string
	 */
	public static function get_popover_ajax_url( $item_id, $item_type, $kind ) {
		return self::get_engagers_ajax_url( $item_id, $item_type, $kind, 'popover' );
	}

	/**
	 * Build engagers AJAX URL.
	 *
	 * @param int    $item_id   Item ID.
	 * @param string $item_type Content type slug.
	 * @param string $kind      Engagement kind.
	 * @param string $format    modal|popover|markup.
	 * @return string
	 */
	public static function get_engagers_ajax_url( $item_id, $item_type, $kind, $format = 'modal' ) {
		$args = array(
			'action'          => 'ulp_engagement_engagers',
			'id'              => $item_id,
			'type'            => $item_type,
			'engagement_kind' => $kind,
			'format'          => $format,
		);

		// Preserve automation/shortcode template so AJAX honors the same engagers settings.
		$template = WP_Ulike_Pro_Engagement_Settings::get_template_slug( $item_type );
		if ( WP_Ulike_Pro_Engagement_Settings::is_engagement_template( $template ) ) {
			$args['engagement_template'] = $template;
			$args['_wpnonce']            = wp_create_nonce(
				WP_Ulike_Pro_Engagement_Settings::get_engagers_nonce_action( $item_type, $item_id, $kind, $template )
			);
		}

		return add_query_arg( $args, admin_url( 'admin-ajax.php' ) );
	}

	/**
	 * Likers list limits from content-type settings.
	 *
	 * @param string $item_type Content type slug.
	 * @return array{limit:int,avatar_size:int}
	 */
	private static function get_likers_list_limits( $item_type ) {
		$group   = WP_Ulike_Pro_Engagement_Settings::get_option_group( $item_type );
		$options = $group ? wp_ulike_get_option( $group, array() ) : array();

		return array(
			'limit'       => ! empty( $options['likers_count'] ) ? absint( $options['likers_count'] ) : 10,
			'avatar_size' => ! empty( $options['likers_gravatar_size'] ) ? absint( $options['likers_gravatar_size'] ) : 64,
		);
	}

	/**
	 * Render avatar pile for engagers.
	 *
	 * @param int    $item_id   Item ID.
	 * @param string $item_type Content type slug.
	 * @param string $kind      Engagement kind.
	 * @return string
	 */
	public static function render_pile( $item_id, $item_type, $kind ) {
		$engagers = self::get_engagers( $item_id, $item_type, $kind, 8 );

		if ( empty( $engagers ) ) {
			return '';
		}

		$ajax_url = self::get_engagers_ajax_url( $item_id, $item_type, $kind, 'modal' );

		ob_start();
		?>
		<div class="ulp-engagement-engagers ulp-engagement-engagers-pile ulp-engagement-engagers-trigger"
			data-ulpmodal-type="ajax"
			data-ulpmodal="<?php echo esc_url( $ajax_url ); ?>"
			tabindex="0"
			role="button"
			aria-label="<?php esc_attr_e( 'View all engagers', WP_ULIKE_PRO_DOMAIN ); ?>">
			<?php echo self::render_pile_avatars( $engagers, $kind ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Shared overlapping avatar pile markup.
	 *
	 * @param array<int,array<string,mixed>> $engagers Engager rows.
	 * @param string                         $kind     Engagement kind.
	 * @return string
	 */
	private static function render_pile_avatars( $engagers, $kind = '' ) {
		ob_start();
		?>
		<div class="ulp-engagement-engagers-pile">
			<?php foreach ( $engagers as $engager ) : ?>
				<?php
				$name  = self::get_display_name( $engager['user_id'] );
				$meta  = $kind ? self::get_engager_meta_label( $engager, $kind ) : '';
				$title = $meta ? $name . ' · ' . $meta : $name;
				?>
				<span class="ulp-engagement-engager-avatar" title="<?php echo esc_attr( $title ); ?>">
					<?php echo get_avatar( $engager['user_id'], 28 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</span>
			<?php endforeach; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render inline avatar list (default likers style — matches core wp-ulike).
	 *
	 * @param int    $item_id   Item ID.
	 * @param string $item_type Content type slug.
	 * @param string $kind      Engagement kind.
	 * @return string
	 */
	public static function render_default( $item_id, $item_type, $kind ) {
		$limits   = self::get_likers_list_limits( $item_type );
		$engagers = self::get_engagers( $item_id, $item_type, $kind, 0 );

		if ( empty( $engagers ) ) {
			return '';
		}

		$preview  = array_slice( $engagers, 0, $limits['limit'] );
		$has_more = count( $engagers ) > count( $preview );
		$ajax_url = self::get_engagers_ajax_url( $item_id, $item_type, $kind, 'modal' );
		$list     = '';

		foreach ( $preview as $engager ) {
			$user_id = (int) $engager['user_id'];
			$name    = self::get_display_name( $user_id );
			$profile = get_author_posts_url( $user_id );
			$meta    = self::get_engager_meta_label( $engager, $kind );
			$title   = $meta ? $name . ' · ' . $meta : $name;

			$list .= sprintf(
				'<span class="wp-ulike-liker ulp-engagement-default-liker"><a href="%1$s" title="%2$s">%3$s</a></span>',
				esc_url( $profile ),
				esc_attr( $title ),
				get_avatar( $user_id, $limits['avatar_size'], '', esc_attr( $name ), array( 'class' => 'avatar' ) )
			);
		}

		$more = $has_more
			? sprintf(
				'<span class="ulp-engagement-engagers-more">+%s</span>',
				esc_html( wp_ulike_pro_format_engagement_count( count( $engagers ) - count( $preview ) ) )
			)
			: '';

		ob_start();
		?>
		<div class="ulp-engagement-engagers ulp-engagement-engagers-default ulp-engagement-engagers-trigger"
			data-ulpmodal-type="ajax"
			data-ulpmodal="<?php echo esc_url( $ajax_url ); ?>"
			tabindex="0"
			role="button"
			aria-label="<?php esc_attr_e( 'View all engagers', WP_ULIKE_PRO_DOMAIN ); ?>">
			<div class="wp_ulike_likers_wrapper wp_<?php echo esc_attr( $item_type ); ?>_engagers_<?php echo (int) $item_id; ?>">
				<div class="wp-ulike-likers-list"><?php echo $list; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo $more; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Popover list markup (classic likers-style avatars).
	 *
	 * @param int    $item_id   Item ID.
	 * @param string $item_type Content type slug.
	 * @param string $kind      Engagement kind.
	 * @return string
	 */
	public static function render_popover_list( $item_id, $item_type, $kind ) {
		$limits   = self::get_likers_list_limits( $item_type );
		$engagers = self::get_engagers( $item_id, $item_type, $kind, $limits['limit'] );

		if ( empty( $engagers ) ) {
			return '';
		}

		$list = '';
		foreach ( $engagers as $engager ) {
			$user_id = (int) $engager['user_id'];
			$user    = get_userdata( $user_id );
			if ( ! $user ) {
				continue;
			}

			$name  = self::get_display_name( $user_id );
			$meta  = self::get_engager_meta_label( $engager, $kind );
			$title = $meta ? $name . ' · ' . $meta : $name;

			$list .= sprintf(
				'<span class="wp-ulike-liker ulp-engagement-popover-liker"><a href="%1$s" title="%2$s">%3$s</a></span>',
				esc_url( get_author_posts_url( $user_id ) ),
				esc_attr( $title ),
				get_avatar( $user_id, $limits['avatar_size'], '', esc_attr( $name ), array( 'class' => 'avatar' ) )
			);
		}

		if ( '' === $list ) {
			return '';
		}

		return sprintf(
			'<div class="wp_ulike_likers_wrapper wp_%1$s_engagers_%2$d ulp-engagement-engagers-popover-list"><div class="wp-ulike-likers-list">%3$s</div></div>',
			esc_attr( $item_type ),
			(int) $item_id,
			$list
		);
	}

	/**
	 * Modal list markup for AJAX engagers endpoint.
	 *
	 * @param int    $item_id   Item ID.
	 * @param string $item_type Content type slug.
	 * @param string $kind      Engagement kind.
	 * @return string
	 */
	public static function render_modal_list( $item_id, $item_type, $kind ) {
		$engagers = self::get_engagers( $item_id, $item_type, $kind, 0 );

		if ( empty( $engagers ) ) {
			return '';
		}

		$list = '';
		foreach ( $engagers as $engager ) {
			$user_id = (int) $engager['user_id'];
			$profile = get_author_posts_url( $user_id );
			$name    = self::get_display_name( $user_id );
			$meta    = self::get_engager_meta_label( $engager, $kind );

			$list .= sprintf(
				'<a href="%1$s" class="ulp-flex-row ulp-flex-middle-xs ulp-flex-start-md ulp-engagement-engager-row"><span class="ulp-flex-col-md-2 ulp-flex-col-xs-1 ulp-user-icon">%2$s</span><span class="ulp-flex-col-md-10 ulp-flex-col-xs-11 ulp-user-info"><span class="ulp-title">%3$s</span></span><span class="ulp-engagement-engager-meta">%4$s %5$s</span></a>',
				esc_url( $profile ),
				get_avatar( $user_id, 36, '', '', array( 'class' => 'ulp-img-icon' ) ),
				esc_html( $name ),
				! empty( $engager['emoji'] ) ? '<span class="ulp-engagement-engager-emoji">' . esc_html( $engager['emoji'] ) . '</span>' : '',
				esc_html( $meta )
			);
		}

		$title = class_exists( 'WP_Ulike_Pro_Options' )
			? WP_Ulike_Pro_Options::getLikersModalTitle( $item_type )
			: esc_html__( 'Likers', WP_ULIKE_PRO_DOMAIN );

		return sprintf(
			'<div class="ulpmodal-ajax-wrapper ulp-engagement-engagers-modal"><h3 class="ulpmodal-title">%1$s</h3><div class="ulp-modal-likers-list ulp-engagement-engagers-list">%2$s</div></div>',
			esc_html( $title ),
			$list
		);
	}

	/**
	 * Human-readable reaction/rating label for an engager row.
	 *
	 * @param array  $engager Engager row.
	 * @param string $kind    Engagement kind.
	 * @return string
	 */
	private static function get_engager_meta_label( $engager, $kind ) {
		if ( 'star' === $kind && ! empty( $engager['value'] ) ) {
			return sprintf(
				/* translators: %d: star value */
				esc_html__( '%d stars', WP_ULIKE_PRO_DOMAIN ),
				(int) $engager['value']
			);
		}

		if ( ! empty( $engager['label'] ) ) {
			return (string) $engager['label'];
		}

		return ! empty( $engager['engagement_key'] ) ? (string) $engager['engagement_key'] : '';
	}

	/**
	 * @param int $user_id User ID.
	 * @return string
	 */
	private static function get_display_name( $user_id ) {
		$user = get_userdata( $user_id );

		return $user ? $user->display_name : '';
	}
}

