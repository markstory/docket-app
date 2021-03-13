import React, {useEffect, useState} from 'react';
import {MentionsInput, Mention} from 'react-mentions';
import {addDays} from 'date-fns';

import {t} from 'app/locale';
import {Project} from 'app/types';
import {InlineIcon} from '@iconify/react';
import {getToday, toDateString} from 'app/utils/dates';

type Props = {
  defaultValue?: string;
  projects: Project[];
  onChangeDate: (value: string) => void;
  onChangeProject: (value: number) => void;
};

function SmartTaskInput({
  defaultValue,
  projects,
  onChangeDate,
  onChangeProject,
}: Props): JSX.Element {
  const [textValue, setTextValue] = useState(defaultValue ?? ''):
  const [value, setValue] = useState(defaultValue ?? '');

  function handleChange(_, newValue: string) {
    setValue(newValue);
    const newPlainText = newValue.replace(/#[^#]+#/, '').replace(/%[^%]%/, '');
    setTextValue(newPlainText);
  }

  function generateDateOptions(query) {
    const today = getToday();
    // TODO Figure out how to handle months.
    if (!query) {
      return [
        {id: toDateString(today), display: t('Today')},
        {id: toDateString(addDays(today, 1)), display: t('Tomorrow')},
      ]
    }
    return [];
  }

  return (
    <React.Fragment>
      <MentionsInput
        autoFocus
        className="smart-task-input form-input-like"
        value={value}
        onChange={handleChange}
        singleLine
      >
        <Mention
          className="project-mention"
          trigger="#"
          displayTransform={(_id, display) => `#${display}`}
          markup="#__display__:__id__#"
          onAdd={(id) => onChangeProject(Number(id))}
          data={projects.map(project => ({id: project.id, display: project.name}))}
        />
        <Mention
          className="date-mention"
          trigger="%"
          displayTransform={(_id, display) => `%${display}`}
          markup="%__display__:__id__%"
          onAdd={(id) => onChangeDate(String(id))}
          data={generateDateOptions}
        />
      </MentionsInput>
      <input type="hidden" name="title" value={textValue} />
    </React.Fragment>
  );
}

export default SmartTaskInput;
