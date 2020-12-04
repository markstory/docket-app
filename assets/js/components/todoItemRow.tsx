import React, {useState} from 'react';
import {Inertia} from '@inertiajs/inertia';

import {TodoItem} from 'app/types';
import {useProjects} from 'app/providers/projects';
import ProjectBadge from 'app/components/projectBadge';

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
    <div className="todoitem-row">
      <input
        type="checkbox"
        value="1"
        onClick={handleComplete}
        defaultChecked={todo.completed}
      />
      <div className="summary" onDoubleClick={handleDoubleClick}>
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
      <span className="title">{todo.title}</span>
      <div className="attributes">
        {showDueOn && todo.due_on && <time dateTime={todo.due_on}>{todo.due_on}</time>}
        {showProject && <ProjectBadge project={todo.project} />}
      </div>
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
  const [projects] = useProjects();

  return (
    <form className="todoitem-add" onSubmit={handleSubmit}>
      <div className="title">
        <input type="text" name="title" defaultValue={todo.title} autoFocus />
      </div>
      <div className="project">
        <label htmlFor="todoitem-project">Project</label>
        <select name="project_id" defaultValue={todo.project.id}>
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
          defaultValue={todo.due_on ?? undefined}
        />
      </div>
      <div className="button-bar">
        <button type="submit">Save</button>
        <button className="button-default" onClick={onCancel}>
          Cancel
        </button>
      </div>
    </form>
  );
}

export default TodoItemRow;
