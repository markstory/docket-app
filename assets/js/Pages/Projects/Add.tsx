import {Inertia} from '@inertiajs/inertia';

import {t} from 'app/locale';
import FormControl from 'app/components/formControl';
import LoggedIn from 'app/layouts/loggedIn';
import ColorSelect from 'app/components/colorSelect';
import Modal from 'app/components/modal';
import {ValidationErrors} from 'app/types';

type Props = {
  errors?: ValidationErrors;
  referer: string;
};

function ProjectsAdd({errors, referer}: Props) {
  function handleCancel(event: React.MouseEvent<HTMLButtonElement>) {
    event.preventDefault();
    handleClose();
  }

  function handleClose() {
    Inertia.visit(referer);
  }

  function handleSubmit(e: React.FormEvent) {
    e.preventDefault();
    const formData = new FormData(e.target as HTMLFormElement);

    Inertia.post('/projects/add', formData);
  }

  return (
    <LoggedIn title={t('New Project')}>
      <Modal onClose={handleClose} label={t('New Project')}>
        <h2>{t('New Project')}</h2>
        <form method="POST" onSubmit={handleSubmit}>
          <input type="hidden" value={referer} name="referer" />
          <FormControl
            className="narrow"
            name="name"
            label={t('Name')}
            type="text"
            errors={errors}
            required
          />
          <FormControl
            className="narrow"
            name="color"
            label={t('Color')}
            type={() => <ColorSelect />}
            errors={errors}
            required
          />
          <div className="button-bar">
            <button className="button-primary" type="submit">
              {t('Save')}
            </button>
            <button className="button-muted" onClick={handleCancel}>
              {t('Cancel')}
            </button>
          </div>
        </form>
      </Modal>
    </LoggedIn>
  );
}
export default ProjectsAdd;
