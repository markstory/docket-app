import React from 'react';
import {Inertia} from '@inertiajs/inertia';

import {Project} from 'app/types';
import ProjectsContext from 'app/components/projectsContext';

type ChildRenderProps = {
  handleOrderChange: (items: Project[]) => void;
  projects: Project[];
};

type Props = {
  children: (props: ChildRenderProps) => JSX.Element;
};

export default function ProjectSorter({children}: Props) {
  return (
    <ProjectsContext.Consumer>
      {(projects: Project[]) => (
        <ProjectSortHelper projects={projects} child={children} />
      )}
    </ProjectsContext.Consumer>
  );
}

type HelperProps = {
  child: Props['children'];
  projects: Project[];
};

function ProjectSortHelper({child, projects}: HelperProps) {
  const [sorted, setSorted] = React.useState<Project[] | undefined>(undefined);

  function handleChange(items: Project[]) {
    const itemIds = items.map(item => item.id);
    const data = {
      projects: itemIds,
    };
    setSorted(items);
    Inertia.post('/projects/reorder', data);
  }

  const items = sorted || projects;

  return child({projects: items, handleOrderChange: handleChange});
}
