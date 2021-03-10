import React from 'react';

import {ProjectSection, ValidationErrors} from 'app/types';
import {t} from 'app/locale';
import FormError from './formError';

type Props = {
  url: string;
  section?: ProjectSection;
  errors?: ValidationErrors;
  onSubmit: (data: FormData) => void;
  onCancel?: () => void;
};

function SectionQuickForm({
  section,
  errors,
  onSubmit,
  onCancel,
  url,
}: Props): JSX.Element {
  function handleSubmit(event: React.FormEvent) {
    event.preventDefault();
    const form = event.target as HTMLFormElement;
    const formData = new FormData(form);
    onSubmit(formData);
  }

  return (
    <form
      className="section-quickform"
      method="post"
      action={url}
      onSubmit={handleSubmit}
    >
      <div className="title">
        <input
          type="text"
          name="name"
          defaultValue={section?.name}
          placeholder={t('Movies to watch')}
          autoFocus
          required
        />
        <FormError errors={errors} field="name" />
      </div>
      <div className="button-bar">
        <button type="submit" className="button-primary" data-testid="save-section">
          {t('Save')}
        </button>
        <button className="button-muted" onClick={onCancel}>
          {t('Cancel')}
        </button>
      </div>
    </form>
  );
}

export default SectionQuickForm;