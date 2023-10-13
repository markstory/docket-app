class DropDown extends HTMLElement {
  connectedCallback() {
    const revealTarget = this.getAttribute('reveal') ?? 'drop-down-menu';
    const triggerTarget = this.getAttribute('trigger') ?? 'button';

    const trigger = this.querySelector(triggerTarget) as HTMLElement | null;
    const reveal = this.querySelector(revealTarget) as HTMLElement | null;
    if (trigger === null || reveal === null) {
      throw new Error(
        `Could not find one of trigger=${triggerTarget} reveal=${revealTarget} elements.`
      );
    }
    const portal = this.makePortal();

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
      portal.style.position = 'absolute';

      // Setup hide handler
      document.addEventListener('click', removeMenu);
    });

    reveal.addEventListener('click', function (evt) {
      evt.stopPropagation();
    });
  }

  makePortal() {
    const id = 'drop-down-portal';
    let portal = document.getElementById(id);
    if (portal) {
      return portal;
    }
    portal = document.createElement('div');
    portal.setAttribute('id', id);

    document.body.appendChild(portal);
    return portal;
  }
}

customElements.define('drop-down', DropDown);

export default DropDown;
