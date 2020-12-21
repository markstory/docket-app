import React, {useState} from 'react';
import {Droppable, Draggable} from 'react-beautiful-dnd';

import {Task} from 'app/types';
import TaskRow from 'app/components/taskRow';
import TaskAddForm from 'app/components/taskAddForm';
import {Icon, InlineIcon} from './icon';

type Props = {
  dropId: string;
  tasks: Task[];
  defaultDate?: string;
  defaultProjectId?: number;
  showProject?: boolean;
  showDueOn?: boolean;
  showAdd?: boolean;
};

export default function TaskGroup({
  dropId,
  tasks,
  defaultDate,
  defaultProjectId,
  showProject,
  showDueOn,
  showAdd = true,
}: Props) {
  const [showForm, setShowForm] = useState(false);

  return (
    <div className="task-group">
      <Droppable droppableId={dropId} type="task">
        {(provided, snapshot) => {
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
              {tasks.map((item, index) => (
                <Draggable key={item.id} draggableId={String(item.id)} index={index}>
                  {(provided, snapshot) => {
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
                        <TaskRow
                          key={item.id}
                          task={item}
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
      {showAdd && (
        <div className="add-task">
          {!showForm && (
            <button className="button-secondary" onClick={() => setShowForm(true)}>
              <InlineIcon icon="plus" />
              Add Task
            </button>
          )}
          {showForm && (
            <TaskAddForm
              defaultDate={defaultDate}
              defaultProjectId={defaultProjectId}
              onCancel={() => setShowForm(false)}
            />
          )}
        </div>
      )}
    </div>
  );
}
