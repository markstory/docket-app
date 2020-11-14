import React from 'react';
import {usePage} from '@inertiajs/inertia-react';

import {FlashMessage, Project} from 'app/types';

import FlashMessages from 'app/components/flashMessages';
import ProjectFilter from 'app/components/projectFilter';
import ProjectsContext from 'app/components/projectsContext';

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
    <ProjectsContext.Provider value={projects}>
      <main>
        <FlashMessages flash={flash} />
        <section>
          <ProjectFilter />
        </section>
        <section>{children}</section>
      </main>
    </ProjectsContext.Provider>
  );
}

export default LoggedIn;
