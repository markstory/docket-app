import React from 'react';
import {Inertia} from '@inertiajs/inertia';

import {TodoItem, TodoSubtask} from 'app/types';

type ChildRenderProps = {
  handleOrderChange: (items: TodoSubtask[]) => void;
  items: TodoSubtask[];
};

type Props = {
  todoItemId: TodoItem['id'];
  subtasks: TodoSubtask[];
  children: (props: ChildRenderProps) => JSX.Element;
};

/**
 * Abstraction around reorder lists of todo subtasks and optimistically updating state.
 */
export default function TodoSubtaskSorter({children, todoItemId, subtasks}: Props) {
  const [sorted, setSorted] = React.useState<TodoSubtask[] | undefined>(undefined);

  function handleChange(items: TodoSubtask[]) {
    const itemIds = items.map(item => item.id);
    const data = {
      items: itemIds,
    };
    setSorted(items);
    Inertia.post(`/todos/${todoItemId}/subtasks/reorder`, data);
  }

  const items = sorted || subtasks;

  return children({items: items, handleOrderChange: handleChange});
}
