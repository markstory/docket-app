class DropDown extends HTMLElement {
  connectedCallback() {
    const triggerTarget = this.getAttribute('trigger') ?? 'button';

    const trigger = this.querySelector(triggerTarget) as HTMLElement | null;
    const reveal = this.querySelector('drop-down-menu') as HTMLElement | null;
    if (trigger === null || reveal === null) {
      throw new Error(
        `Could not find one of trigger=${triggerTarget} drop-down-menu elements.`
      );
    }
    const portal = this.makePortal();

    // Handle clicks outside the root parent element.
    const removeMenu = () => {
      // Remove this listener
      document.removeEventListener('click', removeMenu);

      if (!reveal || !portal) {
        return;
      }
      portal.style.display = 'none';
      this.appendChild(portal.children[0]);
    };

    trigger.addEventListener('click', function (evt) {
      evt.stopPropagation();

      // Move menu contents to portal element
      portal.appendChild(reveal);

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
      const target = evt.target;
      if (target instanceof HTMLElement && target.getAttribute('dropdown-close')) {
        removeMenu();
      }
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
