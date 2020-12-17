import React from 'react';
import {DropResult} from 'react-beautiful-dnd';
import {Inertia} from '@inertiajs/inertia';

import {Project} from 'app/types';
import {useProjects} from 'app/providers/projects';

type ChildRenderProps = {
  onDragEnd: (result: DropResult) => void;
  handleOrderChange: (items: Project[]) => void;
  projects: Project[];
};

type Props = {
  children: (props: ChildRenderProps) => JSX.Element;
};

export default function ProjectSorter({children}: Props) {
  const [projects, setProjects] = useProjects();

  function handleChange(items: Project[]) {
    const itemIds = items.map(item => item.id);
    const data = {
      projects: itemIds,
    };
    setProjects(items);
    Inertia.post('/projects/reorder', data);
  }

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

    // TODO should this use axios instead so we don't repaint?
    Inertia.post(`/projects/${result.draggableId}/move`, data);
  }

  return children({projects, handleOrderChange: handleChange, onDragEnd: handleDragEnd});
}
