/******/ (() => { // webpackBootstrap
/*!*********************************!*\
  !*** ./assets/tinymce/field.js ***!
  \*********************************/
/**
 * TinyMCE Field Handler for AJAX/Live Components
 *
 * This script ensures TinyMCE editors are properly initialized when loaded via AJAX
 * in the page builder or other dynamic contexts.
 */

/**
 * Dynamically load a script if not already loaded
 */
function loadScriptIfNeeded(url) {
  var checkGlobal = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : null;
  return new Promise(function (resolve, reject) {
    // If we have a check function and it passes, script is already loaded
    if (checkGlobal && checkGlobal()) {
      resolve();
      return;
    }

    // Check if script is already in the DOM
    if (document.querySelector("script[src=\"".concat(url, "\"]"))) {
      resolve();
      return;
    }
    var script = document.createElement('script');
    script.src = url;
    script.type = 'module';
    script.onload = function () {
      return resolve();
    };
    script.onerror = function () {
      return reject(new Error("Failed to load script: ".concat(url)));
    };
    document.head.appendChild(script);
  });
}

/**
 * Setup callback to update live component when TinyMCE content changes
 */
function setupLiveComponentCallback(editor) {
  // Wait for the TinyMCE instance to be ready
  var _checkEditorReady = function checkEditorReady() {
    if (editor._editor) {
      // Add change listener to update live component
      editor._editor.on('Change Input Undo Redo', function () {
        // Update the value and dispatch change event
        var event = new Event('change', {
          'bubbles': true
        });
        editor.dispatchEvent(event);
        console.log('TinyMCE: Live component updated with new content');
      });
      console.log('TinyMCE: Live component callback registered successfully');
    } else {
      // Retry if editor not ready yet
      setTimeout(_checkEditorReady, 50);
    }
  };
  _checkEditorReady();
}

/**
 * Inject custom CSS into TinyMCE shadow DOM
 */
function injectTinyMCEStyles(editor) {
  // Wait for the shadow root to be available
  var _checkShadowRoot = function checkShadowRoot() {
    if (editor.shadowRoot) {
      // Create a style element
      var style = document.createElement('style');
      style.textContent = "\n                .tox-fullscreen {\n                    max-width: 100% !important;\n                    max-height: 100% !important;\n                }\n\n                /* Additional TinyMCE overrides for page builder */\n                .tox-tinymce {\n                    border-radius: 4px;\n                }\n            ";

      // Inject into shadow DOM
      editor.shadowRoot.appendChild(style);
      console.log('TinyMCE: Custom styles injected into shadow DOM');
    } else {
      // Retry if shadow root not ready yet
      setTimeout(_checkShadowRoot, 50);
    }
  };
  _checkShadowRoot();
}

/**
 * Initialize or reinitialize TinyMCE editors
 * @param {HTMLElement|null} scope - Optional scope to limit search (for AJAX contexts)
 */
function initializeTinyMCE() {
  var searchRoot = document;

  // Ensure the TinyMCE webcomponent script is loaded
  loadScriptIfNeeded('/bundles/tinymce/ext/tinymce-webcomponent.js').then(function () {
    console.log('TinyMCE: Webcomponent script loaded');
    // Wait a bit for the custom element to be defined
    return customElements.whenDefined('tinymce-editor')["catch"](function () {
      // If not defined yet, wait a bit more
      return new Promise(function (resolve) {
        return setTimeout(resolve, 100);
      });
    });
  }).then(function () {
    console.log('TinyMCE: Custom element defined');
    // Find all tinymce-editor elements in the scope
    var editors = searchRoot.querySelectorAll('tinymce-editor');
    console.log("TinyMCE: Found ".concat(editors.length, " editor(s) to initialize"));
    editors.forEach(function (editor) {
      var parent = editor.parentNode;
      if (!parent) {
        console.warn('TinyMCE: Editor has no parent, cannot replace');
        return;
      }

      // Step 1: Destroy the existing TinyMCE instance if it exists
      if (editor._editor) {
        console.log('TinyMCE: Destroying existing editor instance');
        try {
          editor._editor.remove();
        } catch (e) {
          console.warn('TinyMCE: Error destroying instance:', e);
        }
      }

      // Step 2: Also try to remove any TinyMCE instances by ID from global registry
      if (window.tinymce) {
        var editorId = editor.getAttribute('id');
        if (editorId && window.tinymce.get(editorId)) {
          console.log('TinyMCE: Removing instance from global registry:', editorId);
          try {
            window.tinymce.get(editorId).remove();
          } catch (e) {
            console.warn('TinyMCE: Error removing from global registry:', e);
          }
        }
      }

      // // Step 3: Create a completely new webcomponent by cloning the original
      // console.log('TinyMCE: Creating fresh clone of webcomponent');
      // const clone = editor.cloneNode(true);
      //
      // // Step 4: Replace the old element with the fresh clone
      // console.log('TinyMCE: Replacing old element with clone');
      // parent.replaceChild(clone, editor);

      // Step 5: The new element should auto-initialize via its connectedCallback
      // Wait a bit and inject custom styles
      setTimeout(function () {
        console.log('TinyMCE: Checking clone status - _status =', editor._status);
        injectTinyMCEStyles(editor);

        // Step 6: Add change callback to update live component
        setupLiveComponentCallback(editor);
      }, 500);
    });
  })["catch"](function (error) {
    console.error('Error initializing TinyMCE:', error);
  });
}

/**
 * Initialize on DOM ready
 */
if (document.readyState === 'loading') {
  window.addEventListener('DOMContentLoaded', function () {
    // Give the DOM a moment to settle after AJAX update
    setTimeout(function () {
      initializeTinyMCE();
    }, 100);
  });
} else {
  // Give the DOM a moment to settle after AJAX update
  setTimeout(function () {
    initializeTinyMCE();
  }, 100);
}

/**
 * Reinitialize when loaded in the page builder or other AJAX contexts
 */
window.addEventListener('sylius-crud:dynamic:reload', function () {
  // Give the DOM a moment to settle after AJAX update
  setTimeout(function () {
    initializeTinyMCE();
  }, 100);
});
/******/ })()
;
//# sourceMappingURL=data:application/json;charset=utf-8;base64,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QUFNO01BQ2ZNLGlCQUFpQixDQUFDLENBQUM7SUFDckIsQ0FBQyxFQUFFLEdBQUcsQ0FBQztFQUNULENBQUMsQ0FBQztBQUNOLENBQUMsTUFBTTtFQUNMO0VBQ0FOLFVBQVUsQ0FBQyxZQUFNO0lBQ2ZNLGlCQUFpQixDQUFDLENBQUM7RUFDckIsQ0FBQyxFQUFFLEdBQUcsQ0FBQztBQUNUOztBQUVBO0FBQ0E7QUFDQTtBQUNBYSxNQUFNLENBQUNRLGdCQUFnQixDQUFDLDRCQUE0QixFQUFFLFlBQU07RUFDeEQ7RUFDQTNCLFVBQVUsQ0FBQyxZQUFNO0lBQ2JNLGlCQUFpQixDQUFDLENBQUM7RUFDdkIsQ0FBQyxFQUFFLEdBQUcsQ0FBQztBQUNYLENBQUMsQ0FBQyxDIiwic291cmNlcyI6WyJ3ZWJwYWNrOi8vQGFnZW5jZS1hZGVsaW9tL3N5bGl1cy1oYXBweS1jbXMtcGx1Z2luLy4vYXNzZXRzL3RpbnltY2UvZmllbGQuanMiXSwic291cmNlc0NvbnRlbnQiOlsiLyoqXG4gKiBUaW55TUNFIEZpZWxkIEhhbmRsZXIgZm9yIEFKQVgvTGl2ZSBDb21wb25lbnRzXG4gKlxuICogVGhpcyBzY3JpcHQgZW5zdXJlcyBUaW55TUNFIGVkaXRvcnMgYXJlIHByb3Blcmx5IGluaXRpYWxpemVkIHdoZW4gbG9hZGVkIHZpYSBBSkFYXG4gKiBpbiB0aGUgcGFnZSBidWlsZGVyIG9yIG90aGVyIGR5bmFtaWMgY29udGV4dHMuXG4gKi9cblxuLyoqXG4gKiBEeW5hbWljYWxseSBsb2FkIGEgc2NyaXB0IGlmIG5vdCBhbHJlYWR5IGxvYWRlZFxuICovXG5mdW5jdGlvbiBsb2FkU2NyaXB0SWZOZWVkZWQodXJsLCBjaGVja0dsb2JhbCA9IG51bGwpIHtcbiAgICByZXR1cm4gbmV3IFByb21pc2UoKHJlc29sdmUsIHJlamVjdCkgPT4ge1xuICAgICAgICAvLyBJZiB3ZSBoYXZlIGEgY2hlY2sgZnVuY3Rpb24gYW5kIGl0IHBhc3Nlcywgc2NyaXB0IGlzIGFscmVhZHkgbG9hZGVkXG4gICAgICAgIGlmIChjaGVja0dsb2JhbCAmJiBjaGVja0dsb2JhbCgpKSB7XG4gICAgICAgICAgICByZXNvbHZlKCk7XG4gICAgICAgICAgICByZXR1cm47XG4gICAgICAgIH1cblxuICAgICAgICAvLyBDaGVjayBpZiBzY3JpcHQgaXMgYWxyZWFkeSBpbiB0aGUgRE9NXG4gICAgICAgIGlmIChkb2N1bWVudC5xdWVyeVNlbGVjdG9yKGBzY3JpcHRbc3JjPVwiJHt1cmx9XCJdYCkpIHtcbiAgICAgICAgICAgIHJlc29sdmUoKTtcbiAgICAgICAgICAgIHJldHVybjtcbiAgICAgICAgfVxuXG4gICAgICAgIGNvbnN0IHNjcmlwdCA9IGRvY3VtZW50LmNyZWF0ZUVsZW1lbnQoJ3NjcmlwdCcpO1xuICAgICAgICBzY3JpcHQuc3JjID0gdXJsO1xuICAgICAgICBzY3JpcHQudHlwZSA9ICdtb2R1bGUnO1xuICAgICAgICBzY3JpcHQub25sb2FkID0gKCkgPT4gcmVzb2x2ZSgpO1xuICAgICAgICBzY3JpcHQub25lcnJvciA9ICgpID0+IHJlamVjdChuZXcgRXJyb3IoYEZhaWxlZCB0byBsb2FkIHNjcmlwdDogJHt1cmx9YCkpO1xuICAgICAgICBkb2N1bWVudC5oZWFkLmFwcGVuZENoaWxkKHNjcmlwdCk7XG4gICAgfSk7XG59XG5cbi8qKlxuICogU2V0dXAgY2FsbGJhY2sgdG8gdXBkYXRlIGxpdmUgY29tcG9uZW50IHdoZW4gVGlueU1DRSBjb250ZW50IGNoYW5nZXNcbiAqL1xuZnVuY3Rpb24gc2V0dXBMaXZlQ29tcG9uZW50Q2FsbGJhY2soZWRpdG9yKSB7XG4gICAgLy8gV2FpdCBmb3IgdGhlIFRpbnlNQ0UgaW5zdGFuY2UgdG8gYmUgcmVhZHlcbiAgICBjb25zdCBjaGVja0VkaXRvclJlYWR5ID0gKCkgPT4ge1xuICAgICAgICBpZiAoZWRpdG9yLl9lZGl0b3IpIHtcbiAgICAgICAgICAgIC8vIEFkZCBjaGFuZ2UgbGlzdGVuZXIgdG8gdXBkYXRlIGxpdmUgY29tcG9uZW50XG4gICAgICAgICAgICBlZGl0b3IuX2VkaXRvci5vbignQ2hhbmdlIElucHV0IFVuZG8gUmVkbycsICgpID0+IHtcbiAgICAgICAgICAgICAgICAvLyBVcGRhdGUgdGhlIHZhbHVlIGFuZCBkaXNwYXRjaCBjaGFuZ2UgZXZlbnRcbiAgICAgICAgICAgICAgICBjb25zdCBldmVudCA9IG5ldyBFdmVudCgnY2hhbmdlJywgeyAnYnViYmxlcyc6IHRydWUgfSk7XG4gICAgICAgICAgICAgICAgZWRpdG9yLmRpc3BhdGNoRXZlbnQoZXZlbnQpO1xuXG4gICAgICAgICAgICAgICAgY29uc29sZS5sb2coJ1RpbnlNQ0U6IExpdmUgY29tcG9uZW50IHVwZGF0ZWQgd2l0aCBuZXcgY29udGVudCcpO1xuICAgICAgICAgICAgfSk7XG5cbiAgICAgICAgICAgIGNvbnNvbGUubG9nKCdUaW55TUNFOiBMaXZlIGNvbXBvbmVudCBjYWxsYmFjayByZWdpc3RlcmVkIHN1Y2Nlc3NmdWxseScpO1xuICAgICAgICB9IGVsc2Uge1xuICAgICAgICAgICAgLy8gUmV0cnkgaWYgZWRpdG9yIG5vdCByZWFkeSB5ZXRcbiAgICAgICAgICAgIHNldFRpbWVvdXQoY2hlY2tFZGl0b3JSZWFkeSwgNTApO1xuICAgICAgICB9XG4gICAgfTtcblxuICAgIGNoZWNrRWRpdG9yUmVhZHkoKTtcbn1cblxuLyoqXG4gKiBJbmplY3QgY3VzdG9tIENTUyBpbnRvIFRpbnlNQ0Ugc2hhZG93IERPTVxuICovXG5mdW5jdGlvbiBpbmplY3RUaW55TUNFU3R5bGVzKGVkaXRvcikge1xuICAgIC8vIFdhaXQgZm9yIHRoZSBzaGFkb3cgcm9vdCB0byBiZSBhdmFpbGFibGVcbiAgICBjb25zdCBjaGVja1NoYWRvd1Jvb3QgPSAoKSA9PiB7XG4gICAgICAgIGlmIChlZGl0b3Iuc2hhZG93Um9vdCkge1xuICAgICAgICAgICAgLy8gQ3JlYXRlIGEgc3R5bGUgZWxlbWVudFxuICAgICAgICAgICAgY29uc3Qgc3R5bGUgPSBkb2N1bWVudC5jcmVhdGVFbGVtZW50KCdzdHlsZScpO1xuICAgICAgICAgICAgc3R5bGUudGV4dENvbnRlbnQgPSBgXG4gICAgICAgICAgICAgICAgLnRveC1mdWxsc2NyZWVuIHtcbiAgICAgICAgICAgICAgICAgICAgbWF4LXdpZHRoOiAxMDAlICFpbXBvcnRhbnQ7XG4gICAgICAgICAgICAgICAgICAgIG1heC1oZWlnaHQ6IDEwMCUgIWltcG9ydGFudDtcbiAgICAgICAgICAgICAgICB9XG5cbiAgICAgICAgICAgICAgICAvKiBBZGRpdGlvbmFsIFRpbnlNQ0Ugb3ZlcnJpZGVzIGZvciBwYWdlIGJ1aWxkZXIgKi9cbiAgICAgICAgICAgICAgICAudG94LXRpbnltY2Uge1xuICAgICAgICAgICAgICAgICAgICBib3JkZXItcmFkaXVzOiA0cHg7XG4gICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgYDtcblxuICAgICAgICAgICAgLy8gSW5qZWN0IGludG8gc2hhZG93IERPTVxuICAgICAgICAgICAgZWRpdG9yLnNoYWRvd1Jvb3QuYXBwZW5kQ2hpbGQoc3R5bGUpO1xuICAgICAgICAgICAgY29uc29sZS5sb2coJ1RpbnlNQ0U6IEN1c3RvbSBzdHlsZXMgaW5qZWN0ZWQgaW50byBzaGFkb3cgRE9NJyk7XG4gICAgICAgIH0gZWxzZSB7XG4gICAgICAgICAgICAvLyBSZXRyeSBpZiBzaGFkb3cgcm9vdCBub3QgcmVhZHkgeWV0XG4gICAgICAgICAgICBzZXRUaW1lb3V0KGNoZWNrU2hhZG93Um9vdCwgNTApO1xuICAgICAgICB9XG4gICAgfTtcblxuICAgIGNoZWNrU2hhZG93Um9vdCgpO1xufVxuXG4vKipcbiAqIEluaXRpYWxpemUgb3IgcmVpbml0aWFsaXplIFRpbnlNQ0UgZWRpdG9yc1xuICogQHBhcmFtIHtIVE1MRWxlbWVudHxudWxsfSBzY29wZSAtIE9wdGlvbmFsIHNjb3BlIHRvIGxpbWl0IHNlYXJjaCAoZm9yIEFKQVggY29udGV4dHMpXG4gKi9cbmZ1bmN0aW9uIGluaXRpYWxpemVUaW55TUNFKCkge1xuICAgIGNvbnN0IHNlYXJjaFJvb3QgPSBkb2N1bWVudDtcblxuICAgIC8vIEVuc3VyZSB0aGUgVGlueU1DRSB3ZWJjb21wb25lbnQgc2NyaXB0IGlzIGxvYWRlZFxuICAgIGxvYWRTY3JpcHRJZk5lZWRlZCgnL2J1bmRsZXMvdGlueW1jZS9leHQvdGlueW1jZS13ZWJjb21wb25lbnQuanMnKVxuICAgICAgICAudGhlbigoKSA9PiB7XG4gICAgICAgICAgICBjb25zb2xlLmxvZygnVGlueU1DRTogV2ViY29tcG9uZW50IHNjcmlwdCBsb2FkZWQnKTtcbiAgICAgICAgICAgIC8vIFdhaXQgYSBiaXQgZm9yIHRoZSBjdXN0b20gZWxlbWVudCB0byBiZSBkZWZpbmVkXG4gICAgICAgICAgICByZXR1cm4gY3VzdG9tRWxlbWVudHMud2hlbkRlZmluZWQoJ3RpbnltY2UtZWRpdG9yJykuY2F0Y2goKCkgPT4ge1xuICAgICAgICAgICAgICAgIC8vIElmIG5vdCBkZWZpbmVkIHlldCwgd2FpdCBhIGJpdCBtb3JlXG4gICAgICAgICAgICAgICAgcmV0dXJuIG5ldyBQcm9taXNlKHJlc29sdmUgPT4gc2V0VGltZW91dChyZXNvbHZlLCAxMDApKTtcbiAgICAgICAgICAgIH0pO1xuICAgICAgICB9KVxuICAgICAgICAudGhlbigoKSA9PiB7XG4gICAgICAgICAgICBjb25zb2xlLmxvZygnVGlueU1DRTogQ3VzdG9tIGVsZW1lbnQgZGVmaW5lZCcpO1xuICAgICAgICAgICAgLy8gRmluZCBhbGwgdGlueW1jZS1lZGl0b3IgZWxlbWVudHMgaW4gdGhlIHNjb3BlXG4gICAgICAgICAgICBjb25zdCBlZGl0b3JzID0gc2VhcmNoUm9vdC5xdWVyeVNlbGVjdG9yQWxsKCd0aW55bWNlLWVkaXRvcicpO1xuXG4gICAgICAgICAgICBjb25zb2xlLmxvZyhgVGlueU1DRTogRm91bmQgJHtlZGl0b3JzLmxlbmd0aH0gZWRpdG9yKHMpIHRvIGluaXRpYWxpemVgKTtcblxuICAgICAgICAgICAgZWRpdG9ycy5mb3JFYWNoKGVkaXRvciA9PiB7XG5cbiAgICAgICAgICAgICAgICBjb25zdCBwYXJlbnQgPSBlZGl0b3IucGFyZW50Tm9kZTtcbiAgICAgICAgICAgICAgICBpZiAoIXBhcmVudCkge1xuICAgICAgICAgICAgICAgICAgICBjb25zb2xlLndhcm4oJ1RpbnlNQ0U6IEVkaXRvciBoYXMgbm8gcGFyZW50LCBjYW5ub3QgcmVwbGFjZScpO1xuICAgICAgICAgICAgICAgICAgICByZXR1cm47XG4gICAgICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICAgICAgLy8gU3RlcCAxOiBEZXN0cm95IHRoZSBleGlzdGluZyBUaW55TUNFIGluc3RhbmNlIGlmIGl0IGV4aXN0c1xuICAgICAgICAgICAgICAgIGlmIChlZGl0b3IuX2VkaXRvcikge1xuICAgICAgICAgICAgICAgICAgICBjb25zb2xlLmxvZygnVGlueU1DRTogRGVzdHJveWluZyBleGlzdGluZyBlZGl0b3IgaW5zdGFuY2UnKTtcbiAgICAgICAgICAgICAgICAgICAgdHJ5IHtcbiAgICAgICAgICAgICAgICAgICAgICAgIGVkaXRvci5fZWRpdG9yLnJlbW92ZSgpO1xuICAgICAgICAgICAgICAgICAgICB9IGNhdGNoIChlKSB7XG4gICAgICAgICAgICAgICAgICAgICAgICBjb25zb2xlLndhcm4oJ1RpbnlNQ0U6IEVycm9yIGRlc3Ryb3lpbmcgaW5zdGFuY2U6JywgZSk7XG4gICAgICAgICAgICAgICAgICAgIH1cbiAgICAgICAgICAgICAgICB9XG5cbiAgICAgICAgICAgICAgICAvLyBTdGVwIDI6IEFsc28gdHJ5IHRvIHJlbW92ZSBhbnkgVGlueU1DRSBpbnN0YW5jZXMgYnkgSUQgZnJvbSBnbG9iYWwgcmVnaXN0cnlcbiAgICAgICAgICAgICAgICBpZiAod2luZG93LnRpbnltY2UpIHtcbiAgICAgICAgICAgICAgICAgICAgY29uc3QgZWRpdG9ySWQgPSBlZGl0b3IuZ2V0QXR0cmlidXRlKCdpZCcpO1xuICAgICAgICAgICAgICAgICAgICBpZiAoZWRpdG9ySWQgJiYgd2luZG93LnRpbnltY2UuZ2V0KGVkaXRvcklkKSkge1xuICAgICAgICAgICAgICAgICAgICAgICAgY29uc29sZS5sb2coJ1RpbnlNQ0U6IFJlbW92aW5nIGluc3RhbmNlIGZyb20gZ2xvYmFsIHJlZ2lzdHJ5OicsIGVkaXRvcklkKTtcbiAgICAgICAgICAgICAgICAgICAgICAgIHRyeSB7XG4gICAgICAgICAgICAgICAgICAgICAgICAgICAgd2luZG93LnRpbnltY2UuZ2V0KGVkaXRvcklkKS5yZW1vdmUoKTtcbiAgICAgICAgICAgICAgICAgICAgICAgIH0gY2F0Y2ggKGUpIHtcbiAgICAgICAgICAgICAgICAgICAgICAgICAgICBjb25zb2xlLndhcm4oJ1RpbnlNQ0U6IEVycm9yIHJlbW92aW5nIGZyb20gZ2xvYmFsIHJlZ2lzdHJ5OicsIGUpO1xuICAgICAgICAgICAgICAgICAgICAgICAgfVxuICAgICAgICAgICAgICAgICAgICB9XG4gICAgICAgICAgICAgICAgfVxuXG4gICAgICAgICAgICAgICAgLy8gLy8gU3RlcCAzOiBDcmVhdGUgYSBjb21wbGV0ZWx5IG5ldyB3ZWJjb21wb25lbnQgYnkgY2xvbmluZyB0aGUgb3JpZ2luYWxcbiAgICAgICAgICAgICAgICAvLyBjb25zb2xlLmxvZygnVGlueU1DRTogQ3JlYXRpbmcgZnJlc2ggY2xvbmUgb2Ygd2ViY29tcG9uZW50Jyk7XG4gICAgICAgICAgICAgICAgLy8gY29uc3QgY2xvbmUgPSBlZGl0b3IuY2xvbmVOb2RlKHRydWUpO1xuICAgICAgICAgICAgICAgIC8vXG4gICAgICAgICAgICAgICAgLy8gLy8gU3RlcCA0OiBSZXBsYWNlIHRoZSBvbGQgZWxlbWVudCB3aXRoIHRoZSBmcmVzaCBjbG9uZVxuICAgICAgICAgICAgICAgIC8vIGNvbnNvbGUubG9nKCdUaW55TUNFOiBSZXBsYWNpbmcgb2xkIGVsZW1lbnQgd2l0aCBjbG9uZScpO1xuICAgICAgICAgICAgICAgIC8vIHBhcmVudC5yZXBsYWNlQ2hpbGQoY2xvbmUsIGVkaXRvcik7XG5cbiAgICAgICAgICAgICAgICAvLyBTdGVwIDU6IFRoZSBuZXcgZWxlbWVudCBzaG91bGQgYXV0by1pbml0aWFsaXplIHZpYSBpdHMgY29ubmVjdGVkQ2FsbGJhY2tcbiAgICAgICAgICAgICAgICAvLyBXYWl0IGEgYml0IGFuZCBpbmplY3QgY3VzdG9tIHN0eWxlc1xuICAgICAgICAgICAgICAgIHNldFRpbWVvdXQoKCkgPT4ge1xuICAgICAgICAgICAgICAgICAgICBjb25zb2xlLmxvZygnVGlueU1DRTogQ2hlY2tpbmcgY2xvbmUgc3RhdHVzIC0gX3N0YXR1cyA9JywgZWRpdG9yLl9zdGF0dXMpO1xuICAgICAgICAgICAgICAgICAgICBpbmplY3RUaW55TUNFU3R5bGVzKGVkaXRvcik7XG5cbiAgICAgICAgICAgICAgICAgICAgLy8gU3RlcCA2OiBBZGQgY2hhbmdlIGNhbGxiYWNrIHRvIHVwZGF0ZSBsaXZlIGNvbXBvbmVudFxuICAgICAgICAgICAgICAgICAgICBzZXR1cExpdmVDb21wb25lbnRDYWxsYmFjayhlZGl0b3IpO1xuICAgICAgICAgICAgICAgIH0sIDUwMCk7XG4gICAgICAgICAgICB9KTtcbiAgICAgICAgfSlcbiAgICAgICAgLmNhdGNoKGVycm9yID0+IHtcbiAgICAgICAgICAgIGNvbnNvbGUuZXJyb3IoJ0Vycm9yIGluaXRpYWxpemluZyBUaW55TUNFOicsIGVycm9yKTtcbiAgICAgICAgfSk7XG59XG5cbi8qKlxuICogSW5pdGlhbGl6ZSBvbiBET00gcmVhZHlcbiAqL1xuaWYgKGRvY3VtZW50LnJlYWR5U3RhdGUgPT09ICdsb2FkaW5nJykge1xuICAgIHdpbmRvdy5hZGRFdmVudExpc3RlbmVyKCdET01Db250ZW50TG9hZGVkJywgKCkgPT4ge1xuICAgICAgLy8gR2l2ZSB0aGUgRE9NIGEgbW9tZW50IHRvIHNldHRsZSBhZnRlciBBSkFYIHVwZGF0ZVxuICAgICAgc2V0VGltZW91dCgoKSA9PiB7XG4gICAgICAgIGluaXRpYWxpemVUaW55TUNFKCk7XG4gICAgICB9LCAxMDApO1xuICAgIH0pO1xufSBlbHNlIHtcbiAgLy8gR2l2ZSB0aGUgRE9NIGEgbW9tZW50IHRvIHNldHRsZSBhZnRlciBBSkFYIHVwZGF0ZVxuICBzZXRUaW1lb3V0KCgpID0+IHtcbiAgICBpbml0aWFsaXplVGlueU1DRSgpO1xuICB9LCAxMDApO1xufVxuXG4vKipcbiAqIFJlaW5pdGlhbGl6ZSB3aGVuIGxvYWRlZCBpbiB0aGUgcGFnZSBidWlsZGVyIG9yIG90aGVyIEFKQVggY29udGV4dHNcbiAqL1xud2luZG93LmFkZEV2ZW50TGlzdGVuZXIoJ3N5bGl1cy1jcnVkOmR5bmFtaWM6cmVsb2FkJywgKCkgPT4ge1xuICAgIC8vIEdpdmUgdGhlIERPTSBhIG1vbWVudCB0byBzZXR0bGUgYWZ0ZXIgQUpBWCB1cGRhdGVcbiAgICBzZXRUaW1lb3V0KCgpID0+IHtcbiAgICAgICAgaW5pdGlhbGl6ZVRpbnlNQ0UoKTtcbiAgICB9LCAxMDApO1xufSk7XG4iXSwibmFtZXMiOlsibG9hZFNjcmlwdElmTmVlZGVkIiwidXJsIiwiY2hlY2tHbG9iYWwiLCJhcmd1bWVudHMiLCJsZW5ndGgiLCJ1bmRlZmluZWQiLCJQcm9taXNlIiwicmVzb2x2ZSIsInJlamVjdCIsImRvY3VtZW50IiwicXVlcnlTZWxlY3RvciIsImNvbmNhdCIsInNjcmlwdCIsImNyZWF0ZUVsZW1lbnQiLCJzcmMiLCJ0eXBlIiwib25sb2FkIiwib25lcnJvciIsIkVycm9yIiwiaGVhZCIsImFwcGVuZENoaWxkIiwic2V0dXBMaXZlQ29tcG9uZW50Q2FsbGJhY2siLCJlZGl0b3IiLCJjaGVja0VkaXRvclJlYWR5IiwiX2VkaXRvciIsIm9uIiwiZXZlbnQiLCJFdmVudCIsImRpc3BhdGNoRXZlbnQiLCJjb25zb2xlIiwibG9nIiwic2V0VGltZW91dCIsImluamVjdFRpbnlNQ0VTdHlsZXMiLCJjaGVja1NoYWRvd1Jvb3QiLCJzaGFkb3dSb290Iiwic3R5bGUiLCJ0ZXh0Q29udGVudCIsImluaXRpYWxpemVUaW55TUNFIiwic2VhcmNoUm9vdCIsInRoZW4iLCJjdXN0b21FbGVtZW50cyIsIndoZW5EZWZpbmVkIiwiZWRpdG9ycyIsInF1ZXJ5U2VsZWN0b3JBbGwiLCJmb3JFYWNoIiwicGFyZW50IiwicGFyZW50Tm9kZSIsIndhcm4iLCJyZW1vdmUiLCJlIiwid2luZG93IiwidGlueW1jZSIsImVkaXRvcklkIiwiZ2V0QXR0cmlidXRlIiwiZ2V0IiwiX3N0YXR1cyIsImVycm9yIiwicmVhZHlTdGF0ZSIsImFkZEV2ZW50TGlzdGVuZXIiXSwic291cmNlUm9vdCI6IiJ9