import htmx from 'htmx.org';

(function () {
  /**
   * Adds event listeners to remove an element when
   * a click occurs outside of the element.
   */
  htmx.defineExtension('click-outside', {
    onEvent: function (name, event) {
      if (name !== 'htmx:afterProcessNode') {
        return;
      }
      const element = event.target as HTMLElement;

      document.addEventListener('click', function () {
        element.parentNode?.removeChild(element);
      });

      element.addEventListener('click', function (evt) {
        evt.stopPropagation();
      });
    },
  });
})();
