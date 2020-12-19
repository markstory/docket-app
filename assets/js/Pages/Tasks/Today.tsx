import React from 'react';
import {partition} from 'lodash';

import {Task} from 'app/types';
import LoggedIn from 'app/layouts/loggedIn';
import TaskGroup from 'app/components/taskGroup';
import TaskGroupedSorter, {GroupedItems} from 'app/components/taskGroupedSorter';
import {toDateString} from 'app/utils/dates';

type Props = {
  tasks: Task[];
};

function grouper(items: Task[]): GroupedItems {
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

export default function TasksToday({tasks}: Props) {
  const today = new Date();
  const defaultDate = today.toISOString().substring(0, 10);

  return (
    <LoggedIn>
      <TaskGroupedSorter tasks={tasks} scope="day" grouper={grouper}>
        {({groupedItems}) => {
          const [today, overdue] = groupedItems;
          return (
            <React.Fragment>
              {overdue && (
                <React.Fragment>
                  <h3>Overdue</h3>
                  <TaskGroup
                    dropId="overdue"
                    tasks={overdue.items}
                    defaultDate={defaultDate}
                    showProject
                    showDueOn
                  />
                </React.Fragment>
              )}
              <h1>Today</h1>
              <TaskGroup
                dropId={defaultDate}
                tasks={today.items}
                defaultDate={defaultDate}
                showProject
              />
            </React.Fragment>
          );
        }}
      </TaskGroupedSorter>
    </LoggedIn>
  );
}
