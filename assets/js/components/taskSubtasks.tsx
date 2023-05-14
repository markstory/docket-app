import {TaskDetailed} from 'app/types';
import SubtaskSorter from 'app/components/subtaskSorter';
import SubtaskAddForm from 'app/components/subtaskAddForm';
import useKeyboardShortcut from 'app/hooks/useKeyboardShortcut';
import {SubtasksProvider} from 'app/providers/subtasks';
import {InlineIcon} from './icon';

type Props = {
  task: TaskDetailed;
};

export default function TaskSubtasks({task}: Props): JSX.Element {
  return (
    <SubtasksProvider subtasks={task.subtasks}>
      <div className="task-subtasks">
        <h3>
          <InlineIcon icon="workflow" /> Sub-tasks
        </h3>
        <SubtaskSorter taskId={task.id} />
        <div className="add-subtask">
          <SubtaskAddForm task={task} />
        </div>
      </div>
    </SubtasksProvider>
  );
}
