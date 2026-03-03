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

    // Listen for form:saved event from BlockEditorForm component
    this.element.addEventListener('block-editor:reload-requested', this.onReloadRequested.bind(this));

    // Listen for custom editBlock event from the page builder script
    this.element.addEventListener('block-editor:edit', this.onEditBlock.bind(this));
    this.element.addEventListener('block-editor:close', this.onCloseBlock.bind(this));
  }

  disconnect() {
    this.element.removeEventListener('block:saved', this.onBlockSaved.bind(this));
    this.element.removeEventListener('form:saved', this.onFormSaved.bind(this));
    this.element.removeEventListener('block-editor:edit', this.onEditBlock.bind(this));
    this.element.removeEventListener('block-editor:close', this.onCloseBlock.bind(this));
  }

  /**
   * Handle custom editBlock event from page builder script
   */
  async onEditBlock(event) {
    console.log('Edit block event received');
    const { blockId } = event.detail;
    await this.setBlockId(blockId);
  }

  /**
   * Handle custom editBlock event from page builder script
   */
  async onCloseBlock(event) {
    console.log('Close block event received');
    await this.component.action('cancel', {} );
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

  /**
   * Handle reload-requested event from BlockEditor component
   */
  onReloadRequested(event) {

    console.log('Reloading iframe to show updated content');
    window.location.reload();

  }
}
