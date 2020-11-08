import React from 'react';
import {InertiaApp} from '@inertiajs/inertia-react';
import {render} from 'react-dom';

const el = document.getElementById('app');
if (!el) {
  throw new Error('Could not find application root element');
}

render(
  <InertiaApp
    initialPage={JSON.parse(el.dataset.page || '')}
    resolveComponent={(name: string) => require(`./Pages/${name}`).default}
  />,
  el
);
