import React, {useState} from 'react';
import {InertiaLink} from '@inertiajs/inertia-react';

import {Project} from 'app/types';
import ProjectBadge from 'app/components/projectBadge';
import ProjectMenu from 'app/components/projectMenu';

type Props = {
  project: Project;
};

export default function ProjectItem({project}: Props): JSX.Element {
  const [active, setActive] = useState(false);
  return (
    <div
      className="project-item"
      onMouseEnter={() => setActive(true)}
      onMouseLeave={() => setActive(false)}
    >
      <InertiaLink key={project.slug} href={`/projects/${project.slug}`}>
        <ProjectBadge project={project} />
        <span className="counter">{project.incomplete_task_count.toLocaleString()}</span>
      </InertiaLink>
      <ProjectMenu project={project} onClick={() => setActive(!active)} />
    </div>
  );
}
