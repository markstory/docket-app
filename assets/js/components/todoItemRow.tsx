import React, {useState} from 'react';
import {Inertia} from '@inertiajs/inertia';

import {TodoItem, Project} from 'app/types';
import ProjectBadge from 'app/components/projectBadge';

type Props = {
  todo: TodoItem;
  projects: Project[];
};

function TodoItemRow({todo, projects}: Props) {
  const handleComplete = (e: React.MouseEvent<HTMLInputElement>) => {
    e.stopPropagation();
    Inertia.post(`/todos/${todo.id}/complete`);
  };
  const [edit, setEdit] = useState(false);

  return (
    <div>
      <input
        type="checkbox"
        value="1"
        onClick={handleComplete}
        defaultChecked={todo.completed}
      />
      <div onDoubleClick={() => setEdit(!edit)}>
        {edit ? (
          <TodoItemInlineEdit
            todo={todo}
            projects={projects}
            onCancel={() => setEdit(!edit)}
          />
        ) : (
          <TodoItemSummary todo={todo} />
        )}
      </div>
    </div>
  );
}

function TodoItemSummary({todo}: Pick<Props, 'todo'>) {
  return (
    <React.Fragment>
      <span>{todo.title}</span>
      <ProjectBadge project={todo.project} />
    </React.Fragment>
  );
}

type InlineEditProps = Props & {
  onCancel: () => void;
};

function TodoItemInlineEdit({todo, projects, onCancel}: InlineEditProps) {
  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    const formData = new FormData(e.target as HTMLFormElement);
    Inertia.post(`/todos/${todo.id}/edit`, formData);
    onCancel();
  };

  return (
    <form onSubmit={handleSubmit}>
      <input type="text" name="title" defaultValue={todo.title} autoFocus />
      <select name="project_id">
        {projects.map(project => (
          <option
            key={project.id}
            value={project.id}
            selected={todo.project.id == project.id ? true : undefined}
          >
            {project.name}
          </option>
        ))}
      </select>
      <input type="date" name="due_on" />
      <button type="submit">Save</button>
      <button onClick={onCancel}>Cancel</button>
    </form>
  );
}

export default TodoItemRow;
