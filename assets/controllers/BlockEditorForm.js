import { Controller } from '@hotwired/stimulus';
import { getComponent } from '@symfony/ux-live-component';

export default class extends Controller {
  static values = {
    blockId: Number
  };

  async initialize() {
    console.log('BlockEditorForm controller initialize');
    this.component = await getComponent(this.element);
  }

  connect() {
    console.log('BlockEditorForm controller connected');

    // Listen for block:saved event from Live Component
    this.element.addEventListener('block:saved', this.onBlockSaved.bind(this));

    // Listen for Live Component render finished event to dispatch form loaded event
    this.element.addEventListener('live:render:finished', this.onRenderFinished.bind(this));

    // Trigger form loaded event on initial load
    // Use requestAnimationFrame to ensure DOM is fully ready
    requestAnimationFrame(() => {
      this.onRenderFinished();
    });
  }

  disconnect() {
    this.element.removeEventListener('block:saved', this.onBlockSaved.bind(this));
    this.element.removeEventListener('live:render:finished', this.onRenderFinished.bind(this));
  }

  /**
   * Handle block:saved event from Live Component
   */
  onBlockSaved(event) {
    console.log('Block saved event received in form:', event.detail);

    // Bubble the event up to the parent component
    this.element.dispatchEvent(new CustomEvent('form:saved', {
      detail: event.detail,
      bubbles: true
    }));
  }

  /**
   * Handle Live Component render finished event
   * Dispatches a custom event when the form is loaded/updated
   * This allows block-specific JavaScript to initialize with the correct scope
   */
  onRenderFinished(event) {
    console.log('Block editor form rendered');

    const blockId = this.blockIdValue;

    // Only dispatch if we have a valid blockId (form is loaded)
    if (blockId) {
      console.log('Dispatching sylius-crud:dynamic:reload event');

      // Also dispatch on window for global listeners
      window.dispatchEvent(new CustomEvent('sylius-crud:dynamic:reload', {
        detail: {
          blockId: blockId,
          scope: this.element,
          formElement: this.element.querySelector('form')
        }
      }));
    }
  }
}
