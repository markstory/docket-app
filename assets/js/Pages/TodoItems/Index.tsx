import React from 'react';

import {TodoItem} from 'app/types';
import LoggedIn from 'app/layouts/loggedIn';
import TodoItemGroup from 'app/components/todoItemGroup';
import TodoItemGroupedSorter from 'app/components/todoItemGroupedSorter';

type Props = {
  todoItems: TodoItem[];
};

export default function TodoItemsIndex({todoItems}: Props) {
  return (
    <LoggedIn>
      <h1>Upcoming</h1>
      <TodoItemGroupedSorter todoItems={todoItems} scope="day">
        {({groupedItems}) => (
          <React.Fragment>
            {groupedItems.map(({key, items}) => (
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
        )}
      </TodoItemGroupedSorter>
    </LoggedIn>
  );
}
