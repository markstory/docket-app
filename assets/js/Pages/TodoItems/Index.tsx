import React from 'react';
import {sortBy} from 'lodash';

import {TodoItem} from 'app/types';
import LoggedIn from 'app/layouts/loggedIn';
import TodoItemGroup from 'app/components/todoItemGroup';
import TodoItemGroupedSorter, {GroupedItems} from 'app/components/todoItemGroupedSorter';
import {toDateString} from 'app/utils/dates';

type Props = {
  todoItems: TodoItem[];
};

const ONE_DAY_IN_MS = 1000 * 60 * 60 * 24;

function parseDate(input: string): Date {
  const date = new Date(input);
  date.setTime(date.getTime() + date.getTimezoneOffset() * 60 * 1000);
  return date;
}

/**
 * Fill out the sparse input data to have all the days.
 */
function zeroFillItems(groups: GroupedItems): GroupedItems {
  const sorted = sortBy(groups, group => group.key);

  const first = (sorted.length ? parseDate(sorted[0].key) : new Date()).getTime();
  // XXX: Time based views are for 28 days at a time.
  const end = first + 28 * ONE_DAY_IN_MS;

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

export default function TodoItemsIndex({todoItems}: Props) {
  return (
    <LoggedIn>
      <h1>Upcoming</h1>
      <TodoItemGroupedSorter todoItems={todoItems} scope="day">
        {({groupedItems}) => {
          const calendarGroups = zeroFillItems(groupedItems);
          return (
            <React.Fragment>
              {calendarGroups.map(({key, items}) => (
                <React.Fragment key={key}>
                  <h2>{key}</h2>
                  <TodoItemGroup
                    dropId={key}
                    todoItems={items}
                    defaultDate={key}
                    showProject
                  />
                </React.Fragment>
              ))}
            </React.Fragment>
          );
        }}
      </TodoItemGroupedSorter>
    </LoggedIn>
  );
}
