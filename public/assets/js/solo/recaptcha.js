/**
 * reCAPTCHA handler for WP Ulike Pro
 * Handles both reCAPTCHA v2 and v3 with modern ES6 standards
 */
(function () {
  'use strict';

  // Check if reCAPTCHA data is available
  if (
    typeof UlikeProRecaptchaData === 'undefined' ||
    typeof UlikeProRecaptchaData.recaptchaVersion === 'undefined'
  ) {
    return;
  }

  const { recaptchaVersion, recaptchaSiteKey } = UlikeProRecaptchaData;

  /**
   * Execute reCAPTCHA v3 for a specific element
   * @param {HTMLElement} element - The form container element
   */
  const ulpRecaptchaV3Execute = (element) => {
    if (typeof grecaptcha === 'undefined' || recaptchaVersion !== 'v3') {
      return;
    }

    const form =
      element.tagName === 'FORM' ? element : element.querySelector('form');

    if (!form) {
      return;
    }

    const recaptchaElement = form.querySelector('.ulp-google-recaptch');
    const action =
      recaptchaElement?.dataset?.mode ||
      recaptchaElement?.getAttribute('data-mode') ||
      'homepage';

    grecaptcha
      .execute(recaptchaSiteKey, { action })
      .then((token) => {
        const tokenInput = form.querySelector('[name="g-recaptcha-response"]');

        if (tokenInput) {
          tokenInput.value = token;
        } else {
          const hiddenInput = document.createElement('input');
          hiddenInput.type = 'hidden';
          hiddenInput.name = 'g-recaptcha-response';
          hiddenInput.value = token;
          form.appendChild(hiddenInput);
        }
      })
      .catch((error) => {
        console.error('reCAPTCHA v3 execution failed:', error);
      });
  };

  // Handle custom event: UlpAjaxFormEnded
  // event.detail is an array: [element, response]
  document.addEventListener('UlpAjaxFormEnded', (event) => {
    const [element, response] = Array.isArray(event.detail) ? event.detail : [];

    if (!element || !response) {
      return;
    }

    // Refresh reCAPTCHA on errors or when explicitly requested (e.g., 2FA needs fresh token)
    const shouldRefresh =
      (response.success !== undefined && !response.success) ||
      response.data?.refresh_recaptcha === true;

    if (!shouldRefresh) {
      return;
    }

    if (recaptchaVersion === 'v2' && typeof ulp_recaptcha_refresh !== 'undefined') {
      ulp_recaptcha_refresh();
    } else if (recaptchaVersion === 'v3') {
      ulpRecaptchaV3Execute(element);
    }
  });

  // Handle custom event: UlpRecaptchaReload
  // event.detail is the element directly
  document.addEventListener('UlpRecaptchaReload', (event) => {
    const element = event.detail;

    if (recaptchaVersion === 'v2' && typeof ulpOnloadCallback !== 'undefined') {
      ulpOnloadCallback();
    } else if (recaptchaVersion === 'v3' && element) {
      ulpRecaptchaV3Execute(element);
    }
  });

  // Initialize reCAPTCHA v3 for all forms on page load
  if (typeof grecaptcha !== 'undefined' && recaptchaVersion === 'v3') {
    const initializeRecaptcha = () => {
      grecaptcha.ready(() => {
        const forms = document.querySelectorAll('.ulp-ajax-form');
        forms.forEach((form) => {
          ulpRecaptchaV3Execute(form);
        });
      });
    };

    // Try to initialize immediately if DOM is ready
    if (
      document.readyState === 'loading' ||
      document.readyState === 'interactive'
    ) {
      document.addEventListener('DOMContentLoaded', initializeRecaptcha);
    } else {
      // DOM is already ready
      initializeRecaptcha();
    }
  }
})();
