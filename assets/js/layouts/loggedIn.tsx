import React, {useEffect} from 'react';
import {usePage} from '@inertiajs/inertia-react';

import {FlashMessage, Project} from 'app/types';

import FlashMessages from 'app/components/flashMessages';
import ProjectFilter from 'app/components/projectFilter';
import {useProjects, ProjectsProvider} from 'app/providers/projects';

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
  return (
    <ProjectsProvider>
      <Contents>{children}</Contents>
    </ProjectsProvider>
  );
}

/**
 * For the ProjectsProvider component to work it needs to be
 * wrapping components that want to call useProjects().
 */
function Contents({children}: Props) {
  const {flash, projects} = usePage<SharedPageProps>().props;
  const [_, setProjects] = useProjects();

  useEffect(() => {
    setProjects(projects);
  }, [projects]);

  return (
    <React.Fragment>
      <main className="layout-three-quarter">
        <section>
          <ProjectFilter />
        </section>
        <section>
          <FlashMessages flash={flash} />
          {children}
        </section>
      </main>
    </React.Fragment>
  );
}

export default LoggedIn;
