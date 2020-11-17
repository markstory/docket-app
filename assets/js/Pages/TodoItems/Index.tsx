import React from 'react';
import {groupBy} from 'lodash';

import {TodoItem} from 'app/types';
import LoggedIn from 'app/layouts/loggedIn';
import TodoItemGroup from 'app/components/todoItemGroup';

type Props = {
  todoItems: TodoItem[];
};

export default function TodoItemsIndex({todoItems}: Props) {
  const byDate: Record<string, TodoItem[]> = groupBy(
    todoItems,
    item => item.due_on || 'No Due Date'
  );

  function handleChange(items: TodoItem[]) {
    console.log('new items', items);
  }

  return (
    <LoggedIn>
      <h1>Upcoming</h1>
      {Object.entries(byDate).map(([date, items]) => (
        <React.Fragment key={date}>
          <h2>{date}</h2>
          <TodoItemGroup
            todoItems={items}
            defaultDate={date}
            onReorder={handleChange}
            showDueOn
            showProject
          />
        </React.Fragment>
      ))}
    </LoggedIn>
  );
}
