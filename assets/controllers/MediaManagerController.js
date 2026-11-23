import { Controller } from '@hotwired/stimulus';

/**
 * Media manager page (`media.index`) with media.easy_media_manager.enabled: easy-media-manager mounted in the element.
 */
export default class extends Controller {
  async connect() {
    const { getEasyMedia } = await import('../easy-media/easy-media.js');
    getEasyMedia().mount({ target: this.element });
  }
}
