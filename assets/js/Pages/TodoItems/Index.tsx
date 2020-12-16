import React from 'react';
import {DragDropContext, DropResult} from 'react-beautiful-dnd';

import {TodoItem} from 'app/types';
import LoggedIn from 'app/layouts/loggedIn';
import TodoItemGroup from 'app/components/todoItemGroup';
import TodoItemGroupedSorter from 'app/components/todoItemGroupedSorter';

type DateItems = {
  date: Date;
  items: TodoItem[];
};

type Props = {
  todoItems: TodoItem[];
};

export default function TodoItemsIndex({todoItems}: Props) {
  return (
    <LoggedIn>
      <h1>Upcoming</h1>
      <TodoItemGroupedSorter todoItems={todoItems} scope="day">
        {({groupedItems, onDragEnd}) => (
          <DragDropContext onDragEnd={onDragEnd}>
            {groupedItems.map(function({key, items}) {
              // The key must be used as
              return (
                <React.Fragment key={key}>
                  <h2>{key}</h2>
                  <TodoItemGroup
                    dropId={key}
                    todoItems={items}
                    defaultDate={key}
                    showProject
                  />
                </React.Fragment>
              );
            })}
          </DragDropContext>
        )}
      </TodoItemGroupedSorter>
    </LoggedIn>
  );
}
