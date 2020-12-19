import React, {useState} from 'react';
import axios, {AxiosResponse} from 'axios';
import {Inertia} from '@inertiajs/inertia';

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
        `/todos/${task.id}/subtasks`,
        formData
      );
      // Clear input for next task.
      setValue('');
      setSubtasks([...subtasks, resp.data.subtask]);
    } catch (error) {
      // TOOD handle this error.
    }
  }

  return (
    <form className="todosubtask-addform" method="post" onSubmit={handleSubmit}>
      <div className="title">
        <input
          type="text"
          name="title"
          placeholder="Take out the trash"
          autoFocus
          required
          value={value}
          onChange={(e: React.ChangeEvent<HTMLInputElement>) => setValue(e.target.value)}
        />
      </div>
      <div className="button-bar">
        <button type="submit">Save</button>
        <button className="button-secondary" onClick={onCancel}>
          Cancel
        </button>
      </div>
    </form>
  );
}
