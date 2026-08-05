<?php
/**
 * Native comment editor meta — per-comment display overrides.
 *
 *
 * @package    wp-ulike-pro
 * @author     TechnoWich 2026
 * @link       https://wpulike.com
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

class WP_Ulike_Pro_Comment_Display_Meta {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'register' ) );
		add_action( 'add_meta_boxes_comment', array( $this, 'register' ) );
		add_action( 'edit_comment', array( $this, 'save' ) );
		add_action( 'comment_post', array( $this, 'save' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue editor meta styles on comment screens.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_assets( $hook ) {
		if ( 'comment.php' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'wp-ulike-pro-admin-styles',
			WP_ULIKE_PRO_ADMIN_URL . '/assets/css/admin.css',
			array(),
			WP_ULIKE_PRO_VERSION
		);
	}

	/**
	 * Register comment meta box.
	 *
	 * @return void
	 */
	public function register() {
		if ( ! WP_Ulike_Pro_API::has_permission() ) {
			return;
		}

		add_meta_box(
			'wp_ulike_pro_comment_display_meta',
			esc_html__( 'WP ULike', WP_ULIKE_PRO_DOMAIN ),
			array( $this, 'render' ),
			'comment',
			'normal',
			'high'
		);
	}

	/**
	 * Render comment meta box.
	 *
	 * @param WP_Comment $comment Comment object.
	 * @return void
	 */
	public function render( $comment ) {
		wp_nonce_field( 'wp_ulike_pro_comment_display_meta', 'wp_ulike_pro_comment_display_meta_nonce' );

		$box = wp_ulike_pro_get_comment_metabox_blob( $comment->comment_ID );
		$get = static function ( $key, $default = '' ) use ( $box ) {
			if ( ! array_key_exists( $key, $box ) ) {
				return $default;
			}
			return maybe_unserialize( $box[ $key ] );
		};

		$auto_display = $get( 'auto_display' );
		$template     = $get( 'template' );
		$position     = $get( 'display_position' );
		$likes_qty    = $get( 'likes_counter_quantity' );
		$dislikes_qty = $get( 'dislikes_counter_quantity' );
		$likes_qty_display    = ( '' === $likes_qty || null === $likes_qty || false === $likes_qty ) ? '' : $likes_qty;
		$dislikes_qty_display = ( '' === $dislikes_qty || null === $dislikes_qty || false === $dislikes_qty ) ? '' : $dislikes_qty;
		$templates    = wp_ulike_pro_get_templates_list_by_name();
		$show_panel   = wp_ulike_is_true( $auto_display );
		?>
		<div class="wp-ulike-pro-editor-meta wp-ulike-pro-editor-meta--table">
			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row"><?php esc_html_e( 'Display', WP_ULIKE_PRO_DOMAIN ); ?></th>
						<td>
							<label class="wp-ulike-pro-editor-meta__toggle">
								<input type="checkbox" name="wp_ulike_pro_comment_display[auto_display]" value="1" <?php checked( $show_panel ); ?>>
								<span><?php esc_html_e( 'Show like button on this comment', WP_ULIKE_PRO_DOMAIN ); ?></span>
							</label>
						</td>
					</tr>
					<tr class="wp-ulike-pro-comment-display-conditional" <?php echo $show_panel ? '' : 'style="display:none;"'; ?>>
						<th scope="row"><label for="wp_ulike_pro_comment_template"><?php esc_html_e( 'Template', WP_ULIKE_PRO_DOMAIN ); ?></label></th>
						<td>
							<select name="wp_ulike_pro_comment_display[template]" id="wp_ulike_pro_comment_template" class="regular-text">
								<option value=""><?php esc_html_e( 'Default', WP_ULIKE_PRO_DOMAIN ); ?></option>
								<?php foreach ( $templates as $key => $label ) : ?>
									<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $template, $key ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr class="wp-ulike-pro-comment-display-conditional" <?php echo $show_panel ? '' : 'style="display:none;"'; ?>>
						<th scope="row"><?php esc_html_e( 'Position', WP_ULIKE_PRO_DOMAIN ); ?></th>
						<td>
							<div class="wp-ulike-pro-editor-meta__choices">
								<label class="wp-ulike-pro-editor-meta__choice">
									<input type="radio" name="wp_ulike_pro_comment_display[display_position]" value="top" <?php checked( $position, 'top' ); ?>>
									<span><?php esc_html_e( 'Top', WP_ULIKE_PRO_DOMAIN ); ?></span>
								</label>
								<label class="wp-ulike-pro-editor-meta__choice">
									<input type="radio" name="wp_ulike_pro_comment_display[display_position]" value="bottom" <?php checked( $position ? $position : 'bottom', 'bottom' ); ?>>
									<span><?php esc_html_e( 'Bottom', WP_ULIKE_PRO_DOMAIN ); ?></span>
								</label>
								<label class="wp-ulike-pro-editor-meta__choice">
									<input type="radio" name="wp_ulike_pro_comment_display[display_position]" value="top_bottom" <?php checked( $position, 'top_bottom' ); ?>>
									<span><?php esc_html_e( 'Top and bottom', WP_ULIKE_PRO_DOMAIN ); ?></span>
								</label>
							</div>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="wp_ulike_pro_comment_likes_qty"><?php esc_html_e( 'Starting like count', WP_ULIKE_PRO_DOMAIN ); ?></label></th>
						<td>
							<input type="number" class="small-text" min="0" step="1" name="wp_ulike_pro_comment_display[likes_counter_quantity]" id="wp_ulike_pro_comment_likes_qty" value="<?php echo esc_attr( $likes_qty_display ); ?>" placeholder="0">
							<p class="description"><?php esc_html_e( 'Extra likes added to the real count shown on this comment. Leave at 0 for no boost. Does not create votes in the database.', WP_ULIKE_PRO_DOMAIN ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="wp_ulike_pro_comment_dislikes_qty"><?php esc_html_e( 'Starting dislike count', WP_ULIKE_PRO_DOMAIN ); ?></label></th>
						<td>
							<input type="number" class="small-text" min="0" step="1" name="wp_ulike_pro_comment_display[dislikes_counter_quantity]" id="wp_ulike_pro_comment_dislikes_qty" value="<?php echo esc_attr( $dislikes_qty_display ); ?>" placeholder="0">
							<p class="description"><?php esc_html_e( 'Extra dislikes added to the real count shown on this comment. Leave at 0 for no boost. Does not create votes in the database.', WP_ULIKE_PRO_DOMAIN ); ?></p>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
		<script>
		(function () {
			const toggle = document.querySelector('#wp_ulike_pro_comment_display_meta input[name="wp_ulike_pro_comment_display[auto_display]"]');
			const rows = document.querySelectorAll('.wp-ulike-pro-comment-display-conditional');
			if (!toggle) return;
			toggle.addEventListener('change', function () {
				rows.forEach(function (row) {
					row.style.display = toggle.checked ? '' : 'none';
				});
			});
		})();
		</script>
		<?php
	}

	/**
	 * Save comment meta.
	 *
	 * @param int $comment_id Comment ID.
	 * @return void
	 */
	public function save( $comment_id ) {
		if ( ! isset( $_POST['wp_ulike_pro_comment_display_meta_nonce'] ) || ! wp_verify_nonce( $_POST['wp_ulike_pro_comment_display_meta_nonce'], 'wp_ulike_pro_comment_display_meta' ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_comment', $comment_id ) || ! WP_Ulike_Pro_API::has_permission() ) {
			return;
		}

		$raw = isset( $_POST['wp_ulike_pro_comment_display'] ) && is_array( $_POST['wp_ulike_pro_comment_display'] )
			? wp_unslash( $_POST['wp_ulike_pro_comment_display'] )
			: array();

		$values = array(
			'auto_display'     => ! empty( $raw['auto_display'] ) ? 'true' : 'false',
			'template'         => isset( $raw['template'] ) ? $raw['template'] : '',
			'display_position' => isset( $raw['display_position'] ) ? $raw['display_position'] : 'bottom',
		);

		$likes_qty    = isset( $raw['likes_counter_quantity'] ) ? absint( $raw['likes_counter_quantity'] ) : 0;
		$dislikes_qty = isset( $raw['dislikes_counter_quantity'] ) ? absint( $raw['dislikes_counter_quantity'] ) : 0;

		if ( $likes_qty > 0 || self::counter_quantity_is_stored( $comment_id, 'likes_counter_quantity' ) ) {
			$values['likes_counter_quantity'] = $likes_qty;
		}
		if ( $dislikes_qty > 0 || self::counter_quantity_is_stored( $comment_id, 'dislikes_counter_quantity' ) ) {
			$values['dislikes_counter_quantity'] = $dislikes_qty;
		}

		wp_ulike_pro_save_comment_metabox_values( $comment_id, $values );
	}

	/**
	 * Whether a counter offset was previously saved for this comment.
	 *
	 * @param int    $comment_id Comment ID.
	 * @param string $key        Meta key without prefix.
	 * @return bool
	 */
	private static function counter_quantity_is_stored( $comment_id, $key ) {
		$box = wp_ulike_pro_get_comment_metabox_blob( $comment_id );
		if ( ! is_array( $box ) || ! array_key_exists( $key, $box ) ) {
			return false;
		}
		$stored = maybe_unserialize( $box[ $key ] );
		return ! ( '' === $stored || null === $stored || false === $stored );
	}
}

