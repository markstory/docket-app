import React, {useState} from 'react';

import {TodoItem} from 'app/types';
import TodoItemRow from 'app/components/todoItemRow';
import QuickAddTaskForm from 'app/forms/quickAddTaskForm';
import LoggedIn from 'app/layouts/loggedIn';

type Props = {
  todoItems: TodoItem[];
};

export default function TodoItemsIndex({todoItems}: Props) {
  const [showForm, setShowForm] = useState(false);

  return (
    <LoggedIn>
      <h1>Today</h1>
      {todoItems.map(todo => (
        <TodoItemRow key={todo.id} todo={todo} />
      ))}
      <div>
        {!showForm && <button onClick={() => setShowForm(true)}>Add Task</button>}
        {showForm && <QuickAddTaskForm onCancel={() => setShowForm(false)} />}
      </div>
    </LoggedIn>
  );
}
