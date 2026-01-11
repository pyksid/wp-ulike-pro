

/* ================== public/assets/js/src/_forms.js =================== */


(function (window, document, undefined) {
  "use strict";

  // Create the defaults once
  const pluginName = "WordpressUlikeAjaxForms";
  const defaults = {};

  // Helper function to trigger custom events (vanilla JS only)
  const triggerEvent = (element, eventName, data) => {
    // Create CustomEvent for vanilla JS listeners
    const event = new CustomEvent(eventName, {
      bubbles: true,
      cancelable: true,
      detail: data
    });

    // Dispatch the event
    element.dispatchEvent(event);
  };

  // Store plugin instances to prevent multiple instantiations
  const pluginInstances = new WeakMap();

  // Helper function to serialize form data (like jQuery's serializeArray)
  // jQuery's serializeArray() only includes: checked checkboxes, selected radios, 
  // selected options, and all text inputs/selects/textareas with values
  const serializeForm = (form) => {
    const values = {};
    const formData = new FormData(form);

    // Process all form fields (FormData only includes checked checkboxes, selected radios, etc.)
    for (const [name, value] of formData.entries()) {
      if (values[name] !== undefined) {
        // Convert to array if multiple values
        if (!Array.isArray(values[name])) {
          values[name] = [values[name]];
        }
        values[name].push(value || "");
      } else {
        values[name] = value || "";
      }
    }

    return values;
  };

  // Helper function to apply fragments (DOM updates from response)
  const applyFragments = (fragments) => {
    if (!fragments || typeof fragments !== "object") return;

    Object.keys(fragments).forEach((selector) => {
      const fragment = fragments[selector];
      if (!fragment || typeof fragment !== "object") return;

      const elements = document.querySelectorAll(selector);
      elements.forEach((element) => {
        const method = fragment.method || "append";
        const content = fragment.content || "";

        switch (method) {
          case "prepend":
            if (content) {
              const tempDiv = document.createElement("div");
              tempDiv.innerHTML = content;
              // Insert all children in reverse order to maintain original order
              const children = Array.from(tempDiv.children);
              children.reverse().forEach((child) => {
                element.insertBefore(child, element.firstChild);
              });
            }
            break;

          case "hidden":
            element.classList.add("ulp-hidden-visually");
            break;

          case "append":
          default:
            if (content) {
              const tempDiv = document.createElement("div");
              tempDiv.innerHTML = content;
              while (tempDiv.firstChild) {
                element.appendChild(tempDiv.firstChild);
              }
            }
            break;
        }
      });
    });
  };

  // The actual plugin constructor
  function Plugin(element, options) {
    if (!element) {
      console.warn("WordpressUlikeAjaxForms: element is required");
      return;
    }

    // Check if already instantiated
    if (pluginInstances.has(element)) {
      return pluginInstances.get(element);
    }

    this.element = element;
    this.settings = Object.assign({}, defaults, options);
    this._defaults = defaults;
    this._name = pluginName;

    // Find form element
    this.form = this.element.querySelector("form");
    if (!this.form) {
      console.warn("WordpressUlikeAjaxForms: no form element found");
      return;
    }

    // Store instance
    pluginInstances.set(element, this);

    this.init();
  }

  // Password strength calculator
  const calculatePasswordStrength = (password) => {
    let strength = 0;
    const checks = {
      length: password.length >= 8,
      lowercase: /[a-z]/.test(password),
      uppercase: /[A-Z]/.test(password),
      numbers: /[0-9]/.test(password),
      special: /[^A-Za-z0-9]/.test(password),
    };

    Object.values(checks).forEach(check => {
      if (check) strength += 20;
    });

    // Bonus for longer passwords
    if (password.length >= 12) strength += 10;
    if (password.length >= 16) strength += 10;

    return Math.min(100, strength);
  };

  // Get password strength class and value (text hidden via CSS for better UX)
  const getPasswordStrengthInfo = (strength) => {
    if (strength === 0) return { class: '', value: 0 };
    if (strength < 30) return { class: 'ulp-strength-very-weak', value: strength };
    if (strength < 50) return { class: 'ulp-strength-weak', value: strength };
    if (strength < 70) return { class: 'ulp-strength-fair', value: strength };
    if (strength < 90) return { class: 'ulp-strength-good', value: strength };
    return { class: 'ulp-strength-strong', value: strength };
  };

  // Debounce function for performance
  const debounce = (func, wait) => {
    let timeout;
    return function executedFunction(...args) {
      const later = () => {
        clearTimeout(timeout);
        func(...args);
      };
      clearTimeout(timeout);
      timeout = setTimeout(later, wait);
    };
  };

  // Validate email format
  const validateEmail = (email) => {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
  };

  // Plugin prototype methods
  Plugin.prototype = {
    init() {
      // Call submit handler on form submit
      if (this.form) {
        this.form.addEventListener("submit", this._submit.bind(this));
      }

      // Initialize password visibility toggles
      this._initPasswordToggles();

      // Initialize password strength indicators
      this._initPasswordStrength();

      // Initialize real-time validation
      this._initValidation();
    },

    /**
     * Initialize password visibility toggles
     */
    _initPasswordToggles() {
      const passwordToggles = this.element.querySelectorAll(".ulp-password-toggle");
      
      passwordToggles.forEach((toggle) => {
        const passwordInput = toggle.closest(".ulp-password-wrapper")?.querySelector('input[type="password"], input[type="text"]');
        if (!passwordInput) return;

        const toggleIcon = toggle.querySelector(".ulp-password-toggle-icon");
        if (!toggleIcon) return;

        // Set initial state
        toggleIcon.classList.add("ulp-password-hidden");

        // Click handler
        const handleToggle = (e) => {
          e.preventDefault();
          e.stopPropagation();
          
          const isPassword = passwordInput.type === "password";
          passwordInput.type = isPassword ? "text" : "password";
          toggle.setAttribute("aria-pressed", isPassword ? "true" : "false");
          // Update aria-label using data attributes (translated in templates)
          const label = isPassword 
            ? toggle.getAttribute("data-label-hide") || toggle.getAttribute("aria-label") || ""
            : toggle.getAttribute("data-label-show") || toggle.getAttribute("aria-label") || "";
          if (label) {
            toggle.setAttribute("aria-label", label);
          }
          toggleIcon.classList.toggle("ulp-password-visible", isPassword);
          toggleIcon.classList.toggle("ulp-password-hidden", !isPassword);
        };

        toggle.addEventListener("click", handleToggle, { passive: false });
        
        // Keyboard handler for accessibility (WCAG 2.1 Level AA compliance)
        toggle.addEventListener("keydown", (e) => {
          if (e.key === "Enter" || e.key === " ") {
            e.preventDefault();
            handleToggle(e);
          }
        }, { passive: false });
      });
    },

    /**
     * Initialize password strength indicators
     */
    _initPasswordStrength() {
      const passwordInputs = this.element.querySelectorAll('input[type="password"][name="password"], input[type="password"][name="newpassword"]');
      
      passwordInputs.forEach((passwordInput) => {
        const wrapper = passwordInput.closest(".ulp-password-wrapper");
        if (!wrapper) return;

        const strengthBar = wrapper.querySelector(".ulp-password-strength-fill");
        const requirements = wrapper.querySelector(".ulp-password-requirements");

        if (!strengthBar) return;

        const updateStrength = debounce(() => {
          const password = passwordInput.value;
          
          if (!password) {
            strengthBar.style.width = "0%";
            strengthBar.setAttribute("aria-valuenow", "0");
            strengthBar.className = "ulp-password-strength-fill";
            requirements?.classList.remove("ulp-visible");
            return;
          }

          const strength = calculatePasswordStrength(password);
          const info = getPasswordStrengthInfo(strength);

          // Use requestAnimationFrame for smooth animations (modern performance pattern)
          requestAnimationFrame(() => {
            strengthBar.style.width = `${info.value}%`;
            strengthBar.setAttribute("aria-valuenow", info.value);
            strengthBar.className = `ulp-password-strength-fill ${info.class}`;
            requirements?.classList.add("ulp-visible");
          });
        }, 300);

        passwordInput.addEventListener("input", updateStrength, { passive: true });
        passwordInput.addEventListener("blur", updateStrength);
      });
    },

    /**
     * Initialize real-time validation - Visual feedback only (no text messages)
     */
    _initValidation() {
      // Email validation - visual feedback via border color only
      const emailInputs = this.element.querySelectorAll('input[type="email"]');
      emailInputs.forEach((input) => {
        const validateEmailField = debounce(() => {
          const email = input.value.trim();
          if (email && !validateEmail(email)) {
            input.classList.add("ulp-input-error");
            input.classList.remove("ulp-input-success");
          } else if (email && validateEmail(email)) {
            input.classList.remove("ulp-input-error");
            input.classList.add("ulp-input-success");
          } else {
            input.classList.remove("ulp-input-error", "ulp-input-success");
          }
        }, 300);

        input.addEventListener("blur", validateEmailField);
        // Clear error immediately on input for better UX
        input.addEventListener("input", () => {
          if (input.classList.contains("ulp-input-error")) {
            input.classList.remove("ulp-input-error");
            if (input.value.trim()) {
              validateEmailField();
            } else {
              input.classList.remove("ulp-input-success");
            }
          }
        }, { passive: true });
      });

      // Password match validation - visual feedback via border color only
      const passwordInput = this.element.querySelector('input[name="newpassword"]');
      const confirmPasswordInput = this.element.querySelector('input[name="repassword"]');
      
      if (passwordInput && confirmPasswordInput) {
        const validatePasswordMatch = debounce(() => {
          const password = passwordInput.value;
          const confirmPassword = confirmPasswordInput.value;

          if (!password || !confirmPassword) {
            // Reset validation states when fields are empty
            confirmPasswordInput.classList.remove("ulp-input-error", "ulp-input-success");
            passwordInput.classList.remove("ulp-input-error", "ulp-input-success");
            return;
          }

          if (password === confirmPassword) {
            // Passwords match - show success state
            confirmPasswordInput.classList.remove("ulp-input-error");
            confirmPasswordInput.classList.add("ulp-input-success");
            passwordInput.classList.remove("ulp-input-error");
            passwordInput.classList.add("ulp-input-success");
          } else {
            // Passwords don't match - show error state
            confirmPasswordInput.classList.remove("ulp-input-success");
            confirmPasswordInput.classList.add("ulp-input-error");
            passwordInput.classList.remove("ulp-input-success");
            passwordInput.classList.add("ulp-input-error");
          }
        }, 300);

        passwordInput.addEventListener("input", validatePasswordMatch, { passive: true });
        confirmPasswordInput.addEventListener("input", validatePasswordMatch, { passive: true });
      }
    },

    /**
     * global AJAX callback
     */
    _ajax(args, callback) {
      // Convert args to FormData
      const formData = new FormData();
      for (const key in args) {
        if (args.hasOwnProperty(key)) {
          const value = args[key];
          if (Array.isArray(value)) {
            // Preserve original field name format: if key already ends with [],
            // use it as-is (e.g., "otp[]" stays "otp[]"), otherwise append []
            // This prevents double-bracketing: "otp[]" -> "otp[][]"
            const arrayKey = key.endsWith("[]") ? key : key + "[]";
            value.forEach((v) => {
              formData.append(arrayKey, v);
            });
          } else {
            formData.append(key, value);
          }
        }
      }

      fetch(UlikeProCommonConfig.AjaxUrl, {
        method: "POST",
        cache: "no-cache",
        body: formData,
      })
        .then((response) => response.json())
        .then(callback)
        .catch((error) => {
          console.error("WP ULike Pro Ajax Forms error:", error);
          // Re-enable button on error - querySelectorAll returns NodeList
          if (this.buttonElement && this.buttonElement.length > 0) {
            Array.from(this.buttonElement).forEach((btn) => {
              btn.disabled = false;
            });
          }
          if (this.currentForm) {
            this.currentForm.classList.remove("ulp-loading");
          }
        });
    },


    /**
     * init ulike core process
     */
    _submit(event) {
      event.preventDefault();
      event.stopPropagation();

      // Manipulations
      triggerEvent(document, "UlpAjaxFormStarted", [this.element]);

      this.currentForm = event.currentTarget;
      // querySelectorAll always returns NodeList (matching jQuery .find() behavior)
      this.buttonElement = this.currentForm.querySelectorAll(".ulp-button");

      // Serialize form data
      const values = serializeForm(this.currentForm);

      // Disable button(s) - querySelectorAll returns NodeList (works like jQuery .find())
      if (this.buttonElement && this.buttonElement.length > 0) {
        Array.from(this.buttonElement).forEach((btn) => {
          btn.disabled = true;
        });
      }
      // Add progress class
      this.currentForm.classList.add("ulp-loading");
      // submit form data
      this._submitFormData(values);
    },

    _submitFormData(args) {
      // Start AJAX process
      this._ajax(
        args,
        (response) => {
          // if has nested ajax levels
          if (
            typeof response.data.action !== "undefined" &&
            response.data.action
          ) {
            this._submitFormData(response.data);
            // Note: Old version continued processing after nested call
            // Keeping same behavior for compatibility
          }

          // Remove progress class
          if (this.currentForm) {
            this.currentForm.classList.remove("ulp-loading");
          }
          // Re-enable button(s) - querySelectorAll returns NodeList (works like jQuery .find())
          if (this.buttonElement && this.buttonElement.length > 0) {
            Array.from(this.buttonElement).forEach((btn) => {
              btn.disabled = false;
            });
          }

          if (
            typeof response.data.message !== "undefined" &&
            response.data.message
          ) {
            this._sendNotification(response.data.status, response.data.message);
          }

          // Handle fragments (DOM updates)
          if (
            typeof response.data.fragments !== "undefined" &&
            response.data.fragments
          ) {
            applyFragments(response.data.fragments);
            
            // Re-initialize password features in updated fragments
            if (this._initPasswordToggles) {
              this._initPasswordToggles();
            }
            if (this._initPasswordStrength) {
              this._initPasswordStrength();
            }
            if (this._initValidation) {
              this._initValidation();
            }
            
            // Initialize OTP inputs if fragments contain 2FA code inputs (with small delay)
            if (typeof window.ulpOtpInput === "function") {
              setTimeout(() => {
                window.ulpOtpInput();
              }, 50);
            }
          }

          // Handle form replacement
          if (
            typeof response.data.replace !== "undefined" &&
            response.data.replace
          ) {
            const tempDiv = document.createElement("div");
            tempDiv.innerHTML = response.data.replace;
            const newForm = tempDiv.firstElementChild;
            if (newForm && this.currentForm && this.currentForm.parentNode) {
              // Re-initialize plugin on new form element
              this.currentForm.parentNode.replaceChild(newForm, this.currentForm);
              // Find the container element
              const container = newForm.closest(".ulp-ajax-form");
              if (container) {
                // Remove old instance and create new one
                pluginInstances.delete(this.element);
                const newInstance = new Plugin(container);
                
                // Re-initialize password features
                if (newInstance._initPasswordToggles) {
                  newInstance._initPasswordToggles();
                }
                if (newInstance._initPasswordStrength) {
                  newInstance._initPasswordStrength();
                }
                if (newInstance._initValidation) {
                  newInstance._initValidation();
                }
                
                // Initialize OTP inputs if present (with small delay to ensure DOM is ready)
                if (typeof window.ulpOtpInput === "function") {
                  setTimeout(() => {
                    window.ulpOtpInput(container);
                  }, 50);
                }
              }
              return; // Exit early after replacement
            }
          }

          // Add new trigger when process finished
          triggerEvent(document, "UlpAjaxFormEnded", [this.element, response]);

          if (
            typeof response.data.refresh !== "undefined" &&
            response.data.refresh
          ) {
            location.reload();
          }

          if (
            typeof response.data.redirect !== "undefined" &&
            response.data.redirect
          ) {
            window.location.replace(response.data.redirect);
          }
        }
      );
    },

    /**
     * Send notification by 'WordpressUlikeNotifications' plugin
     */
    _sendNotification(messageType, messageText) {
      // Display Notification (vanilla JS only)
      if (typeof WordpressUlikeNotifications !== "undefined") {
        new WordpressUlikeNotifications(document.body, {
          messageType,
          messageText,
        });
      }
    },
  };

  // Expose plugin to window for global access
  window[pluginName] = Plugin;
})(window, document);


/* ================== public/assets/js/src/_modal.js =================== */


/**
 * Lightweight Vanilla JavaScript Modal
 * ~180 lines - maintains HTML structure and CSS classes
 */
(function() {
	'use strict';

	let currentModal = null; // Track current modal to prevent multiple
	const defaults = {
		namespace: 'ulpmodal',
		closeOnClick: 'background',
		closeOnEsc: true,
		closeIcon: '&#10005;',
		openSpeed: 250,
		closeSpeed: 250,
		afterOpen: null
	};

	// Create modal HTML structure
	const createModal = (config) => {
		const ns = config.namespace || defaults.namespace;
		const modal = document.createElement('div');
		modal.className = `${ns}-loading ${ns}`;
		modal.setAttribute('role', 'dialog');
		modal.setAttribute('aria-modal', 'true');
		modal.setAttribute('aria-labelledby', `${ns}-title`);
		modal.setAttribute('tabindex', '-1');
		modal.innerHTML = `
			<div class="${ns}-content">
				<button class="${ns}-close-icon ${ns}-close" aria-label="Close" type="button">${config.closeIcon || defaults.closeIcon}</button>
				<div class="${ns}-inner" role="document"></div>
			</div>
		`;
		return modal;
	};

	// Get content from URL or element
	const getContent = async (target, type) => {
		// If it's already a DOM element
		if (target instanceof Element) {
			return target.cloneNode(true);
		}

		// If it's HTML string
		if (typeof target === 'string' && target.trim().startsWith('<')) {
			const div = document.createElement('div');
			div.innerHTML = target.trim();
			const result = div.firstElementChild || div;
			// If it's a wrapper div, return its children
			if (result.tagName === 'DIV' && result.children.length > 0) {
				return result;
			}
			return result;
		}

		// If it's a URL (ajax or image)
		if (typeof target === 'string') {
			if (type === 'ajax' || (!type && !target.match(/\.(png|jpg|jpeg|gif|svg|webp)(\?|$)/i))) {
				// AJAX request
				try {
					const response = await fetch(target);
					if (!response.ok) throw new Error('Network response was not ok');
					const html = await response.text();
					const div = document.createElement('div');
					div.innerHTML = html;
					return div;
				} catch (e) {
					console.error('Modal AJAX error:', e);
					const div = document.createElement('div');
					div.textContent = 'Failed to load content';
					return div;
				}
			} else {
				// Image
				return new Promise((resolve, reject) => {
					const img = document.createElement('img');
					img.src = target;
					img.className = 'ulpmodal-image';
					img.alt = '';
					img.onload = () => resolve(img);
					img.onerror = () => reject(img);
				});
			}
		}

		// Fallback
		const div = document.createElement('div');
		div.textContent = String(target);
		return div;
	};

	// Close current modal
	const closeCurrent = () => {
		if (currentModal) {
			const modal = currentModal;
			const ns = defaults.namespace;

			modal.classList.remove(`${ns}-visible`);

			setTimeout(() => {
				modal.remove();
				currentModal = null;
				document.documentElement.classList.remove('with-ulpmodal');
			}, defaults.closeSpeed);
		}
	};

	// Open modal
	const open = async (content, config = {}) => {
		config = { ...defaults, ...config };
		const ns = config.namespace || defaults.namespace;

		// Close existing modal if open
		if (currentModal) {
			closeCurrent();
			// Wait a bit for close animation
			await new Promise(resolve => setTimeout(resolve, config.closeSpeed));
		}

		// Create modal
		const modal = createModal(config);
		document.body.appendChild(modal);
		currentModal = modal;

		// Store previously focused element for restoration
		const previouslyFocused = document.activeElement;
		
		// Show modal immediately with loading state
		document.documentElement.classList.add('with-ulpmodal');

		// Force reflow before showing
		modal.offsetHeight;

		// Add visible class - CSS handles display and fade in
		modal.classList.add(`${ns}-visible`);
		
		// Focus the modal for keyboard navigation
		modal.focus();

		// Get content type
		const type = config.type || (content && content.getAttribute && content.getAttribute('data-ulpmodal-type'));

		// Load content asynchronously
		try {
			const contentEl = await getContent(content, type);
			const inner = modal.querySelector(`.${ns}-inner`);
			if (inner && contentEl) {
				inner.replaceWith(contentEl);
				contentEl.classList.add(`${ns}-inner`);
				contentEl.setAttribute('role', 'document');
				modal.classList.remove(`${ns}-loading`);
				
				// Focus first focusable element or close button
				const focusable = contentEl.querySelector('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
				if (focusable) {
					setTimeout(() => focusable.focus(), 100);
				} else {
					const closeBtn = modal.querySelector(`.${ns}-close`);
					if (closeBtn) closeBtn.focus();
				}
			}
		} catch (e) {
			console.error('Modal content error:', e);
			modal.classList.remove(`${ns}-loading`);
			const inner = modal.querySelector(`.${ns}-inner`);
			if (inner) {
				inner.innerHTML = '<div class="ulpmodal-error"><p>Failed to load content. Please try again.</p></div>';
			}
		}

		// Close handler
		const close = (e) => {
			if (e) e.preventDefault();
			if (currentModal !== modal) return; // Prevent closing if modal changed

			// Remove visible class - CSS handles the fade out
			modal.classList.remove(`${ns}-visible`);

			setTimeout(() => {
				if (currentModal === modal) {
					modal.remove();
					currentModal = null;
					if (!document.querySelector(`.${ns}.${ns}-visible`)) {
						document.documentElement.classList.remove('with-ulpmodal');
					}
					
					// Restore focus to previously focused element
					if (previouslyFocused && typeof previouslyFocused.focus === 'function') {
						previouslyFocused.focus();
					}
				}
			}, config.closeSpeed);
		};

		// Click to close
		modal.addEventListener('click', (e) => {
			if (config.closeOnClick === 'background' && e.target === modal) {
				close(e);
			} else if (e.target.closest(`.${ns}-close`)) {
				close(e);
			}
		});

		// ESC to close
		if (config.closeOnEsc) {
			const escHandler = (e) => {
				if (e.key === 'Escape' || e.keyCode === 27) {
					close(e);
					document.removeEventListener('keydown', escHandler);
				}
			};
			document.addEventListener('keydown', escHandler);
		}
		
		// Focus trap - keep focus within modal (updates when content loads)
		const updateFocusTrap = () => {
			const focusableElements = modal.querySelectorAll(
				'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
			);
			const firstFocusable = focusableElements[0] || modal.querySelector(`.${ns}-close`);
			const lastFocusable = focusableElements[focusableElements.length - 1] || modal.querySelector(`.${ns}-close`);
			
			return { firstFocusable, lastFocusable };
		};
		
		const trapFocus = (e) => {
			if (e.key !== 'Tab') return;
			const { firstFocusable, lastFocusable } = updateFocusTrap();
			
			if (e.shiftKey) {
				if (document.activeElement === firstFocusable) {
					lastFocusable.focus();
					e.preventDefault();
				}
			} else {
				if (document.activeElement === lastFocusable) {
					firstFocusable.focus();
					e.preventDefault();
				}
			}
		};
		
		modal.addEventListener('keydown', trapFocus);

		// After open callback
		setTimeout(() => {
			if (config.afterOpen) config.afterOpen({ target: modal });
		}, config.openSpeed);

		return modal;
	};

	// Auto-bind data-ulpmodal attributes
	const autoBind = () => {
		// Use event delegation for all clicks (works with dynamically added elements)
		document.addEventListener('click', (e) => {
			// Find the closest element with data-ulpmodal
			const trigger = e.target.closest('[data-ulpmodal]');
			if (!trigger) return;

			// Get the URL and type
			const url = trigger.getAttribute('data-ulpmodal');
			if (!url || !url.trim()) return;

			// Prevent default to stop navigation
			e.preventDefault();
			e.stopPropagation();

			// Prevent multiple clicks - if modal is already open, ignore
			if (currentModal) return;

			// Open modal
			const type = trigger.getAttribute('data-ulpmodal-type') || 'ajax';
			open(url, { type, afterOpen: defaults.afterOpen });
		});
	};

	// Export
	if (typeof window !== 'undefined') {
		window.ulpmodal = open;
		window.ulpmodalClose = closeCurrent;
	}

	// Auto-bind on ready
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', autoBind);
	} else {
		autoBind();
	}

})();


/* ================== public/assets/js/src/_toast.js =================== */


/**
 * WP ULike Notifications Plugin
 *
 * @fileoverview Toast notification system for user feedback
 * @requires ES7 (ES2016) compatible browser
 * @author WP ULike Team
 * @see https://github.com/alimir/wp-ulike
 */
(function (window, document, undefined) {
  "use strict";

  // Create the defaults once
  const pluginName = "WordpressUlikeNotifications";
  const defaults = {
    messageType: "success",
    messageText: "Hello World!",
    timeout: 8000,
    messageElement: "wpulike-message",
    notifContainer: "wpulike-notification",
    fadeOutClass: "wpulike-message-fadeout"
  };

  // Constants
  const FADE_OUT_DURATION = 300; // Match CSS transition duration

  // Cache container instances to avoid repeated DOM queries
  const containerCache = new WeakMap();

  /**
   * Helper function to dispatch custom events
   * Optimized: avoid creating empty objects
   */
  const triggerEvent = (element, eventName, detail) => {
    if (!element) return;
    const event = new CustomEvent(eventName, {
      bubbles: true,
      cancelable: true,
      detail: detail || null
    });
    element.dispatchEvent(event);
  };

  /**
   * Helper function to fade out an element using CSS class
   * No inline styles - all handled by CSS
   * Optimized: use requestAnimationFrame for better timing
   */
  const fadeOut = (element, callback, instance) => {
    if (!element) return;

    // Use requestAnimationFrame to sync with CSS transition
    requestAnimationFrame(() => {
      element.classList.add(defaults.fadeOutClass);

      // Remove element after transition completes
      const timeoutId = setTimeout(() => {
        if (instance) instance.fadeTimeoutId = null;
        if (callback) {
          callback();
        }
      }, FADE_OUT_DURATION);
      if (instance) instance.fadeTimeoutId = timeoutId;
    });
  };

  /**
   * Helper to get or create notification container
   * Optimized: cache container lookup using WeakMap
   */
  const getOrCreateContainer = (parentElement, containerClass) => {
    // Check cache first
    let container = containerCache.get(parentElement);
    if (container && container.parentNode) {
      return container;
    }

    // Query DOM only if not cached
    container = parentElement.querySelector(`.${containerClass}`);
    if (!container) {
      container = document.createElement("div");
      container.className = containerClass;
      parentElement.appendChild(container);
    }

    // Cache the container
    containerCache.set(parentElement, container);
    return container;
  };

  // The actual plugin constructor
  function Plugin(element, options) {
    if (!element) {
      console.warn("WordpressUlikeNotifications: element is required");
      return;
    }

    this.element = element;
    this.settings = Object.assign({}, defaults, options);
    this._defaults = defaults;
    this._name = pluginName;
    this.timeoutId = null;
    this.fadeTimeoutId = null; // Track fade timeout
    this.isRemoving = false;
    // Cache className to avoid template literal on each access
    this._messageClassName = null;

    this.init();
  }

  // Plugin prototype methods
  Plugin.prototype = {
    init() {
      // Create Message Wrapper
      this._createMessage();
      // Get or Create Notification Container
      this._getContainer();
      // Append Notification
      this._append();
      // Setup removal handlers
      this._setupRemoval();
    },

    /**
     * Create Message Wrapper
     * Optimized: cache className string
     */
    _createMessage() {
      this.messageElement = document.createElement("div");

      // Cache className to avoid template literal recreation
      if (!this._messageClassName) {
        this._messageClassName = `${this.settings.messageElement} wpulike-${this.settings.messageType}`;
      }
      this.messageElement.className = this._messageClassName;

      this.messageElement.textContent = this.settings.messageText;
      this.messageElement.setAttribute("role", "alert");
      this.messageElement.setAttribute("aria-live", "polite");
    },

    /**
     * Get or create notification container
     */
    _getContainer() {
      this.notifContainer = getOrCreateContainer(
        this.element,
        this.settings.notifContainer
      );
    },

    /**
     * Append notice to container
     * Optimized: batch DOM operations
     */
    _append() {
      if (!this.notifContainer || !this.messageElement) return;

      // Single DOM operation
      this.notifContainer.appendChild(this.messageElement);

      // Trigger event after DOM update
      requestAnimationFrame(() => {
        triggerEvent(this.notifContainer, "WordpressUlikeNotificationAppend", {
          messageElement: this.messageElement
        });
      });
    },

    /**
     * Setup removal handlers (click and timeout)
     * Optimized: use arrow function to avoid binding
     */
    _setupRemoval() {
      if (!this.messageElement) return;

      // Remove Message On Click - use arrow function for better performance
      this.messageElement.addEventListener("click", () => {
        this.remove();
      }, { once: true, passive: true }); // passive for better scroll performance

      // Remove Message With Timeout
      if (this.settings.timeout && this.settings.timeout > 0) {
        this.timeoutId = setTimeout(() => {
          this.remove();
        }, this.settings.timeout);
      }
    },

    /**
     * Remove message with fade out animation
     * Optimized to prevent multiple calls
     */
    remove() {
      if (this.isRemoving || !this.messageElement) return;
      this.isRemoving = true;

      // Clear timeouts if still pending
      if (this.timeoutId) {
        clearTimeout(this.timeoutId);
        this.timeoutId = null;
      }
      if (this.fadeTimeoutId) {
        clearTimeout(this.fadeTimeoutId);
        this.fadeTimeoutId = null;
      }

      // Remove message with fade out
      fadeOut(this.messageElement, () => {
        this._cleanup();
      }, this);
    },

    /**
     * Cleanup after removal
     * Optimized: batch DOM operations
     */
    _cleanup() {
      if (!this.messageElement) return;

      const messageEl = this.messageElement;
      const container = this.notifContainer;

      // Remove element from DOM
      if (messageEl.parentNode) {
        messageEl.remove();
      }

      // Check if container is empty and remove it
      if (container && container.children.length === 0) {
        if (container.parentNode) {
          container.remove();
          // Clear cache when container is removed
          containerCache.delete(this.element);
        }
      }

      // Trigger removal event
      triggerEvent(this.element, "WordpressUlikeRemoveNotification", {
        messageElement: messageEl
      });

      // Cleanup references
      this.messageElement = null;
      this.notifContainer = null;
      this.isRemoving = false;
      this._messageClassName = null;
    }
  };

  // Expose plugin to window for global access
  window[pluginName] = Plugin;

  // Expose as jQuery plugin for backward compatibility (if jQuery is available)
  // This allows users' existing jQuery code to continue working
  // Example: $(document.body).WordpressUlikeNotifications({...})
  if (typeof jQuery !== 'undefined' && jQuery && jQuery.fn) {
    jQuery.fn[pluginName] = function (options) {
      return this.each(function () {
        new Plugin(this, options);
      });
    };
  }
})(window, document);


/* ================== public/assets/js/src/_tooltip.js =================== */


/**
 * WP ULike Tooltip Plugin
 *
 * @fileoverview Lightweight tooltip solution with dynamic content loading
 * @requires ES7 (ES2016) compatible browser
 * @author WP ULike Team
 * @see https://github.com/alimir/wp-ulike
 */
(function (window, document, undefined) {
  "use strict";

  // Store tooltip instances
  const tooltipInstances = new WeakMap();
  const tooltipInstancesById = {}; // For easier access by ID
  const activeTooltips = [];

  // Default options
  const defaults = {
    id: Date.now(),
    title: "",
    trigger: "hover",
    position: "top",
    class: "",
    theme: "light",
    size: "small",
    singleton: true,
    close_on_outside_click: true,
  };

  // Constants
  const SPACING = 13;
  const SHOW_DELAY = 100;
  const HIDE_DELAY = 100;

  // Helper: Get element position (viewport-relative for fixed positioning)
  const getOffset = (element) => {
    const rect = element.getBoundingClientRect();
    return {
      top: rect.top,
      left: rect.left,
      width: rect.width,
      height: rect.height,
    };
  };

  // Helper: Create loading spinner HTML
  const createSpinnerHTML = () => {
    return '<div class="ulf-loading-spinner"><div class="ulf-spinner-circle"></div><div class="ulf-spinner-circle"></div><div class="ulf-spinner-circle"></div></div>';
  };

  // Helper: Create tooltip element
  const createTooltipElement = (content, className, isLoading) => {
    const tooltip = document.createElement("div");
    tooltip.className = `ulf-tooltip ${className || ""}`;
    tooltip.setAttribute("role", "tooltip");

    // Ensure content is never empty to prevent glitch
    const contentHTML = isLoading ? createSpinnerHTML() : (content || "&nbsp;");
    tooltip.innerHTML = `<div class="ulf-arrow"></div><div class="ulf-content">${contentHTML}</div>`;
    return tooltip;
  };

  // Helper: Position tooltip
  const positionTooltip = (tooltip, reference, placement) => {
    // Force a reflow to ensure tooltip has proper dimensions
    void tooltip.offsetHeight;

    const refRect = getOffset(reference);
    const tooltipRect = tooltip.getBoundingClientRect();
    const arrow = tooltip.querySelector(".ulf-arrow");
    const viewport = {
      width: window.innerWidth,
      height: window.innerHeight,
    };

    const positions = {
      top: {
        top: refRect.top - tooltipRect.height - SPACING,
        left: refRect.left + refRect.width / 2 - tooltipRect.width / 2,
        arrow: "bottom",
      },
      bottom: {
        top: refRect.top + refRect.height + SPACING,
        left: refRect.left + refRect.width / 2 - tooltipRect.width / 2,
        arrow: "top",
      },
      left: {
        top: refRect.top + refRect.height / 2 - tooltipRect.height / 2,
        left: refRect.left - tooltipRect.width - SPACING,
        arrow: "right",
      },
      right: {
        top: refRect.top + refRect.height / 2 - tooltipRect.height / 2,
        left: refRect.left + refRect.width + SPACING,
        arrow: "left",
      },
    };

    const pos = positions[placement] || positions.top;

    // Keep tooltip in viewport
    if (pos.left < 10) pos.left = 10;
    if (pos.left + tooltipRect.width > viewport.width - 10) {
      pos.left = viewport.width - tooltipRect.width - 10;
    }
    if (pos.top < 10) pos.top = 10;
    if (pos.top + tooltipRect.height > viewport.height - 10) {
      pos.top = viewport.height - tooltipRect.height - 10;
    }

    // Use fixed positioning (viewport-relative) for consistent positioning
    tooltip.style.position = "fixed";
    tooltip.style.left = `${pos.left}px`;
    tooltip.style.top = `${pos.top}px`;

    if (arrow) {
      arrow.className = `ulf-arrow ulf-arrow-${pos.arrow}`;
    }

    // Mark as positioned to show arrow
    tooltip.setAttribute("data-positioned", "true");
  };

  // Helper: Convert array-like to array
  const arrayFrom = Array.from || ((arr) => Array.prototype.slice.call(arr));

  // Main plugin
  function WordpressUlikeTooltipPlugin(element, options) {
    // Handle multiple elements
    if (element.length !== undefined && element.length > 1) {
      arrayFrom(element).forEach((el) => {
        new WordpressUlikeTooltipPlugin(el, options);
      });
      return element;
    }

    if (!element) return false;

    // Merge options
    options = Object.assign({}, defaults, options || {});

    // Get title from attribute or hidden content element
    if (!options.title) {
      // Check for hidden content element (for dynamic content like likers)
      const hiddenContent = element.querySelector('[data-tooltip-content]');
      if (hiddenContent) {
        const tooltipState = hiddenContent.getAttribute('data-tooltip-state');
        if (tooltipState === 'ready') {
          options.title = hiddenContent.innerHTML.trim();
        }
      }

      // Fallback to title attribute
      if (!options.title) {
        const titleAttr = element.getAttribute("title");
        if (titleAttr) {
          options.title = titleAttr;
          element.removeAttribute("title");
        }
      }
    }

    // Destroy existing
    const existing = tooltipInstances.get(element);
    if (existing) {
      existing.destroy();
    }

    let tooltip = null;
    let showTimeout = null;
    let hideTimeout = null;
    let isLoading = false;
    let scrollHandler = null;
    let scrollHandlerOptions = null;
    let outsideHandler = null; // Store for cleanup
    let isHovering = false; // Track if user is currently hovering

    const show = (showLoading) => {
      // If showing loading, always show even if tooltip exists
      if (tooltip && tooltip.parentNode && !showLoading) return;

      // Hide others if singleton
      if (options.singleton !== false) {
        activeTooltips.forEach((t) => {
          if (t && t.hide && t.element !== element) t.hide();
        });
      }

      // Create or update tooltip
      let className = `ulf-${options.theme || "light"}-theme ulf-${options.size || "small"}`;
      if (options.class) className += ` ${options.class}`;

      // Get reference element for positioning
      let reference = element;
      if (options.child) {
        const childEl = element.querySelector(options.child);
        if (childEl) reference = childEl;
      }

      if (!tooltip || !tooltip.parentNode || showLoading) {
        if (tooltip && tooltip.parentNode) {
          tooltip.remove();
        }
        isLoading = showLoading === true;
        tooltip = createTooltipElement(
          options.title || "",
          className,
          isLoading
        );
        document.body.appendChild(tooltip);
        // Position after a brief delay to ensure dimensions are calculated
        requestAnimationFrame(() => {
          if (tooltip && tooltip.parentNode) {
            positionTooltip(tooltip, reference, options.position || "top");
          }
        });
      } else {
        // Update existing tooltip content
        const contentEl = tooltip.querySelector(".ulf-content");
        if (contentEl) {
          isLoading = showLoading === true;
          contentEl.innerHTML = isLoading
            ? createSpinnerHTML()
            : (options.title || "&nbsp;");
        }
        // Reposition after content update (with delay for dimension calculation)
        requestAnimationFrame(() => {
          if (tooltip && tooltip.parentNode) {
            positionTooltip(tooltip, reference, options.position || "top");
          }
        });
      }

      // Add hover handlers to tooltip if trigger is hover
      if (options.trigger === "hover" || !options.trigger) {
        tooltip.addEventListener("mouseenter", () => {
          clearTimeout(hideTimeout);
        });
        tooltip.addEventListener("mouseleave", handleHide);
      }

      // Add scroll listener to hide tooltip on scroll (standard behavior)
      // This prevents tooltip from appearing to "move" when scrolling
      // Most tooltip libraries (Tippy.js, Popper.js) hide tooltips on scroll
      if (!scrollHandler) {
        scrollHandler = () => {
          if (tooltip && tooltip.parentNode) {
            hide();
          }
        };
        // Use capture phase and passive for better performance
        scrollHandlerOptions = { capture: true, passive: true };
        window.addEventListener("scroll", scrollHandler, scrollHandlerOptions);
      }

      // Add to active
      const isInActive = activeTooltips.some((t) => t.element === element);
      if (!isInActive) {
        activeTooltips.push({ element, hide });
      }

      // Set ID for accessibility
      const id = `ulp-dom-${options.id}`;
      tooltip.setAttribute("id", id);
      element.setAttribute("aria-describedby", id);

      // Trigger event
      const event = new CustomEvent("ulf-show", {
        bubbles: true,
        detail: { tooltip },
      });
      element.dispatchEvent(event);
    };

    // Helper: Get or create hidden content element
    const getTooltipContentElement = () => {
      let hiddenContent = element.querySelector('[data-tooltip-content]');
      if (!hiddenContent) {
        hiddenContent = document.createElement("div");
        hiddenContent.setAttribute('data-tooltip-content', '');
        hiddenContent.style.display = 'none';
        element.appendChild(hiddenContent);
      }
      return hiddenContent;
    };

    // Helper: Set tooltip state
    const setTooltipState = (state) => {
      const hiddenContent = getTooltipContentElement();
      hiddenContent.setAttribute('data-tooltip-state', state);
    };

    // Helper: Get tooltip state
    const getTooltipState = () => {
      const hiddenContent = element.querySelector('[data-tooltip-content]');
      return hiddenContent ? hiddenContent.getAttribute('data-tooltip-state') : null;
    };

    const updateContent = (content) => {
      // Update options.title to keep it in sync
      options.title = content || "";

      // Update hidden content element (for dynamic content)
      const hiddenContent = getTooltipContentElement();
      hiddenContent.innerHTML = content || "";

      // Set state: 'ready' if has content, 'empty' if no content
      const hasContent = content && content.trim().length > 0;
      setTooltipState(hasContent ? 'ready' : 'empty');

      // If content is empty, hide tooltip immediately (don't show empty tooltip)
      if (!hasContent) {
        if (tooltip && tooltip.parentNode) {
          hide();
        }
        return;
      }

      // If tooltip is visible, update it immediately
      if (tooltip && tooltip.parentNode) {
        const contentEl = tooltip.querySelector(".ulf-content");
        if (contentEl) {
          contentEl.innerHTML = content;
          isLoading = false;
          // Reposition after content update
          const reference = options.child ? (element.querySelector(options.child) || element) : element;
          requestAnimationFrame(() => {
            if (tooltip && tooltip.parentNode) {
              positionTooltip(tooltip, reference, options.position || "top");
            }
          });
        }
      } else if (isHovering) {
        // Tooltip not visible but user is hovering - show it
        show(false);
      }
    };

    const hide = () => {
      if (!tooltip || !tooltip.parentNode) return;

      // Remove scroll listener when hiding
      if (scrollHandler && scrollHandlerOptions) {
        window.removeEventListener("scroll", scrollHandler, scrollHandlerOptions);
        scrollHandler = null;
        scrollHandlerOptions = null;
      }

      tooltip.remove();
      tooltip = null;
      isLoading = false;

      // Remove from active
      const index = activeTooltips.findIndex((t) => t.element === element);
      if (index > -1) {
        activeTooltips.splice(index, 1);
      }

      // Remove aria
      element.removeAttribute("aria-describedby");

      // Trigger event
      const event = new CustomEvent("ulf-hide", { bubbles: true });
      element.dispatchEvent(event);
    };

    // Helper: Get cached content from hidden element
    const getCachedContent = () => {
      const hiddenContent = element.querySelector('[data-tooltip-content]');
      return hiddenContent ? hiddenContent.innerHTML.trim() : '';
    };

    // Event handlers
    const handleShow = () => {
      clearTimeout(hideTimeout);
      isHovering = true;

      const tooltipState = getTooltipState();

      // Handle different states
      if (tooltipState === 'empty') return; // Don't show empty tooltip

      if (tooltipState === 'ready') {
        const cachedContent = getCachedContent();
        if (cachedContent) {
          options.title = cachedContent;
          showTimeout = setTimeout(show, SHOW_DELAY);
        } else {
          setTooltipState('empty'); // Content disappeared - mark as empty
        }
        return;
      }

      if (tooltipState === 'loading') {
        show(true);
        return;
      }

      // Not initialized - request data
      if (!tooltipState || tooltipState === '') {
        if (instance.requestData && instance.requestData()) {
          show(true);
        } else {
          setTooltipState('loading');
          show(true);
        }
        return;
      }

      // Fallback for static content
      if (options.showLoadingImmediately) {
        show(true);
      } else {
        showTimeout = setTimeout(show, SHOW_DELAY);
      }
    };

    const handleHide = () => {
      clearTimeout(showTimeout);
      isHovering = false;
      hideTimeout = setTimeout(hide, HIDE_DELAY);
    };

    // Setup events based on trigger
    if (options.trigger === "hover" || !options.trigger) {
      element.addEventListener("mouseenter", handleShow);
      element.addEventListener("mouseleave", handleHide);
    } else if (options.trigger === "click") {
      element.addEventListener("click", (e) => {
        e.preventDefault();
        if (tooltip && tooltip.parentNode) {
          hide();
        } else {
          show();
        }
      });
    }

    // Click outside handler
    if (options.close_on_outside_click !== false) {
      outsideHandler = (e) => {
        if (
          tooltip &&
          tooltip.parentNode &&
          !tooltip.contains(e.target) &&
          !element.contains(e.target)
        ) {
          hide();
        }
      };
      document.addEventListener("mousedown", outsideHandler);
    }

    // Store instance
    const instance = {
      show,
      showLoading: () => show(true),
      updateContent,
      hide,
      destroy: () => {
        hide();
        element.removeEventListener("mouseenter", handleShow);
        element.removeEventListener("mouseleave", handleHide);
        if (instance.contentUpdateHandler) {
          element.removeEventListener("tooltip-content-updated", instance.contentUpdateHandler);
        }
        if (scrollHandler && scrollHandlerOptions) {
          window.removeEventListener("scroll", scrollHandler, scrollHandlerOptions);
          scrollHandler = null;
          scrollHandlerOptions = null;
        }
        if (outsideHandler) {
          document.removeEventListener("mousedown", outsideHandler);
          outsideHandler = null;
        }
        if (showTimeout) {
          clearTimeout(showTimeout);
          showTimeout = null;
        }
        if (hideTimeout) {
          clearTimeout(hideTimeout);
          hideTimeout = null;
        }
        tooltipInstances.delete(element);
        if (options.id) delete tooltipInstancesById[options.id];
      },
    };

    tooltipInstances.set(element, instance);
    if (options.id) {
      tooltipInstancesById[options.id] = instance;
    }

    // Expose helper methods for external use
    instance.setLoadingState = () => {
      setTooltipState('loading');
    };

    // If tooltip is created with hover trigger, check state immediately
    // This handles the case when tooltip is created while user is already hovering
    if (!options.trigger || options.trigger === "hover") {
      setTimeout(() => {
        const currentState = getTooltipState();
        if (!currentState || currentState === '') {
          handleShow(); // Will request data
        } else if (currentState === 'loading') {
          show(true);
        } else if (currentState === 'ready') {
          const cachedContent = getCachedContent();
          if (cachedContent) {
            options.title = cachedContent;
            showTimeout = setTimeout(show, SHOW_DELAY);
          }
        }
      }, 0);
    }

    // Request data from external source (e.g., AJAX)
    // This method checks state and triggers event or calls dataFetcher callback if provided
    instance.requestData = () => {
      const currentState = getTooltipState();

      // If already loaded (ready or empty), don't request again
      if (currentState === 'ready' || currentState === 'empty') {
        return false; // Data already available
      }

      // If already loading, don't request again
      if (currentState === 'loading') {
        return false; // Already requesting
      }

      // Set loading state
      setTooltipState('loading');

      // If dataFetcher callback is provided, use it directly
      if (typeof options.dataFetcher === 'function') {
        options.dataFetcher(element, options.id);
        return true;
      }

      // Fallback: trigger event for external handler (backward compatibility)
      setTimeout(() => {
        const event = new CustomEvent("tooltip-request-data", {
          bubbles: true,
          detail: { element, tooltipId: options.id }
        });
        element.dispatchEvent(event);
        document.dispatchEvent(event);
      }, 0);

      return true;
    };

    // Listen for content updates via custom event (optional, for external updates)
    const contentUpdateHandler = (e) => {
      const detail = e.detail || {};
      if (detail.element === element || (detail.target && element.contains(detail.target))) {
        updateContent(detail.content || "");
      }
    };
    element.addEventListener("tooltip-content-updated", contentUpdateHandler);
    instance.contentUpdateHandler = contentUpdateHandler;

    return element;
  }

  // Expose
  window.WordpressUlikeTooltipPlugin = WordpressUlikeTooltipPlugin;
  window.WordpressUlikeTooltip = {
    visible: activeTooltips,
    defaults,
    getInstanceById: (id) => tooltipInstancesById[id],
    getInstanceByElement: (element) => tooltipInstances.get(element),
  };

  // Expose as jQuery plugin for backward compatibility (if jQuery is available)
  // This allows users' existing jQuery code to continue working
  // Example: $('.element').WordpressUlikeTooltip({...})
  if (typeof jQuery !== 'undefined' && jQuery && jQuery.fn) {
    jQuery.fn.WordpressUlikeTooltip = function (options) {
      return this.each(function () {
        new WordpressUlikeTooltipPlugin(this, options);
      });
    };
  }
})(window, document);


/* ================== public/assets/js/src/_ulike.js =================== */


/**
 * WP ULike Pro - Main Plugin
 *
 * @fileoverview Core like/unlike functionality with AJAX support (Pro Version)
 * @requires ES7 (ES2016) compatible browser
 * @author WP ULike Team
 * @see https://github.com/alimir/wp-ulike
 */
(function (window, document, undefined) {
  "use strict";

  // Create the defaults once
  const pluginName = "WordpressUlike";
  const defaults = {
    ID: 0,
    nonce: 0,
    type: "",
    append: "",
    appendTimeout: 2000,
    displayLikers: false,
    likersTemplate: "default",
    disablePophover: true,
    isTotal: false,
    factor: "",
    template: "",
    counterSelector: ".count-box",
    generalSelector: ".wp_ulike_general_class",
    buttonSelector: ".wp_ulike_btn",
    likersSelector: ".wp_ulike_likers_wrapper",
  };
  const attributesMap = {
    "ulike-id": "ID",
    "ulike-nonce": "nonce",
    "ulike-type": "type",
    "ulike-append": "append",
    "ulike-is-total": "isTotal",
    "ulike-display-likers": "displayLikers",
    "ulike-likers-style": "likersTemplate",
    "ulike-disable-pophover": "disablePophover",
    "ulike-append-timeout": "appendTimeout",
    "ulike-factor": "factor",
    "ulike-template": "template",
  };

  // Helper function to get data attribute value
  const getDataAttribute = (element, name) => {
    const camelName = name.replace(/-([a-z])/g, (g) => g[1].toUpperCase());
    if (element.dataset && element.dataset[camelName] !== undefined) {
      return element.dataset[camelName];
    }
    const value = element.getAttribute(`data-${name}`);
    if (value === null) {
      return undefined;
    }
    if (value === "true") return true;
    if (value === "false") return false;
    if (value === "" || value === "null") return null;
    if (!isNaN(value) && value !== "") return Number(value);
    return value;
  };

  // Helper function to trigger custom events (works with both jQuery and vanilla JS)
  const triggerEvent = (element, eventName, data) => {
    const event = new CustomEvent(eventName, {
      bubbles: true,
      cancelable: true,
      detail: data
    });
    element.dispatchEvent(event);

    if (typeof jQuery !== 'undefined' && jQuery && jQuery.fn && jQuery.fn.on) {
      const $element = jQuery(element);
      $element.trigger(eventName, data);
    }
  };

  // Safe Array.from polyfill for older browsers
  const arrayFrom = (arrayLike) => {
    if (Array.from) {
      return Array.from(arrayLike);
    }
    return Array.prototype.slice.call(arrayLike);
  };

  // Helper function to handle multiple elements (like jQuery collection)
  const forEachElement = (elements, callback) => {
    if (!elements) return;
    if (elements.length === undefined) {
      callback(elements, 0);
    } else {
      arrayFrom(elements).forEach(callback);
    }
  };

  // Helper to get siblings (like jQuery .siblings())
  const getSiblings = (element, selector) => {
    const siblings = [];
    const parent = element.parentNode;
    if (!parent) return siblings;
    const children = parent.children;
    for (let i = 0; i < children.length; i++) {
      if (children[i] !== element) {
        if (!selector || children[i].matches(selector)) {
          siblings.push(children[i]);
        }
      }
    }
    return siblings;
  };

  // Helper to get all siblings from multiple elements (like jQuery collection.siblings())
  const getAllSiblings = (elements, selector) => {
    const allSiblings = [];
    const seen = new Set();
    forEachElement(elements, (el) => {
      const siblings = getSiblings(el, selector);
      siblings.forEach((sibling) => {
        if (!seen.has(sibling)) {
          seen.add(sibling);
          allSiblings.push(sibling);
        }
      });
    });
    return allSiblings;
  };

  // Helper to get single element from array/NodeList
  const getSingleElement = (elements) => {
    return Array.isArray(elements) || elements.length !== undefined
      ? elements[0]
      : elements;
  };

  // Helper to normalize boolean values in settings
  const normalizeBooleanValues = (settings, defaults) => {
    for (const key in defaults) {
      if (typeof defaults[key] === 'boolean' && settings[key] != null) {
        settings[key] = settings[key] != 0 && settings[key] !== "0" && settings[key] !== false;
      }
    }
  };

  // The actual plugin constructor
  function Plugin(element, options) {
    this.element = element;
    this.settings = Object.assign({}, defaults, options);
    // Normalize boolean values automatically
    normalizeBooleanValues(this.settings, defaults);
    this._defaults = defaults;
    this._name = pluginName;
    // Store handlers and timeouts for cleanup
    this._boundHandlers = [];
    this._timeouts = [];
    // Initialize fetching flag
    this._isFetchingLikers = false;

    // Create main selectors (like jQuery .find())
    this.buttonElement = this.element.querySelectorAll(this.settings.buttonSelector);

    // read attributes from first button
    const firstButton = this.buttonElement.length > 0 ? this.buttonElement[0] : null;
    if (firstButton) {
      for (const attrName in attributesMap) {
        if (attributesMap.hasOwnProperty(attrName)) {
          const value = getDataAttribute(firstButton, attrName);
          if (value !== undefined) {
            this.settings[attributesMap[attrName]] = value;
          }
        }
      }
      // Normalize boolean values after reading attributes
      normalizeBooleanValues(this.settings, defaults);
    }

    // General element (like jQuery .find())
    this.generalElement = this.element.querySelectorAll(this.settings.generalSelector);

    // Create counter element (like jQuery .find() on collection)
    this.counterElement = [];
    if (this.generalElement.length > 0) {
      forEachElement(this.generalElement, (generalEl) => {
        const counters = generalEl.querySelectorAll(this.settings.counterSelector);
        forEachElement(counters, (counter) => {
          this.counterElement.push(counter);
        });
      });
    }

    // Append dom counter element
    if (this.counterElement.length > 0) {
      forEachElement(this.counterElement, (element) => {
        const counterValue = getDataAttribute(element, "ulike-counter-value");
        if (counterValue !== undefined) {
          element.innerHTML = counterValue;
        }
      });
    }
    // Get likers box container element
    this.likersElement = this.element.querySelector(this.settings.likersSelector);

    this.init();
  }

  // Plugin prototype methods
  Plugin.prototype = {
    init() {
      // Attach click listeners to ALL buttons
      if (this.buttonElement && this.buttonElement.length > 0) {
        const boundHandler = this._initLike.bind(this);
        this._boundHandlers.push({ element: this.buttonElement, event: 'click', handler: boundHandler });
        forEachElement(this.buttonElement, (button) => {
          if (button) {
            button.addEventListener("click", boundHandler);
          }
        });
      }
      // Call likers box generator (one-time event)
      const firstGeneralEl = this.generalElement.length > 0 ? this.generalElement[0] : null;
      if (firstGeneralEl) {
        const mouseenterHandler = (event) => {
          this._updateLikers(event);
          firstGeneralEl.removeEventListener("mouseenter", mouseenterHandler);
          // Remove from tracking since it removes itself
          const index = this._boundHandlers.findIndex(h => h.handler === mouseenterHandler);
          if (index > -1) this._boundHandlers.splice(index, 1);
        };
        this._boundHandlers.push({ element: firstGeneralEl, event: 'mouseenter', handler: mouseenterHandler });
        firstGeneralEl.addEventListener("mouseenter", mouseenterHandler);
      }
      // Track button view when it becomes visible
      this._trackButtonView();
    },

    /**
     * global AJAX callback
     * PRO VERSION: Uses UlikeProCommonConfig.AjaxUrl
     */
    _ajax(args, callback) {
      const formData = new FormData();
      for (const key in args) {
        if (args.hasOwnProperty(key)) {
          formData.append(key, args[key]);
        }
      }

      const ajaxUrl = (typeof UlikeProCommonConfig !== 'undefined' && UlikeProCommonConfig.AjaxUrl)
        ? UlikeProCommonConfig.AjaxUrl
        : (typeof wp_ulike_params !== 'undefined' ? wp_ulike_params.ajax_url : '');

      fetch(ajaxUrl, {
        method: "POST",
        body: formData,
      })
        .then((response) => response.json())
        .then(callback)
        .catch((error) => {
          console.error("WP Ulike AJAX error:", error);
        });
    },

    /**
     * init ulike core process
     */
    _initLike(event) {
      event.stopPropagation();
      // Update element if there's more than one button
      this._maybeUpdateElements(event);
      // Check for same buttons elements
      this._updateSameButtons();
      // Check for same likers elements
      this._updateSameLikers();
      // Disable button
      if (this.buttonElement) {
        forEachElement(this.buttonElement, (btn) => {
          btn.disabled = true;
        });
      }
      // Manipulations
      triggerEvent(document, "WordpressUlikeLoading", this.element);
      // Add progress class
      if (this.generalElement) {
        forEachElement(this.generalElement, (el) => {
          el.classList.add("wp_ulike_is_loading");
        });
      }
      // Start AJAX process
      this._ajax(
        {
          action: "wp_ulike_process",
          id: this.settings.ID,
          nonce: this.settings.nonce,
          factor: this.settings.factor,
          type: this.settings.type,
          template: this.settings.template,
          displayLikers: this.settings.displayLikers,
          likersTemplate: this.settings.likersTemplate,
        },
        (response) => {
          //remove progress class
          if (this.generalElement) {
            forEachElement(this.generalElement, (el) => {
              el.classList.remove("wp_ulike_is_loading");
            });
          }
          // Make changes
          if (response.success) {
            // PRO VERSION: Handle modalTemplate before _updateMarkup
            if (
              typeof response.data.hasToast !== "undefined" &&
              typeof response.data.modalTemplate !== "undefined" &&
              response.data.modalTemplate
            ) {
              this._openModal(response.data.modalTemplate);
            } else {
              this._updateMarkup(response);
              // Append html data
              this._appendChild();
            }
          } else if (response.data && response.data.hasToast) {
            this._sendNotification("error", response.data.message);
          }
          // Re-enable button
          if (this.buttonElement) {
            forEachElement(this.buttonElement, (btn) => {
              btn.disabled = false;
            });
          }
          // Add new trigger when process finished
          triggerEvent(document, "WordpressUlikeUpdated", this.element);
        }
      );
    },

    /**
     * PRO VERSION: Open modal with content
     */
    _openModal(data) {
      if (typeof window.ulpmodal === 'function') {
        // Create wrapper div with class
        const wrapper = document.createElement('div');
        wrapper.className = 'ulpmodal-ajax-wrapper';
        wrapper.innerHTML = data;

        window.ulpmodal(wrapper, {
          closeOnClick: "background",
          afterOpen: (event) => {
            triggerEvent(document, "WordpressUlikeModalAfterOpen", event);
          },
          closeOnEsc: true,
        });
      } else {
        console.warn("WP ULike Pro: Modal functionality requires ulpmodal plugin");
      }
    },

    _maybeUpdateElements(event) {
      this.buttonElement = event.currentTarget;
      this.generalElement = this.buttonElement.closest(this.settings.generalSelector);
      if (this.generalElement) {
        this.counterElement = this.generalElement.querySelectorAll(this.settings.counterSelector);
      } else {
        this.counterElement = [];
      }
      this.settings.factor = getDataAttribute(this.buttonElement, "ulike-factor");
    },

    /**
     * append child
     */
    _appendChild() {
      if (this.settings.append !== "" && this.buttonElement) {
        let sourceElements = [];

        // Check if append is HTML content (starts with <) or a CSS selector
        if (this.settings.append.trim().startsWith('<')) {
          // Parse HTML content
          const tempDiv = document.createElement("div");
          tempDiv.innerHTML = this.settings.append;
          // Collect all children by removing them from tempDiv
          while (tempDiv.firstChild) {
            sourceElements.push(tempDiv.removeChild(tempDiv.firstChild));
          }
        } else {
          // Try to use as CSS selector
          const appendedElement = document.querySelector(this.settings.append);
          if (appendedElement) {
            sourceElements.push(appendedElement);
          }
        }

        if (sourceElements.length > 0) {
          const appendedElements = [];
          forEachElement(this.buttonElement, (button) => {
            if (button) {
              sourceElements.forEach((sourceElement) => {
                const clonedElement = sourceElement.cloneNode(true);
                button.appendChild(clonedElement);
                appendedElements.push(clonedElement);
              });
            }
          });

          if (this.settings.appendTimeout && appendedElements.length > 0) {
            const timeoutId = setTimeout(() => {
              appendedElements.forEach((el) => {
                if (el && el.parentNode) {
                  el.remove();
                }
              });
            }, this.settings.appendTimeout);
            this._timeouts.push(timeoutId);
          }
        }
      }
    },

    /**
     * update button markup and calling some actions
     */
    _updateMarkup(response) {
      // Set sibling general elements
      this._setSbilingElement();
      // Set sibling button elements
      this._setSbilingButtons();
      // Update general element class names
      this._updateGeneralClassNames(response.data.status);
      // If data exist
      if (response.data.data !== null) {
        // Update counter + check refresh likers box
        if (response.data.status !== 5) {
          this.__updateCounter(response.data.data);
          // Refresh likers box on data update
          if (
            this.settings.displayLikers &&
            typeof response.data.likers !== "undefined"
          ) {
            this._updateLikersMarkup(response.data.likers);
          }
        }
        // Update button status
        this._updateButton(response.data.btnText, response.data.status);
      }
      // Display Notifications
      if (response.data.hasToast) {
        this._sendNotification(
          response.data.messageType,
          response.data.message
        );
      }
      // PRO VERSION: Display share buttons modal after success
      if (response.data.modalAfterSuccess) {
        this._openModal(response.data.modalAfterSuccess);
      }
    },

    _updateGeneralClassNames(status) {
      const classNameObj = {
        start: "wp_ulike_is_not_liked",
        active: "wp_ulike_is_liked",
        deactive: "wp_ulike_is_unliked",
        disable: "wp_ulike_click_is_disabled",
      };

      // Remove status from sibling element
      if (this.siblingElement && this.siblingElement.length) {
        forEachElement(this.siblingElement, (el) => {
          el.classList.remove(classNameObj.active, classNameObj.deactive);
        });
      }

      // Update general element(s)
      forEachElement(this.generalElement, (generalEl) => {
        if (!generalEl) return;

        switch (status) {
          case 1:
            generalEl.classList.add(classNameObj.active);
            generalEl.classList.remove(classNameObj.start);
            const firstChild = generalEl.firstElementChild;
            if (firstChild) {
              firstChild.classList.add(classNameObj.disable);
            }
            break;

          case 2:
            generalEl.classList.add(classNameObj.deactive);
            generalEl.classList.remove(classNameObj.active);
            break;

          case 3:
            generalEl.classList.add(classNameObj.active);
            generalEl.classList.remove(classNameObj.deactive);
            break;

          case 0:
          case 5:
            generalEl.classList.add(classNameObj.disable);
            break;
        }
      });

      // Handle sibling disable for case 0 and 5
      if ((status === 0 || status === 5) && this.siblingElement && this.siblingElement.length) {
        forEachElement(this.siblingElement, (el) => {
          el.classList.add(classNameObj.disable);
        });
      }
    },

    _arrayToString(data) {
      return data.join(" ");
    },

    _setSbilingElement() {
      // Like jQuery: this.generalElement.siblings()
      // When generalElement is a collection, get siblings of ALL elements
      if (this.generalElement.length !== undefined && this.generalElement.length > 1) {
        this.siblingElement = getAllSiblings(this.generalElement);
      } else {
        const singleEl = getSingleElement(this.generalElement);
        this.siblingElement = singleEl ? getSiblings(singleEl) : [];
      }
    },

    _setSbilingButtons() {
      // Like jQuery: this.buttonElement.siblings(selector)
      // When buttonElement is a collection, get siblings of ALL elements
      if (this.buttonElement.length !== undefined && this.buttonElement.length > 1) {
        this.siblingButton = getAllSiblings(this.buttonElement, this.settings.buttonSelector);
      } else {
        const singleEl = getSingleElement(this.buttonElement);
        this.siblingButton = singleEl ? getSiblings(singleEl, this.settings.buttonSelector) : [];
      }
    },

    /**
     * PRO VERSION: Enhanced counter update with isTotal and factor (up/down) support
     */
    __updateCounter(counterValue) {
      // Update counter element
      if (typeof counterValue !== "object") {
        forEachElement(this.counterElement, (element) => {
          element.setAttribute("data-ulike-counter-value", counterValue);
          element.innerHTML = counterValue;
        });
      } else {
        if (this.settings.isTotal && typeof counterValue.sub !== "undefined") {
          forEachElement(this.counterElement, (element) => {
            element.setAttribute("data-ulike-counter-value", counterValue.sub);
            element.innerHTML = counterValue.sub;
          });
        } else {
          if (this.settings.factor === "down") {
            forEachElement(this.counterElement, (element) => {
              element.setAttribute("data-ulike-counter-value", counterValue.down);
              element.innerHTML = counterValue.down;
            });
            if (this.siblingElement && this.siblingElement.length) {
              forEachElement(this.siblingElement, (sibling) => {
                const siblingCounters = sibling.querySelectorAll(this.settings.counterSelector);
                forEachElement(siblingCounters, (counter) => {
                  counter.setAttribute("data-ulike-counter-value", counterValue.up);
                  counter.innerHTML = counterValue.up;
                });
              });
            }
          } else {
            forEachElement(this.counterElement, (element) => {
              element.setAttribute("data-ulike-counter-value", counterValue.up);
              element.innerHTML = counterValue.up;
            });
            if (this.siblingElement && this.siblingElement.length) {
              forEachElement(this.siblingElement, (sibling) => {
                const siblingCounters = sibling.querySelectorAll(this.settings.counterSelector);
                forEachElement(siblingCounters, (counter) => {
                  counter.setAttribute("data-ulike-counter-value", counterValue.down);
                  counter.innerHTML = counterValue.down;
                });
              });
            }
          }
        }
      }

      const buttonEl = getSingleElement(this.buttonElement);
      triggerEvent(document, "WordpressUlikeCounterUpdated", [buttonEl]);
    },

    /**
     * Fetch likers data via AJAX
     * Prevents duplicate requests
     */
    _fetchLikersData() {
      if (!this.settings.displayLikers) {
        this._isFetchingLikers = false;
        return;
      }

      // Prevent duplicate requests
      if (this._isFetchingLikers) {
        return;
      }

      this._isFetchingLikers = true;

      const generalEl = getSingleElement(this.generalElement);
      if (generalEl) {
        generalEl.classList.add("wp_ulike_is_getting_likers_list");
      }

      this._ajax(
        {
          action: "wp_ulike_get_likers",
          id: this.settings.ID,
          nonce: this.settings.nonce,
          type: this.settings.type,
          displayLikers: this.settings.displayLikers,
          likersTemplate: this.settings.likersTemplate,
        },
        (response) => {
          if (generalEl) {
            generalEl.classList.remove("wp_ulike_is_getting_likers_list");
          }
          this._isFetchingLikers = false;
          if (response.success) {
            this._updateLikersMarkup(response.data);
          } else {
            this._updateLikersMarkup("");
          }
        }
      );
    },

    /**
     * Get all sibling wrapper elements that should have tooltips
     */
    _getAllTooltipElements() {
      const factorMethod =
        typeof this.settings.factor !== "undefined" && this.settings.factor
          ? `_${this.settings.factor}`
          : "";
      const buttonSelector = `.wp_${this.settings.type.toLowerCase()}${factorMethod}_btn_${this.settings.ID}`;
      const allSameButtons = document.querySelectorAll(buttonSelector);

      const wrapperElements = [];
      forEachElement(allSameButtons, (btn) => {
        const wrapper = btn.closest('.wpulike');
        if (wrapper && !wrapperElements.includes(wrapper)) {
          wrapperElements.push(wrapper);
        }
      });

      return wrapperElements.length > 0 ? wrapperElements : [this.element];
    },

    /**
     * init & update likers box
     * PRO VERSION: Added "pile" template check
     */
    _updateLikers(event) {
      if (this.settings.displayLikers) {
        // return on these conditions
        if (
          this.settings.likersTemplate === "popover" &&
          getDataAttribute(this.element, "ulike-tooltip")
        ) {
          return;
        } else if (
          ["default", "pile"].includes(this.settings.likersTemplate) &&
          this.likersElement &&
          (this.likersElement.length === undefined || this.likersElement.length > 0)
        ) {
          return;
        }

        // Handle popover tooltips
        if (this.settings.likersTemplate === "popover") {
          if (typeof WordpressUlikeTooltipPlugin !== "undefined") {
            const tooltipId = `${this.settings.type.toLowerCase()}-${this.settings.ID}`;

            // Create tooltip only for current element (not all siblings) to ensure correct hover behavior
            const currentInstance = window.WordpressUlikeTooltip && window.WordpressUlikeTooltip.getInstanceByElement
              ? window.WordpressUlikeTooltip.getInstanceByElement(this.element)
              : null;

            if (!currentInstance) {
              new WordpressUlikeTooltipPlugin(this.element, {
                id: tooltipId,
                position: "top",
                child: this.settings.generalSelector,
                theme: "white",
                size: "tiny",
                trigger: "hover",
                dataFetcher: (element, tooltipId) => {
                  // Don't set flag here - let _fetchLikersData handle it
                  // This prevents the flag from blocking the AJAX request
                  this._fetchLikersData();
                }
              });
            }
          }
        } else {
          // For default template, fetch data directly
          this._fetchLikersData();
        }

        if (event) {
          event.stopImmediatePropagation();
        }
        return false;
      }
    },

    /**
     * Update likers markup
     */
    _updateLikersMarkup(data) {
      if (this.settings.likersTemplate === "popover") {
        this.likersElement = this.element;
        const tooltipId = `${this.settings.type.toLowerCase()}-${this.settings.ID}`;

        const template = data && typeof data === 'object' ? data.template : data;
        const templateContent = template || "";

        const allTooltipElements = this._getAllTooltipElements();

        // Update content for all siblings (existing instances and pre-populate for future instances)
        forEachElement(allTooltipElements, (wrapperEl) => {
          // Update existing tooltip instances via events
          const updateEvent = new CustomEvent("tooltip-content-updated", {
            bubbles: true,
            detail: {
              element: wrapperEl,
              content: templateContent
            }
          });
          wrapperEl.dispatchEvent(updateEvent);
          document.dispatchEvent(updateEvent);

          // Pre-populate content for siblings that don't have tooltip instances yet
          // This ensures when they're hovered, content is already available
          let hiddenContent = wrapperEl.querySelector('[data-tooltip-content]');
          if (!hiddenContent) {
            hiddenContent = document.createElement("div");
            hiddenContent.setAttribute('data-tooltip-content', '');
            hiddenContent.setAttribute('data-tooltip-state', 'ready');
            hiddenContent.style.display = 'none';
            wrapperEl.appendChild(hiddenContent);
          }
          hiddenContent.innerHTML = templateContent;
          hiddenContent.setAttribute('data-tooltip-state', 'ready');
        });
      } else {
        // Handle both single element and NodeList/array (from _updateSameLikers)
        const hasLikersElement = this.likersElement &&
          (this.likersElement.length === undefined
            ? true
            : this.likersElement.length > 0);

        if (!hasLikersElement && data && data.template) {
          // If the likers container doesn't exist, create it
          const tempDiv = document.createElement("div");
          tempDiv.innerHTML = data.template;
          const newElement = tempDiv.firstElementChild;
          if (newElement) {
            this.element.appendChild(newElement);
            this.likersElement = newElement;
          }
        }

        // Update all likers elements (handles both single element and NodeList)
        if (this.likersElement) {
          const elementsToUpdate = this.likersElement.length !== undefined
            ? arrayFrom(this.likersElement)
            : [this.likersElement];

          // Handle data as object with template property, or as string/empty
          const template = (data && typeof data === 'object' && data.template)
            ? data.template
            : (typeof data === 'string' ? data : '');

          forEachElement(elementsToUpdate, (likersEl) => {
            if (!likersEl) return;
            if (template) {
              likersEl.style.display = "";
              likersEl.innerHTML = template;
            } else {
              likersEl.style.display = "none";
              likersEl.innerHTML = "";
            }
          });
        }
      }

      const template = data && typeof data === 'object' ? data.template : data;
      triggerEvent(document, "WordpressUlikeLikersMarkupUpdated", [
        this.likersElement,
        this.settings.likersTemplate,
        template
      ]);
    },

    /**
     * Update the elements of same buttons at the same time
     */
    _updateSameButtons() {
      // Get buttons with same unique class names
      const factorMethod =
        typeof this.settings.factor !== "undefined" && this.settings.factor
          ? `_${this.settings.factor}`
          : "";
      const selector = `.wp_${this.settings.type.toLowerCase()}${factorMethod}_btn_${this.settings.ID}`;
      this.sameButtons = document.querySelectorAll(selector);
      // Update general elements (only when there are multiple same buttons)
      if (this.sameButtons.length > 1) {
        this.buttonElement = this.sameButtons;
        // Get general elements for all buttons (like jQuery .closest() on collection)
        const generalElements = [];
        forEachElement(this.sameButtons, (btn) => {
          const genEl = btn.closest(this.settings.generalSelector);
          if (genEl) {
            generalElements.push(genEl);
          }
        });
        this.generalElement = generalElements.length === 1 ? generalElements[0] : generalElements;
        // Get counter elements from all general elements (like jQuery .find() on collection)
        const counterElements = [];
        forEachElement(generalElements, (genEl) => {
          const counters = genEl.querySelectorAll(this.settings.counterSelector);
          forEachElement(counters, (counter) => {
            counterElements.push(counter);
          });
        });
        this.counterElement = counterElements;
      }
    },

    /**
     * Update the elements of same likers at the same time
     */
    _updateSameLikers() {
      const selector = `.wp_${this.settings.type.toLowerCase()}_likers_${this.settings.ID}`;
      this.sameLikers = document.querySelectorAll(selector);
      // Update general elements
      if (this.sameLikers.length > 1) {
        this.likersElement = this.sameLikers;
      }
    },

    /**
     * Get likers wrapper element
     */
    _getLikersElement() {
      return this.likersElement;
    },

    /**
     * Control actions
     * PRO VERSION: Enhanced with factor-based button text handling (up/down)
     */
    _updateButton(btnText, status) {
      forEachElement(this.buttonElement, (buttonEl) => {
        if (!buttonEl) return;

        if (buttonEl.classList.contains("wp_ulike_put_image")) {
          if (status === 4) {
            buttonEl.classList.add("image-unlike", "wp_ulike_btn_is_active");
          } else {
            buttonEl.classList.toggle("image-unlike");
            buttonEl.classList.toggle("wp_ulike_btn_is_active");
          }
        } else if (
          buttonEl.classList.contains("wp_ulike_put_text") &&
          btnText !== null
        ) {
          const span = buttonEl.querySelector("span");
          if (span) {
            // PRO VERSION: Handle factor-based button text (up/down)
            if (typeof btnText === "object" && btnText !== null) {
              if (this.settings.factor === "down") {
                span.innerHTML = btnText.down;
                if (this.siblingElement && this.siblingElement.length) {
                  forEachElement(this.siblingElement, (sibling) => {
                    const siblingBtn = sibling.querySelector(this.settings.buttonSelector);
                    if (siblingBtn) {
                      const siblingSpan = siblingBtn.querySelector("span");
                      if (siblingSpan) {
                        siblingSpan.innerHTML = btnText.up;
                      }
                    }
                  });
                }
              } else {
                span.innerHTML = btnText.up;
                if (this.siblingElement && this.siblingElement.length) {
                  forEachElement(this.siblingElement, (sibling) => {
                    const siblingBtn = sibling.querySelector(this.settings.buttonSelector);
                    if (siblingBtn) {
                      const siblingSpan = siblingBtn.querySelector("span");
                      if (siblingSpan) {
                        siblingSpan.innerHTML = btnText.down;
                      }
                    }
                  });
                }
              }
            } else {
              span.innerHTML = btnText;
            }
          }
        }
      });

      // Update sibling buttons (remove active state from siblings)
      if (this.siblingElement && this.siblingElement.length) {
        forEachElement(this.siblingElement, (sibling) => {
          const siblingBtn = sibling.querySelector(this.settings.buttonSelector);
          if (siblingBtn) {
            siblingBtn.classList.remove("image-unlike", "wp_ulike_btn_is_active");
          }
        });
      }
      if (this.siblingButton && this.siblingButton.length) {
        forEachElement(this.siblingButton, (siblingBtn) => {
          siblingBtn.classList.remove("image-unlike", "wp_ulike_btn_is_active");
        });
      }
    },

    /**
     * Send notification by 'WordpressUlikeNotifications' plugin
     */
    _sendNotification(messageType, messageText) {
      if (typeof WordpressUlikeNotifications !== "undefined") {
        new WordpressUlikeNotifications(document.body, {
          messageType,
          messageText,
        });
      }
    },

    /**
     * Track button view when it becomes visible
     * Uses Intersection Observer for performance and global tracking to prevent duplicates
     */
    _trackButtonView() {
      // Get button info
      const itemId = this.settings.ID;
      const type = this.settings.type;

      if (!itemId || !type) {
        return;
      }

      // Check if view tracking is enabled for this content type
      const viewTrackingConfig = (typeof UlikeProCommonConfig !== 'undefined' && UlikeProCommonConfig.ViewTracking)
        ? UlikeProCommonConfig.ViewTracking
        : null;

      if (viewTrackingConfig && viewTrackingConfig.enabledTypes) {
        if (!viewTrackingConfig.enabledTypes.includes(type)) {
          // View tracking is disabled for this content type
          return;
        }
      }

      // Check if already tracked globally (prevents duplicate tracking for sibling buttons)
      if (ViewTrackingSystem.isTracked(itemId, type)) {
        return;
      }

      // Get AJAX config
      const ajaxUrl = (typeof UlikeProCommonConfig !== 'undefined' && UlikeProCommonConfig.AjaxUrl)
        ? UlikeProCommonConfig.AjaxUrl
        : (typeof wp_ulike_params !== 'undefined' ? wp_ulike_params.ajax_url : '');

      const nonce = (typeof UlikeProCommonConfig !== 'undefined' && UlikeProCommonConfig.Nonce)
        ? UlikeProCommonConfig.Nonce
        : (typeof wp_ulike_params !== 'undefined' ? wp_ulike_params.nonce : '');

      if (!ajaxUrl || !nonce) {
        return;
      }

      // Track view function (queues for batching)
      const trackView = () => {
        // Don't track in preview/edit mode
        if (document.body.classList.contains('elementor-editor-active') ||
            document.body.classList.contains('block-editor-page') ||
            window.location.search.indexOf('preview=true') !== -1) {
          return;
        }

        // Queue view for batching (prevents duplicate requests for same item)
        ViewTrackingSystem.queueView(itemId, type, ajaxUrl, nonce);

        // Mark element as tracked locally
        if (this.element.setAttribute) {
          this.element.setAttribute('data-view-tracked', 'true');
        }
      };

      // Use Intersection Observer if available (better performance)
      if ('IntersectionObserver' in window) {
        // Create observer with optimal settings for button visibility
        const observer = new IntersectionObserver((entries) => {
          entries.forEach((entry) => {
            // Only track when button is actually visible (not just intersecting)
            if (entry.isIntersecting && entry.intersectionRatio > 0) {
              trackView();
              // Stop observing after first view
              observer.disconnect();
            }
          });
        }, {
          threshold: [0.1, 0.5], // Trigger at 10% and 50% visibility
          rootMargin: '0px' // Only track when actually in viewport (no pre-tracking)
        });

        // Observe the general element (button wrapper) or the element itself
        const firstGeneralEl = this.generalElement.length > 0 ? this.generalElement[0] : null;
        const targetElement = firstGeneralEl || this.element;

        if (targetElement) {
          observer.observe(targetElement);

          // Store observer for cleanup
          this._viewObserver = observer;
        }
      } else {
        // Fallback for older browsers - track immediately (but still queue for batching)
        trackView();
      }
    },

    /**
     * Cleanup method to prevent memory leaks
     */
    destroy() {
      // Remove all event listeners
      this._boundHandlers.forEach(({ element, event, handler }) => {
        if (element && element.length !== undefined) {
          forEachElement(element, (el) => {
            if (el) el.removeEventListener(event, handler);
          });
        } else if (element) {
          element.removeEventListener(event, handler);
        }
      });
      this._boundHandlers = [];

      // Clear all timeouts
      this._timeouts.forEach((timeoutId) => {
        clearTimeout(timeoutId);
      });
      this._timeouts = [];

      // Disconnect view observer if exists
      if (this._viewObserver) {
        this._viewObserver.disconnect();
        this._viewObserver = null;
      }

      // Reset flags
      this._isFetchingLikers = false;
    },
  };

  // Global view tracking system (prevents duplicates and batches requests)
  const ViewTrackingSystem = (function() {
    // Track which items have been tracked (itemId_type as key)
    const trackedItems = new Set();

    // Queue for batching requests
    const viewQueue = [];
    let batchTimeout = null;
    const BATCH_DELAY = 2000; // 2 seconds - batch requests together
    const MAX_BATCH_SIZE = 10; // Send batch when queue reaches this size

    /**
     * Generate unique key for item
     */
    function getItemKey(itemId, type) {
      return `${itemId}_${type}`;
    }

    /**
     * Check if item is already tracked
     */
    function isTracked(itemId, type) {
      return trackedItems.has(getItemKey(itemId, type));
    }

    /**
     * Mark item as tracked
     */
    function markAsTracked(itemId, type) {
      trackedItems.add(getItemKey(itemId, type));
    }

    /**
     * Add view to queue for batching
     */
    function queueView(itemId, type, ajaxUrl, nonce) {
      const key = getItemKey(itemId, type);

      // Check if already in queue
      const existingIndex = viewQueue.findIndex(item => item.key === key);
      if (existingIndex !== -1) {
        // Already queued, skip
        return;
      }

      viewQueue.push({
        key: key,
        itemId: itemId,
        type: type,
        ajaxUrl: ajaxUrl,
        nonce: nonce
      });

      // Send batch if queue is full
      if (viewQueue.length >= MAX_BATCH_SIZE) {
        sendBatch();
      } else {
        // Schedule batch send
        scheduleBatch();
      }
    }

    /**
     * Schedule batch send
     */
    function scheduleBatch() {
      if (batchTimeout) {
        clearTimeout(batchTimeout);
      }

      batchTimeout = setTimeout(() => {
        sendBatch();
      }, BATCH_DELAY);
    }

    /**
     * Send batched views
     */
    function sendBatch() {
      if (batchTimeout) {
        clearTimeout(batchTimeout);
        batchTimeout = null;
      }

      if (viewQueue.length === 0) {
        return;
      }

      // Get unique items from queue (in case of duplicates)
      const uniqueItems = [];
      const seen = new Set();

      viewQueue.forEach(item => {
        if (!seen.has(item.key)) {
          seen.add(item.key);
          uniqueItems.push(item);
        }
      });

      // Clear queue
      viewQueue.length = 0;

      // Send batch request
      if (uniqueItems.length > 0) {
        const formData = new FormData();
        formData.append('action', 'ulp_track_view_batch');
        formData.append('nonce', uniqueItems[0].nonce);
        formData.append('items', JSON.stringify(
          uniqueItems.map(item => ({
            id: item.itemId,
            type: item.type
          }))
        ));

        // Mark all as tracked
        uniqueItems.forEach(item => {
          markAsTracked(item.itemId, item.type);
        });

        // Send request (fire and forget)
        fetch(uniqueItems[0].ajaxUrl, {
          method: 'POST',
          body: formData,
          keepalive: true
        }).catch(() => {
          // Silently fail - tracking should not break user experience
        });
      }
    }

    /**
     * Flush queue on page unload
     */
    function setupUnloadHandler() {
      // Flush queue when page is about to unload
      window.addEventListener('beforeunload', () => {
        sendBatch();
      });

      // Also flush on page visibility change (when user switches tabs)
      document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
          sendBatch();
        }
      });
    }

    // Setup unload handler
    if (typeof window !== 'undefined') {
      setupUnloadHandler();
    }

    /**
     * Public API
     */
    return {
      isTracked: isTracked,
      queueView: queueView,
      sendBatch: sendBatch // Expose for manual flush if needed
    };
  })();

  // Expose plugin to window for global access
  window[pluginName] = Plugin;

  // Expose as jQuery plugin for backward compatibility
  if (typeof jQuery !== 'undefined' && jQuery && jQuery.fn) {
    jQuery.fn[pluginName] = function (options) {
      return this.each(function () {
        if (!this.hasAttribute || !this.hasAttribute("data-ulike-initialized")) {
          new Plugin(this, options);
          if (this.setAttribute) {
            this.setAttribute("data-ulike-initialized", "true");
          }
        }
      });
    };
  }
})(window, document);


/* ================== public/assets/js/src/scripts.js =================== */


/* Run :) */
(function (window, document, undefined) {
  "use strict";

  // Helper function to trigger custom events
  const triggerEvent = (element, eventName, data) => {
    const event = new CustomEvent(eventName, {
      bubbles: true,
      cancelable: true,
      detail: data
    });

    element.dispatchEvent(event);
  };

  // Helper function to get data attribute value
  const getDataAttribute = (element, name) => {
    const camelName = name.replace(/-([a-z])/g, (g) => g[1].toUpperCase());
    if (element.dataset && element.dataset[camelName] !== undefined) {
      return element.dataset[camelName];
    }
    const value = element.getAttribute(`data-${name}`);
    if (value === null) {
      return undefined;
    }
    if (value === "true") return true;
    if (value === "false") return false;
    if (value === "" || value === "null") return null;
    if (!isNaN(value) && value !== "") return Number(value);
    return value;
  };

  // Helper function to find elements within a node (like jQuery's find)
  const findInNodes = (nodes, selector) => {
    const results = [];
    const nodeList = nodes.length !== undefined ? nodes : [nodes];
    
    for (let i = 0; i < nodeList.length; i++) {
      const node = nodeList[i];
      if (node.nodeType === 1) { // Element node
        // Check if the node itself matches
        if (node.matches && node.matches(selector)) {
          results.push(node);
        }
        // Find children
        const children = node.querySelectorAll ? node.querySelectorAll(selector) : [];
        for (let j = 0; j < children.length; j++) {
          results.push(children[j]);
        }
      }
    }
    
    return results;
  };

  // Helper function to initialize WordpressUlikeAjaxForms
  const initWordpressUlikeAjaxForms = (elements) => {
    if (!elements || (elements.length !== undefined && elements.length === 0)) return;
    
    const elementList = elements.length !== undefined ? Array.from(elements) : [elements];
    
    elementList.forEach((element) => {
      if (element && element.nodeType === 1) {
        if (typeof WordpressUlikeAjaxForms !== "undefined" && typeof WordpressUlikeAjaxForms === "function") {
          new WordpressUlikeAjaxForms(element);
        }
      }
    });
  };

  // Helper function to initialize WordpressUlike
  const initWordpressUlike = (elements) => {
    if (!elements || (elements.length !== undefined && elements.length === 0)) return;
    
    const elementList = elements.length !== undefined ? Array.from(elements) : [elements];
    
    elementList.forEach((element) => {
      if (element && element.nodeType === 1) {
        // Check if already initialized
        if (element.hasAttribute && element.hasAttribute("data-ulike-initialized")) {
          return;
        }
        
        if (typeof WordpressUlike !== "undefined" && typeof WordpressUlike === "function") {
          new WordpressUlike(element);
        }
        
        // Mark as initialized
        if (element.setAttribute) {
          element.setAttribute("data-ulike-initialized", "true");
        }
      }
    });
  };

  // Helper function for fade out animation
  const fadeOut = (element, callback) => {
    if (!element) return;
    
    element.style.transition = "opacity 300ms";
    element.style.opacity = "0";
    
    setTimeout(() => {
      element.style.display = "none";
      if (callback) callback();
    }, 300);
  };

  /**
   * Enhanced OTP digit input handler with improved UX
   * Supports: paste, arrow keys, backspace, auto-focus, visual feedback
   */
  // Store initialized OTP containers to prevent duplicate initialization
  const initializedOtpContainers = new WeakSet();

  function ulpOtpInput(container) {
    // Support both container element and global selector
    const targetContainer = container || document;
    const containerEl = targetContainer.querySelector ? 
      targetContainer.querySelector("#ulp-2fa-code") : 
      (targetContainer.id === "ulp-2fa-code" ? targetContainer : null);
    
    if (!containerEl) {
      // Fallback: try to find all 2FA code containers
      const containers = document.querySelectorAll("#ulp-2fa-code");
      containers.forEach(cont => {
        if (!initializedOtpContainers.has(cont)) {
          initializeOtpContainer(cont);
        }
      });
      return;
    }

    // Skip if already initialized
    if (initializedOtpContainers.has(containerEl)) {
      return;
    }

    initializeOtpContainer(containerEl);
  }

  function initializeOtpContainer(containerEl) {
    const inputs = Array.from(containerEl.querySelectorAll("input.ulp-digit-input"));
    
    if (!inputs || inputs.length === 0) return;

    // Mark as initialized
    initializedOtpContainers.add(containerEl);

    // Helper function to clear error states
    const clearErrors = () => {
      containerEl.classList.remove("ulp-otp-error");
      inputs.forEach(inp => inp.classList.remove("ulp-digit-error"));
    };

    // Handle paste event on container
    containerEl.addEventListener("paste", (e) => {
        e.preventDefault();
        clearErrors();
        const pastedData = (e.clipboardData || window.clipboardData).getData("text").trim();
        const digits = pastedData.replace(/\D/g, "").slice(0, inputs.length);
        
        // Visual feedback: briefly highlight all inputs being filled
        containerEl.classList.add("ulp-otp-pasting");
        setTimeout(() => containerEl.classList.remove("ulp-otp-pasting"), 300);
        
        digits.split("").forEach((digit, index) => {
          if (inputs[index]) {
            inputs[index].value = digit;
            inputs[index].classList.add("ulp-digit-filled");
          }
        });
        
        // If all digits pasted, blur last input and auto-submit
        if (digits.length === inputs.length) {
          const lastInput = inputs[inputs.length - 1];
          if (lastInput) {
            lastInput.blur();
            containerEl.classList.add("ulp-otp-complete");
            
            // Auto-submit after short delay
            setTimeout(() => {
              const form = lastInput.closest("form");
              if (form) {
                form.requestSubmit();
              }
            }, 400);
          }
        } else {
          // Focus the next empty input
          const nextEmptyIndex = Math.min(digits.length, inputs.length - 1);
          if (inputs[nextEmptyIndex]) {
            inputs[nextEmptyIndex].focus();
          }
        }
      });

    // Handle input for each digit field
    inputs.forEach((input, index) => {
      // Add visual feedback class when input has value
      const updateVisualState = () => {
        if (input.value) {
          input.classList.add("ulp-digit-filled");
        } else {
          input.classList.remove("ulp-digit-filled");
        }
      };

      // Handle keydown events
      input.addEventListener("keydown", (event) => {
        // Handle backspace
        if (event.key === "Backspace") {
          containerEl.classList.remove("ulp-otp-complete");
          clearErrors();
          
          if (input.value) {
            input.value = "";
            updateVisualState();
          } else if (index > 0) {
            inputs[index - 1].focus();
            inputs[index - 1].value = "";
            updateVisualState();
          }
          event.preventDefault();
          return;
        }

        // Handle arrow keys
        if (event.key === "ArrowLeft" && index > 0) {
          inputs[index - 1].focus();
          event.preventDefault();
          return;
        }
        if (event.key === "ArrowRight" && index < inputs.length - 1) {
          inputs[index + 1].focus();
          event.preventDefault();
          return;
        }

        // Handle delete key
        if (event.key === "Delete") {
          input.value = "";
          updateVisualState();
          event.preventDefault();
          return;
        }

        // Handle Enter key - submit form if all fields are filled
        if (event.key === "Enter") {
          const allFilled = inputs.every(inp => inp.value);
          if (allFilled) {
            const form = input.closest("form");
            if (form) {
              form.requestSubmit();
            }
          }
          event.preventDefault();
          return;
        }

        // Handle numeric input (0-9)
        if (/^[0-9]$/.test(event.key)) {
          clearErrors();
          input.value = event.key;
          updateVisualState();
          
          // Move to next input if not the last one
          if (index < inputs.length - 1) {
            inputs[index + 1].focus();
          } else {
            // Last digit entered - blur input and auto-submit after brief delay
            // This matches behavior of Google, GitHub, Microsoft, and other major services
            input.blur();
            
            // Add completion class to container for visual feedback
            containerEl.classList.add("ulp-otp-complete");
            
            // Auto-submit after short delay (allows user to see completion)
            const allFilled = inputs.every(inp => inp.value);
            if (allFilled) {
              setTimeout(() => {
                const form = input.closest("form");
                if (form) {
                  // Use requestSubmit to trigger form validation if needed
                  form.requestSubmit();
                }
              }, 400); // 400ms delay - standard for major services
            }
          }
          event.preventDefault();
          return;
        }

        // Block non-numeric characters
        if (!/^(Backspace|Delete|ArrowLeft|ArrowRight|Tab|Enter)$/.test(event.key) && 
            !event.ctrlKey && !event.metaKey) {
          event.preventDefault();
        }
      });

      // Handle input event for paste and other input methods
      input.addEventListener("input", (event) => {
        clearErrors();
        const value = event.target.value;
        // Only allow single digit
        if (value.length > 1) {
          const lastDigit = value.slice(-1);
          input.value = /^[0-9]$/.test(lastDigit) ? lastDigit : "";
        } else if (value && !/^[0-9]$/.test(value)) {
          input.value = "";
        }
        updateVisualState();
        
        // Auto-advance if valid digit entered
        if (input.value && index < inputs.length - 1) {
          inputs[index + 1].focus();
        }
      });

      // Handle focus - select text for easy replacement
      input.addEventListener("focus", () => {
        input.select();
        input.classList.add("ulp-digit-focused");
        containerEl.classList.remove("ulp-otp-complete");
        clearErrors();
      });

      // Handle blur
      input.addEventListener("blur", () => {
        input.classList.remove("ulp-digit-focused");
      });

      // Initialize visual state
      updateVisualState();
    });

    // Auto-focus first input if all are empty
    const allEmpty = inputs.every(input => !input.value);
    if (allEmpty && inputs[0]) {
      // Small delay to ensure DOM is ready
      setTimeout(() => {
        inputs[0].focus();
      }, 100);
    }
  }

  /**
   * vanilla js detecting div of certain class has been added to DOM
   */
  function ulpOnElementInserted(containerSelector, elementSelector, callback) {
    const onMutationsObserved = (mutations) => {
      mutations.forEach((mutation) => {
        if (mutation.addedNodes.length) {
          // Find elements matching selector in added nodes
          const elements = findInNodes(mutation.addedNodes, elementSelector);
          elements.forEach((element) => {
            callback(element);
          });
        }
      });
    };

    const target = document.querySelector(containerSelector);
    if (!target) return;

    const config = {
      childList: true,
      subtree: true,
    };
    const MutationObserver =
      window.MutationObserver || window.WebKitMutationObserver;
    if (!MutationObserver) return;

    const observer = new MutationObserver(onMutationsObserved);
    observer.observe(target, config);
  }

  // Initialize on DOM ready
  const init = () => {
    // Init share buttons / forms
    const ajaxForms = document.querySelectorAll(".ulp-ajax-form");
    initWordpressUlikeAjaxForms(ajaxForms);

    // Init goodshare
    if (typeof window._goodshare !== "undefined" && window._goodshare.reNewAllInstance) {
      window._goodshare.reNewAllInstance();
    }

    // Init ulike buttons
    const ulikeElements = document.querySelectorAll(".wpulike");
    initWordpressUlike(ulikeElements);

    // Init OTP inputs
    ulpOtpInput();
  };

  // Run on DOM ready
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }

  // On wp ulike element added
  ulpOnElementInserted("body", ".wpulike", function (element) {
    initWordpressUlike(element);
  });

  // On share button element added
  ulpOnElementInserted("body", ".ulp-social-wrapper", function (element) {
    // Init goodshare
    if (typeof window._goodshare !== "undefined" && window._goodshare.reNewAllInstance) {
      window._goodshare.reNewAllInstance();
    }
  });

  // On form element added
  ulpOnElementInserted("body", ".ulp-ajax-form", function (element) {
    initWordpressUlikeAjaxForms(element);
    // Initialize OTP inputs in the new form
    ulpOtpInput(element);
  });

  // Handle OTP form submission errors
  document.addEventListener("UlpAjaxFormEnded", (event) => {
    const response = event.detail?.[1];
    if (!response || response.success) return;

    const msg = response.data?.message?.toLowerCase() || "";
    const otpKeywords = ["otp", "one-time password", "tfa", "two factor", "verification code", "incorrect"];
    if (!otpKeywords.some(keyword => msg.includes(keyword))) return;

    const formElement = event.detail?.[0];
    if (!formElement) return;

    formElement.querySelectorAll("#ulp-2fa-code").forEach((container) => {
      container.classList.remove("ulp-otp-complete");
      container.classList.add("ulp-otp-error");
      container.querySelectorAll("input.ulp-digit-input").forEach((input) => {
        input.classList.add("ulp-digit-error");
        input.classList.remove("ulp-digit-filled", "ulp-digit-success");
      });
    });
  });

  // On recaptcha element added
  ulpOnElementInserted("body", ".ulp-recaptcha-field", function (element) {
    triggerEvent(document, "UlpRecaptchaReload", element);
  });

  // On 2FA code container added
  ulpOnElementInserted("body", "#ulp-2fa-code", function (element) {
    ulpOtpInput(element);
  });

  // Handle 2FA remove button clicks
  document.addEventListener("click", (e) => {
    const removeButton = e.target.closest(".ulp-2fa-remove");
    if (!removeButton) return;

    e.preventDefault();
    if (confirm("Are you sure you want to make this change?")) {
      const itemElement = removeButton.closest(".ulp-2fa-item");
      const nonce = getDataAttribute(removeButton, "nonce");
      const key = getDataAttribute(removeButton, "key");

      const formData = new FormData();
      formData.append("action", "ulp_two_factor_remove");
      formData.append("nonce", nonce);
      formData.append("key", key);

      fetch(UlikeProCommonConfig.AjaxUrl, {
        method: "POST",
        body: formData,
      })
        .then((response) => response.json())
        .then((response) => {
          if (response.success && itemElement) {
            fadeOut(itemElement);
          }
          // Display Notification
          if (typeof WordpressUlikeNotifications !== "undefined") {
            new WordpressUlikeNotifications(document.body, {
              messageType: response.data.status,
              messageText: response.data.message,
            });
          }
        })
        .catch((error) => {
          console.error("WP ULike Pro 2FA Remove error:", error);
        });
    }
  });

  // Handle form toggle clicks
  document.addEventListener("click", (e) => {
    const toggleLink = e.target.closest("a[data-form-toggle]");
    if (!toggleLink) return;

    e.preventDefault();
    const contentEl = toggleLink.closest(".ulp-ajax-form");
    if (!contentEl) return;

    const formEl = contentEl.querySelector("form");
    if (!formEl) return;

    formEl.classList.add("ulp-loading");

    const request = getDataAttribute(toggleLink, "form-toggle");
    const formData = new FormData();
    formData.append("action", "ulp_forms_toggle");
    formData.append("request", request);

    fetch(UlikeProCommonConfig.AjaxUrl, {
      method: "POST",
      body: formData,
    })
      .then((response) => response.json())
      .then((response) => {
        formEl.classList.remove("ulp-loading");

        if (response.success) {
          // Create temporary container to parse HTML
          const tempDiv = document.createElement("div");
          tempDiv.innerHTML = response.data.content;
          const fragmentEl = tempDiv.firstElementChild;

          if (fragmentEl) {
            // Initialize WordpressUlikeAjaxForms on new element
            initWordpressUlikeAjaxForms(fragmentEl);
            // Replace content element
            if (contentEl.parentNode) {
              contentEl.parentNode.replaceChild(fragmentEl, contentEl);
            }
            // Initialize OTP inputs in the new form (with small delay to ensure DOM is ready)
            setTimeout(() => {
              ulpOtpInput(fragmentEl);
            }, 50);
          }
        } else {
          // Display Notification
          if (typeof WordpressUlikeNotifications !== "undefined") {
            new WordpressUlikeNotifications(document.body, {
              messageType: response.data.status,
              messageText: response.data.message,
            });
          }
        }
      })
      .catch((error) => {
        console.error("WP ULike Pro Form Toggle error:", error);
        formEl.classList.remove("ulp-loading");
      });
  });

  // Expose ulpOtpInput globally for use in other scripts
  window.ulpOtpInput = ulpOtpInput;

})(window, document);