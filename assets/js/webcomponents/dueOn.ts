import {formatCompactDate, parseDateInput, toDateString} from 'app/utils/dates';

type OpenEvent = {
  menu: HTMLElement;
};

// Copy paste from element/icons/moon16.php
const MOON_ICON =
  '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16"><path fill="currentColor" d="M9.598 1.591a.749.749 0 0 1 .785-.175a7.001 7.001 0 1 1-8.967 8.967a.75.75 0 0 1 .961-.96a5.5 5.5 0 0 0 7.046-7.046a.75.75 0 0 1 .175-.786Zm1.616 1.945a7 7 0 0 1-7.678 7.678a5.499 5.499 0 1 0 7.678-7.678Z"/></svg>';

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
    const eveningInput = this.querySelector('input:not([type="hidden"])[name="evening"]');
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

    const update = (dueon: string, evening: boolean) => {
      dueOnInput.value = dueon;
      dueOnString.value = dueon;
      eveningInput.checked = evening;

      // Update display state
      display.innerHTML = this.displayValue(dueon, evening);

      this.updateCalendar(menu, dueon);
    };

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

      let dueon = target.value;
      const dateVal  = parseDateInput(dueon);
      if (dateVal) {
        dueon = toDateString(dateVal);
      }
      let evening = eveningInput.checked;
      if (target.dataset.evening !== undefined) {
        evening = target.dataset.evening === '1' ? true : false;
      }
      dueon ??= dueOnInput.value;
      update(dueon, evening)

      menu.removeEventListener('click', handleSelection);
      dueOnString.removeEventListener('change', handleSelection);

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

    // TODO this isn't great as clicking an active icon
    // shouldn't change state.
    const eveningToggle = this.querySelector('.toggle-evening');
    if (eveningToggle) {
      eveningToggle.addEventListener('click', function () {
        eveningInput.checked = !eveningInput.checked;
        update(dueOnInput.value, eveningInput.checked);
      });
    }
  }

  updateCalendar(menu: Element | null, value: string | null) {
    if (!menu) {
      return;
    }
    const selected = menu.querySelectorAll('[aria-selected="true"]');
    for (const item of selected) {
      item.setAttribute('aria-selected', 'false');
    }

    const selection = menu.querySelector(`[value="${value}"]`);
    if (selection) {
      selection.setAttribute('aria-selected', 'true');
    }
  }

  displayValue(dueon: string, evening: boolean): string {
    let formatted = formatCompactDate(dueon);
    if (formatted === 'Today' && evening) {
      formatted = 'This evening';
    }
    let icon = '';
    if (evening) {
      icon = MOON_ICON;
    }
    return icon + formatted;
  }
}

customElements.define('due-on', DueOn);

export default DueOn;
