import React from 'react';
import {InertiaLink} from '@inertiajs/inertia-react';

import {archiveProject, deleteProject, unarchiveProject} from 'app/actions/projects';
import {Project, Task} from 'app/types';
import {InlineIcon} from 'app/components/icon';
import LoggedIn from 'app/layouts/loggedIn';
import TaskGroup from 'app/components/taskGroup';
import TaskSorter from 'app/components/taskSorter';

type Props = {
  project: Project;
  tasks: Task[];
};

export default function TasksIndex({project, tasks}: Props) {
  return (
    <LoggedIn>
      <h1>{project.name} Tasks</h1>
      <div className="button-bar">
        {project.archived && (
          <button className="button-default" onClick={() => unarchiveProject(project)}>
            <InlineIcon icon="archive" />
            Unarchive
          </button>
        )}
        {!project.archived && (
          <button className="button-default" onClick={() => archiveProject(project)}>
            <InlineIcon icon="archive" />
            Archive
          </button>
        )}
        <button className="button-default" onClick={() => deleteProject(project)}>
          <InlineIcon icon="trash" />
          Delete
        </button>
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
            showDueOn
          />
        )}
      </TaskSorter>
    </LoggedIn>
  );
}
