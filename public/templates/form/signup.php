<?php
/**
 * Signup form template
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
  // SECURITY: Validate redirect URL to prevent open redirect attacks
  $redirect_to_raw = wp_unslash( $_GET['redirect_to'] );
  $redirect_to = wp_validate_redirect( $redirect_to_raw, home_url() );
  if ( ! $redirect_to ) {
    $redirect_to = $wp_ulike_form_args->redirect_to; // Fallback to default
  }
}

?>

<div class="ulp-form ulp-form-center ulp-ajax-form ulp-signup">
    <form id="ulp-signup-<?php echo esc_attr( $wp_ulike_form_args->form_id ); ?>" method="post" action=""
        autocomplete="off" aria-label="<?php esc_attr_e( 'Signup form', 'wp-ulike-pro' ); ?>">

        <?php wp_ulike_pro_print_notices(); ?>

        <div class="ulp-form-row ulp-flex-row ulp-flex-middle-xs">

            <?php do_action( 'wp_ulike_pro_forms_before_hook', 'signup', $wp_ulike_form_args ); ?>

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

            <div class="ulp-flex-col-xl-6 ulp-flex-col-md-6 ulp-flex-col-xs-12">
                <div class="ulp-floating">
                    <input id="ulp-firstname" class="ulp-floating-input" name="firstname" type="text"
                        placeholder="<?php echo esc_attr( $wp_ulike_form_args->firstname ); ?>" />
                    <label for="ulp-firstname" class="ulp-floating-label"
                        data-content="<?php echo esc_attr( $wp_ulike_form_args->firstname ); ?>">
                        <span
                            class="ulp-hidden-visually"><?php echo esc_html( $wp_ulike_form_args->firstname ); ?></span>
                    </label>
                </div>
            </div>
            <div class="ulp-flex-col-xl-6 ulp-flex-col-md-6 ulp-flex-col-xs-12">
                <div class="ulp-floating">
                    <input id="ulp-lastname" class="ulp-floating-input" name="lastname" type="text"
                        placeholder="<?php echo esc_attr( $wp_ulike_form_args->lastname ); ?>" />
                    <label for="ulp-lastname" class="ulp-floating-label"
                        data-content="<?php echo esc_attr( $wp_ulike_form_args->lastname ); ?>">
                        <span
                            class="ulp-hidden-visually"><?php echo esc_html( $wp_ulike_form_args->lastname ); ?></span>
                    </label>
                </div>
            </div>

            <div class="ulp-flex-col-xl-12 ulp-flex-col-md-12 ulp-flex-col-xs-12">
                <div class="ulp-floating">
                    <input id="ulp-email" type="email" class="ulp-floating-input" name="email"
                        placeholder="<?php echo esc_attr( $wp_ulike_form_args->email ); ?>" required autocomplete="email" />
                    <label for="ulp-email" class="ulp-floating-label"
                        data-content="<?php echo esc_attr( $wp_ulike_form_args->email ); ?>">
                        <span class="ulp-hidden-visually"><?php echo esc_html( $wp_ulike_form_args->email ); ?></span>
                    </label>
                </div>
            </div>

            <div class="ulp-flex-col-xl-12 ulp-flex-col-md-12 ulp-flex-col-xs-12">
                <div class="ulp-floating ulp-password-wrapper">
                    <input id="ulp-password" type="password" class="ulp-floating-input" name="password"
                        placeholder="<?php echo esc_attr( $wp_ulike_form_args->password ); ?>" spellcheck="false" required autocomplete="new-password"
                        aria-describedby="ulp-password-strength-description ulp-password-requirements" />
                    <label for="ulp-password" class="ulp-floating-label"
                        data-content="<?php echo esc_attr( $wp_ulike_form_args->password ); ?>">
                        <span
                            class="ulp-hidden-visually"><?php echo esc_html( $wp_ulike_form_args->password ); ?></span>
                    </label>
                    <button type="button" class="ulp-password-toggle" aria-label="<?php esc_attr_e( 'Show password', 'wp-ulike-pro' ); ?>" aria-pressed="false" tabindex="-1">
                        <span class="ulp-password-toggle-icon" aria-hidden="true"></span>
                        <span class="ulp-hidden-visually"><?php esc_html_e( 'Show password', 'wp-ulike-pro' ); ?></span>
                    </button>
                    <span id="ulp-password-strength-description" class="ulp-hidden-visually"><?php esc_html_e( 'Password strength indicator', 'wp-ulike-pro' ); ?></span>
                    <div id="ulp-password-requirements" class="ulp-password-requirements" role="status" aria-live="polite">
                        <div class="ulp-password-strength">
                            <div class="ulp-password-strength-bar">
                                <div class="ulp-password-strength-fill" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <span class="ulp-password-strength-text"></span>
                        </div>
                    </div>
                </div>
            </div>

            <?php do_action( 'wp_ulike_pro_forms_before_submit', 'signup', $wp_ulike_form_args ); ?>

            <div class="ulp-submit-field ulp-flex-col-xl-12 ulp-flex-col-md-12 ulp-flex-col-xs-12">
                <div class="ulp-flex ulp-flex-center-xs">
                    <input class="ulp-button" type="submit"
                        value="<?php echo esc_attr( $wp_ulike_form_args->button ); ?>" name="submit" />
                </div>
            </div>

            <?php do_action( 'wp_ulike_pro_forms_after_hook', 'signup', $wp_ulike_form_args ); ?>

            <?php wp_nonce_field( 'wp-ulike-pro-forms-nonce', 'security', $wp_ulike_form_args ); ?>
            <input type="hidden" name="action" value="ulp_signup" />
            <input type="hidden" name="_form_id" value="<?php echo esc_attr( $wp_ulike_form_args->form_id ); ?>" />
            <input type="hidden" name="_redirect_to" value="<?php echo esc_url( $redirect_to ); ?>" />

        </div>
    </form>
</div>