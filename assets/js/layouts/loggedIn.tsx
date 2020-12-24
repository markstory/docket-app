import React, {useEffect} from 'react';
import {usePage} from '@inertiajs/inertia-react';

import {FlashMessage, Project} from 'app/types';

import FlashMessages from 'app/components/flashMessages';
import ProjectFilter from 'app/components/projectFilter';
import {ProjectsProvider} from 'app/providers/projects';

type SharedPageProps = {
  flash: null | FlashMessage;
  projects: Project[];
};

type Props = {
  children: React.ReactNode;
};

function LoggedIn({children}: Props) {
  const {projects} = usePage().props as SharedPageProps;
  return (
    <ProjectsProvider projects={projects}>
      <Contents>{children}</Contents>
    </ProjectsProvider>
  );
}

/**
 * For the ProjectsProvider component to work it needs to be
 * wrapping components that want to call useProjects().
 *
 * For other elements to be able to access projects they need
 * to be another layers of components down.
 */
function Contents({children}: Props) {
  const {flash} = usePage().props as SharedPageProps;

  return (
    <React.Fragment>
      <main className="layout-three-quarter">
        <section className="sidebar">
          <ProjectFilter />
        </section>
        <section className="content">
          {children}
          <FlashMessages flash={flash} />
        </section>
      </main>
    </React.Fragment>
  );
}

export default LoggedIn;
