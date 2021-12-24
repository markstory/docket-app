import {Fragment, useEffect, useState, useRef} from 'react';
import {Inertia} from '@inertiajs/inertia';
import {usePage} from '@inertiajs/inertia-react';

import {FlashMessage, Project, User} from 'app/types';

import FlashMessages from 'app/components/flashMessages';
import {Icon} from 'app/components/icon';
import GlobalTaskCreate from 'app/components/globalTaskCreate';
import HelpModal from 'app/components/helpModal';
import ProjectFilter from 'app/components/projectFilter';
import ProfileMenu from 'app/components/profileMenu';
import useKeyboardShortcut from 'app/hooks/useKeyboardShortcut';
import {t} from 'app/locale';
import {ProjectsProvider} from 'app/providers/projects';
import DefaultTaskValuesStore from 'app/providers/defaultTaskValues';

type SharedPageProps = {
  flash: null | FlashMessage;
  projects: Project[];
  identity: User;
};

type Props = {
  children: React.ReactNode;
  title?: string;
};
// 30 Minutes
const VISIBILITY_TIMEOUT = 60 * 30 * 1000;

function LoggedIn({children, title}: Props) {
  const {projects, identity} = usePage().props as SharedPageProps;
  const hiddenTime = useRef(0);

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

  // Reload the application if data could be stale.
  // A simple timeout is a rough solution. A more refined
  // solution would be to keep track of 'revision number' or timestamp
  // of when the page was loaded and then check if the server
  // has a newer timestamp/revision.
  useEffect(() => {
    function listener() {
      if (document.visibilityState === 'hidden') {
        hiddenTime.current = Date.now();
      }
      if (document.visibilityState === 'visible') {
        const now = Date.now();
        if (now - hiddenTime.current > VISIBILITY_TIMEOUT) {
          Inertia.reload();
        }
      }
    }
    window.addEventListener('visibilitychange', listener);
    return function cleanup() {
      window.removeEventListener('visibilitychange', listener);
    };
  }, []);

  const generationId = projects.reduce((acc, project) => {
    const currentId = [project.name, project.color, project.incomplete_task_count];
    return currentId.join(':') + ':' + acc;
  }, '');

  return (
    <DefaultTaskValuesStore>
      <ProjectsProvider generationId={generationId} projects={projects}>
        <Contents>{children}</Contents>
      </ProjectsProvider>
    </DefaultTaskValuesStore>
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
    <Fragment>
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
      <GlobalTaskCreate />
    </Fragment>
  );
}

export default LoggedIn;
