import React, {useState} from 'react';
import {Droppable, Draggable} from 'react-beautiful-dnd';
import classnames from 'classnames';

import {Task} from 'app/types';
import {t} from 'app/locale';
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
          const className = classnames('dnd-dropper-left-offset', {
            'dnd-dropper-active': snapshot.isDraggingOver,
          });
          return (
            <div
              ref={provided.innerRef}
              className={className}
              {...provided.droppableProps}
            >
              {tasks.map((item, index) => (
                <Draggable key={item.id} draggableId={String(item.id)} index={index}>
                  {(provided, snapshot) => {
                    const className = classnames('dnd-item', {
                      'dnd-item-dragging': snapshot.isDragging,
                    });
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
                          <Icon icon="grabber" width="xlarge" />
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
            <button
              data-testid="add-task"
              className="button-secondary"
              onClick={() => setShowForm(true)}
            >
              <InlineIcon icon="plus" />
              {t('Add Task')}
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
