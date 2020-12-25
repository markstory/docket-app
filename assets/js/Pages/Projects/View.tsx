import React from 'react';
import {InertiaLink} from '@inertiajs/inertia-react';

import {Project, Task} from 'app/types';
import {Icon} from 'app/components/icon';
import LoggedIn from 'app/layouts/loggedIn';
import ProjectMenu from 'app/components/projectMenu';
import TaskGroup from 'app/components/taskGroup';
import TaskList from 'app/components/taskList';
import TaskSorter from 'app/components/taskSorter';

type Props = {
  project: Project;
  tasks: Task[];
  completed?: Task[];
};

export default function ProjectsView({completed, project, tasks}: Props) {
  return (
    <LoggedIn>
      <div className="project-view">
        <div className="heading">
          <h1>
            {project.archived && <Icon icon="archive" />}
            {project.name} Tasks
          </h1>

          <ProjectMenu project={project} showAll alignMenu="right" />
        </div>

        <div className="attributes">
          {project.archived && <span className="archived">Archived</span>}
        </div>
        <TaskSorter tasks={tasks} scope="child">
          {({items}) => (
            <TaskGroup
              dropId="project"
              tasks={items}
              defaultProjectId={project.id}
              showAdd={!project.archived}
              showDueOn
            />
          )}
        </TaskSorter>
        {completed && (
          <React.Fragment>
            <TaskList title="Completed" tasks={completed} showDueOn />
            <InertiaLink
              className="button button-muted"
              href={`/projects/${project.slug}`}
            >
              Hide completed tasks
            </InertiaLink>
          </React.Fragment>
        )}
      </div>
    </LoggedIn>
  );
}
