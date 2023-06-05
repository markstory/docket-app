import {Fragment, useState} from 'react';
import classnames from 'classnames';
import {Inertia} from '@inertiajs/inertia';

import SubtaskEditForm from 'app/components/subtaskEditForm';
import Checkbox from 'app/components/checkbox';
import {Subtask} from 'app/types';
import {NEW_ID} from 'app/constants';

type RowProps = {
  taskId: number;
  index: number;
  subtask: Subtask;
};

function SubtaskItem({index, subtask, taskId}: RowProps): JSX.Element {
  const [editing, setEditing] = useState(false);
  let isNew = subtask.id == NEW_ID;
  function handleComplete(event: React.ChangeEvent<HTMLInputElement>) {
    event.stopPropagation();
    if (isNew) {
      return;
    }
    Inertia.post(
      `/tasks/${taskId}/subtasks/${subtask.id}/toggle`,
      {},
      {
        only: ['task'],
      }
    );
  }
  const className = classnames('subtask-row', {
    'is-completed': subtask.completed,
  });
  let inputs: React.ReactNode;

  if (isNew) {
    const namePrefix = `subtasks[${index}]`;
    inputs = (
      <Fragment>
        <input type="hidden" name={`${namePrefix}[title]`} value={subtask.title} />
        <input
          type="hidden"
          name={`${namePrefix}[completed]`}
          value={subtask.completed ? 1 : 0}
        />
      </Fragment>
    );
  }

  return (
    <div className={className}>
      <Checkbox name="complete" onChange={handleComplete} checked={subtask.completed} />
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
          {inputs}
        </div>
      )}
    </div>
  );
}
export default SubtaskItem;
