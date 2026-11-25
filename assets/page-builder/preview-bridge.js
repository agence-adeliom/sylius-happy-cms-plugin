/**
 * Page builder preview bridge, loaded inside the preview iframe.
 *
 * The preview may be served on another origin than the admin (a dedicated shop host): the page builder cannot read
 * this document then. This bridge does the work inside the preview (hover, edit buttons, blocked navigation, block
 * positions) and talks to the page builder through postMessage only.
 *
 * Handshake: on each iframe load, the page builder sends `hcms:connect`. The bridge accepts it from its parent window
 * only and keeps its origin as the single origin it talks to.
 *
 * Messages sent to the page builder: hcms:ready, hcms:edit-block, hcms:block-hover, hcms:blocks-layout.
 * Messages received from the page builder: hcms:connect, hcms:theme, hcms:highlight-block, hcms:reload.
 */
(function () {
  if (window.parent === window) {
    return;
  }

  let parentOrigin = null;
  let initialized = false;

  function send(type, payload) {
    if (parentOrigin) {
      window.parent.postMessage(Object.assign({ type: type }, payload || {}), parentOrigin);
    }
  }

  function blocks() {
    return document.querySelectorAll('.content-block-wrapper[data-block-id]');
  }

  function block(blockId) {
    return document.querySelector('.content-block-wrapper[data-block-id="' + CSS.escape(String(blockId)) + '"]');
  }

  function injectStyles() {
    if (document.getElementById('page-builder-styles')) {
      return;
    }

    const style = document.createElement('style');
    style.id = 'page-builder-styles';
    style.textContent = `
      /* Minimal height for blocks (some are blank) */
      .content-block-wrapper { min-height: 150px; }

      /* Show outline and shadow on hover */
      .content-block-wrapper.is-hovered {
        outline: 3px solid var(--hcms-accent, #22b99a) !important;
        outline-offset: -3px !important;
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--hcms-accent, #22b99a) 20%, transparent) !important;
        z-index: 100 !important;
      }

      /* Show hover overlay when hovering */
      .content-block-wrapper.is-hovered .content-block-hover-overlay { display: flex !important; }

      /* Unpublished and deleted blocks have their own overlay */
      .content-block-wrapper[data-published="false"] .content-block-hover-overlay,
      .content-block-wrapper[data-deleted="true"] .content-block-hover-overlay { display: none !important; }

      /* Button hover effect */
      .content-block-edit-button:hover {
        background: var(--hcms-accent-hover, #1d9d83) !important;
        transform: translateY(-2px) !important;
        box-shadow: 0 6px 16px color-mix(in srgb, var(--hcms-accent, #22b99a) 50%, transparent) !important;
      }
    `;
    document.head.appendChild(style);
  }

  /**
   * Declare the admin theme tokens in the preview. Only --hcms-* custom properties are accepted.
   */
  function applyTheme(tokens) {
    let style = document.getElementById('page-builder-theme');
    if (!style) {
      style = document.createElement('style');
      style.id = 'page-builder-theme';
      document.head.appendChild(style);
    }

    const declarations = Object.keys(tokens || {})
      .filter(function (token) { return /^--hcms-[a-z-]+$/.test(token); })
      .map(function (token) { return token + ': ' + String(tokens[token]).replace(/[;{}<>]/g, '') + ';'; })
      .join(' ');
    style.textContent = ':root { ' + declarations + ' }';
  }

  let layoutFrame = null;

  /**
   * Send the position of each block relative to the iframe viewport, so the page builder aligns its handles.
   */
  function reportLayout() {
    if (layoutFrame !== null) {
      return;
    }

    layoutFrame = requestAnimationFrame(function () {
      layoutFrame = null;
      send('hcms:blocks-layout', {
        viewportHeight: window.innerHeight,
        blocks: Array.prototype.map.call(blocks(), function (element) {
          const rect = element.getBoundingClientRect();

          return { id: element.dataset.blockId, top: rect.top, height: rect.height };
        }),
      });
    });
  }

  function init() {
    if (initialized) {
      return;
    }
    initialized = true;

    injectStyles();

    // Edit buttons, and no navigation inside the preview
    document.addEventListener('click', function (event) {
      const editButton = event.target.closest('.content-block-edit-button');
      if (editButton) {
        event.preventDefault();
        event.stopPropagation();
        send('hcms:edit-block', { blockId: editButton.dataset.blockId });

        return;
      }

      const link = event.target.closest('a');
      if (link && link.href) {
        event.preventDefault();
      }
    }, true);

    // No form submission inside the preview
    document.addEventListener('submit', function (event) {
      event.preventDefault();
    }, true);

    blocks().forEach(function (element) {
      element.addEventListener('mouseenter', function () {
        element.classList.add('is-hovered');
        send('hcms:block-hover', { blockId: element.dataset.blockId, hovered: true });
      });
      element.addEventListener('mouseleave', function () {
        element.classList.remove('is-hovered');
        send('hcms:block-hover', { blockId: element.dataset.blockId, hovered: false });
      });
    });

    // Remove the Symfony toolbar inside the preview
    const toolbar = document.querySelector('.sf-toolbar');
    if (toolbar) {
      toolbar.remove();
    }

    window.addEventListener('scroll', reportLayout, { passive: true });
    window.addEventListener('resize', reportLayout);
    if ('ResizeObserver' in window) {
      new ResizeObserver(reportLayout).observe(document.body);
    }
  }

  window.addEventListener('message', function (event) {
    if (event.source !== window.parent) {
      return;
    }

    const data = event.data || {};

    if (data.type === 'hcms:connect') {
      // Keep the first origin only: a later connect from another origin is ignored
      if (parentOrigin !== null && parentOrigin !== event.origin) {
        return;
      }
      parentOrigin = event.origin;

      const ready = function () {
        init();
        send('hcms:ready', { path: window.location.pathname });
        reportLayout();
      };
      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', ready);
      } else {
        ready();
      }

      return;
    }

    if (event.origin !== parentOrigin) {
      return;
    }

    switch (data.type) {
      case 'hcms:theme':
        applyTheme(data.tokens);
        break;
      case 'hcms:highlight-block': {
        const element = block(data.blockId);
        if (element) {
          element.classList.toggle('is-hovered', data.highlighted === true);
        }
        break;
      }
      case 'hcms:reload':
        window.location.reload();
        break;
    }
  });
})();
