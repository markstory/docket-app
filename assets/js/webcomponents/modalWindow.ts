class ModalWindow extends HTMLElement {
  connectedCallback() {
    const dialog = this.querySelector('dialog');
    if (!dialog) {
      console.error('No dialog element found');
      return;
    }

    dialog.showModal();

    this.setupClose(dialog);

    const selectbox = this.querySelector('select-box');
    if (selectbox) {
      // If there is a selectbox child, allow for the dropdown
      // to overflow. This isn't a perfect solution either but works ok.
      // in larger modals that have scroll, elements can overflow with this change.
      selectbox.addEventListener('open', function () {
        dialog.style.overflow = 'visible';
      });
      selectbox.addEventListener('close', function () {
        dialog.style.overflow = 'auto';
      });
    }
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
