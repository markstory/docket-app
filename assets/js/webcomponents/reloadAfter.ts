class ReloadAfter extends HTMLElement {
  private timeoutId: number | undefined;

  connectedCallback() {
    const attrValue = this.getAttribute('timestamp');
    let deadline = Number(attrValue);
    if (isNaN(deadline)) {
      console.error(`Invalid 'timestamp' attribute value ${attrValue}`);
      return;
    }
    // Seconds -> milliseconds
    deadline = deadline * 1000;

    const currentTime = Date.now();
    const delay = deadline - currentTime;
    this.timeoutId = setTimeout(function () {
      console.log(
        `Triggering reload as current time is ${delay} seconds after ${currentTime}`
      );
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
