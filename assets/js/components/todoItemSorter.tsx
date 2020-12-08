import React from 'react';
import {Inertia} from '@inertiajs/inertia';

import {TodoItem} from 'app/types';

type ChildRenderProps = {
  handleOrderChange: (items: TodoItem[]) => void;
  items: TodoItem[];
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

  function handleChange(items: TodoItem[]) {
    const itemIds = items.map(item => item.id);
    const data = {
      items: itemIds,
      scope,
    };
    setSorted(items);
    Inertia.post('/todos/reorder', data, {preserveScroll: true});
  }

  const items = sorted || todoItems;

  return children({items: items, handleOrderChange: handleChange});
}
