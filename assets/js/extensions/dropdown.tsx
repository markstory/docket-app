import htmx from 'htmx.org';

(function () {
  /**
   * Build a simple dropdown interaction.
   * A trigger element, will reveal the reveal element
   * on click. Clicking outside the reveal target will close
   * the menu. You are responsible for aria attributes.
   *
   * - dropdown-reveal
   * - dropdown-trigger
   */
  htmx.defineExtension('dropdown', {
    onEvent: function (name, event) {
      if (name !== 'htmx:afterProcessNode') {
        return;
      }
      const element = event.target as HTMLElement;
      const revealTarget = element.getAttribute('dropdown-reveal');
      const triggerTarget = element.getAttribute('dropdown-trigger');
      if (!revealTarget || !triggerTarget) {
        throw new Error('The trigger and reveal attributes are required.');
      }

      const trigger = element.querySelector(triggerTarget);
      const reveal = element.querySelector(revealTarget) as HTMLElement | null;
      if (trigger === null || reveal === null) {
        throw new Error(
          `Could not find trigger ${triggerTarget} or reveal  ${revealTarget} element.`
        );
      }

      trigger.addEventListener('click', function (evt) {
        evt.preventDefault();
        evt.stopPropagation();

        reveal.style.display = 'block';
      });

      // Handle clicks outside the root parent element.
      document.addEventListener('click', function () {
        reveal.style.display = 'none';
      });
      reveal.addEventListener('click', function (evt) {
        evt.stopPropagation();
      });
    },
  });
})();
