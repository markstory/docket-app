import htmx from 'htmx.org';
import Sortable from 'sortablejs';

(function () {
  const sorters: Sortable[] = [];

  htmx.defineExtension('project-sorter', {
    onEvent: function (name, evt) {
      if (name !== 'htmx:afterProcessNode') {
        return;
      }
      const element = evt.target as HTMLElement;
      const sorter = new Sortable(element, {
        animation: 150,
        ghostClass: 'dnd-ghost',
        dragClass: 'dnd-item-dragging',
        handle: '.dnd-handle',
      });
      sorters.push(sorter);
    },
  });
})();
