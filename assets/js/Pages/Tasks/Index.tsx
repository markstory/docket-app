import React from 'react';
import {sortBy} from 'lodash';
import {InertiaLink} from '@inertiajs/inertia-react';
import {groupBy} from 'lodash';

import {t} from 'app/locale';
import {Task} from 'app/types';
import LoggedIn from 'app/layouts/loggedIn';
import TaskGroup from 'app/components/taskGroup';
import TaskGroupedSorter, {GroupedItems} from 'app/components/taskGroupedSorter';
import {toDateString, formatDateHeading, parseDate, ONE_DAY_IN_MS} from 'app/utils/dates';

type Props = {
  tasks: Task[];
  start: string;
  nextStart: string;
};

/**
 * Fill out the sparse input data to have all the days.
 */
function zeroFillItems(
  start: string,
  numDays: number,
  groups: GroupedItems
): GroupedItems {
  const sorted = sortBy(groups, group => group.key);

  const first = parseDate(start).getTime();
  const end = first + numDays * ONE_DAY_IN_MS;

  const complete: GroupedItems = [];
  for (let i = first; i < end; i += ONE_DAY_IN_MS) {
    const date = new Date(i);
    const dateKey = toDateString(date);
    if (sorted.length && sorted[0].key === dateKey) {
      const values = sorted.shift();
      if (values) {
        complete.push(values);
      }
    } else {
      complete.push({key: dateKey, items: []});
    }
  }
  return complete;
}

function createGrouper(start: string, numDays: number) {
  return function taskGrouper(items: Task[]): GroupedItems {
    const byDate: Record<string, Task[]> = groupBy(items, item =>
      item.due_on ? item.due_on : t('No Due Date')
    );
    const grouped = Object.entries(byDate).map(([key, value]) => {
      return {key, items: value};
    });
    return zeroFillItems(start, numDays, grouped);
  };
}

export default function TasksIndex({tasks, start, nextStart}: Props) {
  const nextPage = nextStart ? `/todos/upcoming?start=${nextStart}` : null;
  return (
    <LoggedIn>
      <h1>Upcoming</h1>
      <TaskGroupedSorter tasks={tasks} scope="day" grouper={createGrouper(start, 28)}>
        {({groupedItems}) => {
          return (
            <React.Fragment>
              {groupedItems.map(({key, items}) => {
                const [heading, subheading] = formatDateHeading(key);
                return (
                  <React.Fragment key={key}>
                    <h3>
                      {heading}
                      {subheading && <span className="minor">{subheading}</span>}
                    </h3>
                    <TaskGroup dropId={key} tasks={items} defaultDate={key} showProject />
                  </React.Fragment>
                );
              })}
            </React.Fragment>
          );
        }}
      </TaskGroupedSorter>
      <div className="button-bar">
        {nextPage && (
          <InertiaLink className="button button-secondary" href={nextPage}>
            {t('Next')}
          </InertiaLink>
        )}
      </div>
    </LoggedIn>
  );
}
