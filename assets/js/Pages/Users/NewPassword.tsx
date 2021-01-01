import React from 'react';
import {Inertia} from '@inertiajs/inertia';
import {InertiaLink} from '@inertiajs/inertia-react';

import Card from 'app/layouts/card';
import FormError from 'app/components/formError';
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
        <div className="form-input">
          <label htmlFor="password">{t('Password')}</label>
          <input id="password" name="password" type="password" required />
          <FormError errors={errors} field="password" />
        </div>
        <div className="form-input">
          <label htmlFor="confirm_password">{t('Confirm Password')}</label>
          <input id="confirm_password" name="confirm_password" type="password" required />
          <FormError errors={errors} field="confirm_password" />
        </div>
        <div className="button-bar">
          <button type="submit">{t('Reset Password')}</button>
          <InertiaLink className="button button-muted" href="/login">
            {t('Log in')}
          </InertiaLink>
        </div>
      </form>
    </Card>
  );
}
