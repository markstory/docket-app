import React from 'react';
import {groupBy} from 'lodash';

import {TodoItem} from 'app/types';
import LoggedIn from 'app/layouts/loggedIn';
import TodoItemGroup from 'app/components/todoItemGroup';
import TodoItemSorter from 'app/components/todoItemSorter';

type Props = {
  todoItems: TodoItem[];
};

export default function TodoItemsIndex({todoItems}: Props) {
  const byDate: Record<string, TodoItem[]> = groupBy(
    todoItems,
    item => item.due_on || 'No Due Date'
  );

  return (
    <LoggedIn>
      <h1>Upcoming</h1>
      {Object.entries(byDate).map(([date, dateTodos]) => (
        <React.Fragment key={date}>
          <h2>{date}</h2>
          <TodoItemSorter todoItems={dateTodos} scope="day">
            {({handleOrderChange, items}) => (
              <TodoItemGroup
                todoItems={items}
                defaultDate={date}
                onReorder={handleOrderChange}
                showDueOn
                showProject
              />
            )}
          </TodoItemSorter>
        </React.Fragment>
      ))}
    </LoggedIn>
  );
}
