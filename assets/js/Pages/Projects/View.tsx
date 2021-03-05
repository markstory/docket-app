import React, {useState} from 'react';
import {SortableContext, verticalListSortingStrategy} from '@dnd-kit/sortable';
import {InertiaLink} from '@inertiajs/inertia-react';

import {t} from 'app/locale';
import {Project, ProjectSection, Task} from 'app/types';
import {Icon} from 'app/components/icon';
import LoggedIn from 'app/layouts/loggedIn';
import ProjectMenu from 'app/components/projectMenu';
import TaskGroup from 'app/components/taskGroup';
import TaskList from 'app/components/taskList';
import SectionAddForm from 'app/components/sectionAddForm';
import SectionContainer from 'app/components/sectionContainer';
import ProjectSectionSorter from 'app/components/projectSectionSorter';

type Props = {
  project: Project;
  tasks: Task[];
  completed?: Task[];
};

export default function ProjectsView({completed, project, tasks}: Props): JSX.Element {
  const [showAddSection, setShowAddSection] = useState(false);
  function handleCancelSection() {
    setShowAddSection(false);
  }

  return (
    <LoggedIn title={t('{project} Project', {project: project.name})}>
      <div className="project-view">
        <div className="heading" data-archived={project.archived}>
          <h1>
            {project.archived && <Icon icon="archive" />}
            {project.name}
          </h1>

          <ProjectMenu
            project={project}
            onAddSection={() => setShowAddSection(true)}
            showDetailed
          />
        </div>

        <div className="attributes">
          {project.archived && <span className="archived">{t('Archived')}</span>}
        </div>
        <ProjectSectionSorter tasks={tasks} sections={project.sections}>
          {({groups, activeTask, activeSection}) => {
            return groups.map(({key, section, tasks}) => {
              if (section === undefined) {
                return (
                  <TaskGroup
                    dropId={key}
                    activeTask={activeTask}
                    tasks={tasks}
                    defaultProjectId={project.id}
                    showAdd={!project.archived}
                    showDueOn
                  />
                );
              }
              return (
                <SectionContainer
                  key={key}
                  id={key}
                  active={activeSection}
                  project={project}
                  section={section}
                >
                  <TaskGroup
                    dropId={key}
                    activeTask={activeTask}
                    tasks={tasks}
                    defaultProjectId={project.id}
                    showAdd={!project.archived}
                    showDueOn
                  />
                </SectionContainer>
              );
            });
          }}
        </ProjectSectionSorter>
        {showAddSection && (
          <SectionAddForm project={project} onCancel={handleCancelSection} />
        )}
        {completed && (
          <React.Fragment>
            <TaskList title={t('Completed')} tasks={completed} showDueOn />
            <div className="button-bar">
              <InertiaLink
                className="button button-muted"
                href={`/projects/${project.slug}`}
              >
                {t('Hide completed tasks')}
              </InertiaLink>
            </div>
          </React.Fragment>
        )}
      </div>
    </LoggedIn>
  );
}
