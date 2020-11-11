import React from 'react';

import {TodoItem} from 'app/types';
import ProjectBadge from 'app/components/projectBadge';

type Props = {
  todo: TodoItem;
};

function TodoItemRow({todo}: Props) {
  return (
    <div>
      <input type="checkbox" value="1" defaultChecked={todo.completed} />
      <span>{todo.title}</span>
      <ProjectBadge project={todo.project} />
    </div>
  );
}

export default TodoItemRow;
