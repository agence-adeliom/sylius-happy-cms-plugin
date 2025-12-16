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

    // Listen for block:saved event from Live Component (for toolbar actions)
    this.element.addEventListener('block:saved', this.onBlockSaved.bind(this));

    // Listen for form:saved event from BlockEditorForm component
    this.element.addEventListener('form:saved', this.onFormSaved.bind(this));

    // Listen for custom editBlock event from the page builder script
    this.element.addEventListener('block-editor:edit', this.onEditBlock.bind(this));
  }

  disconnect() {
    this.element.removeEventListener('block:saved', this.onBlockSaved.bind(this));
    this.element.removeEventListener('form:saved', this.onFormSaved.bind(this));
    this.element.removeEventListener('block-editor:edit', this.onEditBlock.bind(this));
  }

  /**
   * Handle custom editBlock event from page builder script
   */
  async onEditBlock(event) {
    console.log('Edit block event received');
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
   * Handle block:saved event from Live Component (for toolbar actions like publish, move, delete)
   */
  onBlockSaved(event) {
    console.log('Block saved event received from toolbar:', event.detail);

    const { blockId } = event.detail;

    console.log('Reloading iframe to show updated content');
    document.querySelector('[data-page-builder-target="iframe"]').contentWindow.location.reload();

    // Dispatch custom event for other parts of the page to listen
    this.dispatch('blockSaved', { detail: { blockId } });
  }

  /**
   * Handle form:saved event from BlockEditorForm component
   */
  onFormSaved(event) {
    console.log('Form saved event received from BlockEditorForm:', event.detail);

    const { blockId } = event.detail;

    console.log('Reloading iframe to show updated content');
    document.querySelector('[data-page-builder-target="iframe"]').contentWindow.location.reload();

    // Dispatch custom event for other parts of the page to listen
    this.dispatch('blockSaved', { detail: { blockId } });
  }

  onRenderFinished(event) {
    console.log('Block editor rendered');

    const blockId = this.blockIdValue;

    // Only dispatch if we have a valid blockId (form is loaded)
    if (blockId) {
      console.log('Dispatching on-load-builder event for block:', blockId);

      // Dispatch custom event with blockId and element scope
      const customEvent = new CustomEvent('on-load-builder', {
        detail: {
          blockId: blockId,
          scope: this.element,
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
        }
      }));
    }
  }
}
