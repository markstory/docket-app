import React from 'react';

import {Project, TodoItem} from 'app/types';
import LoggedIn from 'app/layouts/loggedIn';
import TodoItemGroup from 'app/components/todoItemGroup';

type Props = {
  project: Project;
  todoItems: TodoItem[];
};

export default function TodoItemsIndex({project, todoItems}: Props) {
  return (
    <LoggedIn>
      <h1>{project.name} Tasks</h1>
      <TodoItemGroup todoItems={todoItems} defaultProjectId={project.id} />
    </LoggedIn>
  );
}
