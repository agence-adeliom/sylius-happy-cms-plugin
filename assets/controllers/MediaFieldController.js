import { Controller } from '@hotwired/stimulus';

function formatSize(bytes) {
  if (!bytes) {
    return '';
  }
  const units = ['B', 'KB', 'MB', 'GB'];
  const power = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1);

  return `${(bytes / 1024 ** power).toFixed(power === 0 ? 0 : 1)} ${units[power]}`;
}

/**
 * Media field (`MediaType`) with media.easy_media_manager.enabled: the hidden input holds the id of the media chosen
 * in easy-media-manager.
 *
 * A choice or a removal dispatches `easy-media-field:change` on the field (bubbling), with `detail.file` (the chosen
 * file, null when removed) and `detail.input` (the hidden input), for the project scripts that follow the field.
 */
export default class extends Controller {
  static targets = ['input', 'preview', 'name', 'size', 'clear'];

  static values = {
    uploadTypes: Array,
    uploadSize: Number,
    path: String,
    features: Object,
    emptyLabel: String,
  };

  async connect() {
    // Library and styles loaded as soon as a media field is displayed.
    this.easyMedia = import('../easy-media/easy-media.js').then(({ getEasyMedia }) => getEasyMedia());
    const id = this.inputTarget.value;
    if (id) {
      const easyMedia = await this.easyMedia;
      // Media deleted since: the id stays displayed until the field is cleared.
      this.render((await easyMedia.getFile(id)) ?? { id, name: `#${id}`, type: '', size: 0, path: '' });
    }
  }

  async pick() {
    const easyMedia = await this.easyMedia;
    const file = await easyMedia.pick({
      restrictions: {
        types: this.uploadTypesValue.length > 0 ? this.uploadTypesValue : null,
        path: this.pathValue || null,
        // restrictions_uploadSize is in MB, the library expects bytes.
        uploadSize: this.uploadSizeValue ? this.uploadSizeValue * 1024 * 1024 : null,
      },
      features: this.featuresValue,
    });
    if (!file || file.type === 'folder') {
      return;
    }

    this.select(file);
  }

  clear() {
    this.select(null);
  }

  select(file) {
    this.inputTarget.value = file ? file.id : '';
    this.inputTarget.dispatchEvent(new Event('change', { bubbles: true }));
    this.render(file);
    this.dispatch('change', { prefix: 'easy-media-field', detail: { file, input: this.inputTarget } });
  }

  render(file) {
    this.previewTarget.replaceChildren();
    if (file?.type?.startsWith('image/') && file.path) {
      const image = document.createElement('img');
      image.src = file.path;
      image.alt = file.metas?.alt ?? '';
      image.loading = 'lazy';
      this.previewTarget.append(image);
    }
    this.nameTarget.textContent = file ? file.name : this.emptyLabelValue;
    this.sizeTarget.textContent = file ? formatSize(file.size) : '';
    this.clearTarget.hidden = !file;
  }
}
