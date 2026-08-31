import htmx from 'htmx.org';

(function () {
  htmx.registerExtension('hx-remove-row', {
    htmx_after_process: function (element: HTMLElement, _detail) {
      for (const item of element.querySelectorAll('[hx-remove-row]')) {
        item.addEventListener('click', function () {
          const selector = this.getAttribute('remove-row-target');
          let row: HTMLElement | null = null;
          if (selector) {
            row = this.closest(selector);
          } else {
            row = this.parentNode as HTMLElement;
          }
          if (row) {
            row.parentNode!.removeChild(row);
          }
        });

      }
    },
  });
})();
