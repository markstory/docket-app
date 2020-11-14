import React from 'react';
import {usePage} from '@inertiajs/inertia-react';

import {FlashMessage, Project} from 'app/types';

import FlashMessages from 'app/components/flashMessages';
import ProjectFilter from 'app/components/projectFilter';

type SharedPageProps = {
  props: {
    flash: null | FlashMessage;
    projects: Project[];
  };
};

type Props = {
  children: React.ReactNode;
};

function LoggedIn({children}: Props) {
  const {flash, projects} = usePage<SharedPageProps>().props;

  return (
    <main>
      <FlashMessages flash={flash} />
      <section>
        <ProjectFilter projects={projects} />
      </section>
      <section>{children}</section>
    </main>
  );
}

export default LoggedIn;
