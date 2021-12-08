import classnames from 'classnames';
import {Fragment, useContext, useState} from 'react';

import {createTask, makeTaskFromDefaults} from 'app/actions/tasks';
import useKeyboardShortcut from 'app/hooks/useKeyboardShortcut';
import {t} from 'app/locale';
import {DefaultTaskValuesContext} from 'app/providers/defaultTaskValues';
import {ValidationErrors} from 'app/types';

import Modal from './modal';
import TaskQuickForm from './taskQuickForm';
import {Icon} from './icon';

type Props = {};

function GlobalTaskCreate(_props: Props) {
  const [defaultTaskValues, _] = useContext(DefaultTaskValuesContext);
  const [visible, setVisible] = useState(false);
  const [errors, setErrors] = useState<ValidationErrors>({});
  const showForm = () => setVisible(true);

  useKeyboardShortcut(['c'], showForm);
  const classname = classnames('button-global-add', {
    'button-primary': !visible,
    'button-muted': visible,
  });

  const button = (
    <button className={classname} data-testid="global-task-add" onClick={showForm}>
      <Icon icon="plus" width="64" />
    </button>
  );

  if (!visible) {
    return button;
  }
  const task = makeTaskFromDefaults(defaultTaskValues);

  function handleClose(event: MouseEvent | React.MouseEvent) {
    if (event.defaultPrevented) {
      return;
    }
    setVisible(false);
  }

  function handleCancel() {
    setVisible(false);
  }

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

  return (
    <Fragment>
      {button}
      <Modal className="modal-float" onClose={handleClose} label={t('Create a task')}>
        <h2>{t('Create a new Task')}</h2>
        <TaskQuickForm
          url="/tasks/add"
          errors={errors}
          task={task}
          onCancel={handleCancel}
          onSubmit={handleSubmit}
          showNotes
        />
      </Modal>
    </Fragment>
  );
}

export default GlobalTaskCreate;
