import {formatCompactDate, parseDateInput, toDateString} from 'app/utils/dates';

type OpenEvent = {
  menu: HTMLElement;
};

/**
 * Provides logic to update a set of hidden inputs
 * based on button clicks in the menu
 */
class DueOn extends HTMLElement {
  connectedCallback() {
    const menu = this.querySelector('[role="menu"]');
    if (!menu) {
      console.error('Missing role=menu element');
      return;
    }
    const dueOnInput = this.querySelector('input[name="due_on"]');
    const eveningInput = this.querySelector('input[name="evening"]');
    const dueOnString = this.querySelector('input[name="due_on_string"]');
    if (
      !(dueOnInput instanceof HTMLInputElement) ||
      !(eveningInput instanceof HTMLInputElement) ||
      !(dueOnString instanceof HTMLInputElement)
    ) {
      console.error('Missing an input for due_on or evening');
      return;
    }
    const display = this.querySelector('[data-dueon-display]');
    if (!display) {
      console.error('Missing data-dueon-display element');
      return;
    }

    // Listen for button clicks
    const handleSelection = (event: Event) => {
      const target = event.target;

      const accept =
        (target instanceof HTMLButtonElement && event.type === 'click') ||
        (target instanceof HTMLInputElement && event.type === 'change');

      if (!accept) {
        return;
      }
      event.preventDefault();
      event.stopPropagation();

      // Update inputs in form.
      let dueon = target.getAttribute('value');
      if (target instanceof HTMLInputElement) {
        const dateVal = parseDateInput(target.value);
        if (dateVal) {
          dueon = toDateString(dateVal);
        }
      }

      const evening = target.dataset.evening;
      dueon ??= dueOnInput.value;
      dueOnInput.value = dueon;
      eveningInput.value = evening ?? eveningInput.value;

      // Update display state
      display.textContent = formatCompactDate(dueon);
      dueOnString.value = dueon;

      this.updateCalendar(menu, dueon);

      // Close the dropdown.
      const close = new CustomEvent('close', {
        bubbles: true,
        cancelable: true,
      });
      document.dispatchEvent(close);
    };

    this.addEventListener('open', ((event: CustomEvent<OpenEvent>) => {
      const menu = event.detail.menu;
      if (!menu) {
        return;
      }
      menu.addEventListener('click', handleSelection);
      dueOnString.addEventListener('change', handleSelection);
      dueOnString.focus();
    }) as EventListener);
  }

  updateCalendar(menu: Element | null, value: string | null) {
    if (!menu) {
      return;
    }
    const selected = menu.querySelector('[aria-selected]');
    if (selected) {
      selected.removeAttribute('aria-selected');
    }

    const selection = menu.querySelector(`[value="${value}"]`);
    if (selection) {
      selection.setAttribute('aria-selected', 'true');
    }
  }
}

customElements.define('due-on', DueOn);

export default DueOn;
