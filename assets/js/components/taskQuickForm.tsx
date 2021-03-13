import React, {useState} from 'react';

import {t} from 'app/locale';
import FormError from 'app/components/formError';
import DueOnPicker from 'app/components/dueOnPicker';
import {Task, ValidationErrors} from 'app/types';
import ProjectSelect from 'app/components/projectSelect';
import SmartTaskInput from 'app/components/smartTaskInput';
import {useProjects} from 'app/providers/projects';

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
  const [data, setData] = useState(task);
  const [projects] = useProjects();

  function handleChangeProject(projectId: number) {
    setData({...data, project: {...data.project, id: projectId}});
  }
  function handleChangeDueOn(dueOn: string | null) {
    setData({...data, due_on: dueOn});
  }

  return (
    <form className="task-quickform" method="post" onSubmit={onSubmit} action={url}>
      <div className="title">
        <SmartTaskInput
          defaultValue={data.title}
          projects={projects}
          onChangeProject={handleChangeProject}
          onChangeDate={handleChangeDueOn}
        />
        <FormError errors={errors} field="title" />
      </div>
      <div className="attributes">
        <div className="projectid">
          <ProjectSelect value={data.project.id} />
          <FormError errors={errors} field="project_id" />
        </div>
        <div className="due-on">
          <input type="hidden" name="due_on" value={data.due_on ?? ''} />
          <input type="hidden" name="evening" value={data.evening ? 1 : 0} />
          <DueOnPicker
            task={data}
            onChange={(newDueOn, newEvening) => {
              setData({...data, due_on: newDueOn, evening: newEvening});
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
