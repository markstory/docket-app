import React from 'react';
import {partition} from 'lodash';

import {TodoItem} from 'app/types';
import LoggedIn from 'app/layouts/loggedIn';
import TodoItemGroup from 'app/components/todoItemGroup';
import TodoItemGroupedSorter, {GroupedItems} from 'app/components/todoItemGroupedSorter';
import {toDateString} from 'app/utils/dates';

type Props = {
  todoItems: TodoItem[];
};

function grouper(items: TodoItem[]): GroupedItems {
  const today = toDateString(new Date());
  const [overdueItems, todayItems] = partition(items, ({due_on}) => due_on !== today);
  const output = [
    {
      key: today,
      items: todayItems,
    },
  ];
  if (overdueItems.length) {
    output.push({
      key: 'overdue',
      items: overdueItems,
    });
  }
  return output;
}

export default function TodoItemsToday({todoItems}: Props) {
  const today = new Date();
  const defaultDate = today.toISOString().substring(0, 10);

  return (
    <LoggedIn>
      <TodoItemGroupedSorter todoItems={todoItems} scope="day" grouper={grouper}>
        {({groupedItems}) => {
          const [today, overdue] = groupedItems;
          return (
            <React.Fragment>
              {overdue && (
                <React.Fragment>
                  <h3>Overdue</h3>
                  <TodoItemGroup
                    dropId="overdue"
                    todoItems={overdue.items}
                    defaultDate={defaultDate}
                    showProject
                    showDueOn
                  />
                </React.Fragment>
              )}
              <h1>Today</h1>
              <TodoItemGroup
                dropId={defaultDate}
                todoItems={today.items}
                defaultDate={defaultDate}
                showProject
              />
            </React.Fragment>
          );
        }}
      </TodoItemGroupedSorter>
    </LoggedIn>
  );
}
