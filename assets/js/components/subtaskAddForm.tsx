import {useState} from 'react';
import axios, {AxiosResponse} from 'axios';

import {NEW_ID} from 'app/constants';
import {t} from 'app/locale';
import {TaskDetailed, Subtask} from 'app/types';
import {useSubtasks} from 'app/providers/subtasks';

type Props = {
  task: TaskDetailed;
};

export default function SubtaskAddForm({task}: Props) {
  const [value, setValue] = useState('');
  const [subtasks, setSubtasks] = useSubtasks();

  async function handleSubmitSave(e: React.FormEvent) {
    e.preventDefault();
    const formData = new FormData(e.target as HTMLFormElement);

    // Do an XHR request so we can update page state
    // as reloading doesn't work due to sort contexts
    try {
      const resp: AxiosResponse<{subtask: Subtask}> = await axios.post(
        `/tasks/${task.id}/subtasks`,
        formData
      );
      // Clear input for next task.
      setValue('');
      setSubtasks([...subtasks, resp.data.subtask]);
    } catch (error) {
      // TOOD handle this error.
    }
  }

  async function handleSubmitAdd(e: React.FormEvent) {
    e.preventDefault();
    e.stopPropagation();

    const newSubtask: Subtask = {
      id: NEW_ID,
      title: value,
      body: '',
      completed: false,
    };
    // Clear the input for the next task
    setValue('');
    setSubtasks([...subtasks, newSubtask]);
  }

  function handleKeyDown(e: React.KeyboardEvent) {
    switch (e.key) {
      case 'Esc':
      case 'Escape':
        e.stopPropagation();
        break;
    }
  }
  const isNew = task.id == NEW_ID;

  if (isNew) {
    return (
      <div className="subtask-addform">
        <input
          type="text"
          name="subtask_title"
          placeholder={t('Take out the trash')}
          value={value}
          onKeyDown={handleKeyDown}
          onChange={(e: React.ChangeEvent<HTMLInputElement>) => setValue(e.target.value)}
        />
        <div>
          <button
            className="button-primary"
            data-testid="save-subtask"
            type="submit"
            onClick={handleSubmitAdd}
          >
            {t('Add')}
          </button>
        </div>
      </div>
    );
  }

  return (
    <form className="subtask-addform" method="post" onSubmit={handleSubmitSave}>
      <input
        type="text"
        name="title"
        placeholder={t('Take out the trash')}
        required
        value={value}
        onChange={(e: React.ChangeEvent<HTMLInputElement>) => setValue(e.target.value)}
        onKeyDown={handleKeyDown}
      />
      <div>
        <button className="button-primary" data-testid="save-subtask" type="submit">
          {t('Save')}
        </button>
      </div>
    </form>
  );
}
