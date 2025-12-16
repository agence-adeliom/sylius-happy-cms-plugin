/**
 * TinyMCE Field Handler for AJAX/Live Components
 *
 * This script ensures TinyMCE editors are properly initialized when loaded via AJAX
 * in the page builder or other dynamic contexts.
 */

/**
 * Dynamically load a script if not already loaded
 */
function loadScriptIfNeeded(url, checkGlobal = null) {
    return new Promise((resolve, reject) => {
        // If we have a check function and it passes, script is already loaded
        if (checkGlobal && checkGlobal()) {
            resolve();
            return;
        }

        // Check if script is already in the DOM
        if (document.querySelector(`script[src="${url}"]`)) {
            resolve();
            return;
        }

        const script = document.createElement('script');
        script.src = url;
        script.type = 'module';
        script.onload = () => resolve();
        script.onerror = () => reject(new Error(`Failed to load script: ${url}`));
        document.head.appendChild(script);
    });
}

/**
 * Inject custom CSS into TinyMCE shadow DOM
 */
function injectTinyMCEStyles(editor) {
    // Wait for the shadow root to be available
    const checkShadowRoot = () => {
        if (editor.shadowRoot) {
            // Create a style element
            const style = document.createElement('style');
            style.textContent = `
                .tox-fullscreen {
                    max-width: 100% !important;
                    max-height: 100% !important;
                }

                /* Additional TinyMCE overrides for page builder */
                .tox-tinymce {
                    border-radius: 4px;
                }
            `;

            // Inject into shadow DOM
            editor.shadowRoot.appendChild(style);
            console.log('TinyMCE: Custom styles injected into shadow DOM');
        } else {
            // Retry if shadow root not ready yet
            setTimeout(checkShadowRoot, 50);
        }
    };

    checkShadowRoot();
}

/**
 * Initialize or reinitialize TinyMCE editors
 */
function initializeTinyMCE() {
    // Ensure the TinyMCE webcomponent script is loaded
    loadScriptIfNeeded('/bundles/tinymce/ext/tinymce-webcomponent.js')
        .then(() => {
            // Wait a bit for the custom element to be defined
            return customElements.whenDefined('tinymce-editor').catch(() => {
                // If not defined yet, wait a bit more
                return new Promise(resolve => setTimeout(resolve, 100));
            });
        })
        .then(() => {
            // Find all tinymce-editor elements
            const editors = document.querySelectorAll('tinymce-editor');

            editors.forEach(editor => {
                // Inject custom styles into shadow DOM
                injectTinyMCEStyles(editor);

                // Check if the editor is already initialized
                // The webcomponent should auto-initialize, but we can force a refresh if needed
                if (!editor.initialized && typeof editor.connectedCallback === 'function') {
                    // Force reconnection if the element was previously disconnected
                    editor.connectedCallback();
                }
            });
        })
        .catch(error => {
            console.error('Error initializing TinyMCE:', error);
        });
}

/**
 * Initialize on DOM ready
 */
if (document.readyState === 'loading') {
    window.addEventListener('DOMContentLoaded', initializeTinyMCE);
} else {
    initializeTinyMCE();
}

/**
 * Reinitialize when loaded in the page builder or other AJAX contexts
 */
window.addEventListener('on-load-builder', (event) => {
    console.log('TinyMCE: Reinitializing after AJAX load');

    // Give the DOM a moment to settle after AJAX update
    setTimeout(() => {
        initializeTinyMCE();
    }, 100);
});