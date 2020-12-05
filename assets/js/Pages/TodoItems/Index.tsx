import React from 'react';
import {groupBy} from 'lodash';

import {TodoItem} from 'app/types';
import LoggedIn from 'app/layouts/loggedIn';
import TodoItemGroup from 'app/components/todoItemGroup';
import TodoItemSorter from 'app/components/todoItemSorter';

type DateItems = {
  date: Date;
  items: TodoItem[];
};

type Props = {
  todoItems: TodoItem[];
};

const ONE_DAY_IN_MS = 1000 * 60 * 60 * 24;

function toDateString(date: Date): string {
  const day = date.getDate();
  return `${date.getFullYear()}-${date.getMonth() + 1}-${day < 10 ? '0' + day : day}`;
}

export default function TodoItemsIndex({todoItems}: Props) {
  const byDate: Record<string, TodoItem[]> = groupBy(
    todoItems,
    item => item.due_on || 'No Due Date'
  );
  const dateStrings = Object.keys(byDate).sort();
  const dates = dateStrings.map(value => new Date(`${value} 00:00:00`));

  const first = (dates.length ? dates[0] : new Date()).getTime();
  // XXX: Time based views are for 28 days at a time.
  const end = first + 28 * ONE_DAY_IN_MS;

  const dateItems: DateItems[] = [];
  for (let i = first; i < end; i += ONE_DAY_IN_MS) {
    const date = new Date(i);
    const dateKey = toDateString(date);
    if (byDate.hasOwnProperty(dateKey)) {
      dateItems.push({date, items: byDate[dateKey]});
    } else {
      dateItems.push({date, items: []});
    }
  }

  return (
    <LoggedIn>
      <h1>Upcoming</h1>
      {dateItems.map(function({date, items}) {
        const key = toDateString(date);
        return (
          <React.Fragment key={key}>
            <h2>{key}</h2>
            <TodoItemSorter todoItems={items} scope="day">
              {({handleOrderChange, items}) => (
                <TodoItemGroup
                  todoItems={items}
                  defaultDate={key}
                  onReorder={handleOrderChange}
                  showProject
                />
              )}
            </TodoItemSorter>
          </React.Fragment>
        );
      })}
    </LoggedIn>
  );
}
