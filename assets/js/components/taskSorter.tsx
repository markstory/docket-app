import React, {useState} from 'react';
import {Inertia} from '@inertiajs/inertia';
import {
  DndContext,
  closestCorners,
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
} from '@dnd-kit/sortable';

import {Task} from 'app/types';
import DragHandle from 'app/components/dragHandle';
import TaskRow from 'app/components/taskRow';

type ChildRenderProps = {
  items: Task[];
  activeTask: Task | null;
};

type Props = {
  tasks: Task[];
  scope: 'day' | 'child';
  idPrefix: string;
  children: (props: ChildRenderProps) => JSX.Element;
  showDueOn?: boolean;
};

type UpdateData = {
  child_order?: number;
  day_order?: number;
  due_on?: string;
};

/**
 * Abstraction around reorder lists of tasks and optimistically updating state.
 */
export default function TaskSorter({
  children,
  tasks,
  scope,
  idPrefix,
  showDueOn,
}: Props): JSX.Element {
  const [activeTask, setActiveTask] = useState<Task | null>(null);
  const [sorted, setSorted] = React.useState<Task[] | undefined>(undefined);
  const items = sorted || tasks;
  const taskIds = items.map(task => `${idPrefix}:${task.id}`);

  const sensors = useSensors(
    useSensor(PointerSensor),
    useSensor(KeyboardSensor, {
      coordinateGetter: sortableKeyboardCoordinates,
    })
  );

  function handleDragStart(event: DragStartEvent) {
    const activeId = Number(event.active.id.split(':')[1]);
    setActiveTask(items.find(p => p.id === activeId) ?? null);
  }

  function handleDragEnd(event: DragEndEvent) {
    const {active, over} = event;
    setActiveTask(null);

    // Dropped outside of a dropzone
    if (!over) {
      return;
    }
    const activeId = active.id;
    const overId = over.id;

    const oldIndex = taskIds.indexOf(activeId);
    const newIndex = taskIds.indexOf(overId);
    const newItems = arrayMove(tasks, oldIndex, newIndex);

    setSorted(newItems);

    const property = scope === 'day' ? 'day_order' : 'child_order';
    const data: UpdateData = {
      [property]: newIndex,
    };

    const activeTaskId = activeId.split(':')[1];
    Inertia.post(`/tasks/${activeTaskId}/move`, data, {
      preserveScroll: true,
      onSuccess() {
        // Revert local state.
        setSorted(undefined);
      },
    });
  }

  return (
    <DndContext
      collisionDetection={closestCorners}
      sensors={sensors}
      onDragStart={handleDragStart}
      onDragEnd={handleDragEnd}
    >
      <SortableContext items={taskIds} strategy={verticalListSortingStrategy}>
        {children({items: items, activeTask})}
      </SortableContext>
      <DragOverlay>
        {activeTask ? (
          <div className="dnd-item dnd-item-dragging">
            <DragHandle />
            <TaskRow task={activeTask} showDueOn={showDueOn} />
          </div>
        ) : null}
      </DragOverlay>
    </DndContext>
  );
}
