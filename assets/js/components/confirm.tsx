import {useRef} from 'react';
import {AlertDialog, AlertDialogLabel, AlertDialogDescription} from '@reach/alert-dialog';

import {t} from 'app/locale';
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
  const cancelRef = useRef<HTMLButtonElement>(null);

  return (
    <AlertDialog
      onDismiss={() => proceed(false)}
      isOpen={show}
      leastDestructiveRef={cancelRef}
    >
      {title && <AlertDialogLabel>{title}</AlertDialogLabel>}
      <AlertDialogDescription>{confirmation}</AlertDialogDescription>
      <div className="button-bar-right">
        <button
          className="button-muted"
          data-testid="confirm-cancel"
          ref={cancelRef}
          onClick={() => proceed(false)}
        >
          {cancelLabel}
        </button>
        <button
          className="button-danger"
          data-testid="confirm-proceed"
          onClick={() => proceed(true)}
        >
          {proceedLabel}
        </button>
      </div>
    </AlertDialog>
  );
}

export function confirm(
  title: string,
  confirmation: string,
  proceedLabel: string = t('Ok'),
  cancelLabel = t('Cancel'),
  options = {}
): Promise<string> {
  return createConfirmation(confirmable(Confirmation))({
    title,
    confirmation,
    proceedLabel,
    cancelLabel,
    ...options,
  });
}
