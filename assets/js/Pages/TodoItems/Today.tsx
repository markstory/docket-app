import React from 'react';

import {TodoItem} from 'app/types';
import LoggedIn from 'app/layouts/loggedIn';
import TodoItemGroup from 'app/components/todoItemGroup';
import TodoItemSorter from 'app/components/todoItemSorter';

type Props = {
  todoItems: TodoItem[];
  view: string | null;
};

export default function TodoItemsToday({todoItems, view}: Props) {
  const defaultDate =
    view === 'today' ? new Date().toISOString().substring(0, 10) : undefined;

  return (
    <LoggedIn>
      <h1>Today</h1>
      <TodoItemSorter todoItems={todoItems} scope="day">
        {({items}) => (
          <TodoItemGroup
            dropId="today"
            todoItems={items}
            defaultDate={defaultDate}
            showProject
          />
        )}
      </TodoItemSorter>
    </LoggedIn>
  );
}
