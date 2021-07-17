import classnames from 'classnames';
import type {ReactNode, CSSProperties} from 'react';

import {CalendarItem} from 'app/types';
import {toTimeString} from 'app/utils/dates';
import {PROJECT_COLORS} from 'app/constants';

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

  const style = {'--calendar-color': PROJECT_COLORS[item.color].code} as CSSProperties;
  return (
    <div className={classname} style={style}>
      {start}
      {item.title}
    </div>
  );
}

export default CalendarItemList;
