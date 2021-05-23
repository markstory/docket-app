import {Inertia} from '@inertiajs/inertia';
import Select from 'react-select';

import {VALID_THEMES} from 'app/constants';
import Modal from 'app/components/modal';
import {User, ValidationErrors} from 'app/types';
import {t} from 'app/locale';
import FormControl from 'app/components/formControl';
import LoggedIn from 'app/layouts/loggedIn';

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
          <FormControl
            name="name"
            label={t('Name')}
            type="text"
            value={identity.name}
            errors={errors}
          />
          <FormControl
            name="unverified_email"
            label={t('Email Address')}
            type="text"
            help={
              identity.unverified_email
                ? t(
                    'You have a pending email address change for {email} that needs to be verified.',
                    {email: identity.unverified_email}
                  )
                : t(
                    'Until your new email address is verified, you must continue to login with your current email address.'
                  )
            }
            placeholder={identity.email}
            errors={errors}
          />
          <FormControl
            key="timezone"
            name="timezone"
            label={t('Timezone')}
            type="text"
            help={t(
              'This should update on each login, so today and tomorrow are always right.'
            )}
            value={identity.timezone}
            errors={errors}
          />
          <FormControl
            name="theme"
            label={t('Theme')}
            type={() => (
              <Select
                classNamePrefix="select"
                name="theme"
                label={t('Choose a theme')}
                defaultValue={VALID_THEMES.find(opt => opt.value === identity.theme)}
                getOptionValue={option => option.value}
                components={{IndicatorSeparator: null}}
                options={VALID_THEMES}
              />
            )}
            help={t(
              'The "system" theme inherits light/dark from your operating system when possible.'
            )}
            errors={errors}
          />

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
