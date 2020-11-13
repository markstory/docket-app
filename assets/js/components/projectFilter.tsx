import React from 'react';
import {InertiaLink} from '@inertiajs/inertia-react';

import ProjectBadge from 'app/components/projectBadge';
import {Project} from 'app/types';

type Props = {
  projects: Project[];
};

function ProjectFilter({projects}: Props) {
  return (
    <div>
      <h3>Projects</h3>
      <ul>
        {projects.map(project => (
          <li key={project.slug}>
            <InertiaLink href={`/projects/${project.slug}/todos`}>
              <ProjectBadge project={project} />
            </InertiaLink>
          </li>
        ))}
      </ul>
    </div>
  );
}

export default ProjectFilter;
