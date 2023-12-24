import htmx from 'htmx.org';
import Sortable from 'sortablejs';
import {SortableJsEvent} from 'app/types';

(function () {
  htmx.defineExtension('subtask-sorter', {
    onEvent: function (name, evt) {
      if (name !== 'htmx:afterProcessNode') {
        return;
      }
      const element = evt.target as HTMLElement;
      if (element.getAttribute('hx-ext') !== 'subtask-sorter') {
        return;
      }

      new Sortable(element, {
        group: 'subtasks',
        animation: 150,
        ghostClass: 'dnd-ghost',
        dragClass: 'dnd-item-dragging',
        handle: '.dnd-handle',
      });

      element.addEventListener('end', function (event: SortableJsEvent) {
        event.stopPropagation();

        // Update the ranking of all items in the list
        const rankings = element.querySelectorAll('input[name*="ranking"]');
        for (const index in rankings) {
          const rankInput = rankings[index];
          if (rankInput instanceof HTMLInputElement) {
            rankInput.value = index;
          }
        }
      } as EventListener);
    },
  });
})();
