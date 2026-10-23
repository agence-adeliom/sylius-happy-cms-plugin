import { Controller } from '@hotwired/stimulus';
import { getComponent } from '@symfony/ux-live-component';
import InfiniteTree from 'infinite-tree';

/**
 * @property {HTMLDivElement} element
 * @property {HTMLDivElement} itemPrototypTarget
 * @property {HTMLDivElement} treeTarget
 */

export default class extends Controller {
  static values = {
    treeData: Array,
    autoOpen: Boolean,
  };

  static targets = ['tree', 'itemPrototyp'];

  async initialize() {
    this.component = await getComponent(this.element);
    this.tree = this.createInfiniteTree();

    this.component.on('render:finished', () => {
      this.tree.loadData(this.treeDataValue);
    });
  }

  createInfiniteTree() {
    return new InfiniteTree({
      el: this.treeTarget,
      data: this.treeDataValue,
      autoOpen: this.autoOpenValue,
      selectable: false,
      rowRenderer: (node, treeOptions) => this.rowRenderer(node, treeOptions),
    });
  }

  rowRenderer(node) {
    const {
      id, name, children, state,
    } = node;
    const {
      depth, open, path, total,
    } = state;
    const more = node.hasChildren();
    const nodeMargin = 20;
    const rtl = document.querySelector('[dir="rtl"]');

    const itemPrototyp = this.itemPrototypTarget.firstElementChild.cloneNode(true);
    const togglerPrototyp = itemPrototyp.querySelector('[data-infinite-tree-toggler]');

    // eslint-disable-next-line no-unused-expressions
    rtl
      ? itemPrototyp.style.marginRight = `${(depth * nodeMargin)}px`
      : itemPrototyp.style.marginLeft = `${(depth * nodeMargin)}px`;

    itemPrototyp.setAttribute('data-id', id);
    itemPrototyp.setAttribute('data-expanded', more && open);
    itemPrototyp.setAttribute('data-depth', depth);
    itemPrototyp.setAttribute('data-path', path);
    itemPrototyp.setAttribute('data-children', children.length);
    itemPrototyp.setAttribute('data-total', total);

    togglerPrototyp.style.width = `${nodeMargin}px`;
    if (more) {
      togglerPrototyp.classList.add(open ? 'infinite-tree-open' : 'infinite-tree-closed');
    } else {
      togglerPrototyp.classList.add('infinite-tree-leaf');
    }

    // Placeholders are also injected into attributes (title, test attribute), so quotes must be escaped too.
    const escapeHtml = (value) => String(value)
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#39;');

    return itemPrototyp.outerHTML
      .replaceAll('__PAGE_ID__', escapeHtml(id))
      .replaceAll('__PAGE_NAME__', escapeHtml(name));
  }
}
