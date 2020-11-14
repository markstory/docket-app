import React from 'react';
import {InertiaLink} from '@inertiajs/inertia-react';

import ProjectBadge from 'app/components/projectBadge';
import ProjectsContext from 'app/components/projectsContext';

function ProjectFilter() {
  return (
    <div>
      <ul>
        <li>
          <InertiaLink href="/todos/today">Today</InertiaLink>
        </li>
        <li>
          <InertiaLink href="/todos/upcoming">Upcoming</InertiaLink>
        </li>
      </ul>
      <h3>Projects</h3>
      <ul>
        <ProjectsContext.Consumer>
          {projects =>
            projects.map(project => (
              <li key={project.slug}>
                <InertiaLink href={`/projects/${project.slug}/todos`}>
                  <ProjectBadge project={project} />
                </InertiaLink>
              </li>
            ))
          }
        </ProjectsContext.Consumer>
      </ul>
    </div>
  );
}

export default ProjectFilter;
