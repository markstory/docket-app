/** @jsx jsx */
import {jsx} from '@emotion/core';
import Select, {
  OptionTypeBase,
  ValueType,
  OptionProps,
  SingleValueProps,
} from 'react-select';

import ProjectBadge from 'app/components/projectBadge';
import {useProjects} from 'app/providers/projects';

function ProjectOption(props: OptionProps<OptionTypeBase, false>) {
  const {getStyles, innerRef, innerProps, data} = props;
  return (
    <div css={getStyles('option', props)} ref={innerRef} {...innerProps}>
      <ProjectBadge project={data.project} />
    </div>
  );
}

function ProjectValue(props: SingleValueProps<OptionTypeBase>) {
  const {getStyles, innerProps, data} = props;
  return (
    <div css={getStyles('singleValue', props)} {...innerProps}>
      <ProjectBadge project={data.project} />
    </div>
  );
}

type OptionType = ValueType<OptionTypeBase, false>;

type Props = {
  value: number | string | undefined | null;
  onChange?: (value: OptionType) => void;
};

function ProjectSelect({value, onChange}: Props) {
  const [projects] = useProjects();
  const options = projects.map(project => ({
    value: project.id,
    label: project.name,
    project,
  }));
  const selected = value ? value : options?.[0]?.value;
  const valueOption = options.find(opt => opt.value === selected);

  return (
    <Select
      classNamePrefix="project-select"
      defaultValue={valueOption}
      menuPlacement="auto"
      name="project_id"
      options={options}
      onChange={onChange}
      components={{
        Option: ProjectOption,
        SingleValue: ProjectValue,
      }}
    />
  );
}

export default ProjectSelect;
