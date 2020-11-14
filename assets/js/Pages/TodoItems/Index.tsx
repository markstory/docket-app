import React, {useState} from 'react';

import {Project, TodoItem} from 'app/types';
import TodoItemRow from 'app/components/todoItemRow';
import QuickAddTaskForm from 'app/forms/quickAddTaskForm';
import LoggedIn from 'app/layouts/loggedIn';

type Props = {
  projects: Project[];
  todoItems: TodoItem[];
};

export default function TodoItemsIndex({projects, todoItems}: Props) {
  const [showForm, setShowForm] = useState(false);

  return (
    <LoggedIn>
      <h1>Today</h1>
      {todoItems.map(todo => (
        <TodoItemRow key={todo.id} todo={todo} projects={projects} />
      ))}
      <div>
        {!showForm && <button onClick={() => setShowForm(true)}>Add Task</button>}
        {showForm && (
          <QuickAddTaskForm projects={projects} onCancel={() => setShowForm(false)} />
        )}
      </div>
    </LoggedIn>
  );
}
