import React from 'react';
import {Inertia} from '@inertiajs/inertia';

import {VALID_THEMES} from 'app/constants';
import Modal from 'app/components/modal';
import {User, ValidationErrors} from 'app/types';
import {t} from 'app/locale';
import FormError from 'app/components/formError';
import LoggedIn from 'app/layouts/loggedIn';
import Autocomplete from 'app/components/autocomplete';

type Props = {
  identity: User;
  errors: ValidationErrors;
  referer: string;
};

export default function Edit({identity, referer, errors}: Props) {
  function handleClose() {
    Inertia.visit(referer);
  }

  function handleSubmit(event: React.FormEvent) {
    event.preventDefault();
    const formData = new FormData(event.target as HTMLFormElement);
    Inertia.post('/users/profile', formData);
  }
  const title = t('Edit Profile');

  return (
    <LoggedIn title={title}>
      <Modal onClose={handleClose} label={title}>
        <h1>{t('Edit Profile')}</h1>
        <form method="post" onSubmit={handleSubmit}>
          <input type="hidden" name="referer" value={referer} />
          <div className="form-input">
            <label htmlFor="name">{t('Name')}</label>
            <input id="name" name="name" type="text" defaultValue={identity.name} />
            <FormError errors={errors} field="name" />
          </div>

          <div className="form-input">
            <label htmlFor="unverified_email">
              {t('Email Address')}
              {identity.unverified_email && (
                <p className="form-help">
                  {t(
                    'You have a pending email address change for {email} that needs to be verified.',
                    {email: identity.unverified_email}
                  )}
                </p>
              )}
              <p className="form-help">
                {t(
                  'Until your new email address is verified, you must continue to login with your current email address.'
                )}
              </p>
            </label>
            <input
              id="unverified_email"
              name="unverified_email"
              type="email"
              placeholder={identity.email}
            />
            <FormError errors={errors} field="unverified_email" />
          </div>

          <div className="form-input">
            <label htmlFor="timezone">
              {t('Timezone')}

              <p className="form-help">
                {t(
                  'This should update on each login, so today and tomorrow are always right.'
                )}
              </p>
            </label>
            <input
              id="timezone"
              name="timezone"
              type="text"
              defaultValue={identity.timezone}
            />
            <FormError errors={errors} field="timezone" />
          </div>

          <div className="form-input">
            <label htmlFor="theme">
              {t('Theme')}
              <p className="form-help">
                {t('The "system" theme inherits light/dark from your operating system.')}
              </p>
            </label>
            <Autocomplete
              name="theme"
              label={t('Choose a theme')}
              value={identity.theme}
              options={VALID_THEMES}
            />
            <FormError errors={errors} field="theme" />
          </div>

          <div className="button-bar">
            <button className="button-primary" type="submit">
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
