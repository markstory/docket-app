import React from 'react';
import {InertiaLink} from '@inertiajs/inertia-react';

import {t} from 'app/locale';
import ProjectSorter from 'app/components/projectSorter';
import {InlineIcon} from './icon';

function ProjectFilter(): JSX.Element {
  return (
    <div className="project-filter">
      <ul className="links">
        <li>
          <InertiaLink href="/tasks/today">
            <InlineIcon icon="clippy" className="today" />
            {t('Today')}
          </InertiaLink>
        </li>
        <li>
          <InertiaLink href="/tasks/upcoming">
            <InlineIcon icon="calendar" className="upcoming" />
            {t('Upcoming')}
          </InertiaLink>
        </li>
      </ul>
      <h3>{t('Projects')}</h3>
      <ProjectSorter />
      <div className="button-bar-vertical">
        <InertiaLink className="button-sidebar-action-primary " href="/projects/add">
          <InlineIcon icon="plus" />
          {t('New Project')}
        </InertiaLink>
        <InertiaLink className="button-sidebar-action" href="/projects/archived">
          {t('Archived Projects')}
        </InertiaLink>
      </div>
    </div>
  );
}

export default ProjectFilter;
