import React, {useState} from 'react';

import {TodoItem} from 'app/types';
import DragContainer from 'app/components/dragContainer';
import TodoItemRow from 'app/components/todoItemRow';
import TodoItemAddForm from 'app/components/todoItemAddForm';
import {InlineIcon} from './icon';

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
    <div className="todo-item-group">
      <div className="drag-container-left-offset">
        <DragContainer
          items={todoItems}
          renderItem={(todoItem: TodoItem) => (
            <TodoItemRow
              key={todoItem.id}
              todoItem={todoItem}
              showProject={showProject}
              showDueOn={showDueOn}
            />
          )}
          onChange={onReorder}
        />
      </div>
      <div className="add-task">
        {!showForm && (
          <button className="button-secondary" onClick={() => setShowForm(true)}>
            <InlineIcon icon="plus" />
            Add Task
          </button>
        )}
        {showForm && (
          <TodoItemAddForm
            defaultDate={defaultDate}
            defaultProjectId={defaultProjectId}
            order={todoItems.length}
            onCancel={() => setShowForm(false)}
          />
        )}
      </div>
    </div>
  );
}
