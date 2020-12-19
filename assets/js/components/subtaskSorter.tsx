import React from 'react';
import {Inertia} from '@inertiajs/inertia';
import {DragDropContext, DropResult} from 'react-beautiful-dnd';

import {Task, Subtask} from 'app/types';
import {useSubtasks} from 'app/providers/subtasks';

type ChildRenderProps = {
  items: Subtask[];
  setItems: (items: Subtask[]) => void;
};

type Props = {
  taskId: Task['id'];
  children: (props: ChildRenderProps) => JSX.Element;
};

/**
 * Abstraction around reorder lists of todo subtasks and optimistically updating state.
 */
export default function SubtaskSorter({children, taskId}: Props) {
  const [sorted, setSorted] = React.useState<Subtask[] | undefined>(undefined);
  const [subtasks, setSubtasks] = useSubtasks();

  function handleDragEnd(result: DropResult) {
    // Dropped outside of a dropzone
    if (!result.destination) {
      return;
    }
    const newItems = [...subtasks];
    const [moved] = newItems.splice(result.source.index, 1);
    newItems.splice(result.destination.index, 0, moved);

    const data = {
      ranking: result.destination.index,
    };
    setSubtasks(newItems);

    // TODO should this use axios instead so we don't repaint?
    Inertia.post(`/todos/${taskId}/subtasks/${result.draggableId}/move`, data);
  }

  const items = sorted || subtasks;

  return (
    <DragDropContext onDragEnd={handleDragEnd}>
      {children({items: items, setItems: setSorted})}
    </DragDropContext>
  );
}
