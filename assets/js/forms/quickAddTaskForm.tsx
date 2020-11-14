import React from 'react';
import {Inertia} from '@inertiajs/inertia';

import ProjectsContext from 'app/components/projectsContext';

type Props = {
  onCancel: () => void;
};

function QuickAddTaskForm({onCancel}: Props) {
  const onSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    const formData = new FormData(e.target as HTMLFormElement);
    Inertia.post('/todos/add', formData);
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
          <input type="date" name="due_on" />
          <button type="submit">Save</button>
          <button onClick={onCancel}>Cancel</button>
        </form>
      )}
    </ProjectsContext.Consumer>
  );
}
export default QuickAddTaskForm;
