import React from 'react';
import {Inertia} from '@inertiajs/inertia';
import {InertiaLink} from '@inertiajs/inertia-react';

import Card from 'app/layouts/card';
import {t} from 'app/locale';

export default function Login() {
  function onSubmit(e: React.FormEvent) {
    e.preventDefault();
    const formData = new FormData(e.target as HTMLFormElement);
    Inertia.post('/login', formData);
  }

  return (
    <Card title={t('Login')}>
      <h1>{t('Login')}</h1>
      <form method="post" onSubmit={onSubmit}>
        <div className="form-input">
          <label htmlFor="email">{t('Email')}</label>
          <input id="email" name="email" type="email" required />
        </div>
        <div className="form-input">
          <label htmlFor="password">{t('Password')}</label>
          <input id="password" name="password" type="password" required />
        </div>
        <div className="button-bar">
          <button type="submit">{t('Login')}</button>
          <InertiaLink className="button button-muted" href="/forgot">
            {t('Forgot Password?')}
          </InertiaLink>
        </div>
      </form>
    </Card>
  );
}
