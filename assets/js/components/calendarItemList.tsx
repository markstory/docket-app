import classnames from 'classnames';
import type {ReactNode} from 'react';

import {CalendarItem} from 'app/types';
import {toTimeString} from 'app/utils/dates';

type Props = {
  items: CalendarItem[];
};

function CalendarItemList({items}: Props) {
  return (
    <div className="calendar-item-list">
      {items.map(item => (
        <CalendarListItem key={item.id} item={item} />
      ))}
    </div>
  );
}

type ItemProps = {
  item: CalendarItem;
};

function CalendarListItem({item}: ItemProps) {
  const classname = classnames('calendar-item', {
    'all-day': item.all_day,
  });
  let start: ReactNode = '';
  if (!item.all_day) {
    const startTime = new Date(item.start_time);
    start = <time dateTime={item.start_time}>{toTimeString(startTime)}</time>;
  }

  return (
    <div className={classname}>
      {start}
      {item.title}
    </div>
  );
}

export default CalendarItemList;
