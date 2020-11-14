import React, {useState} from 'react';
import {Inertia} from '@inertiajs/inertia';

import {TodoItem} from 'app/types';
import ProjectBadge from 'app/components/projectBadge';
import ProjectsContext from 'app/components/projectsContext';

type Props = {
  todo: TodoItem;
};

function TodoItemRow({todo}: Props) {
  const [edit, setEdit] = useState(false);

  const handleComplete = (e: React.MouseEvent<HTMLInputElement>) => {
    e.stopPropagation();
    Inertia.post(`/todos/${todo.id}/complete`);
  };

  const handleDoubleClick = () => {
    if (edit) {
      return false;
    }
    setEdit(!edit);
    return false;
  };

  return (
    <div>
      <input
        type="checkbox"
        value="1"
        onClick={handleComplete}
        defaultChecked={todo.completed}
      />
      <div onDoubleClick={handleDoubleClick}>
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
          <select name="project_id" defaultValue={todo.project.id}>
            {projects.map(project => (
              <option key={project.id} value={project.id}>
                {project.name}
              </option>
            ))}
          </select>
          <input type="date" name="due_on" defaultValue={todo.due_on ?? undefined} />
          <button type="submit">Save</button>
          <button onClick={onCancel}>Cancel</button>
        </form>
      )}
    </ProjectsContext.Consumer>
  );
}

export default TodoItemRow;
