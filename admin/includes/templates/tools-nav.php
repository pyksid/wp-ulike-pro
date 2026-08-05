<?php
/**
 * Tools page — section navigation.
 *
 * @package WP_ULike_Pro
 * @var array $data Tools view data from WP_Ulike_Pro_Tools::get_tools_view_data().
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}
?>

<nav class="wp-ulike-tools-tabs" aria-label="<?php esc_attr_e( 'Tools sections', WP_ULIKE_PRO_DOMAIN ); ?>">
	<div class="wp-ulike-tools-tabs__scroll" tabindex="-1">
		<?php foreach ( $data['tabs'] as $tab_key => $tab ) : ?>
			<?php
			$is_active = $data['current_tab'] === $tab_key;
			$classes   = 'wp-ulike-tools-tabs__link' . ( $is_active ? ' is-active' : '' );
			?>
			<a
				href="<?php echo esc_url( add_query_arg( 'tab', $tab_key, $data['tools_base'] ) ); ?>"
				class="<?php echo esc_attr( $classes ); ?>"
				<?php echo $is_active ? 'aria-current="page"' : ''; ?>
			>
				<?php echo esc_html( $tab['label'] ); ?>
			</a>
		<?php endforeach; ?>
	</div>
</nav>

