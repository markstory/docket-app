import * as React from 'react';
import {Inertia} from '@inertiajs/inertia';
import {InertiaLink} from '@inertiajs/inertia-react';

import Card from 'app/layouts/card';
import FormControl from 'app/components/formControl';
import {t} from 'app/locale';
import {ValidationErrors} from 'app/types';

type Props = {
  errors?: ValidationErrors;
};

export default function Add({errors}: Props) {
  function onSubmit(e: React.FormEvent) {
    e.preventDefault();
    const formData = new FormData(e.target as HTMLFormElement);
    try {
      const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
      formData.append('timezone', timezone);
    } catch (e) {
      // Do nothing we'll use their last timezone.
    }
    Inertia.post('/users/add/', formData);
  }

  return (
    <Card title={t('Register a new account')}>
      <h1>{t('Register')}</h1>
      <p>{t('Get started tracking tasks and subtasks in projects today.')}</p>
      <form method="post" onSubmit={onSubmit}>
        <FormControl
          name="name"
          type="text"
          label={t('Name')}
          help={t('Used when we contact you by email and in the site.')}
          errors={errors}
          required
        />
        <FormControl
          name="email"
          type="email"
          label={t('Email')}
          help={t('Used to email you and to login.')}
          errors={errors}
          required
        />
        <FormControl
          name="password"
          type="password"
          label={t('Password')}
          help={t('More than 10 characters long.')}
          errors={errors}
          required
        />
        <FormControl
          name="confirm_password"
          type="password"
          label={t('Confirm Password')}
          help={t('One more time please.')}
          errors={errors}
          required
        />
        <div className="button-bar">
          <button className="button-primary" type="submit">
            {t('Sign Up')}
          </button>
          <InertiaLink className="button-muted" href="/login">
            {t('Log in')}
          </InertiaLink>
        </div>
      </form>
    </Card>
  );
}
