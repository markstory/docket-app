import axios from 'axios';
import {InertiaApp} from '@inertiajs/inertia-react';
import {render} from 'react-dom';
import '../sass/app.scss';

// Setup CSRF tokens.
axios.defaults.xsrfCookieName = 'csrfToken';
axios.defaults.xsrfHeaderName = 'X-Csrf-Token';

const el = document.getElementById('app');
if (!el) {
  throw new Error('Could not find application root element');
}

render(
  <InertiaApp
    initialPage={JSON.parse(el.dataset.page || '')}
    resolveComponent={(name: string) =>
      import(`./Pages/${name}.tsx`).then(module => module.default)
    }
  />,
  el
);
