import React from 'react';
import {Inertia} from '@inertiajs/inertia';

import {t} from 'app/locale';
import FormError from 'app/components/formError';
import LoggedIn from 'app/layouts/loggedIn';
import ColorSelect from 'app/components/colorSelect';
import Modal from 'app/components/modal';
import {ValidationErrors} from 'app/types';

type Props = {
  errors?: ValidationErrors;
  referer: string;
};

function ProjectsAdd({errors, referer}: Props) {
  function handleCancel(event: React.MouseEvent<HTMLButtonElement>) {
    event.preventDefault();
    handleClose();
  }

  function handleClose() {
    Inertia.visit(referer);
  }

  function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    const formData = new FormData(e.target as HTMLFormElement);

    Inertia.post('/projects/add', formData);
  }

  return (
    <LoggedIn title={t('New Project')}>
      <Modal onClose={handleClose} label={t('New Project')}>
        <h2>{t('New Project')}</h2>
        <form method="POST" onSubmit={handleSubmit}>
          <input type="hidden" value={referer} name="referer" />
          <div className="form-input">
            <label htmlFor="project-name">{t('Name')}</label>
            <input id="project-name" type="text" name="name" required autoFocus />
            <FormError errors={errors} field="name" />
          </div>
          <div className="form-input">
            <label htmlFor="project-color">{t('Color')}</label>
            <ColorSelect />
            <FormError errors={errors} field="color" />
          </div>
          <div className="button-bar">
            <button className="button-primary" type="submit">
              {t('Save')}
            </button>
            <button className="button-muted" onClick={handleCancel}>
              {t('Cancel')}
            </button>
          </div>
        </form>
      </Modal>
    </LoggedIn>
  );
}
export default ProjectsAdd;
