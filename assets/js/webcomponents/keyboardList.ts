const FOCUS_CLASS = 'keyboard-focus';

/**
 * Provide keyboard focus management for a list of items.
 *
 * The following keybindings will be created:
 *
 * - j and k move focus up and down through the list. Items
 *   are identified by the `itemselector` attribute.
 * - o triggers a click on `openselector`
 * - d triggers a click on `toggleselector`
 */
class KeyboardList extends HTMLElement {
  private focusIndex: number | undefined;
  private itemselector: string;
  private toggleselector: string;
  private openselector: string;

  constructor() {
    super();
    this.itemselector = this.getAttribute('itemselector') ?? '.row';
    this.toggleselector = this.getAttribute('toggleselector') ?? 'input[type="checkbox"]';
    this.openselector = this.getAttribute('openselector') ?? 'a';
  }

  connectedCallback() {
    window.addEventListener('keydown', this.handleKeydown);
  }

  disconnectedCallback() {
    window.removeEventListener('keydown', this.handleKeydown);
  }

  get items() {
    return this.querySelectorAll(this.itemselector);
  }

  get focusedItem(): Element | null {
    const focusIndex = this.focusIndex;
    if (focusIndex === undefined) {
      return null;
    }
    const items = this.items;
    if (items[focusIndex] !== undefined) {
      return items[focusIndex];
    }
    return null;
  }

  handleKeydown = (event: KeyboardEvent) => {
    if (event.target && event.target !== document.body) {
      return;
    }
    const currentFocus = this.focusedItem;
    if (currentFocus) {
      currentFocus.classList.remove(FOCUS_CLASS);
    }

    const maxIndex = this.items.length;
    const key = event.key.toLowerCase();
    switch (key) {
      case 'j':
        if (this.focusIndex === undefined) {
          this.focusIndex = 0;
        } else {
          this.focusIndex++;
        }
        break;
      case 'k':
        if (this.focusIndex === undefined) {
          return;
        }
        this.focusIndex--;
        break;
      case 'o':
        this.triggerOpen();
        break;
      case 'x':
        this.triggerToggle();
        break;
    }
    if (this.focusIndex !== undefined) {
      this.focusIndex = Math.min(this.focusIndex, maxIndex - 1);
      this.focusIndex = Math.max(0, this.focusIndex);
    }
    this.updateFocus();
  };

  triggerOpen() {
    const focused = this.focusedItem;
    if (!focused) {
      return;
    }
    const opener = focused.querySelector(this.openselector);
    if (!opener || !(opener instanceof HTMLElement)) {
      return;
    }
    opener.click();
  }

  triggerToggle() {
    const focused = this.focusedItem;
    if (!focused) {
      return;
    }
    const toggle = focused.querySelector(this.toggleselector);
    if (!toggle || !(toggle instanceof HTMLElement)) {
      return;
    }
    toggle.click();
  }

  updateFocus() {
    const focusIndex = this.focusIndex;
    if (focusIndex === undefined) {
      return;
    }
    const items = this.items;
    if (items[focusIndex] !== undefined) {
      items[focusIndex].classList.add('keyboard-focus');
    }
  }
}

customElements.define('keyboard-list', KeyboardList);

export default KeyboardList;
