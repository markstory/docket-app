import React, {useState} from 'react';

import {t} from 'app/locale';
import FormError from 'app/components/formError';
import DueOnPicker from 'app/components/dueOnPicker';
import {Task, ValidationErrors} from 'app/types';
import ProjectSelect from 'app/components/projectSelect';

type Props = {
  task: Task;
  url: string;
  errors?: null | ValidationErrors;
  onSubmit: (e: React.FormEvent) => void;
  onCancel: () => void;
};

export default function TaskQuickForm({
  errors,
  task,
  url,
  onSubmit,
  onCancel,
}: Props): JSX.Element {
  const [dueOn, setDueOn] = useState(task.due_on);
  const [evening, setEvening] = useState(task.evening);

  return (
    <form className="task-quickform" method="post" onSubmit={onSubmit} action={url}>
      <div className="title">
        <input
          type="text"
          name="title"
          placeholder={t('Take out the trash')}
          defaultValue={task.title}
          autoFocus
          required
        />
        <FormError errors={errors} field="title" />
      </div>
      <div className="attributes">
        <div className="projectid">
          <ProjectSelect value={task.project.id} />
          <FormError errors={errors} field="project_id" />
        </div>
        <div className="due-on">
          <input type="hidden" name="due_on" value={dueOn ?? ''} />
          <input type="hidden" name="evening" value={evening ? 1 : 0} />
          <DueOnPicker
            task={task}
            onChange={(newDueOn, newEvening) => {
              setDueOn(newDueOn);
              setEvening(newEvening);
            }}
          />
          <FormError errors={errors} field="due_on" />
        </div>
      </div>
      <div className="button-bar">
        <button type="submit" className="button-primary" data-testid="save-task">
          {t('Save')}
        </button>
        <button className="button-muted" onClick={onCancel}>
          {t('Cancel')}
        </button>
      </div>
    </form>
  );
}
