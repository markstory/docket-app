import React from 'react';
import {InertiaLink} from '@inertiajs/inertia-react';

import {Project} from 'app/types';
import ProjectBadge from 'app/components/projectBadge';
import ProjectMenu from 'app/components/projectMenu';
import {InlineIcon} from './icon';

type Props = {
  project: Project;
};

export default function ProjectItem({project}: Props) {
  return (
    <div className="project-item">
      <InertiaLink key={project.slug} href={`/projects/${project.slug}`}>
        <ProjectBadge project={project} />
      </InertiaLink>
      <ProjectMenu project={project} />
    </div>
  );
}
