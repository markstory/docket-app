import React from 'react';

import {Inertia} from '@inertiajs/inertia';
import {FlashMessage} from 'app/types';
import FlashMessages from 'app/components/flashMessages';
import {InertiaLink} from '@inertiajs/inertia-react';

type Props = {
  _csrfToken: string;
  flash: null | FlashMessage;
};

export default function Login({_csrfToken, flash}: Props) {
  function onSubmit(e: React.FormEvent) {
    e.preventDefault();
    const formData = new FormData(e.target as HTMLFormElement);
    Inertia.post('/login', formData);
  }

  return (
    <React.Fragment>
      <h1>Login</h1>
      <FlashMessages flash={flash} />
      <form method="post" onSubmit={onSubmit}>
        <input type="hidden" name="_csrfToken" value={_csrfToken} />
        <div className="form-input">
          <label htmlFor="email">Email</label>
          <input id="email" name="email" type="email" required />
        </div>
        <div className="form-input">
          <label htmlFor="password">Password</label>
          <input id="password" name="password" type="password" required />
        </div>
        <div className="button-bar">
          <button type="submit">Login</button>
          <InertiaLink href="/forgot">Recover Password</InertiaLink>
        </div>
      </form>
    </React.Fragment>
  );
}
