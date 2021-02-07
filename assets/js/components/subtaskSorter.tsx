import React, {useState} from 'react';
import classnames from 'classnames';
import {Inertia} from '@inertiajs/inertia';
import {
  DndContext,
  closestCenter,
  KeyboardSensor,
  PointerSensor,
  useSensor,
  useSensors,
  DragOverlay,
  DragEndEvent,
  DragStartEvent,
} from '@dnd-kit/core';
import {
  arrayMove,
  SortableContext,
  verticalListSortingStrategy,
  sortableKeyboardCoordinates,
  useSortable,
} from '@dnd-kit/sortable';
import {CSS} from '@dnd-kit/utilities';

import {Task, Subtask} from 'app/types';
import SubtaskItem from 'app/components/subtaskItem';
import {useSubtasks} from 'app/providers/subtasks';

import DragHandle from './dragHandle';

type Props = {
  taskId: Task['id'];
};

/**
 * Abstraction around reorder lists of todo subtasks and optimistically updating state.
 */
export default function SubtaskSorter({taskId}: Props): JSX.Element {
  const [subtasks, setSubtasks] = useSubtasks();
  const subtaskIds = subtasks.map(subtask => String(subtask.id));

  const [activeSubtask, setActiveSubtask] = useState<Subtask | null>(null);
  const sensors = useSensors(
    useSensor(PointerSensor),
    useSensor(KeyboardSensor, {
      coordinateGetter: sortableKeyboardCoordinates,
    })
  );

  function handleDragStart(event: DragStartEvent) {
    const activeId = Number(event.active.id);
    setActiveSubtask(subtasks.find(p => p.id === activeId) ?? null);
  }

  function handleDragEnd(event: DragEndEvent) {
    const {active, over} = event;
    setActiveSubtask(null);

    // Dropped outside of a dropzone
    if (!over) {
      return;
    }
    const oldIndex = subtaskIds.indexOf(active.id);
    const newIndex = subtaskIds.indexOf(over.id);
    const newItems = arrayMove(subtasks, oldIndex, newIndex);

    setSubtasks(newItems);

    const data = {
      ranking: newIndex,
    };

    Inertia.post(`/tasks/${taskId}/subtasks/${active.id}/move`, data, {
      only: ['task', 'flash'],
      onSuccess() {
        // Revert local state.
        setSubtasks(null);
      },
    });
  }

  return (
    <DndContext
      collisionDetection={closestCenter}
      sensors={sensors}
      onDragStart={handleDragStart}
      onDragEnd={handleDragEnd}
    >
      <SortableContext items={subtaskIds} strategy={verticalListSortingStrategy}>
        <ul className="dnd-dropper-left-offset">
          {subtasks.map((subtask, index) => (
            <SortableItem
              key={subtask.id}
              id={String(subtask.id)}
              index={index}
              active={String(activeSubtask?.id)}
              subtask={subtask}
              taskId={taskId}
            />
          ))}
        </ul>
      </SortableContext>
      <DragOverlay>
        {activeSubtask ? (
          <li className="dnd-item dnd-item-dragging">
            <DragHandle />
            <SubtaskItem index={0} subtask={activeSubtask} taskId={taskId} />
          </li>
        ) : null}
      </DragOverlay>
    </DndContext>
  );
}

type ItemProps = {
  id: string;
  subtask: Subtask;
  index: number;
  taskId: number;
  active?: string;
};
function SortableItem({active, id, index, subtask, taskId}: ItemProps): JSX.Element {
  const {attributes, listeners, setNodeRef, transform, transition} = useSortable({
    id,
  });
  const style = {
    transform: CSS.Transform.toString(transform),
    transition,
  };
  const className = classnames('dnd-item', {
    'dnd-ghost': id === active,
  });
  return (
    <li className={className} ref={setNodeRef} style={style}>
      <DragHandle attributes={attributes} listeners={listeners} />

      <SubtaskItem subtask={subtask} taskId={taskId} index={index} />
    </li>
  );
}
