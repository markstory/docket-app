import React from 'react';
import {Inertia} from '@inertiajs/inertia';
import {DragDropContext, DropResult} from 'react-beautiful-dnd';

import {Task} from 'app/types';

type ChildRenderProps = {
  items: Task[];
};

type Props = {
  tasks: Task[];
  scope: 'day' | 'child';
  children: (props: ChildRenderProps) => JSX.Element;
};

type UpdateData = {
  child_order?: number;
  day_order?: number;
  due_on?: string;
};

/**
 * Abstraction around reorder lists of tasks and optimistically updating state.
 */
export default function TaskSorter({children, tasks, scope}: Props) {
  const [sorted, setSorted] = React.useState<Task[] | undefined>(undefined);

  function handleDragEnd(result: DropResult) {
    // Dropped outside of a dropzone
    if (!result.destination) {
      return;
    }
    const newItems = [...tasks];
    const [moved] = newItems.splice(result.source.index, 1);
    newItems.splice(result.destination.index, 0, moved);

    setSorted(newItems);

    const property = scope === 'day' ? 'day_order' : 'child_order';
    const data: UpdateData = {
      [property]: result.destination.index,
    };
    if (result.source.droppableId !== result.destination.droppableId) {
      data.due_on = result.destination.droppableId;
    }

    Inertia.post(`/tasks/${result.draggableId}/move`, data, {
      preserveScroll: true,
      onSuccess() {
        // Revert local state.
        setSorted(undefined);
      },
    });
  }

  const items = sorted || tasks;

  return (
    <DragDropContext onDragEnd={handleDragEnd}>
      {children({items: items})}
    </DragDropContext>
  );
}
