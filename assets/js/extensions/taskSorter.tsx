import htmx from 'htmx.org';
import Sortable from 'sortablejs';

(function () {
  htmx.defineExtension('task-sorter', {
    onEvent: function (name, evt) {
      if (name !== 'htmx:afterProcessNode') {
        return;
      }
      const element = evt.target as HTMLElement;
      // Implementing elements listen to the `end` event
      // triggered on this element and submits a form.
      new Sortable(element, {
        group: 'tasks',
        animation: 150,
        ghostClass: 'dnd-ghost',
        dragClass: 'dnd-item-dragging',
        handle: '.dnd-handle',
      });
      const orderAttr = element.getAttribute('task-sorter-attr');
      if (orderAttr === null) {
        throw new Error('Missing required parameter task-sorter-attr');
      }

      element.addEventListener('end', function (event) {
        const taskEl = event.item as HTMLElement;
        const toEl = event.to as HTMLElement;
        const newIndex = event.newIndex;
        const taskId = taskEl.getAttribute('data-id');

        const updateData = {
          [orderAttr]: newIndex,
        };
        const sectionId = toEl.getAttribute('task-sorter-section');
        if (sectionId != null) {
          updateData.section_id = sectionId;
        }
        console.log('update data', updateData);

        // URL could be attribute driven if that makes sense
        // in the future.
        htmx.ajax('POST', `/tasks/${taskId}/move`, {
          target: 'main.main',
          swap: 'innerHTML',
          values: updateData,
        });
      });
    },
  });
})();
