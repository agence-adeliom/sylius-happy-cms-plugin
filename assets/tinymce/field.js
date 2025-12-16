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
 * @param {HTMLElement|null} scope - Optional scope to limit search (for AJAX contexts)
 */
function initializeTinyMCE() {
    const searchRoot = document;

    // Ensure the TinyMCE webcomponent script is loaded
    loadScriptIfNeeded('/bundles/tinymce/ext/tinymce-webcomponent.js')
        .then(() => {
            console.log('TinyMCE: Webcomponent script loaded');
            // Wait a bit for the custom element to be defined
            return customElements.whenDefined('tinymce-editor').catch(() => {
                // If not defined yet, wait a bit more
                return new Promise(resolve => setTimeout(resolve, 100));
            });
        })
        .then(() => {
            console.log('TinyMCE: Custom element defined');
            // Find all tinymce-editor elements in the scope
            const editors = searchRoot.querySelectorAll('tinymce-editor');

            console.log(`TinyMCE: Found ${editors.length} editor(s) to initialize`);

            editors.forEach(editor => {
                console.log('TinyMCE: Processing editor element', editor);
                console.log('TinyMCE: editor._status =', editor._status);
                console.log('TinyMCE: editor._editor exists?', !!editor._editor);

                // NEW APPROACH: Instead of trying to reset the webcomponent state,
                // we completely replace it with a fresh clone

                const parent = editor.parentNode;
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
                    const editorId = editor.getAttribute('id');
                    if (editorId && window.tinymce.get(editorId)) {
                        console.log('TinyMCE: Removing instance from global registry:', editorId);
                        try {
                            window.tinymce.get(editorId).remove();
                        } catch (e) {
                            console.warn('TinyMCE: Error removing from global registry:', e);
                        }
                    }
                }

                // Step 3: Create a completely new webcomponent by cloning the original
                console.log('TinyMCE: Creating fresh clone of webcomponent');
                const clone = editor.cloneNode(true);

                // Step 4: Replace the old element with the fresh clone
                console.log('TinyMCE: Replacing old element with clone');
                parent.replaceChild(clone, editor);

                // Step 5: The new element should auto-initialize via its connectedCallback
                // Wait a bit and inject custom styles
                setTimeout(() => {
                    console.log('TinyMCE: Checking clone status - _status =', clone._status);
                    injectTinyMCEStyles(clone);
                }, 500);
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
    console.log('TinyMCE: Reinitializing after AJAX load', event.detail);

    // Give the DOM a moment to settle after AJAX update
    setTimeout(() => {
        initializeTinyMCE();
    }, 100);
});
