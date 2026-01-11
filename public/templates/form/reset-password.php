<?php
/**
 * Login form template
 */
if ( ! defined( 'ABSPATH' ) ) exit;

global $wp_ulike_form_args;

if( is_user_logged_in() && ! WP_Ulike_Pro::is_preview_mode() ){
  // Display message
  echo WP_Ulike_Pro_Options::getLoggedInMessage();
  return;
}

// SECURITY: Sanitize action parameter
$action = isset( $_REQUEST['action'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ) : 'lostpassword';
// Whitelist allowed actions
$allowed_actions = array( 'lostpassword', 'changepassword' );
if ( ! in_array( $action, $allowed_actions, true ) ) {
	$action = 'lostpassword';
}

$btn_label = $wp_ulike_form_args->reset_button;
$msg_text  = $wp_ulike_form_args->reset_message;
$msg_class = 'ulp-info-message';

if ( $action == 'changepassword' ) {
	$msg_text  = $wp_ulike_form_args->change_message;
	$btn_label = $wp_ulike_form_args->reset_button;
}

?>
<div class="ulp-form ulp-form-center ulp-ajax-form ulp-reset-password">
    <form id="ulp-reset-password-<?php echo esc_attr( $wp_ulike_form_args->form_id ); ?>" method="post" action=""
        autocomplete="off" aria-label="<?php esc_attr_e( 'Password reset form', 'wp-ulike-pro' ); ?>">

        <?php wp_ulike_pro_print_notices(); ?>

        <div class="ulp-form-row ulp-flex-row ulp-flex-middle-xs">

            <?php do_action( 'wp_ulike_pro_forms_before_hook', 'reset-password', $wp_ulike_form_args ); ?>

            <div
                class="ulp-flex-col-xl-12 ulp-message <?php echo esc_attr( $msg_class ); ?> ulp-flex-col-md-12 ulp-flex-col-xs-12">
                <div class="ulp-flex">
                    <span><?php echo wp_kses_post( $msg_text ); ?></span>
                </div>
            </div>

            <?php if( $action === 'changepassword' ) :
                // Check if reset pass was activated and extract username for accessibility
                $rp_cookie  = 'wp-resetpass-' . COOKIEHASH;
                $rp_login = '';
                $rp_key = '';
                // SECURITY: Sanitize cookie value
                if ( isset( $_COOKIE[$rp_cookie] ) && 0 < strpos( $_COOKIE[$rp_cookie], ':' ) ) {
                    $cookie_value = sanitize_text_field( wp_unslash( $_COOKIE[ $rp_cookie ] ) );
                    list( $rp_login, $rp_key ) = explode( ':', $cookie_value, 2 );
                    // SECURITY: Validate key format
                    if ( ! empty( $rp_key ) && preg_match( '/^[a-zA-Z0-9]+$/', $rp_key ) ) {
                        // Add hidden rp_key field
            ?>
            <input type="hidden" name="rp_key" value="<?php echo esc_attr( $rp_key ); ?>" />
            <?php
                    }
                }
                // Add visually hidden username field BEFORE password fields for accessibility
                if ( ! empty( $rp_login ) ) {
            ?>
            <input type="text" name="username" value="<?php echo esc_attr( $rp_login ); ?>" autocomplete="username" style="position: absolute; left: -9999px; width: 1px; height: 1px; opacity: 0; pointer-events: none;" aria-hidden="true" tabindex="-1" />
            <?php
                }
            ?>

            <div class="ulp-flex-col-xl-12 ulp-flex-col-md-12 ulp-flex-col-xs-12">
                <div class="ulp-floating ulp-password-wrapper">
                    <input id="ulp-new-password" type="password" class="ulp-floating-input" name="newpassword"
                        placeholder="<?php echo esc_attr( $wp_ulike_form_args->new_pass ); ?>" spellcheck="false" required autocomplete="new-password"
                        aria-describedby="ulp-new-password-strength-description" />
                    <label for="ulp-new-password" class="ulp-floating-label"
                        data-content="<?php echo esc_attr( $wp_ulike_form_args->new_pass ); ?>">
                        <span
                            class="ulp-hidden-visually"><?php echo esc_html( $wp_ulike_form_args->new_pass ); ?></span>
                    </label>
                    <button type="button" class="ulp-password-toggle" aria-label="<?php esc_attr_e( 'Show password', 'wp-ulike-pro' ); ?>" aria-pressed="false" tabindex="-1">
                        <span class="ulp-password-toggle-icon" aria-hidden="true"></span>
                        <span class="ulp-hidden-visually"><?php esc_html_e( 'Show password', 'wp-ulike-pro' ); ?></span>
                    </button>
                    <span id="ulp-new-password-strength-description" class="ulp-hidden-visually"><?php esc_html_e( 'Password strength indicator', 'wp-ulike-pro' ); ?></span>
                    <div class="ulp-password-requirements" role="status" aria-live="polite">
                        <div class="ulp-password-strength">
                            <div class="ulp-password-strength-bar">
                                <div class="ulp-password-strength-fill" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <span class="ulp-password-strength-text"></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="ulp-flex-col-xl-12 ulp-flex-col-md-12 ulp-flex-col-xs-12">
                <div class="ulp-floating ulp-password-wrapper">
                    <input id="ulp-re-password" type="password" class="ulp-floating-input" name="repassword"
                        placeholder="<?php echo esc_attr( $wp_ulike_form_args->re_new_pass ); ?>" spellcheck="false" required autocomplete="new-password" />
                    <label for="ulp-re-password" class="ulp-floating-label"
                        data-content="<?php echo esc_attr( $wp_ulike_form_args->re_new_pass ); ?>">
                        <span
                            class="ulp-hidden-visually"><?php echo esc_html( $wp_ulike_form_args->re_new_pass ); ?></span>
                    </label>
                    <button type="button" class="ulp-password-toggle" aria-label="<?php esc_attr_e( 'Show password', 'wp-ulike-pro' ); ?>" aria-pressed="false" tabindex="-1">
                        <span class="ulp-password-toggle-icon" aria-hidden="true"></span>
                        <span class="ulp-hidden-visually"><?php esc_html_e( 'Show password', 'wp-ulike-pro' ); ?></span>
                    </button>
                </div>
            </div>

            <?php else: ?>

            <div class="ulp-flex-col-xl-12 ulp-flex-col-md-12 ulp-flex-col-xs-12">
                <div class="ulp-floating">
                    <input id="ulp-username" class="ulp-floating-input" name="username" type="text"
                        placeholder="<?php echo esc_attr( $wp_ulike_form_args->username ); ?>" autocapitalize="off" autocomplete="username" required />
                    <label for="ulp-username" class="ulp-floating-label"
                        data-content="<?php echo esc_attr( $wp_ulike_form_args->username ); ?>">
                        <span
                            class="ulp-hidden-visually"><?php echo esc_html( $wp_ulike_form_args->username ); ?></span>
                    </label>
                </div>
            </div>

            <?php endif; ?>


            <?php do_action( 'wp_ulike_pro_forms_before_submit', 'reset-password', $wp_ulike_form_args ); ?>

            <div class="ulp-submit-field ulp-flex-col-xl-12 ulp-flex-col-md-12 ulp-flex-col-xs-12">
                <div class="ulp-flex ulp-flex-center-xs">
                    <input class="ulp-button" type="submit" value="<?php echo esc_attr( $btn_label ); ?>"
                        name="submit" />
                </div>
            </div>

            <?php do_action( 'wp_ulike_pro_forms_after_hook', 'reset-password', $wp_ulike_form_args ); ?>

            <?php wp_nonce_field( 'wp-ulike-pro-forms-nonce', 'security' ); ?>
            <input type="hidden" name="action" value="ulp_reset_password" />
            <input type="hidden" name="_form_id" value="<?php echo esc_attr( $wp_ulike_form_args->form_id ); ?>" />

        </div>
    </form>
</div>