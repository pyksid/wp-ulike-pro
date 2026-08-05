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
				throw new Exception( esc_html__( 'Invalid request.', WP_ULIKE_PRO_DOMAIN ) );
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
				throw new Exception( esc_html__( 'Invalid request.', WP_ULIKE_PRO_DOMAIN ) );
			}

			$context_bootstrapped = WP_Ulike_Pro_Engagement_Settings::bootstrap_ajax_context(
				$type,
				array(
					'engagement_template' => $template,
					'engagement_kind'     => $kind,
				)
			);

			if ( ! WP_Ulike_Pro_Engagement_Settings::show_engagers( $type ) ) {
				throw new Exception( esc_html__( 'Engagers are not available for this content.', WP_ULIKE_PRO_DOMAIN ) );
			}

			if ( wp_ulike_setting_repo::restrictLikersBox( $type ) && ! $this->user ) {
				throw new Exception( esc_html__( 'Please log in to view engagers.', WP_ULIKE_PRO_DOMAIN ) );
			}

			if ( 'markup' === $format ) {
				$template = WP_Ulike_Pro_Engagement_Engagers::render( $item_id, $type, $kind );
			} elseif ( 'popover' === $format ) {
				$template = WP_Ulike_Pro_Engagement_Engagers::render_popover_list( $item_id, $type, $kind );
			} else {
				$template = WP_Ulike_Pro_Engagement_Engagers::render_modal_list( $item_id, $type, $kind );
			}

			if ( empty( $template ) ) {
				throw new Exception( esc_html__( 'No engagers yet.', WP_ULIKE_PRO_DOMAIN ) );
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

