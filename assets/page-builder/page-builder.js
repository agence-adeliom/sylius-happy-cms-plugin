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

  // Preview iframe. The page builder never reads the preview document: the preview may be served on another origin
  // than the admin (a dedicated shop host), where the browser forbids it. Every exchange goes through postMessage
  // with the preview bridge (preview-bridge.js), injected in the preview page by PageBuilderPreviewBridgeListener.
  const iframe = document.querySelector('[data-page-builder-target="iframe"]');
  const previewUrl = iframeUri ? new URL(iframeUri, window.location.href) : null;
  const previewOrigin = previewUrl ? previewUrl.origin : null;
  // The bridge reports the path of every loaded document: blocks are editable on the edited page only
  let previewIsValid = false;

  // Editor panel elements (used by editBlock function)
  const editorPanel = document.querySelector('[data-page-builder-target="editorPanel"]');
  const editorToggle = document.querySelector('[data-page-builder-target="editorToggle"]');

  /**
   * Send a message to the preview bridge, on the preview origin only
   */
  function sendToPreview(type, payload) {
    if (iframe && iframe.contentWindow && previewOrigin) {
      iframe.contentWindow.postMessage(Object.assign({ type: type }, payload || {}), previewOrigin);
    }
  }

  /**
   * Reload the preview (after a block is saved, moved, published or deleted)
   */
  function reloadPreview() {
    sendToPreview('hcms:reload');
  }

  /**
   * Common function to handle block editing
   * Accessible globally for all buttons (sidebar, iframe, add block)
   */
  function editBlock(blockId) {
    // Security check: only allow editing on the edited page (skip for create mode)
    if (blockId !== null && !previewIsValid) {
      console.error('Cannot edit block - the preview left the edited page');
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
  }

  /**
   * Common function to close the block editor panel
   * Resets the form and closes the panel
   */
  function closeBlock() {
    if (!editorPanel) return;

    // Dispatch custom event to the BlockEditor Stimulus controller
    const pageBuilderElement = document.querySelector('[data-controller*="agence-adeliom--sylius-happy-cms-plugin--builder-block-editor"]');
    if (pageBuilderElement) {
      const event = new CustomEvent('block-editor:close');
      pageBuilderElement.dispatchEvent(event);
    } else {
      console.warn('Page builder element not found');
    }
  }

  // Theme tokens forwarded to the shop preview iframe, which knows nothing about the admin theme variables
  const PREVIEW_THEME_TOKENS = ['--hcms-accent', '--hcms-accent-hover', '--hcms-warning', '--hcms-danger'];

  /**
   * Resolved admin theme tokens. Empty values are skipped so the inline fallbacks of the overlays still apply.
   */
  function previewThemeTokens() {
    const rootStyles = getComputedStyle(document.documentElement);
    const tokens = {};
    PREVIEW_THEME_TOKENS.forEach(token => {
      const value = rootStyles.getPropertyValue(token).trim();
      if (value !== '') {
        tokens[token] = value;
      }
    });

    return tokens;
  }

  /**
   * Propagate the admin theme (light / dark) to the block form iframes and refresh the preview tokens.
   * The form iframe is recreated by the Live Component, so it is queried on every call.
   */
  function syncTheme() {
    const theme = document.documentElement.getAttribute('data-bs-theme') || 'light';

    document.querySelectorAll('.block-editor-form-iframe').forEach(formIframe => {
      try {
        const formDocument = formIframe.contentDocument;
        if (formDocument && formDocument.documentElement) {
          formDocument.documentElement.setAttribute('data-bs-theme', theme);
        }
      } catch (error) {
        // Iframe not accessible yet
      }
    });

    sendToPreview('hcms:theme', { tokens: previewThemeTokens() });
  }

  // Sylius theme switcher only toggles data-bs-theme on <html>
  new MutationObserver(syncTheme).observe(document.documentElement, {
    attributes: true,
    attributeFilter: ['data-bs-theme'],
  });

  // Align every newly loaded form iframe with the current admin theme (load does not bubble: capture)
  if (editorPanel) {
    editorPanel.addEventListener('load', function(e) {
      if (e.target instanceof HTMLIFrameElement && e.target.classList.contains('block-editor-form-iframe')) {
        syncTheme();
      }
    }, true);
  }

  const blockHandlesContainer = document.querySelector('[data-page-builder-target="blockHandles"]');

  function blockHandle(blockId) {
    return document.querySelector(`.page-builder__block-handle[data-block-id="${CSS.escape(String(blockId))}"]`);
  }

  /**
   * Align the sidebar handles on the blocks, from the positions sent by the preview bridge
   */
  function updateBlockHandlesPositions(blocks, viewportHeight) {
    if (!blockHandlesContainer) return;

    // Get the offset of the handles container to compensate for the add block button
    const addBlockButton = document.querySelector('[data-page-builder-target="addBlock"]');
    let offset = 0;
    if (addBlockButton) {
      const addBlockMarginBottom = parseFloat(window.getComputedStyle(addBlockButton).marginBottom);
      const handlesContainerMarginTop = parseFloat(window.getComputedStyle(blockHandlesContainer).marginTop);
      offset = addBlockButton.offsetHeight + addBlockMarginBottom + handlesContainerMarginTop;
    }

    blocks.forEach(block => {
      const handle = blockHandle(block.id);
      if (!handle) return;

      handle.style.transform = `translateY(${block.top - offset}px)`;
      handle.style.height = block.height + 'px';

      // A block is visible if any part of it is in the viewport
      handle.classList.toggle('is-active', block.top < viewportHeight && block.top + block.height > 0);
    });
  }

  if (iframe && previewOrigin) {
    // Messages from the preview bridge, on the preview origin only
    window.addEventListener('message', function(event) {
      if (event.source !== iframe.contentWindow || event.origin !== previewOrigin) {
        return;
      }

      const data = event.data || {};

      switch (data.type) {
        case 'hcms:ready':
          previewIsValid = data.path === previewUrl.pathname;
          if (!previewIsValid) {
            console.warn('Preview left the edited page:', data.path);
          }
          sendToPreview('hcms:theme', { tokens: previewThemeTokens() });
          break;

        case 'hcms:edit-block':
          editBlock(String(data.blockId));
          break;

        case 'hcms:block-hover': {
          const handle = previewIsValid ? blockHandle(data.blockId) : null;
          const handleButton = handle ? handle.querySelector('.page-builder__block-handle-button') : null;
          if (handleButton) {
            handleButton.classList.toggle('is-hovered', data.hovered === true);
          }
          break;
        }

        case 'hcms:blocks-layout':
          if (previewIsValid && Array.isArray(data.blocks)) {
            updateBlockHandlesPositions(data.blocks, data.viewportHeight);
          }
          break;
      }
    });

    // Handshake on every load (first load, reload, navigation): the bridge then answers hcms:ready
    iframe.addEventListener('load', function() {
      previewIsValid = false;
      sendToPreview('hcms:connect');
    });

    // Sidebar handles: hover highlights the block in the preview, click edits it
    document.querySelectorAll('.page-builder__block-handle').forEach(handle => {
      const handleButton = handle.querySelector('.page-builder__block-handle-button');
      if (!handleButton) return;

      handleButton.addEventListener('mouseenter', function() {
        sendToPreview('hcms:highlight-block', { blockId: handle.dataset.blockId, highlighted: true });
      });

      handleButton.addEventListener('mouseleave', function() {
        sendToPreview('hcms:highlight-block', { blockId: handle.dataset.blockId, highlighted: false });
      });

      handleButton.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        editBlock(handle.dataset.blockId);
      });
    });

    // Reload requested by the BlockEditor controller (block saved from the toolbar or the form)
    document.addEventListener('page-builder:reload-preview', reloadPreview);

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

  // Listen for postMessage from the block edit iframe, served by the admin itself
  window.addEventListener('message', function(event) {
    if (event.origin !== window.location.origin) return;

    const data = event.data;

    // Handle block:saved message from the form iframe: reload the preview to show the updated block
    if (data && data.type === 'block:saved') {
      reloadPreview();
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
