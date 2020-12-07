import React, {useState} from 'react';
import axios from 'axios';
import {Inertia} from '@inertiajs/inertia';

import {TodoItem} from 'app/types';

type Props = {
  todoItem: TodoItem;
};

export default function TodoItemNotes({todoItem}: Props) {
  const [editing, setEditing] = useState(false);

  function handleSave(event: React.FormEvent) {
    event.preventDefault();
    const formData = new FormData(event.target as HTMLFormElement);
    axios.post(`/todos/${todoItem.id}/edit`, formData).then(() => {
      Inertia.visit(`/todos/${todoItem.id}/view`);
    });
  }

  const lines = todoItem.body ? todoItem.body.split('\n') : ['Click to add notes'];
  if (!editing) {
    return (
      <div className="notes">
        <h4 className="heading-actions">
          Notes
          <button className="button-default" onClick={() => setEditing(true)}>
            Edit
          </button>
        </h4>
        <div onClick={() => setEditing(true)}>
          {lines.map((text: string) => (
            <p key={text}>{text}</p>
          ))}
        </div>
      </div>
    );
  }

  return (
    <form className="notes" onSubmit={handleSave}>
      <h4 className="heading-actions">Notes</h4>
      <textarea
        name="body"
        cols={999}
        rows={lines.length + 3}
        defaultValue={todoItem.body}
      />
      <div className="button-bar">
        <button type="submit">Save</button>
        <button className="button-default" onClick={() => setEditing(false)}>
          Cancel
        </button>
      </div>
    </form>
  );
}
