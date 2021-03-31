import React, {useState} from 'react';
import axios, {AxiosResponse} from 'axios';
import {Inertia} from '@inertiajs/inertia';

import {t} from 'app/locale';
import {TaskDetailed, Subtask} from 'app/types';
import {useSubtasks} from 'app/providers/subtasks';

type Props = {
  task: TaskDetailed;
  onCancel: () => void;
};

export default function SubtaskAddForm({task, onCancel}: Props) {
  const [value, setValue] = useState('');
  const [subtasks, setSubtasks] = useSubtasks();

  async function handleSubmit(e: React.FormEvent) {
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

  function handleKeyDown(e: React.KeyboardEvent) {
    switch (e.key) {
      case 'Esc':
      case 'Escape':
        onCancel();
        e.stopPropagation();
        break;
    }
  }

  return (
    <form className="subtask-addform" method="post" onSubmit={handleSubmit}>
      <div className="title" onKeyDown={handleKeyDown}>
        <input
          type="text"
          name="title"
          placeholder={t('Take out the trash')}
          autoFocus
          required
          value={value}
          onChange={(e: React.ChangeEvent<HTMLInputElement>) => setValue(e.target.value)}
        />
      </div>
      <div className="button-bar">
        <button className="button-primary" data-testid="save-subtask" type="submit">
          {t('Save')}
        </button>
        <button className="button-muted" onClick={onCancel}>
          {t('Cancel')}
        </button>
      </div>
    </form>
  );
}
