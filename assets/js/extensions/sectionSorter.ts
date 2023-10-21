import htmx from 'htmx.org';
import Sortable from 'sortablejs';
import {SortableJsEvent} from 'app/types';

(function () {
  htmx.defineExtension('section-sorter', {
    onEvent: function (name, evt) {
      if (name !== 'htmx:afterProcessNode') {
        return;
      }
      const element = evt.target as HTMLElement;
      const projectSlug = element.getAttribute('section-sorter-slug');
      if (!projectSlug) {
        console.error('Missing required attr `section-sorter-slug`');
        return;
      }

      new Sortable(element, {
        group: 'sections',
        animation: 150,
        ghostClass: 'dnd-ghost',
        dragClass: 'dnd-item-dragging',
        handle: '.dnd-handle',
      });

      element.addEventListener('end', function (event: SortableJsEvent) {
        const sectionEl = event.item as HTMLElement;
        const newIndex = event.newIndex;
        const sectionId = sectionEl.getAttribute('data-id');

        const updateData: Record<string, number | undefined> = {
          ranking: newIndex,
        };

        // URL could be attribute driven if that makes sense
        // in the future.
        htmx.ajax('POST', `/projects/${projectSlug}/sections/${sectionId}/move`, {
          target: 'main.main',
          swap: 'innerHTML',
          values: updateData,
        });
      } as EventListener);
    },
  });
})();
