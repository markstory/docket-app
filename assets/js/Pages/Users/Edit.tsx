import React from 'react';
import {Inertia} from '@inertiajs/inertia';

import Modal from 'app/components/modal';
import {User} from 'app/types';
import {t} from 'app/locale';
import LoggedIn from 'app/layouts/loggedIn';

type Props = {
  identity: User;
  referer: string;
};

export default function UsersEdit({identity, referer}: Props) {
  function handleClose() {
    Inertia.visit(referer);
  }

  function handleSubmit(event: React.FormEvent) {
    event.preventDefault();
    const formData = new FormData(event.target as HTMLFormElement);
    Inertia.post('/users/profile', formData);
  }

  return (
    <LoggedIn>
      <Modal onClose={handleClose}>
        <h1>{t('Edit Profile')}</h1>
        <form method="post" className="form-vertical" onSubmit={handleSubmit}>
          <input type="hidden" name="referer" value={referer} />
          <div className="form-input">
            <label htmlFor="name">{t('Name')}</label>
            <input name="name" type="text" defaultValue={identity.name} />
          </div>

          <div className="form-input">
            <label htmlFor="unverified_email">{t('Email Address')}</label>
            <input name="unverified_email" type="email" defaultValue={identity.email} />
            <p className="form-help">
              {t(
                'Until your new email address is verified, you must continue to login with your current email address.'
              )}
            </p>
          </div>

          <div className="form-input">
            <label htmlFor="timezone">{t('Timezone')}</label>
            <input name="timezone" type="text" defaultValue={identity.timezone} />
          </div>

          <div className="button-bar">
            <button type="submit">{t('Save')}</button>
            <button type="reset" className="button-secondary" onClick={handleClose}>
              {t('Cancel')}
            </button>
          </div>
        </form>
      </Modal>
    </LoggedIn>
  );
}
