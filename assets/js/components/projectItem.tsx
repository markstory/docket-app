import React from 'react';
import {InertiaLink} from '@inertiajs/inertia-react';

import {Project} from 'app/types';
import {archiveProject, unarchiveProject} from 'app/actions/projects';
import ContextMenu from 'app/components/contextMenu';
import ProjectBadge from 'app/components/projectBadge';

type Props = {
  project: Project;
};

export default function ProjectItem({project}: Props) {
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
            <button className="button-default" onClick={() => unarchiveProject(project)}>
              Unarchive Project
            </button>
          </li>
        ) : (
          <li>
            <button className="button-default" onClick={() => archiveProject(project)}>
              Archive Project
            </button>
          </li>
        )}
      </ContextMenu>
    </div>
  );
}
