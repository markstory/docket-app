import React, {useState} from 'react';

import {t} from 'app/locale';
import FormError from 'app/components/formError';
import DueOnPicker from 'app/components/dueOnPicker';
import {Task, ValidationErrors} from 'app/types';
import ProjectSelect from 'app/components/projectSelect';

type Props = {
  task: Task;
  errors?: null | ValidationErrors;
  onSubmit: (e: React.FormEvent) => void;
  onCancel: () => void;
};

export default function TaskQuickForm({errors, task, onSubmit, onCancel}: Props) {
  const [dueOn, setDueOn] = useState(task.due_on);

  return (
    <form className="task-quickform" method="post" onSubmit={onSubmit}>
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
        <div className="project">
          <ProjectSelect value={task.project.id} />
          <FormError errors={errors} field="project_id" />
        </div>
        <div className="due-on">
          <input type="hidden" name="due_on" value={dueOn ?? ''} />
          <DueOnPicker
            selected={dueOn}
            onChange={(value: Task['due_on']) => setDueOn(value)}
          />
          <FormError errors={errors} field="due_on" />
        </div>
      </div>
      <div className="button-bar">
        <button type="submit" data-testid="save-task">
          {t('Save')}
        </button>
        <button className="button-secondary" onClick={onCancel}>
          {t('Cancel')}
        </button>
      </div>
    </form>
  );
}
