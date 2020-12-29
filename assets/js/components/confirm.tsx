import React from 'react';

import {t} from 'app/locale';
import Modal from 'app/components/modal';
import {confirmable, createConfirmation} from 'react-confirm';

type Props = {
  proceedLabel: string;
  cancelLabel: string;
  title: string;
  confirmation: string;
  proceed: (value: boolean) => void;
  enableEscape: boolean;
  show: boolean;
};

function Confirmation({
  proceedLabel,
  cancelLabel,
  title,
  confirmation,
  proceed,
  show,
}: Props) {
  return (
    <Modal
      className="modal modal-confirm"
      onClose={() => {}}
      canClose={false}
      isOpen={show}
    >
      <div className="confirm">
        {title && <h3>{title}</h3>}
        <p className="body">{confirmation}</p>
        <div className="button-bar-right">
          <button className="button-default" onClick={() => proceed(false)}>
            {cancelLabel}
          </button>
          <button className="button-danger" onClick={() => proceed(true)}>
            {proceedLabel}
          </button>
        </div>
      </div>
    </Modal>
  );
}

export function confirm(
  title: string,
  confirmation: string,
  proceedLabel: string = t('Ok'),
  cancelLabel = t('Cancel'),
  options = {}
) {
  return createConfirmation(confirmable(Confirmation))({
    title,
    confirmation,
    proceedLabel,
    cancelLabel,
    ...options,
  });
}
