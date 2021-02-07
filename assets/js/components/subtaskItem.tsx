import React, {useState} from 'react';
import classnames from 'classnames';
import {Inertia} from '@inertiajs/inertia';

import SubtaskEditForm from 'app/components/subtaskEditForm';
import {Subtask} from 'app/types';

type RowProps = {
  taskId: number;
  index: number;
  subtask: Subtask;
};

function SubtaskItem({index, subtask, taskId}: RowProps): JSX.Element {
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
export default SubtaskItem;
