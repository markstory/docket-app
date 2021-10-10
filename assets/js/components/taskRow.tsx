import {useRef, useState, Fragment, useEffect} from 'react';
import {Inertia} from '@inertiajs/inertia';
import {InertiaLink} from '@inertiajs/inertia-react';
import {MenuItem} from '@reach/menu-button';
import classnames from 'classnames';

import {t} from 'app/locale';
import {updateTask} from 'app/actions/tasks';
import {Task} from 'app/types';
import useOnClickOutside from 'app/utils/useClickOutside';
import useKeyboardShortcut from 'app/utils/useKeyboardShortcut';
import ProjectSelect from './projectSelect';
import Checkbox from './checkbox';
import DueOn from './dueOn';
import ContextMenu from './contextMenu';
import {InlineIcon} from './icon';
import {MenuContents} from './dueOnPicker';
import ProjectBadge from './projectBadge';

type Props = {
  task: Task;
  focused?: boolean;
  showDueOn?: boolean;
  showProject?: boolean;
};

type InnerMenuState = 'project' | 'dueOn' | null;

export default function TaskRow({
  focused,
  task,
  showDueOn,
  showProject,
}: Props): JSX.Element {
  const element = useRef<HTMLDivElement>(null);
  const [completed, setCompleted] = useState(task.completed);

  const handleComplete = (e: React.ChangeEvent<HTMLInputElement>) => {
    e.stopPropagation();
    setCompleted(!task.completed);
    const action = task.completed ? 'incomplete' : 'complete';
    Inertia.post(`/tasks/${task.id}/${action}`);
  };

  // TODO These bindings might need to be higher in the tree as
  // in each row will bind a pile of listeners.
  useKeyboardShortcut(['o'], () => {
    if (focused) {
      Inertia.visit(`/tasks/${task.id}/view/`);
    }
  });
  useKeyboardShortcut(['d'], () => {
    if (focused) {
      setCompleted(!task.completed);
      const action = task.completed ? 'incomplete' : 'complete';
      Inertia.post(`/tasks/${task.id}/${action}`);
    }
  });
  useEffect(() => {
    if (!focused) {
      element.current?.scrollIntoView({
        behavior: 'smooth',
        block: 'center',
      });
    }
  }, [focused, element]);

  const className = classnames('task-row', {
    'is-completed': completed,
    'is-focused': focused,
  });

  return (
    <div className={className} ref={element}>
      <Checkbox name="complete" checked={completed} onChange={handleComplete} />
      <InertiaLink href={`/tasks/${task.id}/view`}>
        <span className="title">{task.title}</span>
        <div className="attributes">
          {showProject && <ProjectBadge project={task.project} />}
          <DueOn task={task} showDetailed={showDueOn} />
          <SubtaskSummary task={task} />
        </div>
      </InertiaLink>
      <TaskActions task={task} />
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

type ActionsProps = Pick<Props, 'task'>;

function TaskActions({task}: ActionsProps) {
  const [innerMenu, setInnerMenu] = useState<InnerMenuState>(null);

  async function handleDueOnChange(dueOn: string | null, evening: boolean) {
    const data = {due_on: dueOn, evening};
    await updateTask(task, data);
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
  const menuRef = useRef<HTMLDivElement>(null);

  useOnClickOutside(menuRef, function () {
    setInnerMenu(null);
  });

  return (
    <div className="actions" ref={menuRef}>
      <ContextMenu tooltip={t('Task actions')}>
        {!innerMenu && (
          <Fragment>
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
          </Fragment>
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
