import htmx from 'htmx.org';

// TODO can components inherit from each other?
class DueOn extends HTMLElement {
  private revealBackup: Node | undefined = undefined;

  connectedCallback() {
    const triggerTarget = this.getAttribute('trigger') ?? '& > button';

    const trigger = this.querySelector(triggerTarget) as HTMLElement | null;
    let reveal = this.querySelector('due-on-menu') as HTMLElement | null;
    if (trigger === null || reveal === null) {
      throw new Error(
        `Could not find one of trigger=${triggerTarget} due-on-menu elements.`
      );
    }
    const portal = this.makePortal();

    // Handle clicks outside the root parent element.
    const removeMenu = () => {
      // Remove this listener
      document.removeEventListener('click', removeMenu);
      document.removeEventListener('close', removeMenu);
      document.removeEventListener('reposition', reposition);

      if (!reveal || !portal) {
        return;
      }
      portal.style.display = 'none';
      portal.innerHTML = '';

      if (this.revealBackup) {
        reveal = this.revealBackup.cloneNode(true) as HTMLElement;
        htmx.process(reveal);
        attachRevealEvents(reveal);
        this.appendChild(reveal);
      }
    };

    function attachRevealEvents(element) {
      element.addEventListener('click', function (evt) {
        evt.stopPropagation();
        const target = evt.target;
        if (target instanceof HTMLElement && target.getAttribute('dropdown-close')) {
          removeMenu();
        }
      });
    }

    function reposition() {
      if (!trigger || !reveal || !portal) {
        return;
      }
      const triggerRect = trigger.getBoundingClientRect();

      // position portal left aligned and below trigger.
      portal.style.left = `${triggerRect.left + 5}px`;
      portal.style.top = `${triggerRect.top + triggerRect.height + 5}px`;
      portal.style.display = 'block';
      portal.style.position = 'absolute';

      // TODO solve for scroll offsets
      const menuRect = reveal.getBoundingClientRect();
      const bodyRect = document.body.getBoundingClientRect();
      // If the menu would overflow, align to the right
      if (menuRect.right> bodyRect.right) {
        const rightEdge = triggerRect.left + triggerRect.width;
        portal.style.left = `${rightEdge - menuRect.width}px`;
      }
    }

    attachRevealEvents(reveal);
    trigger.addEventListener('click', evt => {
      evt.stopPropagation();
      if (reveal) {
        this.revealBackup = reveal.cloneNode(true);
      }

      if (portal.style.display !== 'none') {
        // If the portal is open force it closed.
        const close = new CustomEvent('close', {
          bubbles: true,
          cancelable: true,
        });
        document.dispatchEvent(close);
      }
      // Move menu contents to portal element
      if (reveal) {
        portal.appendChild(reveal);
        reposition();
      }

      // Setup hide handler and menu reposition event
      // for menus that change the shape of the contents.
      document.addEventListener('click', removeMenu);
      document.addEventListener('close', removeMenu);
      document.addEventListener('reposition', reposition);
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

customElements.define('due-on', DueOn);

export default DueOn;
