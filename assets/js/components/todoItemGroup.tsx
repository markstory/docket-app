import React, {useState} from 'react';

import {TodoItem} from 'app/types';
import DragContainer from 'app/components/dragContainer';
import TodoItemRow from 'app/components/todoItemRow';
import TodoItemAddForm from 'app/components/todoItemAddForm';

type Props = {
  todoItems: TodoItem[];
  onReorder: (items: TodoItem[]) => void;
  defaultDate?: string;
  defaultProjectId?: number;
  showProject?: boolean;
  showDueOn?: boolean;
};

export default function TodoItemsGroup({
  todoItems,
  defaultDate,
  defaultProjectId,
  showProject,
  showDueOn,
  onReorder,
}: Props) {
  const [showForm, setShowForm] = useState(false);

  return (
    <React.Fragment>
      <DragContainer
        items={todoItems}
        renderItem={(todo: TodoItem) => (
          <TodoItemRow
            key={todo.id}
            todo={todo}
            showProject={showProject}
            showDueOn={showDueOn}
          />
        )}
        onChange={onReorder}
      />
      <div>
        {!showForm && <button onClick={() => setShowForm(true)}>Add Task</button>}
        {showForm && (
          <TodoItemAddForm
            defaultDate={defaultDate}
            defaultProjectId={defaultProjectId}
            onCancel={() => setShowForm(false)}
          />
        )}
      </div>
    </React.Fragment>
  );
}
