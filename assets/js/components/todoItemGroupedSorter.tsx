import React from 'react';
import {Inertia} from '@inertiajs/inertia';
import {groupBy} from 'lodash';
import {DropResult} from 'react-beautiful-dnd';

import {TodoItem} from 'app/types';

type GroupedItems = {key: string; items: TodoItem[]}[];

type ChildRenderProps = {
  groupedItems: GroupedItems;
  onDragEnd: (snapshot: DropResult) => void;
};

type Props = {
  todoItems: TodoItem[];
  scope: 'day';
  children: (props: ChildRenderProps) => JSX.Element;
};

type UpdateData = {
  child_order?: number;
  day_order?: number;
  due_on?: string;
};

/**
 * Abstraction around reorder lists of todos and optimistically updating state.
 */
export default function TodoItemGroupedSorter({children, todoItems, scope}: Props) {
  const [sorted, setSorted] = React.useState<GroupedItems | undefined>(undefined);

  const grouped: GroupedItems = [];
  if (scope === 'day') {
    const byDate: Record<string, TodoItem[]> = groupBy(
      todoItems,
      item => item.due_on || 'No Due Date'
    );
    // TODO Consider moving the zero filling out to the 'rendering' components.
    const dateStrings = Object.keys(byDate).sort();
    const dates = dateStrings.map(value => new Date(`${value} 00:00:00`));

    // XXX: Time based views are for 28 days at a time.
    const first = (dates.length ? dates[0] : new Date()).getTime();
    const end = first + 28 * ONE_DAY_IN_MS;

    for (let i = first; i < end; i += ONE_DAY_IN_MS) {
      const date = new Date(i);
      const dateKey = toDateString(date);
      if (byDate.hasOwnProperty(dateKey)) {
        grouped.push({key: dateKey, items: byDate[dateKey]});
      } else {
        grouped.push({key: dateKey, items: []});
      }
    }
  }

  function handleDragEnd(result: DropResult) {
    const destination = result.destination;
    // Dropped outside of a dropzone
    if (!destination) {
      return;
    }
    const newGrouped = [...grouped];
    const destinationGroup = newGrouped.find(
      group => group.key === destination.droppableId
    );
    const sourceGroup = newGrouped.find(group => group.key === result.source.droppableId);
    if (!destinationGroup || !sourceGroup) {
      return;
    }
    const [moved] = sourceGroup.items.splice(result.source.index, 1);
    destinationGroup.items.splice(destination.index, 0, moved);

    setSorted(newGrouped);

    const property = scope === 'day' ? 'day_order' : 'child_order';
    const data: UpdateData = {
      [property]: destination.index,
    };
    if (result.source.droppableId !== destination.droppableId) {
      data.due_on = destination.droppableId;
    }

    // TODO should this use axios instead so we don't repaint?
    Inertia.post(`/todos/${result.draggableId}/move`, data, {preserveScroll: true});
  }

  const items = sorted || grouped;

  return children({
    groupedItems: items,
    onDragEnd: handleDragEnd,
  });
}

const ONE_DAY_IN_MS = 1000 * 60 * 60 * 24;

function toDateString(date: Date): string {
  const day = date.getDate();
  return `${date.getFullYear()}-${date.getMonth() + 1}-${day < 10 ? '0' + day : day}`;
}
