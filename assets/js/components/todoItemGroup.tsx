import React, {useState} from 'react';

import {TodoItem} from 'app/types';
import TodoItemRow from 'app/components/todoItemRow';
import QuickAddTaskForm from 'app/forms/quickAddTaskForm';

type Props = {
  todoItems: TodoItem[];
  defaultDate?: string;
};

export default function TodoItemsGroup({todoItems, defaultDate}: Props) {
  const [showForm, setShowForm] = useState(false);

  return (
    <React.Fragment>
      {todoItems.map(todo => (
        <TodoItemRow key={todo.id} todo={todo} />
      ))}
      <div>
        {!showForm && <button onClick={() => setShowForm(true)}>Add Task</button>}
        {showForm && (
          <QuickAddTaskForm
            defaultDate={defaultDate}
            onCancel={() => setShowForm(false)}
          />
        )}
      </div>
    </React.Fragment>
  );
}
