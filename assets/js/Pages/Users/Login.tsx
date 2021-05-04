import * as React from 'react';
import {Inertia} from '@inertiajs/inertia';
import {InertiaLink} from '@inertiajs/inertia-react';

import FormControl from 'app/components/formControl';
import Card from 'app/layouts/card';
import {t} from 'app/locale';

export default function Login() {
  function onSubmit(e: React.FormEvent) {
    e.preventDefault();
    const formData = new FormData(e.target as HTMLFormElement);
    try {
      const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
      formData.append('timezone', timezone);
    } catch (e) {
      // Do nothing we'll use their last timezone.
    }
    Inertia.post('/login', formData);
  }

  return (
    <Card title={t('Login')}>
      <h1 className="heading-logo">
        <img src="/img/docket-logo.svg" width="45" height="45" />
        {t('Login')}
      </h1>
      <form method="post" onSubmit={onSubmit}>
        <FormControl
          className="narrow"
          name="email"
          label={t('Email')}
          type="email"
          required
        />
        <FormControl
          className="narrow"
          name="password"
          label={t('Password')}
          type="password"
          required
        />
        <div className="button-bar">
          <button className="button-primary" type="submit">
            {t('Login')}
          </button>
          <InertiaLink className="button button-muted" href="/password/reset">
            {t('Forgot Password?')}
          </InertiaLink>
        </div>
        <div className="button-bar">
          {t("Don't have an account?")}
          <InertiaLink className="button button-muted" href="/users/add">
            {t('Sign Up')}
          </InertiaLink>
        </div>
      </form>
    </Card>
  );
}
