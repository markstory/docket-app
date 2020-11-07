try {
  window._ = require('lodash');
} catch (e) {}

/**
 * Set CSRF token as a header based on the value of the "XSRF" token cookie.
 */
window.axios = require('axios');
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

import {App} from '@inertiajs/inertia-react';
import React from 'react';
import {render} from 'react-dom';

const el = document.getElementById('app');

render(
  <App
    initialPage={JSON.parse(el.dataset.page)}
    resolveComponent={name => require(`./Pages/${name}`).default}
  />,
  el
);
