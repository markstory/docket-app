import React, {useState} from 'react';
import {Inertia} from '@inertiajs/inertia';

import {TodoItem} from 'app/types';
import ProjectBadge from 'app/components/projectBadge';
import ProjectsContext from 'app/components/projectsContext';

type Props = {
  todo: TodoItem;
  showDueOn?: boolean;
  showProject?: boolean;
};

function TodoItemRow({todo, showDueOn, showProject}: Props) {
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
          <TodoItemSummary todo={todo} showProject={showProject} showDueOn={showDueOn} />
        )}
      </div>
    </div>
  );
}

function TodoItemSummary({
  todo,
  showDueOn,
  showProject,
}: Pick<Props, 'todo' | 'showDueOn' | 'showProject'>) {
  return (
    <React.Fragment>
      <span>{todo.title}</span>
      {showProject && <ProjectBadge project={todo.project} />}
      {showDueOn && todo.due_on && <time dateTime={todo.due_on}>{todo.due_on}</time>}
    </React.Fragment>
  );
}

type InlineEditProps = {
  todo: TodoItem;
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
