import React from 'react';
import {Inertia} from '@inertiajs/inertia';

import {t} from 'app/locale';
import {Project} from 'app/types';

type Props = {
  project: Project;
  onCancel?: () => void;
};

function SectionQuickForm({onCancel, project}: Props): JSX.Element {
  const url = `/projects/${project.slug}/sections`;
  function handleSubmit(event: React.FormEvent) {
    event.preventDefault();
    const form = event.target as HTMLFormElement;
    const formData = new FormData(form);
    Inertia.post(url, formData, {
      onSuccess: () => onCancel?.(),
    });
  }
  return (
    <form
      className="section-quickform"
      method="post"
      action={url}
      onSubmit={handleSubmit}
    >
      <div className="title">
        <input
          type="text"
          name="name"
          placeholder={t('Movies to watch')}
          autoFocus
          required
        />
      </div>
      <div className="button-bar">
        <button type="submit" className="button-primary" data-testid="save-task">
          {t('Save')}
        </button>
        <button className="button-muted" onClick={onCancel}>
          {t('Cancel')}
        </button>
      </div>
    </form>
  );
}

export default SectionQuickForm;
