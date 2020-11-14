import React from 'react';
import {Inertia} from '@inertiajs/inertia';

import ProjectsContext from 'app/components/projectsContext';

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

  return (
    <ProjectsContext.Consumer>
      {projects => (
        <form method="post" onSubmit={onSubmit}>
          <input type="text" name="title" autoFocus required />
          <select name="project_id" defaultValue={defaultProjectId}>
            {projects.map(project => (
              <option key={project.id} value={project.id}>
                {project.name}
              </option>
            ))}
          </select>
          <div>
            <label htmlFor="todoitem-date">Due on</label>
            <input
              id="todoitem-date"
              type="date"
              name="due_on"
              defaultValue={defaultDate}
            />
          </div>
          <button type="submit">Save</button>
          <button onClick={onCancel}>Cancel</button>
        </form>
      )}
    </ProjectsContext.Consumer>
  );
}
export default QuickAddTaskForm;
