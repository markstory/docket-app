import React, {useState} from 'react';
import {Inertia} from '@inertiajs/inertia';
import {
  DndContext,
  closestCorners,
  KeyboardSensor,
  PointerSensor,
  useSensor,
  useSensors,
  DragEndEvent,
  DragOverEvent,
  DragStartEvent,
} from '@dnd-kit/core';
import {
  arrayMove,
  sortableKeyboardCoordinates,
  SortableContext,
  verticalListSortingStrategy,
} from '@dnd-kit/sortable';

import {Project, ProjectSection, Task} from 'app/types';
import {insertAtIndex} from 'app/utils/array';

const ROOT = '_root_';

type GroupedItem = {
  /**
   * The id for the group. Has an s: prefix
   * to make separating sections and tasks in drag over/end
   * easier as numeric ids can easily overlap.
   */
  key: string;
  /**
   * The section. Useful for rendering.
   * Can be undefined for the root group.
   */
  section?: ProjectSection;
  /**
   * Tasks in the section
   */
  tasks: Task[];
};
type GroupedItems = GroupedItem[];

type ChildProps = {
  groups: GroupedItems;
  activeTask?: Task;
  activeSection?: ProjectSection;
};

type Props = {
  project: Project;
  tasks: Task[];
  children: (args: ChildProps) => React.ReactNode;
};

type UpdateData = {
  child_order: number;
  section_id?: null | number;
};

function createGroups(sections: ProjectSection[], tasks: Task[]): GroupedItems {
  const sectionTable = tasks.reduce<Record<string, Task[]>>((acc, task) => {
    const sectionId = task.section_id === null ? ROOT : String(task.section_id);
    if (acc[sectionId] === undefined) {
      acc[sectionId] = [];
    }
    acc[sectionId].push(task);
    return acc;
  }, {});

  return [
    {
      key: ROOT,
      section: undefined,
      tasks: sectionTable[ROOT] ?? [],
    },
    ...sections.map(section => {
      return {
        key: `s:${section.id}`,
        section,
        tasks: sectionTable[section.id] ?? [],
      };
    }),
  ];
}

/**
 * Find a group by its group key or task id.
 *
 * Ids can be either section ids (prefixed with s:) or task ids.
 */
function findGroupIndex(groups: GroupedItems, id: string): number {
  const sectionIndex = groups.findIndex(group => group.key === id);
  if (sectionIndex !== -1) {
    return sectionIndex;
  }
  const taskId = Number(id);
  return groups.findIndex(
    group => group.tasks.findIndex(task => task.id === taskId) !== -1
  );
}

function ProjectSectionSorter({children, project, tasks}: Props): JSX.Element {
  const sections = project.sections;
  const [activeTask, setActiveTask] = useState<Task | undefined>(undefined);
  const [activeSection, setActiveSection] = useState<ProjectSection | undefined>(
    undefined
  );

  const grouped = createGroups(sections, tasks);
  const [sorted, setSorted] = useState<GroupedItems | undefined>(undefined);
  const items = sorted || grouped;

  function handleDragStart({active}: DragStartEvent) {
    if (active.id[0] === 's') {
      setActiveSection(items.find(item => item.key === active.id)?.section);
      return;
    }
    const taskId = Number(active.id);
    setActiveTask(tasks.find(task => task.id === taskId));
  }

  function handleDragEnd({active, over}: DragEndEvent) {
    setActiveTask(undefined);
    setActiveSection(undefined);

    // Dropped on nothing, revert.
    if (!over) {
      return;
    }

    // Dragging a section.
    if (active.id[0] === 's') {
      const sectionId = active.id.slice(2);
      let newIndex = items.findIndex(group => group.key === over.id);
      // We don't want anything above the root section.
      if (newIndex < 1) {
        newIndex = 1;
      }

      // Index is -1 because the 0th group is the root one.
      const data = {
        ranking: newIndex - 1,
      };
      Inertia.post(`/projects/${project.slug}/sections/${sectionId}/move`, data, {
        preserveScroll: true,
        onSuccess() {
          setSorted(undefined);
        },
      });
      return;
    }

    // Dragging a task
    const sourceGroupIndex = findGroupIndex(items, active.id);
    const destinationGroupIndex = findGroupIndex(items, over.id);
    if (sourceGroupIndex === -1 || destinationGroupIndex === -1) {
      return;
    }
    const activeId = Number(active.id);

    // Look for the task in the destination group as it should
    // be put here by handleDragOver
    const newIndex = items[destinationGroupIndex].tasks.findIndex(
      task => task.id === activeId
    );
    const sectionId = items[sourceGroupIndex].section?.id;
    const data: UpdateData = {
      child_order: newIndex,
      section_id: sectionId === undefined ? null : sectionId,
    };
    Inertia.post(`/tasks/${activeId}/move`, data, {
      preserveScroll: true,
      onSuccess() {
        setSorted(undefined);
      },
    });
  }

  function handleDragOver({active, over, draggingRect}: DragOverEvent) {
    if (!over) {
      return;
    }

    // Dragging a section.
    if (active.id[0] === 's') {
      const oldIndex = items.findIndex(group => group.key === active.id);
      const newIndex = items.findIndex(group => group.key === over.id);

      setSorted(arrayMove(items, oldIndex, newIndex));
      return;
    }

    // Dragging a task
    const activeGroupIndex = findGroupIndex(items, active.id);
    const overGroupIndex = findGroupIndex(items, over.id);
    if (activeGroupIndex === -1 || overGroupIndex === -1) {
      return;
    }
    const activeId = Number(active.id);
    const overId = Number(over.id);

    // Moving within the same group.
    if (activeGroupIndex === overGroupIndex) {
      const active = items[activeGroupIndex].tasks.find(task => task.id === activeId);
      if (!active) {
        return;
      }
      // Get the current over index before moving the task.
      const overTaskIndex = items[activeGroupIndex].tasks.findIndex(
        task => task.id === overId
      );
      const section = {
        ...items[activeGroupIndex],
        tasks: items[activeGroupIndex].tasks.filter(task => task.id !== activeId),
      };
      section.tasks = insertAtIndex(section.tasks, overTaskIndex, active);

      const newItems = [...items];
      newItems[activeGroupIndex] = section;

      setSorted(newItems);
      return;
    }

    // Moving to a new group.
    const activeGroup = items[activeGroupIndex];
    const overGroup = items[overGroupIndex];

    // Use the id lists to find offsets as using the
    // tasks requires another extract.
    const activeTaskIndex = activeGroup.tasks.findIndex(task => task.id === activeId);
    const overTaskIndex = overGroup.tasks.findIndex(task => task.id === overId);

    const isBelowLastItem =
      over &&
      overTaskIndex === overGroup.tasks.length - 1 &&
      draggingRect.offsetTop > over.rect.offsetTop + over.rect.height;

    const modifier = isBelowLastItem ? 1 : 0;
    const newIndex =
      overTaskIndex >= 0 ? overTaskIndex + modifier : overGroup.tasks.length + 1;

    // Remove the active task from its current group.
    const newActiveGroup = {
      key: activeGroup.key,
      section: activeGroup.section,
      tasks: activeGroup.tasks.filter(task => task.id !== activeId),
    };
    // Splice it into the destination group.
    const newOverGroup = {
      key: overGroup.key,
      section: overGroup.section,
      tasks: insertAtIndex(overGroup.tasks, newIndex, activeGroup.tasks[activeTaskIndex]),
    };

    const newItems = [...items];
    newItems[activeGroupIndex] = newActiveGroup;
    newItems[overGroupIndex] = newOverGroup;

    // This state update allows the faded out task
    // to be placed correctly
    setSorted(newItems);
  }

  const sensors = useSensors(
    useSensor(PointerSensor),
    useSensor(KeyboardSensor, {
      coordinateGetter: sortableKeyboardCoordinates,
    })
  );

  const sectionids = items.map(group => group.key);
  const taskids = items.reduce<string[]>((acc, group) => {
    acc.concat(group.tasks.map(task => String(task.id)));
    return acc;
  }, []);

  return (
    <DndContext
      collisionDetection={closestCorners}
      sensors={sensors}
      onDragStart={handleDragStart}
      onDragEnd={handleDragEnd}
      onDragOver={handleDragOver}
    >
      <SortableContext items={sectionids} strategy={verticalListSortingStrategy}>
        <SortableContext items={taskids} strategy={verticalListSortingStrategy}>
          {children({
            groups: items,
            activeTask,
            activeSection,
          })}
        </SortableContext>
      </SortableContext>
    </DndContext>
  );
}

export default ProjectSectionSorter;
