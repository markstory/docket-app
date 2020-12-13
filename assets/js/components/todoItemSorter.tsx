import React from 'react';
import {Inertia} from '@inertiajs/inertia';
import {DropResult} from 'react-beautiful-dnd';

import {TodoItem} from 'app/types';

type ChildRenderProps = {
  items: TodoItem[];
  onDragEnd: (snapshot: any) => void;
};

type Props = {
  todoItems: TodoItem[];
  scope: 'day' | 'child';
  children: (props: ChildRenderProps) => JSX.Element;
};

/**
 * Abstraction around reorder lists of todos and optimistically updating state.
 */
export default function TodoItemSorter({children, todoItems, scope}: Props) {
  const [sorted, setSorted] = React.useState<TodoItem[] | undefined>(undefined);

  function handleDragEnd(result: DropResult) {
    // Dropped outside of a dropzone
    if (!result.destination) {
      return;
    }
    const newItems = [...todoItems];
    const [moved] = newItems.splice(result.source.index, 1);
    newItems.splice(result.destination.index, 0, moved);

    const data = {
      items: newItems.map(({id}) => id),
      scope,
    };
    setSorted(newItems);

    // TODO should this use axios instead so we don't repaint?
    Inertia.post('/todos/reorder', data, {preserveScroll: true});
  }

  const items = sorted || todoItems;

  return children({
    items: items,
    onDragEnd: handleDragEnd,
  });
}
