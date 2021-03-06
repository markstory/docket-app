import React, {useState} from 'react';
import {createPortal} from 'react-dom';
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
  DragOverEvent,
  DragStartEvent,
} from '@dnd-kit/core';
import {arrayMove, sortableKeyboardCoordinates} from '@dnd-kit/sortable';
import DragHandle from 'app/components/dragHandle';
import TaskRow from 'app/components/taskRow';

import {Task} from 'app/types';
import {insertAtIndex} from 'app/utils/array';

export type GroupedItems = {key: string; items: Task[]; ids: string[]}[];
export interface UpdaterCallback {
  (task: Task, newIndex: number, destinationKey: string): UpdateData;
}

type ChildRenderProps = {
  groupedItems: GroupedItems;
  activeTask: Task | null;
};

type Props = {
  tasks: Task[];
  children: (props: ChildRenderProps) => JSX.Element;
  grouper: (tasks: Task[]) => GroupedItems;
  updater: UpdaterCallback;
  showProject?: boolean;
  showDueOn?: boolean;
};

export interface UpdateData {
  child_order?: number;
  day_order?: number;
  due_on?: string;
  evening?: boolean;
}

/**
 * Find a group by its group key or task id.
 *
 * Group keys are received when a SortableItem goes over an empty
 * Droppable. Otherwise the id will be another Sortable (task).
 */
function findGroupIndex(groups: GroupedItems, id: string): number {
  return groups.findIndex(group => group.key === id || group.ids.includes(id));
}

/**
 * Abstraction around reorder lists of tasks and optimistically updating state.
 */
export default function TaskGroupedSorter({
  children,
  tasks,
  grouper,
  updater,
  showProject,
  showDueOn,
}: Props): JSX.Element {
  const grouped = grouper(tasks);
  const [activeTask, setActiveTask] = useState<Task | null>(null);
  const [sorted, setSorted] = useState<GroupedItems | undefined>(undefined);
  const items = sorted || grouped;

  const sensors = useSensors(
    useSensor(PointerSensor),
    useSensor(KeyboardSensor, {
      coordinateGetter: sortableKeyboardCoordinates,
    })
  );

  function handleDragStart({active}: DragStartEvent) {
    const activeId = Number(active.id);
    setActiveTask(tasks.find(p => p.id === activeId) ?? null);
  }

  function handleDragEnd({active, over}: DragEndEvent) {
    setActiveTask(null);
    // Dropped outside of a dropzone
    if (!over) {
      return;
    }
    const overId = over?.id || '';
    const sourceGroupIndex = findGroupIndex(items, active.id);
    const destinationGroupIndex = findGroupIndex(items, overId);

    // If either group couldn't be found bail.
    if (sourceGroupIndex === -1 || destinationGroupIndex === -1) {
      return;
    }
    const sourceIndex = items[sourceGroupIndex].ids.indexOf(active.id);
    if (sourceIndex === -1) {
      return;
    }
    const destinationGroup = items[destinationGroupIndex];
    let destinationIndex = destinationGroup.ids.indexOf(overId);
    if (destinationIndex === -1) {
      destinationIndex = 0;
    }
    const task = items[sourceGroupIndex].items[sourceIndex];

    // This looks like duplicate code, however it ensures
    // that the active item doesn't animate back to an earlier position
    const newItems = [...items];
    newItems[sourceGroupIndex].items = arrayMove(
      newItems[sourceGroupIndex].items,
      sourceIndex,
      destinationIndex
    );
    setSorted(newItems);

    const data = updater(task, destinationIndex, destinationGroup.key);

    Inertia.post(`/tasks/${active.id}/move`, data, {
      preserveScroll: true,
      onSuccess() {
        setSorted(undefined);
      },
    });
  }

  function handleDragOver({active, over, draggingRect}: DragOverEvent) {
    const overId = over?.id || '';

    const activeGroupIndex = findGroupIndex(items, active.id);
    const overGroupIndex = findGroupIndex(items, overId);
    if (
      activeGroupIndex === -1 ||
      overGroupIndex === -1 ||
      activeGroupIndex === overGroupIndex
    ) {
      return;
    }
    const activeGroup = items[activeGroupIndex];
    const overGroup = items[overGroupIndex];

    // Use the id lists to find offsets as using the
    // tasks requires another extract.
    const activeTaskIndex = activeGroup.ids.indexOf(active.id);
    const overTaskIndex = overGroup.ids.indexOf(overId);

    const isBelowLastItem =
      over &&
      overTaskIndex === overGroup.ids.length - 1 &&
      draggingRect.offsetTop > over.rect.offsetTop + over.rect.height;

    const modifier = isBelowLastItem ? 1 : 0;
    const newIndex =
      overTaskIndex >= 0 ? overTaskIndex + modifier : overGroup.ids.length + 1;
    const activeId = Number(active.id);

    // Remove the active item from its current group.
    const newActiveGroup = {
      key: activeGroup.key,
      items: activeGroup.items.filter(task => task.id !== activeId),
      ids: activeGroup.ids.filter(id => id !== active.id),
    };
    // Splice it into the destination group.
    const newOverGroup = {
      key: overGroup.key,
      items: insertAtIndex(overGroup.items, newIndex, activeGroup.items[activeTaskIndex]),
      ids: insertAtIndex(overGroup.ids, newIndex, active.id),
    };

    const newItems = [...items];
    newItems[activeGroupIndex] = newActiveGroup;
    newItems[overGroupIndex] = newOverGroup;

    // This state update allows the faded out task
    // to be placed correctly
    setSorted(newItems);
  }

  return (
    <DndContext
      collisionDetection={closestCorners}
      sensors={sensors}
      onDragStart={handleDragStart}
      onDragEnd={handleDragEnd}
      onDragOver={handleDragOver}
    >
      {children({
        groupedItems: items,
        activeTask,
      })}
      {createPortal(
        <DragOverlay>
          {activeTask ? (
            <div className="dnd-item dnd-item-dragging">
              <DragHandle />
              <TaskRow
                task={activeTask}
                showProject={showProject}
                showDueOn={showDueOn}
              />
            </div>
          ) : null}
        </DragOverlay>,
        document.body
      )}
    </DndContext>
  );
}
