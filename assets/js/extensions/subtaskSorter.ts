import htmx from 'htmx.org';
import Sortable from 'sortablejs';
import {SortableJsEvent} from 'app/types';

(function () {
  function processItem(element: HTMLElement) {
    new Sortable(element, {
      group: 'subtasks',
      animation: 150,
      ghostClass: 'dnd-ghost',
      dragClass: 'dnd-item-dragging',
      handle: '.dnd-handle',
    });

    element.addEventListener('end', function (event: SortableJsEvent) {
      event.stopPropagation();

      // Update state in each item as the name attribute indicies and values matter
      const items = element.querySelectorAll('li');
      let index = -1;
      for (const item of items) {
        index++;
        const ranking = item.querySelector('input[name*="ranking"]');
        if (ranking && ranking instanceof HTMLInputElement) {
          ranking.value = String(index);
        }
        // Update array indices as they matter
        const inputs = item.querySelectorAll('input[name*="subtasks"]');
        for (const input of inputs) {
          let updated = input.getAttribute('name');
          if (updated) {
            updated = updated.replace(/\d+/, String(index));
            input.setAttribute('name', updated);
          }
        }
      }
    } as EventListener);
  }

  htmx.registerExtension('hx-subtask-sorter', {
    htmx_after_process: function (element: HTMLElement, _detail) {
      for (const item of element.querySelectorAll('[hx-subtask-sorter]')) {
        processItem(item);
      }
    },
  });
})();
