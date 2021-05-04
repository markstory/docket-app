import * as React from 'react';
import {Inertia} from '@inertiajs/inertia';

import {DefaultTaskValues, Task} from 'app/types';
import TaskQuickForm from 'app/components/taskQuickForm';

type Props = {
  onCancel: () => void;
  defaultValues?: DefaultTaskValues;
};

function TaskAddForm({onCancel, defaultValues}: Props): JSX.Element {
  function onSubmit(e: React.FormEvent, clearTitle: () => void): void {
    e.preventDefault();
    e.persist();

    const form = e.target as HTMLFormElement;
    const formData = new FormData(form);

    Inertia.post('/tasks/add', formData, {
      onSuccess: () => {
        // clear the title to start over.
        clearTitle();
      },
    });
  }
  const task: Task = {
    id: -1,
    section_id: null,
    title: '',
    body: '',
    due_on: null,
    completed: false,
    evening: false,
    day_order: 0,
    child_order: 0,
    created: '',
    modified: '',
    complete_subtask_count: 0,
    subtask_count: 0,
    project: {
      id: defaultValues?.project_id ? Number(defaultValues.project_id) : 0,
      name: '',
      slug: '',
      color: 0,
    },
    ...defaultValues,
  };

  return (
    <TaskQuickForm url="/tasks/add" task={task} onSubmit={onSubmit} onCancel={onCancel} />
  );
}
export default TaskAddForm;
