import React, {useState} from 'react';
import {Inertia} from '@inertiajs/inertia';
import {Droppable, Draggable} from 'react-beautiful-dnd';

import {TodoItemDetailed, TodoSubtask} from 'app/types';
import TodoSubtaskSorter from 'app/components/todoSubtaskSorter';
import TodoSubtaskAddForm from 'app/components/todoSubtaskAddForm';
import {SubtasksProvider} from 'app/providers/subtasks';
import {Icon, InlineIcon} from './icon';
import SubtaskEditForm from 'app/components/subtaskEditForm';

type Props = {
  todoItem: TodoItemDetailed;
};

export default function TodoItemSubtasks({todoItem}: Props) {
  const [showForm, setShowForm] = useState(false);

  return (
    <SubtasksProvider subtasks={todoItem.subtasks}>
      <div className="todoitem-subtasks">
        <h3>Sub-tasks</h3>
        <TodoSubtaskSorter todoItemId={todoItem.id}>
          {({items}) => (
            <Droppable droppableId="subtasks" type="subtask">
              {provided => (
                <ul
                  ref={provided.innerRef}
                  className="dnd-droppable-left-offset"
                  {...provided.droppableProps}
                >
                  {items.map((subtask, index) => {
                    return (
                      <Draggable
                        key={subtask.id}
                        draggableId={String(subtask.id)}
                        index={index}
                      >
                        {(provided, snapshot) => {
                          let className = 'dnd-item';
                          if (snapshot.isDragging) {
                            className += ' dnd-item-dragging';
                          }

                          return (
                            <li
                              ref={provided.innerRef}
                              className={className}
                              {...provided.draggableProps}
                            >
                              <button
                                className="dnd-handle"
                                aria-label="Drag to reorder"
                                {...provided.dragHandleProps}
                              >
                                <Icon icon="grabber" width="large" />
                              </button>
                              <TodoItemSubtaskRow
                                index={index}
                                key={subtask.id}
                                subtask={subtask}
                                todoItemId={todoItem.id}
                              />
                            </li>
                          );
                        }}
                      </Draggable>
                    );
                  })}
                  {provided.placeholder}
                </ul>
              )}
            </Droppable>
          )}
        </TodoSubtaskSorter>
        <div className="add-task">
          {!showForm && (
            <button className="button-default" onClick={() => setShowForm(true)}>
              <InlineIcon icon="plus" />
              Add Sub-task
            </button>
          )}
          {showForm && (
            <TodoSubtaskAddForm todoItem={todoItem} onCancel={() => setShowForm(false)} />
          )}
        </div>
      </div>
    </SubtasksProvider>
  );
}

type RowProps = {
  todoItemId: number;
  index: number;
  subtask: TodoSubtask;
};

function TodoItemSubtaskRow({index, subtask, todoItemId}: RowProps) {
  const [editing, setEditing] = useState(false);
  function handleComplete(event: React.MouseEvent<HTMLInputElement>) {
    event.stopPropagation();
    Inertia.post(`/todos/${todoItemId}/subtasks/${subtask.id}/toggle`);
  }

  return (
    <div className="subtask-row">
      <input
        type="checkbox"
        onClick={handleComplete}
        value="1"
        defaultChecked={subtask.completed}
      />
      {editing ? (
        <SubtaskEditForm
          index={index}
          subtask={subtask}
          todoItemId={todoItemId}
          onCancel={() => setEditing(false)}
        />
      ) : (
        <div role="button" onClick={() => setEditing(true)}>
          {subtask.title}
        </div>
      )}
    </div>
  );
}
