import { Controller } from '@hotwired/stimulus';
import { getComponent } from '@symfony/ux-live-component';

export default class extends Controller {
  static targets = ['liveComponent', 'iframe'];

  async initialize() {
    console.log('BlockEditor controller initialize');
    this.component = await getComponent(this.element);
  }

  connect() {
    console.log('BlockEditor controller connected');

    console.log(this.element);

    // Listen for block:saved event from Live Component
    this.element.addEventListener('block:saved', this.onBlockSaved.bind(this));

    // Listen for custom editBlock event from the page builder script
    this.element.addEventListener('block-editor:edit', this.onEditBlock.bind(this));
  }

  disconnect() {
    this.element.removeEventListener('block:saved', this.onBlockSaved.bind(this));
    this.element.removeEventListener('block-editor:edit', this.onEditBlock.bind(this));
  }

  /**
   * Handle custom editBlock event from page builder script
   */
  async onEditBlock(event) {
    console.log('dsds');
    const { blockId } = event.detail;
    await this.setBlockId(blockId);
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
}
