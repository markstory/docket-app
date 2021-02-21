import React, {useState} from 'react';
import axios from 'axios';
import {Inertia} from '@inertiajs/inertia';

import {TaskDetailed, ValidationErrors} from 'app/types';
import {t} from 'app/locale';
import LoggedIn from 'app/layouts/loggedIn';
import DueOn from 'app/components/dueOn';
import Modal from 'app/components/modal';
import TaskQuickForm from 'app/components/taskQuickForm';
import TaskNotes from 'app/components/taskNotes';
import TaskSubtasks from 'app/components/taskSubtasks';
import ProjectBadge from 'app/components/projectBadge';

type Props = {
  task: TaskDetailed;
  referer: string;
};

export default function TasksView({referer, task}: Props): JSX.Element {
  const [editing, setEditing] = useState(false);
  const [errors, setErrors] = useState<ValidationErrors>({});

  function handleClose() {
    const target = referer === window.location.pathname ? '/tasks/upcoming' : referer;
    Inertia.visit(target);
  }

  function handleSubmit(event: React.FormEvent) {
    event.preventDefault();
    const formData = new FormData(event.target as HTMLFormElement);

    // Do an XHR request so we can handle validation errors
    // inside the modal.
    axios
      .post(`/tasks/${task.id}/edit`, formData)
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
  const title = t('View {task}', {task: task.title});

  return (
    <LoggedIn title={title}>
      <Modal onClose={handleClose} label={title}>
        <div className="task-view">
          {editing ? (
            <TaskQuickForm
              url={`/tasks/${task.id}/edit`}
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
      `/tasks/${task.id}/${task.completed ? 'incomplete' : 'complete'}`,
      {},
      {only: ['task']}
    );
  };

  return (
    <div className="task-view-summary">
      <div className="title">
        <input
          className="checkbox"
          type="checkbox"
          value="1"
          onClick={handleComplete}
          defaultChecked={task.completed}
        />
        <a href="#" role="button" onClick={onClick}>
          <h3>{task.title}</h3>
        </a>
      </div>
      <a href="#" onClick={onClick} className="attributes">
        <ProjectBadge project={task.project} />
        <DueOn task={task} />
      </a>
    </div>
  );
}
