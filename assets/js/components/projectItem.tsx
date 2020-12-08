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
  function handleUnarchive(project: Project) {
    Inertia.post(`/projects/${project.slug}/unarchive`);
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
        {project.archived ? (
          <li>
            <button className="button-default" onClick={() => handleUnarchive(project)}>
              Unarchive Project
            </button>
          </li>
        ) : (
          <li>
            <button className="button-default" onClick={() => handleArchive(project)}>
              Archive Project
            </button>
          </li>
        )}
      </ContextMenu>
    </div>
  );
}
