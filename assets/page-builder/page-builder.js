document.addEventListener('DOMContentLoaded', function() {
  const iframeUri = document.querySelector('[data-builder-configuration]').dataset.iframeUrl;
  const selectLocaleMessage = document.querySelector('[data-builder-configuration]').dataset.selectLocaleMessage;

  const localeDropdown = document.getElementById('localeDropdown');
  const localeItems = document.querySelectorAll('.dropdown-item[data-locale]');
  const resolutionButtons = document.querySelectorAll('.resolution-selector button');
  const iframeWrapper = document.querySelector('[data-page-builder-target="iframeWrapper"]');

  // Locale dropdown item click handler with confirmation
  if (localeItems.length > 0 && localeDropdown) {
    const currentLocale = localeDropdown.dataset.currentLocale;

    localeItems.forEach(item => {
      item.addEventListener('click', function(e) {
        e.preventDefault();

        const newLocale = this.dataset.locale;

        // If locale hasn't changed, do nothing
        if (newLocale === currentLocale) {
          return;
        }

        // Show confirmation dialog
        const confirmed = confirm(
          'Changing the locale will reload the page. Any unsaved changes will be lost. Do you want to continue?'
        );

        if (confirmed) {
          // Build new URL with locale parameter
          const url = new URL(window.location.href);
          url.searchParams.set('locale', newLocale);
          window.location.href = url.toString();
        }
      });
    });
  }

  if (resolutionButtons.length && iframeWrapper) {
    resolutionButtons.forEach(button => {
      button.addEventListener('click', function() {
        // Remove active class from all buttons
        resolutionButtons.forEach(btn => btn.classList.remove('active'));

        // Add active class to clicked button
        this.classList.add('active');

        // Change iframe wrapper class
        const resolution = this.dataset.resolution;
        iframeWrapper.className = 'page-builder__iframe-wrapper resolution-' + resolution;
      });
    });
  }

  // Alignment toggle functionality
  const alignmentToggle = document.querySelector('[data-page-builder-target="alignmentToggle"]');

  if (alignmentToggle && iframeWrapper) {
    // Cycle through alignments: center → left → right → center
    alignmentToggle.addEventListener('click', function() {
      this.classList.toggle('btn-outline-primary');
      this.classList.toggle('btn-outline-secondary');

      if (iframeWrapper.style.margin === '0px auto') {
        iframeWrapper.style.margin = '0px';
      } else {
        iframeWrapper.style.margin = '0px auto';
      }

    });
  }

  // Load the preview URL in the iframe
  const iframe = document.querySelector('[data-page-builder-target="iframe"]');
  let initialIframeUrl = null; // Store the initial URL to protect against navigation
  let iframeEventsInitialized = false; // Track if events are currently initialized

  // Editor panel elements (used by editBlock function)
  const editorPanel = document.querySelector('[data-page-builder-target="editorPanel"]');
  const editorToggle = document.querySelector('[data-page-builder-target="editorToggle"]');
  const editorContent = document.querySelector('[data-page-builder-target="editorContent"]');

  /**
   * Check if the current iframe URL matches the initial URL
   * Returns true if valid, false otherwise
   */
  function isCurrentIframeUrlValid() {
    if (!initialIframeUrl) return true; // Allow during first load

    try {
      const currentUrl = new URL(iframe.contentWindow.location.href);
      const currentPath = currentUrl.origin + currentUrl.pathname;
      return currentPath === initialIframeUrl;
    } catch (error) {
      console.error('Cannot check iframe URL:', error);
      return false;
    }
  }

  /**
   * Common function to handle block editing
   * Accessible globally for all buttons (sidebar, iframe, add block)
   */
  function editBlock(blockId) {
    console.log('Edit block:', blockId);

    // Security check: only allow editing if iframe URL hasn't changed (skip for create mode)
    if (blockId !== null && !isCurrentIframeUrlValid()) {
      console.error('Cannot edit block - iframe URL has changed');
      alert('The page has changed. Please reload the page builder to continue editing.');
      return;
    }

    if (!editorPanel) return;

    // Dispatch custom event to the BlockEditor Stimulus controller
    const pageBuilderElement = document.querySelector('[data-controller*="agence-adeliom--sylius-happy-cms-plugin--builder-block-editor"]');
    if (pageBuilderElement) {
      const event = new CustomEvent('block-editor:edit', {
        detail: { blockId: blockId }
      });
      pageBuilderElement.dispatchEvent(event);
    } else {
      console.warn('Page builder element not found');
    }

    // Open the overlay panel
    editorPanel.classList.add('is-open');

    console.log('Panel opened for block:', blockId);
  }

  /**
   * Common function to close the block editor panel
   * Resets the form and closes the panel
   */
  function closeBlock() {
    console.log('Close block editor');

    if (!editorPanel) return;

    // Dispatch custom event to the BlockEditor Stimulus controller
    const pageBuilderElement = document.querySelector('[data-controller*="agence-adeliom--sylius-happy-cms-plugin--builder-block-editor"]');
    if (pageBuilderElement) {
      const event = new CustomEvent('block-editor:close');
      pageBuilderElement.dispatchEvent(event);
    } else {
      console.warn('Page builder element not found');
    }

    console.log('Block editor closed');
  }

  if (iframe) {

    // Prevent navigation in iframe and re-initialize events on reload
    iframe.addEventListener('load', function() {
      console.log('Iframe loaded. URL:', iframe.src);
      console.log('Iframe location:', iframe.contentWindow?.location?.href);

      try {
        const iframeDocument = iframe.contentDocument || iframe.contentWindow.document;
        const iframeWindow = iframe.contentWindow;

        // Store initial URL on first load (without query params and hash for comparison)
        if (!initialIframeUrl) {
          const url = new URL(iframeWindow.location.href);
          // Store pathname only (ignore query params and hash)
          initialIframeUrl = url.origin + url.pathname;
          console.log('Initial iframe URL stored:', initialIframeUrl);
        }

        // Check if the loaded URL is the authorized one
        if (!isCurrentIframeUrlValid()) {
          console.warn('Iframe loaded with unauthorized URL, skipping event initialization');
          iframeEventsInitialized = false;
          return;
        }

        console.log('Successfully accessed iframe document - initializing events');

        /**
         * Initialize events on elements INSIDE the iframe
         * This function can be called multiple times when returning to the authorized URL
         */
        function initIframeBlockEvents() {
          if (!iframeDocument) return;

          console.log('Initializing iframe block events...');

          // Intercept all link clicks
          iframeDocument.addEventListener('click', function(e) {
            const target = e.target.closest('a');
            if (target && target.href) {
              e.preventDefault();
              console.log('Navigation blocked in preview:', target.href);
              return false;
            }
          }, true);

          // Intercept form submissions
          iframeDocument.addEventListener('submit', function(e) {
            e.preventDefault();
            console.log('Form submission blocked in preview');
            return false;
          }, true);

          // Inject CSS for hover effect in iframe (only if not already present)
          if (!iframeDocument.getElementById('page-builder-styles')) {
            const style = iframeDocument.createElement('style');
            style.id = 'page-builder-styles';
            style.textContent = `
                                    /* Minimal height for blocks (some are blank) */
                                    .content-block-wrapper {
                                      min-height: 150px;
                                    }
                                    /* Show outline and shadow on hover */
                                    .content-block-wrapper.is-hovered {
                                        outline: 3px solid #1e74fd !important;
                                        outline-offset: -3px !important;
                                        box-shadow: 0 0 0 3px rgba(30, 116, 253, 0.2) !important;
                                        z-index: 100 !important;
                                    }

                                    /* Show hover overlay when hovering */
                                    .content-block-wrapper.is-hovered .content-block-hover-overlay {
                                        display: flex !important;
                                    }

                                    /* Hide hover overlay for unpublished blocks (they have their own overlay) */
                                    .content-block-wrapper[data-published="false"] .content-block-hover-overlay {
                                        display: none !important;
                                    }

                                    /* Hide hover overlay for deleted blocks (they have their own overlay) */
                                    .content-block-wrapper[data-deleted="true"] .content-block-hover-overlay {
                                        display: none !important;
                                    }

                                    /* Button hover effect */
                                    .content-block-edit-button:hover {
                                        background: #0056d6 !important;
                                        transform: translateY(-2px) !important;
                                        box-shadow: 0 6px 16px rgba(30, 116, 253, 0.5) !important;
                                    }
                                `;
            iframeDocument.head.appendChild(style);
          }

          // Add hover effect on blocks in iframe to highlight handles in sidebar (reverse)
          const contentBlocks = iframeDocument.querySelectorAll('.content-block-wrapper[data-block-id]');
          contentBlocks.forEach(block => {
            block.addEventListener('mouseenter', function() {
              // Security check: only apply hover if iframe URL hasn't changed
              if (!isIframeUrlValid()) {
                return;
              }

              const blockId = this.dataset.blockId;
              const handle = document.querySelector(`.page-builder__block-handle[data-block-id="${blockId}"]`);
              if (handle) {
                const handleButton = handle.querySelector('.page-builder__block-handle-button');
                if (handleButton) {
                  handleButton.classList.add('is-hovered');
                }
                // Also highlight the block itself
                this.classList.add('is-hovered');
              }
            });

            block.addEventListener('mouseleave', function() {
              // Security check: only apply hover if iframe URL hasn't changed
              if (!isIframeUrlValid()) {
                return;
              }

              const blockId = this.dataset.blockId;
              const handle = document.querySelector(`.page-builder__block-handle[data-block-id="${blockId}"]`);
              if (handle) {
                const handleButton = handle.querySelector('.page-builder__block-handle-button');
                if (handleButton) {
                  handleButton.classList.remove('is-hovered');
                }
                // Remove highlight from block
                this.classList.remove('is-hovered');
              }
            });
          });

          // Add click handler for edit buttons in iframe (both hover overlay and unpublished overlay)
          const editButtons = iframeDocument.querySelectorAll('.content-block-edit-button');
          editButtons.forEach(button => {
            button.addEventListener('click', function(e) {
              e.preventDefault();
              e.stopPropagation();
              const blockId = this.dataset.blockId;
              editBlock(blockId);
            });
          });

          // Remove Symfony toolbar inside iframe
          if (iframeDocument.querySelector('.sf-toolbar')) {
            iframeDocument.querySelector('.sf-toolbar').remove();
          }

          iframeEventsInitialized = true;
          console.log('Iframe block events initialized');
        }

        // Initialize iframe events for the first time
        initIframeBlockEvents();

        // Synchronize block handles positions with iframe scroll
        const blockHandles = document.querySelectorAll('.page-builder__block-handle');
        const blockHandlesContainer = document.querySelector('[data-page-builder-target="blockHandles"]');
        const sidebar = document.querySelector('[data-page-builder-target="sidebar"]');

        /**
         * Check if the iframe URL has changed from the initial URL
         * Returns true if the URL is still the same (safe to operate), false otherwise
         */
        function isIframeUrlValid() {
          if (!initialIframeUrl) return true; // Allow during initialization

          try {
            const currentUrl = new URL(iframe.contentWindow.location.href);
            const currentPath = currentUrl.origin + currentUrl.pathname;

            if (currentPath !== initialIframeUrl) {
              console.warn('Iframe URL has changed! Expected:', initialIframeUrl, 'Got:', currentPath);
              return false;
            }
            return true;
          } catch (error) {
            // Cannot access URL (cross-origin), assume it's invalid
            console.error('Cannot check iframe URL:', error);
            return false;
          }
        }

        function updateBlockHandlesPositions() {
          // Security check: only update if iframe URL hasn't changed
          if (!isIframeUrlValid()) {
            console.warn('Skipping block handles update - iframe URL has changed');
            return;
          }
          if (!blockHandlesContainer) return;

          // Get all content blocks in the iframe
          const contentBlocks = iframeDocument.querySelectorAll('.content-block-wrapper[data-block-id]');

          if (contentBlocks.length === 0) return;

          const viewportHeight = iframeWindow.innerHeight;

          // Get the offset of the handles container to compensate for the add block button
          const addBlockButton = document.querySelector('[data-page-builder-target="addBlock"]');
          let offset = 0;
          if (addBlockButton) {
            // Calculate total height including margins
            const addBlockStyles = window.getComputedStyle(addBlockButton);
            const addBlockHeight = addBlockButton.offsetHeight;
            const addBlockMarginBottom = parseFloat(addBlockStyles.marginBottom);
            const handlesContainerMarginTop = parseFloat(window.getComputedStyle(blockHandlesContainer).marginTop);
            offset = addBlockHeight + addBlockMarginBottom + handlesContainerMarginTop;
          }

          // Position each handle based on its corresponding block in the iframe
          contentBlocks.forEach(contentBlock => {
            const blockId = contentBlock.dataset.blockId;
            const handle = document.querySelector(`.page-builder__block-handle[data-block-id="${blockId}"]`);

            if (handle) {
              // Get block position and dimensions in iframe (relative to viewport)
              const blockRect = contentBlock.getBoundingClientRect();

              // Use the position relative to the iframe viewport, compensating for the offset
              const blockTop = blockRect.top - offset;
              const blockHeight = blockRect.height;

              // Set handle position and height
              handle.style.top = blockTop + 'px';
              handle.style.height = blockHeight + 'px';

              // Check if block is visible in viewport
              // A block is considered visible if any part of it is in the viewport
              const isVisible = blockRect.top < viewportHeight && blockRect.bottom > 0;

              // Add/remove active class
              if (isVisible) {
                handle.classList.add('is-active');
              } else {
                handle.classList.remove('is-active');
              }
            }
          });
        }

        // Throttle function to limit scroll event frequency
        function throttle(func, limit) {
          let inThrottle;
          return function() {
            const args = arguments;
            const context = this;
            if (!inThrottle) {
              func.apply(context, args);
              inThrottle = true;
              setTimeout(() => inThrottle = false, limit);
            }
          }
        }

        // Update positions on iframe scroll (throttled)
        const throttledUpdate = throttle(updateBlockHandlesPositions, 50);
        iframeWindow.addEventListener('scroll', throttledUpdate);

        // Update positions on iframe resize
        iframeWindow.addEventListener('resize', throttledUpdate);

        // Initial positioning
        setTimeout(updateBlockHandlesPositions, 100);

        // Add hover effect on handles to highlight blocks in iframe
        blockHandles.forEach(handle => {
          const handleButton = handle.querySelector('.page-builder__block-handle-button');
          if (!handleButton) return;

          handleButton.addEventListener('mouseenter', function() {
            // Security check: only apply hover if iframe URL hasn't changed
            if (!isIframeUrlValid()) {
              return;
            }

            const blockId = handle.dataset.blockId;
            const block = iframeDocument.querySelector(`.content-block-wrapper[data-block-id="${blockId}"]`);
            if (block) {
              block.classList.add('is-hovered');
            }
          });

          handleButton.addEventListener('mouseleave', function() {
            // Security check: only apply hover if iframe URL hasn't changed
            if (!isIframeUrlValid()) {
              return;
            }

            const blockId = handle.dataset.blockId;
            const block = iframeDocument.querySelector(`.content-block-wrapper[data-block-id="${blockId}"]`);
            if (block) {
              block.classList.remove('is-hovered');
            }
          });

          // Add click handler for sidebar buttons
          handleButton.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const blockId = handle.dataset.blockId;
            editBlock(blockId);
          });
        });

        // Note: Events on blocks INSIDE iframe (hover, click on edit buttons) are handled by initIframeBlockEvents()

      } catch (error) {
        console.error('Cannot access iframe:', error);
        console.log('Iframe src:', iframe.src);
        // Cross-origin restrictions prevent access
        console.log('Cannot access iframe content (cross-origin restriction)');
      }
    });

    // Monitor iframe URL changes and re-initialize events when returning to authorized URL
    let lastKnownUrl = null;
    let urlCheckInterval = setInterval(function() {
      try {
        const currentUrl = iframe.contentWindow.location.href;

        // Check if URL has changed
        if (currentUrl !== lastKnownUrl) {
          console.log('Iframe URL changed from', lastKnownUrl, 'to', currentUrl);
          lastKnownUrl = currentUrl;

          // Check if we're back on the authorized URL
          const url = new URL(currentUrl);
          const currentPath = url.origin + url.pathname;

          if (currentPath === initialIframeUrl && !iframeEventsInitialized) {
            console.log('Returned to authorized URL - triggering re-initialization');
            // Manually trigger the load event handler
            iframe.dispatchEvent(new Event('load'));
          } else if (currentPath !== initialIframeUrl && iframeEventsInitialized) {
            console.log('Navigated away from authorized URL - events will be disabled');
            iframeEventsInitialized = false;
          }
        }
      } catch (error) {
        // Cross-origin error - ignore
      }
    }, 500); // Check every 500ms

    // Load the preview URL with locale
    iframe.src = iframeUri;
  }

  // Editor panel toggle functionality - Overlay behavior for all screen sizes
  const addBlockButton = document.querySelector('[data-page-builder-target="addBlock"]');

  if (editorPanel && editorToggle) {
    // Toggle button click handler - close panel and reset form
    editorToggle.addEventListener('click', function(e) {
      e.preventDefault();

      // If panel is open, close it and reset the form
      if (editorPanel.classList.contains('is-open')) {
        editorPanel.classList.remove('is-open');
        // Reset the form by calling closeBlock()
        closeBlock();
      }
    });

    // Close panel when clicking outside
    // document.addEventListener('click', function(e) {
    //     if (editorPanel.classList.contains('is-open') &&
    //         !editorPanel.contains(e.target) &&
    //         e.target !== editorToggle &&
    //         e.target !== addBlockButton &&
    //         !editorToggle.contains(e.target)) {
    //         editorPanel.classList.remove('is-open');
    //         // Reset the form by calling closeBlock()
    //         closeBlock();
    //     }
    // });

    // Escape key to close panel
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape' && editorPanel.classList.contains('is-open')) {
        editorPanel.classList.remove('is-open');
        // Reset the form by calling closeBlock()
        closeBlock();
      }
    });

    // Add block button - open panel in create mode
    if (addBlockButton) {
      addBlockButton.addEventListener('click', function(e) {
        e.preventDefault();
        // Call editBlock with null to enter "create mode"
        editBlock(null);
      });
    }
  }

  // Listen for blocks:copied event to reload the page
  document.addEventListener('blocks:copied', function(event) {
    console.log('Blocks copied from locale:', event.detail.sourceLocale, 'to', event.detail.targetLocale);

    // Reload the page to show the newly copied blocks
    if (event.detail.reload) {
      // Small delay to ensure the database transaction is committed
      setTimeout(function() {
        window.location.reload();
      }, 300);
    }
  });

  // Listen for postMessage from block edit iframe
  window.addEventListener('message', function(event) {
    // Security: verify origin if needed
    // if (event.origin !== window.location.origin) return;

    const data = event.data;

    // Handle block:saved message from the form iframe
    if (data && data.type === 'block:saved') {
      console.log('Block saved with ID:', data.blockId);

      // Reload the preview iframe to show the updated block
      const previewIframe = document.querySelector('[data-page-builder-target="iframe"]');
      if (previewIframe) {
        console.log('Reloading preview iframe to show updated block');
        previewIframe.contentWindow.location.reload();
      }
    }
  });

  // Publish modal functionality
  const publishModal = document.getElementById('publishModal');
  if (publishModal) {
    const selectAllCheckbox = document.getElementById('selectAllLocales');
    const localeCheckboxes = document.querySelectorAll('.locale-checkbox');
    const confirmPublishBtn = document.getElementById('confirmPublishBtn');

    // Handle "Select all" checkbox
    if (selectAllCheckbox) {
      selectAllCheckbox.addEventListener('change', function() {
        localeCheckboxes.forEach(checkbox => {
          checkbox.checked = selectAllCheckbox.checked;
        });
      });
    }

    // Update "Select all" checkbox when individual checkboxes change
    localeCheckboxes.forEach(checkbox => {
      checkbox.addEventListener('change', function() {
        const allChecked = Array.from(localeCheckboxes).every(cb => cb.checked);
        const noneChecked = Array.from(localeCheckboxes).every(cb => !cb.checked);

        if (allChecked) {
          selectAllCheckbox.checked = true;
          selectAllCheckbox.indeterminate = false;
        } else if (noneChecked) {
          selectAllCheckbox.checked = false;
          selectAllCheckbox.indeterminate = false;
        } else {
          selectAllCheckbox.indeterminate = true;
        }
      });
    });

    // Handle confirm publish button
    if (confirmPublishBtn) {
      confirmPublishBtn.addEventListener('click', function() {
        // Get selected locales
        const selectedLocales = Array.from(localeCheckboxes)
          .filter(cb => cb.checked)
          .map(cb => cb.value);

        if (selectedLocales.length === 0) {
          alert(selectLocaleMessage);
          return;
        }

        // Build URL with GET parameters
        const currentUrl = new URL(window.location.href);
        currentUrl.searchParams.set('publish', '1');

        // Add locales as array parameters
        currentUrl.searchParams.delete('locales[]'); // Clear existing
        selectedLocales.forEach(locale => {
          currentUrl.searchParams.append('locales[]', locale);
        });

        // Redirect to the URL with publish parameters
        window.location.href = currentUrl.toString();
      });
    }
  }
});
