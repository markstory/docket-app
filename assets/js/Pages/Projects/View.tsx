import React from 'react';
import {InertiaLink} from '@inertiajs/inertia-react';

import {archiveProject, deleteProject, unarchiveProject} from 'app/actions/projects';
import {Project, TodoItem} from 'app/types';
import {InlineIcon} from 'app/components/icon';
import LoggedIn from 'app/layouts/loggedIn';
import TodoItemGroup from 'app/components/todoItemGroup';
import TodoItemSorter from 'app/components/todoItemSorter';

type Props = {
  project: Project;
  todoItems: TodoItem[];
};

export default function TodoItemsIndex({project, todoItems}: Props) {
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
      <TodoItemSorter todoItems={todoItems} scope="child">
        {({items}) => (
          <TodoItemGroup
            dropId="project"
            todoItems={items}
            defaultProjectId={project.id}
            showDueOn
          />
        )}
      </TodoItemSorter>
    </LoggedIn>
  );
}
