class SelectBox extends HTMLElement {
  static get observedAttributes() {
    return ['val'];
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

    // Set the initial value
    const value = this.getAttribute('val') ?? '';
    menu.setAttribute('val', value);
    trigger.setAttribute('val', value);

    // Handle clicks outside the root parent element.
    function hideMenu() {
      // Remove this listener
      document.removeEventListener('click', hideMenu);

      menu.style.display = 'none';
      trigger.setAttribute('open', 'false');
    }

    // Open the menu
    trigger.addEventListener('click', function (evt) {
      evt.preventDefault();
      evt.stopPropagation();

      // Show menu
      menu.style.display = 'block';
      trigger.setAttribute('open', 'true');

      // Setup hide handler
      document.addEventListener('click', hideMenu);
    });

    // Swallow clicks to the menu
    menu.addEventListener(
      'click',
      function (evt) {
        evt.stopPropagation();
      },
      false
    );

    // Update values when an option is selected.
    menu.addEventListener('selected', ((evt: CustomEvent) => {
      hidden.value = evt.detail;
      menu.setAttribute('val', evt.detail);
      trigger.setAttribute('val', evt.detail);
      this.setAttribute('val', evt.detail);
      hideMenu();
      // TODO clone selected option into current value.
    }) as EventListener);

    trigger.addEventListener('keyup', evt => {
      const target = evt.target;
      if (target instanceof HTMLInputElement) {
        menu.setAttribute('filter', target.value);
      }
    });
  }
}

class SelectBoxOption extends HTMLElement {
  static get observedAttributes() {
    return ['selected'];
  }

  attributeChangedCallback(property: string, oldValue: string, newValue: string) {
    if (oldValue === newValue) {
      return;
    }
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

    this.setAttribute('aria-selected', this.getAttribute('selected') ?? 'false');
  }
}

class SelectBoxMenu extends HTMLElement {
  static get observedAttributes() {
    return ['val', 'filter', 'focused'];
  }
  attributeChangedCallback(property: string, oldValue: string, newValue: string) {
    if (oldValue === newValue) {
      return;
    }
    if (property === 'val') {
      this.updateSelected();
    }
    if (property === 'filter') {
      this.filterOptions();
    }
  }

  updateSelected() {
    const active = this.querySelector('select-box-option[selected="true"]');
    if (active) {
      active.setAttribute('selected', 'false');
    }
    const val = this.getAttribute('val');
    const option = this.querySelector(`select-box-option[value="${val}"]`);
    if (option) {
      option.setAttribute('selected', 'true');
    }
  }

  filterOptions() {
    const menuOptions: NodeListOf<SelectBoxOption> =
      this.querySelectorAll('select-box-option');
    const filter = this.getAttribute('filter');
    for (var option of menuOptions) {
      if (!filter) {
        option.style.display = 'flex';
      } else if (option.innerText.includes(filter)) {
        option.style.display = 'flex';
      } else {
        option.style.display = 'none';
      }
    }
  }
}

class SelectBoxCurrent extends HTMLElement {
  static get observedAttributes() {
    return ['val', 'open'];
  }
  attributeChangedCallback(property: string, oldValue: string, newValue: string) {
    if (oldValue === newValue) {
      return;
    }
    if (property === 'val') {
      this.updateSelected();
    }
    if (property === 'open') {
      if (newValue === 'true') {
        const input = this.querySelector('input') as HTMLInputElement;
        input.value = '';
        input.focus();

        const keyup = new CustomEvent('keyup', {bubbles: true});
        input.dispatchEvent(keyup);
      }
    }
  }

  connectedCallback() {
    this.updateSelected();
    // TODO consider using shadowdom with a link element
    // to the application CSS file. How to get that file path is unknown.
  }

  updateSelected() {
    const parent = this.parentNode;
    if (!parent) {
      return;
    }
    const val = this.getAttribute('val');
    if (!val) {
      return;
    }
    const menu = parent.querySelector('select-box-menu') as HTMLElement;
    const selected = menu.querySelector('[selected="true"]');
    if (!selected) {
      return;
    }
    const content = selected.innerHTML ?? '';
    this.querySelector('.select-box-value')!.innerHTML = content;
  }
}

customElements.define('select-box', SelectBox);
customElements.define('select-box-menu', SelectBoxMenu);
customElements.define('select-box-option', SelectBoxOption);
customElements.define('select-box-current', SelectBoxCurrent);

export default SelectBox;
