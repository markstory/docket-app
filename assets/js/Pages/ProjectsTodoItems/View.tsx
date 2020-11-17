import React from 'react';

import {Project, TodoItem} from 'app/types';
import LoggedIn from 'app/layouts/loggedIn';
import TodoItemGroup from 'app/components/todoItemGroup';

type Props = {
  project: Project;
  todoItems: TodoItem[];
};

export default function TodoItemsIndex({project, todoItems}: Props) {
  function handleChange(items: TodoItem[]) {
    console.log('new items', items);
  }
  return (
    <LoggedIn>
      <h1>{project.name} Tasks</h1>
      <TodoItemGroup
        onReorder={handleChange}
        todoItems={todoItems}
        defaultProjectId={project.id}
        showDueOn
      />
    </LoggedIn>
  );
}
