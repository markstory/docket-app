import React, {useState} from 'react';
import {Inertia} from '@inertiajs/inertia';
import {Droppable, Draggable} from 'react-beautiful-dnd';
import classnames from 'classnames';

import {t} from 'app/locale';
import {TaskDetailed, Subtask} from 'app/types';
import SubtaskSorter from 'app/components/subtaskSorter';
import SubtaskAddForm from 'app/components/subtaskAddForm';
import {SubtasksProvider} from 'app/providers/subtasks';
import {Icon, InlineIcon} from './icon';
import SubtaskEditForm from 'app/components/subtaskEditForm';

type Props = {
  task: TaskDetailed;
};

export default function TaskSubtasks({task}: Props) {
  const [showForm, setShowForm] = useState(false);

  return (
    <SubtasksProvider subtasks={task.subtasks}>
      <div className="task-subtasks">
        <h3>
          <InlineIcon icon="workflow" /> Sub-tasks
        </h3>
        <SubtaskSorter taskId={task.id}>
          {({items}) => (
            <Droppable droppableId="subtasks" type="subtask">
              {(provided, snapshot) => {
                const className = classnames('dnd-dropper-left-offset', {
                  'dnd-dropper-active': snapshot.isDraggingOver,
                });
                return (
                  <ul
                    ref={provided.innerRef}
                    className={className}
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
                            const className = classnames('dnd-item', {
                              'dnd-item-dragging': snapshot.isDragging,
                            });
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
                                  <Icon icon="grabber" width="xlarge" />
                                </button>
                                <TaskSubtaskRow
                                  index={index}
                                  key={subtask.id}
                                  subtask={subtask}
                                  taskId={task.id}
                                />
                              </li>
                            );
                          }}
                        </Draggable>
                      );
                    })}
                    {provided.placeholder}
                  </ul>
                );
              }}
            </Droppable>
          )}
        </SubtaskSorter>
        <div className="add-subtask">
          {!showForm && (
            <button className="button-secondary" onClick={() => setShowForm(true)}>
              <InlineIcon icon="plus" />
              {t('Add Sub-task')}
            </button>
          )}
          {showForm && <SubtaskAddForm task={task} onCancel={() => setShowForm(false)} />}
        </div>
      </div>
    </SubtasksProvider>
  );
}

type RowProps = {
  taskId: number;
  index: number;
  subtask: Subtask;
};

function TaskSubtaskRow({index, subtask, taskId}: RowProps) {
  const [editing, setEditing] = useState(false);
  function handleComplete(event: React.MouseEvent<HTMLInputElement>) {
    event.stopPropagation();
    Inertia.post(`/tasks/${taskId}/subtasks/${subtask.id}/toggle`);
  }
  const className = classnames('subtask-row', {
    'is-completed': subtask.completed,
  });

  return (
    <div className={className}>
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
          taskId={taskId}
          onCancel={() => setEditing(false)}
        />
      ) : (
        <div className="title" role="button" onClick={() => setEditing(true)}>
          {subtask.title}
        </div>
      )}
    </div>
  );
}
