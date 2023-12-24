import htmx from 'htmx.org';
import Sortable from 'sortablejs';

(function () {
  htmx.defineExtension('project-sorter', {
    onEvent: function (name, evt) {
      if (name !== 'htmx:afterProcessNode') {
        return;
      }
      const element = evt.target as HTMLElement;
      // Implementing elements listen to the `end` event
      // triggered on this element and submits a form.
      new Sortable(element, {
        animation: 150,
        ghostClass: 'dnd-ghost',
        dragClass: 'dnd-item-dragging',
        handle: '.dnd-handle',
      });
    },
  });
})();
