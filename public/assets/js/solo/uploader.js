/**
 * Beautiful Avatar Uploader
 * Lightweight, user-friendly avatar upload with cropping
 * Inspired by Twitter/Facebook avatar uploads
 */
(function() {
  'use strict';

  // Get defaults from WordPress localized config or use fallback
  const getDefaults = () => {
    const config = typeof fileUploaderCommonConfig !== 'undefined' && fileUploaderCommonConfig.avatarConfig
      ? fileUploaderCommonConfig.avatarConfig
      : {};

    return {
      maxSize: config.maxSize || 5 * 1024 * 1024, // 5MB default
      minWidth: config.minWidth || 256,
      minHeight: config.minHeight || 256,
      cropSize: config.cropSize || 512,
      quality: config.quality || 0.92,
      ajaxUrl: '',
      nonce: '',
      captions: config.captions || {
        changePhoto: 'Change photo',
        removePhoto: 'Remove photo',
        save: 'Save',
        cancel: 'Cancel'
      },
      messages: config.messages || {
        chooseProfilePicture: 'Choose profile picture',
        confirmRemoveAvatar: 'Are you sure you want to remove your avatar?',
        pleaseSelectImage: 'Please select an image file',
        fileSizeMustBeLess: 'File size must be less than %sMB',
        imageMustBeAtLeast: 'Image must be at least %sx%s pixels',
        modalSystemNotAvailable: 'Modal system not available',
        failedToProcessImage: 'Failed to process image',
        uploadUrlNotConfigured: 'Upload URL not configured',
        avatarUploadedSuccessfully: 'Avatar uploaded successfully',
        uploadFailed: 'Upload failed',
        invalidResponseFromServer: 'Invalid response from server',
        avatarRemoved: 'Avatar removed',
        failedToRemoveAvatar: 'Failed to remove avatar'
      }
    };
  };

  class AvatarUploader {
    constructor(container, options = {}) {
      this.container = typeof container === 'string'
        ? document.querySelector(container)
        : container;

      if (!this.container) return;

      // Helper to check if avatar is user-uploaded (not Gravatar/default)
      this.isUserUploaded = (url) => {
        if (!url) return false;
        return !url.includes('gravatar') &&
               !url.includes('wp.com/avatar') &&
               !url.includes('data:image/svg') &&
               !url.includes('secure.gravatar.com');
      };

      // Get defaults with localized config
      const defaults = getDefaults();

      // Merge with localized config from fileUploaderCommonConfig if available
      if (typeof fileUploaderCommonConfig !== 'undefined') {
        defaults.ajaxUrl = fileUploaderCommonConfig.AjaxUrl || defaults.ajaxUrl;
        defaults.nonce = fileUploaderCommonConfig.Nonce || defaults.nonce;
      }

      this.options = { ...defaults, ...options };
      this.currentFile = null;
      this.init();
    }

    init() {
      const input = this.container.querySelector('input[type="file"]');
      if (!input) return;

      // Hide input immediately to prevent flash
      input.style.display = 'none';

      this.fileInput = input;
      this.options.ajaxUrl = input.dataset.ajaxUrl || this.options.ajaxUrl;
      this.options.nonce = input.dataset.nonce || this.options.nonce;

      // Store original WordPress default avatar (Gravatar URL) - this is what we'll show after removal
      this.originalDefaultAvatar = input.dataset.default || '';

      // Get existing avatar data - if user has uploaded avatar, use that, otherwise use default
      try {
        const existingData = input.dataset.files;
        if (existingData) {
          const files = JSON.parse(existingData);
          if (files[0]?.url) {
            this.options.defaultAvatar = files[0].url;
          } else {
            this.options.defaultAvatar = this.originalDefaultAvatar;
          }
        } else {
          this.options.defaultAvatar = this.originalDefaultAvatar;
        }
      } catch (e) {
        this.options.defaultAvatar = this.originalDefaultAvatar;
      }

      this.createUI();
      this.bindEvents();
      // Mark container as initialized immediately after creating UI to hide CSS skeleton
      this.container.classList.add('ulp-avatar-initialized');
      this.loadExistingAvatar();
    }

    createUI() {
      const defaultPlaceholder = 'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\'%3E%3Cpath fill=\'%23ccc\' d=\'M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z\'/%3E%3C/svg%3E';
      const avatarSrc = this.options.defaultAvatar || defaultPlaceholder;

      const wrapper = document.createElement('div');
      wrapper.className = 'ulp-avatar-uploader';
      // Escape single quotes in defaultPlaceholder for use in JavaScript string within HTML attribute
      const escapedPlaceholder = defaultPlaceholder.replace(/'/g, "\\'");
      wrapper.innerHTML = `
        <div class="ulp-avatar-container">
          <div class="ulp-avatar-preview">
            <img class="ulp-avatar-image" src="" alt="Avatar" style="opacity: 0;" onerror="this.onerror=null; this.src='${escapedPlaceholder}'">
            <div class="ulp-avatar-overlay" role="presentation">
              <div class="ulp-avatar-overlay-content">
                <svg class="ulp-avatar-icon" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                  <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                  <circle cx="12" cy="13" r="4"></circle>
                </svg>
                <span class="ulp-avatar-text">${this.options.captions.changePhoto}</span>
              </div>
            </div>
            <div class="ulp-avatar-success-overlay" style="display: none;" aria-hidden="true">
              <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 6L9 17l-5-5" stroke-dasharray="24" stroke-dashoffset="24"/>
              </svg>
            </div>
            <button type="button" class="ulp-avatar-remove-btn" title="${this.options.captions.removePhoto}" aria-label="${this.options.captions.removePhoto}" style="display: none;">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
              </svg>
            </button>
          </div>
          <div class="ulp-avatar-progress" style="display: none;">
            <div class="ulp-avatar-progress-ring">
              <svg viewBox="0 0 100 100">
                <circle class="ulp-progress-track" cx="50" cy="50" r="45"></circle>
                <circle class="ulp-progress-bar" cx="50" cy="50" r="45"></circle>
              </svg>
            </div>
            <div class="ulp-avatar-progress-text">0%</div>
          </div>
        </div>
      `;

      this.container.appendChild(wrapper);
      this.wrapper = wrapper;

      // Find the inner avatar-container (created in wrapper) and mark it as initialized
      const innerContainer = wrapper.querySelector('.ulp-avatar-container');
      if (innerContainer) {
        innerContainer.classList.add('ulp-avatar-initialized');
      }

      // Also mark outer container as initialized
      this.container.classList.add('ulp-avatar-initialized');

      this.preview = wrapper.querySelector('.ulp-avatar-preview');
      this.image = wrapper.querySelector('.ulp-avatar-image');
      this.overlay = wrapper.querySelector('.ulp-avatar-overlay');
      this.progress = wrapper.querySelector('.ulp-avatar-progress');
      this.removeBtn = wrapper.querySelector('.ulp-avatar-remove-btn');
      this.successOverlay = wrapper.querySelector('.ulp-avatar-success-overlay');

      // Handle image load
      this.setupImageLoading();
    }

    setupImageLoading() {
      if (!this.image) return;

      // Get the source to load
      const imageSrc = this.options.defaultAvatar || '';
      const defaultPlaceholder = 'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\'%3E%3Cpath fill=\'%23ccc\' d=\'M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z\'/%3E%3C/svg%3E';

      // Preload image before showing it
      const img = new Image();
      img.onload = () => {
        // Defer DOM updates to avoid blocking load event
        requestAnimationFrame(() => {
          // Image is loaded, show it (CSS skeleton auto-hides via :has() selector)
          this.image.src = img.src;
          this.image.classList.add('loaded');
          this.image.style.opacity = '1';

          // Check if avatar is user-uploaded to show/hide remove button
          if (this.removeBtn) {
            this.removeBtn.style.display = this.isUserUploaded(img.src) ? '' : 'none';
          }
        });
      };
      img.onerror = () => {
        // On error, show placeholder
        this.image.src = defaultPlaceholder;
        this.image.classList.add('loaded');
        this.image.style.opacity = '1';
        if (this.removeBtn) {
          this.removeBtn.style.display = 'none';
        }
      };

      // Start loading the image
      if (imageSrc) {
        img.src = imageSrc;
      } else {
        // No image to load, show placeholder
        this.image.src = defaultPlaceholder;
        this.image.classList.add('loaded');
        this.image.style.opacity = '1';
        if (this.removeBtn) {
          this.removeBtn.style.display = 'none';
        }
      }
    }

    bindEvents() {
      // File input change
      this.fileInput.addEventListener('change', (e) => {
        const file = e.target.files[0];
        if (file) {
          this.handleFile(file);
          e.target.value = '';
        }
      });

      // Click to upload (but not on remove button)
      this.preview.addEventListener('click', (e) => {
        if (!e.target.closest('.ulp-avatar-remove-btn')) {
          this.fileInput.click();
        }
      });

      // Keyboard support
      this.preview.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          if (!e.target.closest('.ulp-avatar-remove-btn')) {
            this.fileInput.click();
          }
        }
      });

      // Make preview keyboard accessible
      this.preview.setAttribute('tabindex', '0');
      this.preview.setAttribute('role', 'button');
      this.preview.setAttribute('aria-label', this.options.captions.changePhoto);

      // Remove button
      this.removeBtn?.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        if (confirm(this.options.messages.confirmRemoveAvatar)) {
          this.removeAvatar();
        }
      });
    }

    handleFile(file) {
      // Validate
      if (!file.type.startsWith('image/')) {
        this.showNotification('error', this.options.messages.pleaseSelectImage);
        return;
      }

      if (file.size > this.options.maxSize) {
        const maxMB = Math.round(this.options.maxSize / 1024 / 1024);
        this.showNotification('error', this.options.messages.fileSizeMustBeLess.replace('%s', maxMB));
        return;
      }

      // Load image
      const reader = new FileReader();
      reader.onload = (e) => {
        const img = new Image();
        img.onload = () => {
          // Quick validation first (synchronous, lightweight)
          if (img.width < this.options.minWidth || img.height < this.options.minHeight) {
            const errorMsg = this.options.messages.imageMustBeAtLeast
              .replace('%s', this.options.minWidth)
              .replace('%s', this.options.minHeight);
            this.showNotification('error', errorMsg);
            return;
          }

          // Store file data
          this.currentFile = { file, url: e.target.result, width: img.width, height: img.height };

          // Defer openCropper to avoid blocking load event
          // Use setTimeout to allow load event to complete, then split work across frames
          setTimeout(() => {
            requestAnimationFrame(() => {
              this.openCropper();
            });
          }, 0);
        };
        img.src = e.target.result;
      };
      reader.readAsDataURL(file);
    }

    openCropper() {
      if (!window.ulpmodal) {
        this.showNotification('error', this.options.messages.modalSystemNotAvailable);
        return;
      }

      // Create modal HTML WITHOUT the large image URL to avoid blocking
      // We'll set the image src after modal opens to prevent large data URLs from blocking
      const cropperHTML = `
        <div class="ulpmodal-media-wrapper ulp-avatar-cropper-modal">
          <h3 class="ulpmodal-title">${this.options.messages.chooseProfilePicture}</h3>
          <div class="ulp-avatar-cropper-body">
            <div class="ulp-avatar-cropper-wrapper">
              <div class="ulp-avatar-cropper-image-container">
                <div class="ulp-avatar-image-wrapper">
                  <img id="ulp-crop-img" src="" alt="Crop" style="opacity: 0;">
                </div>
                <div class="ulp-avatar-crop-overlay"></div>
                <div class="ulp-avatar-crop-box"></div>
              </div>
            </div>
            <div class="ulp-avatar-zoom-controls">
              <button type="button" class="ulp-zoom-btn ulp-zoom-minus" aria-label="Zoom out">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
              </button>
              <input type="range" class="ulp-zoom-slider" id="ulp-zoom-slider" min="1" max="3" step="0.1" value="1" aria-label="Zoom">
              <button type="button" class="ulp-zoom-btn ulp-zoom-plus" aria-label="Zoom in">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <line x1="12" y1="5" x2="12" y2="19"></line>
                  <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
              </button>
            </div>
          </div>
          <div class="ulp-avatar-cropper-footer">
            <button type="button" class="ulp-btn ulp-btn-secondary" id="ulp-crop-cancel">${this.options.captions.cancel}</button>
            <button type="button" class="ulp-btn ulp-btn-primary" id="ulp-crop-save">${this.options.captions.save}</button>
          </div>
        </div>
      `;

      // Store image URL separately to set after modal opens
      const imageUrl = this.currentFile.url;

      // Open modal immediately (without large image URL, so it's fast)
      window.ulpmodal(cropperHTML, {
        closeOnClick: false,
        closeOnEsc: true,
        ariaLabel: this.options.messages.chooseProfilePicture,
        afterOpen: (event) => {
          const modalElement = event && event.target ? event.target : event;
          const img = modalElement.querySelector('#ulp-crop-img');

          // Set image src AFTER modal is open (non-blocking)
          // Use setTimeout instead of rAF since setting src triggers image decoding (not visual)
          if (img) {
            setTimeout(() => {
              img.src = imageUrl;

              // Initialize cropper after image loads
              // Image decoding is heavy, so we wait for load event
              img.addEventListener('load', () => {
                // Defer cropper init to avoid blocking
                requestAnimationFrame(() => {
                  this.initCropper(modalElement);
                });
              }, { once: true });
            }, 0);
          } else {
            // Fallback if image not found
            requestAnimationFrame(() => {
              this.initCropper(modalElement);
            });
          }
        }
      });
    }

    initCropper(modal) {
      let modalElement = modal;
      if (modal && typeof modal === 'object' && !modal.querySelector && modal.target) {
        modalElement = modal.target;
      }

      if (!modalElement || typeof modalElement.querySelector !== 'function') {
        modalElement = document.querySelector('.ulpmodal-content') || document.querySelector('.ulp-avatar-cropper-modal');
        if (!modalElement) {
          console.error('Avatar uploader: Could not find modal element');
          return;
        }
      }

      const img = modalElement.querySelector('#ulp-crop-img');
      const container = modalElement.querySelector('.ulp-avatar-cropper-image-container');
      const imageWrapper = modalElement.querySelector('.ulp-avatar-image-wrapper');
      const cropBox = modalElement.querySelector('.ulp-avatar-crop-box');
      const zoomSlider = modalElement.querySelector('#ulp-zoom-slider');
      const zoomMinus = modalElement.querySelector('.ulp-zoom-minus');
      const zoomPlus = modalElement.querySelector('.ulp-zoom-plus');

      if (!img || !container || !imageWrapper || !cropBox || !zoomSlider) {
        console.warn('Avatar uploader: Could not find crop elements');
        return;
      }

      // State variables
      let cropSize = 0;
      let minScale = 1; // Minimum scale to cover crop area
      let currentScale = 1;
      let zoomLevel = 1; // Zoom level (1 = base, 3 = 3x zoom)
      let translateX = 0; // Image position offset from center
      let translateY = 0;

      // Cached container dimensions to avoid forced reflows
      let cachedContainerWidth = 0;
      let cachedContainerHeight = 0;
      let containerDimensionsDirty = true;

      // Drag state
      let isDragging = false;
      let dragStartX = 0;
      let dragStartY = 0;
      let dragStartTranslateX = 0;
      let dragStartTranslateY = 0;

      // Cache container dimensions during drag to avoid forced reflows
      let cachedDragContainerWidth = 0;
      let cachedDragContainerHeight = 0;
      let cachedDragCropSize = 0;

      // Get container dimensions (cached to avoid forced reflows)
      const getContainerDimensions = () => {
        if (containerDimensionsDirty) {
          const rect = container.getBoundingClientRect();
          cachedContainerWidth = rect.width;
          cachedContainerHeight = rect.height;
          containerDimensionsDirty = false;
        }
        return { width: cachedContainerWidth, height: cachedContainerHeight };
      };

      // Mark container dimensions as dirty when container might have changed
      const markDimensionsDirty = () => {
        containerDimensionsDirty = true;
      };

      // Calculate minimum scale to cover crop area (like background-size: cover for crop area)
      const calculateMinScale = () => {
        if (!img.naturalWidth || !img.naturalHeight) return;

        const { width: containerWidth, height: containerHeight } = getContainerDimensions();

        // Calculate crop size first if not already set
        // Use larger percentage on mobile for better UX
        if (cropSize === 0) {
          const isMobile = window.innerWidth <= 768;
          const cropPercentage = isMobile ? 0.9 : 0.8;
          cropSize = Math.min(containerWidth, containerHeight) * cropPercentage;
        }

        const imgAspect = img.naturalWidth / img.naturalHeight;
        const cropAspect = 1; // Crop area is square (cropSize x cropSize)

        // Scale to cover crop area (cover mode - image fills crop area)
        // This ensures the crop area is always filled, even for rectangular images
        if (imgAspect > cropAspect) {
          // Image is wider than crop - scale to fit crop height
          minScale = cropSize / img.naturalHeight;
        } else {
          // Image is taller than crop - scale to fit crop width
          minScale = cropSize / img.naturalWidth;
        }

        // Set initial scale (calculations only, no DOM writes)
        currentScale = minScale;
        zoomLevel = 1;
      };

      // Apply initial dimensions (separate function for DOM writes)
      const applyImageDimensions = () => {
        if (!img.naturalWidth || !img.naturalHeight) return;
        imageWrapper.style.width = img.naturalWidth + 'px';
        imageWrapper.style.height = img.naturalHeight + 'px';
        img.style.width = img.naturalWidth + 'px';
        img.style.height = img.naturalHeight + 'px';
        zoomSlider.value = 1;
      };

      // Constrain image position to keep crop area within image bounds
      const constrainPosition = (useCache = false) => {
        if (!img.naturalWidth || !img.naturalHeight || cropSize === 0) return;

        // Use cached dimensions during drag to avoid forced reflows
        const containerWidth = useCache ? cachedDragContainerWidth : getContainerDimensions().width;
        const containerHeight = useCache ? cachedDragContainerHeight : getContainerDimensions().height;
        const currentCropSize = useCache ? cachedDragCropSize : cropSize;

        const scaledWidth = img.naturalWidth * currentScale;
        const scaledHeight = img.naturalHeight * currentScale;
        const halfCrop = currentCropSize / 2;

        // Container center (where crop area is fixed)
        const containerCenterX = containerWidth / 2;
        const containerCenterY = containerHeight / 2;

        // Image center position (container center + translate offset)
        const imgCenterX = containerCenterX + translateX;
        const imgCenterY = containerCenterY + translateY;

        // Image bounds
        const imgLeft = imgCenterX - scaledWidth / 2;
        const imgRight = imgCenterX + scaledWidth / 2;
        const imgTop = imgCenterY - scaledHeight / 2;
        const imgBottom = imgCenterY + scaledHeight / 2;

        // Constrain: crop center (at container center) must stay within image bounds
        // Crop area extends from (containerCenterX - halfCrop) to (containerCenterX + halfCrop)
        // So we need: imgLeft <= containerCenterX - halfCrop AND containerCenterX + halfCrop <= imgRight

        // Calculate min/max translate values
        // For X: containerCenterX - halfCrop >= imgLeft  =>  translateX >= -(scaledWidth/2) + halfCrop
        //        containerCenterX + halfCrop <= imgRight =>  translateX <= (scaledWidth/2) - halfCrop
        const minTranslateX = -(scaledWidth / 2) + halfCrop;
        const maxTranslateX = (scaledWidth / 2) - halfCrop;
        const minTranslateY = -(scaledHeight / 2) + halfCrop;
        const maxTranslateY = (scaledHeight / 2) - halfCrop;

        // Apply constraints
        translateX = Math.max(minTranslateX, Math.min(translateX, maxTranslateX));
        translateY = Math.max(minTranslateY, Math.min(translateY, maxTranslateY));
      };

      // Update image transform
      const updateImageTransform = (skipConstrain = false) => {
        if (!img.naturalWidth || !img.naturalHeight) return;

        // Constrain position (use cache during drag to avoid reflows)
        if (!skipConstrain) {
          constrainPosition(isDragging);
        }

        // Apply transform
        // Wrapper is at 50% 50% (top-left at container center)
        // Translate by -50% of natural size to center, then add offset, then scale
        const halfWidth = img.naturalWidth / 2;
        const halfHeight = img.naturalHeight / 2;
        const x = -halfWidth + translateX;
        const y = -halfHeight + translateY;

        // Use will-change for better performance during drag
        if (isDragging) {
          imageWrapper.style.willChange = 'transform';
          imageWrapper.style.transform = `translate(${x}px, ${y}px) scale(${currentScale})`;
          imageWrapper.style.transition = 'none';
        } else {
          imageWrapper.style.willChange = 'auto';
          imageWrapper.style.transform = `translate(${x}px, ${y}px) scale(${currentScale})`;
          imageWrapper.style.transition = 'transform 0.2s ease-out';
        }
      };

      // Update crop box (fixed size, always centered)
      const updateCropBox = (forceRecalculate = false) => {
        const { width: containerWidth, height: containerHeight } = getContainerDimensions();

        // Recalculate crop size if forced or not set yet
        // Use larger percentage on mobile for better UX
        const isMobile = window.innerWidth <= 768;
        const cropPercentage = isMobile ? 0.9 : 0.8;

        if (cropSize === 0 || forceRecalculate) {
          cropSize = Math.min(containerWidth, containerHeight) * cropPercentage;
        }

        const cropX = (containerWidth - cropSize) / 2;
        const cropY = (containerHeight - cropSize) / 2;

        cropBox.style.left = cropX + 'px';
        cropBox.style.top = cropY + 'px';
        cropBox.style.width = cropSize + 'px';
        cropBox.style.height = cropSize + 'px';
      };

      // Initialize cropper - split into tiny chunks to stay under 16ms per frame
      const initializeCropper = () => {
        // Mark dimensions as dirty to force recalculation
        markDimensionsDirty();

        // Phase 1: Read dimensions only (fast, < 1ms)
        requestAnimationFrame(() => {
          updateCropBox();

          // Phase 2: Calculate scale (fast, < 1ms)
          requestAnimationFrame(() => {
            calculateMinScale();

            // Phase 3: Apply dimensions (fast, < 1ms)
            requestAnimationFrame(() => {
              applyImageDimensions();

              // Reset translation
              translateX = 0;
              translateY = 0;

              // Phase 4: Apply transform (fast, < 1ms)
              requestAnimationFrame(() => {
                updateImageTransform();

                // Phase 5: Show elements (fast, < 1ms)
                requestAnimationFrame(() => {
                  img.style.opacity = '1';
                  cropBox.style.opacity = '1';
                  const overlay = container.querySelector('.ulp-avatar-crop-overlay');
                  if (overlay) overlay.style.opacity = '1';
                });
              });
            });
          });
        });
      };

      // Wait for image to load, then initialize in next frame to avoid blocking
      if (img.complete && img.naturalWidth && img.naturalHeight) {
        // Image already loaded, defer initialization to next frame
        requestAnimationFrame(() => {
          initializeCropper();
        });
      } else {
        // Wait for image load, then defer to next frame
        img.addEventListener('load', () => {
          requestAnimationFrame(() => {
            initializeCropper();
          });
        }, { once: true });
      }

      // Update zoom level helper
      const updateZoom = (value) => {
        zoomSlider.value = value;
        currentScale = minScale * value;
        zoomLevel = value;
        updateImageTransform();
      };

      // Zoom slider handler
      zoomSlider.addEventListener('input', (e) => {
        updateZoom(parseFloat(e.target.value));
      });

      // Zoom buttons
      zoomMinus?.addEventListener('click', () => {
        updateZoom(Math.max(1, parseFloat(zoomSlider.value) - 0.1));
      });

      zoomPlus?.addEventListener('click', () => {
        updateZoom(Math.min(3, parseFloat(zoomSlider.value) + 0.1));
      });

      // Resize handler - immediate updates with batched DOM operations
      let resizeFrameId = null;
      let resizeObserver = null;

      const handleResize = () => {
        // Cancel any pending resize update
        if (resizeFrameId) {
          cancelAnimationFrame(resizeFrameId);
        }

        // Mark dimensions as dirty
        markDimensionsDirty();

        // Use requestAnimationFrame to batch ALL DOM operations
        // This prevents forced reflows by doing all reads, then all writes
        resizeFrameId = requestAnimationFrame(() => {
          resizeFrameId = null;

          // Read phase: Get dimensions ONCE (prevents multiple forced reflows)
          const { width: containerWidth, height: containerHeight } = getContainerDimensions();
          const oldMinScale = minScale;

          // Calculate phase: All calculations (no DOM writes yet)
          const isMobile = window.innerWidth <= 768;
          const cropPercentage = isMobile ? 0.9 : 0.8;
          cropSize = Math.min(containerWidth, containerHeight) * cropPercentage;

          // Update crop box dimensions
          const cropX = (containerWidth - cropSize) / 2;
          const cropY = (containerHeight - cropSize) / 2;

          // Calculate new min scale
          if (img.naturalWidth && img.naturalHeight) {
            const imgAspect = img.naturalWidth / img.naturalHeight;
            if (imgAspect > 1) {
              minScale = cropSize / img.naturalHeight;
            } else {
              minScale = cropSize / img.naturalWidth;
            }
          }

          // Adjust current scale proportionally if minScale changed
          if (oldMinScale !== minScale && oldMinScale > 0) {
            currentScale = (currentScale / oldMinScale) * minScale;
            zoomLevel = currentScale / minScale;
          }

          // Write phase: Apply ALL DOM changes together (single reflow)
          cropBox.style.left = cropX + 'px';
          cropBox.style.top = cropY + 'px';
          cropBox.style.width = cropSize + 'px';
          cropBox.style.height = cropSize + 'px';
          zoomSlider.value = zoomLevel;

          // Constrain and update transform
          constrainPosition();
          updateImageTransform();
        });
      };

      // Use ResizeObserver for immediate container size detection
      if (typeof ResizeObserver !== 'undefined') {
        resizeObserver = new ResizeObserver(handleResize);
        resizeObserver.observe(container);
      } else {
        // Fallback to window resize
        window.addEventListener('resize', handleResize, { passive: true });
      }

      // Helper function to get event coordinates relative to container center
      const getEventCoords = (e) => {
        const containerRect = container.getBoundingClientRect();
        let clientX, clientY;

        if (e.touches && e.touches.length > 0) {
          clientX = e.touches[0].clientX;
          clientY = e.touches[0].clientY;
        } else {
          clientX = e.clientX;
          clientY = e.clientY;
        }

        // Return coordinates relative to container center
        return {
          x: clientX - containerRect.left - containerRect.width / 2,
          y: clientY - containerRect.top - containerRect.height / 2
        };
      };

      // Mouse/Touch down on image container (drag image to reposition)
      const onImageStart = (e) => {
        // Don't drag if clicking on zoom controls
        if (e.target.closest('.ulp-avatar-zoom-controls')) return;

        isDragging = true;

        // Cache container dimensions at drag start to avoid reflows during drag
        const dims = getContainerDimensions();
        cachedDragContainerWidth = dims.width;
        cachedDragContainerHeight = dims.height;
        cachedDragCropSize = cropSize;

        const coords = getEventCoords(e);
        dragStartX = coords.x;
        dragStartY = coords.y;
        dragStartTranslateX = translateX;
        dragStartTranslateY = translateY;
        container.style.cursor = 'grabbing';
        container.style.touchAction = 'none';
        e.preventDefault();
        e.stopPropagation();
      };

      container.addEventListener('mousedown', onImageStart);
      container.addEventListener('touchstart', onImageStart, { passive: false });

      // Throttle transform updates during drag using requestAnimationFrame
      let transformFrameId = null;

      // Mouse/Touch move (drag image)
      const onImageMove = (e) => {
        if (!isDragging) return;

        const coords = getEventCoords(e);
        const deltaX = coords.x - dragStartX;
        const deltaY = coords.y - dragStartY;
        translateX = dragStartTranslateX + deltaX;
        translateY = dragStartTranslateY + deltaY;

        // Throttle updates to animation frames to avoid blocking
        if (!transformFrameId) {
          transformFrameId = requestAnimationFrame(() => {
            transformFrameId = null;
            // Use cached dimensions to avoid forced reflows
            updateImageTransform(false);
          });
        }

        e.preventDefault();
      };

      document.addEventListener('mousemove', onImageMove);
      document.addEventListener('touchmove', onImageMove, { passive: false });

      const onImageEnd = () => {
        if (isDragging) {
          isDragging = false;
          container.style.cursor = '';
          container.style.touchAction = '';

          // Cancel any pending transform update
          if (transformFrameId) {
            cancelAnimationFrame(transformFrameId);
            transformFrameId = null;
          }

          // Final update with fresh dimensions (not cached)
          updateImageTransform(false);

          // Clear cache
          cachedDragContainerWidth = 0;
          cachedDragContainerHeight = 0;
          cachedDragCropSize = 0;
        }
      };

      document.addEventListener('mouseup', onImageEnd);
      document.addEventListener('touchend', onImageEnd);
      document.addEventListener('touchcancel', onImageEnd);

      // Cleanup function for event listeners
      const cleanup = () => {
        window.removeEventListener('resize', handleResize);
        document.removeEventListener('mousemove', onImageMove);
        document.removeEventListener('mouseup', onImageEnd);
        document.removeEventListener('touchmove', onImageMove);
        document.removeEventListener('touchend', onImageEnd);
        document.removeEventListener('touchcancel', onImageEnd);
        if (resizeFrameId) {
          cancelAnimationFrame(resizeFrameId);
          resizeFrameId = null;
        }
        if (resizeObserver) {
          resizeObserver.disconnect();
          resizeObserver = null;
        }
      };

      // Save button
      const saveBtn = modalElement.querySelector('#ulp-crop-save');
      if (saveBtn) {
        saveBtn.addEventListener('click', (e) => {
          e.preventDefault();
          e.stopPropagation();

          // Defer heavy calculations to avoid blocking click handler
          requestAnimationFrame(() => {
            cleanup();

            // Use cached dimensions to avoid forced reflow
            const { width: containerWidth, height: containerHeight } = getContainerDimensions();

            // Container center (crop area center)
            const containerCenterX = containerWidth / 2;
            const containerCenterY = containerHeight / 2;

            // Image center position (container center + translate offset)
            const imgCenterX = containerCenterX + translateX;
            const imgCenterY = containerCenterY + translateY;

            // Crop center relative to image center
            const cropXRelative = containerCenterX - imgCenterX;
            const cropYRelative = containerCenterY - imgCenterY;

            // Convert to natural image coordinates
            // Image center is at (naturalWidth/2, naturalHeight/2) in natural coordinates
            const cropXNatural = (img.naturalWidth / 2) + (cropXRelative / currentScale) - (cropSize / currentScale / 2);
            const cropYNatural = (img.naturalHeight / 2) + (cropYRelative / currentScale) - (cropSize / currentScale / 2);
            const cropSizeNatural = cropSize / currentScale;

            // Close modal immediately for better UX (crop happens in background)
            if (typeof window.ulpmodalClose === 'function') {
              window.ulpmodalClose();
            }

            // Start crop operation (now handles its own async scheduling)
            this.cropAndUpload(img, cropXNatural, cropYNatural, cropSizeNatural);
          });
        });
      }

      // Cancel button
      const cancelBtn = modalElement.querySelector('#ulp-crop-cancel');
      if (cancelBtn) {
        cancelBtn.addEventListener('click', (e) => {
          e.preventDefault();
          e.stopPropagation();

          cleanup();

          if (typeof window.ulpmodalClose === 'function') {
            window.ulpmodalClose();
          }
        });
      }
    }

    cropAndUpload(img, cropX, cropY, cropSize) {
      // Split canvas operation into chunks to avoid long blocking
      const size = this.options.cropSize;
      const canvas = document.createElement('canvas');
      const ctx = canvas.getContext('2d');

      canvas.width = size;
      canvas.height = size;

      // Use requestAnimationFrame to ensure we're not blocking during critical rendering
      requestAnimationFrame(() => {
        // Draw cropped image (this is CPU-intensive but necessary)
        ctx.drawImage(
          img,
          cropX, cropY, cropSize, cropSize,
          0, 0, size, size
        );

        // Convert to blob - use setTimeout since toBlob is CPU work, not visual
        // This prevents blocking the animation frame
        setTimeout(() => {
          canvas.toBlob((blob) => {
            if (blob) {
              this.uploadAvatar(blob);
            } else {
              this.showNotification('error', this.options.messages.failedToProcessImage);
            }
            // Clean up canvas reference
            canvas.width = 0;
            canvas.height = 0;
          }, 'image/jpeg', this.options.quality);
        }, 0);
      });
    }

    uploadAvatar(blob) {
      if (!this.options.ajaxUrl) {
        this.showNotification('error', this.options.messages.uploadUrlNotConfigured);
        return;
      }

      const formData = new FormData();
      formData.append('action', 'ulp_avatar');
      formData.append('method', 'upload');
      formData.append('security', this.options.nonce);
      formData.append('fileuploader', '1');
      formData.append('files', blob, 'avatar.jpg');
      formData.append('name', 'avatar.jpg');

      this.showProgress(0);
      this.preview.classList.add('is-uploading');

      const xhr = new XMLHttpRequest();

      xhr.upload.addEventListener('progress', (e) => {
        if (e.lengthComputable) {
          const percent = Math.round((e.loaded / e.total) * 100);
          this.showProgress(percent);
        }
      });

      xhr.addEventListener('load', () => {
        this.hideProgress();
        this.preview.classList.remove('is-uploading');

        if (xhr.status === 200) {
          try {
            const response = JSON.parse(xhr.responseText);
            if (response.isSuccess && response.files?.[0]) {
              const fileData = response.files[0];
              let avatarUrl = '';

              if (fileData.url) {
                avatarUrl = fileData.url;
              } else if (fileData.name) {
                let uploadUrl = '';
                if (typeof fileUploaderCommonConfig !== 'undefined' && fileUploaderCommonConfig.uploadUrl) {
                  uploadUrl = fileUploaderCommonConfig.uploadUrl;
                } else {
                  const url = new URL(window.location.href);
                  uploadUrl = url.origin + '/wp-content/uploads/wp-ulike/avatars/';
                }

                if (!uploadUrl.endsWith('/')) {
                  uploadUrl += '/';
                }

                avatarUrl = uploadUrl + fileData.name;
              }

              if (avatarUrl) {
                this.updateAvatar(avatarUrl);
                this.removeBtn.style.display = '';
                this.options.defaultAvatar = avatarUrl; // Update default for future loads
                this.showSuccessFeedback();
                this.showNotification('success', this.options.messages.avatarUploadedSuccessfully);
                // No page reload - avatar is updated via updateAvatar()
              }
            } else {
              const error = response.warnings?.[0] || response.warnings || this.options.messages.uploadFailed;
              this.showNotification('error', Array.isArray(error) ? error[0] : error);
            }
          } catch (e) {
            this.showNotification('error', this.options.messages.invalidResponseFromServer);
          }
        } else {
          this.showNotification('error', this.options.messages.uploadFailed);
        }
      });

      xhr.addEventListener('error', () => {
        this.hideProgress();
        this.preview.classList.remove('is-uploading');
        this.showNotification('error', this.options.messages.uploadFailed);
      });

      xhr.open('POST', this.options.ajaxUrl);
      xhr.send(formData);
    }

    updateAvatar(url) {
      if (this.image) {
        // Remove loaded class to fade out current image
        this.image.classList.remove('loaded');
        this.image.style.opacity = '0';

        const newUrl = url + (url.includes('?') ? '&' : '?') + 't=' + Date.now();
        const img = new Image();
        img.onload = () => {
          // Show new image
          this.image.src = newUrl;
          this.image.classList.add('loaded');
          this.image.style.opacity = '1';

          // Check if new avatar is user-uploaded to show/hide remove button
          if (this.removeBtn) {
            this.removeBtn.style.display = this.isUserUploaded(newUrl) ? '' : 'none';
          }
        };
        img.onerror = () => {
          // On error, still try to show it
          this.image.src = newUrl;
          this.image.classList.add('loaded');
          this.image.style.opacity = '1';

          // Check if it's user-uploaded
          if (this.removeBtn) {
            this.removeBtn.style.display = this.isUserUploaded(newUrl) ? '' : 'none';
          }
        };
        img.src = newUrl;
      }
    }

    async removeAvatar() {
      if (!this.options.ajaxUrl) return;

      const formData = new FormData();
      formData.append('action', 'ulp_avatar');
      formData.append('method', 'delete');
      formData.append('security', this.options.nonce);
      formData.append('removeMeta', '1');

      const currentSrc = this.image?.src;
      if (currentSrc && !currentSrc.includes('data:') && !currentSrc.includes('gravatar')) {
        const fileName = currentSrc.split('/').pop().split('?')[0];
        if (fileName) {
          formData.append('file', fileName);
        }
      }

      try {
        const response = await fetch(this.options.ajaxUrl, {
          method: 'POST',
          body: formData
        });

        const data = await response.json();

        if (data.isSuccess) {
          this.showNotification('success', this.options.messages.avatarRemoved);

          // Reload page to fetch fresh Gravatar URL and ensure everything is in sync
          // This is more reliable than trying to construct the URL client-side
          setTimeout(() => {
            window.location.reload();
          }, 500);
        } else {
          this.showNotification('error', this.options.messages.failedToRemoveAvatar);
        }
      } catch (e) {
        this.showNotification('error', this.options.messages.failedToRemoveAvatar);
      }
    }

    loadExistingAvatar() {
      // Only show remove button if avatar is user-uploaded (not Gravatar/WordPress default)
      if (this.image && this.image.src && this.removeBtn) {
        this.removeBtn.style.display = this.isUserUploaded(this.image.src) ? '' : 'none';
      }
    }

    showProgress(percent) {
      if (!this.progress) return;

      this.progress.style.display = 'flex';
      const bar = this.progress.querySelector('.ulp-progress-bar');
      const text = this.progress.querySelector('.ulp-avatar-progress-text');

      if (bar) {
        const circumference = 2 * Math.PI * 45;
        const offset = circumference - (percent / 100) * circumference;
        bar.style.strokeDashoffset = offset;
      }

      if (text) {
        text.textContent = percent + '%';
      }
    }

    hideProgress() {
      if (this.progress) {
        setTimeout(() => {
          this.progress.style.display = 'none';
        }, 500);
      }
    }

    showSuccessFeedback() {
      if (!this.successOverlay) return;

      // Show success overlay briefly
      this.successOverlay.style.display = 'flex';
      this.preview.classList.add('ulp-avatar-success');

      // Hide after animation
      setTimeout(() => {
        this.successOverlay.style.display = 'none';
        this.preview.classList.remove('ulp-avatar-success');
      }, 2000);
    }

    showNotification(type, message) {
      if (typeof WordpressUlikeNotifications !== 'undefined') {
        new WordpressUlikeNotifications(document.body, {
          messageType: type === 'error' ? 'error' : 'success',
          messageText: message
        });
      } else {
        alert(message);
      }
    }
  }

  // Auto-initialize
  function init() {
    // Get defaults with localized config
    const defaults = getDefaults();

    document.querySelectorAll('.ulp_avatar_upload_field').forEach(input => {
      const container = input.closest('.ulp-avatar-container') || input.parentElement;
      if (!container.classList.contains('ulp-avatar-initialized')) {
        // Mark as initialized immediately to hide CSS skeleton
        container.classList.add('ulp-avatar-initialized');

        // Get config from localized script or input dataset
        const config = {
          ajaxUrl: input.dataset.ajaxUrl || (typeof fileUploaderCommonConfig !== 'undefined' ? fileUploaderCommonConfig.AjaxUrl : '') || defaults.ajaxUrl,
          nonce: input.dataset.nonce || (typeof fileUploaderCommonConfig !== 'undefined' ? fileUploaderCommonConfig.Nonce : '') || defaults.nonce,
          defaultAvatar: input.dataset.default || '',
          // Use localized config if available, otherwise use defaults
          maxSize: (typeof fileUploaderCommonConfig !== 'undefined' && fileUploaderCommonConfig.avatarConfig && fileUploaderCommonConfig.avatarConfig.maxSize)
            ? fileUploaderCommonConfig.avatarConfig.maxSize
            : (input.dataset.maxSize ? parseInt(input.dataset.maxSize) * 1024 * 1024 : defaults.maxSize),
          minWidth: (typeof fileUploaderCommonConfig !== 'undefined' && fileUploaderCommonConfig.avatarConfig && fileUploaderCommonConfig.avatarConfig.minWidth)
            ? fileUploaderCommonConfig.avatarConfig.minWidth
            : defaults.minWidth,
          minHeight: (typeof fileUploaderCommonConfig !== 'undefined' && fileUploaderCommonConfig.avatarConfig && fileUploaderCommonConfig.avatarConfig.minHeight)
            ? fileUploaderCommonConfig.avatarConfig.minHeight
            : defaults.minHeight,
          cropSize: (typeof fileUploaderCommonConfig !== 'undefined' && fileUploaderCommonConfig.avatarConfig && fileUploaderCommonConfig.avatarConfig.cropSize)
            ? fileUploaderCommonConfig.avatarConfig.cropSize
            : defaults.cropSize,
          quality: (typeof fileUploaderCommonConfig !== 'undefined' && fileUploaderCommonConfig.avatarConfig && fileUploaderCommonConfig.avatarConfig.quality)
            ? fileUploaderCommonConfig.avatarConfig.quality
            : defaults.quality,
          captions: (typeof fileUploaderCommonConfig !== 'undefined' && fileUploaderCommonConfig.avatarConfig && fileUploaderCommonConfig.avatarConfig.captions)
            ? fileUploaderCommonConfig.avatarConfig.captions
            : defaults.captions
        };

        const uploader = new AvatarUploader(container, config);
        container.ulpAvatarUploader = uploader;
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  if (typeof window !== 'undefined') {
    window.AvatarUploader = AvatarUploader;
  }

})();