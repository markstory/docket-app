import React from 'react';
import {Inertia} from '@inertiajs/inertia';

import {TodoItem} from 'app/types';

type Props = {
  todoItem: TodoItem;
  onCancel: () => void;
};

export default function TodoSubtaskAddForm({todoItem, onCancel}: Props) {
  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    const formData = new FormData(e.target as HTMLFormElement);

    // Use regular post as validation errors should be rare.
    Inertia.post(`/todos/${todoItem.id}/subtasks`, formData);
  };

  return (
    <form className="todosubtask-addform" method="post" onSubmit={handleSubmit}>
      <div className="title">
        <input
          type="text"
          name="title"
          placeholder="Take out the trash"
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
