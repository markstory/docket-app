/**
 * Component to reload the page after it has been blurred for `duration` amount of seconds.
 *
 * This is used by daily and project views to reload after periods of inactivity.
 */
class ReloadAfter extends HTMLElement {
  private lastBlur: Date | undefined;
  private deadline: Number;

  constructor() {
    super();
    this.deadline = Infinity;
  }

  connectedCallback() {
    const attrValue = this.getAttribute('duration');
    let deadline = Number(attrValue);
    if (isNaN(deadline)) {
      console.error(`Invalid 'duration' attribute value ${attrValue}`);
      return;
    }
    // Attribute is in seconds.
    this.deadline = deadline * 1000;

    window.addEventListener('focus', this.handleFocus);
    window.addEventListener('blur', this.handleBlur);
  }

  handleFocus = () => {
    if (!this.lastBlur) {
      return;
    }
    const gap = new Date().getTime() - this.lastBlur.getTime();
    if (gap > this.deadline) {
      console.log(`reload-after deadline ${this.deadline}ms reached, reloading`);
      window.location.reload();
    }
  };

  handleBlur = () => {
    this.lastBlur = new Date();
  };

  disconnectedCallback() {
    window.removeEventListener('blur', this.handleBlur);
    window.removeEventListener('focus', this.handleFocus);
  }
}

customElements.define('reload-after', ReloadAfter);

export default ReloadAfter;
