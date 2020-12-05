import React from 'react';
import Modal from 'react-modal';

import {Project, ValidationErrors} from 'app/types';
import LoggedIn from 'app/layouts/loggedIn';
import {Inertia} from '@inertiajs/inertia';

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
            {errors?.name && <div>{errors.name}</div>}
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
            {errors?.color && <div>{errors.color}</div>}
          </div>
          <div>
            <label htmlFor="project-archived">Archived</label>
            <input
              type="checkbox"
              name="archived"
              id="project-archived"
              checked={project.archived}
            />
          </div>
          <button type="submit">Save</button>
        </form>
      </Modal>
    </LoggedIn>
  );
}
