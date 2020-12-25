import React from 'react';
import {DragDropContext, DropResult} from 'react-beautiful-dnd';
import {Inertia} from '@inertiajs/inertia';

import {Project} from 'app/types';
import {useProjects} from 'app/providers/projects';

type ChildRenderProps = {
  projects: Project[];
};

type Props = {
  children: (props: ChildRenderProps) => JSX.Element;
};

export default function ProjectSorter({children}: Props) {
  const [projects, setProjects] = useProjects();

  function handleDragEnd(result: DropResult) {
    // Dropped outside of a dropzone
    if (!result.destination) {
      return;
    }
    const newItems = [...projects];
    const [moved] = newItems.splice(result.source.index, 1);
    newItems.splice(result.destination.index, 0, moved);

    setProjects(newItems);
    const data = {
      ranking: result.destination.index,
    };

    Inertia.post(`/projects/${result.draggableId}/move`, data, {
      onSuccess() {
        // Revert local state.
        setProjects(null);
      },
    });
  }

  return (
    <DragDropContext onDragEnd={handleDragEnd}>{children({projects})}</DragDropContext>
  );
}
