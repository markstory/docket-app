import {Fragment} from 'react';
import {SortableContext, verticalListSortingStrategy} from '@dnd-kit/sortable';

import {CalendarItem, Project, Task} from 'app/types';
import {sortUpdater} from 'app/actions/tasks';
import {t} from 'app/locale';
import AddTaskButton from 'app/components/addTaskButton';
import CalendarItemList from 'app/components/calendarItemList';
import {Icon} from 'app/components/icon';
import NoProjects from 'app/components/noProjects';
import TaskGroup from 'app/components/taskGroup';
import useKeyboardListNav from 'app/hooks/useKeyboardListNav';
import LoggedIn from 'app/layouts/loggedIn';
import TaskGroupedSorter, {GroupedItems} from 'app/components/taskGroupedSorter';
import {parseDate, toDateString} from 'app/utils/dates';

type Props = {
  date: string;
  tasks: Task[];
  projects: Project[];
  calendarItems: CalendarItem[];
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

export default function TasksDaily({calendarItems, date, tasks, projects}: Props): JSX.Element {
  const date = parseDate(date);
  const dateName = toDateString(today);
  const title = t("%s Tasks", dateName);
  if (!projects.length) {
    return (
      <LoggedIn title={title}>
        <NoProjects />
      </LoggedIn>
    );
  }

  const [focusedIndex] = useKeyboardListNav(tasks.length);
  let focused: null | Task = null;
  if (focusedIndex >= 0 && tasks[focusedIndex] !== undefined) {
    focused = tasks[focusedIndex];
  }

  return (
    <LoggedIn title={title}>
      <TaskGroupedSorter
        tasks={tasks}
        grouper={grouper}
        updater={sortUpdater}
        showProject
        showDueOn
      >
        {({groupedItems, activeTask}) => {
          const [overdue, today, evening] = groupedItems;
          return (
            <Fragment>
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
                    dataTestId="overdue-group"
                    dropId={overdue.key}
                    tasks={overdue.items}
                    activeTask={activeTask}
                    showProject
                    showDueOn
                    focusedTask={focused}
                  />
                </SortableContext>
              )}
              <SortableContext items={today.ids} strategy={verticalListSortingStrategy}>
                <h2 className="heading-icon today">
                  <Icon icon="clippy" />
                  {dateName}
                  <AddTaskButton defaultValues={{due_on: date}} />
                </h2>
                {calendarItems.length > 0 && (
                  <CalendarItemList date={date} items={calendarItems} />
                )}
                <TaskGroup
                  dataTestId="today-group"
                  dropId={today.key}
                  tasks={today.items}
                  activeTask={activeTask}
                  defaultTaskValues={{due_on: date}}
                  focusedTask={focused}
                  showProject
                />
              </SortableContext>
              <SortableContext items={evening.ids} strategy={verticalListSortingStrategy}>
                <h2 className="heading-icon evening" data-testid="evening-group">
                  <Icon icon="moon" />
                  {t('This Evening')}
                  <AddTaskButton defaultValues={{evening: true, due_on: date}} />
                </h2>
                <TaskGroup
                  dropId={evening.key}
                  tasks={evening.items}
                  activeTask={activeTask}
                  defaultTaskValues={{evening: true, due_on: date}}
                  focusedTask={focused}
                  showProject
                />
              </SortableContext>
            </Fragment>
          );
        }}
      </TaskGroupedSorter>
    </LoggedIn>
  );
}
