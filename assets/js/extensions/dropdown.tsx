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
   * - dropdown-portal - defaults to '#dropdown-portal'
   */
  htmx.defineExtension('dropdown', {
    onEvent: function (name, event) {
      if (name !== 'htmx:afterProcessNode') {
        return;
      }
      const element = event.target as HTMLElement;
      if (element.getAttribute('hx-ext') !== 'dropdown') {
        return;
      }
      const revealTarget = element.getAttribute('dropdown-reveal');
      const triggerTarget = element.getAttribute('dropdown-trigger');
      const portalTarget = element.getAttribute('dropdown-portal') ?? '#dropdown-portal';
      if (!revealTarget || !triggerTarget) {
        throw new Error('The trigger and reveal attributes are required.');
      }

      const trigger = element.querySelector(triggerTarget) as HTMLElement | null;
      const reveal = element.querySelector(revealTarget) as HTMLElement | null;
      const portal = document.querySelector(portalTarget) as HTMLElement | null;
      if (trigger === null || reveal === null || portal === null) {
        throw new Error(
          `Could not find one of trigger=${triggerTarget} reveal=${revealTarget} or portal=${portalTarget} elements.`
        );
      }

      // Handle clicks outside the root parent element.
      function removeMenu() {
        // Remove this listener
        document.removeEventListener('click', removeMenu);

        if (!reveal || !portal) {
          return;
        }
        reveal.appendChild(portal.children[0]);
        portal.style.display = 'none';
      }

      trigger.addEventListener('click', function (evt) {
        evt.preventDefault();
        evt.stopPropagation();

        // Move menu contents to portal element
        portal.appendChild(reveal.children[0]);

        // position portal
        portal.style.left = `${trigger.offsetLeft + 5}px`;
        portal.style.top = `${trigger.offsetTop + trigger.offsetHeight + 5}px`;
        portal.style.display = 'block';

        // Setup hide handler
        document.addEventListener('click', removeMenu);
      });

      reveal.addEventListener('click', function (evt) {
        evt.stopPropagation();
      });
    },
  });
})();
