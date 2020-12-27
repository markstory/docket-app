import React from 'react';
import DayPicker from 'react-day-picker';
import addDays from 'date-fns/addDays';

import DropdownMenu from './dropdownMenu';
import {InlineIcon} from './icon';
import {formatCompactDate, parseDate, toDateString} from 'app/utils/dates';
import {Task} from 'app/types';

type Props = {
  selected: Task['due_on'];
  onChange: (value: Task['due_on']) => void;
};

export default function DueOnPicker({selected, onChange}: Props) {
  const selectedDate = typeof selected === 'string' ? parseDate(selected) : undefined;

  // TODO add a text input.
  // Accept a few different formats. Eg. Dec 25, Wednesday etc
  return (
    <div className="due-on-picker">
      <DropdownMenu
        button={props => {
          return (
            <button {...props} className="opener">
              <InlineIcon icon="calendar" />{' '}
              {selectedDate ? formatCompactDate(selectedDate) : 'No Due Date'}
            </button>
          );
        }}
        alignMenu="left"
      >
        <MenuContents selected={selectedDate} onChange={onChange} />
      </DropdownMenu>
    </div>
  );
}

type ContentsProps = {
  selected: Date | undefined;
  onChange: Props['onChange'];
};

export function MenuContents({selected, onChange}: ContentsProps) {
  const today = toDateString(new Date());
  const tomorrow = toDateString(addDays(new Date(), 1));

  function handleButtonClick(value: Task['due_on']) {
    return function onClick(event: React.MouseEvent) {
      event.preventDefault();
      onChange(value);
    };
  }
  return (
    <React.Fragment>
      <button className="menu-option" onClick={handleButtonClick(today)}>
        <InlineIcon icon="clippy" /> Today
      </button>
      <button className="menu-option" onClick={handleButtonClick(tomorrow)}>
        <InlineIcon icon="sun" />
        Tommorrow
      </button>
      <button className="menu-option" onClick={handleButtonClick(null)}>
        <InlineIcon icon="trash" />
        No Date
      </button>
      <DayPicker
        disabledDays={{before: new Date()}}
        onDayClick={value => onChange(toDateString(value))}
        selectedDays={selected}
        numberOfMonths={2}
        pagedNavigation
        fixedWeeks
      />
    </React.Fragment>
  );
}
