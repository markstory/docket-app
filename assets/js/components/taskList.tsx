import React, {useState} from 'react';
import classnames from 'classnames';

import {Task} from 'app/types';
import TaskRow from 'app/components/taskRow';

type Props = {
  tasks: Task[];
  title?: React.ReactNode;
  showProject?: boolean;
  showDueOn?: boolean;
};

export default function TaskList({title, tasks, showProject, showDueOn}: Props) {
  // TODO add pagination.
  return (
    <div className="task-list">
      {title && <h2>{title}</h2>}
      {tasks.map(item => (
        <TaskRow
          key={item.id}
          task={item}
          showProject={showProject}
          showDueOn={showDueOn}
        />
      ))}
    </div>
  );
}
