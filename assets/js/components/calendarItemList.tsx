import classnames from 'classnames';
import type {ReactNode, CSSProperties} from 'react';

import {CalendarItem} from 'app/types';
import {toDateString, toTimeString} from 'app/utils/dates';
import {PROJECT_COLORS} from 'app/constants';

type Props = {
  items: CalendarItem[];
  date: string;
};

function CalendarItemList({date, items}: Props) {
  return (
    <div className="calendar-item-list">
      {items.map(item => (
        <CalendarListItem key={item.id} date={date} item={item} />
      ))}
    </div>
  );
}

type ItemProps = {
  item: CalendarItem;
  date: string;
};

function CalendarListItem({date, item}: ItemProps) {
  let start: ReactNode = '';
  let allDay = item.all_day;
  if (!item.all_day) {
    const startTime = new Date(item.start_time);
    if (toDateString(startTime) === date) {
      start = <time dateTime={item.start_time}>{toTimeString(startTime)}</time>;
    } else {
      allDay = true;
    }
  }
  const classname = classnames('calendar-item', {
    'all-day': allDay,
  });

  const style = {'--calendar-color': PROJECT_COLORS[item.color].code} as CSSProperties;
  return (
    <div className={classname} style={style}>
      {start}
      <a href={item.html_link} target="_blank" rel="noreferrer">
        {item.title}
      </a>
    </div>
  );
}

export default CalendarItemList;
