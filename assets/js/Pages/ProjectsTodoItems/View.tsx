import React, {useState} from 'react';

import {FlashMessage, Project, TodoItem} from 'app/types';
import TodoItemRow from 'app/components/todoItemRow';
import QuickAddTaskForm from 'app/forms/quickAddTaskForm';
import LoggedIn from 'app/layouts/loggedIn';

type Props = {
  _csrfToken: string;
  project: Project;
  projects: Project[];
  flash: null | FlashMessage;
  todoItems: TodoItem[];
};

export default function TodoItemsIndex({
  project,
  flash,
  projects,
  todoItems,
  _csrfToken,
}: Props) {
  const [showForm, setShowForm] = useState(false);

  return (
    <LoggedIn projects={projects} flash={flash}>
      <h1>{project.name} Tasks</h1>
      {todoItems.map(todo => (
        <TodoItemRow todo={todo} key={todo.id} />
      ))}
      <div>
        {!showForm && <button onClick={() => setShowForm(true)}>Add Task</button>}
        {showForm && (
          <QuickAddTaskForm
            projects={projects}
            csrfToken={_csrfToken}
            onCancel={() => setShowForm(false)}
          />
        )}
      </div>
    </LoggedIn>
  );
}
