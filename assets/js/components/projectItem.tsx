import React from 'react';
import {InertiaLink} from '@inertiajs/inertia-react';

import {Project} from 'app/types';
import {archiveProject, unarchiveProject} from 'app/actions/projects';
import ContextMenu from 'app/components/contextMenu';
import ProjectBadge from 'app/components/projectBadge';
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
      <ContextMenu>
        <li>
          <InertiaLink className="context-item" href={`/projects/${project.slug}/edit`}>
            <InlineIcon icon="pencil" />
            Edit Project
          </InertiaLink>
        </li>
        {project.archived ? (
          <li>
            <button className="context-item" onClick={() => unarchiveProject(project)}>
              <InlineIcon icon="archive" />
              Unarchive Project
            </button>
          </li>
        ) : (
          <li>
            <button className="context-item" onClick={() => archiveProject(project)}>
              <InlineIcon icon="archive" />
              Archive Project
            </button>
          </li>
        )}
      </ContextMenu>
    </div>
  );
}
