import React, {useState} from 'react';

import {TodoItemDetailed, TodoSubtask} from 'app/types';
import DragContainer from 'app/components/dragContainer';
import TodoSubtaskSorter from 'app/components/todoSubtaskSorter';
import TodoSubtaskAddForm from 'app/components/todoSubtaskAddForm';

type Props = {
  todoItem: TodoItemDetailed;
};

export default function TodoItemSubtasks({todoItem}: Props) {
  const [showForm, setShowForm] = useState(false);

  return (
    <div className="todoitem-subtasks">
      <h3>Sub-tasks</h3>
      <TodoSubtaskSorter todoItemId={todoItem.id} subtasks={todoItem.subtasks}>
        {({items, handleOrderChange}) => (
          <div className="drag-container-left-offset">
            <DragContainer
              items={items}
              renderItem={(subtask: TodoSubtask) => (
                <TodoItemSubtaskRow key={subtask.id} subtask={subtask} />
              )}
              onChange={handleOrderChange}
            />
          </div>
        )}
      </TodoSubtaskSorter>
      <div className="add-task">
        {!showForm && (
          <button className="button-default" onClick={() => setShowForm(true)}>
            Add Sub-task
          </button>
        )}
        {showForm && (
          <TodoSubtaskAddForm todoItem={todoItem} onCancel={() => setShowForm(false)} />
        )}
      </div>
    </div>
  );
}

type RowProps = {
  subtask: TodoSubtask;
};

function TodoItemSubtaskRow({subtask}: RowProps) {
  return <div>{subtask.title}</div>;
}
