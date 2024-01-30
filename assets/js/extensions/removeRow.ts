import htmx from 'htmx.org';

(function () {
  htmx.defineExtension('remove-row', {
    onEvent: function (name, event) {
      if (name !== 'htmx:afterProcessNode') {
        return;
      }
      const element = event.target as HTMLElement;
      element.addEventListener('click', function () {
        const selector = element.getAttribute('remove-row-target');
        let row: HTMLElement | null = null;
        if (selector) {
          row = element.closest(selector);
        } else {
          row = element.parentNode as HTMLElement;
        }
        if (row) {
          row.parentNode!.removeChild(row);
        }
      });
    },
  });
})();
