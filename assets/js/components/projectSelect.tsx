import React from 'react';
import Autocomplete, {Option} from 'app/components/autocomplete';
import ProjectBadge from 'app/components/projectBadge';
import {t} from 'app/locale';
import {useProjects} from 'app/providers/projects';

type Props = {
  value: number | undefined | null;
  onChange?: (value: number | string) => void;
};

function ProjectSelect({value, onChange}: Props): JSX.Element {
  const [projects] = useProjects();
  const options: Option[] = projects.map(project => ({
    value: project.id,
    text: project.name,
    label: <ProjectBadge project={project} />,
  }));
  return (
    <Autocomplete
      label={t('Choose a project')}
      name="project_id"
      value={value}
      options={options}
      onChange={onChange}
    />
  );
}

export default ProjectSelect;
