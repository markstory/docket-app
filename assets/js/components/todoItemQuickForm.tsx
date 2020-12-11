import React from 'react';

import FormError from 'app/components/formError';
import {TodoItem, ValidationErrors} from 'app/types';
import {useProjects} from 'app/providers/projects';

type Props = {
  todoItem: TodoItem;
  errors?: null | ValidationErrors;
  onSubmit: (e: React.FormEvent) => void;
  onCancel: () => void;
};

export default function TodoItemQuickForm({errors, todoItem, onSubmit, onCancel}: Props) {
  const [projects] = useProjects();

  return (
    <form className="todoitem-quickform" method="post" onSubmit={onSubmit}>
      <input type="hidden" name="child_order" value={todoItem.child_order} />
      <input type="hidden" name="day_order" value={todoItem.day_order} />
      <div className="title">
        <input
          type="text"
          name="title"
          placeholder="Take out the trash"
          defaultValue={todoItem.title}
          autoFocus
          required
        />
        <FormError errors={errors} field="title" />
      </div>
      <div className="project">
        <label htmlFor="todoitem-project">Project</label>
        <select
          id="todoitem-project"
          name="project_id"
          defaultValue={todoItem.project.id}
        >
          {projects.map(project => (
            <option key={project.id} value={project.id}>
              {project.name}
            </option>
          ))}
        </select>
        <FormError errors={errors} field="project_id" />
      </div>
      <div className="due-on">
        <label htmlFor="todoitem-due-on">Due on</label>
        <input
          id="todoitem-due-on"
          type="date"
          name="due_on"
          defaultValue={todoItem.due_on ?? undefined}
        />
        <FormError errors={errors} field="due_on" />
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
