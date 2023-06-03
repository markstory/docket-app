import {TaskDetailed} from 'app/types';
import SubtaskSorter from 'app/components/subtaskSorter';
import SubtaskAddForm from 'app/components/subtaskAddForm';
import {SubtasksProvider} from 'app/providers/subtasks';
import {InlineIcon} from './icon';
import {NEW_ID} from 'app/constants';

type Props = {
  task: TaskDetailed;
};

export default function TaskSubtasks({task}: Props): JSX.Element {
  // TODO(classnames) Consider a library if this becomes a pattern.
  let className = 'task-subtasks';
  if (task.id == NEW_ID) {
    className += ' is-new';
  }

  return (
    <SubtasksProvider subtasks={task.subtasks}>
      <div className={className}>
        <h3>
          <InlineIcon icon="workflow" /> Sub-tasks
        </h3>
        <SubtaskSorter task={task} />
        <div className="add-subtask">
          <SubtaskAddForm task={task} />
        </div>
      </div>
    </SubtasksProvider>
  );
}
