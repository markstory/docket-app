import React from 'react';
import {Inertia} from '@inertiajs/inertia';

import {t} from 'app/locale';
import Modal from 'app/components/modal';
import {Project, ValidationErrors} from 'app/types';
import ColorSelect from 'app/components/colorSelect';
import FormError from 'app/components/formError';
import LoggedIn from 'app/layouts/loggedIn';

type Props = {
  project: Project;
  errors: ValidationErrors | null;
  referer: string;
};

export default function ProjectsEdit({project, errors, referer}: Props) {
  function handleClose() {
    Inertia.visit(referer);
  }

  function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    const formData = new FormData(e.target as HTMLFormElement);
    Inertia.post(`/projects/${project.slug}/edit`, formData);
  }
  const title = t('Edit {project} Project', {project: project.name});

  return (
    <LoggedIn title={title}>
      <Modal onClose={handleClose} label={title}>
        <h2>{t('Edit Project')}</h2>
        <form className="form-vertical" method="post" onSubmit={handleSubmit}>
          <input type="hidden" name="referer" value={referer} />
          <div className="form-input">
            <label htmlFor="project-name">{t('Name')}</label>
            <input
              id="project-name"
              type="text"
              name="name"
              required
              defaultValue={project.name}
            />
            <FormError errors={errors} field="name" />
          </div>
          <div className="form-input">
            <label htmlFor="project-color">{t('Color')}</label>
            <ColorSelect value={project.color} />
            <FormError errors={errors} field="color" />
          </div>
          <div className="form-input-checkbox">
            <label htmlFor="project-archived">{t('Archived')}</label>
            <input
              type="checkbox"
              name="archived"
              id="project-archived"
              defaultChecked={project.archived}
            />
          </div>
          <div className="button-bar">
            <button type="submit" className="button-primary">
              {t('Save')}
            </button>
            <button type="reset" className="button-muted" onClick={handleClose}>
              {t('Cancel')}
            </button>
          </div>
        </form>
      </Modal>
    </LoggedIn>
  );
}
