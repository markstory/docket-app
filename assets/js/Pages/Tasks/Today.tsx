import React from 'react';
import {partition} from 'lodash';
import {SortableContext, verticalListSortingStrategy} from '@dnd-kit/sortable';

import {Task} from 'app/types';
import {t} from 'app/locale';
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
      key: 'overdue',
      items: overdueItems,
      ids: overdueItems.map(task => String(task.id)),
    },
    {
      key: today,
      items: todayItems,
      ids: todayItems.map(task => String(task.id)),
    },
  ];
  return output;
}

export default function TasksToday({tasks}: Props): JSX.Element {
  const today = new Date();
  const defaultDate = toDateString(today);

  return (
    <LoggedIn title={t("Today's Tasks")}>
      <TaskGroupedSorter tasks={tasks} scope="day" grouper={grouper}>
        {({groupedItems, activeTask}) => {
          const [overdue, today] = groupedItems;
          return (
            <React.Fragment>
              {overdue.ids && (
                <SortableContext
                  items={overdue.ids}
                  strategy={verticalListSortingStrategy}
                >
                  <h2>{t('Overdue')}</h2>
                  <TaskGroup
                    dropId={overdue.key}
                    tasks={overdue.items}
                    activeTask={activeTask}
                    showProject
                    showDueOn
                    showAdd={false}
                  />
                </SortableContext>
              )}
              <SortableContext items={today.ids} strategy={verticalListSortingStrategy}>
                <h2>{t('Today')}</h2>
                <TaskGroup
                  dropId={today.key}
                  tasks={today.items}
                  activeTask={activeTask}
                  defaultDate={defaultDate}
                  showProject
                />
              </SortableContext>
            </React.Fragment>
          );
        }}
      </TaskGroupedSorter>
    </LoggedIn>
  );
}
