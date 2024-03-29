type SelectedDetail = {
  value: string;
  htmlText: string;
};

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
    const hidden = this.querySelector('input') as HTMLInputElement;
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
    const hideMenu = () => {
      menu.style.display = 'none';
      trigger.setAttribute('open', 'false');
    };

    const openMenu = (evt: Event) => {
      evt.preventDefault();
      evt.stopPropagation();

      // Show menu
      menu.style.display = 'block';
      trigger.setAttribute('open', 'true');

      // Reset current keyboard focus
      this.currentOffset = -1;
      menu.removeAttribute('current');
    };

    const setValue = (value: string) => {
      if (value !== hidden.value) {
        hidden.value = value;
        const change = new Event('change');
        hidden.dispatchEvent(change);
      }
      menu.setAttribute('val', value);
      this.setAttribute('val', value);
    };

    const updateSelected = (val: string) => {
      const options = this.querySelectorAll('select-box-option');
      for (const option of options) {
        if (option.getAttribute('value') === val) {
          option.setAttribute('selected', 'selected');
        }
        if (option.getAttribute('selected') === 'selected') {
          const contents = option.innerHTML;
          trigger.setAttribute('selectedhtml', contents);
        }
      }
    };

    // Set the initial value
    const value = this.getAttribute('val') ?? '';
    setValue(value);
    updateSelected(value);

    // Open and close the menu
    this.addEventListener('open', evt => {
      openMenu(evt);
    });
    this.addEventListener('close', () => {
      hideMenu();
    });

    // Update values when an option is selected.
    menu.addEventListener('selected', ((evt: CustomEvent<SelectedDetail>) => {
      setValue(evt.detail.value);
      trigger.setAttribute('selectedhtml', evt.detail.htmlText);
      hideMenu();
    }) as EventListener);

    // Handle the menu signaling that our last operation
    // would go out of bounds.
    menu.addEventListener('outofbounds', ((evt: CustomEvent) => {
      this.currentOffset = Number(evt.detail);
      menu.setAttribute('current', this.currentOffset.toString());
    }) as EventListener);

    menu.addEventListener('changecurrent', ((evt: CustomEvent<number>) => {
      this.currentOffset = evt.detail;
    }) as EventListener);

    // Propagate filtering into menu updates;
    trigger.addEventListener('keyup', evt => {
      const target = evt.target;
      if (target instanceof HTMLInputElement) {
        menu.setAttribute('filter', target.value);
      }
    });

    // Handle keyboard nav in the menu.
    trigger.addEventListener('keydown', evt => {
      if (evt.key === 'ArrowDown') {
        // Down arrow, rely on outofbounds event to constrain the max.
        evt.preventDefault();
        this.currentOffset += 1;
        menu.setAttribute('current', this.currentOffset.toString());
      } else if (evt.key === 'ArrowUp') {
        // Moving up, constrain to 0 on the min
        evt.preventDefault();
        this.currentOffset = Math.max(this.currentOffset - 1, 0);
        menu.setAttribute('current', this.currentOffset.toString());
      } else if (evt.key === 'Enter') {
        evt.preventDefault();

        const currentOpt = menu.querySelector(
          'select-box-option[aria-current="true"]'
        ) as SelectBoxOption | null;
        if (currentOpt) {
          currentOpt.select();
        }
      }
    });
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
    const menuOptions: Array<SelectBoxOption> = Array.from(
      this.querySelectorAll('select-box-option')
    );
    let filter = this.getAttribute('filter');
    if (filter) {
      filter = filter.toLowerCase();
    }

    let firstIndex: number | undefined = undefined;
    for (let i = 0, len = menuOptions.length; i < len; i++) {
      const option = menuOptions[i];
      if (!filter || option.innerText.toLowerCase().includes(filter)) {
        option.removeAttribute('aria-hidden');
        if (firstIndex === undefined) {
          firstIndex = i;
        }
      } else {
        option.removeAttribute('aria-current');
        option.setAttribute('aria-hidden', 'true');
      }
    }

    if (firstIndex === undefined) {
      return;
    }
    const current = menuOptions[firstIndex];
    if (current) {
      current.setAttribute('aria-current', 'true');
      const changeCurrent = new CustomEvent('changecurrent', {
        bubbles: true,
        cancelable: true,
        detail: firstIndex,
      });
      this.dispatchEvent(changeCurrent);
    }
  }
}

class SelectBoxCurrent extends HTMLElement {
  static get observedAttributes() {
    return ['open', 'selectedhtml'];
  }
  attributeChangedCallback(property: string, oldValue: string, newValue: string) {
    if (oldValue === newValue) {
      return;
    }
    if (property === 'selectedhtml') {
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
    input.addEventListener('blur', () => {
      // Delay the blur to close as to let other events run their course.
      setTimeout(() => {
        const close = new CustomEvent('close', {
          bubbles: true,
          cancelable: true,
        });
        this.dispatchEvent(close);
      }, 200);
    });
    this.addEventListener('click', evt => {
      evt.preventDefault();
      evt.stopPropagation();
      const open = new CustomEvent('open', {
        bubbles: true,
        cancelable: true,
      });
      this.dispatchEvent(open);
    });
  }

  updateSelected() {
    const selectedhtml = this.getAttribute('selectedhtml');
    if (!selectedhtml) {
      return;
    }
    this.querySelector('.select-box-value')!.innerHTML = selectedhtml;
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

  select() {
    const selected = new CustomEvent<SelectedDetail>('selected', {
      bubbles: true,
      cancelable: false,
      detail: {
        value: this.getAttribute('value') ?? '',
        htmlText: this.innerHTML ?? '',
      },
    });
    this.dispatchEvent(selected);
  }

  connectedCallback() {
    this.addEventListener('click', evt => {
      evt.stopPropagation();
      this.select();
    });

    this.setAttribute('aria-selected', this.getAttribute('selected') ?? 'false');
  }
}

customElements.define('select-box', SelectBox);
customElements.define('select-box-menu', SelectBoxMenu);
customElements.define('select-box-current', SelectBoxCurrent);
customElements.define('select-box-option', SelectBoxOption);

export default SelectBox;
