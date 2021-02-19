import React, {useState} from 'react';
import {Inertia} from '@inertiajs/inertia';
import {InertiaLink} from '@inertiajs/inertia-react';
import {MenuItem} from '@reach/menu-button';
import classnames from 'classnames';

import {t} from 'app/locale';
import {updateTask} from 'app/actions/tasks';
import DueOn from 'app/components/dueOn';
import ContextMenu from 'app/components/contextMenu';
import TaskEvening from 'app/components/taskEvening';
import {InlineIcon} from 'app/components/icon';
import {MenuContents} from 'app/components/dueOnPicker';
import ProjectBadge from 'app/components/projectBadge';
import {Task} from 'app/types';
import {parseDate} from 'app/utils/dates';

type Props = {
  task: Task;
  showDueOn?: boolean;
  showProject?: boolean;
};

export default function TaskRow({task, showDueOn, showProject}: Props): JSX.Element {
  const [active, setActive] = useState(false);

  const handleComplete = (e: React.MouseEvent<HTMLInputElement>) => {
    e.stopPropagation();
    const action = task.completed ? 'incomplete' : 'complete';
    Inertia.post(`/tasks/${task.id}/${action}`);
  };
  const className = classnames('task-row', {
    'is-completed': task.completed,
  });

  return (
    <div
      className={className}
      onMouseEnter={() => setActive(true)}
      onMouseLeave={() => setActive(false)}
    >
      <input
        type="checkbox"
        value="1"
        onClick={handleComplete}
        defaultChecked={task.completed}
      />
      <InertiaLink href={`/tasks/${task.id}/view`}>
        <span className="title">{task.title}</span>
        <div className="attributes">
          {showProject && <ProjectBadge project={task.project} />}
          {showDueOn && <DueOn value={task.due_on} />}
          <TaskEvening task={task} />
          <SubtaskSummary task={task} />
        </div>
      </InertiaLink>
      {active ? <TaskActions task={task} setActive={setActive} /> : null}
    </div>
  );
}

function SubtaskSummary({task}: Pick<Props, 'task'>) {
  if (task.subtask_count < 1) {
    return null;
  }
  return (
    <span className="counter">
      <InlineIcon icon="workflow" width="xsmall" />
      {task.complete_subtask_count.toLocaleString()} /{' '}
      {task.subtask_count.toLocaleString()}
    </span>
  );
}

type ActionsProps = Pick<Props, 'task'> & {
  setActive: (val: boolean) => void;
};

function TaskActions({task, setActive}: ActionsProps) {
  async function handleDueOnChange(dueOn: string | null, evening: boolean) {
    const data = {due_on: dueOn, evening};
    await updateTask(task, data);
    setActive(false);
    Inertia.reload();
  }

  function handleDelete() {
    Inertia.post(`/tasks/${task.id}/delete`);
  }

  return (
    <div className="actions" onMouseEnter={() => setActive(true)}>
      <ContextMenu icon="calendar" tooltip={t('Reschedule')}>
        <MenuContents task={task} onChange={handleDueOnChange} />
      </ContextMenu>
      <ContextMenu tooltip={t('Task actions')}>
        <MenuItem className="delete" onSelect={handleDelete}>
          <InlineIcon icon="trash" />
          {t('Delete Task')}
        </MenuItem>
      </ContextMenu>
    </div>
  );
}
