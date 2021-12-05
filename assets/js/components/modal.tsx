import {useState} from 'react';

import BaseModal from '@reach/dialog';

type Props = {
  children: React.ReactNode;
  onClose: (event: React.MouseEvent | MouseEvent) => void;
  label?: string;
  className?: string;
  canClose?: boolean;
  isOpen?: boolean;
};

function Modal({
  children,
  onClose,
  className,
  label,
  isOpen = true,
  canClose = true,
}: Props): JSX.Element {
  const [showDialog, setShowDialog] = useState(isOpen);

  function handleClose(event: React.MouseEvent) {
    onClose(event);
    if (event.isDefaultPrevented()) {
      return;
    }
    event.preventDefault();
    setShowDialog(false);
  }

  function handleDismiss() {
    const event = new MouseEvent('keyDown', {
      view: window,
      bubbles: true,
      cancelable: true,
      buttons: 1,
    });
    onClose(event);
  }

  return (
    <BaseModal
      className={className}
      aria-label={label}
      isOpen={showDialog}
      onDismiss={handleDismiss}
    >
      {canClose && (
        <button className="modal-close" onClick={handleClose}>
          {'\u2715'}
        </button>
      )}
      <div className="modal-contents">{children}</div>
    </BaseModal>
  );
}

export default Modal;
