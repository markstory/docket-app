import {Fragment} from 'react';
import groupBy from 'lodash.groupby';
import {SortableContext, verticalListSortingStrategy} from '@dnd-kit/sortable';
import {InertiaLink} from '@inertiajs/inertia-react';

import {sortUpdater} from 'app/actions/tasks';
import {t} from 'app/locale';
import {Project, Task} from 'app/types';
import {InlineIcon} from 'app/components/icon';
import LoggedIn from 'app/layouts/loggedIn';
import NoProjects from 'app/components/noProjects';
import TaskGroup from 'app/components/taskGroup';
import TaskGroupedSorter, {GroupedItems} from 'app/components/taskGroupedSorter';
import {toDateString, formatDateHeading, parseDate, ONE_DAY_IN_MS} from 'app/utils/dates';

type Props = {
  tasks: Task[];
  projects: Project[];
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
  const first = parseDate(start).getTime();
  const end = first + numDays * ONE_DAY_IN_MS;

  const complete: GroupedItems = [];
  for (let i = first; i < end; i += ONE_DAY_IN_MS) {
    const date = new Date(i);
    const dateKey = toDateString(date);

    if (groups.length && groups[0].key === dateKey) {
      const values = groups.shift();
      if (values) {
        complete.push(values);
      }
    } else {
      complete.push({key: dateKey, items: [], ids: [], hasAdd: false});
    }

    if (groups.length && groups[0].key === `evening:${dateKey}`) {
      const values = groups.shift();
      if (values) {
        complete.push(values);
      }
    }
    // The last group in a day should have an add button.
    complete[complete.length - 1].hasAdd = true;
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
        hasAdd: false,
      };
    });
    return zeroFillItems(start, numDays, grouped);
  };
}

export default function TasksIndex({
  generation,
  tasks,
  projects,
  start,
  nextStart,
}: Props): JSX.Element {
  const nextPage = nextStart ? `/tasks/upcoming?start=${nextStart}` : null;
  const title = t('Upcoming Tasks');

  if (!projects.length) {
    return (
      <LoggedIn title={title}>
        <NoProjects />
      </LoggedIn>
    );
  }

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
              {groupedItems.map(({key, ids, items, hasAdd}) => {
                const [heading, subheading] = formatDateHeading(key);
                const dateValue = key.includes('evening:') ? key.split(':')[1] : key;
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
                      </h3>
                    )}
                    <SortableContext items={ids} strategy={verticalListSortingStrategy}>
                      <TaskGroup
                        dropId={key}
                        tasks={items}
                        activeTask={activeTask}
                        defaultTaskValues={{due_on: dateValue}}
                        showAdd={hasAdd}
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
