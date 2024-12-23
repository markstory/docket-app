class ModalWindow extends HTMLElement {
  connectedCallback() {
    const dialog = this.querySelector('dialog');
    dialog.showModal();

    this.setupClose(dialog);
  }

  setupClose(dialog: HTMLDialogElement | null): void {
    const closer = this.querySelector('[modal-close]');
    if (!closer) {
      return;
    }
    const attrVal = closer.getAttribute('modal-close');
    if (attrVal !== '1' && attrVal !== 'true') {
      return;
    }
    closer.addEventListener(
      'click',
      evt => {
        evt.preventDefault();
        this._close(dialog);
      },
      false
    );
  }

  _close(dialog: HTMLDialogElement | null): void {
    if (dialog) {
      dialog.close();
      dialog.remove();
    } else {
      this.remove();
    }
  }
}

customElements.define('modal-window', ModalWindow);

export default ModalWindow;
