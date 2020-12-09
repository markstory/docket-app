import React from 'react';

import BaseModal from 'react-modal';

type Props = {
  children: React.ReactNode;
  onClose: () => void;
  canClose?: boolean;
  isOpen?: boolean;
};

export default function Modal({
  children,
  onClose,
  isOpen = true,
  canClose = true,
}: Props) {
  function handleClose(event: React.MouseEvent) {
    event.preventDefault();
    onClose();
  }

  return (
    <BaseModal className="modal" overlayClassName="modal-overlay" isOpen={isOpen}>
      {canClose && <button onClick={handleClose}>{'\u2715'}</button>}
      {children}
    </BaseModal>
  );
}
