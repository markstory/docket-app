import React, {useMemo} from 'react';
import {MentionsInput, Mention} from 'react-mentions';
import {addDays, isLeapYear} from 'date-fns';

import {t} from 'app/locale';
import {Project} from 'app/types';
import {getToday, parseDateInput, parseDate, toDateString} from 'app/utils/dates';

type Props = {
  projects: Project[];
  onChangeDate: (value: string) => void;
  onChangeProject: (value: number) => void;
  onChangeTitle: (title: string, textTitle: string) => void;
  value?: string;
};

type MentionOption = {id: string; display: string};

function generateMonth(name: string, end: number): MentionOption[] {
  const today = getToday();
  const options: MentionOption[] = [];
  for (var i = 1; i <= end; i++) {
    const value = `${name} ${i}`;
    const time = parseDateInput(value);
    options.push({id: toDateString(time ?? today), display: value});
  }
  return options;
}

function generateDateOptions(today: string): MentionOption[] {
  const date = parseDate(today);
  return [
    {id: 's:' + today, display: t('Today')},
    {id: 's:' + toDateString(addDays(date, 1)), display: t('Tomorrow')},
    {id: 'r:' + toDateString(parseDateInput('Monday') ?? date), display: 'Monday'},
    {id: 'r:' + toDateString(parseDateInput('Tuesday') ?? date), display: 'Tuesday'},
    {id: 'r:' + toDateString(parseDateInput('Wednesday') ?? date), display: 'Wednesday'},
    {id: 'r:' + toDateString(parseDateInput('Thursday') ?? date), display: 'Thursday'},
    {id: 'r:' + toDateString(parseDateInput('Saturday') ?? date), display: 'Saturday'},
    {id: 'r:' + toDateString(parseDateInput('Sunday') ?? date), display: 'Sunday'},
    {id: 'r:' + toDateString(parseDateInput('Friday') ?? date), display: 'Friday'},
    ...generateMonth('January', 31),
    ...generateMonth('February', isLeapYear(date) ? 29 : 28),
    ...generateMonth('March', 31),
    ...generateMonth('April', 30),
    ...generateMonth('May', 31),
    ...generateMonth('June', 30),
    ...generateMonth('July', 31),
    ...generateMonth('August', 31),
    ...generateMonth('September', 30),
    ...generateMonth('October', 31),
    ...generateMonth('November', 30),
    ...generateMonth('December', 31),
  ];
}

function SmartTaskInput({
  value,
  projects,
  onChangeDate,
  onChangeProject,
  onChangeTitle,
}: Props): JSX.Element {
  const today = toDateString(getToday());
  const dateOptions = useMemo(() => generateDateOptions(today), [today]);

  function handleChange(_: any, newValue: string) {
    const newPlainText = newValue
      .replace(/#[^#]+#/, '')
      .replace(/%[^%]+%/, '')
      .trim();
    onChangeTitle(newValue, newPlainText);
  }

  return (
    <MentionsInput
      autoFocus
      className="smart-task-input"
      value={value}
      onChange={handleChange}
      singleLine
      allowSpaceInQuery
    >
      <Mention
        className="project-mention"
        trigger="#"
        displayTransform={(_id, display) => `#${display}`}
        markup="#__display__:__id__#"
        onAdd={id => onChangeProject(Number(id))}
        data={projects.map(project => ({id: project.id, display: project.name}))}
        appendSpaceOnAdd
      />
      <Mention
        className="date-mention"
        trigger="%"
        displayTransform={(_id, display) => `%${display}`}
        markup="%__display__:__id__%"
        onAdd={id => onChangeDate(String(id).replace(/[sr]:/, ''))}
        data={dateOptions}
        appendSpaceOnAdd
      />
    </MentionsInput>
  );
}

export default SmartTaskInput;
