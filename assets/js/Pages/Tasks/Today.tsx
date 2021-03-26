import React from 'react';
import {SortableContext, verticalListSortingStrategy} from '@dnd-kit/sortable';

import {Task} from 'app/types';
import {todaySortUpdater} from 'app/actions/tasks';
import {t} from 'app/locale';
import {Icon} from 'app/components/icon';
import LoggedIn from 'app/layouts/loggedIn';
import TaskGroup from 'app/components/taskGroup';
import TaskGroupedSorter, {GroupedItems} from 'app/components/taskGroupedSorter';
import {toDateString} from 'app/utils/dates';

type Props = {
  tasks: Task[];
};

function grouper(items: Task[]): GroupedItems {
  const today = toDateString(new Date());
  const groups = items.reduce<Record<string, Task[]>>(
    (acc, item) => {
      if (item.due_on !== today) {
        acc.overdue.push(item);
        return acc;
      }
      if (item.evening) {
        acc.evening.push(item);
        return acc;
      }
      acc.today.push(item);
      return acc;
    },
    {today: [], overdue: [], evening: []}
  );
  const output = [
    {
      key: 'overdue',
      items: groups.overdue,
      ids: groups.overdue.map(task => String(task.id)),
    },
    {
      key: today,
      items: groups.today,
      ids: groups.today.map(task => String(task.id)),
    },
    {
      key: `evening:${today}`,
      items: groups.evening,
      ids: groups.evening.map(task => String(task.id)),
    },
  ];
  return output;
}

export default function TasksToday({tasks}: Props): JSX.Element {
  const today = new Date();
  const defaultDate = toDateString(today);

  return (
    <LoggedIn title={t("Today's Tasks")}>
      <TaskGroupedSorter
        tasks={tasks}
        grouper={grouper}
        updater={todaySortUpdater}
        showProject
        showDueOn
      >
        {({groupedItems, activeTask}) => {
          const [overdue, today, evening] = groupedItems;
          return (
            <React.Fragment>
              {overdue.ids.length > 0 && (
                <SortableContext
                  items={overdue.ids}
                  strategy={verticalListSortingStrategy}
                >
                  <h2 className="heading-icon overdue">
                    <Icon icon="alert" />
                    {t('Overdue')}
                  </h2>
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
                <h2 className="heading-icon today">
                  <Icon icon="clippy" />
                  {t('Today')}
                </h2>
                <TaskGroup
                  dropId={today.key}
                  tasks={today.items}
                  activeTask={activeTask}
                  defaultTaskValues={{due_on: defaultDate}}
                  showProject
                />
              </SortableContext>
              <SortableContext items={evening.ids} strategy={verticalListSortingStrategy}>
                <h2 className="heading-icon evening">
                  <Icon icon="moon" />
                  {t('This Evening')}
                </h2>
                <TaskGroup
                  dropId={evening.key}
                  tasks={evening.items}
                  activeTask={activeTask}
                  defaultTaskValues={{evening: true, due_on: defaultDate}}
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
