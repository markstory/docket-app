import {useState} from 'react';
import axios from 'axios';
import {Inertia} from '@inertiajs/inertia';

import {TaskDetailed, ValidationErrors} from 'app/types';
import LoggedIn from 'app/layouts/loggedIn';
import Checkbox from 'app/components/checkbox';
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
    const promise = new Promise((resolve, reject) => {
      // Do an XHR request so we can handle validation errors
      // inside the modal.
      axios
        .post(`/tasks/${task.id}/edit`, formData)
        .then(() => {
          setEditing(false);
          resolve(true);
          Inertia.reload({only: ['task']});
        })
        .catch(error => {
          const errors: ValidationErrors = {};
          if (error.response) {
            setErrors(error.response.data.errors);
          }
          reject(errors);
        });
    });

    return promise;
  }

  function handleCancel() {
    setEditing(false);
  }

  return (
    <LoggedIn title={task.title}>
      <Modal onClose={handleClose} label={task.title}>
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
  const handleComplete = (e: React.ChangeEvent<HTMLInputElement>) => {
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
        <Checkbox name="complete" checked={task.completed} onChange={handleComplete} />
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
