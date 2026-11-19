<?php
/**
 * Engagement engagers AJAX listener.
 *
 * @package WP ULike Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WP_Ulike_Pro_Engagement_Engagers_Listener extends wp_ulike_ajax_listener_base {

	public function __construct() {
		parent::__construct();
		$this->print_list();
	}

	/**
	 * Output modal HTML.
	 *
	 * @return void
	 */
	private function print_list() {
		$context_bootstrapped = false;
		$type                 = '';

		try {
			$item_id = isset( $_REQUEST['id'] ) ? absint( wp_unslash( $_REQUEST['id'] ) ) : 0;
			$type    = isset( $_REQUEST['type'] ) ? sanitize_key( wp_unslash( $_REQUEST['type'] ) ) : '';
			$kind    = isset( $_REQUEST['engagement_kind'] ) ? wp_ulike_pro_sanitize_engagement_kind( wp_unslash( $_REQUEST['engagement_kind'] ) ) : '';
			$format  = isset( $_REQUEST['format'] ) ? sanitize_key( wp_unslash( $_REQUEST['format'] ) ) : 'modal';

			if ( ! $item_id || ! $type || ! $kind ) {
				throw new Exception( __( 'Invalid request.', 'wp-ulike-pro' ) );
			}

			$template = isset( $_REQUEST['engagement_template'] )
				? sanitize_key( wp_unslash( $_REQUEST['engagement_template'] ) )
				: '';
			$nonce    = isset( $_REQUEST['_wpnonce'] )
				? sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) )
				: '';

			if (
				! $template
				|| ! WP_Ulike_Pro_Engagement_Settings::is_engagement_template( $template )
				|| ! $nonce
				|| ! wp_verify_nonce(
					$nonce,
					WP_Ulike_Pro_Engagement_Settings::get_engagers_nonce_action( $type, $item_id, $kind, $template )
				)
			) {
				throw new Exception( __( 'Invalid request.', 'wp-ulike-pro' ) );
			}

			$context_bootstrapped = WP_Ulike_Pro_Engagement_Settings::bootstrap_ajax_context(
				$type,
				array(
					'engagement_template' => $template,
					'engagement_kind'     => $kind,
				)
			);

			if ( ! WP_Ulike_Pro_Engagement_Settings::show_engagers( $type ) ) {
				throw new Exception( __( 'Engagers are not available for this content.', 'wp-ulike-pro' ) );
			}

			if ( wp_ulike_setting_repo::restrictLikersBox( $type ) && ! $this->user ) {
				throw new Exception( WP_Ulike_Pro_Options::getNoticeMessage( 'engagers_login_required', __( 'Please log in to view engagers.', 'wp-ulike-pro' ) ) );
			}

			if ( 'markup' === $format ) {
				$template = WP_Ulike_Pro_Engagement_Engagers::render( $item_id, $type, $kind );
			} elseif ( 'popover' === $format ) {
				$template = WP_Ulike_Pro_Engagement_Engagers::render_popover_list( $item_id, $type, $kind );
			} else {
				$template = WP_Ulike_Pro_Engagement_Engagers::render_modal_list( $item_id, $type, $kind );
			}

			if ( empty( $template ) ) {
				throw new Exception( WP_Ulike_Pro_Options::getNoticeMessage( 'no_engagers', __( 'No engagers yet.', 'wp-ulike-pro' ) ) );
			}

			$template = apply_filters( 'wp_ulike_pro_engagement_engagers_template', $template, $item_id, $type, $kind );

			if ( 'popover' === $format ) {
				wp_send_json_success(
					array(
						'template' => $template,
					)
				);
			}

			echo $template; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			wp_die();

		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		} finally {
			if ( $context_bootstrapped ) {
				WP_Ulike_Pro_Engagement_Settings::pop_context( $type );
			}
		}
	}
}

