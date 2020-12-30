import React from 'react';

import BaseModal from 'react-modal';

type Props = {
  children: React.ReactNode;
  onClose: () => void;
  className?: string;
  canClose?: boolean;
  isOpen?: boolean;
};

export default function Modal({
  children,
  onClose,
  className = 'modal',
  isOpen = true,
  canClose = true,
}: Props) {
  function handleClose(event: React.MouseEvent) {
    event.preventDefault();
    onClose();
  }

  function handleRequestClose() {
    onClose();
  }

  return (
    <BaseModal
      className={className}
      overlayClassName="modal-overlay"
      isOpen={isOpen}
      onRequestClose={handleRequestClose}
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
