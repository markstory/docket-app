import htmx from 'htmx.org';
import Sortable from 'sortablejs';

(function () {
  htmx.registerExtension('hx-project-sorter', {
    htmx_after_process: function (element: HTMLElement, _detail) {
      for (const item in element.querySelectorAll('[hx-project-sorter]')) {
        // Implementing elements listen to the `end` event
        // triggered on this element and submits a form.
        new Sortable(item, {
          animation: 150,
          ghostClass: 'dnd-ghost',
          dragClass: 'dnd-item-dragging',
          handle: '.dnd-handle',
        });
      }
    },
  });
})();
