import { Controller } from '@hotwired/stimulus';
import { getComponent } from '@symfony/ux-live-component';

export default class extends Controller {
  static targets = ['liveComponent', 'iframe'];
  static values = {
    blockId: Number
  };

  async initialize() {
    console.log('BlockEditor controller initialize');
    this.component = await getComponent(this.element);
  }

  connect() {
    console.log('BlockEditor controller connected');

    // Listen for block:saved event from Live Component
    this.element.addEventListener('block:saved', this.onBlockSaved.bind(this));

    // Listen for custom editBlock event from the page builder script
    this.element.addEventListener('block-editor:edit', this.onEditBlock.bind(this));

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
    this.element.removeEventListener('block-editor:edit', this.onEditBlock.bind(this));
    this.element.removeEventListener('live:render:finished', this.onRenderFinished.bind(this));
  }

  /**
   * Handle custom editBlock event from page builder script
   */
  async onEditBlock(event) {
    console.log('dsds');
    const { blockId } = event.detail;
    await this.setBlockId(blockId);
    this.onRenderFinished();
  }

  /**
   * Update the Live Component blockId property
   * Called from the page builder script when editing a block
   */
  async setBlockId(blockId) {

    try {
      await this.component.action('changeBlock', { blockId: blockId ? parseInt(blockId) : null} );
      console.log('Block ID updated in Live Component:', blockId);
    } catch (error) {
      console.error('Error setting block ID:', error);
    }
  }

  /**
   * Handle block:saved event from Live Component
   */
  onBlockSaved(event) {
    console.log('Block saved event received:', event.detail);

    const { blockId } = event.detail;

    console.log('Reloading iframe to show updated content');
    document.querySelector('[data-page-builder-target="iframe"]').contentWindow.location.reload();

    // Dispatch custom event for other parts of the page to listen
    this.dispatch('blockSaved', { detail: { blockId } });
  }

  /**
   * Handle Live Component render finished event
   * Dispatches a custom event when the form is loaded/updated
   * This allows block-specific JavaScript to initialize with the correct scope
   *
   * USAGE IN BLOCK-SPECIFIC SCRIPTS:
   *
   * // Listen for the form loaded event on window
   * window.addEventListener('on-load-builder', (event) => {
   *   const { blockId, scope, formElement } = event.detail;
   *
   *   // Example: Initialize a date picker within the form scope
   *   const dateInputs = scope.querySelectorAll('.datepicker');
   *   dateInputs.forEach(input => {
   *     // Initialize your date picker library here
   *   });
   *
   *   // Example: Initialize a rich text editor for a specific field
   *   const textArea = formElement.querySelector('textarea.wysiwyg');
   *   if (textArea) {
   *     // Initialize your editor here
   *   }
   * });
   *
   * // Or listen for a specific block type by checking blockId
   * window.addEventListener('on-load-builder', (event) => {
   *   const { blockId, scope } = event.detail;
   *
   *   // Only initialize for specific block type
   *   const blockTypeField = scope.querySelector('[name*="[type]"]');
   *   if (blockTypeField && blockTypeField.value === 'my_custom_block') {
   *     // Initialize your custom block's JavaScript here
   *   }
   * });
   */
  onRenderFinished(event) {
    console.log('Block editor form rendered');

    const blockId = this.blockIdValue;

    // Only dispatch if we have a valid blockId (form is loaded)
    if (blockId) {
      console.log('Dispatching on-load-builder event for block:', blockId);

      // Dispatch custom event with blockId and element scope
      const customEvent = new CustomEvent('on-load-builder', {
        detail: {
          blockId: blockId,
          scope: this.element,
          formElement: this.element.querySelector('form')
        },
        bubbles: true,
        cancelable: false
      });

      this.element.dispatchEvent(customEvent);

      // Also dispatch on window for global listeners
      window.dispatchEvent(new CustomEvent('on-load-builder', {
        detail: {
          blockId: blockId,
          scope: this.element,
          formElement: this.element.querySelector('form')
        }
      }));
    }
  }
}
