import React from 'react';
import {Inertia} from '@inertiajs/inertia';

import Modal from 'app/components/modal';
import FormError from 'app/components/formError';
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
          <div className="form-input">
            <label htmlFor="current_password">{t('Current password')}</label>
            <input name="current_password" type="password" />
            <FormError errors={errors} field="current_password" />
          </div>

          <div className="form-input">
            <label htmlFor="password">{t('New password')}</label>
            <input name="password" type="password" />
            <FormError errors={errors} field="password" />
          </div>

          <div className="form-input">
            <label htmlFor="confirm_password">{t('Confirm new password')}</label>
            <input name="confirm_password" type="password" />
            <FormError errors={errors} field="confirm_password" />
          </div>

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
