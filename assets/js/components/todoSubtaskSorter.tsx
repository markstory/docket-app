import React from 'react';
import {Inertia} from '@inertiajs/inertia';
import {DragDropContext, DropResult} from 'react-beautiful-dnd';

import {TodoItem, TodoSubtask} from 'app/types';
import {useSubtasks} from 'app/providers/subtasks';

type ChildRenderProps = {
  items: TodoSubtask[];
  setItems: (items: TodoSubtask[]) => void;
};

type Props = {
  todoItemId: TodoItem['id'];
  children: (props: ChildRenderProps) => JSX.Element;
};

/**
 * Abstraction around reorder lists of todo subtasks and optimistically updating state.
 */
export default function TodoSubtaskSorter({children, todoItemId}: Props) {
  const [sorted, setSorted] = React.useState<TodoSubtask[] | undefined>(undefined);
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
    Inertia.post(`/todos/${todoItemId}/subtasks/${result.draggableId}/move`, data);
  }

  const items = sorted || subtasks;

  return (
    <DragDropContext onDragEnd={handleDragEnd}>
      {children({items: items, setItems: setSorted})}
    </DragDropContext>
  );
}
