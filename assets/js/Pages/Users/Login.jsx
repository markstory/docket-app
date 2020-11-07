import React from 'react';

export default function Login({_csrfToken, flash}) {
  return (
    <React.Fragment>
      <h1>Login</h1>
      {/* This needs an abstraction for it */}
      {flash && flash.message && <div className="message error">{flash.message}</div>}
      <form method="post">
        <input type="hidden" name="_csrfToken" value={_csrfToken} />
        <div>
          <label htmlFor="email">Email</label>
          <input id="email" name="email" type="email" required />
        </div>
        <div>
          <label htmlFor="password">Password</label>
          <input id="password" name="password" type="password" required />
        </div>
        <button type="submit">Login</button>
      </form>
    </React.Fragment>
  );
}
