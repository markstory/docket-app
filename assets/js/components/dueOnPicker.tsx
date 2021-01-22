import React, {useState} from 'react';
import DayPicker from 'react-day-picker';
import addDays from 'date-fns/addDays';

import {t} from 'app/locale';
import {parseDate, parseDateInput, toDateString} from 'app/utils/dates';
import DueOn from 'app/components/dueOn';
import {Task} from 'app/types';

import DropdownMenu from './dropdownMenu';
import {InlineIcon} from './icon';

type Props = {
  selected: Task['due_on'];
  onChange: (value: Task['due_on']) => void;
};

export default function DueOnPicker({selected, onChange}: Props) {
  const selectedDate = typeof selected === 'string' ? parseDate(selected) : undefined;

  // Accept a few different formats. Eg. Dec 25, Wednesday etc
  return (
    <div className="due-on-picker">
      <DropdownMenu
        button={props => {
          return (
            <button {...props} className="button-secondary opener">
              <DueOn value={selectedDate} showNull />
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
  const [inputValue, setInputValue] = useState('');

  function handleButtonClick(value: Task['due_on']) {
    return function onClick() {
      onChange(value);
    };
  }

  function handleInputChange(event: React.ChangeEvent<HTMLInputElement>) {
    const value = event.target.value;
    setInputValue(value);
  }

  function handleInputKeydown(event: React.KeyboardEvent<HTMLInputElement>) {
    const key = event.key;
    if (key === 'Enter') {
      const target = event.target as HTMLInputElement;
      const parsed = parseDateInput(target.value);
      if (parsed) {
        onChange(toDateString(parsed));
      }
    }
  }

  function clickSink(event: React.MouseEvent) {
    const target = event.target as HTMLElement;
    if (target.nodeName === 'INPUT' || target.className.includes('NavButton')) {
      event.stopPropagation();
    }
  }

  return (
    <div className="due-on-menu" onClick={clickSink}>
      <div className="menu-option">
        <input
          type="text"
          onChange={handleInputChange}
          onKeyDown={handleInputKeydown}
          value={inputValue}
          placeholder="Type a due date"
        />
      </div>
      <button
        className="menu-option today"
        data-testid="today"
        onClick={handleButtonClick(today)}
      >
        <InlineIcon icon="clippy" /> {t('Today')}
      </button>
      <button
        className="menu-option tomorrow"
        data-testid="tomorrow"
        onClick={handleButtonClick(tomorrow)}
      >
        <InlineIcon icon="sun" />
        {t('Tommorrow')}
      </button>
      <button
        className="menu-option not-due"
        data-testid="not-due"
        onClick={handleButtonClick(null)}
      >
        <InlineIcon icon="trash" />
        {t('No Due Date')}
      </button>
      <DayPicker
        disabledDays={{before: new Date()}}
        onDayClick={value => onChange(toDateString(value))}
        selectedDays={selected}
        numberOfMonths={2}
        pagedNavigation
        fixedWeeks
      />
    </div>
  );
}
