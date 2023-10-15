class SelectBox extends HTMLElement {
  private currentOffset: number = -1;

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

    const openMenu = (evt: Event) => {
      evt.preventDefault();
      evt.stopPropagation();

      // Show menu
      menu.style.display = 'block';
      trigger.setAttribute('open', 'true');

      // Reset current keyboard focus
      this.currentOffset = -1;
      menu.removeAttribute('current');

      // Setup hide handler
      document.addEventListener('click', hideMenu);
    };

    const setValue = (value: string) => {
      hidden.value = value;
      menu.setAttribute('val', value);
      trigger.setAttribute('val', value);
      this.setAttribute('val', value);
    };

    // Update values when an option is selected.
    menu.addEventListener('selected', ((evt: CustomEvent) => {
      setValue(evt.detail);
      hideMenu();
    }) as EventListener);

    // Handle the menu signaling that our last operation
    // would go out of bounds.
    menu.addEventListener('outofbounds', ((evt: CustomEvent) => {
      this.currentOffset = Number(evt.detail);
      menu.setAttribute('current', this.currentOffset.toString());
    }) as EventListener);

    // Open the menu
    trigger.addEventListener('click', evt => {
      openMenu(evt);
    });
    trigger.addEventListener('open', evt => {
      openMenu(evt);
    });
    // Propagate filtering into menu updates;
    trigger.addEventListener('keyup', evt => {
      const target = evt.target;
      if (target instanceof HTMLInputElement) {
        menu.setAttribute('filter', target.value);
      }
    });

    trigger.addEventListener('keydown', evt => {
      // Down arrow
      if (evt.key === 'ArrowDown') {
        evt.preventDefault();
        this.currentOffset += 1;
        menu.setAttribute('current', this.currentOffset.toString());
      } else if (evt.key === 'ArrowUp') {
        evt.preventDefault();
        this.currentOffset = Math.max(this.currentOffset - 1, 0);
        menu.setAttribute('current', this.currentOffset.toString());
      } else if (evt.key === 'Enter') {
        evt.preventDefault();

        const currentOpt = menu.querySelector('select-box-option[aria-current="true"]');
        if (currentOpt) {
          setValue(currentOpt.getAttribute('value') ?? '');
          hideMenu();
        }
        return false;
      }
      return;
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
    return ['val', 'filter', 'current'];
  }
  connectedCallback() {
    // Swallow clicks to the menu
    this.addEventListener(
      'click',
      function (evt) {
        evt.stopPropagation();
      },
      false
    );
  }

  attributeChangedCallback(property: string, oldValue: string, newValue: string) {
    if (oldValue === newValue) {
      return;
    }
    if (property === 'filter') {
      this.filterOptions();
    }
    if (property === 'val' || property === 'current') {
      this.updateSelected();
    }
  }

  updateSelected() {
    const active = this.querySelector('select-box-option[selected="true"]');
    if (active) {
      active.setAttribute('selected', 'false');
    }
    const val = this.getAttribute('val');
    const selected = this.querySelector(`select-box-option[value="${val}"]`);
    if (selected) {
      selected.setAttribute('selected', 'true');
    }

    // Clear the current focused element.
    const current = Number(this.getAttribute('current') ?? '-1');
    if (isNaN(current)) {
      return;
    }
    const currentOpt = this.querySelector('select-box-option[aria-current="true"]');
    if (currentOpt) {
      currentOpt.removeAttribute('aria-current');
    }
    const options = this.querySelectorAll('select-box-option:not([aria-hidden="true"])');
    if (options[current] !== undefined) {
      options[current].setAttribute('aria-current', 'true');
    } else {
      let target = 0;
      if (current >= options.length) {
        target = options.length - 1;
      }
      var outofbounds = new CustomEvent('outofbounds', {
        bubbles: true,
        cancelable: true,
        detail: target.toString(),
      });
      this.dispatchEvent(outofbounds);
    }
  }

  filterOptions() {
    const menuOptions: NodeListOf<SelectBoxOption> =
      this.querySelectorAll('select-box-option');
    const filter = this.getAttribute('filter');

    for (var option of menuOptions) {
      if (!filter || option.innerText.includes(filter)) {
        option.removeAttribute('aria-hidden');
      } else {
        option.setAttribute('aria-hidden', 'true');
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
    const input = this.querySelector('input[type="text"]');
    if (!input) {
      console.error('Missing required element input');
      return;
    }
    input.addEventListener('focus', () => {
      const open = new CustomEvent('open', {
        bubbles: true,
        cancelable: true,
      });
      this.dispatchEvent(open);
    });

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
