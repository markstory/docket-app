import {useContext, useState, useEffect, useRef} from 'react';
import classnames from 'classnames';
import {useDroppable} from '@dnd-kit/core';

import {DefaultTaskValues, Task} from 'app/types';
import {DefaultTaskValuesContext} from 'app/providers/defaultTaskValues';

import SortableItem from './sortableItem';
import TaskRow from './taskRow';

type Props = {
  dropId: string;
  tasks: Task[];
  dataTestId?: string;
  activeTask?: Task | null;
  focusedTask?: Task | null;
  defaultTaskValues?: DefaultTaskValues;
  showProject?: boolean;
  showDueOn?: boolean;
  showRestore?: boolean;
};

export default function TaskGroup({
  dropId,
  dataTestId,
  activeTask,
  focusedTask,
  tasks,
  defaultTaskValues,
  showProject = false,
  showDueOn = false,
  showRestore = false,
}: Props): JSX.Element {
  const element = useRef<HTMLDivElement>(null);
  const {over, isOver, setNodeRef} = useDroppable({id: dropId});
  const [_, updateDefaultTaskValues] = useContext(DefaultTaskValuesContext);

  const taskIds = tasks.map(t => t.id);
  const hasFocus = focusedTask && taskIds.includes(focusedTask.id);

  useEffect(() => {
    // If the current group has focus it should be used for new tasks.
    if (!defaultTaskValues) {
      return;
    }
    updateDefaultTaskValues({
      type: hasFocus ? 'add' : 'remove',
      data: defaultTaskValues,
    });
  }, [hasFocus]);

  useEffect(() => {
    // Use IntersectionObserver so we can update default value context
    // for the global task add button.
    if (!element.current || !defaultTaskValues) {
      return;
    }
    const options = {
      root: null,
      rootMargin: '0px',
      threshold: 0.9,
    };
    const observer = new IntersectionObserver(entries => {
      const visible = entries[0].isIntersecting;
      updateDefaultTaskValues({
        type: visible ? 'add' : 'remove',
        data: defaultTaskValues,
      });
    }, options);
    observer.observe(element.current);

    return function cleanup() {
      observer.disconnect();
    };
  }, [element.current]);

  const className = classnames('dnd-dropper-left-offset', {
    'dnd-dropper-active':
      isOver || (over && activeTask ? taskIds.includes(activeTask.id) : null),
  });
  const activeId = activeTask ? String(activeTask.id) : undefined;

  return (
    <div className="task-group" data-testid={dataTestId} ref={element}>
      <div className={className} ref={setNodeRef}>
        {tasks.map(item => {
          const focused = focusedTask?.id === item.id;
          return (
            <SortableItem
              key={item.id}
              id={String(item.id)}
              dragActive={activeId}
              tag="div"
            >
              <TaskRow
                task={item}
                focused={focused}
                showProject={showProject}
                showDueOn={showDueOn}
                showRestore={showRestore}
              />
            </SortableItem>
          );
        })}
      </div>
    </div>
  );
}
