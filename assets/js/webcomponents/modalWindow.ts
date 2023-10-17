class ModalWindow extends HTMLElement {
  connectedCallback() {
    const closeable = this.getAttribute('closeable');
    if (closeable) {
      console.error('closable is not implemented yet');
    }

    this.addEventListener(
      'click',
      evt => {
        const target = evt.target;
        if (target instanceof HTMLElement && target.getAttribute('modal-close')) {
          this.remove();
        }
      },
      false
    );
  }
}

customElements.define('modal-window', ModalWindow);

export default ModalWindow;
