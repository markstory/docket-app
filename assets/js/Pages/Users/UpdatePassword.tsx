import * as React from 'react';
import {Inertia} from '@inertiajs/inertia';

import Modal from 'app/components/modal';
import FormControl from 'app/components/formControl';
import {ValidationErrors} from 'app/types';
import {t} from 'app/locale';
import LoggedIn from 'app/layouts/loggedIn';

type Props = {
  errors: ValidationErrors;
  referer: string;
};

export default function UpdatePassword({errors, referer}: Props) {
  function handleClose() {
    Inertia.visit(referer);
  }

  function handleSubmit(event: React.FormEvent) {
    event.preventDefault();
    const formData = new FormData(event.target as HTMLFormElement);
    Inertia.post('/users/updatePassword', formData);
  }

  return (
    <LoggedIn title={t('Update Password')}>
      <Modal onClose={handleClose}>
        <h1>{t('Update Password')}</h1>
        <form method="post" className="form-vertical" onSubmit={handleSubmit}>
          <input type="hidden" name="referer" value={referer} />
          <FormControl
            name="current_password"
            label={t('Current password')}
            type="password"
            errors={errors}
          />
          <FormControl
            name="password"
            label={t('New password')}
            type="password"
            errors={errors}
          />
          <FormControl
            name="confirm_password"
            label={t('Confirm new password')}
            type="password"
            errors={errors}
          />
          <div className="button-bar">
            <button type="submit" className="button-primary">
              {t('Save')}
            </button>
            <button type="reset" className="button-muted" onClick={handleClose}>
              {t('Cancel')}
            </button>
          </div>
        </form>
      </Modal>
    </LoggedIn>
  );
}
