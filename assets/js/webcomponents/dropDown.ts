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
      const triggerRect = trigger.getBoundingClientRect();

      // position portal left aligned and below trigger.
      portal.style.left = `${triggerRect.left + 5}px`;
      portal.style.top = `${triggerRect.top + triggerRect.height + 5}px`;
      portal.style.display = 'block';
      portal.style.position = 'absolute';

      const menuRect = reveal.getBoundingClientRect();
      const bodyRect = document.body.getBoundingClientRect();
      // If the menu would overflow, align to the right
      if (menuRect.right > bodyRect.right) {
        const rightEdge = triggerRect.left + triggerRect.width;
        portal.style.left = `${rightEdge - menuRect.width}px`;
      }

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
