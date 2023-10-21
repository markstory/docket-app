import {usePage} from '@inertiajs/inertia-react';
import classnames from 'classnames';

import {Project} from 'app/types';
import ProjectBadge from './projectBadge';

type Props = {
  project: Project;
};

export default function ProjectItem({project}: Props): JSX.Element {
  const path = `/projects/${project.slug}`;
  const page = usePage();
  const className = classnames('project-item', {
    active: page.url.indexOf(path) > 0,
  });

  return (
    <div className={className}>
      <a key={project.slug} href={path}>
        <ProjectBadge project={project} />
        <span className="counter">{project.incomplete_task_count.toLocaleString()}</span>
      </a>
    </div>
  );
}
