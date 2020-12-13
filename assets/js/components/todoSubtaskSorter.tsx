import React from 'react';
import {Inertia} from '@inertiajs/inertia';
import {DropResult} from 'react-beautiful-dnd';

import {TodoItem, TodoSubtask} from 'app/types';

type ChildRenderProps = {
  onDragEnd: (result: DropResult) => void;
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

  function handleDragEnd(result: DropResult) {
    // Dropped outside of a dropzone
    if (!result.destination) {
      return;
    }
    const newItems = [...subtasks];
    const [moved] = newItems.splice(result.source.index, 1);
    newItems.splice(result.destination.index, 0, moved);

    const data = {
      items: newItems.map(({id}) => id),
    };
    setSorted(newItems);

    // TODO should this use axios instead so we don't repaint?
    Inertia.post(`/todos/${todoItemId}/subtasks/reorder`, data);
  }

  const items = sorted || subtasks;

  return children({items: items, onDragEnd: handleDragEnd});
}
