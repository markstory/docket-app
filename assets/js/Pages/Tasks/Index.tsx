import {Fragment} from 'react';
import groupBy from 'lodash.groupby';
import {SortableContext, verticalListSortingStrategy} from '@dnd-kit/sortable';
import {InertiaLink} from '@inertiajs/inertia-react';

import {sortUpdater} from 'app/actions/tasks';
import {t} from 'app/locale';
import {CalendarItem, Project, Task} from 'app/types';
import AddTaskButton from 'app/components/addTaskButton';
import CalendarItemList from 'app/components/calendarItemList';
import {InlineIcon} from 'app/components/icon';
import LoggedIn from 'app/layouts/loggedIn';
import NoProjects from 'app/components/noProjects';
import TaskGroup from 'app/components/taskGroup';
import TaskGroupedSorter, {GroupedItems} from 'app/components/taskGroupedSorter';
import useKeyboardListNav from 'app/hooks/useKeyboardListNav';

import {
  addDays,
  toDateString,
  formatDateHeading,
  getRangeInDays,
  parseDate,
} from 'app/utils/dates';

type Props = {
  tasks: Task[];
  projects: Project[];
  calendarItems: CalendarItem[];
  start: string;
  nextStart: string;
  generation: string;
};

/**
 * Fill out the sparse input data to have all the days.
 */
function zeroFillItems(
  start: string,
  numDays: number,
  groups: GroupedItems
): GroupedItems {
  const firstDate = parseDate(start);
  const endDate = addDays(firstDate, numDays);

  const complete: GroupedItems = [];
  let date = new Date(firstDate);
  while (true) {
    if (date && date > endDate) {
      break;
    }
    const dateKey = toDateString(date);
    if (groups.length && groups[0].key === dateKey) {
      const values = groups.shift();
      if (values) {
        complete.push(values);
      }
    } else {
      complete.push({key: dateKey, items: [], ids: []});
    }

    if (groups.length && groups[0].key === `evening:${dateKey}`) {
      const values = groups.shift();
      if (values) {
        complete.push(values);
      }
    }

    // Increment for next loop. We are using a while/break
    // because incrementing timestamps fails when DST happens.
    date = addDays(date, 1);
  }
  return complete;
}

function createGrouper(start: string, numDays: number) {
  return function taskGrouper(items: Task[]): GroupedItems {
    const byDate: Record<string, Task[]> = groupBy(items, item => {
      if (item.evening) {
        return `evening:${item.due_on}`;
      }
      return item.due_on ? item.due_on : t('No Due Date');
    });

    const grouped = Object.entries(byDate).map(([key, value]) => {
      return {
        key,
        items: value,
        ids: value.map(task => String(task.id)),
      };
    });
    return zeroFillItems(start, numDays, grouped);
  };
}

type GroupedCalendarItems = Record<string, CalendarItem[]>;

function groupCalendarItems(items: CalendarItem[]): GroupedCalendarItems {
  return items.reduce<GroupedCalendarItems>((acc, item) => {
    let keys = [];
    if (item.all_day) {
      keys = getRangeInDays(parseDate(item.start_date), parseDate(item.end_date));
    } else {
      keys = getRangeInDays(new Date(item.start_time), new Date(item.end_time));
    }

    keys.forEach(key => {
      if (typeof acc[key] === 'undefined') {
        acc[key] = [];
      }
      acc[key].push(item);
    });

    return acc;
  }, {});
}

export default function TasksIndex({
  calendarItems,
  generation,
  tasks,
  projects,
  start,
  nextStart,
}: Props): JSX.Element {
  const nextPage = nextStart ? `/tasks/upcoming?start=${nextStart}` : null;
  const title = t('Upcoming Tasks');

  const [focusedIndex] = useKeyboardListNav(tasks.length);
  let focused: null | Task = null;
  if (focusedIndex >= 0 && tasks[focusedIndex] !== undefined) {
    focused = tasks[focusedIndex];
  }

  if (!projects.length) {
    return (
      <LoggedIn title={title}>
        <NoProjects />
      </LoggedIn>
    );
  }

  const groupedCalendarItems = groupCalendarItems(calendarItems);

  return (
    <LoggedIn title={title}>
      <h1>Upcoming</h1>
      <TaskGroupedSorter
        key={generation}
        tasks={tasks}
        grouper={createGrouper(start, 28)}
        updater={sortUpdater}
        showProject
        showDueOn
      >
        {({groupedItems, activeTask}) => {
          return (
            <Fragment>
              {groupedItems.map(({key, ids, items}) => {
                const [heading, subheading] = formatDateHeading(key);

                const eveningValue = key.includes('evening:');
                const dateValue = eveningValue ? key.split(':')[1] : key;
                const defaultValues = {due_on: dateValue, evening: eveningValue};
                return (
                  <Fragment key={key}>
                    {key.includes('evening') ? (
                      <h5 className="heading-evening-group">
                        <InlineIcon icon="moon" /> {t('Evening')}
                      </h5>
                    ) : (
                      <h3 className="heading-task-group">
                        {heading}
                        {subheading && <span className="minor">{subheading}</span>}
                        <AddTaskButton defaultValues={defaultValues} />
                      </h3>
                    )}
                    {groupedCalendarItems[key] && (
                      <CalendarItemList
                        key={`cak:${key}`}
                        date={key}
                        items={groupedCalendarItems[key]}
                      />
                    )}
                    <SortableContext items={ids} strategy={verticalListSortingStrategy}>
                      <TaskGroup
                        dropId={key}
                        tasks={items}
                        activeTask={activeTask}
                        focusedTask={focused}
                        defaultTaskValues={defaultValues}
                        showProject
                      />
                    </SortableContext>
                  </Fragment>
                );
              })}
            </Fragment>
          );
        }}
      </TaskGroupedSorter>
      <div className="button-bar">
        {nextPage && (
          <InertiaLink className="button button-secondary" href={nextPage}>
            {t('Next')}
          </InertiaLink>
        )}
      </div>
    </LoggedIn>
  );
}
