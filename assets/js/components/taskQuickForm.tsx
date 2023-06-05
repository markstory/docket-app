import {useEffect, useState, useRef} from 'react';

import {t} from 'app/locale';
import FormError from 'app/components/formError';
import DueOnPicker from 'app/components/dueOnPicker';
import {TaskDetailed, ValidationErrors} from 'app/types';
import ProjectSelect from 'app/components/projectSelect';
import SmartTaskInput from 'app/components/smartTaskInput';
import {useProjects} from 'app/providers/projects';
import TaskSubtasks from './taskSubtasks';
import {InlineIcon} from './icon';

type Props = {
  task: TaskDetailed;
  url: string;
  onSubmit: (e: React.FormEvent) => Promise<boolean>;
  onCancel: () => void;
  errors?: null | ValidationErrors;
  showNotes?: boolean;
  showSubtasks?: boolean;
};

export default function TaskQuickForm({
  errors,
  task,
  url,
  onSubmit,
  onCancel,
  showNotes = false,
  showSubtasks = false,
}: Props): JSX.Element {
  const mounted = useRef(true);
  const notesRef = useRef<HTMLTextAreaElement>(null);
  const titleRef = useRef<HTMLInputElement>(null);
  const [textTitle, setTextTitle] = useState(task.title);
  const [data, setData] = useState(task);
  const [busy, setBusy] = useState(false);
  const [showNotesInput, setShowNotesInput] = useState(false);
  const [showSubtaskInputs, setShowSubtaskInputs] = useState(false);
  const [projects] = useProjects();

  mounted.current = true;

  // Be careful to use setState() with an updater callback.
  // Failing to do so results in stale data.
  function handleChangeProject(projectId: number) {
    setData(prevState => ({
      ...prevState,
      project: {...prevState.project, id: projectId},
    }));
  }

  function handleChangeDueOn(dueOn: string | null) {
    setData(prevState => ({...prevState, due_on: dueOn}));
  }

  function handleChangeEvening(evening: boolean) {
    setData(prevState => ({...prevState, evening: evening}));
  }

  function handleChangeTitle(title: string, textTitle: string) {
    setTextTitle(textTitle);
    setData(prevState => ({...prevState, title}));
  }

  function handleSubmit(e: React.FormEvent) {
    setBusy(true);
    onSubmit(e)
      .then(() => {
        // This can happen after saving is complete and the form has been removed from the DOM.
        if (!mounted.current) {
          return;
        }
        setBusy(false);
        setTextTitle('');
        setShowNotesInput(false);
        setData(prevState => ({...prevState, title: '', body: ''}));
      })
      .catch(() => {
        setBusy(false);
      });
  }

  function handleKeyDown(e: React.KeyboardEvent<HTMLFormElement>) {
    switch (e.key) {
      case 'Esc':
      case 'Escape':
        e.preventDefault();
        e.stopPropagation();
        break;
    }
  }

  function handleToggleNotes(event: React.MouseEvent) {
    event.preventDefault();
    setShowNotesInput(true);
  }
  function handleToggleSubtasks(event: React.MouseEvent) {
    event.preventDefault();
    setShowSubtaskInputs(true);
  }

  useEffect(() => {
    if (showNotesInput && notesRef.current) {
      notesRef.current.focus();
    }
  }, [showNotesInput]);
  useEffect(() => {
    if (titleRef.current && textTitle == '') {
      titleRef.current.focus();
    }
  }, [textTitle]);

  const extraButtons = [];
  if (showNotes && !showNotesInput) {
    extraButtons.push(
      <button
        type="button"
        onClick={handleToggleNotes}
        className="button-muted"
        data-testid="add-notes"
      >
        {t('Add Notes')}
      </button>
    );
  }
  if (showSubtasks && !showSubtaskInputs) {
    extraButtons.push(
      <button
        type="button"
        onClick={handleToggleSubtasks}
        className="button-muted"
        data-testid="add-subtasks"
      >
        {t('Add Subtasks')}
      </button>
    );
  }

  return (
    <form
      className="task-quickform"
      method="post"
      onSubmit={handleSubmit}
      action={url}
      onKeyDown={handleKeyDown}
    >
      {data.section_id && (
        <input type="hidden" name="section_id" value={data.section_id} />
      )}
      <div className="title">
        <SmartTaskInput
          inputRef={titleRef}
          value={data.title}
          projects={projects}
          onChangeProject={handleChangeProject}
          onChangeDate={handleChangeDueOn}
          onChangeEvening={handleChangeEvening}
          onChangeTitle={handleChangeTitle}
        />
        <FormError errors={errors} field="title" />
        <input data-testid="task-title" type="hidden" name="title" value={textTitle} />
      </div>
      <div className="attributes">
        <div className="projectid">
          <ProjectSelect value={data.project.id} onChange={handleChangeProject} />
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
        {extraButtons.length && <div className="task-extra-buttons">{extraButtons}</div>}
      </div>
      {showNotesInput && (
        <div className="task-body">
          <h4 className="heading-button">
            <InlineIcon icon="note" />
            {t('Notes')}
          </h4>

          <textarea
            ref={notesRef}
            id="task-body"
            name="body"
            rows={3}
            defaultValue={data.body ?? ''}
          />
        </div>
      )}
      {showSubtaskInputs && <TaskSubtasks task={task} />}
      <div className="button-bar">
        <button
          type="submit"
          className="button-primary"
          data-testid="save-task"
          aria-disabled={busy}
          disabled={busy}
        >
          {t('Save')}
        </button>
        <button className="button-muted" onClick={onCancel}>
          {t('Cancel')}
        </button>
      </div>
    </form>
  );
}
