import React from 'react';
import {Inertia} from '@inertiajs/inertia';

import {useProjects} from 'app/providers/projects';

type Props = {
  onCancel: () => void;
  defaultDate?: string;
  defaultProjectId?: number;
};

function QuickAddTaskForm({onCancel, defaultDate, defaultProjectId}: Props) {
  const onSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    e.persist();

    const form = e.target as HTMLFormElement;
    const formData = new FormData(form);

    Inertia.post('/todos/add', formData, {
      onSuccess: () => onCancel(),
    });
  };
  const [projects] = useProjects();

  return (
    <form className="todoitem-add" method="post" onSubmit={onSubmit}>
      <div className="title">
        <input
          type="text"
          name="title"
          placeholder="Take out the trash"
          autoFocus
          required
        />
      </div>
      <div className="project">
        <label htmlFor="todoitem-project">Project</label>
        <select id="todoitem-project" name="project_id" defaultValue={defaultProjectId}>
          {projects.map(project => (
            <option key={project.id} value={project.id}>
              {project.name}
            </option>
          ))}
        </select>
      </div>
      <div className="due-on">
        <label htmlFor="todoitem-due-on">Due on</label>
        <input
          id="todoitem-due-on"
          type="date"
          name="due_on"
          defaultValue={defaultDate}
        />
      </div>
      <div className="button-bar">
        <button type="submit">Save</button>
        <button className="button-secondary" onClick={onCancel}>
          Cancel
        </button>
      </div>
    </form>
  );
}
export default QuickAddTaskForm;
