import React, {useState} from 'react';
import {Inertia} from '@inertiajs/inertia';

import {t} from 'app/locale';
import {updateTask} from 'app/actions/tasks';
import {Task} from 'app/types';

type Props = {
  task: Task;
};

export default function TaskNotes({task}: Props) {
  const [editing, setEditing] = useState(false);

  function handleSave(event: React.FormEvent) {
    event.preventDefault();
    const formData = new FormData(event.target as HTMLFormElement);
    updateTask(task, formData).then(() => {
      Inertia.get(`/todos/${task.id}/view`, {}, {only: ['task'], replace: true});
    });
  }

  const lines = task.body ? task.body.split('\n') : [];
  if (!editing) {
    return (
      <div className="task-notes">
        <h4 className="heading-actions">
          {t('Notes')}
          <button className="button-default" onClick={() => setEditing(true)}>
            {t('Edit')}
          </button>
        </h4>
        <div onClick={() => setEditing(true)}>
          {lines.map((text: string) => (
            <p key={text}>{text}</p>
          ))}
          {lines.length == 0 && <p className="placeholder">{t('Click to Edit')}</p>}
        </div>
      </div>
    );
  }

  return (
    <form className="task-notes" onSubmit={handleSave}>
      <h4 className="heading-actions">{t('Notes')}</h4>
      <textarea name="body" rows={lines.length + 2} defaultValue={task.body} autoFocus />
      <div className="button-bar">
        <button type="submit">{t('Save')}</button>
        <button className="button-default" onClick={() => setEditing(false)}>
          {t('Cancel')}
        </button>
      </div>
    </form>
  );
}
