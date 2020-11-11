import React from 'react';

import {Project} from 'app/types';

type Props = {
  project: Project;
};

function ProjectBadge({project}: Props) {
  return (
    <span>
      <svg
        viewBox="0 0 100 100"
        xmlns="http://www.w3.org/2000/svg"
        width="12"
        height="12"
      >
        <circle cx="50" cy="50" r="50" fill={`#${project.color}`} />
      </svg>
      <span>{project.name}</span>
    </span>
  );
}

export default ProjectBadge;
