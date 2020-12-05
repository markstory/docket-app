import React from 'react';
import {Inertia} from '@inertiajs/inertia';
import {InertiaLink} from '@inertiajs/inertia-react';

import {Project} from 'app/types';
import ContextMenu from 'app/components/contextMenu';
import ProjectBadge from 'app/components/projectBadge';

type Props = {
  project: Project;
};

export default function ProjectItem({project}: Props) {
  function handleArchive(project: Project) {
    Inertia.post(`/projects/${project.slug}/archive`);
  }

  return (
    <div className="project-item">
      <InertiaLink key={project.slug} href={`/projects/${project.slug}`}>
        <ProjectBadge project={project} />
      </InertiaLink>
      <ContextMenu>
        <li>
          <InertiaLink href={`/projects/${project.slug}/edit`}>Edit Project</InertiaLink>
        </li>
        <li>
          <button className="button-default" onClick={() => handleArchive(project)}>
            Archive Project
          </button>
        </li>
        <li>Delete Project</li>
      </ContextMenu>
    </div>
  );
}
