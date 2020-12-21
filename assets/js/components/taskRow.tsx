import React, {useState} from 'react';
import {Inertia} from '@inertiajs/inertia';
import {InertiaLink} from '@inertiajs/inertia-react';
import classnames from 'classnames';

import {InlineIcon} from 'app/components/icon';
import {Task} from 'app/types';
import ProjectBadge from 'app/components/projectBadge';
import {formatCompactDate} from 'app/utils/dates';

type Props = {
  task: Task;
  showDueOn?: boolean;
  showProject?: boolean;
};

export default function TaskRow({task, showDueOn, showProject}: Props) {
  const handleComplete = (e: React.MouseEvent<HTMLInputElement>) => {
    e.stopPropagation();
    Inertia.post(`/todos/${task.id}/complete`);
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
        </div>
      </InertiaLink>
    </div>
  );
}
