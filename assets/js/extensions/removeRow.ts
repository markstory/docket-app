import htmx from 'htmx.org';

(function () {
  htmx.defineExtension('remove-row', {
    onEvent: function (name, event) {
      if (name !== 'htmx:afterProcessNode') {
        return;
      }
      const element = event.target as HTMLElement;
      element.addEventListener('click', function () {
        const row = element.parentNode;
        if (row) {
          row.parentNode!.removeChild(row);
        }
      });
    },
  });
})();
