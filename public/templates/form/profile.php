<?php
/**
 * Profile form template
 */
if ( ! defined( 'ABSPATH' ) ) exit;

global $wp_ulike_form_args;
?>

<div class="ulp-form ulp-form-center ulp-ajax-form ulp-profile">
    <form id="ulp-profile-<?php echo esc_attr( $wp_ulike_form_args->form_id ); ?>" method="post" action="">

        <?php wp_ulike_pro_print_notices(); ?>

        <div class="ulp-form-row ulp-flex-row ulp-flex-middle-xs">

            <?php do_action( 'wp_ulike_pro_forms_before_hook', 'profile', $wp_ulike_form_args ); ?>

            <div class="ulp-flex-col-xl-6 ulp-flex-col-md-6 ulp-flex-col-xs-12">
                <div class="ulp-floating">
                    <input id="ulp-firstname" value="<?php echo esc_attr( $wp_ulike_form_args->user->first_name ); ?>"
                        class="ulp-floating-input" name="firstname" type="text"
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
                    <input id="ulp-lastname" value="<?php echo esc_attr( $wp_ulike_form_args->user->last_name ); ?>"
                        class="ulp-floating-input" name="lastname" type="text"
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
                    <input id="ulp-email" value="<?php echo esc_attr( $wp_ulike_form_args->user->user_email ); ?>"
                        type="email" class="ulp-floating-input" name="email" type="text"
                        placeholder="<?php echo esc_attr( $wp_ulike_form_args->email ); ?>" required />
                    <label for="ulp-email" class="ulp-floating-label"
                        data-content="<?php echo esc_attr( $wp_ulike_form_args->email ); ?>">
                        <span class="ulp-hidden-visually"><?php echo esc_html( $wp_ulike_form_args->email ); ?></span>
                    </label>
                </div>
            </div>

            <div class="ulp-flex-col-xl-12 ulp-flex-col-md-12 ulp-flex-col-xs-12">
                <div class="ulp-floating">
                    <input id="ulp-website" value="<?php echo esc_attr( $wp_ulike_form_args->user->user_url ); ?>"
                        type="url" class="ulp-floating-input" name="website" type="text"
                        placeholder="<?php echo esc_attr( $wp_ulike_form_args->website ); ?>" />
                    <label for="ulp-website" class="ulp-floating-label"
                        data-content="<?php echo esc_attr( $wp_ulike_form_args->website ); ?>">
                        <span class="ulp-hidden-visually"><?php echo esc_html( $wp_ulike_form_args->website ); ?></span>
                    </label>
                </div>
            </div>

            <div class="ulp-flex-col-xl-12 ulp-flex-col-md-12 ulp-flex-col-xs-12">
                <div class="ulp-floating">
                    <textarea name="bio" id="ulp-description" rows="5" class="ulp-floating-input ulp-upper"
                        spellcheck="false"
                        placeholder="<?php echo esc_attr( $wp_ulike_form_args->description ); ?>"><?php echo esc_attr( $wp_ulike_form_args->user->description ); ?></textarea>
                    <label for="ulp-description" class="ulp-floating-label ulp-upper"
                        data-content="<?php echo esc_attr( $wp_ulike_form_args->description ); ?>">
                        <span
                            class="ulp-hidden-visually"><?php echo esc_html( $wp_ulike_form_args->description ); ?></span>
                    </label>
                </div>
            </div>

            <?php do_action( 'wp_ulike_pro_forms_before_submit', 'profile', $wp_ulike_form_args ); ?>

            <div class="ulp-submit-field ulp-flex-col-xl-12 ulp-flex-col-md-12 ulp-flex-col-xs-12">
                <div class="ulp-flex ulp-flex-center-xs">
                    <input class="ulp-button" type="submit"
                        value="<?php echo esc_attr( $wp_ulike_form_args->button ); ?>" name="submit" />
                </div>
            </div>

            <?php do_action( 'wp_ulike_pro_forms_after_hook', 'profile', $wp_ulike_form_args ); ?>

            <?php wp_nonce_field( 'wp-ulike-pro-forms-nonce', 'security' ); ?>
            <input type="hidden" name="action" value="ulp_profile" />
            <input type="hidden" name="_form_id" value="<?php echo esc_attr( $wp_ulike_form_args->form_id ); ?>" />

        </div>
    </form>
</div>