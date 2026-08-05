<?php
/**
 * Engagement AJAX listener.
 *
 * @package WP ULike Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class WP_Ulike_Pro_Engagement_Listener extends wp_ulike_ajax_listener_base {

	private $response = array(
		'message'     => null,
		'messageType' => 'info',
		'status'      => 0,
		'data'        => null,
		'hasToast'    => true,
	);

	/** @var bool */
	private $engagement_context_bootstrapped = false;

	public function __construct() {
		parent::__construct();
		$this->set_form_data();
		$this->process();
	}

	/**
	 * Parse POST data.
	 *
	 * @return void
	 */
	private function set_form_data() {
		$this->data = array(
			'id'                      => isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0,
			'type'                    => isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : '',
			'nonce'                   => isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '',
			'engagement_kind'         => isset( $_POST['engagement_kind'] ) ? sanitize_key( wp_unslash( $_POST['engagement_kind'] ) ) : '',
			'engagement_key'          => isset( $_POST['engagement_key'] ) ? sanitize_key( wp_unslash( $_POST['engagement_key'] ) ) : '',
			'engagement_template'     => isset( $_POST['engagement_template'] ) ? sanitize_key( wp_unslash( $_POST['engagement_template'] ) ) : '',
			'engagement_picker_style' => isset( $_POST['engagement_picker_style'] ) ? sanitize_key( wp_unslash( $_POST['engagement_picker_style'] ) ) : '',
			'value'                   => isset( $_POST['value'] ) ? absint( $_POST['value'] ) : 0,
			'client_address'          => wp_ulike_get_user_ip(),
		);

		$this->data = apply_filters( 'wp_ulike_engagement_listener_data', $this->data );
	}

	/**
	 * Process engagement vote.
	 *
	 * @return void
	 */
	private function process() {
		try {
			do_action( 'wp_ulike_before_engagement_process', $this->data );

			$this->validate();

			$this->engagement_context_bootstrapped = WP_Ulike_Pro_Engagement_Settings::bootstrap_ajax_context(
				$this->data['type'],
				array(
					'engagement_template'     => $this->data['engagement_template'],
					'engagement_kind'         => $this->data['engagement_kind'],
					'engagement_picker_style' => $this->data['engagement_picker_style'],
				)
			);

			if ( ! WP_Ulike_Pro_Engagement_Settings::allows_engagement_kind( $this->data['type'], $this->data['engagement_kind'] ) ) {
				throw new Exception( esc_html__( 'Engagements are not enabled for this content type.', WP_ULIKE_PRO_DOMAIN ) );
			}

			$settings = wp_ulike_setting_type::get_instance( $this->data['type'] );

			if ( wp_ulike_setting_repo::requireLogin( $settings->getType() ) && ! $this->user ) {
				$this->response['message']      = wp_ulike_setting_repo::getLoginNotice();
				$this->response['status']       = 4;
				$this->response['requireLogin'] = true;
				$this->response['hasToast']     = wp_ulike_setting_repo::hasToast( $settings->getType() );
				return $this->response( apply_filters( 'wp_ulike_engagement_ajax_respond', $this->response, $this->data ) );
			}

			$lock_type = 'engagement_' . $this->data['type'];
			$fp_lock   = wp_ulike_acquire_lock( $lock_type, $this->data['id'] );

			if ( false === $fp_lock ) {
				throw new Exception( esc_html__( 'Unable to obtain lock for this request.', 'wp-ulike' ) );
			}

			$processor = new WP_Ulike_Pro_Engagement_Process(
				array(
					'item_id'         => $this->data['id'],
					'item_type'       => $this->data['type'],
					'engagement_kind' => $this->data['engagement_kind'],
					'engagement_key'  => $this->data['engagement_key'],
					'value'           => $this->data['value'] > 0 ? $this->data['value'] : null,
					'user_ip'         => $this->data['client_address'],
				)
			);

			if ( ! $processor->update() ) {
				$this->response['message']     = $this->get_permission_message( $processor );
				$this->response['status']      = 5;
				$this->response['messageType'] = 'warning';
			} else {
				$this->response['status']      = $processor->get_status_code();
				$this->response['messageType'] = 'removed' === $processor->get_current_status() ? 'info' : 'success';
				$this->response['message']     = $this->get_notice_message( $processor );
				$payload                       = $processor->get_counter_payload();
				$this->response['data']        = $payload;
			}

			$this->response['hasToast'] = wp_ulike_setting_repo::hasToast( $settings->getType() );

			if ( ! empty( $this->response['data'] ) && ! WP_Ulike_Pro_Engagement_Settings::show_counters( $this->data['type'] ) ) {
				$payload = $this->response['data'];
				if ( 'emoji' === $payload['kind'] ) {
					$this->response['data'] = array(
						'kind'   => 'emoji',
						'active' => isset( $payload['active'] ) ? $payload['active'] : '',
					);
				} elseif ( 'star' === $payload['kind'] ) {
					$this->response['data'] = array(
						'kind' => 'star',
						'user' => isset( $payload['user'] ) ? (int) $payload['user'] : 0,
					);
				}
			}

			wp_ulike_release_lock( $fp_lock, $lock_type, $this->data['id'] );

			$this->response( apply_filters( 'wp_ulike_engagement_ajax_respond', $this->response, $this->data, $processor ) );

		} catch ( Exception $e ) {
			$this->sendError(
				array(
					'message'     => $e->getMessage(),
					'messageType' => 'error',
					'hasToast'    => true,
				)
			);
		} finally {
			$this->release_engagement_context();
		}
	}

	/**
	 * Pop AJAX bootstrap context when present.
	 *
	 * @return void
	 */
	private function release_engagement_context() {
		if ( ! $this->engagement_context_bootstrapped ) {
			return;
		}

		WP_Ulike_Pro_Engagement_Settings::pop_context( $this->data['type'] );
		$this->engagement_context_bootstrapped = false;
	}

	/**
	 * Validate request.
	 *
	 * @return void
	 */
	private function validate() {
		if ( empty( $this->data['id'] ) || empty( $this->data['type'] ) || empty( $this->data['engagement_kind'] ) ) {
			throw new Exception( wp_ulike_setting_repo::getValidationNotice() );
		}

		if ( ! wp_ulike_blacklist_validator::isValid( array( $this->data['client_address'] ) ) ) {
			throw new Exception( wp_ulike_setting_repo::getValidationNotice() );
		}

		$template = ! empty( $this->data['engagement_template'] )
			? sanitize_key( (string) $this->data['engagement_template'] )
			: '';

		if (
			empty( $this->data['nonce'] )
			|| ! $template
			|| ! WP_Ulike_Pro_Engagement_Settings::is_engagement_template( $template )
			|| ! wp_verify_nonce(
				$this->data['nonce'],
				WP_Ulike_Pro_Engagement_Settings::get_vote_nonce_action( $this->data['type'], $this->data['id'], $template )
			)
		) {
			throw new Exception( wp_ulike_setting_repo::getValidationNotice() );
		}
	}

	/**
	 * Notice message for engagement action.
	 *
	 * @param WP_Ulike_Pro_Engagement_Process $processor Processor instance.
	 * @return string
	 */
	private function get_notice_message( WP_Ulike_Pro_Engagement_Process $processor ) {
		if ( 'removed' === $processor->get_current_status() ) {
			if ( 'star' === $this->data['engagement_kind'] ) {
				return esc_html__( 'Your rating was removed.', WP_ULIKE_PRO_DOMAIN );
			}

			return esc_html__( 'Your reaction was removed.', WP_ULIKE_PRO_DOMAIN );
		}

		if ( 'emoji' === $this->data['engagement_kind'] ) {
			$reaction = WP_Ulike_Pro_Engagement_Registry::get_reaction( $processor->get_engagement_key(), $this->data['type'] );
			$label    = $reaction ? wp_strip_all_tags( $reaction['label'] ) : $processor->get_engagement_key();

			return sprintf(
				/* translators: %s: reaction label */
				esc_html__( 'Thanks! You reacted with %s.', WP_ULIKE_PRO_DOMAIN ),
				esc_html( $label )
			);
		}

		if ( 'star' === $this->data['engagement_kind'] && $processor->get_value() ) {
			$value = (int) $processor->get_value();

			return esc_html(
				sprintf(
					/* translators: %d: star value */
					_n(
						'Thanks! You rated this %d star.',
						'Thanks! You rated this %d stars.',
						$value,
						WP_ULIKE_PRO_DOMAIN
					),
					$value
				)
			);
		}

		return esc_html__( 'Thanks for your feedback!', WP_ULIKE_PRO_DOMAIN );
	}

	/**
	 * Permission-denied message for failed engagement votes.
	 *
	 * @param WP_Ulike_Pro_Engagement_Process $processor Processor instance.
	 * @return string
	 */
	private function get_permission_message( WP_Ulike_Pro_Engagement_Process $processor ) {
		if ( $processor->allows_multi_vote_public() && 'do_not_log' === wp_ulike_setting_repo::getMethod( $this->data['type'] ) ) {
			$limit = (int) wp_ulike_setting_repo::getVoteLimitNumber( $this->data['type'] );

			if ( $limit > 0 ) {
				return sprintf(
					/* translators: %d: maximum votes allowed per visitor */
					esc_html__( 'You have reached the limit of %d reactions for this item.', WP_ULIKE_PRO_DOMAIN ),
					$limit
				);
			}

			return esc_html__( 'Your reaction could not be saved. Please refresh and try again.', WP_ULIKE_PRO_DOMAIN );
		}

		$notice = wp_ulike_setting_repo::getPermissionNotice();

		return wp_strip_all_tags( html_entity_decode( (string) $notice, ENT_QUOTES, 'UTF-8' ) );
	}
}

