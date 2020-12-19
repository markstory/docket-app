import React from 'react';
import axios, {AxiosResponse} from 'axios';

import {TodoSubtask} from 'app/types';
import {useSubtasks} from 'app/providers/subtasks';

type Props = {
  todoItemId: number;
  index: number;
  subtask: TodoSubtask;
  onCancel: () => void;
};

export default function SubtaskEditForm({subtask, index, todoItemId, onCancel}: Props) {
  const [subtasks, setSubtasks] = useSubtasks();

  async function handleSubmit(event: React.FormEvent) {
    event.preventDefault();
    const formData = new FormData(event.target as HTMLFormElement);

    // Do an XHR request so we can update page state
    // as reloading doesn't work due to sort contexts
    try {
      const resp: AxiosResponse<{subtask: TodoSubtask}> = await axios.post(
        `/todos/${todoItemId}/subtasks/${subtask.id}/edit`,
        formData
      );
      // TODO see if we can reset contexts instead of repeating update logic here.
      const updated = [...subtasks];
      updated[index] = resp.data.subtask;
      setSubtasks(updated);
      onCancel();
    } catch (error) {
      // TOOD handle this error.
    }
  }
  return (
    <form className="subtask-quickform" method="post" onSubmit={handleSubmit}>
      <div className="title">
        <input
          type="text"
          name="title"
          placeholder="Take out the trash"
          defaultValue={subtask.title}
          autoFocus
          required
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
