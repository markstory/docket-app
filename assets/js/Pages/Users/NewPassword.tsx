import {Inertia} from '@inertiajs/inertia';
import {InertiaLink} from '@inertiajs/inertia-react';

import Card from 'app/layouts/card';
import FormControl from 'app/components/formControl';
import {t} from 'app/locale';
import {ValidationErrors} from 'app/types';

type Props = {
  token: string;
  errors?: ValidationErrors;
};

export default function NewPassword({errors, token}: Props) {
  function onSubmit(e: React.FormEvent) {
    e.preventDefault();
    const formData = new FormData(e.target as HTMLFormElement);
    Inertia.post(`/password/new/${token}`, formData);
  }

  return (
    <Card title={t('Forgot your password?')}>
      <h1>{t('Reset your password')}</h1>
      <p>
        {t('Update your password. Your password must be at least 10 characters long.')}
      </p>
      <form method="post" onSubmit={onSubmit}>
        <FormControl
          name="password"
          label={t('Password')}
          type="password"
          errors={errors}
          required
        />
        <FormControl
          name="confirm_password"
          label={t('Confirm Password')}
          type="password"
          errors={errors}
          required
        />
        <div className="button-bar">
          <button type="submit" className="button-primary">
            {t('Reset Password')}
          </button>
          <InertiaLink className="button-muted" href="/login">
            {t('Log in')}
          </InertiaLink>
        </div>
      </form>
    </Card>
  );
}
