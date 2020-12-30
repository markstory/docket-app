import React from 'react';
import {Inertia} from '@inertiajs/inertia';
import {InertiaLink} from '@inertiajs/inertia-react';
import classnames from 'classnames';

import {t} from 'app/locale';
import {updateTaskField} from 'app/actions/tasks';
import DropdownMenu from 'app/components/dropdownMenu';
import ContextMenu from 'app/components/contextMenu';
import {InlineIcon} from 'app/components/icon';
import {MenuContents} from 'app/components/dueOnPicker';
import ProjectBadge from 'app/components/projectBadge';
import {Task} from 'app/types';
import {formatCompactDate, parseDate} from 'app/utils/dates';

type Props = {
  task: Task;
  showDueOn?: boolean;
  showProject?: boolean;
};

export default function TaskRow({task, showDueOn, showProject}: Props) {
  const handleComplete = (e: React.MouseEvent<HTMLInputElement>) => {
    e.stopPropagation();
    const action = task.completed ? 'incomplete' : 'complete';
    Inertia.post(`/todos/${task.id}/${action}`);
  };
  const className = classnames('task-row', {
    'is-completed': task.completed,
  });

  return (
    <div className={className}>
      <input
        type="checkbox"
        value="1"
        onClick={handleComplete}
        defaultChecked={task.completed}
      />
      <InertiaLink href={`/todos/${task.id}/view`}>
        <span className="title">{task.title}</span>
        <div className="attributes">
          {showDueOn && task.due_on && (
            <time className="due-on" dateTime={task.due_on}>
              <InlineIcon icon="calendar" width="xsmall" />
              {formatCompactDate(task.due_on)}
            </time>
          )}
          {showProject && <ProjectBadge project={task.project} />}
          <SubtaskSummary task={task} />
        </div>
      </InertiaLink>
      <TaskActions task={task} />
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

function TaskActions({task}: Pick<Props, 'task'>) {
  async function handleDueOnChange(value: string | null) {
    updateTaskField(task, 'due_on', value).then(() => {
      Inertia.reload();
    });
  }

  function handleDelete(event: React.MouseEvent) {
    event.preventDefault();
    Inertia.post(`/todos/${task.id}/delete`);
  }

  const dueOn = typeof task.due_on === 'string' ? parseDate(task.due_on) : undefined;
  return (
    <div className="actions">
      <DropdownMenu
        alignMenu="right"
        button={props => (
          <button className="button-icon" {...props}>
            <InlineIcon icon="calendar" />
          </button>
        )}
      >
        <MenuContents selected={dueOn} onChange={handleDueOnChange} />
      </DropdownMenu>
      <ContextMenu alignMenu="right">
        <li>
          <button className="context-item" onClick={handleDelete}>
            <InlineIcon icon="trash" />
            {t('Delete Task')}
          </button>
        </li>
      </ContextMenu>
    </div>
  );
}
