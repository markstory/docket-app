import {useState} from 'react';
import {Inertia} from '@inertiajs/inertia';

import {updateTask} from 'app/actions/tasks';
import MarkdownText from 'app/components/markdownText';
import useKeyboardShortcut from 'app/hooks/useKeyboardShortcut';
import {t} from 'app/locale';
import {Task} from 'app/types';
import {InlineIcon} from './icon';

type Props = {
  task: Task;
};

export default function TaskNotes({task}: Props) {
  const [editing, setEditing] = useState(false);
  useKeyboardShortcut(['n'], () => {
    setEditing(true);
  });

  function handleSave(event: React.FormEvent): void {
    event.preventDefault();
    const formData = new FormData(event.target as HTMLFormElement);
    updateTask(task, formData).then(() => {
      Inertia.get(`/tasks/${task.id}/view`, {}, {only: ['task'], replace: true});
    });
  }

  function handleClick(event: React.MouseEvent): void {
    if (event.target instanceof HTMLElement && event.target.nodeName === 'A') {
      return;
    }
    setEditing(true);
  }

  const lines = task.body ? task.body.split('\n') : [];
  if (!editing) {
    return (
      <div className="task-notes">
        <h4 className="heading-button">
          <InlineIcon icon="note" />
          {t('Notes')}
          <button
            className="button-secondary button-narrow"
            onClick={() => setEditing(true)}
          >
            {t('Edit')}
          </button>
        </h4>
        <div onClick={handleClick}>
          <MarkdownText text={task.body} />
          {lines.length == 0 && <p className="placeholder">{t('Click to Edit')}</p>}
        </div>
      </div>
    );
  }

  return (
    <form className="task-notes" onSubmit={handleSave}>
      <h4 className="heading-button">{t('Notes')}</h4>
      <textarea
        name="body"
        rows={lines.length + 2}
        defaultValue={task.body ?? ''}
        autoFocus
      />
      <div className="button-bar">
        <button type="submit" className="button-primary">
          {t('Save')}
        </button>
        <button className="button-muted" onClick={() => setEditing(false)}>
          {t('Cancel')}
        </button>
      </div>
    </form>
  );
}
