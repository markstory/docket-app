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
    console.log('drag start', active);
    if (active.id[0] === 's') {
      setActiveSection(items.find(item => item.key === active.id)?.section);
      return;
    }
    const taskId = Number(active.id);
    setActiveTask(tasks.find(task => task.id === taskId));
  }

  function handleDragEnd({active, over}: DragEndEvent) {
    console.log('drag end', active, over);
    setActiveTask(undefined);
    setActiveSection(undefined);

    // Dropped on nothing, revert.
    if (!over) {
      return;
    }

    // Dragging a section.
    if (active.id[0] === 's') {
      const sectionId = active.id.slice(2);
      const newIndex = items.findIndex(group => group.key === over.id);

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
    }
  }

  function handleDragOver({active, over, draggingRect}: DragOverEvent) {
    console.log('drag over', active, over, draggingRect);
    if (!over) {
      return;
    }

    // Dragging a section.
    if (active.id[0] === 's') {
      const oldIndex = items.findIndex(group => group.key === active.id);
      const newIndex = items.findIndex(group => group.key === over.id);

      setSorted(arrayMove(items, oldIndex, newIndex));
    }
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
