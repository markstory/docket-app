class ModalWindow extends HTMLElement {
  connectedCallback() {
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
