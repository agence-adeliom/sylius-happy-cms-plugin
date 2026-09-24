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
 * Setup callback to update live component when TinyMCE content changes
 */
function setupLiveComponentCallback(editor) {
    // Wait for the TinyMCE instance to be ready
    const checkEditorReady = () => {
        if (editor._editor) {
            // Add change listener to update live component
            editor._editor.on('Change Input Undo Redo', () => {
                // Update the value and dispatch change event
                const event = new Event('change', { 'bubbles': true });
                editor.dispatchEvent(event);

                console.log('TinyMCE: Live component updated with new content');
            });

            console.log('TinyMCE: Live component callback registered successfully');
        } else {
            // Retry if editor not ready yet
            setTimeout(checkEditorReady, 50);
        }
    };

    checkEditorReady();
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
 * Light / dark variants of the TinyMCE skins shipped with the bundle.
 * Custom skins not listed here are left untouched.
 */
const SKIN_VARIANTS = {
    'oxide': 'oxide-dark',
    'tinymce-5': 'tinymce-5-dark',
    'appstack_light': 'appstack_dark',
};
const CONTENT_CSS_VARIANTS = {
    'default': 'dark',
    'tinymce-5': 'tinymce-5-dark',
    'appstack_light': 'appstack_dark',
};

function isDarkTheme() {
    return document.documentElement.getAttribute('data-bs-theme') === 'dark';
}

/**
 * Resolve the variant of a skin value for the given theme
 */
function resolveVariant(value, variants, dark) {
    const lightValue = Object.keys(variants).find(light => light === value || variants[light] === value);
    if (lightValue === undefined) {
        return value;
    }

    return dark ? variants[lightValue] : lightValue;
}

/**
 * Align the skin and content_css attributes of an editor with the Sylius admin theme.
 * Returns true when an attribute has been changed.
 */
function applyThemeAttributes(editor) {
    const dark = isDarkTheme();
    let changed = false;

    [['skin', SKIN_VARIANTS], ['content_css', CONTENT_CSS_VARIANTS]].forEach(([attribute, variants]) => {
        const current = editor.getAttribute(attribute) || Object.keys(variants)[0];
        const expected = resolveVariant(current, variants, dark);
        if (expected !== editor.getAttribute(attribute)) {
            editor.setAttribute(attribute, expected);
            changed = true;
        }
    });

    return changed;
}

/**
 * Skin and content_css are only read by the webcomponent at init:
 * replace an initialized editor by a fresh clone carrying the current content.
 */
function syncEditorTheme(editor) {
    if (!applyThemeAttributes(editor) || !editor._editor) {
        return;
    }

    const content = editor._editor.getContent();
    const clone = editor.cloneNode(false);
    clone.textContent = content;
    // disconnectedCallback removes the old instance, connectedCallback initializes the clone
    editor.replaceWith(clone);

    setTimeout(() => {
        injectTinyMCEStyles(clone);
        setupLiveComponentCallback(clone);
    }, 500);
}

/**
 * Initialize or reinitialize TinyMCE editors
 * @param {HTMLElement|null} scope - Optional scope to limit search (for AJAX contexts)
 */
function initializeTinyMCE() {
    const searchRoot = document;

    // Set the theme attributes before the webcomponent initializes the editors
    searchRoot.querySelectorAll('tinymce-editor').forEach(editor => applyThemeAttributes(editor));

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

                // // Step 3: Create a completely new webcomponent by cloning the original
                // console.log('TinyMCE: Creating fresh clone of webcomponent');
                // const clone = editor.cloneNode(true);
                //
                // // Step 4: Replace the old element with the fresh clone
                // console.log('TinyMCE: Replacing old element with clone');
                // parent.replaceChild(clone, editor);

                // Step 5: The new element should auto-initialize via its connectedCallback
                // Wait a bit and inject custom styles
                setTimeout(() => {
                    console.log('TinyMCE: Checking clone status - _status =', editor._status);
                    injectTinyMCEStyles(editor);

                    // Step 6: Add change callback to update live component
                    setupLiveComponentCallback(editor);
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
    window.addEventListener('DOMContentLoaded', () => {
      // Give the DOM a moment to settle after AJAX update
      setTimeout(() => {
        initializeTinyMCE();
      }, 100);
    });
} else {
  // Give the DOM a moment to settle after AJAX update
  setTimeout(() => {
    initializeTinyMCE();
  }, 100);
}

/**
 * Follow the Sylius theme switcher (and the page builder, which forwards it to the form iframes)
 */
new MutationObserver(() => {
    document.querySelectorAll('tinymce-editor').forEach(editor => syncEditorTheme(editor));
}).observe(document.documentElement, {
    attributes: true,
    attributeFilter: ['data-bs-theme'],
});

/**
 * Reinitialize when loaded in the page builder or other AJAX contexts
 */
window.addEventListener('sylius-crud:dynamic:reload', () => {
    // Give the DOM a moment to settle after AJAX update
    setTimeout(() => {
        initializeTinyMCE();
    }, 100);
});
