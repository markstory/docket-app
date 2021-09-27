import {useState} from 'react';
import classnames from 'classnames';
import {useDroppable} from '@dnd-kit/core';

import {DefaultTaskValues, Task} from 'app/types';
import {t} from 'app/locale';
import TaskRow from 'app/components/taskRow';
import TaskAddForm from 'app/components/taskAddForm';
import SortableItem from 'app/components/sortableItem';
import {InlineIcon} from './icon';

type Props = {
  dropId: string;
  tasks: Task[];
  dataTestId?: string;
  activeTask?: Task | null;
  focusedTask?: Task | null;
  defaultTaskValues?: DefaultTaskValues;
  showProject?: boolean;
  showDueOn?: boolean;
  showAdd?: boolean;
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
  showAdd = true,
}: Props): JSX.Element {
  const [showForm, setShowForm] = useState(false);
  const {over, isOver, setNodeRef} = useDroppable({id: dropId});
  const taskIds = tasks.map(t => t.id);

  const className = classnames('dnd-dropper-left-offset', {
    'dnd-dropper-active':
      isOver || (over && activeTask ? taskIds.includes(activeTask.id) : null),
  });
  const activeId = activeTask ? String(activeTask.id) : undefined;

  return (
    <div className="task-group" data-testid={dataTestId}>
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
              />
            </SortableItem>
          );
        })}
      </div>
      {showAdd && (
        <div className="add-task">
          {!showForm && (
            <button
              data-testid="add-task"
              className="button-secondary"
              onClick={() => setShowForm(true)}
            >
              <InlineIcon icon="plus" />
              {t('Add Task')}
            </button>
          )}
          {showForm && (
            <TaskAddForm
              defaultValues={defaultTaskValues}
              onCancel={() => setShowForm(false)}
            />
          )}
        </div>
      )}
    </div>
  );
}
