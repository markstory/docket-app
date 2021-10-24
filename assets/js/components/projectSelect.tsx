import classnames from 'classnames';
import {useEffect} from 'react';
import Select, {ValueType, OptionProps, SingleValueProps} from 'react-select';

import {Project} from 'app/types';
import ProjectBadge from 'app/components/projectBadge';
import {t} from 'app/locale';
import {useProjects} from 'app/providers/projects';

type ProjectItem = {
  value: number;
  label: string;
  project: Project;
};

type Props = {
  value: number | undefined | null;
  onChange?: (value: number) => void;
};

function ProjectOption(props: OptionProps<ProjectItem, false>) {
  const {innerRef, innerProps, data} = props;
  const className = classnames({
    'is-selected': props.isSelected,
    'is-focused': props.isFocused,
  });
  return (
    <div className={className} ref={innerRef} {...innerProps}>
      <ProjectBadge project={data.project} />
    </div>
  );
}

function ProjectValue(props: SingleValueProps<ProjectItem>) {
  const {innerProps, data} = props;
  return (
    <div {...innerProps}>
      <ProjectBadge project={data.project} />
    </div>
  );
}

function ProjectSelect({value, onChange}: Props): JSX.Element {
  const portal = document.createElement('div');
  portal.setAttribute('id', 'project-select-portal');

  useEffect(() => {
    const app = document.getElementById('app');
    if (!app) {
      throw new Error('Could not find app element to mount portal');
    }
    app.appendChild(portal);

    return function cleanup() {
      if (portal) {
        app.removeChild(portal);
      }
    };
  }, []);

  const [projects] = useProjects();
  const options: ProjectItem[] = projects.map(project => ({
    value: project.id,
    label: project.name,
    project: project,
  }));
  const valueOption = options.find(opt => opt.value === value) || options[0];

  function handleChange(selected: ValueType<ProjectItem, false>) {
    if (selected && onChange) {
      onChange(selected.project.id);
    }
  }

  return (
    <Select
      classNamePrefix="select"
      placeholder={t('Choose a project')}
      menuIsOpen={true}
      name="project_id"
      value={valueOption}
      options={options}
      onChange={handleChange}
      getOptionValue={option => String(option.value)}
      components={{
        Option: ProjectOption,
        SingleValue: ProjectValue,
        IndicatorSeparator: null,
      }}
      menuPortalTarget={portal}
      menuPlacement="auto"
    />
  );
}

export default ProjectSelect;
