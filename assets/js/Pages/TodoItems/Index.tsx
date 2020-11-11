import React, {useState} from 'react';

import {FlashMessage, TodoItem} from 'app/types';
import FlashMessages from 'app/components/flashMessages';
import TodoItemRow from 'app/components/todoItemRow';
import QuickAddTaskForm from 'app/forms/quickAddTaskForm';

type Props = {
  _csrfToken: string;
  flash: null | FlashMessage;
  todoItems: TodoItem[];
};

export default function TodoItemsIndex({flash, todoItems, _csrfToken}: Props) {
  const [showForm, setShowForm] = useState(false);

  return (
    <React.Fragment>
      <FlashMessages flash={flash} />
      <h1>Tasks</h1>
      {todoItems.map(todo => (
        <TodoItemRow todo={todo} key={todo.id} />
      ))}
      <div>
        {!showForm && <button onClick={() => setShowForm(true)}>Add Task</button>}
        {showForm && (
          <QuickAddTaskForm csrfToken={_csrfToken} onCancel={() => setShowForm(false)} />
        )}
      </div>
    </React.Fragment>
  );
}
