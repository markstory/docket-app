import {t} from 'app/locale';
import ProjectSorter from 'app/components/projectSorter';
import NavLink from './navLink';
import {InlineIcon} from './icon';

function ProjectFilter(): JSX.Element {
  return (
    <div className="project-filter">
      <ul className="links">
        <li>
          <NavLink href="/tasks/today">
            <InlineIcon icon="clippy" className="today" />
            {t('Today')}
          </NavLink>
        </li>
        <li>
          <NavLink href="/tasks/upcoming">
            <InlineIcon icon="calendar" className="upcoming" />
            {t('Upcoming')}
          </NavLink>
        </li>
      </ul>
      <h3>{t('Projects')}</h3>
      <ProjectSorter />
      <div className="secondary-actions">
        <NavLink className="action-primary" href="/projects/add">
          <InlineIcon icon="plus" />
          {t('New Project')}
        </NavLink>
        <NavLink className="action-secondary" href="/projects/archived">
          <InlineIcon icon="archive" />
          {t('Archived Projects')}
        </NavLink>
        <NavLink className="action-secondary" href="/tasks/deleted">
          <InlineIcon icon="trash" />
          {t('Trash Bin')}
        </NavLink>
      </div>
    </div>
  );
}

export default ProjectFilter;
