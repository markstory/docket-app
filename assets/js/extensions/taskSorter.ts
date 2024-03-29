import htmx from 'htmx.org';
import Sortable from 'sortablejs';
import {SortableJsEvent} from 'app/types';

(function () {
  htmx.defineExtension('task-sorter', {
    onEvent: function (name, evt) {
      if (name !== 'htmx:afterProcessNode') {
        return;
      }
      const element = evt.target as HTMLElement;
      // Not sure why this happens, but it does.
      if (element.getAttribute('hx-ext') !== 'task-sorter') {
        return;
      }
      const putAttr = element.getAttribute('task-sorter-put');
      let group: Sortable.SortableOptions['group'] = 'tasks';
      if (putAttr === 'false') {
        group = {name: 'tasks', put: false};
      }

      // Implementing elements listen to the `end` event
      // triggered on this element and submits a form.
      new Sortable(element, {
        group: group,
        animation: 150,
        ghostClass: 'dnd-ghost',
        dragClass: 'dnd-item-dragging',
        handle: '.dnd-handle',
      });
      const orderAttr = element.getAttribute('task-sorter-attr');

      element.addEventListener('end', function (event: SortableJsEvent) {
        event.stopPropagation();
        const taskEl = event.item as HTMLElement;
        const toEl = event.to as HTMLElement;
        const newIndex = event.newIndex;
        const taskId = taskEl.getAttribute('data-id');

        let updateData: Record<string, string | number | undefined> = {};
        if (orderAttr) {
          updateData[orderAttr] = newIndex;
        }
        const sectionId = toEl.getAttribute('task-sorter-section');
        if (sectionId != null) {
          updateData.section_id = sectionId;
        }
        const evening = toEl.getAttribute('task-sorter-evening');
        if (evening != null) {
          updateData.evening = evening;
        }
        const dueon = toEl.getAttribute('task-sorter-dueon');
        if (dueon != null) {
          updateData.due_on = dueon;
        }

        // URL could be attribute driven if that makes sense
        // in the future.
        htmx.ajax('POST', `/tasks/${taskId}/move`, {
          target: 'main.main',
          swap: 'innerHTML',
          values: updateData,
        });
      } as EventListener);
    },
  });
})();
