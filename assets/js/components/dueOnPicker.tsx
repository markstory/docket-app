import React from 'react';
import DayPicker from 'react-day-picker';
import addDays from 'date-fns/addDays';

import {InlineIcon} from 'app/components/icon';
import {parseDate, toDateString} from 'app/utils/dates';
import {Task} from 'app/types';

type Props = {
  selected: Task['due_on'];
  onChange: (value: Task['due_on']) => void;
};

export default function DueOnPicker({selected, onChange}: Props) {
  const selectedDate = typeof selected === 'string' ? parseDate(selected) : undefined;
  const today = toDateString(new Date());
  const tomorrow = toDateString(addDays(new Date(), 1));

  // TODO add a text input.
  // Accept a few different formats. Eg. Dec 25, Wednesday etc
  return (
    <React.Fragment>
      <label htmlFor="task-due-on">Due on</label>
      <span className="form-input-like">{selected ?? 'No Due Date'}</span>
      <div className="put-me-in-context-menu">
        <button onClick={() => onChange(today)}>
          <InlineIcon icon="clippy" /> Today
        </button>
        <button onClick={() => onChange(tomorrow)}>
          <InlineIcon icon="sun" />
          Tommorrow
        </button>
        <button onClick={() => onChange(null)}>
          <InlineIcon icon="trash" />
          No Date
        </button>
        <DayPicker
          disabledDays={{before: new Date()}}
          onDayClick={value => onChange(toDateString(value))}
          selectedDays={selectedDate}
          numberOfMonths={2}
          pagedNavigation
          fixedWeeks
        />
      </div>
    </React.Fragment>
  );
}
