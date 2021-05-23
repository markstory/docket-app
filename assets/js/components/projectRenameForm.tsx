import {Inertia} from '@inertiajs/inertia';

import {Project} from 'app/types';
import {t} from 'app/locale';

type Props = {
  project: Project;
  onCancel: () => void;
};

function ProjectRenameForm({onCancel, project}: Props): JSX.Element {
  const url = `/projects/${project.slug}/edit`;

  function handleSubmit(event: React.FormEvent) {
    event.preventDefault();
    const form = event.target as HTMLFormElement;
    const formData = new FormData(form);
    Inertia.post(url, formData, {
      onSuccess: () => onCancel(),
    });
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
    <form
      className="project-rename form-inline-rename"
      method="post"
      action={url}
      onSubmit={handleSubmit}
    >
      <div className="title" onKeyDown={handleKeyDown}>
        <input type="text" name="name" defaultValue={project.name} autoFocus required />
      </div>
      <div className="button-bar-inline">
        <button type="submit" className="button-primary" data-testid="save-section">
          {t('Save')}
        </button>
        <button className="button-muted" onClick={onCancel}>
          {t('Cancel')}
        </button>
      </div>
    </form>
  );
}

export default ProjectRenameForm;
