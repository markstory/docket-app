import React, {useState} from 'react';
import {InertiaLink} from '@inertiajs/inertia-react';

import {Project} from 'app/types';
import ProjectBadge from 'app/components/projectBadge';
import ProjectMenu from 'app/components/projectMenu';

type Props = {
  project: Project;
};

export default function ProjectItem({project}: Props) {
  const [active, setActive] = useState(false);
  return (
    <div className="project-item" data-active={active}>
      <InertiaLink key={project.slug} href={`/projects/${project.slug}`}>
        <ProjectBadge project={project} />
        <span className="counter">{project.incomplete_task_count.toLocaleString()}</span>
      </InertiaLink>
      <ProjectMenu
        project={project}
        onOpen={() => setActive(true)}
        onClose={() => setActive(false)}
      />
    </div>
  );
}
