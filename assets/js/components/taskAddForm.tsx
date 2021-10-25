import {useState} from 'react';

import {createTask, makeTaskFromDefaults} from 'app/actions/tasks';
import {DefaultTaskValues, ValidationErrors} from 'app/types';
import TaskQuickForm from 'app/components/taskQuickForm';

type Props = {
  onCancel: () => void;
  defaultValues?: DefaultTaskValues;
};

function TaskAddForm({onCancel, defaultValues}: Props): JSX.Element {
  const [errors, setErrors] = useState<ValidationErrors>({});

  async function onSubmit(e: React.FormEvent) {
    e.preventDefault();
    e.persist();

    const form = e.target as HTMLFormElement;
    const formData = new FormData(form);
    const promise = createTask(formData);

    return promise.catch((errors: ValidationErrors) => {
      setErrors(errors);

      return promise;
    });
  }
  const task = makeTaskFromDefaults(defaultValues);

  return (
    <TaskQuickForm
      url="/tasks/add"
      errors={errors}
      task={task}
      onSubmit={onSubmit}
      onCancel={onCancel}
    />
  );
}
export default TaskAddForm;
