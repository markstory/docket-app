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
  showProject?: boolean;
  showDueOn?: boolean;
};

type UpdateData = {
  child_order?: number;
  day_order?: number;
  due_on?: string;
  evening?: boolean;
};

function insertAtIndex<Item>(items: Item[], index: number, insert: Item): Item[] {
  return [...items.slice(0, index), insert, ...items.slice(index, items.length)];
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
  scope,
  showProject,
  showDueOn,
}: Props): JSX.Element {
  const grouped = grouper(tasks);
  const [activeTask, setActiveTask] = useState<Task | null>(null);
  const [sorted, setSorted] = React.useState<GroupedItems | undefined>(undefined);
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

    const property = scope === 'day' ? 'day_order' : 'child_order';
    const data: UpdateData = {
      [property]: destinationIndex,
    };
    const task = items[sourceGroupIndex].items[sourceIndex];

    const newItems = [...items];
    newItems[sourceGroupIndex].items = arrayMove(
      newItems[sourceGroupIndex].items,
      sourceIndex,
      destinationIndex
    );
    if (scope === 'day') {
      let isEvening = false;
      let newDate = destinationGroup.key;
      if (newDate.includes('evening:')) {
        isEvening = true;
        newDate = newDate.substring(8);
      }
      if (isEvening !== task.evening) {
        data.evening = isEvening;
      }
      if (newDate !== task.due_on) {
        data.due_on = newDate;
      }
    }

    setSorted(newItems);

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
