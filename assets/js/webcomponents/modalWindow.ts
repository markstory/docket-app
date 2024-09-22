class ModalWindow extends HTMLElement {
  connectedCallback() {
    const dialog = this.querySelector('dialog');
    dialog.showModal();

    this.setupClose(dialog);
    this.closeOnSubmit(dialog);
  }

  setupClose(dialog: HTMLDialogElement | null): void {
    const closer = this.querySelector('[modal-close="true"]');
    if (!closer) {
      return;
    }
    closer.addEventListener(
      'click',
      evt => {
        evt.preventDefault();
        if (dialog) {
          dialog.close();
          dialog.remove();
        }
      },
      false
    );
  }

  closeOnSubmit(dialog: HTMLDialogElement | null): void {
    const form = this.querySelector('form');
    if (!form || !dialog) {
      return;
    }
    dialog.addEventListener('submit', () => dialog.close(), false);
  }
}

customElements.define('modal-window', ModalWindow);

export default ModalWindow;
