import React from 'react';

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
    <Modal onClose={() => {}} canClose={false} isOpen={show}>
      <div className="confirm">
        <h3>{title}</h3>
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
  confirmation: string,
  proceedLabel: string = 'OK',
  cancelLabel = 'cancel',
  options = {}
) {
  return createConfirmation(confirmable(Confirmation))({
    confirmation,
    proceedLabel,
    cancelLabel,
    ...options,
  });
}
