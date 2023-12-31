class ReloadAfter extends HTMLElement {
  private timeoutId: number | undefined;

  connectedCallback() {
    const attrValue = this.getAttribute('timestamp');
    const deadline = Number(attrValue);
    if (isNaN(deadline)) {
      console.error(`Invalid 'timestamp' attribute value ${attrValue}`);
      return;
    }
    const currentTime = Date.now();
    const delay = deadline - currentTime;
    this.timeoutId = setTimeout(function () {
      window.location.reload();
    }, delay);
  }

  disconnectedCallback() {
    const timeout = this.timeoutId;
    if (timeout) {
      clearTimeout(timeout);
      this.timeoutId = undefined;
    }
  }
}

customElements.define('reload-after', ReloadAfter);

export default ReloadAfter;
