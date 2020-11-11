import React from 'react';
import {Inertia} from '@inertiajs/inertia';

type Props = {
  csrfToken: string;
  onCancel: () => void;
};

function QuickAddTaskForm({onCancel, csrfToken}: Props) {
  const onSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    const formData = new FormData(e.target as HTMLFormElement);
    Inertia.post('/todos/add', formData);
  };

  return (
    <form method="post" onSubmit={onSubmit}>
      <input type="hidden" name="_csrfToken" value={csrfToken} />
      <input type="text" name="title" autoFocus />
      <select name="project_id">
        {/* TODO make this dynamic */}
        <option value="1">Home</option>
      </select>
      <input type="date" name="due_on" />
      <button type="submit">Save</button>
      <button onClick={onCancel}>Cancel</button>
    </form>
  );
}
export default QuickAddTaskForm;
