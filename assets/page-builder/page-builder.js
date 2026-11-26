import { MessageType } from './preview-protocol.js';
import { PreviewBridge } from './preview-bridge.js';

// Shown when the preview leaves the signed page (it is no longer safe to edit through it)
const UNEXPECTED_PAGE_MESSAGE = 'The page has changed. Please reload the page builder to continue editing.';

document.addEventListener('DOMContentLoaded', function() {
  const builderConfiguration = document.querySelector('[data-builder-configuration]');
  const iframeUri = builderConfiguration.dataset.iframeUrl;
  const selectLocaleMessage = builderConfiguration.dataset.selectLocaleMessage;

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

  // Preview iframe, driven through postMessage only (it may be served from another host than the admin)
  const iframe = document.querySelector('[data-page-builder-target="iframe"]');
  const previewMessage = document.querySelector('[data-page-builder-target="previewMessage"]');

  // Editor panel elements (used by editBlock function)
  const editorPanel = document.querySelector('[data-page-builder-target="editorPanel"]');
  const editorToggle = document.querySelector('[data-page-builder-target="editorToggle"]');
  const editorContent = document.querySelector('[data-page-builder-target="editorContent"]');

  /**
   * Show a message above the preview. The container is a live region present from the start, so it is announced.
   */
  function showPreviewMessage(message) {
    if (!previewMessage) {
      alert(message);
      return;
    }

    const alertBox = document.createElement('div');
    alertBox.className = 'alert alert-warning mb-2';
    alertBox.textContent = message;
    previewMessage.replaceChildren(alertBox);
  }

  function clearPreviewMessage() {
    previewMessage?.replaceChildren();
  }

  function onUnexpectedPage() {
    alert(UNEXPECTED_PAGE_MESSAGE);
  }

  const bridge = iframe ? new PreviewBridge(iframe, {
    refreshEndpoint: builderConfiguration.dataset.previewUrlEndpoint,
    onMessage: handlePreviewMessage,
    onTimeout: () => showPreviewMessage(builderConfiguration.dataset.previewUnreachableMessage),
    onSessionExpired: () => showPreviewMessage(builderConfiguration.dataset.previewSessionExpiredMessage),
    onUnexpectedPage,
  }) : null;

  /**
   * Common function to handle block editing
   * Accessible globally for all buttons (sidebar, iframe, add block)
   */
  function editBlock(blockId) {
    console.log('Edit block:', blockId);

    // Security check: only allow editing while the preview shows the signed page (skip for create mode)
    if (blockId !== null && bridge && !bridge.isOnExpectedPage) {
      console.error('Cannot edit block - the preview shows another page');
      onUnexpectedPage();
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

  // Theme tokens forwarded to the shop preview iframe, which knows nothing about the admin theme variables
  const PREVIEW_THEME_TOKENS = ['--hcms-accent', '--hcms-accent-hover', '--hcms-warning', '--hcms-danger'];

  /**
   * Resolved admin theme tokens. Empty values are skipped so the inline fallbacks of the overlays still apply.
   */
  function previewThemeVariables() {
    const rootStyles = getComputedStyle(document.documentElement);

    return Object.fromEntries(
      PREVIEW_THEME_TOKENS
        .map(token => [token, rootStyles.getPropertyValue(token).trim()])
        .filter(([, value]) => value !== '')
    );
  }

  /**
   * Propagate the admin theme (light / dark) to the block form iframes and refresh the preview tokens.
   * The form iframe is recreated by the Live Component, so it is queried on every call.
   */
  function syncTheme() {
    const theme = document.documentElement.getAttribute('data-bs-theme') || 'light';

    // The block form iframes are served by the admin itself (same origin)
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

    bridge?.send(MessageType.THEME, { variables: previewThemeVariables() });
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

  function findHandle(blockId) {
    return document.querySelector(`.page-builder__block-handle[data-block-id="${CSS.escape(String(blockId))}"]`);
  }

  /**
   * Position each sidebar handle in front of its block, from the geometry sent by the preview agent
   */
  function updateBlockHandlesPositions(geometry) {
    if (!blockHandlesContainer || !Array.isArray(geometry.blocks)) return;

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

    geometry.blocks.forEach(block => {
      const handle = findHandle(block.id);
      if (!handle || !Number.isFinite(block.top) || !Number.isFinite(block.height)) return;

      // Position relative to the iframe viewport, compensating for the offset
      handle.style.transform = `translateY(${block.top - offset}px)`;
      handle.style.height = block.height + 'px';

      // A block is visible if any part of it is in the viewport
      const isVisible = block.top < geometry.viewportHeight && block.top + block.height > 0;
      handle.classList.toggle('is-active', isVisible);
    });
  }

  /**
   * Highlight the handle of the block hovered in the preview (null: none)
   */
  function highlightHandle(blockId) {
    document.querySelectorAll('.page-builder__block-handle-button.is-hovered').forEach(button => {
      button.classList.remove('is-hovered');
    });

    if (blockId === null || blockId === undefined) return;

    findHandle(blockId)?.querySelector('.page-builder__block-handle-button')?.classList.add('is-hovered');
  }

  function handlePreviewMessage(type, payload) {
    switch (type) {
      case MessageType.READY:
        clearPreviewMessage();
        syncTheme();
        updateBlockHandlesPositions(payload);
        break;
      case MessageType.GEOMETRY:
        updateBlockHandlesPositions(payload);
        break;
      case MessageType.HOVER:
        highlightHandle(payload.blockId);
        break;
      case MessageType.EDIT:
        if (payload.blockId !== undefined && payload.blockId !== null) {
          editBlock(String(payload.blockId));
        }
        break;
      default:
        break;
    }
  }

  // Hover and click on the sidebar handles act on the blocks of the preview
  document.querySelectorAll('.page-builder__block-handle').forEach(handle => {
    const handleButton = handle.querySelector('.page-builder__block-handle-button');
    if (!handleButton) return;

    handleButton.addEventListener('mouseenter', function() {
      bridge?.send(MessageType.HIGHLIGHT, { blockId: handle.dataset.blockId, on: true });
    });

    handleButton.addEventListener('mouseleave', function() {
      bridge?.send(MessageType.HIGHLIGHT, { blockId: handle.dataset.blockId, on: false });
    });

    handleButton.addEventListener('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      const blockId = handle.dataset.blockId;
      bridge?.send(MessageType.SCROLL_TO_BLOCK, { blockId });
      editBlock(blockId);
    });
  });

  // BlockEditor (toolbar actions, form saves) asks for a preview reload through this event
  document.addEventListener('happy-cms:preview-reload', function() {
    bridge?.reload();
  });

  // Load the signed preview URL
  bridge?.load(iframeUri);

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

    // Handle block:saved message from the form iframe
    if (data && data.type === 'block:saved') {
      console.log('Block saved with ID:', data.blockId);

      // Reload the preview with a freshly signed URL to show the updated block
      bridge?.reload();
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
