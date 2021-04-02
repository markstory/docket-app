import React from 'react';
import {Inertia} from '@inertiajs/inertia';

import {t} from 'app/locale';
import Modal from 'app/components/modal';
import {Project, ValidationErrors} from 'app/types';
import ColorSelect from 'app/components/colorSelect';
import FormControl from 'app/components/formControl';
import LoggedIn from 'app/layouts/loggedIn';
import ToggleCheckbox from 'app/components/toggleCheckbox';

type Props = {
  project: Project;
  referer: string;
  errors?: ValidationErrors;
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
          <FormControl
            className="narrow"
            name="name"
            label={t('Name')}
            type="text"
            value={project.name}
            errors={errors}
            required
          />
          <FormControl
            className="narrow"
            name="color"
            label={t('Color')}
            type={() => <ColorSelect value={project.color} />}
            errors={errors}
          />
          <FormControl
            className="narrow"
            name="archived"
            label={t('Archived')}
            type={() => (
              <React.Fragment>
                <input type="hidden" name="archived" value="0" />
                <ToggleCheckbox name="archived" checked={project.archived} />
              </React.Fragment>
            )}
            errors={errors}
            required
          />
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
