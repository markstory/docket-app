import 'vite/modulepreload-polyfill';
import axios from 'axios';
import {InertiaApp} from '@inertiajs/inertia-react';
import {render} from 'react-dom';

import '../sass/app.scss';

// Htmx setup
import htmx from 'htmx.org';

// Expose htmx on window
// @ts-ignore-next-line
window.htmx = htmx;

// htmx extensions
import 'app/extensions/ajax';
import 'app/extensions/flashMessage';
import 'app/extensions/projectSorter';
import 'app/extensions/taskSorter';
import 'app/extensions/sectionSorter';
import 'app/extensions/subtaskSorter';
import 'app/extensions/removeRow';

// Webcomponents
import 'app/webcomponents/dropDown.ts';
import 'app/webcomponents/modalWindow.ts';
import 'app/webcomponents/selectBox.ts';
import 'app/webcomponents/dueOn.ts';
import 'app/webcomponents/markdownText.ts';

// Setup CSRF tokens.
axios.defaults.xsrfCookieName = 'csrfToken';
axios.defaults.xsrfHeaderName = 'X-Csrf-Token';

const el = document.getElementById('app');
if (!el) {
  console.log('Could not find application root element');
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
