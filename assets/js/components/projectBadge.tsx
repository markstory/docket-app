import React from 'react';

import {InlineIcon} from 'app/components/icon';
import {Project} from 'app/types';

type Props = {
  project: Project;
};

function ProjectBadge({project}: Props) {
  return (
    <span className="project-badge">
      <InlineIcon icon="dot" color={`#${project.color}`} width="medium" />
      <span>{project.name}</span>
    </span>
  );
}

export default ProjectBadge;
