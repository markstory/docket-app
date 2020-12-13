import React, {useState} from 'react';
import {Droppable, Draggable} from 'react-beautiful-dnd';

import {TodoItem} from 'app/types';
import TodoItemRow from 'app/components/todoItemRow';
import TodoItemAddForm from 'app/components/todoItemAddForm';
import {Icon, InlineIcon} from './icon';

type Props = {
  dropId: string;
  todoItems: TodoItem[];
  defaultDate?: string;
  defaultProjectId?: number;
  showProject?: boolean;
  showDueOn?: boolean;
};

export default function TodoItemGroup({
  dropId,
  todoItems,
  defaultDate,
  defaultProjectId,
  showProject,
  showDueOn,
}: Props) {
  const [showForm, setShowForm] = useState(false);

  return (
    <div className="todo-item-group">
      <Droppable droppableId={dropId} type="todoitem">
        {(provided: any, snapshot: any) => {
          let className = 'dnd-dropper-left-offset';
          if (snapshot.isDraggingOver) {
            className += ' dnd-dropper-active';
          }
          return (
            <div
              ref={provided.innerRef}
              className={className}
              {...provided.droppableProps}
            >
              {todoItems.map((item, index) => (
                <Draggable key={item.id} draggableId={String(item.id)} index={index}>
                  {(provided: any, snapshot: any) => {
                    let className = 'dnd-item';
                    if (snapshot.isDragging) {
                      className += ' dnd-item-dragging';
                    }
                    return (
                      <div
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
                        <TodoItemRow
                          key={item.id}
                          todoItem={item}
                          showProject={showProject}
                          showDueOn={showDueOn}
                        />
                      </div>
                    );
                  }}
                </Draggable>
              ))}
              {provided.placeholder}
            </div>
          );
        }}
      </Droppable>
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
