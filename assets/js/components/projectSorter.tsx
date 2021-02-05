import React, {useState} from 'react';
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
import {Inertia} from '@inertiajs/inertia';
import classnames from 'classnames';

import {Project} from 'app/types';
import {useProjects} from 'app/providers/projects';
import ProjectItem from './projectItem';
import {Icon} from './icon';

export default function ProjectSorter(): JSX.Element {
  const [projects, setProjects] = useProjects();
  const [activeProject, setActiveProject] = useState<Project | null>(null);
  const projectSlugs = projects.map(p => p.slug);

  const sensors = useSensors(
    useSensor(PointerSensor),
    useSensor(KeyboardSensor, {
      coordinateGetter: sortableKeyboardCoordinates,
    })
  );

  function handleDragStart(event: DragStartEvent) {
    setActiveProject(projects.find(p => p.slug === event.active.id) ?? null);
  }

  function handleDragEnd(event: DragEndEvent) {
    const {active, over} = event;
    setActiveProject(null);

    if (!over) {
      return;
    }
    // Nothing happened.
    if (active.id === over.id) {
      return;
    }

    const oldIndex = projectSlugs.indexOf(active.id);
    const newIndex = projectSlugs.indexOf(over.id);
    const newItems = arrayMove(projects, oldIndex, newIndex);

    setProjects(newItems);
    const data = {
      ranking: newIndex,
    };

    Inertia.post(`/projects/${active.id}/move`, data, {
      onSuccess() {
        // Revert local state.
        setProjects(null);
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
      <SortableContext items={projectSlugs} strategy={verticalListSortingStrategy}>
        <ul className="dnd-dropper-left-offset">
          {projects.map(project => (
            <SortableItem
              key={project.slug}
              id={project.slug}
              active={activeProject?.slug}
              project={project}
            />
          ))}
        </ul>
      </SortableContext>
      <DragOverlay>
        {activeProject ? (
          <li className="dnd-item dnd-item-dragging">
            <button className="dnd-handle" aria-label="Drag to reorder">
              <Icon icon="grabber" width="xlarge" />
            </button>
            <ProjectItem project={activeProject} />
          </li>
        ) : null}
      </DragOverlay>
    </DndContext>
  );
}

type ItemProps = {
  id: string;
  project: Project;
  active?: string;
};
function SortableItem({project, id, active}: ItemProps): JSX.Element {
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
      <button
        className="dnd-handle"
        aria-label="Drag to reorder"
        {...attributes}
        {...listeners}
      >
        <Icon icon="grabber" width="xlarge" />
      </button>
      <ProjectItem key={project.slug} project={project} />
    </li>
  );
}
