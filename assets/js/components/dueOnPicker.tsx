import {useEffect, useRef, useState} from 'react';
import {MenuButton, MenuItem} from '@reach/menu-button';
import DayPicker from 'react-day-picker';
import addDays from 'date-fns/addDays';
import startOfWeek from 'date-fns/startOfWeek';

import {t} from 'app/locale';
import {
  parseDate,
  parseDateInput,
  toDateString,
  formatCompactDate,
} from 'app/utils/dates';
import DueOn from 'app/components/dueOn';
import {Task} from 'app/types';

import DropdownMenu from './dropdownMenu';
import {InlineIcon} from './icon';
import ToggleCheckbox from './toggleCheckbox';

type Props = {
  task: Task;
  onChange: (dueOn: Task['due_on'], evening: boolean) => void;
};

export default function DueOnPicker({task, onChange}: Props): JSX.Element {
  // Accept a few different formats. Eg. Dec 25, Wednesday etc
  return (
    <div className="due-on-picker">
      <DropdownMenu
        button={() => (
          <MenuButton className="button-secondary" data-testid="due-on">
            <DueOn task={task} showNull />
          </MenuButton>
        )}
      >
        <MenuContents task={task} onChange={onChange} />
      </DropdownMenu>
    </div>
  );
}

type ContentsProps = {
  task: Task;
  onChange: Props['onChange'];
};

export function MenuContents({task, onChange}: ContentsProps): JSX.Element {
  const todayDate = new Date();
  const today = toDateString(todayDate);
  const tomorrow = toDateString(addDays(todayDate, 1));
  const [inputValue, setInputValue] = useState(task.due_on ?? '');
  const inputRef = useRef<HTMLInputElement>(null);
  const dueOn = typeof task.due_on === 'string' ? parseDate(task.due_on) : undefined;

  useEffect(() => {
    setTimeout(() => {
      if (!inputRef.current) {
        return;
      }
      inputRef.current.focus();
    }, 1);
  }, [inputRef.current]);

  function handleButtonClick(newDueOn: Task['due_on'], newEvening: boolean) {
    return function onClick() {
      onChange(newDueOn, newEvening);
    };
  }

  function handleInputChange(event: React.ChangeEvent<HTMLInputElement>) {
    const value = event.target.value;
    setInputValue(value);
  }

  function handleEveningChange(event: React.ChangeEvent<HTMLInputElement>) {
    const checked = event.target.checked;
    onChange(task.due_on, checked);
  }

  function handleInputKeydown(event: React.KeyboardEvent<HTMLInputElement>) {
    const key = event.key;
    if (key === 'Enter') {
      const target = event.target as HTMLInputElement;
      const parsed = parseDateInput(target.value);
      if (parsed) {
        onChange(toDateString(parsed), task.evening);
      }
    }
  }

  function clickSink(event: React.MouseEvent) {
    const target = event.target as HTMLElement;
    if (target.nodeName === 'INPUT' || target.className.includes('NavButton')) {
      event.stopPropagation();
    }
  }
  const daypickerModifiers = {
    past: {
      before: startOfWeek(todayDate),
    },
  };
  const isToday = task.due_on === today && task.evening === false;
  const isThisEvening = task.due_on === today && task.evening === true;
  const isTomorrow = task.due_on === tomorrow;
  const isEvening = task.evening;
  const futureDue = task.due_on !== null && task.due_on !== today;

  return (
    <div className="due-on-menu" onClick={clickSink}>
      <div data-reach-menu-item>
        <input
          ref={inputRef}
          type="text"
          onChange={handleInputChange}
          onKeyDown={handleInputKeydown}
          value={inputValue}
          placeholder="Type a due date"
        />
      </div>
      {!isToday && (
        <MenuItem
          className="today"
          data-testid="today"
          onSelect={handleButtonClick(today, false)}
        >
          <InlineIcon icon="clippy" /> {t('Today')}
        </MenuItem>
      )}
      {!isThisEvening && (
        <MenuItem
          className="evening"
          data-testid="evening"
          onSelect={handleButtonClick(today, true)}
        >
          <InlineIcon icon="moon" /> {t('This Evening')}
        </MenuItem>
      )}
      {!isTomorrow && (
        <MenuItem
          className="tomorrow"
          data-testid="tomorrow"
          onSelect={handleButtonClick(tomorrow, task.evening)}
        >
          <InlineIcon icon="sun" />
          {t('Tommorrow')}
        </MenuItem>
      )}
      {futureDue && isEvening && (
        <MenuItem
          className="tomorrow"
          data-testid="remove-evening"
          onSelect={handleButtonClick(task.due_on, false)}
        >
          <InlineIcon icon="calendar" />
          {t('{date} day', {date: formatCompactDate(task.due_on ?? '')})}
        </MenuItem>
      )}
      {futureDue && !isEvening && (
        <MenuItem
          className="evening"
          data-testid="add-evening"
          onSelect={handleButtonClick(task.due_on, true)}
        >
          <InlineIcon icon="calendar" />
          {t('{date} evening', {date: formatCompactDate(task.due_on ?? '')})}
        </MenuItem>
      )}
      <MenuItem
        className="not-due"
        data-testid="not-due"
        onSelect={handleButtonClick(null, task.evening)}
      >
        <InlineIcon icon="trash" />
        {t('No Due Date')}
      </MenuItem>
      <DayPicker
        disabledDays={{before: new Date()}}
        onDayClick={value => onChange(toDateString(value), task.evening)}
        modifiers={daypickerModifiers}
        fromMonth={todayDate}
        selectedDays={dueOn}
        todayButton={t('Today')}
        pagedNavigation
        numberOfMonths={2}
      />
      <div className="dropdown-item-text">
        <ToggleCheckbox
          name="evening"
          checked={task.evening}
          onChange={handleEveningChange}
          label={
            <React.Fragment>
              <InlineIcon icon="moon" />
              {t('Evening')}
            </React.Fragment>
          }
        />
      </div>
    </div>
  );
}
