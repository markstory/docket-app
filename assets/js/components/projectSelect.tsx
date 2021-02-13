import React, {useState} from 'react';

import {
  Combobox,
  ComboboxInput,
  ComboboxPopover,
  ComboboxList,
  ComboboxOption,
} from '@reach/combobox';

import {Project} from 'app/types';
import {t} from 'app/locale';
import ProjectBadge from 'app/components/projectBadge';
import {useProjects} from 'app/providers/projects';

type Props = {
  value: number | undefined | null;
  onChange?: (value: number | string | undefined | null) => void;
};

function ProjectSelect({value, onChange}: Props): JSX.Element {
  const [projects] = useProjects();
  const [current, setCurrent] = useState<number | undefined>(value ?? undefined);
  const [term, setTerm] = useState<string>('');
  const [options, setOptions] = useState<Project[]>(projects);

  let selected = '';
  if (term) {
    selected = projects.find(project => project.name === term)?.name ?? '';
  } else if (value) {
    selected = projects.find(project => project.id === value)?.name ?? '';
  }
  if (!selected) {
    selected = projects[0]?.name;
  }

  function handleChange(event: React.ChangeEvent<HTMLInputElement>) {
    const {value} = event.target;
    setOptions(
      value
        ? projects.filter(project =>
            project.name.toLowerCase().includes(value.toLowerCase())
          )
        : [...projects]
    );
    setTerm(value);
  }

  function handleSelect(value: string) {
    const selected = projects.find(project => project.name === value);
    if (!selected) {
      return;
    }
    setTerm(selected.name);
    setCurrent(selected.id);
    onChange?.(selected.id);
  }

  return (
    <Combobox aria-label={t('Choose a project')} onSelect={handleSelect} openOnFocus>
      <input type="hidden" value={current} name="project_id" />
      <ComboboxInput value={term || selected} onChange={handleChange} selectOnClick />
      <ComboboxPopover>
        <ComboboxList persistSelection>
          {options.map(project => (
            <ComboboxOption key={project.id} value={project.name}>
              <ProjectBadge project={project} />
            </ComboboxOption>
          ))}
          {!options.length && <div className="combobox-empty">{t('No results')}</div>}
        </ComboboxList>
      </ComboboxPopover>
    </Combobox>
  );
}

export default ProjectSelect;
