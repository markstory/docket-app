import {useEffect, useState} from 'react';
import {Inertia} from '@inertiajs/inertia';
import {usePage} from '@inertiajs/inertia-react';

import {FlashMessage, Project, User} from 'app/types';

import FlashMessages from 'app/components/flashMessages';
import {Icon} from 'app/components/icon';
import HelpModal from 'app/components/helpModal';
import ProjectFilter from 'app/components/projectFilter';
import ProfileMenu from 'app/components/profileMenu';
import {t} from 'app/locale';
import {ProjectsProvider} from 'app/providers/projects';
import useKeyboardShortcut from 'app/utils/useKeyboardShortcut';

type SharedPageProps = {
  flash: null | FlashMessage;
  projects: Project[];
  identity: User;
};

type Props = {
  title?: string;
  children: React.ReactNode;
};

function LoggedIn({children, title}: Props) {
  const {projects, identity} = usePage().props as SharedPageProps;

  useEffect(() => {
    if (title) {
      document.title = title;
    }
    if (identity.theme !== 'system') {
      document.body.classList.remove(
        ...[...document.body.classList].filter(item => item.startsWith('theme-'))
      );
      document.body.classList.add(`theme-${identity.theme}`);
    } else if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
      document.body.classList.add('theme-dark');
    }
  }, [title, identity]);

  const generationId = projects.reduce((acc, project) => {
    const currentId = [project.name, project.color, project.incomplete_task_count];
    return currentId.join(':') + ':' + acc;
  }, '');

  return (
    <ProjectsProvider generationId={generationId} projects={projects}>
      <Contents>{children}</Contents>
    </ProjectsProvider>
  );
}

/**
 * For the ProjectsProvider component to work it needs to be
 * wrapping components that want to call useProjects().
 *
 * For other elements to be able to access projects they need
 * to be another layers of components down.
 */
function Contents({children}: Props) {
  const {flash} = usePage().props as SharedPageProps;
  const [expanded, setExpanded] = useState(false);
  const [showHelp, setShowHelp] = useState(false);

  // Keyboard shortcuts.
  useKeyboardShortcut(['t'], () => {
    Inertia.visit('/tasks/today');
  });
  useKeyboardShortcut(['u'], () => {
    Inertia.visit('/tasks/upcoming');
  });
  useKeyboardShortcut(['?'], () => {
    setShowHelp(true);
  });

  return (
    <React.Fragment>
      <main
        className="layout-three-quarter"
        data-expanded={expanded}
        data-testid="loggedin"
      >
        <section className="sidebar">
          <div className="menu">
            <div>
              <ProfileMenu />
              <ProjectFilter />
            </div>
            <img src="/img/docket-logo-translucent.svg" width="30" height="30" />
          </div>
          <button
            className="expander"
            title={t('Show project menu')}
            onClick={() => setExpanded(!expanded)}
          >
            <Icon icon="kebab" width="large" />
          </button>
        </section>
        <section className="content">
          {children}
          <FlashMessages flash={flash} />
          {showHelp && <HelpModal onClose={() => setShowHelp(false)} />}
        </section>
      </main>
    </React.Fragment>
  );
}

export default LoggedIn;
