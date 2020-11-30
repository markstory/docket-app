import React from 'react';
import Modal from 'react-modal';
import axios from 'axios';
import {Inertia} from '@inertiajs/inertia';

import {ValidationErrors} from 'app/types';

type Props = {
  showModal: boolean;
  onClose: () => void;
};

function NewProjectModal({showModal, onClose}: Props) {
  if (!showModal) {
    return null;
  }

  const [errors, setErrors] = React.useState<ValidationErrors>({});
  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    const form = e.target as HTMLFormElement;
    const formData = new FormData(form);

    // Do an XHR request so we can handle validation errors
    // inside the modal.
    axios
      .post('/projects/add', formData)
      .then(() => {
        Inertia.visit('/todos');
      })
      .catch(error => {
        if (error.response) {
          setErrors(error.response.data.errors);
        }
      });
  };

  return (
    <Modal className="modal" overlayClassName="modal-overlay" isOpen>
      <button onClick={onClose}>{'\u2715'}</button>
      <form method="POST" onSubmit={handleSubmit}>
        <h2>New Project</h2>
        <div>
          <label htmlFor="project-name">Name</label>
          <input id="project-name" type="text" name="name" required />
          {errors.name && <div>{errors.name}</div>}
        </div>
        <div>
          <label htmlFor="project-color">Color</label>
          <input
            id="project-color"
            type="text"
            name="color"
            required
            defaultValue="ff00ff"
          />
          {errors.color && <div>{errors.color}</div>}
        </div>
        <button type="submit">Save</button>
      </form>
    </Modal>
  );
}
export default NewProjectModal;
