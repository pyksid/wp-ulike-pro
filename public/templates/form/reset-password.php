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

$action = isset( $_REQUEST['action'] ) ? $_REQUEST['action'] : 'lostpassword';

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
        autocomplete="off">

        <?php wp_ulike_pro_print_notices(); ?>

        <div class="ulp-form-row ulp-flex-row ulp-flex-middle-xs">

            <?php do_action( 'wp_ulike_pro_forms_before_hook', 'reset-password', $wp_ulike_form_args ); ?>

            <div
                class="ulp-flex-col-xl-12 ulp-message <?php echo esc_attr( $msg_class ); ?> ulp-flex-col-md-12 ulp-flex-col-xs-12">
                <div class="ulp-flex">
                    <span><?php echo $msg_text; ?></span>
                </div>
            </div>

            <?php if( $action === 'changepassword' ) : ?>

            <div class="ulp-flex-col-xl-12 ulp-flex-col-md-12 ulp-flex-col-xs-12">
                <div class="ulp-floating">
                    <input id="ulp-new-password" type="password" class="ulp-floating-input" name="newpassword"
                        type="text" placeholder="<?php echo esc_attr( $wp_ulike_form_args->new_pass ); ?>" spellcheck="false" required autocomplete="new-password" />
                    <label for="ulp-new-password" class="ulp-floating-label"
                        data-content="<?php echo esc_attr( $wp_ulike_form_args->new_pass ); ?>">
                        <span
                            class="ulp-hidden-visually"><?php echo esc_html( $wp_ulike_form_args->new_pass ); ?></span>
                    </label>
                </div>
            </div>

            <div class="ulp-flex-col-xl-12 ulp-flex-col-md-12 ulp-flex-col-xs-12">
                <div class="ulp-floating">
                    <input id="ulp-re-password" type="password" class="ulp-floating-input" name="repassword" type="text"
                        placeholder="<?php echo esc_attr( $wp_ulike_form_args->re_new_pass ); ?>" spellcheck="false" required autocomplete="new-password" />
                    <label for="ulp-re-password" class="ulp-floating-label"
                        data-content="<?php echo esc_attr( $wp_ulike_form_args->re_new_pass ); ?>">
                        <span
                            class="ulp-hidden-visually"><?php echo esc_html( $wp_ulike_form_args->re_new_pass ); ?></span>
                    </label>
                </div>
            </div>

            <?php
            // Check if reset pass was activated
            $rp_cookie  = 'wp-resetpass-' . COOKIEHASH;
            if ( isset( $_COOKIE[$rp_cookie] ) && 0 < strpos( $_COOKIE[$rp_cookie], ':' ) ) {
                list( $rp_login, $rp_key ) = explode( ':', wp_unslash( $_COOKIE[ $rp_cookie ] ), 2 );
            ?>
            <input type="hidden" name="rp_key" value="<?php echo esc_attr( $rp_key ); ?>" />
            <?php } ?>

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