import htmx from 'htmx.org';

/**
 * Acts as a dropdown menu.
 *
 * Triggers events `open` and `close` when the menu is opened and closed.
 * By default menu contents are snapshot from their original position
 * in the DOM and the cloned into a portal element. Each time the menu
 * is open state will be reset to the state on page load.
 * Use the `clonemenu=false` attribute to disable this behavior and
 * have stateful dom nodes.
 */
class DropDown extends HTMLElement {
  private revealBackup: Node | undefined = undefined;

  connectedCallback() {
    const triggerTarget = this.getAttribute('trigger') ?? 'button';
    const clonemenu = this.getAttribute('clonemenu') ?? 'true';

    const trigger = this.querySelector(triggerTarget) as HTMLElement | null;
    let reveal = this.querySelector('drop-down-menu') as HTMLElement | null;
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
      document.removeEventListener('close', removeMenu);
      document.removeEventListener('reposition', reposition);

      if (!reveal || !portal) {
        return;
      }
      portal.style.display = 'none';

      if (this.revealBackup && clonemenu === 'true') {
        portal.innerHTML = ' ';
        reveal = this.revealBackup.cloneNode(true) as HTMLElement;
        htmx.process(reveal);
        attachRevealEvents(reveal);
        this.appendChild(reveal);
      } else {
        this.appendChild(reveal);
      }
    };

    function attachRevealEvents(element: HTMLElement) {
      element.addEventListener('click', function (evt) {
        evt.stopPropagation();
        const target = evt.target;
        if (target instanceof HTMLElement && target.getAttribute('dropdown-close')) {
          removeMenu();
        }
      });
    }

    const reposition = () => {
      if (!trigger || !reveal || !portal) {
        return;
      }
      const triggerRect = trigger.getBoundingClientRect();

      // Show the portal
      portal.style.display = 'block';
      portal.style.position = 'absolute';

      const portalScope = this.portalScope;
      const isGlobal = portalScope === 'global';
      const bodyRect = document.body.getBoundingClientRect();
      if (isGlobal) {
        // position portal left aligned and below trigger.
        const topOffset = bodyRect.top * -1 + triggerRect.top + triggerRect.height + 5;
        portal.style.left = `${triggerRect.left + 5}px`;
        portal.style.top = `${topOffset}px`;
      } else {
        portal.style.left = '0px';
        portal.style.top = `${triggerRect.height + 5}px`;
      }

      const menuRect = reveal.getBoundingClientRect();
      // If the menu would overflow, align to the right
      if (menuRect.right > bodyRect.right) {
        const rightEdge = triggerRect.left + triggerRect.width;
        portal.style.left = `${rightEdge - menuRect.width}px`;
      }
      portal.style.display = 'block';
    };
    attachRevealEvents(reveal);

    trigger.addEventListener('click', evt => {
      evt.stopPropagation();
      if (reveal && clonemenu === 'true') {
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

      // Let parents know that the menu is open
      const open = new CustomEvent('open', {
        bubbles: true,
        cancelable: true,
        detail: {
          menu: reveal,
        },
      });
      this.dispatchEvent(open);
    });
  }

  get portalScope() {
    return this.getAttribute('portalscope') ?? 'global';
  }

  makePortal() {
    const portalScope = this.portalScope;
    const isGlobal = portalScope === 'global';
    const isLocal = portalScope === 'local';
    let id = '';
    if (isGlobal) {
      id = 'drop-down-portal';
    }
    if (isLocal) {
      id = 'drop-down-portal-local-' + (Math.random() * 1000).toFixed(5);
    }

    let portal = document.getElementById(id);
    if (portal) {
      return portal;
    }
    portal = document.createElement('div');
    portal.setAttribute('id', id);
    portal.classList.add('drop-down-portal');

    if (isGlobal) {
      document.body.appendChild(portal);
    }
    if (isLocal) {
      this.append(portal);
    }
    return portal;
  }
}

customElements.define('drop-down', DropDown);

export default DropDown;
