import React, {useState} from 'react';
import {Inertia} from '@inertiajs/inertia';
import {Droppable, Draggable} from 'react-beautiful-dnd';
import classnames from 'classnames';

import {t} from 'app/locale';
import {TaskDetailed, Subtask} from 'app/types';
import SubtaskSorter from 'app/components/subtaskSorter';
import SubtaskAddForm from 'app/components/subtaskAddForm';
import {SubtasksProvider} from 'app/providers/subtasks';
import {InlineIcon} from './icon';

type Props = {
  task: TaskDetailed;
};

export default function TaskSubtasks({task}: Props): JSX.Element {
  const [showForm, setShowForm] = useState(false);

  return (
    <SubtasksProvider subtasks={task.subtasks}>
      <div className="task-subtasks">
        <h3>
          <InlineIcon icon="workflow" /> Sub-tasks
        </h3>
        <SubtaskSorter taskId={task.id} />
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
