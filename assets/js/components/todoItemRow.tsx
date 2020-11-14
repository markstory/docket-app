import React, {useState} from 'react';
import {Inertia} from '@inertiajs/inertia';

import {TodoItem} from 'app/types';
import ProjectBadge from 'app/components/projectBadge';
import ProjectsContext from 'app/components/projectsContext';

type Props = {
  todo: TodoItem;
};

function TodoItemRow({todo}: Props) {
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
          <TodoItemInlineEdit todo={todo} onCancel={() => setEdit(!edit)} />
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

function TodoItemInlineEdit({todo, onCancel}: InlineEditProps) {
  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    const formData = new FormData(e.target as HTMLFormElement);
    Inertia.post(`/todos/${todo.id}/edit`, formData);
    onCancel();
  };

  return (
    <ProjectsContext.Consumer>
      {projects => (
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
      )}
    </ProjectsContext.Consumer>
  );
}

export default TodoItemRow;
