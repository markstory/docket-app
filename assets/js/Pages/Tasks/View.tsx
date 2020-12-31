import React, {useState} from 'react';
import axios from 'axios';
import {Inertia} from '@inertiajs/inertia';
import {InlineIcon} from '@iconify/react';

import {TaskDetailed, ValidationErrors} from 'app/types';
import {t} from 'app/locale';
import LoggedIn from 'app/layouts/loggedIn';
import Modal from 'app/components/modal';
import TaskQuickForm from 'app/components/taskQuickForm';
import TaskNotes from 'app/components/taskNotes';
import TaskSubtasks from 'app/components/taskSubtasks';
import ProjectBadge from 'app/components/projectBadge';
import {formatCompactDate} from 'app/utils/dates';

type Props = {
  task: TaskDetailed;
  referer: string;
};

export default function TasksView({referer, task}: Props) {
  const [editing, setEditing] = useState(false);
  const [errors, setErrors] = useState<ValidationErrors>({});

  function handleClose() {
    const target = referer === window.location.pathname ? '/todos/upcoming' : referer;
    Inertia.visit(target);
  }

  function handleSubmit(event: React.FormEvent) {
    event.preventDefault();
    const formData = new FormData(event.target as HTMLFormElement);

    // Do an XHR request so we can handle validation errors
    // inside the modal.
    axios
      .post(`/todos/${task.id}/edit`, formData)
      .then(() => {
        setEditing(false);
        Inertia.reload({only: ['task']});
      })
      .catch(error => {
        if (error.response) {
          setErrors(error.response.data.errors);
        }
      });
  }

  function handleCancel() {
    setEditing(false);
  }

  return (
    <LoggedIn title={t('View {task}', {task: task.title})}>
      <Modal onClose={handleClose}>
        <div className="task-view">
          {editing ? (
            <TaskQuickForm
              onSubmit={handleSubmit}
              onCancel={handleCancel}
              task={task}
              errors={errors}
            />
          ) : (
            <TaskSummary task={task} onClick={() => setEditing(true)} />
          )}
          <TaskNotes task={task} />
          <TaskSubtasks task={task} />
        </div>
      </Modal>
    </LoggedIn>
  );
}

type SummaryProps = {
  task: TaskDetailed;
  onClick: () => void;
};

function TaskSummary({task, onClick}: SummaryProps) {
  const handleComplete = (e: React.MouseEvent<HTMLInputElement>) => {
    e.stopPropagation();
    Inertia.post(
      `/todos/${task.id}/${task.completed ? 'incomplete' : 'complete'}`,
      {},
      {only: ['task']}
    );
  };

  return (
    <div className="summary">
      <input
        className="completed"
        type="checkbox"
        value="1"
        onClick={handleComplete}
        defaultChecked={task.completed}
      />
      <a href="#" onClick={onClick}>
        <h3>{task.title}</h3>
        <div className="attributes">
          {<ProjectBadge project={task.project} />}
          {task.due_on && (
            <time className="due-on" dateTime={task.due_on}>
              <InlineIcon icon="calendar" />
              {formatCompactDate(task.due_on)}
            </time>
          )}
        </div>
      </a>
    </div>
  );
}
