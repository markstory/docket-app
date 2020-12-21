import React from 'react';
import {Inertia} from '@inertiajs/inertia';

import FormError from 'app/components/formError';
import LoggedIn from 'app/layouts/loggedIn';
import Modal from 'app/components/modal';
import {ValidationErrors} from 'app/types';

type Props = {
  errors?: ValidationErrors;
  referer: string;
};

function ProjectsAdd({errors, referer}: Props) {
  function handleClose() {
    Inertia.visit(referer);
  }

  function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    const formData = new FormData(e.target as HTMLFormElement);

    Inertia.post('/projects/add', formData);
  }

  return (
    <LoggedIn>
      <Modal onClose={handleClose}>
        <h2>New Project</h2>
        <form method="POST" onSubmit={handleSubmit}>
          <input type="hidden" value={referer} name="referer" />
          <div className="form-input">
            <label htmlFor="project-name">Name</label>
            <input id="project-name" type="text" name="name" required />
            <FormError errors={errors} field="name" />
          </div>
          <div className="form-input">
            <label htmlFor="project-color">Color</label>
            <input
              id="project-color"
              type="text"
              name="color"
              required
              defaultValue="ff00ff"
            />
            <FormError errors={errors} field="color" />
          </div>
          <div className="button-bar">
            <button type="submit">Save</button>
          </div>
        </form>
      </Modal>
    </LoggedIn>
  );
}
export default ProjectsAdd;
