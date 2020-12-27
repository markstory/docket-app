import React, {useState} from 'react';

import FormError from 'app/components/formError';
import DueOnPicker from 'app/components/dueOnPicker';
import {Task, ValidationErrors} from 'app/types';
import {useProjects} from 'app/providers/projects';

type Props = {
  task: Task;
  errors?: null | ValidationErrors;
  onSubmit: (e: React.FormEvent) => void;
  onCancel: () => void;
};

export default function TaskQuickForm({errors, task, onSubmit, onCancel}: Props) {
  const [projects] = useProjects();
  const [dueOn, setDueOn] = useState(task.due_on);

  return (
    <form className="task-quickform" method="post" onSubmit={onSubmit}>
      <div className="title">
        <input
          type="text"
          name="title"
          placeholder="Take out the trash"
          defaultValue={task.title}
          autoFocus
          required
        />
        <FormError errors={errors} field="title" />
      </div>
      <div className="attributes">
        <div className="project">
          <label htmlFor="task-project">Project</label>
          <select id="task-project" name="project_id" defaultValue={task.project.id}>
            {projects.map(project => (
              <option key={project.id} value={project.id}>
                {project.name}
              </option>
            ))}
          </select>
          <FormError errors={errors} field="project_id" />
        </div>
        <div className="due-on">
          <input type="hidden" name="due_on" value={dueOn ?? ''} />
          <DueOnPicker
            selected={dueOn}
            onChange={(value: Task['due_on']) => setDueOn(value)}
          />
          <FormError errors={errors} field="due_on" />
        </div>
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
