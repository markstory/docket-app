import React from 'react';
import {Inertia} from '@inertiajs/inertia';

import {TodoItem} from 'app/types';
import ProjectBadge from 'app/components/projectBadge';

type Props = {
  todo: TodoItem;
};

function TodoItemRow({todo}: Props) {
  const handleComplete = (e: React.MouseEvent<HTMLInputElement>) => {
    e.stopPropagation();
    Inertia.post(`/todos/${todo.id}/complete`);
  };

  return (
    <div>
      <input
        type="checkbox"
        value="1"
        onClick={handleComplete}
        defaultChecked={todo.completed}
      />
      <span>{todo.title}</span>
      <ProjectBadge project={todo.project} />
    </div>
  );
}

export default TodoItemRow;
