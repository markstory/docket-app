class ModalWindow extends HTMLElement {
  connectedCallback() {
    const closeable = this.getAttribute('closeable');
    if (closeable) {
      console.error('closable is not implemented yet');
    }
    const open = this.getAttribute('open');
    const dialog = this.querySelector('dialog');
    if (open && dialog) {
      dialog.showModal();
    }

    this.addEventListener(
      'click',
      evt => {
        const target = evt.target;
        if (target instanceof HTMLElement && target.getAttribute('modal-close')) {
          evt.preventDefault();
          this.remove();
        }
      },
      false
    );
  }
}

customElements.define('modal-window', ModalWindow);

export default ModalWindow;
