import React from 'react';
import Modal from 'react-modal';
import {Inertia} from '@inertiajs/inertia';

import {Project, ValidationErrors} from 'app/types';
import FormError from 'app/components/formError';
import LoggedIn from 'app/layouts/loggedIn';

type Props = {
  project: Project;
  errors: ValidationErrors | null;
  referer: string;
};

export default function ProjectsEdit({project, errors, referer}: Props) {
  function handleClose(event: React.MouseEvent) {
    event.preventDefault();
    Inertia.visit(referer);
  }

  function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    const formData = new FormData(e.target as HTMLFormElement);
    Inertia.post(`/projects/${project.slug}/edit`, formData);
  }

  return (
    <LoggedIn>
      <Modal className="modal" overlayClassName="modal-overlay" isOpen>
        <button onClick={handleClose}>{'\u2715'}</button>
        <form method="POST" onSubmit={handleSubmit}>
          <h2>Edit Project</h2>
          <div>
            <label htmlFor="project-name">Name</label>
            <input
              id="project-name"
              type="text"
              name="name"
              required
              defaultValue={project.name}
            />
            <FormError errors={errors} field="name" />
          </div>
          <div>
            <label htmlFor="project-color">Color</label>
            <input
              id="project-color"
              type="text"
              name="color"
              required
              defaultValue={project.color}
            />
            <FormError errors={errors} field="color" />
          </div>
          <div>
            <label htmlFor="project-archived">Archived</label>
            <input
              type="checkbox"
              name="archived"
              id="project-archived"
              defaultChecked={project.archived}
            />
          </div>
          <button type="submit">Save</button>
        </form>
      </Modal>
    </LoggedIn>
  );
}
