import React from 'react';
import {InertiaLink} from '@inertiajs/inertia-react';

import {Project} from 'app/types';
import {t} from 'app/locale';
import {archiveProject, deleteProject, unarchiveProject} from 'app/actions/projects';
import ContextMenu from 'app/components/contextMenu';
import {InlineIcon} from './icon';
import {useProjects} from 'app/providers/projects';

type ContextMenuProps = React.ComponentProps<typeof ContextMenu>;

type Props = {
  project: Project;
  showAll?: boolean;
  alignMenu?: ContextMenuProps['alignMenu'];
  onOpen?: ContextMenuProps['onOpen'];
  onClose?: ContextMenuProps['onClose'];
};

export default function ProjectMenu({
  project,
  onOpen,
  onClose,
  showAll = false,
  alignMenu = 'left',
}: Props) {
  const [_, setProjects] = useProjects();

  async function handleDelete() {
    await deleteProject(project);
    setProjects(null);
  }
  async function handleUnarchive() {
    await unarchiveProject(project);
    setProjects(null);
  }
  async function handleArchive() {
    await archiveProject(project);
    setProjects(null);
  }

  return (
    <ContextMenu alignMenu={alignMenu} onClose={onClose} onOpen={onOpen}>
      {showAll && (
        <li>
          <InertiaLink
            className="context-item"
            href={`/projects/${project.slug}?completed=1`}
          >
            <InlineIcon icon="check" />
            {t('View completed tasks')}
          </InertiaLink>
        </li>
      )}
      <li>
        <InertiaLink className="context-item" href={`/projects/${project.slug}/edit`}>
          <InlineIcon icon="pencil" />
          {t('Edit Project')}
        </InertiaLink>
      </li>
      {project.archived ? (
        <li>
          <button className="context-item" onClick={handleUnarchive}>
            <InlineIcon icon="archive" />
            {t('Unarchive Project')}
          </button>
        </li>
      ) : (
        <li>
          <button className="context-item" onClick={handleArchive}>
            <InlineIcon icon="archive" />
            {t('Archive Project')}
          </button>
        </li>
      )}
      <li>
        <button className="context-item" onClick={handleDelete}>
          <InlineIcon icon="trash" />
          {t('Delete Project')}
        </button>
      </li>
    </ContextMenu>
  );
}
