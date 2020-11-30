import React from 'react';
import {Inertia} from '@inertiajs/inertia';

import {Project} from 'app/types';
import {useProjects} from 'app/providers/projects';

type ChildRenderProps = {
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

  return children({projects, handleOrderChange: handleChange});
}
