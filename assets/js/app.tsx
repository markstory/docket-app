import 'vite/modulepreload-polyfill';
import axios from 'axios';
import {InertiaApp} from '@inertiajs/inertia-react';
import {render} from 'react-dom';

// The nice-select2 package doesn't import cleanly
import NiceSelect from '../../node_modules/nice-select2/src/js/nice-select2.js';

import '../sass/app.scss';

// Htmx setup
import htmx from 'htmx.org';
import 'app/extensions/ajax';
import 'app/extensions/dropdown';
import 'app/extensions/flashMessage';
import 'app/extensions/projectSorter';

// Expose htmx on window
// @ts-ignore-next-line
window.htmx = htmx;

// Setup CSRF tokens.
axios.defaults.xsrfCookieName = 'csrfToken';
axios.defaults.xsrfHeaderName = 'X-Csrf-Token';

document.addEventListener('DOMContentLoaded', function () {
  const selectboxes = document.querySelectorAll('[data-niceselect]');
  for (const element of selectboxes) {
    new NiceSelect(element);
  }
});

const el = document.getElementById('app');
if (!el) {
  console.error('Could not find application root element');
} else {
  render(
    <InertiaApp
      initialPage={JSON.parse(el.dataset.page || '')}
      resolveComponent={async (name: string) => {
        const pages = import.meta.glob(`./Pages/*/*.tsx`);
        return (await pages[`./Pages/${name}.tsx`]()).default;
      }}
    />,
    el
  );
}
