import React from 'react';

import {FlashMessage, Project} from 'app/types';

import FlashMessages from 'app/components/flashMessages';
import ProjectFilter from 'app/components/projectFilter';

type Props = {
  flash: null | FlashMessage;
  projects: Project[];
  children: React.ReactNode;
};

function LoggedIn({children, flash, projects}: Props) {
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
