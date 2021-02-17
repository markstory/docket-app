import React from 'react';

import {InlineIcon} from 'app/components/icon';
import {Task} from 'app/types';

type Props = {
  task: Task;
};

function TaskEvening({task}: Props): JSX.Element | null {
  return task.evening ? <InlineIcon className="icon-evening" icon="moon" /> : null;
}

export default TaskEvening;
