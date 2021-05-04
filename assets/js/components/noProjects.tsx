import {InertiaLink} from '@inertiajs/inertia-react';

import {t} from 'app/locale';

function NoProjects(): JSX.Element {
  return (
    <div className="no-projects">
      <h1>{t('You have no projects')}</h1>
      <p>{t('Projects help organize your tasks')}</p>
      <div className="button-bar">
        <InertiaLink href="/projects/add" className="button-primary">
          {t('Create a Project')}
        </InertiaLink>
      </div>
    </div>
  );
}

export default NoProjects;
