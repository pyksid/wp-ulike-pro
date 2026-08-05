<?php
/**
 * Native post editor meta box — per-post display overrides.
 *
 *
 * @package    wp-ulike-pro
 * @author     TechnoWich 2026
 * @link       https://wpulike.com
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

class WP_Ulike_Pro_Post_Display_Meta {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'register' ) );
		add_action( 'save_post', array( $this, 'save' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue editor meta styles on post screens.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_assets( $hook ) {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
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
	 * Register meta boxes for enabled post types.
	 *
	 * @return void
	 */
	public function register() {
		if ( ! WP_Ulike_Pro_API::has_permission() ) {
			return;
		}

		$post_types = wp_ulike_get_option( 'enable_meta_box', array( 'post', 'page' ) );
		if ( empty( $post_types ) || ! is_array( $post_types ) ) {
			return;
		}

		foreach ( $post_types as $post_type ) {
			add_meta_box(
				'wp_ulike_pro_display_meta',
				esc_html__( 'WP ULike', WP_ULIKE_PRO_DOMAIN ),
				array( $this, 'render' ),
				$post_type,
				'side',
				'default'
			);
		}
	}

	/**
	 * Render meta box.
	 *
	 * @param WP_Post $post Current post.
	 * @return void
	 */
	public function render( $post ) {
		wp_nonce_field( 'wp_ulike_pro_display_meta', 'wp_ulike_pro_display_meta_nonce' );

		$auto_display = wp_ulike_pro_get_metabox_value_raw( 'auto_display', $post->ID );
		$template     = wp_ulike_pro_get_metabox_value_raw( 'template', $post->ID );
		$position     = wp_ulike_pro_get_metabox_value_raw( 'display_position', $post->ID );
		$likes_qty    = wp_ulike_pro_get_metabox_value_raw( 'likes_counter_quantity', $post->ID );
		$dislikes_qty = wp_ulike_pro_get_metabox_value_raw( 'dislikes_counter_quantity', $post->ID );
		$likes_qty_display    = ( '' === $likes_qty || null === $likes_qty || false === $likes_qty ) ? '' : $likes_qty;
		$dislikes_qty_display = ( '' === $dislikes_qty || null === $dislikes_qty || false === $dislikes_qty ) ? '' : $dislikes_qty;
		$templates    = wp_ulike_pro_get_templates_list_by_name();
		$tools_url    = admin_url( 'admin.php?page=wp-ulike-pro-tools&tab=schema-generator&post_id=' . $post->ID );
		$show_panel   = wp_ulike_is_true( $auto_display );
		?>
		<div class="wp-ulike-pro-editor-meta wp-ulike-pro-editor-meta--sidebar" id="wp-ulike-pro-display-meta-panel">
			<div class="wp-ulike-pro-editor-meta__section">
				<label class="wp-ulike-pro-editor-meta__toggle">
					<input type="checkbox" name="wp_ulike_pro_display[auto_display]" value="1" <?php checked( $show_panel ); ?>>
					<span><?php esc_html_e( 'Display button on this post', WP_ULIKE_PRO_DOMAIN ); ?></span>
				</label>
				<p class="wp-ulike-pro-editor-meta__help"><?php esc_html_e( 'Overrides global settings when enabled. For site-wide rules, use Display Automation in Tools.', WP_ULIKE_PRO_DOMAIN ); ?></p>
			</div>

			<div class="wp-ulike-pro-editor-meta__panel" id="wp-ulike-pro-display-meta-conditional" <?php echo $show_panel ? '' : 'hidden'; ?>>
				<div class="wp-ulike-pro-editor-meta__field">
					<label for="wp_ulike_pro_display_template"><?php esc_html_e( 'Template', WP_ULIKE_PRO_DOMAIN ); ?></label>
					<select name="wp_ulike_pro_display[template]" id="wp_ulike_pro_display_template" class="widefat">
						<option value=""><?php esc_html_e( 'Default', WP_ULIKE_PRO_DOMAIN ); ?></option>
						<?php foreach ( $templates as $key => $label ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $template, $key ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>

				<fieldset class="wp-ulike-pro-editor-meta__fieldset">
					<legend><?php esc_html_e( 'Position', WP_ULIKE_PRO_DOMAIN ); ?></legend>
					<div class="wp-ulike-pro-editor-meta__choices">
						<label class="wp-ulike-pro-editor-meta__choice">
							<input type="radio" name="wp_ulike_pro_display[display_position]" value="top" <?php checked( $position, 'top' ); ?>>
							<span><?php esc_html_e( 'Top of content', WP_ULIKE_PRO_DOMAIN ); ?></span>
						</label>
						<label class="wp-ulike-pro-editor-meta__choice">
							<input type="radio" name="wp_ulike_pro_display[display_position]" value="bottom" <?php checked( $position ? $position : 'bottom', 'bottom' ); ?>>
							<span><?php esc_html_e( 'Bottom of content', WP_ULIKE_PRO_DOMAIN ); ?></span>
						</label>
						<label class="wp-ulike-pro-editor-meta__choice">
							<input type="radio" name="wp_ulike_pro_display[display_position]" value="top_bottom" <?php checked( $position, 'top_bottom' ); ?>>
							<span><?php esc_html_e( 'Top and bottom', WP_ULIKE_PRO_DOMAIN ); ?></span>
						</label>
					</div>
				</fieldset>
			</div>

			<div class="wp-ulike-pro-editor-meta__section wp-ulike-pro-editor-meta__section--divider">
				<div class="wp-ulike-pro-editor-meta__field">
					<label for="wp_ulike_pro_likes_qty"><?php esc_html_e( 'Starting like count', WP_ULIKE_PRO_DOMAIN ); ?></label>
					<input type="number" class="small-text widefat" min="0" step="1" name="wp_ulike_pro_display[likes_counter_quantity]" id="wp_ulike_pro_likes_qty" value="<?php echo esc_attr( $likes_qty_display ); ?>" placeholder="0">
					<p class="wp-ulike-pro-editor-meta__help"><?php esc_html_e( 'Extra likes added to the real count shown on this post. Leave at 0 for no boost. Does not create votes in the database.', WP_ULIKE_PRO_DOMAIN ); ?></p>
				</div>
				<div class="wp-ulike-pro-editor-meta__field">
					<label for="wp_ulike_pro_dislikes_qty"><?php esc_html_e( 'Starting dislike count', WP_ULIKE_PRO_DOMAIN ); ?></label>
					<input type="number" class="small-text widefat" min="0" step="1" name="wp_ulike_pro_display[dislikes_counter_quantity]" id="wp_ulike_pro_dislikes_qty" value="<?php echo esc_attr( $dislikes_qty_display ); ?>" placeholder="0">
					<p class="wp-ulike-pro-editor-meta__help"><?php esc_html_e( 'Extra dislikes added to the real count shown on this post. Leave at 0 for no boost. Does not create votes in the database.', WP_ULIKE_PRO_DOMAIN ); ?></p>
				</div>
			</div>

			<div class="wp-ulike-pro-editor-meta__footer">
				<a href="<?php echo esc_url( $tools_url ); ?>" class="wp-ulike-pro-editor-meta__footer-link">
					<?php esc_html_e( 'Configure star ratings & schema in Tools', WP_ULIKE_PRO_DOMAIN ); ?>
					<span class="dashicons dashicons-external" aria-hidden="true"></span>
				</a>
			</div>
		</div>

		<script>
		(function () {
			const box = document.getElementById('wp_ulike_pro_display_meta');
			if (!box) return;
			const toggle = box.querySelector('input[name="wp_ulike_pro_display[auto_display]"]');
			const panel = document.getElementById('wp-ulike-pro-display-meta-conditional');
			if (!toggle || !panel) return;
			toggle.addEventListener('change', function () {
				panel.hidden = !toggle.checked;
			});
		})();
		</script>
		<?php
	}

	/**
	 * Save meta box values.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 * @return void
	 */
	public function save( $post_id, $post ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		if ( ! isset( $_POST['wp_ulike_pro_display_meta_nonce'] ) || ! wp_verify_nonce( $_POST['wp_ulike_pro_display_meta_nonce'], 'wp_ulike_pro_display_meta' ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) || ! WP_Ulike_Pro_API::has_permission() ) {
			return;
		}

		$raw = isset( $_POST['wp_ulike_pro_display'] ) && is_array( $_POST['wp_ulike_pro_display'] )
			? wp_unslash( $_POST['wp_ulike_pro_display'] )
			: array();

		$values = array(
			'auto_display'     => ! empty( $raw['auto_display'] ) ? 'true' : 'false',
			'template'         => isset( $raw['template'] ) ? $raw['template'] : '',
			'display_position' => isset( $raw['display_position'] ) ? $raw['display_position'] : 'bottom',
		);

		$likes_qty    = isset( $raw['likes_counter_quantity'] ) ? absint( $raw['likes_counter_quantity'] ) : 0;
		$dislikes_qty = isset( $raw['dislikes_counter_quantity'] ) ? absint( $raw['dislikes_counter_quantity'] ) : 0;

		// Skip storing 0 on first save; still save 0 when clearing a previous value.
		if ( $likes_qty > 0 || self::counter_quantity_is_stored( $post_id, 'likes_counter_quantity' ) ) {
			$values['likes_counter_quantity'] = $likes_qty;
		}
		if ( $dislikes_qty > 0 || self::counter_quantity_is_stored( $post_id, 'dislikes_counter_quantity' ) ) {
			$values['dislikes_counter_quantity'] = $dislikes_qty;
		}

		wp_ulike_pro_save_metabox_values( $post_id, $values, true );
	}

	/**
	 * Whether a counter offset was previously saved for this post.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $key     Meta key without prefix.
	 * @return bool
	 */
	private static function counter_quantity_is_stored( $post_id, $key ) {
		$stored = wp_ulike_pro_get_metabox_value_raw( $key, $post_id );
		return ! ( '' === $stored || null === $stored || false === $stored );
	}
}

