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

// custom redirect
$redirect_to = $wp_ulike_form_args->redirect_to;
if( ! empty( $_GET['redirect_to'] ) ){
  $redirect_to = $_GET['redirect_to'];
}

?>

<div class="ulp-form ulp-form-center ulp-ajax-form ulp-login">
    <form id="ulp-login-<?php echo esc_attr( $wp_ulike_form_args->form_id ); ?>" method="post" action=""
        autocomplete="off">

        <?php wp_ulike_pro_print_notices(); ?>

        <div class="ulp-form-row ulp-flex-row ulp-flex-middle-xs">

            <?php do_action( 'wp_ulike_pro_forms_before_hook', 'login', $wp_ulike_form_args ); ?>

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

            <div class="ulp-flex-col-xl-12 ulp-flex-col-md-12 ulp-flex-col-xs-12">
                <div class="ulp-floating">
                    <input id="ulp-password" type="password" class="ulp-floating-input" name="password" type="text"
                        placeholder="<?php echo esc_attr( $wp_ulike_form_args->password ); ?>" spellcheck="false" required autocomplete="current-password"/>
                    <label for="ulp-password" class="ulp-floating-label"
                        data-content="<?php echo esc_attr( $wp_ulike_form_args->password ); ?>">
                        <span
                            class="ulp-hidden-visually"><?php echo esc_html( $wp_ulike_form_args->password ); ?></span>
                    </label>
                </div>
            </div>

            <div class="ulp-flex-col-xl-6 ulp-flex-col-md-6 ulp-flex-col-xs-12">
                <div class="ulp-flex ulp-flex-start-md ulp-flex-center-xs">
                    <input id="ulp-remember" name="remember" type="checkbox" value="forever">
                    <label for="ulp-remember">
                        <span><?php echo esc_html( $wp_ulike_form_args->remember ); ?></span>
                    </label>
                </div>
            </div>

            <div class="ulp-flex-col-xl-6 ulp-flex-col-md-6 ulp-flex-col-xs-12">
                <div class="ulp-flex ulp-flex-end-md ulp-flex-center-xs">
                    <a
                        <?php echo wp_ulike_is_true( $wp_ulike_form_args->ajax_toggle ) ? 'data-form-toggle="reset-password"' : ''; ?> href="<?php echo esc_url( $wp_ulike_form_args->reset_url ); ?>"><?php echo esc_html( $wp_ulike_form_args->reset_password ); ?></a>
                </div>
            </div>

            <?php do_action( 'wp_ulike_pro_forms_before_submit', 'login', $wp_ulike_form_args ); ?>

            <div class="ulp-submit-field ulp-flex-col-xl-12 ulp-flex-col-md-12 ulp-flex-col-xs-12">
                <div class="ulp-flex ulp-flex-center-xs">
                    <input class="ulp-button" type="submit"
                        value="<?php echo esc_attr( $wp_ulike_form_args->button ); ?>" name="submit" />
                </div>
            </div>

            <?php do_action( 'wp_ulike_pro_forms_after_hook', 'login', $wp_ulike_form_args ); ?>

            <?php wp_nonce_field( 'wp-ulike-pro-forms-nonce', 'security' ); ?>
            <input type="hidden" name="action" value="ulp_login" />
            <input type="hidden" name="_form_id" value="<?php echo esc_attr( $wp_ulike_form_args->form_id ); ?>" />
            <input type="hidden" name="_redirect_to" value="<?php echo esc_url( $redirect_to ); ?>" />

        </div>
    </form>
</div>