import {MenuItem, MenuLink} from '@reach/menu-button';
import {InertiaLink} from '@inertiajs/inertia-react';

import {Project} from 'app/types';
import {t} from 'app/locale';
import {archiveProject, deleteProject, unarchiveProject} from 'app/actions/projects';
import ContextMenu from 'app/components/contextMenu';
import {InlineIcon} from './icon';
import {useProjects} from 'app/providers/projects';

type Props = {
  project: Project;
  showDetailed?: boolean;
  onAddSection?: () => void;
  onClick?: (event: React.MouseEvent) => void;
};

export default function ProjectMenu({
  project,
  onClick,
  onAddSection,
  showDetailed = false,
}: Props): JSX.Element {
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
    <ContextMenu onClick={onClick} tooltip={t('Project Actions')}>
      <MenuLink className="edit" href={`/projects/${project.slug}/edit`}>
        <InlineIcon icon="pencil" />
        {t('Edit Project')}
      </MenuLink>
      {showDetailed && onAddSection && (
        <MenuLink className="complete" onSelect={onAddSection} data-testid="add-section">
          <InlineIcon icon="plus" />
          {t('Add section')}
        </MenuLink>
      )}
      {showDetailed && (
        <MenuLink
          as={InertiaLink}
          className="complete"
          href={`/projects/${project.slug}?completed=1`}
        >
          <InlineIcon icon="check" />
          {t('View completed tasks')}
        </MenuLink>
      )}
      <div className="separator" />
      {project.archived ? (
        <MenuItem className="archive" onSelect={handleUnarchive}>
          <InlineIcon icon="archive" />
          {t('Unarchive Project')}
        </MenuItem>
      ) : (
        <MenuItem className="archive" onSelect={handleArchive}>
          <InlineIcon icon="archive" />
          {t('Archive Project')}
        </MenuItem>
      )}
      <MenuItem className="delete" onSelect={handleDelete}>
        <InlineIcon icon="trash" />
        {t('Delete Project')}
      </MenuItem>
    </ContextMenu>
  );
}
