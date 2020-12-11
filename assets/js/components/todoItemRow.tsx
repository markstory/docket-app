import React, {useState} from 'react';
import {Inertia} from '@inertiajs/inertia';
import {InertiaLink} from '@inertiajs/inertia-react';

import {InlineIcon} from 'app/components/icon';
import {TodoItem} from 'app/types';
import ProjectBadge from 'app/components/projectBadge';

type Props = {
  todoItem: TodoItem;
  showDueOn?: boolean;
  showProject?: boolean;
};

export default function TodoItemRow({todoItem, showDueOn, showProject}: Props) {
  const handleComplete = (e: React.MouseEvent<HTMLInputElement>) => {
    e.stopPropagation();
    Inertia.post(`/todos/${todoItem.id}/complete`);
  };

  return (
    <div className="todoitem-row">
      <input
        type="checkbox"
        value="1"
        onClick={handleComplete}
        defaultChecked={todoItem.completed}
      />
      <InertiaLink href={`/todos/${todoItem.id}/view`}>
        <span className="title">{todoItem.title}</span>
        <div className="attributes">
          {showDueOn && todoItem.due_on && (
            <time className="due-on" dateTime={todoItem.due_on}>
              <InlineIcon icon="calendar" width="xsmall" />
              {todoItem.due_on}
            </time>
          )}
          {showProject && <ProjectBadge project={todoItem.project} />}
        </div>
      </InertiaLink>
    </div>
  );
}
