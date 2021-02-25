import React from 'react';
import {InertiaLink} from '@inertiajs/inertia-react';

import {t} from 'app/locale';
import {Project, ProjectSection, Task} from 'app/types';
import {Icon} from 'app/components/icon';
import LoggedIn from 'app/layouts/loggedIn';
import ProjectMenu from 'app/components/projectMenu';
import TaskGroup from 'app/components/taskGroup';
import TaskList from 'app/components/taskList';
import TaskSorter from 'app/components/taskSorter';

type SectionTaskMap = Record<number, Task[]>;

type Props = {
  project: Project;
  tasks: Task[];
  completed?: Task[];
};

export default function ProjectsView({completed, project, tasks}: Props): JSX.Element {
  const rootTasks = tasks.filter(task => task.project_section_id === null);
  const sectionTasks = project.sections.reduce<SectionTaskMap>((acc, section) => {
    acc[section.id] = tasks.filter(task => task.project_section_id === section.id);
    return acc;
  }, {});

  return (
    <LoggedIn title={t('{project} Project', {project: project.name})}>
      <div className="project-view">
        <div className="heading" data-archived={project.archived}>
          <h1>
            {project.archived && <Icon icon="archive" />}
            {project.name}
          </h1>

          <ProjectMenu project={project} showAll />
        </div>

        <div className="attributes">
          {project.archived && <span className="archived">{t('Archived')}</span>}
        </div>
        <TaskSorter tasks={rootTasks} scope="child" showDueOn>
          {({items, activeTask}) => (
            <TaskGroup
              dropId="project"
              activeTask={activeTask}
              tasks={items}
              defaultProjectId={project.id}
              showAdd={!project.archived}
              showDueOn
            />
          )}
        </TaskSorter>
        {project.sections.map(section => {
          return (
            <SectionControls key={section.id} section={section}>
              <TaskSorter tasks={sectionTasks[section.id]} scope="child" showDueOn>
                {({items, activeTask}) => (
                  <TaskGroup
                    dropId="project"
                    activeTask={activeTask}
                    tasks={items}
                    defaultProjectId={project.id}
                    showAdd={!project.archived}
                    showDueOn
                  />
                )}
              </TaskSorter>
            </SectionControls>
          );
        })}
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

type SectionProps = React.PropsWithChildren<{
  section: ProjectSection;
}>;

function SectionControls({children, section}: SectionProps) {
  return (
    <div className="section-controls">
      <h3>{section.name}</h3>
      {children}
    </div>
  );
}
