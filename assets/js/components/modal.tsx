import {useState} from 'react';

import BaseModal from '@reach/dialog';

type Props = {
  children: React.ReactNode;
  onClose: () => void;
  label?: string;
  className?: string;
  canClose?: boolean;
  isOpen?: boolean;
};

export default function Modal({
  children,
  onClose,
  className,
  label,
  isOpen = true,
  canClose = true,
}: Props): JSX.Element {
  const [showDialog, setShowDialog] = useState(isOpen);

  function handleClose(event: React.MouseEvent) {
    event.preventDefault();
    setShowDialog(false);
    onClose();
  }

  return (
    <BaseModal
      className={className}
      aria-label={label}
      isOpen={showDialog}
      onDismiss={onClose}
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
