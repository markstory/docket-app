import htmx from 'htmx.org';

(function () {
  function startTimer(element: HTMLElement, duration: number) {
    const timerId = setTimeout(function () {
      element.dataset.state = 'hidden';
    }, duration);
    element.dataset.timer = String(timerId);
  }

  function processItem(element: HTMLElement) {
    element.addEventListener('mouseleave', function () {
      clearTimeout(Number(element.dataset.timer));
      startTimer(element, 1500);
    });

    element.addEventListener('mouseenter', function () {
      clearTimeout(Number(element.dataset.timer));
    });

    // Setup initial timeout
    startTimer(element, 4000);
  }

  htmx.registerExtension('hx-flash-message', {
    htmx_after_process: function (element: HTMLElement, _detail) {
      for (const item in element.querySelectorAll('[hx-flash-message]')) {
        processItem(item);
      }
    },
  });
})();
