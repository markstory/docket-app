import axios from 'axios';
import React from 'react';
import {InertiaApp} from '@inertiajs/inertia-react';
import {render} from 'react-dom';
import Modal from 'react-modal';

// Setup CSRF tokens.
axios.defaults.xsrfCookieName = 'csrfToken';
axios.defaults.xsrfHeaderName = 'X-Csrf-Token';

const el = document.getElementById('app');
if (!el) {
  throw new Error('Could not find application root element');
}
Modal.setAppElement('#app');

render(
  <InertiaApp
    initialPage={JSON.parse(el.dataset.page || '')}
    resolveComponent={(name: string) =>
      import(`app/Pages/${name}`).then(module => module.default)
    }
  />,
  el
);
