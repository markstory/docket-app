import React, {useEffect} from 'react';
import {usePage} from '@inertiajs/inertia-react';

import {FlashMessage} from 'app/types';

import FlashMessages from 'app/components/flashMessages';

type SharedPageProps = {
  flash: null | FlashMessage;
};

type Props = {
  children: React.ReactNode;
  title?: string;
};

/**
 * Simple Layout that wraps children in a centered
 * container card element
 */
function Card({children, title}: Props): JSX.Element {
  const {flash} = usePage().props as SharedPageProps;
  useEffect(() => {
    if (title) {
      document.title = title;
    }
  }, [title]);

  return (
    <div className="layout-card-bg">
      <main className="layout-card">
        <section className="content">{children}</section>
      </main>
      <FlashMessages flash={flash} />
    </div>
  );
}

export default Card;
