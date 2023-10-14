class SelectBox extends HTMLElement {
  private val: string | null;

  static get observedAttributes() {
    return ['val'];
  }

  constructor() {
    super();
    this.val = '';
  }

  attributeChangedCallback(property: string, oldValue: string, newValue: string) {
    if (oldValue === newValue) {
      return;
    }
    // @ts-ignore-next-line
    this[property] = newValue;
  }

  connectedCallback() {
    const menu = this.querySelector('select-box-menu') as SelectBoxMenu;
    if (!menu) {
      console.error('Missing required select-box-menu element');
      return;
    }
    const hidden = this.querySelector('input[type="hidden"]') as HTMLInputElement;
    if (!hidden) {
      console.error('Missing required hidden input element');
      return;
    }

    const trigger = this.querySelector('select-box-current') as SelectBoxCurrent;
    if (!trigger) {
      console.error('Missing required child select-box-current');
      return;
    }

    // Handle clicks outside the root parent element.
    function hideMenu() {
      // Remove this listener
      document.removeEventListener('click', hideMenu);

      menu.style.display = 'none';
    }

    trigger.addEventListener('click', function (evt) {
      evt.preventDefault();
      evt.stopPropagation();

      // Show menu
      menu.style.display = 'block';

      // Setup hide handler
      document.addEventListener('click', hideMenu);
    });

    menu.addEventListener(
      'click',
      function (evt) {
        evt.stopPropagation();
      },
      false
    );

    menu.addEventListener('selected', ((evt: CustomEvent) => {
      hidden.value = evt.detail;
      menu.setAttribute('val', evt.detail);
      trigger.setAttribute('val', evt.detail);
      this.setAttribute('val', evt.detail);
      hideMenu();
      // TODO clone selected option into current value.
    }) as EventListener);

    const value = this.getAttribute('val') ?? '';
    menu.setAttribute('val', value);
    trigger.setAttribute('val', value);
  }
}

class SelectBoxOption extends HTMLElement {
  private selected: boolean;

  static get observedAttributes() {
    return ['selected'];
  }

  constructor() {
    super();
    this.selected = false;
  }

  attributeChangedCallback(property: string, oldValue: string, newValue: string) {
    if (oldValue === newValue) {
      return;
    }
    // @ts-ignore-next-line
    this[property] = newValue;
    if (property === 'selected') {
      this.setAttribute('aria-selected', newValue);
    }
  }

  connectedCallback() {
    this.addEventListener('click', function (evt) {
      evt.preventDefault();
      evt.stopPropagation();

      const selected = new CustomEvent('selected', {
        bubbles: true,
        cancelable: false,
        detail: this.getAttribute('value'),
      });
      this.dispatchEvent(selected);
    });

    this.setAttribute('aria-selected', this.selected ? 'true' : 'false');
  }
}

class SelectBoxMenu extends HTMLElement {
  private val: string | null;

  static get observedAttributes() {
    return ['val'];
  }
  constructor() {
    super();
    this.val = '';
  }

  attributeChangedCallback(property: string, oldValue: string, newValue: string) {
    if (oldValue === newValue) {
      return;
    }
    if (property === 'val') {
      this.val = newValue;
      this.updateSelected();
    }
  }

  updateSelected() {
    const active = this.querySelector('select-box-option[selected="true"]');
    if (active) {
      active.setAttribute('selected', 'false');
    }
    const option = this.querySelector(`select-box-option[value="${this.val}"]`);
    if (option) {
      option.setAttribute('selected', 'true');
    }
  }
}

class SelectBoxCurrent extends HTMLElement {
  private val: string | null;

  static get observedAttributes() {
    return ['val'];
  }
  constructor() {
    super();
    this.val = '';
  }

  attributeChangedCallback(property: string, oldValue: string, newValue: string) {
    if (oldValue === newValue) {
      return;
    }
    if (property === 'val') {
      this.val = newValue;
      this.updateSelected();
    }
  }

  connectedCallback() {
    this.updateSelected();
  }

  updateSelected() {
    const parent = this.parentNode;
    if (!parent) {
      return;
    }
    if (!this.val) {
      return;
    }
    const menu = parent.querySelector('select-box-menu') as HTMLElement;
    const selected = menu.querySelector('[selected="true"]');
    if (!selected) {
      return;
    }
    // TODO make this display innerHTML instead.
    const text = selected.textContent ?? '';
    this.querySelector('input')!.value = text;
  }
}

customElements.define('select-box', SelectBox);
customElements.define('select-box-menu', SelectBoxMenu);
customElements.define('select-box-option', SelectBoxOption);
customElements.define('select-box-current', SelectBoxCurrent);

export default SelectBox;
