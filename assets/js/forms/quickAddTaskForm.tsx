import React from 'react';
import {Inertia} from '@inertiajs/inertia';

import ProjectsContext from 'app/components/projectsContext';

type Props = {
  onCancel: () => void;
  defaultDate?: string;
};

function QuickAddTaskForm({onCancel, defaultDate}: Props) {
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
          <input type="text" name="title" autoFocus />
          <select name="project_id">
            {projects.map(project => (
              <option key={project.id} value={project.id}>
                {project.name}
              </option>
            ))}
          </select>
          <input type="date" name="due_on" defaultValue={defaultDate} />
          <button type="submit">Save</button>
          <button onClick={onCancel}>Cancel</button>
        </form>
      )}
    </ProjectsContext.Consumer>
  );
}
export default QuickAddTaskForm;
