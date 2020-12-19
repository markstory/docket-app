import React from 'react';
import {Inertia} from '@inertiajs/inertia';
import {groupBy} from 'lodash';
import {DragDropContext, DropResult} from 'react-beautiful-dnd';

import {Task} from 'app/types';

export type GroupedItems = {key: string; items: Task[]}[];

type ChildRenderProps = {
  groupedItems: GroupedItems;
};

type Props = {
  tasks: Task[];
  scope: 'day';
  children: (props: ChildRenderProps) => JSX.Element;
  grouper?: (tasks: Task[]) => GroupedItems;
};

type UpdateData = {
  child_order?: number;
  day_order?: number;
  due_on?: string;
};

function defaultGrouper(items: Task[]): GroupedItems {
  const byDate: Record<string, Task[]> = groupBy(items, item =>
    item.due_on ? item.due_on : 'No Due Date'
  );
  const grouped = Object.entries(byDate).map(([key, value]) => {
    return {key, items: value};
  });
  return grouped;
}

/**
 * Abstraction around reorder lists of todos and optimistically updating state.
 */
export default function TaskGroupedSorter({children, tasks, grouper, scope}: Props) {
  const [sorted, setSorted] = React.useState<GroupedItems | undefined>(undefined);

  const groupFn = grouper ? grouper : defaultGrouper;
  const grouped = groupFn(tasks);

  function handleDragEnd(result: DropResult) {
    const destination = result.destination;
    // Dropped outside of a dropzone
    if (!destination) {
      console.log('no dest');
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

  return (
    <DragDropContext onDragEnd={handleDragEnd}>
      {children({
        groupedItems: items,
      })}
    </DragDropContext>
  );
}
