import React from 'react';

import {InlineIcon} from 'app/components/icon';
import {Project} from 'app/types';
import {PROJECT_COLORS} from 'app/constants';

type Props = {
  project: Project;
};

function ProjectBadge({project}: Props) {
  const color = PROJECT_COLORS[project.color].code ?? PROJECT_COLORS[0].code;
  return (
    <span className="project-badge">
      <InlineIcon icon="dot" color={color} width="large" />
      <span>{project.name}</span>
    </span>
  );
}

export default ProjectBadge;
