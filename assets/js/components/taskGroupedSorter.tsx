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
  SortableContext,
  verticalListSortingStrategy,
  sortableKeyboardCoordinates,
} from '@dnd-kit/sortable';
import DragHandle from 'app/components/dragHandle';
import TaskRow from 'app/components/taskRow';

import {Task} from 'app/types';

export type GroupedItems = {key: string; items: Task[]; ids: string[]}[];

type ChildRenderProps = {
  groupedItems: GroupedItems;
  activeTask: Task | null;
};

type Props = {
  tasks: Task[];
  scope: 'day';
  children: (props: ChildRenderProps) => JSX.Element;
  grouper: (tasks: Task[]) => GroupedItems;
};

type UpdateData = {
  child_order?: number;
  day_order?: number;
  due_on?: string;
};

/**
 * Abstraction around reorder lists of tasks and optimistically updating state.
 */
export default function TaskGroupedSorter({
  children,
  tasks,
  grouper,
  scope,
}: Props): JSX.Element {
  const [activeTask, setActiveTask] = useState<Task | null>(null);
  const [sorted, setSorted] = React.useState<GroupedItems | undefined>(undefined);

  const grouped = grouper(tasks);
  const taskIds = grouped.reduce<string[]>((acc, group) => acc.concat(group.ids), []);

  const sensors = useSensors(
    useSensor(PointerSensor),
    useSensor(KeyboardSensor, {
      coordinateGetter: sortableKeyboardCoordinates,
    })
  );

  function handleDragStart(event: DragStartEvent) {
    const activeId = Number(event.active.id.split(':')[1]);
    setActiveTask(tasks.find(p => p.id === activeId) ?? null);
  }

  function handleDragEnd(event: DragEndEvent) {
    const {active, over} = event;
    setActiveTask(null);

    // Dropped outside of a dropzone
    if (!over) {
      return;
    }
    const [activeGroupId, activeTaskId] = active.id.split(':');
    const [overGroupId, overTaskId] = over.id.split(':');

    const newGrouped = [...grouped];
    const sourceGroup = newGrouped.find(group => group.key === activeGroupId);
    const destinationGroup = newGrouped.find(group => group.key === overGroupId);
    if (!destinationGroup || !sourceGroup) {
      return;
    }
    const sourceIndex = sourceGroup.ids.indexOf(active.id);

    // If we don't have an overTaskId, we are moving to an empty group.
    let destinationIndex = 0;
    if (overTaskId.length > 0) {
      destinationIndex = destinationGroup.ids.indexOf(over.id);
    }

    const [moved] = sourceGroup.items.splice(sourceIndex, 1);
    destinationGroup.items.splice(destinationIndex, 0, moved);

    setSorted(newGrouped);

    const property = scope === 'day' ? 'day_order' : 'child_order';
    const data: UpdateData = {
      [property]: destinationIndex,
    };
    if (activeGroupId !== overGroupId) {
      data.due_on = overGroupId;
    }

    Inertia.post(`/tasks/${activeTaskId}/move`, data, {
      preserveScroll: true,
      onSuccess() {
        // Revert local state.
        setSorted(undefined);
      },
    });
  }

  const items = sorted || grouped;

  // TODO figure out how to show the row attributes correctly.
  // TaskGroup could use context to avoid prop drilling.
  // TODO implement onDragOver to avoid shifting elements around.
  return (
    <DndContext
      collisionDetection={closestCorners}
      sensors={sensors}
      onDragStart={handleDragStart}
      onDragEnd={handleDragEnd}
    >
      <SortableContext items={taskIds} strategy={verticalListSortingStrategy}>
        {children({
          groupedItems: items,
          activeTask,
        })}
      </SortableContext>
      <DragOverlay>
        {activeTask ? (
          <div className="dnd-item dnd-item-dragging">
            <DragHandle />
            <TaskRow task={activeTask} />
          </div>
        ) : null}
      </DragOverlay>
    </DndContext>
  );
}
