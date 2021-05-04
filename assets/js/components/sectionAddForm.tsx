import {Inertia} from '@inertiajs/inertia';

import {Project} from 'app/types';
import SectionQuickForm from './sectionQuickForm';

type Props = {
  project: Project;
  onCancel: () => void;
};

function SectionAddForm({onCancel, project}: Props): JSX.Element {
  const url = `/projects/${project.slug}/sections`;

  function handleSubmit(formData: FormData) {
    Inertia.post(url, formData, {
      onSuccess: () => onCancel(),
    });
  }

  return <SectionQuickForm url={url} onSubmit={handleSubmit} onCancel={onCancel} />;
}

export default SectionAddForm;
