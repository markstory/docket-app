import React from 'react';

import {Project, TodoItem} from 'app/types';
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
      <div className="attributes">
        {project.archived && <span className="archived">Archived</span>}
      </div>
      <TodoItemSorter todoItems={todoItems} scope="child">
        {({handleOrderChange, items}) => (
          <TodoItemGroup
            onReorder={handleOrderChange}
            todoItems={items}
            defaultProjectId={project.id}
            showDueOn
          />
        )}
      </TodoItemSorter>
    </LoggedIn>
  );
}
