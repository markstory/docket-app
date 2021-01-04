import React from 'react';
import {Inertia} from '@inertiajs/inertia';
import {InertiaLink} from '@inertiajs/inertia-react';

import Card from 'app/layouts/card';
import FormError from 'app/components/formError';
import {t} from 'app/locale';
import {ValidationErrors} from 'app/types';

type Props = {
  errors?: ValidationErrors;
};

export default function Add({errors}: Props) {
  function onSubmit(e: React.FormEvent) {
    e.preventDefault();
    const formData = new FormData(e.target as HTMLFormElement);
    Inertia.post('/users/add/', formData);
  }

  return (
    <Card title={t('Register a new account')}>
      <h1>{t('Register')}</h1>
      <p>{t('Get started tracking tasks and subtasks in projects today.')}</p>
      <form method="post" onSubmit={onSubmit}>
        <div className="form-input">
          <label htmlFor="name">{t('Name')}</label>
          <input id="name" name="name" type="text" required />
          <FormError errors={errors} field="text" />
          <p className="form-help">
            {t('Used when we contact you by email and in the site.')}
          </p>
        </div>
        <div className="form-input">
          <label htmlFor="email">{t('Email')}</label>
          <input id="email" name="email" type="email" required />
          <FormError errors={errors} field="email" />
          <p className="form-help">{t('Used to email you and to login.')}</p>
        </div>
        <div className="form-input">
          <label htmlFor="password">{t('Password')}</label>
          <input id="password" name="password" type="password" required />
          <FormError errors={errors} field="password" />
          <p className="form-help">{t('More than 10 characters long.')}</p>
        </div>
        <div className="form-input">
          <label htmlFor="confirm_password">{t('Confirm Password')}</label>
          <input id="confirm_password" name="confirm_password" type="password" required />
          <FormError errors={errors} field="confirm_password" />
          <p className="form-help">{t('One more time please.')}</p>
        </div>
        <div className="form-input">
          <label htmlFor="timezone">{t('Timezone')}</label>
          <input
            id="timezone"
            name="timezone"
            type="text"
            required
            defaultValue="America/New_York"
          />
          <FormError errors={errors} field="timezone" />
          <p className="form-help">
            {t('Used so we know what day is today in your part of the world.')}
          </p>
        </div>
        <div className="button-bar">
          <button type="submit">{t('Sign Up')}</button>
        </div>
      </form>
    </Card>
  );
}
