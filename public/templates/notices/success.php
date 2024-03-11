<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! $notices ) {
	return;
}
?>

<?php foreach ( $notices as $notice ) : ?>
	<div class="ulp-notice-banner is-success" role="alert">
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false">
			<path d="M16.7 7.1l-6.3 8.5-3.3-2.5-.9 1.2 4.5 3.4L17.9 8z"></path>
		</svg>
		<div class="ulp-notice-banner__content">
			<?php echo wp_ulike_pro_kses_notice( $notice['notice'] ); ?>
		</div>
	</div>
<?php endforeach; ?>
