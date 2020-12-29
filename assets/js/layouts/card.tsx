import React from 'react';
import {usePage} from '@inertiajs/inertia-react';

import {FlashMessage} from 'app/types';

import FlashMessages from 'app/components/flashMessages';

type SharedPageProps = {
  flash: null | FlashMessage;
};

type Props = {
  children: React.ReactNode;
};

/**
 * Simple Layout that wraps children in a centered
 * container card element
 */
function Card({children}: Props) {
  const {flash} = usePage().props as SharedPageProps;

  return (
    <div className="layout-card-bg">
      <main className="layout-card">
        <section className="content">
          {children}
          <FlashMessages flash={flash} />
        </section>
      </main>
    </div>
  );
}

export default Card;
