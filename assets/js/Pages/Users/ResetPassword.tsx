import React from 'react';
import {Inertia} from '@inertiajs/inertia';
import {InertiaLink} from '@inertiajs/inertia-react';

import FormControl from 'app/components/formControl';
import Card from 'app/layouts/card';
import {t} from 'app/locale';

export default function ResetPassword() {
  function onSubmit(e: React.FormEvent) {
    e.preventDefault();
    const formData = new FormData(e.target as HTMLFormElement);
    Inertia.post('/password/reset', formData);
  }

  return (
    <Card title={t('Forgot your password?')}>
      <h1>{t('Forgot your password')}</h1>
      <p>{t('We will send you an email with instructions to reset it.')}</p>
      <form method="post" onSubmit={onSubmit}>
        <FormControl
          className="narrow"
          name="email"
          type="email"
          label={t('Email')}
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
