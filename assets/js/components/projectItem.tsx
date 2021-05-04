import { useState } from 'react';
import {InertiaLink, usePage} from '@inertiajs/inertia-react';
import classnames from 'classnames';

import {Project} from 'app/types';
import ProjectBadge from './projectBadge';
import ProjectMenu from './projectMenu';

type Props = {
  project: Project;
};

export default function ProjectItem({project}: Props): JSX.Element {
  const path = `/projects/${project.slug}`;
  const [active, setActive] = useState(false);
  const page = usePage();
  const className = classnames('project-item', {
    active: page.url.indexOf(path) > 0,
  });

  return (
    <div
      className={className}
      onMouseEnter={() => setActive(true)}
      onMouseLeave={() => setActive(false)}
    >
      <InertiaLink key={project.slug} href={path}>
        <ProjectBadge project={project} />
        <span className="counter">{project.incomplete_task_count.toLocaleString()}</span>
      </InertiaLink>
      <ProjectMenu project={project} onClick={() => setActive(!active)} />
    </div>
  );
}
