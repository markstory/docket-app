import htmx from 'htmx.org';

(function () {
  function startTimer(element: HTMLElement, duration: number) {
    const timerId = setTimeout(function () {
      element.dataset.state = 'hidden';
    }, duration);
    element.dataset.timer = String(timerId);
  }

  htmx.defineExtension('flash-message', {
    onEvent: function (name, event) {
      if (name !== 'htmx:afterProcessNode') {
        return;
      }
      const element = event.target as HTMLElement;
      element.addEventListener('mouseleave', function () {
        clearTimeout(Number(element.dataset.timer));
        startTimer(element, 1500);
      });

      element.addEventListener('mouseenter', function () {
        clearTimeout(Number(element.dataset.timer));
      });

      // Setup initial timeout
      startTimer(element, 4000);
    },
  });
})();
