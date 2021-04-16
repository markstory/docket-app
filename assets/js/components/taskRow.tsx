import React, {useState} from 'react';
import {Inertia} from '@inertiajs/inertia';
import {InertiaLink} from '@inertiajs/inertia-react';
import {MenuItem} from '@reach/menu-button';
import classnames from 'classnames';

import {t} from 'app/locale';
import {updateTask} from 'app/actions/tasks';
import Checkbox from 'app/components/checkbox';
import DueOn from 'app/components/dueOn';
import ContextMenu from 'app/components/contextMenu';
import {InlineIcon} from 'app/components/icon';
import {MenuContents} from 'app/components/dueOnPicker';
import ProjectBadge from 'app/components/projectBadge';
import {Task} from 'app/types';
import ProjectSelect from './projectSelect';

type Props = {
  task: Task;
  showDueOn?: boolean;
  showProject?: boolean;
};

export default function TaskRow({task, showDueOn, showProject}: Props): JSX.Element {
  const [active, setActive] = useState(false);
  const [completed, setCompleted] = useState(task.completed);

  const handleComplete = (e: React.ChangeEvent<HTMLInputElement>) => {
    e.stopPropagation();
    setCompleted(!task.completed);
    const action = task.completed ? 'incomplete' : 'complete';
    Inertia.post(`/tasks/${task.id}/${action}`);
  };

  const className = classnames('task-row', {
    'is-completed': completed,
  });

  return (
    <div
      className={className}
      onMouseEnter={() => setActive(true)}
      onMouseLeave={() => setActive(false)}
    >
      <Checkbox name="complete" checked={completed} onChange={handleComplete} />
      <InertiaLink href={`/tasks/${task.id}/view`}>
        <span className="title">{task.title}</span>
        <div className="attributes">
          {showProject && <ProjectBadge project={task.project} />}
          <DueOn task={task} showDetailed={showDueOn} />
          <SubtaskSummary task={task} />
        </div>
      </InertiaLink>
      {active ? <TaskActions task={task} setActive={setActive} /> : null}
    </div>
  );
}

function SubtaskSummary({task}: Pick<Props, 'task'>) {
  if (task.subtask_count < 1) {
    return null;
  }
  return (
    <span className="counter">
      <InlineIcon icon="workflow" width="xsmall" />
      {task.complete_subtask_count.toLocaleString()} /{' '}
      {task.subtask_count.toLocaleString()}
    </span>
  );
}

type ActionsProps = Pick<Props, 'task'> & {
  setActive: (val: boolean) => void;
};

type InnerMenuState = 'project' | 'dueOn' | null;

function TaskActions({task, setActive}: ActionsProps) {
  const [innerMenu, setInnerMenu] = useState<InnerMenuState>(null);

  async function handleDueOnChange(dueOn: string | null, evening: boolean) {
    const data = {due_on: dueOn, evening};
    await updateTask(task, data);
    setActive(false);
    Inertia.reload();
  }

  function handleDelete() {
    Inertia.post(`/tasks/${task.id}/delete`);
  }

  function handleItemMouseUp(menu: InnerMenuState) {
    return function (event: React.MouseEvent) {
      event.preventDefault();
      event.stopPropagation();
      setInnerMenu(menu);
    };
  }

  function handleItemKeyDown(menu: InnerMenuState) {
    return function (event: React.KeyboardEvent) {
      const key = event.key;
      if (!(key === 'Enter' || key === 'Space')) {
        return;
      }
      event.preventDefault();
      setInnerMenu(menu);
    };
  }

  return (
    <div className="actions" onMouseEnter={() => setActive(true)}>
      <ContextMenu tooltip={t('Task actions')}>
        {!innerMenu && (
          <React.Fragment>
            <MenuItem
              data-testid="move"
              className="edit"
              onMouseUp={handleItemMouseUp('project')}
              onKeyDown={handleItemKeyDown('project')}
              onSelect={() => setInnerMenu('project')}
            >
              <InlineIcon icon="pencil" />
              {t('Move to')}
            </MenuItem>
            <MenuItem
              data-testid="reschedule"
              className="calendar"
              onMouseUp={handleItemMouseUp('dueOn')}
              onKeyDown={handleItemKeyDown('dueOn')}
              onSelect={() => setInnerMenu('dueOn')}
            >
              <InlineIcon icon="calendar" />
              {t('Reschedule')}
            </MenuItem>
            <MenuItem data-testid="delete" className="delete" onSelect={handleDelete}>
              <InlineIcon icon="trash" />
              {t('Delete Task')}
            </MenuItem>
          </React.Fragment>
        )}
        {innerMenu === 'project' && <ProjectAction task={task} />}
        {innerMenu === 'dueOn' && (
          <MenuContents task={task} onChange={handleDueOnChange} />
        )}
      </ContextMenu>
    </div>
  );
}

type ProjectProps = {
  task: Task;
};
function ProjectAction({task}: ProjectProps) {
  async function handleProjectChange(value: number) {
    const data = {project_id: value};
    await updateTask(task, data);
    Inertia.reload();
  }

  return (
    <div>
      <h4 className="dropdown-item-header">{t('Move to project')}</h4>
      <div data-reach-menu-item>
        <ProjectSelect value={task.project.id} onChange={handleProjectChange} />
      </div>
    </div>
  );
}
