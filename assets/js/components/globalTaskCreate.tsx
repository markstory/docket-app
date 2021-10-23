import {useContext, useState} from 'react';

import {createTask, makeTaskFromDefaults} from 'app/actions/tasks';
import useKeyboardShortcut from 'app/hooks/useKeyboardShortcut';
import {t} from 'app/locale';
import {DefaultTaskValuesContext} from 'app/providers/defaultTaskValues';
import {ValidationErrors} from 'app/types';

import Modal from './modal';
import TaskQuickForm from './taskQuickForm';

type Props = {};

function GlobalTaskCreate(_props: Props) {
  const [defaultTaskValues, _] = useContext(DefaultTaskValuesContext);
  const [visible, setVisible] = useState(false);
  const [errors, setErrors] = useState<ValidationErrors>({});

  useKeyboardShortcut(['c'], () => {
    setVisible(true);
  });

  if (!visible) {
    return null;
  }
  const task = makeTaskFromDefaults(defaultTaskValues);
  const handleCancel = () => setVisible(false);

  async function handleSubmit(e: React.FormEvent) {
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

  // TODO fix modal styling so it looks better.
  return (
    <Modal onClose={handleCancel} label={t('Create a task')}>
      <TaskQuickForm
        url="/tasks/add"
        errors={errors}
        task={task}
        onCancel={handleCancel}
        onSubmit={handleSubmit}
      />
    </Modal>
  );
}

export default GlobalTaskCreate;
