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
import {
  arrayMove,
  sortableKeyboardCoordinates,
  SortableContext,
  verticalListSortingStrategy,
} from '@dnd-kit/sortable';

import {ProjectSection, Task} from 'app/types';

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
  sections: ProjectSection[];
  tasks: Task[];
  children: (args: ChildProps) => React.ReactNode;
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

function ProjectSectionSorter({children, sections, tasks}: Props): JSX.Element {
  const [activeTask, setActiveTask] = useState<Task | undefined>(undefined);
  const [activeSection, setActiveSection] = useState<ProjectSection | undefined>(
    undefined
  );

  const grouped = createGroups(sections, tasks);
  const [sorted, setSorted] = useState<GroupedItems | undefined>(undefined);
  const items = sorted || grouped;

  function handleDragStart({active}: DragStartEvent) {}

  function handleDragEnd({active, over}: DragEndEvent) {}

  function handleDragOver({active, over, draggingRect}: DragOverEvent) {}

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
