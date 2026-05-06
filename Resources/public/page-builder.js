/******/ (() => { // webpackBootstrap
/******/ 	var __webpack_modules__ = ({

/***/ "./assets/page-builder/page-builder.js"
/*!*********************************************!*\
  !*** ./assets/page-builder/page-builder.js ***!
  \*********************************************/
() {

document.addEventListener('DOMContentLoaded', function () {
  var iframeUri = document.querySelector('[data-builder-configuration]').dataset.iframeUrl;
  var selectLocaleMessage = document.querySelector('[data-builder-configuration]').dataset.selectLocaleMessage;
  var localeDropdown = document.getElementById('localeDropdown');
  var localeItems = document.querySelectorAll('.dropdown-item[data-locale]');
  var resolutionButtons = document.querySelectorAll('.resolution-selector button');
  var iframeWrapper = document.querySelector('[data-page-builder-target="iframeWrapper"]');

  // Locale dropdown item click handler with confirmation
  if (localeItems.length > 0 && localeDropdown) {
    var currentLocale = localeDropdown.dataset.currentLocale;
    localeItems.forEach(function (item) {
      item.addEventListener('click', function (e) {
        e.preventDefault();
        var newLocale = this.dataset.locale;

        // If locale hasn't changed, do nothing
        if (newLocale === currentLocale) {
          return;
        }

        // Show confirmation dialog
        var confirmed = confirm('Changing the locale will reload the page. Any unsaved changes will be lost. Do you want to continue?');
        if (confirmed) {
          // Build new URL with locale parameter
          var url = new URL(window.location.href);
          url.searchParams.set('locale', newLocale);
          window.location.href = url.toString();
        }
      });
    });
  }
  if (resolutionButtons.length && iframeWrapper) {
    resolutionButtons.forEach(function (button) {
      button.addEventListener('click', function () {
        // Remove active class from all buttons
        resolutionButtons.forEach(function (btn) {
          return btn.classList.remove('active');
        });

        // Add active class to clicked button
        this.classList.add('active');

        // Change iframe wrapper class
        var resolution = this.dataset.resolution;
        iframeWrapper.className = 'page-builder__iframe-wrapper resolution-' + resolution;
      });
    });
  }

  // Alignment toggle functionality
  var alignmentToggle = document.querySelector('[data-page-builder-target="alignmentToggle"]');
  if (alignmentToggle && iframeWrapper) {
    // Cycle through alignments: center → left → right → center
    alignmentToggle.addEventListener('click', function () {
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
  var iframe = document.querySelector('[data-page-builder-target="iframe"]');
  var initialIframeUrl = null; // Store the initial URL to protect against navigation
  var iframeEventsInitialized = false; // Track if events are currently initialized

  // Editor panel elements (used by editBlock function)
  var editorPanel = document.querySelector('[data-page-builder-target="editorPanel"]');
  var editorToggle = document.querySelector('[data-page-builder-target="editorToggle"]');
  var editorContent = document.querySelector('[data-page-builder-target="editorContent"]');

  /**
   * Check if the current iframe URL matches the initial URL
   * Returns true if valid, false otherwise
   */
  function isCurrentIframeUrlValid() {
    if (!initialIframeUrl) return true; // Allow during first load

    try {
      var currentUrl = new URL(iframe.contentWindow.location.href);
      var currentPath = currentUrl.origin + currentUrl.pathname;
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
    var pageBuilderElement = document.querySelector('[data-controller*="agence-adeliom--sylius-happy-cms-plugin--builder-block-editor"]');
    if (pageBuilderElement) {
      var event = new CustomEvent('block-editor:edit', {
        detail: {
          blockId: blockId
        }
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
    var pageBuilderElement = document.querySelector('[data-controller*="agence-adeliom--sylius-happy-cms-plugin--builder-block-editor"]');
    if (pageBuilderElement) {
      var event = new CustomEvent('block-editor:close');
      pageBuilderElement.dispatchEvent(event);
    } else {
      console.warn('Page builder element not found');
    }
    console.log('Block editor closed');
  }
  if (iframe) {
    // Prevent navigation in iframe and re-initialize events on reload
    iframe.addEventListener('load', function () {
      var _iframe$contentWindow;
      console.log('Iframe loaded. URL:', iframe.src);
      console.log('Iframe location:', (_iframe$contentWindow = iframe.contentWindow) === null || _iframe$contentWindow === void 0 || (_iframe$contentWindow = _iframe$contentWindow.location) === null || _iframe$contentWindow === void 0 ? void 0 : _iframe$contentWindow.href);
      try {
        /**
         * Initialize events on elements INSIDE the iframe
         * This function can be called multiple times when returning to the authorized URL
         */
        var initIframeBlockEvents = function initIframeBlockEvents() {
          if (!iframeDocument) return;
          console.log('Initializing iframe block events...');

          // Intercept all link clicks
          iframeDocument.addEventListener('click', function (e) {
            var target = e.target.closest('a');
            if (target && target.href) {
              e.preventDefault();
              console.log('Navigation blocked in preview:', target.href);
              return false;
            }
          }, true);

          // Intercept form submissions
          iframeDocument.addEventListener('submit', function (e) {
            e.preventDefault();
            console.log('Form submission blocked in preview');
            return false;
          }, true);

          // Inject CSS for hover effect in iframe (only if not already present)
          if (!iframeDocument.getElementById('page-builder-styles')) {
            var style = iframeDocument.createElement('style');
            style.id = 'page-builder-styles';
            style.textContent = "\n                                    /* Minimal height for blocks (some are blank) */\n                                    .content-block-wrapper {\n                                      min-height: 150px;\n                                    }\n                                    /* Show outline and shadow on hover */\n                                    .content-block-wrapper.is-hovered {\n                                        outline: 3px solid #1e74fd !important;\n                                        outline-offset: -3px !important;\n                                        box-shadow: 0 0 0 3px rgba(30, 116, 253, 0.2) !important;\n                                        z-index: 100 !important;\n                                    }\n\n                                    /* Show hover overlay when hovering */\n                                    .content-block-wrapper.is-hovered .content-block-hover-overlay {\n                                        display: flex !important;\n                                    }\n\n                                    /* Hide hover overlay for unpublished blocks (they have their own overlay) */\n                                    .content-block-wrapper[data-published=\"false\"] .content-block-hover-overlay {\n                                        display: none !important;\n                                    }\n\n                                    /* Hide hover overlay for deleted blocks (they have their own overlay) */\n                                    .content-block-wrapper[data-deleted=\"true\"] .content-block-hover-overlay {\n                                        display: none !important;\n                                    }\n\n                                    /* Button hover effect */\n                                    .content-block-edit-button:hover {\n                                        background: #0056d6 !important;\n                                        transform: translateY(-2px) !important;\n                                        box-shadow: 0 6px 16px rgba(30, 116, 253, 0.5) !important;\n                                    }\n                                ";
            iframeDocument.head.appendChild(style);
          }

          // Add hover effect on blocks in iframe to highlight handles in sidebar (reverse)
          var contentBlocks = iframeDocument.querySelectorAll('.content-block-wrapper[data-block-id]');
          contentBlocks.forEach(function (block) {
            block.addEventListener('mouseenter', function () {
              // Security check: only apply hover if iframe URL hasn't changed
              if (!isIframeUrlValid()) {
                return;
              }
              var blockId = this.dataset.blockId;
              var handle = document.querySelector(".page-builder__block-handle[data-block-id=\"".concat(blockId, "\"]"));
              if (handle) {
                var handleButton = handle.querySelector('.page-builder__block-handle-button');
                if (handleButton) {
                  handleButton.classList.add('is-hovered');
                }
                // Also highlight the block itself
                this.classList.add('is-hovered');
              }
            });
            block.addEventListener('mouseleave', function () {
              // Security check: only apply hover if iframe URL hasn't changed
              if (!isIframeUrlValid()) {
                return;
              }
              var blockId = this.dataset.blockId;
              var handle = document.querySelector(".page-builder__block-handle[data-block-id=\"".concat(blockId, "\"]"));
              if (handle) {
                var handleButton = handle.querySelector('.page-builder__block-handle-button');
                if (handleButton) {
                  handleButton.classList.remove('is-hovered');
                }
                // Remove highlight from block
                this.classList.remove('is-hovered');
              }
            });
          });

          // Add click handler for edit buttons in iframe (both hover overlay and unpublished overlay)
          var editButtons = iframeDocument.querySelectorAll('.content-block-edit-button');
          editButtons.forEach(function (button) {
            button.addEventListener('click', function (e) {
              e.preventDefault();
              e.stopPropagation();
              var blockId = this.dataset.blockId;
              editBlock(blockId);
            });
          });

          // Remove Symfony toolbar inside iframe
          if (iframeDocument.querySelector('.sf-toolbar')) {
            iframeDocument.querySelector('.sf-toolbar').remove();
          }
          iframeEventsInitialized = true;
          console.log('Iframe block events initialized');
        }; // Initialize iframe events for the first time
        /**
         * Check if the iframe URL has changed from the initial URL
         * Returns true if the URL is still the same (safe to operate), false otherwise
         */
        var isIframeUrlValid = function isIframeUrlValid() {
          if (!initialIframeUrl) return true; // Allow during initialization

          try {
            var currentUrl = new URL(iframe.contentWindow.location.href);
            var currentPath = currentUrl.origin + currentUrl.pathname;
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
        };
        var updateBlockHandlesPositions = function updateBlockHandlesPositions() {
          // Security check: only update if iframe URL hasn't changed
          if (!isIframeUrlValid()) {
            console.warn('Skipping block handles update - iframe URL has changed');
            return;
          }
          if (!blockHandlesContainer) return;

          // Get all content blocks in the iframe
          var contentBlocks = iframeDocument.querySelectorAll('.content-block-wrapper[data-block-id]');
          if (contentBlocks.length === 0) return;
          var viewportHeight = iframeWindow.innerHeight;

          // Get the offset of the handles container to compensate for the add block button
          var addBlockButton = document.querySelector('[data-page-builder-target="addBlock"]');
          var offset = 0;
          if (addBlockButton) {
            // Calculate total height including margins
            var addBlockStyles = window.getComputedStyle(addBlockButton);
            var addBlockHeight = addBlockButton.offsetHeight;
            var addBlockMarginBottom = parseFloat(addBlockStyles.marginBottom);
            var handlesContainerMarginTop = parseFloat(window.getComputedStyle(blockHandlesContainer).marginTop);
            offset = addBlockHeight + addBlockMarginBottom + handlesContainerMarginTop;
          }

          // Position each handle based on its corresponding block in the iframe
          contentBlocks.forEach(function (contentBlock) {
            var blockId = contentBlock.dataset.blockId;
            var handle = document.querySelector(".page-builder__block-handle[data-block-id=\"".concat(blockId, "\"]"));
            if (handle) {
              // Get block position and dimensions in iframe (relative to viewport)
              var blockRect = contentBlock.getBoundingClientRect();

              // Use the position relative to the iframe viewport, compensating for the offset
              var blockTop = blockRect.top - offset;
              var blockHeight = blockRect.height;

              // Set handle position and height
              handle.style.top = blockTop + 'px';
              handle.style.height = blockHeight + 'px';

              // Check if block is visible in viewport
              // A block is considered visible if any part of it is in the viewport
              var isVisible = blockRect.top < viewportHeight && blockRect.bottom > 0;

              // Add/remove active class
              if (isVisible) {
                handle.classList.add('is-active');
              } else {
                handle.classList.remove('is-active');
              }
            }
          });
        }; // Throttle function to limit scroll event frequency
        var throttle = function throttle(func, limit) {
          var inThrottle;
          return function () {
            var args = arguments;
            var context = this;
            if (!inThrottle) {
              func.apply(context, args);
              inThrottle = true;
              setTimeout(function () {
                return inThrottle = false;
              }, limit);
            }
          };
        }; // Update positions on iframe scroll (throttled)
        var iframeDocument = iframe.contentDocument || iframe.contentWindow.document;
        var iframeWindow = iframe.contentWindow;

        // Store initial URL on first load (without query params and hash for comparison)
        if (!initialIframeUrl) {
          var url = new URL(iframeWindow.location.href);
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
        initIframeBlockEvents();

        // Synchronize block handles positions with iframe scroll
        var blockHandles = document.querySelectorAll('.page-builder__block-handle');
        var blockHandlesContainer = document.querySelector('[data-page-builder-target="blockHandles"]');
        var sidebar = document.querySelector('[data-page-builder-target="sidebar"]');
        var throttledUpdate = throttle(updateBlockHandlesPositions, 50);
        iframeWindow.addEventListener('scroll', throttledUpdate);

        // Update positions on iframe resize
        iframeWindow.addEventListener('resize', throttledUpdate);

        // Initial positioning
        setTimeout(updateBlockHandlesPositions, 100);

        // Add hover effect on handles to highlight blocks in iframe
        blockHandles.forEach(function (handle) {
          var handleButton = handle.querySelector('.page-builder__block-handle-button');
          if (!handleButton) return;
          handleButton.addEventListener('mouseenter', function () {
            // Security check: only apply hover if iframe URL hasn't changed
            if (!isIframeUrlValid()) {
              return;
            }
            var blockId = handle.dataset.blockId;
            var block = iframeDocument.querySelector(".content-block-wrapper[data-block-id=\"".concat(blockId, "\"]"));
            if (block) {
              block.classList.add('is-hovered');
            }
          });
          handleButton.addEventListener('mouseleave', function () {
            // Security check: only apply hover if iframe URL hasn't changed
            if (!isIframeUrlValid()) {
              return;
            }
            var blockId = handle.dataset.blockId;
            var block = iframeDocument.querySelector(".content-block-wrapper[data-block-id=\"".concat(blockId, "\"]"));
            if (block) {
              block.classList.remove('is-hovered');
            }
          });

          // Add click handler for sidebar buttons
          handleButton.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var blockId = handle.dataset.blockId;
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
    var lastKnownUrl = null;
    var urlCheckInterval = setInterval(function () {
      try {
        var currentUrl = iframe.contentWindow.location.href;

        // Check if URL has changed
        if (currentUrl !== lastKnownUrl) {
          console.log('Iframe URL changed from', lastKnownUrl, 'to', currentUrl);
          lastKnownUrl = currentUrl;

          // Check if we're back on the authorized URL
          var url = new URL(currentUrl);
          var currentPath = url.origin + url.pathname;
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
  var addBlockButton = document.querySelector('[data-page-builder-target="addBlock"]');
  if (editorPanel && editorToggle) {
    // Toggle button click handler - close panel and reset form
    editorToggle.addEventListener('click', function (e) {
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
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && editorPanel.classList.contains('is-open')) {
        editorPanel.classList.remove('is-open');
        // Reset the form by calling closeBlock()
        closeBlock();
      }
    });

    // Add block button - open panel in create mode
    if (addBlockButton) {
      addBlockButton.addEventListener('click', function (e) {
        e.preventDefault();
        // Call editBlock with null to enter "create mode"
        editBlock(null);
      });
    }
  }

  // Listen for blocks:copied event to reload the page
  document.addEventListener('blocks:copied', function (event) {
    console.log('Blocks copied from locale:', event.detail.sourceLocale, 'to', event.detail.targetLocale);

    // Reload the page to show the newly copied blocks
    if (event.detail.reload) {
      // Small delay to ensure the database transaction is committed
      setTimeout(function () {
        window.location.reload();
      }, 300);
    }
  });

  // Listen for postMessage from block edit iframe
  window.addEventListener('message', function (event) {
    // Security: verify origin if needed
    // if (event.origin !== window.location.origin) return;

    var data = event.data;

    // Handle block:saved message from the form iframe
    if (data && data.type === 'block:saved') {
      console.log('Block saved with ID:', data.blockId);

      // Reload the preview iframe to show the updated block
      var previewIframe = document.querySelector('[data-page-builder-target="iframe"]');
      if (previewIframe) {
        console.log('Reloading preview iframe to show updated block');
        previewIframe.contentWindow.location.reload();
      }
    }
  });

  // Publish modal functionality
  var publishModal = document.getElementById('publishModal');
  if (publishModal) {
    var selectAllCheckbox = document.getElementById('selectAllLocales');
    var localeCheckboxes = document.querySelectorAll('.locale-checkbox');
    var confirmPublishBtn = document.getElementById('confirmPublishBtn');

    // Handle "Select all" checkbox
    if (selectAllCheckbox) {
      selectAllCheckbox.addEventListener('change', function () {
        localeCheckboxes.forEach(function (checkbox) {
          checkbox.checked = selectAllCheckbox.checked;
        });
      });
    }

    // Update "Select all" checkbox when individual checkboxes change
    localeCheckboxes.forEach(function (checkbox) {
      checkbox.addEventListener('change', function () {
        var allChecked = Array.from(localeCheckboxes).every(function (cb) {
          return cb.checked;
        });
        var noneChecked = Array.from(localeCheckboxes).every(function (cb) {
          return !cb.checked;
        });
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
      confirmPublishBtn.addEventListener('click', function () {
        // Get selected locales
        var selectedLocales = Array.from(localeCheckboxes).filter(function (cb) {
          return cb.checked;
        }).map(function (cb) {
          return cb.value;
        });
        if (selectedLocales.length === 0) {
          alert(selectLocaleMessage);
          return;
        }

        // Build URL with GET parameters
        var currentUrl = new URL(window.location.href);
        currentUrl.searchParams.set('publish', '1');

        // Add locales as array parameters
        currentUrl.searchParams["delete"]('locales[]'); // Clear existing
        selectedLocales.forEach(function (locale) {
          currentUrl.searchParams.append('locales[]', locale);
        });

        // Redirect to the URL with publish parameters
        window.location.href = currentUrl.toString();
      });
    }
  }
});

/***/ },

/***/ "./assets/page-builder/page-builder.css"
/*!**********************************************!*\
  !*** ./assets/page-builder/page-builder.css ***!
  \**********************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
// extracted by mini-css-extract-plugin


/***/ }

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		if (!(moduleId in __webpack_modules__)) {
/******/ 			delete __webpack_module_cache__[moduleId];
/******/ 			var e = new Error("Cannot find module '" + moduleId + "'");
/******/ 			e.code = 'MODULE_NOT_FOUND';
/******/ 			throw e;
/******/ 		}
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	(() => {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = (module) => {
/******/ 			var getter = module && module.__esModule ?
/******/ 				() => (module['default']) :
/******/ 				() => (module);
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry needs to be wrapped in an IIFE because it needs to be in strict mode.
(() => {
"use strict";
/*!*******************************************!*\
  !*** ./assets/page-builder/entrypoint.js ***!
  \*******************************************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _page_builder_css__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./page-builder.css */ "./assets/page-builder/page-builder.css");
/* harmony import */ var _page_builder_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./page-builder.js */ "./assets/page-builder/page-builder.js");
/* harmony import */ var _page_builder_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_page_builder_js__WEBPACK_IMPORTED_MODULE_1__);
/**
 * Page Builder Assets Entry Point
 */



})();

/******/ })()
;
//# sourceMappingURL=data:application/json;charset=utf-8;base64,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UyxFQUFFLEdBQUcsQ0FBQzs7UUFFM0M7UUFDQW9CLFVBQVUsQ0FBQ3JCLFlBQVksVUFBTyxDQUFDLFdBQVcsQ0FBQyxDQUFDLENBQUM7UUFDN0N5SCxlQUFlLENBQUN0SSxPQUFPLENBQUMsVUFBQUssTUFBTSxFQUFJO1VBQ2hDNkIsVUFBVSxDQUFDckIsWUFBWSxDQUFDNkgsTUFBTSxDQUFDLFdBQVcsRUFBRXJJLE1BQU0sQ0FBQztRQUNyRCxDQUFDLENBQUM7O1FBRUY7UUFDQUssTUFBTSxDQUFDQyxRQUFRLENBQUNDLElBQUksR0FBR3NCLFVBQVUsQ0FBQ25CLFFBQVEsQ0FBQyxDQUFDO01BQzlDLENBQUMsQ0FBQztJQUNKO0VBQ0Y7QUFDRixDQUFDLENBQUMsQzs7Ozs7Ozs7Ozs7O0FDem9CRjs7Ozs7OztVQ0FBO1VBQ0E7O1VBRUE7VUFDQTtVQUNBO1VBQ0E7VUFDQTtVQUNBO1VBQ0E7VUFDQTtVQUNBO1VBQ0E7VUFDQTtVQUNBO1VBQ0E7O1VBRUE7VUFDQTtVQUNBO1VBQ0E7VUFDQTtVQUNBO1VBQ0E7VUFDQTs7VUFFQTtVQUNBO1VBQ0E7Ozs7O1dDNUJBO1dBQ0E7V0FDQTtXQUNBO1dBQ0E7V0FDQSxpQ0FBaUMsV0FBVztXQUM1QztXQUNBLEU7Ozs7O1dDUEE7V0FDQTtXQUNBO1dBQ0E7V0FDQSx5Q0FBeUMsd0NBQXdDO1dBQ2pGO1dBQ0E7V0FDQSxFOzs7OztXQ1BBLHdGOzs7OztXQ0FBO1dBQ0E7V0FDQTtXQUNBLHVEQUF1RCxpQkFBaUI7V0FDeEU7V0FDQSxnREFBZ0QsYUFBYTtXQUM3RCxFOzs7Ozs7Ozs7Ozs7Ozs7QUNOQTtBQUNBO0FBQ0E7O0FBRTRCIiwic291cmNlcyI6WyJ3ZWJwYWNrOi8vQGFnZW5jZS1hZGVsaW9tL3N5bGl1cy1oYXBweS1jbXMtcGx1Z2luLy4vYXNzZXRzL3BhZ2UtYnVpbGRlci9wYWdlLWJ1aWxkZXIuanMiLCJ3ZWJwYWNrOi8vQGFnZW5jZS1hZGVsaW9tL3N5bGl1cy1oYXBweS1jbXMtcGx1Z2luLy4vYXNzZXRzL3BhZ2UtYnVpbGRlci9wYWdlLWJ1aWxkZXIuY3NzP2EzMzAiLCJ3ZWJwYWNrOi8vQGFnZW5jZS1hZGVsaW9tL3N5bGl1cy1oYXBweS1jbXMtcGx1Z2luL3dlYnBhY2svYm9vdHN0cmFwIiwid2VicGFjazovL0BhZ2VuY2UtYWRlbGlvbS9zeWxpdXMtaGFwcHktY21zLXBsdWdpbi93ZWJwYWNrL3J1bnRpbWUvY29tcGF0IGdldCBkZWZhdWx0IGV4cG9ydCIsIndlYnBhY2s6Ly9AYWdlbmNlLWFkZWxpb20vc3lsaXVzLWhhcHB5LWNtcy1wbHVnaW4vd2VicGFjay9ydW50aW1lL2RlZmluZSBwcm9wZXJ0eSBnZXR0ZXJzIiwid2VicGFjazovL0BhZ2VuY2UtYWRlbGlvbS9zeWxpdXMtaGFwcHktY21zLXBsdWdpbi93ZWJwYWNrL3J1bnRpbWUvaGFzT3duUHJvcGVydHkgc2hvcnRoYW5kIiwid2VicGFjazovL0BhZ2VuY2UtYWRlbGlvbS9zeWxpdXMtaGFwcHktY21zLXBsdWdpbi93ZWJwYWNrL3J1bnRpbWUvbWFrZSBuYW1lc3BhY2Ugb2JqZWN0Iiwid2VicGFjazovL0BhZ2VuY2UtYWRlbGlvbS9zeWxpdXMtaGFwcHktY21zLXBsdWdpbi8uL2Fzc2V0cy9wYWdlLWJ1aWxkZXIvZW50cnlwb2ludC5qcyJdLCJzb3VyY2VzQ29udGVudCI6WyJkb2N1bWVudC5hZGRFdmVudExpc3RlbmVyKCdET01Db250ZW50TG9hZGVkJywgZnVuY3Rpb24oKSB7XG4gIGNvbnN0IGlmcmFtZVVyaSA9IGRvY3VtZW50LnF1ZXJ5U2VsZWN0b3IoJ1tkYXRhLWJ1aWxkZXItY29uZmlndXJhdGlvbl0nKS5kYXRhc2V0LmlmcmFtZVVybDtcbiAgY29uc3Qgc2VsZWN0TG9jYWxlTWVzc2FnZSA9IGRvY3VtZW50LnF1ZXJ5U2VsZWN0b3IoJ1tkYXRhLWJ1aWxkZXItY29uZmlndXJhdGlvbl0nKS5kYXRhc2V0LnNlbGVjdExvY2FsZU1lc3NhZ2U7XG5cbiAgY29uc3QgbG9jYWxlRHJvcGRvd24gPSBkb2N1bWVudC5nZXRFbGVtZW50QnlJZCgnbG9jYWxlRHJvcGRvd24nKTtcbiAgY29uc3QgbG9jYWxlSXRlbXMgPSBkb2N1bWVudC5xdWVyeVNlbGVjdG9yQWxsKCcuZHJvcGRvd24taXRlbVtkYXRhLWxvY2FsZV0nKTtcbiAgY29uc3QgcmVzb2x1dGlvbkJ1dHRvbnMgPSBkb2N1bWVudC5xdWVyeVNlbGVjdG9yQWxsKCcucmVzb2x1dGlvbi1zZWxlY3RvciBidXR0b24nKTtcbiAgY29uc3QgaWZyYW1lV3JhcHBlciA9IGRvY3VtZW50LnF1ZXJ5U2VsZWN0b3IoJ1tkYXRhLXBhZ2UtYnVpbGRlci10YXJnZXQ9XCJpZnJhbWVXcmFwcGVyXCJdJyk7XG5cbiAgLy8gTG9jYWxlIGRyb3Bkb3duIGl0ZW0gY2xpY2sgaGFuZGxlciB3aXRoIGNvbmZpcm1hdGlvblxuICBpZiAobG9jYWxlSXRlbXMubGVuZ3RoID4gMCAmJiBsb2NhbGVEcm9wZG93bikge1xuICAgIGNvbnN0IGN1cnJlbnRMb2NhbGUgPSBsb2NhbGVEcm9wZG93bi5kYXRhc2V0LmN1cnJlbnRMb2NhbGU7XG5cbiAgICBsb2NhbGVJdGVtcy5mb3JFYWNoKGl0ZW0gPT4ge1xuICAgICAgaXRlbS5hZGRFdmVudExpc3RlbmVyKCdjbGljaycsIGZ1bmN0aW9uKGUpIHtcbiAgICAgICAgZS5wcmV2ZW50RGVmYXVsdCgpO1xuXG4gICAgICAgIGNvbnN0IG5ld0xvY2FsZSA9IHRoaXMuZGF0YXNldC5sb2NhbGU7XG5cbiAgICAgICAgLy8gSWYgbG9jYWxlIGhhc24ndCBjaGFuZ2VkLCBkbyBub3RoaW5nXG4gICAgICAgIGlmIChuZXdMb2NhbGUgPT09IGN1cnJlbnRMb2NhbGUpIHtcbiAgICAgICAgICByZXR1cm47XG4gICAgICAgIH1cblxuICAgICAgICAvLyBTaG93IGNvbmZpcm1hdGlvbiBkaWFsb2dcbiAgICAgICAgY29uc3QgY29uZmlybWVkID0gY29uZmlybShcbiAgICAgICAgICAnQ2hhbmdpbmcgdGhlIGxvY2FsZSB3aWxsIHJlbG9hZCB0aGUgcGFnZS4gQW55IHVuc2F2ZWQgY2hhbmdlcyB3aWxsIGJlIGxvc3QuIERvIHlvdSB3YW50IHRvIGNvbnRpbnVlPydcbiAgICAgICAgKTtcblxuICAgICAgICBpZiAoY29uZmlybWVkKSB7XG4gICAgICAgICAgLy8gQnVpbGQgbmV3IFVSTCB3aXRoIGxvY2FsZSBwYXJhbWV0ZXJcbiAgICAgICAgICBjb25zdCB1cmwgPSBuZXcgVVJMKHdpbmRvdy5sb2NhdGlvbi5ocmVmKTtcbiAgICAgICAgICB1cmwuc2VhcmNoUGFyYW1zLnNldCgnbG9jYWxlJywgbmV3TG9jYWxlKTtcbiAgICAgICAgICB3aW5kb3cubG9jYXRpb24uaHJlZiA9IHVybC50b1N0cmluZygpO1xuICAgICAgICB9XG4gICAgICB9KTtcbiAgICB9KTtcbiAgfVxuXG4gIGlmIChyZXNvbHV0aW9uQnV0dG9ucy5sZW5ndGggJiYgaWZyYW1lV3JhcHBlcikge1xuICAgIHJlc29sdXRpb25CdXR0b25zLmZvckVhY2goYnV0dG9uID0+IHtcbiAgICAgIGJ1dHRvbi5hZGRFdmVudExpc3RlbmVyKCdjbGljaycsIGZ1bmN0aW9uKCkge1xuICAgICAgICAvLyBSZW1vdmUgYWN0aXZlIGNsYXNzIGZyb20gYWxsIGJ1dHRvbnNcbiAgICAgICAgcmVzb2x1dGlvbkJ1dHRvbnMuZm9yRWFjaChidG4gPT4gYnRuLmNsYXNzTGlzdC5yZW1vdmUoJ2FjdGl2ZScpKTtcblxuICAgICAgICAvLyBBZGQgYWN0aXZlIGNsYXNzIHRvIGNsaWNrZWQgYnV0dG9uXG4gICAgICAgIHRoaXMuY2xhc3NMaXN0LmFkZCgnYWN0aXZlJyk7XG5cbiAgICAgICAgLy8gQ2hhbmdlIGlmcmFtZSB3cmFwcGVyIGNsYXNzXG4gICAgICAgIGNvbnN0IHJlc29sdXRpb24gPSB0aGlzLmRhdGFzZXQucmVzb2x1dGlvbjtcbiAgICAgICAgaWZyYW1lV3JhcHBlci5jbGFzc05hbWUgPSAncGFnZS1idWlsZGVyX19pZnJhbWUtd3JhcHBlciByZXNvbHV0aW9uLScgKyByZXNvbHV0aW9uO1xuICAgICAgfSk7XG4gICAgfSk7XG4gIH1cblxuICAvLyBBbGlnbm1lbnQgdG9nZ2xlIGZ1bmN0aW9uYWxpdHlcbiAgY29uc3QgYWxpZ25tZW50VG9nZ2xlID0gZG9jdW1lbnQucXVlcnlTZWxlY3RvcignW2RhdGEtcGFnZS1idWlsZGVyLXRhcmdldD1cImFsaWdubWVudFRvZ2dsZVwiXScpO1xuXG4gIGlmIChhbGlnbm1lbnRUb2dnbGUgJiYgaWZyYW1lV3JhcHBlcikge1xuICAgIC8vIEN5Y2xlIHRocm91Z2ggYWxpZ25tZW50czogY2VudGVyIOKGkiBsZWZ0IOKGkiByaWdodCDihpIgY2VudGVyXG4gICAgYWxpZ25tZW50VG9nZ2xlLmFkZEV2ZW50TGlzdGVuZXIoJ2NsaWNrJywgZnVuY3Rpb24oKSB7XG4gICAgICB0aGlzLmNsYXNzTGlzdC50b2dnbGUoJ2J0bi1vdXRsaW5lLXByaW1hcnknKTtcbiAgICAgIHRoaXMuY2xhc3NMaXN0LnRvZ2dsZSgnYnRuLW91dGxpbmUtc2Vjb25kYXJ5Jyk7XG5cbiAgICAgIGlmIChpZnJhbWVXcmFwcGVyLnN0eWxlLm1hcmdpbiA9PT0gJzBweCBhdXRvJykge1xuICAgICAgICBpZnJhbWVXcmFwcGVyLnN0eWxlLm1hcmdpbiA9ICcwcHgnO1xuICAgICAgfSBlbHNlIHtcbiAgICAgICAgaWZyYW1lV3JhcHBlci5zdHlsZS5tYXJnaW4gPSAnMHB4IGF1dG8nO1xuICAgICAgfVxuXG4gICAgfSk7XG4gIH1cblxuICAvLyBMb2FkIHRoZSBwcmV2aWV3IFVSTCBpbiB0aGUgaWZyYW1lXG4gIGNvbnN0IGlmcmFtZSA9IGRvY3VtZW50LnF1ZXJ5U2VsZWN0b3IoJ1tkYXRhLXBhZ2UtYnVpbGRlci10YXJnZXQ9XCJpZnJhbWVcIl0nKTtcbiAgbGV0IGluaXRpYWxJZnJhbWVVcmwgPSBudWxsOyAvLyBTdG9yZSB0aGUgaW5pdGlhbCBVUkwgdG8gcHJvdGVjdCBhZ2FpbnN0IG5hdmlnYXRpb25cbiAgbGV0IGlmcmFtZUV2ZW50c0luaXRpYWxpemVkID0gZmFsc2U7IC8vIFRyYWNrIGlmIGV2ZW50cyBhcmUgY3VycmVudGx5IGluaXRpYWxpemVkXG5cbiAgLy8gRWRpdG9yIHBhbmVsIGVsZW1lbnRzICh1c2VkIGJ5IGVkaXRCbG9jayBmdW5jdGlvbilcbiAgY29uc3QgZWRpdG9yUGFuZWwgPSBkb2N1bWVudC5xdWVyeVNlbGVjdG9yKCdbZGF0YS1wYWdlLWJ1aWxkZXItdGFyZ2V0PVwiZWRpdG9yUGFuZWxcIl0nKTtcbiAgY29uc3QgZWRpdG9yVG9nZ2xlID0gZG9jdW1lbnQucXVlcnlTZWxlY3RvcignW2RhdGEtcGFnZS1idWlsZGVyLXRhcmdldD1cImVkaXRvclRvZ2dsZVwiXScpO1xuICBjb25zdCBlZGl0b3JDb250ZW50ID0gZG9jdW1lbnQucXVlcnlTZWxlY3RvcignW2RhdGEtcGFnZS1idWlsZGVyLXRhcmdldD1cImVkaXRvckNvbnRlbnRcIl0nKTtcblxuICAvKipcbiAgICogQ2hlY2sgaWYgdGhlIGN1cnJlbnQgaWZyYW1lIFVSTCBtYXRjaGVzIHRoZSBpbml0aWFsIFVSTFxuICAgKiBSZXR1cm5zIHRydWUgaWYgdmFsaWQsIGZhbHNlIG90aGVyd2lzZVxuICAgKi9cbiAgZnVuY3Rpb24gaXNDdXJyZW50SWZyYW1lVXJsVmFsaWQoKSB7XG4gICAgaWYgKCFpbml0aWFsSWZyYW1lVXJsKSByZXR1cm4gdHJ1ZTsgLy8gQWxsb3cgZHVyaW5nIGZpcnN0IGxvYWRcblxuICAgIHRyeSB7XG4gICAgICBjb25zdCBjdXJyZW50VXJsID0gbmV3IFVSTChpZnJhbWUuY29udGVudFdpbmRvdy5sb2NhdGlvbi5ocmVmKTtcbiAgICAgIGNvbnN0IGN1cnJlbnRQYXRoID0gY3VycmVudFVybC5vcmlnaW4gKyBjdXJyZW50VXJsLnBhdGhuYW1lO1xuICAgICAgcmV0dXJuIGN1cnJlbnRQYXRoID09PSBpbml0aWFsSWZyYW1lVXJsO1xuICAgIH0gY2F0Y2ggKGVycm9yKSB7XG4gICAgICBjb25zb2xlLmVycm9yKCdDYW5ub3QgY2hlY2sgaWZyYW1lIFVSTDonLCBlcnJvcik7XG4gICAgICByZXR1cm4gZmFsc2U7XG4gICAgfVxuICB9XG5cbiAgLyoqXG4gICAqIENvbW1vbiBmdW5jdGlvbiB0byBoYW5kbGUgYmxvY2sgZWRpdGluZ1xuICAgKiBBY2Nlc3NpYmxlIGdsb2JhbGx5IGZvciBhbGwgYnV0dG9ucyAoc2lkZWJhciwgaWZyYW1lLCBhZGQgYmxvY2spXG4gICAqL1xuICBmdW5jdGlvbiBlZGl0QmxvY2soYmxvY2tJZCkge1xuICAgIGNvbnNvbGUubG9nKCdFZGl0IGJsb2NrOicsIGJsb2NrSWQpO1xuXG4gICAgLy8gU2VjdXJpdHkgY2hlY2s6IG9ubHkgYWxsb3cgZWRpdGluZyBpZiBpZnJhbWUgVVJMIGhhc24ndCBjaGFuZ2VkIChza2lwIGZvciBjcmVhdGUgbW9kZSlcbiAgICBpZiAoYmxvY2tJZCAhPT0gbnVsbCAmJiAhaXNDdXJyZW50SWZyYW1lVXJsVmFsaWQoKSkge1xuICAgICAgY29uc29sZS5lcnJvcignQ2Fubm90IGVkaXQgYmxvY2sgLSBpZnJhbWUgVVJMIGhhcyBjaGFuZ2VkJyk7XG4gICAgICBhbGVydCgnVGhlIHBhZ2UgaGFzIGNoYW5nZWQuIFBsZWFzZSByZWxvYWQgdGhlIHBhZ2UgYnVpbGRlciB0byBjb250aW51ZSBlZGl0aW5nLicpO1xuICAgICAgcmV0dXJuO1xuICAgIH1cblxuICAgIGlmICghZWRpdG9yUGFuZWwpIHJldHVybjtcblxuICAgIC8vIERpc3BhdGNoIGN1c3RvbSBldmVudCB0byB0aGUgQmxvY2tFZGl0b3IgU3RpbXVsdXMgY29udHJvbGxlclxuICAgIGNvbnN0IHBhZ2VCdWlsZGVyRWxlbWVudCA9IGRvY3VtZW50LnF1ZXJ5U2VsZWN0b3IoJ1tkYXRhLWNvbnRyb2xsZXIqPVwiYWdlbmNlLWFkZWxpb20tLXN5bGl1cy1oYXBweS1jbXMtcGx1Z2luLS1idWlsZGVyLWJsb2NrLWVkaXRvclwiXScpO1xuICAgIGlmIChwYWdlQnVpbGRlckVsZW1lbnQpIHtcbiAgICAgIGNvbnN0IGV2ZW50ID0gbmV3IEN1c3RvbUV2ZW50KCdibG9jay1lZGl0b3I6ZWRpdCcsIHtcbiAgICAgICAgZGV0YWlsOiB7IGJsb2NrSWQ6IGJsb2NrSWQgfVxuICAgICAgfSk7XG4gICAgICBwYWdlQnVpbGRlckVsZW1lbnQuZGlzcGF0Y2hFdmVudChldmVudCk7XG4gICAgfSBlbHNlIHtcbiAgICAgIGNvbnNvbGUud2FybignUGFnZSBidWlsZGVyIGVsZW1lbnQgbm90IGZvdW5kJyk7XG4gICAgfVxuXG4gICAgLy8gT3BlbiB0aGUgb3ZlcmxheSBwYW5lbFxuICAgIGVkaXRvclBhbmVsLmNsYXNzTGlzdC5hZGQoJ2lzLW9wZW4nKTtcblxuICAgIGNvbnNvbGUubG9nKCdQYW5lbCBvcGVuZWQgZm9yIGJsb2NrOicsIGJsb2NrSWQpO1xuICB9XG5cbiAgLyoqXG4gICAqIENvbW1vbiBmdW5jdGlvbiB0byBjbG9zZSB0aGUgYmxvY2sgZWRpdG9yIHBhbmVsXG4gICAqIFJlc2V0cyB0aGUgZm9ybSBhbmQgY2xvc2VzIHRoZSBwYW5lbFxuICAgKi9cbiAgZnVuY3Rpb24gY2xvc2VCbG9jaygpIHtcbiAgICBjb25zb2xlLmxvZygnQ2xvc2UgYmxvY2sgZWRpdG9yJyk7XG5cbiAgICBpZiAoIWVkaXRvclBhbmVsKSByZXR1cm47XG5cbiAgICAvLyBEaXNwYXRjaCBjdXN0b20gZXZlbnQgdG8gdGhlIEJsb2NrRWRpdG9yIFN0aW11bHVzIGNvbnRyb2xsZXJcbiAgICBjb25zdCBwYWdlQnVpbGRlckVsZW1lbnQgPSBkb2N1bWVudC5xdWVyeVNlbGVjdG9yKCdbZGF0YS1jb250cm9sbGVyKj1cImFnZW5jZS1hZGVsaW9tLS1zeWxpdXMtaGFwcHktY21zLXBsdWdpbi0tYnVpbGRlci1ibG9jay1lZGl0b3JcIl0nKTtcbiAgICBpZiAocGFnZUJ1aWxkZXJFbGVtZW50KSB7XG4gICAgICBjb25zdCBldmVudCA9IG5ldyBDdXN0b21FdmVudCgnYmxvY2stZWRpdG9yOmNsb3NlJyk7XG4gICAgICBwYWdlQnVpbGRlckVsZW1lbnQuZGlzcGF0Y2hFdmVudChldmVudCk7XG4gICAgfSBlbHNlIHtcbiAgICAgIGNvbnNvbGUud2FybignUGFnZSBidWlsZGVyIGVsZW1lbnQgbm90IGZvdW5kJyk7XG4gICAgfVxuXG4gICAgY29uc29sZS5sb2coJ0Jsb2NrIGVkaXRvciBjbG9zZWQnKTtcbiAgfVxuXG4gIGlmIChpZnJhbWUpIHtcblxuICAgIC8vIFByZXZlbnQgbmF2aWdhdGlvbiBpbiBpZnJhbWUgYW5kIHJlLWluaXRpYWxpemUgZXZlbnRzIG9uIHJlbG9hZFxuICAgIGlmcmFtZS5hZGRFdmVudExpc3RlbmVyKCdsb2FkJywgZnVuY3Rpb24oKSB7XG4gICAgICBjb25zb2xlLmxvZygnSWZyYW1lIGxvYWRlZC4gVVJMOicsIGlmcmFtZS5zcmMpO1xuICAgICAgY29uc29sZS5sb2coJ0lmcmFtZSBsb2NhdGlvbjonLCBpZnJhbWUuY29udGVudFdpbmRvdz8ubG9jYXRpb24/LmhyZWYpO1xuXG4gICAgICB0cnkge1xuICAgICAgICBjb25zdCBpZnJhbWVEb2N1bWVudCA9IGlmcmFtZS5jb250ZW50RG9jdW1lbnQgfHwgaWZyYW1lLmNvbnRlbnRXaW5kb3cuZG9jdW1lbnQ7XG4gICAgICAgIGNvbnN0IGlmcmFtZVdpbmRvdyA9IGlmcmFtZS5jb250ZW50V2luZG93O1xuXG4gICAgICAgIC8vIFN0b3JlIGluaXRpYWwgVVJMIG9uIGZpcnN0IGxvYWQgKHdpdGhvdXQgcXVlcnkgcGFyYW1zIGFuZCBoYXNoIGZvciBjb21wYXJpc29uKVxuICAgICAgICBpZiAoIWluaXRpYWxJZnJhbWVVcmwpIHtcbiAgICAgICAgICBjb25zdCB1cmwgPSBuZXcgVVJMKGlmcmFtZVdpbmRvdy5sb2NhdGlvbi5ocmVmKTtcbiAgICAgICAgICAvLyBTdG9yZSBwYXRobmFtZSBvbmx5IChpZ25vcmUgcXVlcnkgcGFyYW1zIGFuZCBoYXNoKVxuICAgICAgICAgIGluaXRpYWxJZnJhbWVVcmwgPSB1cmwub3JpZ2luICsgdXJsLnBhdGhuYW1lO1xuICAgICAgICAgIGNvbnNvbGUubG9nKCdJbml0aWFsIGlmcmFtZSBVUkwgc3RvcmVkOicsIGluaXRpYWxJZnJhbWVVcmwpO1xuICAgICAgICB9XG5cbiAgICAgICAgLy8gQ2hlY2sgaWYgdGhlIGxvYWRlZCBVUkwgaXMgdGhlIGF1dGhvcml6ZWQgb25lXG4gICAgICAgIGlmICghaXNDdXJyZW50SWZyYW1lVXJsVmFsaWQoKSkge1xuICAgICAgICAgIGNvbnNvbGUud2FybignSWZyYW1lIGxvYWRlZCB3aXRoIHVuYXV0aG9yaXplZCBVUkwsIHNraXBwaW5nIGV2ZW50IGluaXRpYWxpemF0aW9uJyk7XG4gICAgICAgICAgaWZyYW1lRXZlbnRzSW5pdGlhbGl6ZWQgPSBmYWxzZTtcbiAgICAgICAgICByZXR1cm47XG4gICAgICAgIH1cblxuICAgICAgICBjb25zb2xlLmxvZygnU3VjY2Vzc2Z1bGx5IGFjY2Vzc2VkIGlmcmFtZSBkb2N1bWVudCAtIGluaXRpYWxpemluZyBldmVudHMnKTtcblxuICAgICAgICAvKipcbiAgICAgICAgICogSW5pdGlhbGl6ZSBldmVudHMgb24gZWxlbWVudHMgSU5TSURFIHRoZSBpZnJhbWVcbiAgICAgICAgICogVGhpcyBmdW5jdGlvbiBjYW4gYmUgY2FsbGVkIG11bHRpcGxlIHRpbWVzIHdoZW4gcmV0dXJuaW5nIHRvIHRoZSBhdXRob3JpemVkIFVSTFxuICAgICAgICAgKi9cbiAgICAgICAgZnVuY3Rpb24gaW5pdElmcmFtZUJsb2NrRXZlbnRzKCkge1xuICAgICAgICAgIGlmICghaWZyYW1lRG9jdW1lbnQpIHJldHVybjtcblxuICAgICAgICAgIGNvbnNvbGUubG9nKCdJbml0aWFsaXppbmcgaWZyYW1lIGJsb2NrIGV2ZW50cy4uLicpO1xuXG4gICAgICAgICAgLy8gSW50ZXJjZXB0IGFsbCBsaW5rIGNsaWNrc1xuICAgICAgICAgIGlmcmFtZURvY3VtZW50LmFkZEV2ZW50TGlzdGVuZXIoJ2NsaWNrJywgZnVuY3Rpb24oZSkge1xuICAgICAgICAgICAgY29uc3QgdGFyZ2V0ID0gZS50YXJnZXQuY2xvc2VzdCgnYScpO1xuICAgICAgICAgICAgaWYgKHRhcmdldCAmJiB0YXJnZXQuaHJlZikge1xuICAgICAgICAgICAgICBlLnByZXZlbnREZWZhdWx0KCk7XG4gICAgICAgICAgICAgIGNvbnNvbGUubG9nKCdOYXZpZ2F0aW9uIGJsb2NrZWQgaW4gcHJldmlldzonLCB0YXJnZXQuaHJlZik7XG4gICAgICAgICAgICAgIHJldHVybiBmYWxzZTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICB9LCB0cnVlKTtcblxuICAgICAgICAgIC8vIEludGVyY2VwdCBmb3JtIHN1Ym1pc3Npb25zXG4gICAgICAgICAgaWZyYW1lRG9jdW1lbnQuYWRkRXZlbnRMaXN0ZW5lcignc3VibWl0JywgZnVuY3Rpb24oZSkge1xuICAgICAgICAgICAgZS5wcmV2ZW50RGVmYXVsdCgpO1xuICAgICAgICAgICAgY29uc29sZS5sb2coJ0Zvcm0gc3VibWlzc2lvbiBibG9ja2VkIGluIHByZXZpZXcnKTtcbiAgICAgICAgICAgIHJldHVybiBmYWxzZTtcbiAgICAgICAgICB9LCB0cnVlKTtcblxuICAgICAgICAgIC8vIEluamVjdCBDU1MgZm9yIGhvdmVyIGVmZmVjdCBpbiBpZnJhbWUgKG9ubHkgaWYgbm90IGFscmVhZHkgcHJlc2VudClcbiAgICAgICAgICBpZiAoIWlmcmFtZURvY3VtZW50LmdldEVsZW1lbnRCeUlkKCdwYWdlLWJ1aWxkZXItc3R5bGVzJykpIHtcbiAgICAgICAgICAgIGNvbnN0IHN0eWxlID0gaWZyYW1lRG9jdW1lbnQuY3JlYXRlRWxlbWVudCgnc3R5bGUnKTtcbiAgICAgICAgICAgIHN0eWxlLmlkID0gJ3BhZ2UtYnVpbGRlci1zdHlsZXMnO1xuICAgICAgICAgICAgc3R5bGUudGV4dENvbnRlbnQgPSBgXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAvKiBNaW5pbWFsIGhlaWdodCBmb3IgYmxvY2tzIChzb21lIGFyZSBibGFuaykgKi9cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIC5jb250ZW50LWJsb2NrLXdyYXBwZXIge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBtaW4taGVpZ2h0OiAxNTBweDtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIC8qIFNob3cgb3V0bGluZSBhbmQgc2hhZG93IG9uIGhvdmVyICovXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAuY29udGVudC1ibG9jay13cmFwcGVyLmlzLWhvdmVyZWQge1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIG91dGxpbmU6IDNweCBzb2xpZCAjMWU3NGZkICFpbXBvcnRhbnQ7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgb3V0bGluZS1vZmZzZXQ6IC0zcHggIWltcG9ydGFudDtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBib3gtc2hhZG93OiAwIDAgMCAzcHggcmdiYSgzMCwgMTE2LCAyNTMsIDAuMikgIWltcG9ydGFudDtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB6LWluZGV4OiAxMDAgIWltcG9ydGFudDtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgLyogU2hvdyBob3ZlciBvdmVybGF5IHdoZW4gaG92ZXJpbmcgKi9cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIC5jb250ZW50LWJsb2NrLXdyYXBwZXIuaXMtaG92ZXJlZCAuY29udGVudC1ibG9jay1ob3Zlci1vdmVybGF5IHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICBkaXNwbGF5OiBmbGV4ICFpbXBvcnRhbnQ7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICB9XG5cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIC8qIEhpZGUgaG92ZXIgb3ZlcmxheSBmb3IgdW5wdWJsaXNoZWQgYmxvY2tzICh0aGV5IGhhdmUgdGhlaXIgb3duIG92ZXJsYXkpICovXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAuY29udGVudC1ibG9jay13cmFwcGVyW2RhdGEtcHVibGlzaGVkPVwiZmFsc2VcIl0gLmNvbnRlbnQtYmxvY2staG92ZXItb3ZlcmxheSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgZGlzcGxheTogbm9uZSAhaW1wb3J0YW50O1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAvKiBIaWRlIGhvdmVyIG92ZXJsYXkgZm9yIGRlbGV0ZWQgYmxvY2tzICh0aGV5IGhhdmUgdGhlaXIgb3duIG92ZXJsYXkpICovXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAuY29udGVudC1ibG9jay13cmFwcGVyW2RhdGEtZGVsZXRlZD1cInRydWVcIl0gLmNvbnRlbnQtYmxvY2staG92ZXItb3ZlcmxheSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgZGlzcGxheTogbm9uZSAhaW1wb3J0YW50O1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAvKiBCdXR0b24gaG92ZXIgZWZmZWN0ICovXG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAuY29udGVudC1ibG9jay1lZGl0LWJ1dHRvbjpob3ZlciB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgYmFja2dyb3VuZDogIzAwNTZkNiAhaW1wb3J0YW50O1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIHRyYW5zZm9ybTogdHJhbnNsYXRlWSgtMnB4KSAhaW1wb3J0YW50O1xuICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIGJveC1zaGFkb3c6IDAgNnB4IDE2cHggcmdiYSgzMCwgMTE2LCAyNTMsIDAuNSkgIWltcG9ydGFudDtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICAgICAgICAgICAgICAgICAgYDtcbiAgICAgICAgICAgIGlmcmFtZURvY3VtZW50LmhlYWQuYXBwZW5kQ2hpbGQoc3R5bGUpO1xuICAgICAgICAgIH1cblxuICAgICAgICAgIC8vIEFkZCBob3ZlciBlZmZlY3Qgb24gYmxvY2tzIGluIGlmcmFtZSB0byBoaWdobGlnaHQgaGFuZGxlcyBpbiBzaWRlYmFyIChyZXZlcnNlKVxuICAgICAgICAgIGNvbnN0IGNvbnRlbnRCbG9ja3MgPSBpZnJhbWVEb2N1bWVudC5xdWVyeVNlbGVjdG9yQWxsKCcuY29udGVudC1ibG9jay13cmFwcGVyW2RhdGEtYmxvY2staWRdJyk7XG4gICAgICAgICAgY29udGVudEJsb2Nrcy5mb3JFYWNoKGJsb2NrID0+IHtcbiAgICAgICAgICAgIGJsb2NrLmFkZEV2ZW50TGlzdGVuZXIoJ21vdXNlZW50ZXInLCBmdW5jdGlvbigpIHtcbiAgICAgICAgICAgICAgLy8gU2VjdXJpdHkgY2hlY2s6IG9ubHkgYXBwbHkgaG92ZXIgaWYgaWZyYW1lIFVSTCBoYXNuJ3QgY2hhbmdlZFxuICAgICAgICAgICAgICBpZiAoIWlzSWZyYW1lVXJsVmFsaWQoKSkge1xuICAgICAgICAgICAgICAgIHJldHVybjtcbiAgICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICAgIGNvbnN0IGJsb2NrSWQgPSB0aGlzLmRhdGFzZXQuYmxvY2tJZDtcbiAgICAgICAgICAgICAgY29uc3QgaGFuZGxlID0gZG9jdW1lbnQucXVlcnlTZWxlY3RvcihgLnBhZ2UtYnVpbGRlcl9fYmxvY2staGFuZGxlW2RhdGEtYmxvY2staWQ9XCIke2Jsb2NrSWR9XCJdYCk7XG4gICAgICAgICAgICAgIGlmIChoYW5kbGUpIHtcbiAgICAgICAgICAgICAgICBjb25zdCBoYW5kbGVCdXR0b24gPSBoYW5kbGUucXVlcnlTZWxlY3RvcignLnBhZ2UtYnVpbGRlcl9fYmxvY2staGFuZGxlLWJ1dHRvbicpO1xuICAgICAgICAgICAgICAgIGlmIChoYW5kbGVCdXR0b24pIHtcbiAgICAgICAgICAgICAgICAgIGhhbmRsZUJ1dHRvbi5jbGFzc0xpc3QuYWRkKCdpcy1ob3ZlcmVkJyk7XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgIC8vIEFsc28gaGlnaGxpZ2h0IHRoZSBibG9jayBpdHNlbGZcbiAgICAgICAgICAgICAgICB0aGlzLmNsYXNzTGlzdC5hZGQoJ2lzLWhvdmVyZWQnKTtcbiAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgfSk7XG5cbiAgICAgICAgICAgIGJsb2NrLmFkZEV2ZW50TGlzdGVuZXIoJ21vdXNlbGVhdmUnLCBmdW5jdGlvbigpIHtcbiAgICAgICAgICAgICAgLy8gU2VjdXJpdHkgY2hlY2s6IG9ubHkgYXBwbHkgaG92ZXIgaWYgaWZyYW1lIFVSTCBoYXNuJ3QgY2hhbmdlZFxuICAgICAgICAgICAgICBpZiAoIWlzSWZyYW1lVXJsVmFsaWQoKSkge1xuICAgICAgICAgICAgICAgIHJldHVybjtcbiAgICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICAgIGNvbnN0IGJsb2NrSWQgPSB0aGlzLmRhdGFzZXQuYmxvY2tJZDtcbiAgICAgICAgICAgICAgY29uc3QgaGFuZGxlID0gZG9jdW1lbnQucXVlcnlTZWxlY3RvcihgLnBhZ2UtYnVpbGRlcl9fYmxvY2staGFuZGxlW2RhdGEtYmxvY2staWQ9XCIke2Jsb2NrSWR9XCJdYCk7XG4gICAgICAgICAgICAgIGlmIChoYW5kbGUpIHtcbiAgICAgICAgICAgICAgICBjb25zdCBoYW5kbGVCdXR0b24gPSBoYW5kbGUucXVlcnlTZWxlY3RvcignLnBhZ2UtYnVpbGRlcl9fYmxvY2staGFuZGxlLWJ1dHRvbicpO1xuICAgICAgICAgICAgICAgIGlmIChoYW5kbGVCdXR0b24pIHtcbiAgICAgICAgICAgICAgICAgIGhhbmRsZUJ1dHRvbi5jbGFzc0xpc3QucmVtb3ZlKCdpcy1ob3ZlcmVkJyk7XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgIC8vIFJlbW92ZSBoaWdobGlnaHQgZnJvbSBibG9ja1xuICAgICAgICAgICAgICAgIHRoaXMuY2xhc3NMaXN0LnJlbW92ZSgnaXMtaG92ZXJlZCcpO1xuICAgICAgICAgICAgICB9XG4gICAgICAgICAgICB9KTtcbiAgICAgICAgICB9KTtcblxuICAgICAgICAgIC8vIEFkZCBjbGljayBoYW5kbGVyIGZvciBlZGl0IGJ1dHRvbnMgaW4gaWZyYW1lIChib3RoIGhvdmVyIG92ZXJsYXkgYW5kIHVucHVibGlzaGVkIG92ZXJsYXkpXG4gICAgICAgICAgY29uc3QgZWRpdEJ1dHRvbnMgPSBpZnJhbWVEb2N1bWVudC5xdWVyeVNlbGVjdG9yQWxsKCcuY29udGVudC1ibG9jay1lZGl0LWJ1dHRvbicpO1xuICAgICAgICAgIGVkaXRCdXR0b25zLmZvckVhY2goYnV0dG9uID0+IHtcbiAgICAgICAgICAgIGJ1dHRvbi5hZGRFdmVudExpc3RlbmVyKCdjbGljaycsIGZ1bmN0aW9uKGUpIHtcbiAgICAgICAgICAgICAgZS5wcmV2ZW50RGVmYXVsdCgpO1xuICAgICAgICAgICAgICBlLnN0b3BQcm9wYWdhdGlvbigpO1xuICAgICAgICAgICAgICBjb25zdCBibG9ja0lkID0gdGhpcy5kYXRhc2V0LmJsb2NrSWQ7XG4gICAgICAgICAgICAgIGVkaXRCbG9jayhibG9ja0lkKTtcbiAgICAgICAgICAgIH0pO1xuICAgICAgICAgIH0pO1xuXG4gICAgICAgICAgLy8gUmVtb3ZlIFN5bWZvbnkgdG9vbGJhciBpbnNpZGUgaWZyYW1lXG4gICAgICAgICAgaWYgKGlmcmFtZURvY3VtZW50LnF1ZXJ5U2VsZWN0b3IoJy5zZi10b29sYmFyJykpIHtcbiAgICAgICAgICAgIGlmcmFtZURvY3VtZW50LnF1ZXJ5U2VsZWN0b3IoJy5zZi10b29sYmFyJykucmVtb3ZlKCk7XG4gICAgICAgICAgfVxuXG4gICAgICAgICAgaWZyYW1lRXZlbnRzSW5pdGlhbGl6ZWQgPSB0cnVlO1xuICAgICAgICAgIGNvbnNvbGUubG9nKCdJZnJhbWUgYmxvY2sgZXZlbnRzIGluaXRpYWxpemVkJyk7XG4gICAgICAgIH1cblxuICAgICAgICAvLyBJbml0aWFsaXplIGlmcmFtZSBldmVudHMgZm9yIHRoZSBmaXJzdCB0aW1lXG4gICAgICAgIGluaXRJZnJhbWVCbG9ja0V2ZW50cygpO1xuXG4gICAgICAgIC8vIFN5bmNocm9uaXplIGJsb2NrIGhhbmRsZXMgcG9zaXRpb25zIHdpdGggaWZyYW1lIHNjcm9sbFxuICAgICAgICBjb25zdCBibG9ja0hhbmRsZXMgPSBkb2N1bWVudC5xdWVyeVNlbGVjdG9yQWxsKCcucGFnZS1idWlsZGVyX19ibG9jay1oYW5kbGUnKTtcbiAgICAgICAgY29uc3QgYmxvY2tIYW5kbGVzQ29udGFpbmVyID0gZG9jdW1lbnQucXVlcnlTZWxlY3RvcignW2RhdGEtcGFnZS1idWlsZGVyLXRhcmdldD1cImJsb2NrSGFuZGxlc1wiXScpO1xuICAgICAgICBjb25zdCBzaWRlYmFyID0gZG9jdW1lbnQucXVlcnlTZWxlY3RvcignW2RhdGEtcGFnZS1idWlsZGVyLXRhcmdldD1cInNpZGViYXJcIl0nKTtcblxuICAgICAgICAvKipcbiAgICAgICAgICogQ2hlY2sgaWYgdGhlIGlmcmFtZSBVUkwgaGFzIGNoYW5nZWQgZnJvbSB0aGUgaW5pdGlhbCBVUkxcbiAgICAgICAgICogUmV0dXJucyB0cnVlIGlmIHRoZSBVUkwgaXMgc3RpbGwgdGhlIHNhbWUgKHNhZmUgdG8gb3BlcmF0ZSksIGZhbHNlIG90aGVyd2lzZVxuICAgICAgICAgKi9cbiAgICAgICAgZnVuY3Rpb24gaXNJZnJhbWVVcmxWYWxpZCgpIHtcbiAgICAgICAgICBpZiAoIWluaXRpYWxJZnJhbWVVcmwpIHJldHVybiB0cnVlOyAvLyBBbGxvdyBkdXJpbmcgaW5pdGlhbGl6YXRpb25cblxuICAgICAgICAgIHRyeSB7XG4gICAgICAgICAgICBjb25zdCBjdXJyZW50VXJsID0gbmV3IFVSTChpZnJhbWUuY29udGVudFdpbmRvdy5sb2NhdGlvbi5ocmVmKTtcbiAgICAgICAgICAgIGNvbnN0IGN1cnJlbnRQYXRoID0gY3VycmVudFVybC5vcmlnaW4gKyBjdXJyZW50VXJsLnBhdGhuYW1lO1xuXG4gICAgICAgICAgICBpZiAoY3VycmVudFBhdGggIT09IGluaXRpYWxJZnJhbWVVcmwpIHtcbiAgICAgICAgICAgICAgY29uc29sZS53YXJuKCdJZnJhbWUgVVJMIGhhcyBjaGFuZ2VkISBFeHBlY3RlZDonLCBpbml0aWFsSWZyYW1lVXJsLCAnR290OicsIGN1cnJlbnRQYXRoKTtcbiAgICAgICAgICAgICAgcmV0dXJuIGZhbHNlO1xuICAgICAgICAgICAgfVxuICAgICAgICAgICAgcmV0dXJuIHRydWU7XG4gICAgICAgICAgfSBjYXRjaCAoZXJyb3IpIHtcbiAgICAgICAgICAgIC8vIENhbm5vdCBhY2Nlc3MgVVJMIChjcm9zcy1vcmlnaW4pLCBhc3N1bWUgaXQncyBpbnZhbGlkXG4gICAgICAgICAgICBjb25zb2xlLmVycm9yKCdDYW5ub3QgY2hlY2sgaWZyYW1lIFVSTDonLCBlcnJvcik7XG4gICAgICAgICAgICByZXR1cm4gZmFsc2U7XG4gICAgICAgICAgfVxuICAgICAgICB9XG5cbiAgICAgICAgZnVuY3Rpb24gdXBkYXRlQmxvY2tIYW5kbGVzUG9zaXRpb25zKCkge1xuICAgICAgICAgIC8vIFNlY3VyaXR5IGNoZWNrOiBvbmx5IHVwZGF0ZSBpZiBpZnJhbWUgVVJMIGhhc24ndCBjaGFuZ2VkXG4gICAgICAgICAgaWYgKCFpc0lmcmFtZVVybFZhbGlkKCkpIHtcbiAgICAgICAgICAgIGNvbnNvbGUud2FybignU2tpcHBpbmcgYmxvY2sgaGFuZGxlcyB1cGRhdGUgLSBpZnJhbWUgVVJMIGhhcyBjaGFuZ2VkJyk7XG4gICAgICAgICAgICByZXR1cm47XG4gICAgICAgICAgfVxuICAgICAgICAgIGlmICghYmxvY2tIYW5kbGVzQ29udGFpbmVyKSByZXR1cm47XG5cbiAgICAgICAgICAvLyBHZXQgYWxsIGNvbnRlbnQgYmxvY2tzIGluIHRoZSBpZnJhbWVcbiAgICAgICAgICBjb25zdCBjb250ZW50QmxvY2tzID0gaWZyYW1lRG9jdW1lbnQucXVlcnlTZWxlY3RvckFsbCgnLmNvbnRlbnQtYmxvY2std3JhcHBlcltkYXRhLWJsb2NrLWlkXScpO1xuXG4gICAgICAgICAgaWYgKGNvbnRlbnRCbG9ja3MubGVuZ3RoID09PSAwKSByZXR1cm47XG5cbiAgICAgICAgICBjb25zdCB2aWV3cG9ydEhlaWdodCA9IGlmcmFtZVdpbmRvdy5pbm5lckhlaWdodDtcblxuICAgICAgICAgIC8vIEdldCB0aGUgb2Zmc2V0IG9mIHRoZSBoYW5kbGVzIGNvbnRhaW5lciB0byBjb21wZW5zYXRlIGZvciB0aGUgYWRkIGJsb2NrIGJ1dHRvblxuICAgICAgICAgIGNvbnN0IGFkZEJsb2NrQnV0dG9uID0gZG9jdW1lbnQucXVlcnlTZWxlY3RvcignW2RhdGEtcGFnZS1idWlsZGVyLXRhcmdldD1cImFkZEJsb2NrXCJdJyk7XG4gICAgICAgICAgbGV0IG9mZnNldCA9IDA7XG4gICAgICAgICAgaWYgKGFkZEJsb2NrQnV0dG9uKSB7XG4gICAgICAgICAgICAvLyBDYWxjdWxhdGUgdG90YWwgaGVpZ2h0IGluY2x1ZGluZyBtYXJnaW5zXG4gICAgICAgICAgICBjb25zdCBhZGRCbG9ja1N0eWxlcyA9IHdpbmRvdy5nZXRDb21wdXRlZFN0eWxlKGFkZEJsb2NrQnV0dG9uKTtcbiAgICAgICAgICAgIGNvbnN0IGFkZEJsb2NrSGVpZ2h0ID0gYWRkQmxvY2tCdXR0b24ub2Zmc2V0SGVpZ2h0O1xuICAgICAgICAgICAgY29uc3QgYWRkQmxvY2tNYXJnaW5Cb3R0b20gPSBwYXJzZUZsb2F0KGFkZEJsb2NrU3R5bGVzLm1hcmdpbkJvdHRvbSk7XG4gICAgICAgICAgICBjb25zdCBoYW5kbGVzQ29udGFpbmVyTWFyZ2luVG9wID0gcGFyc2VGbG9hdCh3aW5kb3cuZ2V0Q29tcHV0ZWRTdHlsZShibG9ja0hhbmRsZXNDb250YWluZXIpLm1hcmdpblRvcCk7XG4gICAgICAgICAgICBvZmZzZXQgPSBhZGRCbG9ja0hlaWdodCArIGFkZEJsb2NrTWFyZ2luQm90dG9tICsgaGFuZGxlc0NvbnRhaW5lck1hcmdpblRvcDtcbiAgICAgICAgICB9XG5cbiAgICAgICAgICAvLyBQb3NpdGlvbiBlYWNoIGhhbmRsZSBiYXNlZCBvbiBpdHMgY29ycmVzcG9uZGluZyBibG9jayBpbiB0aGUgaWZyYW1lXG4gICAgICAgICAgY29udGVudEJsb2Nrcy5mb3JFYWNoKGNvbnRlbnRCbG9jayA9PiB7XG4gICAgICAgICAgICBjb25zdCBibG9ja0lkID0gY29udGVudEJsb2NrLmRhdGFzZXQuYmxvY2tJZDtcbiAgICAgICAgICAgIGNvbnN0IGhhbmRsZSA9IGRvY3VtZW50LnF1ZXJ5U2VsZWN0b3IoYC5wYWdlLWJ1aWxkZXJfX2Jsb2NrLWhhbmRsZVtkYXRhLWJsb2NrLWlkPVwiJHtibG9ja0lkfVwiXWApO1xuXG4gICAgICAgICAgICBpZiAoaGFuZGxlKSB7XG4gICAgICAgICAgICAgIC8vIEdldCBibG9jayBwb3NpdGlvbiBhbmQgZGltZW5zaW9ucyBpbiBpZnJhbWUgKHJlbGF0aXZlIHRvIHZpZXdwb3J0KVxuICAgICAgICAgICAgICBjb25zdCBibG9ja1JlY3QgPSBjb250ZW50QmxvY2suZ2V0Qm91bmRpbmdDbGllbnRSZWN0KCk7XG5cbiAgICAgICAgICAgICAgLy8gVXNlIHRoZSBwb3NpdGlvbiByZWxhdGl2ZSB0byB0aGUgaWZyYW1lIHZpZXdwb3J0LCBjb21wZW5zYXRpbmcgZm9yIHRoZSBvZmZzZXRcbiAgICAgICAgICAgICAgY29uc3QgYmxvY2tUb3AgPSBibG9ja1JlY3QudG9wIC0gb2Zmc2V0O1xuICAgICAgICAgICAgICBjb25zdCBibG9ja0hlaWdodCA9IGJsb2NrUmVjdC5oZWlnaHQ7XG5cbiAgICAgICAgICAgICAgLy8gU2V0IGhhbmRsZSBwb3NpdGlvbiBhbmQgaGVpZ2h0XG4gICAgICAgICAgICAgIGhhbmRsZS5zdHlsZS50b3AgPSBibG9ja1RvcCArICdweCc7XG4gICAgICAgICAgICAgIGhhbmRsZS5zdHlsZS5oZWlnaHQgPSBibG9ja0hlaWdodCArICdweCc7XG5cbiAgICAgICAgICAgICAgLy8gQ2hlY2sgaWYgYmxvY2sgaXMgdmlzaWJsZSBpbiB2aWV3cG9ydFxuICAgICAgICAgICAgICAvLyBBIGJsb2NrIGlzIGNvbnNpZGVyZWQgdmlzaWJsZSBpZiBhbnkgcGFydCBvZiBpdCBpcyBpbiB0aGUgdmlld3BvcnRcbiAgICAgICAgICAgICAgY29uc3QgaXNWaXNpYmxlID0gYmxvY2tSZWN0LnRvcCA8IHZpZXdwb3J0SGVpZ2h0ICYmIGJsb2NrUmVjdC5ib3R0b20gPiAwO1xuXG4gICAgICAgICAgICAgIC8vIEFkZC9yZW1vdmUgYWN0aXZlIGNsYXNzXG4gICAgICAgICAgICAgIGlmIChpc1Zpc2libGUpIHtcbiAgICAgICAgICAgICAgICBoYW5kbGUuY2xhc3NMaXN0LmFkZCgnaXMtYWN0aXZlJyk7XG4gICAgICAgICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICAgICAgaGFuZGxlLmNsYXNzTGlzdC5yZW1vdmUoJ2lzLWFjdGl2ZScpO1xuICAgICAgICAgICAgICB9XG4gICAgICAgICAgICB9XG4gICAgICAgICAgfSk7XG4gICAgICAgIH1cblxuICAgICAgICAvLyBUaHJvdHRsZSBmdW5jdGlvbiB0byBsaW1pdCBzY3JvbGwgZXZlbnQgZnJlcXVlbmN5XG4gICAgICAgIGZ1bmN0aW9uIHRocm90dGxlKGZ1bmMsIGxpbWl0KSB7XG4gICAgICAgICAgbGV0IGluVGhyb3R0bGU7XG4gICAgICAgICAgcmV0dXJuIGZ1bmN0aW9uKCkge1xuICAgICAgICAgICAgY29uc3QgYXJncyA9IGFyZ3VtZW50cztcbiAgICAgICAgICAgIGNvbnN0IGNvbnRleHQgPSB0aGlzO1xuICAgICAgICAgICAgaWYgKCFpblRocm90dGxlKSB7XG4gICAgICAgICAgICAgIGZ1bmMuYXBwbHkoY29udGV4dCwgYXJncyk7XG4gICAgICAgICAgICAgIGluVGhyb3R0bGUgPSB0cnVlO1xuICAgICAgICAgICAgICBzZXRUaW1lb3V0KCgpID0+IGluVGhyb3R0bGUgPSBmYWxzZSwgbGltaXQpO1xuICAgICAgICAgICAgfVxuICAgICAgICAgIH1cbiAgICAgICAgfVxuXG4gICAgICAgIC8vIFVwZGF0ZSBwb3NpdGlvbnMgb24gaWZyYW1lIHNjcm9sbCAodGhyb3R0bGVkKVxuICAgICAgICBjb25zdCB0aHJvdHRsZWRVcGRhdGUgPSB0aHJvdHRsZSh1cGRhdGVCbG9ja0hhbmRsZXNQb3NpdGlvbnMsIDUwKTtcbiAgICAgICAgaWZyYW1lV2luZG93LmFkZEV2ZW50TGlzdGVuZXIoJ3Njcm9sbCcsIHRocm90dGxlZFVwZGF0ZSk7XG5cbiAgICAgICAgLy8gVXBkYXRlIHBvc2l0aW9ucyBvbiBpZnJhbWUgcmVzaXplXG4gICAgICAgIGlmcmFtZVdpbmRvdy5hZGRFdmVudExpc3RlbmVyKCdyZXNpemUnLCB0aHJvdHRsZWRVcGRhdGUpO1xuXG4gICAgICAgIC8vIEluaXRpYWwgcG9zaXRpb25pbmdcbiAgICAgICAgc2V0VGltZW91dCh1cGRhdGVCbG9ja0hhbmRsZXNQb3NpdGlvbnMsIDEwMCk7XG5cbiAgICAgICAgLy8gQWRkIGhvdmVyIGVmZmVjdCBvbiBoYW5kbGVzIHRvIGhpZ2hsaWdodCBibG9ja3MgaW4gaWZyYW1lXG4gICAgICAgIGJsb2NrSGFuZGxlcy5mb3JFYWNoKGhhbmRsZSA9PiB7XG4gICAgICAgICAgY29uc3QgaGFuZGxlQnV0dG9uID0gaGFuZGxlLnF1ZXJ5U2VsZWN0b3IoJy5wYWdlLWJ1aWxkZXJfX2Jsb2NrLWhhbmRsZS1idXR0b24nKTtcbiAgICAgICAgICBpZiAoIWhhbmRsZUJ1dHRvbikgcmV0dXJuO1xuXG4gICAgICAgICAgaGFuZGxlQnV0dG9uLmFkZEV2ZW50TGlzdGVuZXIoJ21vdXNlZW50ZXInLCBmdW5jdGlvbigpIHtcbiAgICAgICAgICAgIC8vIFNlY3VyaXR5IGNoZWNrOiBvbmx5IGFwcGx5IGhvdmVyIGlmIGlmcmFtZSBVUkwgaGFzbid0IGNoYW5nZWRcbiAgICAgICAgICAgIGlmICghaXNJZnJhbWVVcmxWYWxpZCgpKSB7XG4gICAgICAgICAgICAgIHJldHVybjtcbiAgICAgICAgICAgIH1cblxuICAgICAgICAgICAgY29uc3QgYmxvY2tJZCA9IGhhbmRsZS5kYXRhc2V0LmJsb2NrSWQ7XG4gICAgICAgICAgICBjb25zdCBibG9jayA9IGlmcmFtZURvY3VtZW50LnF1ZXJ5U2VsZWN0b3IoYC5jb250ZW50LWJsb2NrLXdyYXBwZXJbZGF0YS1ibG9jay1pZD1cIiR7YmxvY2tJZH1cIl1gKTtcbiAgICAgICAgICAgIGlmIChibG9jaykge1xuICAgICAgICAgICAgICBibG9jay5jbGFzc0xpc3QuYWRkKCdpcy1ob3ZlcmVkJyk7XG4gICAgICAgICAgICB9XG4gICAgICAgICAgfSk7XG5cbiAgICAgICAgICBoYW5kbGVCdXR0b24uYWRkRXZlbnRMaXN0ZW5lcignbW91c2VsZWF2ZScsIGZ1bmN0aW9uKCkge1xuICAgICAgICAgICAgLy8gU2VjdXJpdHkgY2hlY2s6IG9ubHkgYXBwbHkgaG92ZXIgaWYgaWZyYW1lIFVSTCBoYXNuJ3QgY2hhbmdlZFxuICAgICAgICAgICAgaWYgKCFpc0lmcmFtZVVybFZhbGlkKCkpIHtcbiAgICAgICAgICAgICAgcmV0dXJuO1xuICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICBjb25zdCBibG9ja0lkID0gaGFuZGxlLmRhdGFzZXQuYmxvY2tJZDtcbiAgICAgICAgICAgIGNvbnN0IGJsb2NrID0gaWZyYW1lRG9jdW1lbnQucXVlcnlTZWxlY3RvcihgLmNvbnRlbnQtYmxvY2std3JhcHBlcltkYXRhLWJsb2NrLWlkPVwiJHtibG9ja0lkfVwiXWApO1xuICAgICAgICAgICAgaWYgKGJsb2NrKSB7XG4gICAgICAgICAgICAgIGJsb2NrLmNsYXNzTGlzdC5yZW1vdmUoJ2lzLWhvdmVyZWQnKTtcbiAgICAgICAgICAgIH1cbiAgICAgICAgICB9KTtcblxuICAgICAgICAgIC8vIEFkZCBjbGljayBoYW5kbGVyIGZvciBzaWRlYmFyIGJ1dHRvbnNcbiAgICAgICAgICBoYW5kbGVCdXR0b24uYWRkRXZlbnRMaXN0ZW5lcignY2xpY2snLCBmdW5jdGlvbihlKSB7XG4gICAgICAgICAgICBlLnByZXZlbnREZWZhdWx0KCk7XG4gICAgICAgICAgICBlLnN0b3BQcm9wYWdhdGlvbigpO1xuICAgICAgICAgICAgY29uc3QgYmxvY2tJZCA9IGhhbmRsZS5kYXRhc2V0LmJsb2NrSWQ7XG4gICAgICAgICAgICBlZGl0QmxvY2soYmxvY2tJZCk7XG4gICAgICAgICAgfSk7XG4gICAgICAgIH0pO1xuXG4gICAgICAgIC8vIE5vdGU6IEV2ZW50cyBvbiBibG9ja3MgSU5TSURFIGlmcmFtZSAoaG92ZXIsIGNsaWNrIG9uIGVkaXQgYnV0dG9ucykgYXJlIGhhbmRsZWQgYnkgaW5pdElmcmFtZUJsb2NrRXZlbnRzKClcblxuICAgICAgfSBjYXRjaCAoZXJyb3IpIHtcbiAgICAgICAgY29uc29sZS5lcnJvcignQ2Fubm90IGFjY2VzcyBpZnJhbWU6JywgZXJyb3IpO1xuICAgICAgICBjb25zb2xlLmxvZygnSWZyYW1lIHNyYzonLCBpZnJhbWUuc3JjKTtcbiAgICAgICAgLy8gQ3Jvc3Mtb3JpZ2luIHJlc3RyaWN0aW9ucyBwcmV2ZW50IGFjY2Vzc1xuICAgICAgICBjb25zb2xlLmxvZygnQ2Fubm90IGFjY2VzcyBpZnJhbWUgY29udGVudCAoY3Jvc3Mtb3JpZ2luIHJlc3RyaWN0aW9uKScpO1xuICAgICAgfVxuICAgIH0pO1xuXG4gICAgLy8gTW9uaXRvciBpZnJhbWUgVVJMIGNoYW5nZXMgYW5kIHJlLWluaXRpYWxpemUgZXZlbnRzIHdoZW4gcmV0dXJuaW5nIHRvIGF1dGhvcml6ZWQgVVJMXG4gICAgbGV0IGxhc3RLbm93blVybCA9IG51bGw7XG4gICAgbGV0IHVybENoZWNrSW50ZXJ2YWwgPSBzZXRJbnRlcnZhbChmdW5jdGlvbigpIHtcbiAgICAgIHRyeSB7XG4gICAgICAgIGNvbnN0IGN1cnJlbnRVcmwgPSBpZnJhbWUuY29udGVudFdpbmRvdy5sb2NhdGlvbi5ocmVmO1xuXG4gICAgICAgIC8vIENoZWNrIGlmIFVSTCBoYXMgY2hhbmdlZFxuICAgICAgICBpZiAoY3VycmVudFVybCAhPT0gbGFzdEtub3duVXJsKSB7XG4gICAgICAgICAgY29uc29sZS5sb2coJ0lmcmFtZSBVUkwgY2hhbmdlZCBmcm9tJywgbGFzdEtub3duVXJsLCAndG8nLCBjdXJyZW50VXJsKTtcbiAgICAgICAgICBsYXN0S25vd25VcmwgPSBjdXJyZW50VXJsO1xuXG4gICAgICAgICAgLy8gQ2hlY2sgaWYgd2UncmUgYmFjayBvbiB0aGUgYXV0aG9yaXplZCBVUkxcbiAgICAgICAgICBjb25zdCB1cmwgPSBuZXcgVVJMKGN1cnJlbnRVcmwpO1xuICAgICAgICAgIGNvbnN0IGN1cnJlbnRQYXRoID0gdXJsLm9yaWdpbiArIHVybC5wYXRobmFtZTtcblxuICAgICAgICAgIGlmIChjdXJyZW50UGF0aCA9PT0gaW5pdGlhbElmcmFtZVVybCAmJiAhaWZyYW1lRXZlbnRzSW5pdGlhbGl6ZWQpIHtcbiAgICAgICAgICAgIGNvbnNvbGUubG9nKCdSZXR1cm5lZCB0byBhdXRob3JpemVkIFVSTCAtIHRyaWdnZXJpbmcgcmUtaW5pdGlhbGl6YXRpb24nKTtcbiAgICAgICAgICAgIC8vIE1hbnVhbGx5IHRyaWdnZXIgdGhlIGxvYWQgZXZlbnQgaGFuZGxlclxuICAgICAgICAgICAgaWZyYW1lLmRpc3BhdGNoRXZlbnQobmV3IEV2ZW50KCdsb2FkJykpO1xuICAgICAgICAgIH0gZWxzZSBpZiAoY3VycmVudFBhdGggIT09IGluaXRpYWxJZnJhbWVVcmwgJiYgaWZyYW1lRXZlbnRzSW5pdGlhbGl6ZWQpIHtcbiAgICAgICAgICAgIGNvbnNvbGUubG9nKCdOYXZpZ2F0ZWQgYXdheSBmcm9tIGF1dGhvcml6ZWQgVVJMIC0gZXZlbnRzIHdpbGwgYmUgZGlzYWJsZWQnKTtcbiAgICAgICAgICAgIGlmcmFtZUV2ZW50c0luaXRpYWxpemVkID0gZmFsc2U7XG4gICAgICAgICAgfVxuICAgICAgICB9XG4gICAgICB9IGNhdGNoIChlcnJvcikge1xuICAgICAgICAvLyBDcm9zcy1vcmlnaW4gZXJyb3IgLSBpZ25vcmVcbiAgICAgIH1cbiAgICB9LCA1MDApOyAvLyBDaGVjayBldmVyeSA1MDBtc1xuXG4gICAgLy8gTG9hZCB0aGUgcHJldmlldyBVUkwgd2l0aCBsb2NhbGVcbiAgICBpZnJhbWUuc3JjID0gaWZyYW1lVXJpO1xuICB9XG5cbiAgLy8gRWRpdG9yIHBhbmVsIHRvZ2dsZSBmdW5jdGlvbmFsaXR5IC0gT3ZlcmxheSBiZWhhdmlvciBmb3IgYWxsIHNjcmVlbiBzaXplc1xuICBjb25zdCBhZGRCbG9ja0J1dHRvbiA9IGRvY3VtZW50LnF1ZXJ5U2VsZWN0b3IoJ1tkYXRhLXBhZ2UtYnVpbGRlci10YXJnZXQ9XCJhZGRCbG9ja1wiXScpO1xuXG4gIGlmIChlZGl0b3JQYW5lbCAmJiBlZGl0b3JUb2dnbGUpIHtcbiAgICAvLyBUb2dnbGUgYnV0dG9uIGNsaWNrIGhhbmRsZXIgLSBjbG9zZSBwYW5lbCBhbmQgcmVzZXQgZm9ybVxuICAgIGVkaXRvclRvZ2dsZS5hZGRFdmVudExpc3RlbmVyKCdjbGljaycsIGZ1bmN0aW9uKGUpIHtcbiAgICAgIGUucHJldmVudERlZmF1bHQoKTtcblxuICAgICAgLy8gSWYgcGFuZWwgaXMgb3BlbiwgY2xvc2UgaXQgYW5kIHJlc2V0IHRoZSBmb3JtXG4gICAgICBpZiAoZWRpdG9yUGFuZWwuY2xhc3NMaXN0LmNvbnRhaW5zKCdpcy1vcGVuJykpIHtcbiAgICAgICAgZWRpdG9yUGFuZWwuY2xhc3NMaXN0LnJlbW92ZSgnaXMtb3BlbicpO1xuICAgICAgICAvLyBSZXNldCB0aGUgZm9ybSBieSBjYWxsaW5nIGNsb3NlQmxvY2soKVxuICAgICAgICBjbG9zZUJsb2NrKCk7XG4gICAgICB9XG4gICAgfSk7XG5cbiAgICAvLyBDbG9zZSBwYW5lbCB3aGVuIGNsaWNraW5nIG91dHNpZGVcbiAgICAvLyBkb2N1bWVudC5hZGRFdmVudExpc3RlbmVyKCdjbGljaycsIGZ1bmN0aW9uKGUpIHtcbiAgICAvLyAgICAgaWYgKGVkaXRvclBhbmVsLmNsYXNzTGlzdC5jb250YWlucygnaXMtb3BlbicpICYmXG4gICAgLy8gICAgICAgICAhZWRpdG9yUGFuZWwuY29udGFpbnMoZS50YXJnZXQpICYmXG4gICAgLy8gICAgICAgICBlLnRhcmdldCAhPT0gZWRpdG9yVG9nZ2xlICYmXG4gICAgLy8gICAgICAgICBlLnRhcmdldCAhPT0gYWRkQmxvY2tCdXR0b24gJiZcbiAgICAvLyAgICAgICAgICFlZGl0b3JUb2dnbGUuY29udGFpbnMoZS50YXJnZXQpKSB7XG4gICAgLy8gICAgICAgICBlZGl0b3JQYW5lbC5jbGFzc0xpc3QucmVtb3ZlKCdpcy1vcGVuJyk7XG4gICAgLy8gICAgICAgICAvLyBSZXNldCB0aGUgZm9ybSBieSBjYWxsaW5nIGNsb3NlQmxvY2soKVxuICAgIC8vICAgICAgICAgY2xvc2VCbG9jaygpO1xuICAgIC8vICAgICB9XG4gICAgLy8gfSk7XG5cbiAgICAvLyBFc2NhcGUga2V5IHRvIGNsb3NlIHBhbmVsXG4gICAgZG9jdW1lbnQuYWRkRXZlbnRMaXN0ZW5lcigna2V5ZG93bicsIGZ1bmN0aW9uKGUpIHtcbiAgICAgIGlmIChlLmtleSA9PT0gJ0VzY2FwZScgJiYgZWRpdG9yUGFuZWwuY2xhc3NMaXN0LmNvbnRhaW5zKCdpcy1vcGVuJykpIHtcbiAgICAgICAgZWRpdG9yUGFuZWwuY2xhc3NMaXN0LnJlbW92ZSgnaXMtb3BlbicpO1xuICAgICAgICAvLyBSZXNldCB0aGUgZm9ybSBieSBjYWxsaW5nIGNsb3NlQmxvY2soKVxuICAgICAgICBjbG9zZUJsb2NrKCk7XG4gICAgICB9XG4gICAgfSk7XG5cbiAgICAvLyBBZGQgYmxvY2sgYnV0dG9uIC0gb3BlbiBwYW5lbCBpbiBjcmVhdGUgbW9kZVxuICAgIGlmIChhZGRCbG9ja0J1dHRvbikge1xuICAgICAgYWRkQmxvY2tCdXR0b24uYWRkRXZlbnRMaXN0ZW5lcignY2xpY2snLCBmdW5jdGlvbihlKSB7XG4gICAgICAgIGUucHJldmVudERlZmF1bHQoKTtcbiAgICAgICAgLy8gQ2FsbCBlZGl0QmxvY2sgd2l0aCBudWxsIHRvIGVudGVyIFwiY3JlYXRlIG1vZGVcIlxuICAgICAgICBlZGl0QmxvY2sobnVsbCk7XG4gICAgICB9KTtcbiAgICB9XG4gIH1cblxuICAvLyBMaXN0ZW4gZm9yIGJsb2Nrczpjb3BpZWQgZXZlbnQgdG8gcmVsb2FkIHRoZSBwYWdlXG4gIGRvY3VtZW50LmFkZEV2ZW50TGlzdGVuZXIoJ2Jsb2Nrczpjb3BpZWQnLCBmdW5jdGlvbihldmVudCkge1xuICAgIGNvbnNvbGUubG9nKCdCbG9ja3MgY29waWVkIGZyb20gbG9jYWxlOicsIGV2ZW50LmRldGFpbC5zb3VyY2VMb2NhbGUsICd0bycsIGV2ZW50LmRldGFpbC50YXJnZXRMb2NhbGUpO1xuXG4gICAgLy8gUmVsb2FkIHRoZSBwYWdlIHRvIHNob3cgdGhlIG5ld2x5IGNvcGllZCBibG9ja3NcbiAgICBpZiAoZXZlbnQuZGV0YWlsLnJlbG9hZCkge1xuICAgICAgLy8gU21hbGwgZGVsYXkgdG8gZW5zdXJlIHRoZSBkYXRhYmFzZSB0cmFuc2FjdGlvbiBpcyBjb21taXR0ZWRcbiAgICAgIHNldFRpbWVvdXQoZnVuY3Rpb24oKSB7XG4gICAgICAgIHdpbmRvdy5sb2NhdGlvbi5yZWxvYWQoKTtcbiAgICAgIH0sIDMwMCk7XG4gICAgfVxuICB9KTtcblxuICAvLyBMaXN0ZW4gZm9yIHBvc3RNZXNzYWdlIGZyb20gYmxvY2sgZWRpdCBpZnJhbWVcbiAgd2luZG93LmFkZEV2ZW50TGlzdGVuZXIoJ21lc3NhZ2UnLCBmdW5jdGlvbihldmVudCkge1xuICAgIC8vIFNlY3VyaXR5OiB2ZXJpZnkgb3JpZ2luIGlmIG5lZWRlZFxuICAgIC8vIGlmIChldmVudC5vcmlnaW4gIT09IHdpbmRvdy5sb2NhdGlvbi5vcmlnaW4pIHJldHVybjtcblxuICAgIGNvbnN0IGRhdGEgPSBldmVudC5kYXRhO1xuXG4gICAgLy8gSGFuZGxlIGJsb2NrOnNhdmVkIG1lc3NhZ2UgZnJvbSB0aGUgZm9ybSBpZnJhbWVcbiAgICBpZiAoZGF0YSAmJiBkYXRhLnR5cGUgPT09ICdibG9jazpzYXZlZCcpIHtcbiAgICAgIGNvbnNvbGUubG9nKCdCbG9jayBzYXZlZCB3aXRoIElEOicsIGRhdGEuYmxvY2tJZCk7XG5cbiAgICAgIC8vIFJlbG9hZCB0aGUgcHJldmlldyBpZnJhbWUgdG8gc2hvdyB0aGUgdXBkYXRlZCBibG9ja1xuICAgICAgY29uc3QgcHJldmlld0lmcmFtZSA9IGRvY3VtZW50LnF1ZXJ5U2VsZWN0b3IoJ1tkYXRhLXBhZ2UtYnVpbGRlci10YXJnZXQ9XCJpZnJhbWVcIl0nKTtcbiAgICAgIGlmIChwcmV2aWV3SWZyYW1lKSB7XG4gICAgICAgIGNvbnNvbGUubG9nKCdSZWxvYWRpbmcgcHJldmlldyBpZnJhbWUgdG8gc2hvdyB1cGRhdGVkIGJsb2NrJyk7XG4gICAgICAgIHByZXZpZXdJZnJhbWUuY29udGVudFdpbmRvdy5sb2NhdGlvbi5yZWxvYWQoKTtcbiAgICAgIH1cbiAgICB9XG4gIH0pO1xuXG4gIC8vIFB1Ymxpc2ggbW9kYWwgZnVuY3Rpb25hbGl0eVxuICBjb25zdCBwdWJsaXNoTW9kYWwgPSBkb2N1bWVudC5nZXRFbGVtZW50QnlJZCgncHVibGlzaE1vZGFsJyk7XG4gIGlmIChwdWJsaXNoTW9kYWwpIHtcbiAgICBjb25zdCBzZWxlY3RBbGxDaGVja2JveCA9IGRvY3VtZW50LmdldEVsZW1lbnRCeUlkKCdzZWxlY3RBbGxMb2NhbGVzJyk7XG4gICAgY29uc3QgbG9jYWxlQ2hlY2tib3hlcyA9IGRvY3VtZW50LnF1ZXJ5U2VsZWN0b3JBbGwoJy5sb2NhbGUtY2hlY2tib3gnKTtcbiAgICBjb25zdCBjb25maXJtUHVibGlzaEJ0biA9IGRvY3VtZW50LmdldEVsZW1lbnRCeUlkKCdjb25maXJtUHVibGlzaEJ0bicpO1xuXG4gICAgLy8gSGFuZGxlIFwiU2VsZWN0IGFsbFwiIGNoZWNrYm94XG4gICAgaWYgKHNlbGVjdEFsbENoZWNrYm94KSB7XG4gICAgICBzZWxlY3RBbGxDaGVja2JveC5hZGRFdmVudExpc3RlbmVyKCdjaGFuZ2UnLCBmdW5jdGlvbigpIHtcbiAgICAgICAgbG9jYWxlQ2hlY2tib3hlcy5mb3JFYWNoKGNoZWNrYm94ID0+IHtcbiAgICAgICAgICBjaGVja2JveC5jaGVja2VkID0gc2VsZWN0QWxsQ2hlY2tib3guY2hlY2tlZDtcbiAgICAgICAgfSk7XG4gICAgICB9KTtcbiAgICB9XG5cbiAgICAvLyBVcGRhdGUgXCJTZWxlY3QgYWxsXCIgY2hlY2tib3ggd2hlbiBpbmRpdmlkdWFsIGNoZWNrYm94ZXMgY2hhbmdlXG4gICAgbG9jYWxlQ2hlY2tib3hlcy5mb3JFYWNoKGNoZWNrYm94ID0+IHtcbiAgICAgIGNoZWNrYm94LmFkZEV2ZW50TGlzdGVuZXIoJ2NoYW5nZScsIGZ1bmN0aW9uKCkge1xuICAgICAgICBjb25zdCBhbGxDaGVja2VkID0gQXJyYXkuZnJvbShsb2NhbGVDaGVja2JveGVzKS5ldmVyeShjYiA9PiBjYi5jaGVja2VkKTtcbiAgICAgICAgY29uc3Qgbm9uZUNoZWNrZWQgPSBBcnJheS5mcm9tKGxvY2FsZUNoZWNrYm94ZXMpLmV2ZXJ5KGNiID0+ICFjYi5jaGVja2VkKTtcblxuICAgICAgICBpZiAoYWxsQ2hlY2tlZCkge1xuICAgICAgICAgIHNlbGVjdEFsbENoZWNrYm94LmNoZWNrZWQgPSB0cnVlO1xuICAgICAgICAgIHNlbGVjdEFsbENoZWNrYm94LmluZGV0ZXJtaW5hdGUgPSBmYWxzZTtcbiAgICAgICAgfSBlbHNlIGlmIChub25lQ2hlY2tlZCkge1xuICAgICAgICAgIHNlbGVjdEFsbENoZWNrYm94LmNoZWNrZWQgPSBmYWxzZTtcbiAgICAgICAgICBzZWxlY3RBbGxDaGVja2JveC5pbmRldGVybWluYXRlID0gZmFsc2U7XG4gICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgc2VsZWN0QWxsQ2hlY2tib3guaW5kZXRlcm1pbmF0ZSA9IHRydWU7XG4gICAgICAgIH1cbiAgICAgIH0pO1xuICAgIH0pO1xuXG4gICAgLy8gSGFuZGxlIGNvbmZpcm0gcHVibGlzaCBidXR0b25cbiAgICBpZiAoY29uZmlybVB1Ymxpc2hCdG4pIHtcbiAgICAgIGNvbmZpcm1QdWJsaXNoQnRuLmFkZEV2ZW50TGlzdGVuZXIoJ2NsaWNrJywgZnVuY3Rpb24oKSB7XG4gICAgICAgIC8vIEdldCBzZWxlY3RlZCBsb2NhbGVzXG4gICAgICAgIGNvbnN0IHNlbGVjdGVkTG9jYWxlcyA9IEFycmF5LmZyb20obG9jYWxlQ2hlY2tib3hlcylcbiAgICAgICAgICAuZmlsdGVyKGNiID0+IGNiLmNoZWNrZWQpXG4gICAgICAgICAgLm1hcChjYiA9PiBjYi52YWx1ZSk7XG5cbiAgICAgICAgaWYgKHNlbGVjdGVkTG9jYWxlcy5sZW5ndGggPT09IDApIHtcbiAgICAgICAgICBhbGVydChzZWxlY3RMb2NhbGVNZXNzYWdlKTtcbiAgICAgICAgICByZXR1cm47XG4gICAgICAgIH1cblxuICAgICAgICAvLyBCdWlsZCBVUkwgd2l0aCBHRVQgcGFyYW1ldGVyc1xuICAgICAgICBjb25zdCBjdXJyZW50VXJsID0gbmV3IFVSTCh3aW5kb3cubG9jYXRpb24uaHJlZik7XG4gICAgICAgIGN1cnJlbnRVcmwuc2VhcmNoUGFyYW1zLnNldCgncHVibGlzaCcsICcxJyk7XG5cbiAgICAgICAgLy8gQWRkIGxvY2FsZXMgYXMgYXJyYXkgcGFyYW1ldGVyc1xuICAgICAgICBjdXJyZW50VXJsLnNlYXJjaFBhcmFtcy5kZWxldGUoJ2xvY2FsZXNbXScpOyAvLyBDbGVhciBleGlzdGluZ1xuICAgICAgICBzZWxlY3RlZExvY2FsZXMuZm9yRWFjaChsb2NhbGUgPT4ge1xuICAgICAgICAgIGN1cnJlbnRVcmwuc2VhcmNoUGFyYW1zLmFwcGVuZCgnbG9jYWxlc1tdJywgbG9jYWxlKTtcbiAgICAgICAgfSk7XG5cbiAgICAgICAgLy8gUmVkaXJlY3QgdG8gdGhlIFVSTCB3aXRoIHB1Ymxpc2ggcGFyYW1ldGVyc1xuICAgICAgICB3aW5kb3cubG9jYXRpb24uaHJlZiA9IGN1cnJlbnRVcmwudG9TdHJpbmcoKTtcbiAgICAgIH0pO1xuICAgIH1cbiAgfVxufSk7XG4iLCIvLyBleHRyYWN0ZWQgYnkgbWluaS1jc3MtZXh0cmFjdC1wbHVnaW5cbmV4cG9ydCB7fTsiLCIvLyBUaGUgbW9kdWxlIGNhY2hlXG52YXIgX193ZWJwYWNrX21vZHVsZV9jYWNoZV9fID0ge307XG5cbi8vIFRoZSByZXF1aXJlIGZ1bmN0aW9uXG5mdW5jdGlvbiBfX3dlYnBhY2tfcmVxdWlyZV9fKG1vZHVsZUlkKSB7XG5cdC8vIENoZWNrIGlmIG1vZHVsZSBpcyBpbiBjYWNoZVxuXHR2YXIgY2FjaGVkTW9kdWxlID0gX193ZWJwYWNrX21vZHVsZV9jYWNoZV9fW21vZHVsZUlkXTtcblx0aWYgKGNhY2hlZE1vZHVsZSAhPT0gdW5kZWZpbmVkKSB7XG5cdFx0cmV0dXJuIGNhY2hlZE1vZHVsZS5leHBvcnRzO1xuXHR9XG5cdC8vIENyZWF0ZSBhIG5ldyBtb2R1bGUgKGFuZCBwdXQgaXQgaW50byB0aGUgY2FjaGUpXG5cdHZhciBtb2R1bGUgPSBfX3dlYnBhY2tfbW9kdWxlX2NhY2hlX19bbW9kdWxlSWRdID0ge1xuXHRcdC8vIG5vIG1vZHVsZS5pZCBuZWVkZWRcblx0XHQvLyBubyBtb2R1bGUubG9hZGVkIG5lZWRlZFxuXHRcdGV4cG9ydHM6IHt9XG5cdH07XG5cblx0Ly8gRXhlY3V0ZSB0aGUgbW9kdWxlIGZ1bmN0aW9uXG5cdGlmICghKG1vZHVsZUlkIGluIF9fd2VicGFja19tb2R1bGVzX18pKSB7XG5cdFx0ZGVsZXRlIF9fd2VicGFja19tb2R1bGVfY2FjaGVfX1ttb2R1bGVJZF07XG5cdFx0dmFyIGUgPSBuZXcgRXJyb3IoXCJDYW5ub3QgZmluZCBtb2R1bGUgJ1wiICsgbW9kdWxlSWQgKyBcIidcIik7XG5cdFx0ZS5jb2RlID0gJ01PRFVMRV9OT1RfRk9VTkQnO1xuXHRcdHRocm93IGU7XG5cdH1cblx0X193ZWJwYWNrX21vZHVsZXNfX1ttb2R1bGVJZF0obW9kdWxlLCBtb2R1bGUuZXhwb3J0cywgX193ZWJwYWNrX3JlcXVpcmVfXyk7XG5cblx0Ly8gUmV0dXJuIHRoZSBleHBvcnRzIG9mIHRoZSBtb2R1bGVcblx0cmV0dXJuIG1vZHVsZS5leHBvcnRzO1xufVxuXG4iLCIvLyBnZXREZWZhdWx0RXhwb3J0IGZ1bmN0aW9uIGZvciBjb21wYXRpYmlsaXR5IHdpdGggbm9uLWhhcm1vbnkgbW9kdWxlc1xuX193ZWJwYWNrX3JlcXVpcmVfXy5uID0gKG1vZHVsZSkgPT4ge1xuXHR2YXIgZ2V0dGVyID0gbW9kdWxlICYmIG1vZHVsZS5fX2VzTW9kdWxlID9cblx0XHQoKSA9PiAobW9kdWxlWydkZWZhdWx0J10pIDpcblx0XHQoKSA9PiAobW9kdWxlKTtcblx0X193ZWJwYWNrX3JlcXVpcmVfXy5kKGdldHRlciwgeyBhOiBnZXR0ZXIgfSk7XG5cdHJldHVybiBnZXR0ZXI7XG59OyIsIi8vIGRlZmluZSBnZXR0ZXIgZnVuY3Rpb25zIGZvciBoYXJtb255IGV4cG9ydHNcbl9fd2VicGFja19yZXF1aXJlX18uZCA9IChleHBvcnRzLCBkZWZpbml0aW9uKSA9PiB7XG5cdGZvcih2YXIga2V5IGluIGRlZmluaXRpb24pIHtcblx0XHRpZihfX3dlYnBhY2tfcmVxdWlyZV9fLm8oZGVmaW5pdGlvbiwga2V5KSAmJiAhX193ZWJwYWNrX3JlcXVpcmVfXy5vKGV4cG9ydHMsIGtleSkpIHtcblx0XHRcdE9iamVjdC5kZWZpbmVQcm9wZXJ0eShleHBvcnRzLCBrZXksIHsgZW51bWVyYWJsZTogdHJ1ZSwgZ2V0OiBkZWZpbml0aW9uW2tleV0gfSk7XG5cdFx0fVxuXHR9XG59OyIsIl9fd2VicGFja19yZXF1aXJlX18ubyA9IChvYmosIHByb3ApID0+IChPYmplY3QucHJvdG90eXBlLmhhc093blByb3BlcnR5LmNhbGwob2JqLCBwcm9wKSkiLCIvLyBkZWZpbmUgX19lc01vZHVsZSBvbiBleHBvcnRzXG5fX3dlYnBhY2tfcmVxdWlyZV9fLnIgPSAoZXhwb3J0cykgPT4ge1xuXHRpZih0eXBlb2YgU3ltYm9sICE9PSAndW5kZWZpbmVkJyAmJiBTeW1ib2wudG9TdHJpbmdUYWcpIHtcblx0XHRPYmplY3QuZGVmaW5lUHJvcGVydHkoZXhwb3J0cywgU3ltYm9sLnRvU3RyaW5nVGFnLCB7IHZhbHVlOiAnTW9kdWxlJyB9KTtcblx0fVxuXHRPYmplY3QuZGVmaW5lUHJvcGVydHkoZXhwb3J0cywgJ19fZXNNb2R1bGUnLCB7IHZhbHVlOiB0cnVlIH0pO1xufTsiLCIvKipcbiAqIFBhZ2UgQnVpbGRlciBBc3NldHMgRW50cnkgUG9pbnRcbiAqL1xuXG5pbXBvcnQgJy4vcGFnZS1idWlsZGVyLmNzcyc7XG5pbXBvcnQgJy4vcGFnZS1idWlsZGVyLmpzJztcbiJdLCJuYW1lcyI6WyJkb2N1bWVudCIsImFkZEV2ZW50TGlzdGVuZXIiLCJpZnJhbWVVcmkiLCJxdWVyeVNlbGVjdG9yIiwiZGF0YXNldCIsImlmcmFtZVVybCIsInNlbGVjdExvY2FsZU1lc3NhZ2UiLCJsb2NhbGVEcm9wZG93biIsImdldEVsZW1lbnRCeUlkIiwibG9jYWxlSXRlbXMiLCJxdWVyeVNlbGVjdG9yQWxsIiwicmVzb2x1dGlvbkJ1dHRvbnMiLCJpZnJhbWVXcmFwcGVyIiwibGVuZ3RoIiwiY3VycmVudExvY2FsZSIsImZvckVhY2giLCJpdGVtIiwiZSIsInByZXZlbnREZWZhdWx0IiwibmV3TG9jYWxlIiwibG9jYWxlIiwiY29uZmlybWVkIiwiY29uZmlybSIsInVybCIsIlVSTCIsIndpbmRvdyIsImxvY2F0aW9uIiwiaHJlZiIsInNlYXJjaFBhcmFtcyIsInNldCIsInRvU3RyaW5nIiwiYnV0dG9uIiwiYnRuIiwiY2xhc3NMaXN0IiwicmVtb3ZlIiwiYWRkIiwicmVzb2x1dGlvbiIsImNsYXNzTmFtZSIsImFsaWdubWVudFRvZ2dsZSIsInRvZ2dsZSIsInN0eWxlIiwibWFyZ2luIiwiaWZyYW1lIiwiaW5pdGlhbElmcmFtZVVybCIsImlmcmFtZUV2ZW50c0luaXRpYWxpemVkIiwiZWRpdG9yUGFuZWwiLCJlZGl0b3JUb2dnbGUiLCJlZGl0b3JDb250ZW50IiwiaXNDdXJyZW50SWZyYW1lVXJsVmFsaWQiLCJjdXJyZW50VXJsIiwiY29udGVudFdpbmRvdyIsImN1cnJlbnRQYXRoIiwib3JpZ2luIiwicGF0aG5hbWUiLCJlcnJvciIsImNvbnNvbGUiLCJlZGl0QmxvY2siLCJibG9ja0lkIiwibG9nIiwiYWxlcnQiLCJwYWdlQnVpbGRlckVsZW1lbnQiLCJldmVudCIsIkN1c3RvbUV2ZW50IiwiZGV0YWlsIiwiZGlzcGF0Y2hFdmVudCIsIndhcm4iLCJjbG9zZUJsb2NrIiwiX2lmcmFtZSRjb250ZW50V2luZG93Iiwic3JjIiwiaW5pdElmcmFtZUJsb2NrRXZlbnRzIiwiaWZyYW1lRG9jdW1lbnQiLCJ0YXJnZXQiLCJjbG9zZXN0IiwiY3JlYXRlRWxlbWVudCIsImlkIiwidGV4dENvbnRlbnQiLCJoZWFkIiwiYXBwZW5kQ2hpbGQiLCJjb250ZW50QmxvY2tzIiwiYmxvY2siLCJpc0lmcmFtZVVybFZhbGlkIiwiaGFuZGxlIiwiY29uY2F0IiwiaGFuZGxlQnV0dG9uIiwiZWRpdEJ1dHRvbnMiLCJzdG9wUHJvcGFnYXRpb24iLCJ1cGRhdGVCbG9ja0hhbmRsZXNQb3NpdGlvbnMiLCJibG9ja0hhbmRsZXNDb250YWluZXIiLCJ2aWV3cG9ydEhlaWdodCIsImlmcmFtZVdpbmRvdyIsImlubmVySGVpZ2h0IiwiYWRkQmxvY2tCdXR0b24iLCJvZmZzZXQiLCJhZGRCbG9ja1N0eWxlcyIsImdldENvbXB1dGVkU3R5bGUiLCJhZGRCbG9ja0hlaWdodCIsIm9mZnNldEhlaWdodCIsImFkZEJsb2NrTWFyZ2luQm90dG9tIiwicGFyc2VGbG9hdCIsIm1hcmdpbkJvdHRvbSIsImhhbmRsZXNDb250YWluZXJNYXJnaW5Ub3AiLCJtYXJnaW5Ub3AiLCJjb250ZW50QmxvY2siLCJibG9ja1JlY3QiLCJnZXRCb3VuZGluZ0NsaWVudFJlY3QiLCJibG9ja1RvcCIsInRvcCIsImJsb2NrSGVpZ2h0IiwiaGVpZ2h0IiwiaXNWaXNpYmxlIiwiYm90dG9tIiwidGhyb3R0bGUiLCJmdW5jIiwibGltaXQiLCJpblRocm90dGxlIiwiYXJncyIsImFyZ3VtZW50cyIsImNvbnRleHQiLCJhcHBseSIsInNldFRpbWVvdXQiLCJjb250ZW50RG9jdW1lbnQiLCJibG9ja0hhbmRsZXMiLCJzaWRlYmFyIiwidGhyb3R0bGVkVXBkYXRlIiwibGFzdEtub3duVXJsIiwidXJsQ2hlY2tJbnRlcnZhbCIsInNldEludGVydmFsIiwiRXZlbnQiLCJjb250YWlucyIsImtleSIsInNvdXJjZUxvY2FsZSIsInRhcmdldExvY2FsZSIsInJlbG9hZCIsImRhdGEiLCJ0eXBlIiwicHJldmlld0lmcmFtZSIsInB1Ymxpc2hNb2RhbCIsInNlbGVjdEFsbENoZWNrYm94IiwibG9jYWxlQ2hlY2tib3hlcyIsImNvbmZpcm1QdWJsaXNoQnRuIiwiY2hlY2tib3giLCJjaGVja2VkIiwiYWxsQ2hlY2tlZCIsIkFycmF5IiwiZnJvbSIsImV2ZXJ5IiwiY2IiLCJub25lQ2hlY2tlZCIsImluZGV0ZXJtaW5hdGUiLCJzZWxlY3RlZExvY2FsZXMiLCJmaWx0ZXIiLCJtYXAiLCJ2YWx1ZSIsImFwcGVuZCJdLCJzb3VyY2VSb290IjoiIn0=