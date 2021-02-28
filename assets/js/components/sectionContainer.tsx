import React, {useState} from 'react';
import axios from 'axios';
import {Inertia} from '@inertiajs/inertia';
import {MenuItem} from '@reach/menu-button';

import {t} from 'app/locale';
import {Project, ProjectSection, ValidationErrors} from 'app/types';

import ContextMenu from './contextMenu';
import SectionQuickForm from './sectionQuickForm';
import {InlineIcon} from './icon';

type SectionProps = React.PropsWithChildren<{
  section: ProjectSection;
  project: Project;
}>;

function SectionContainer({children, project, section}: SectionProps) {
  const [editing, setEditing] = useState(false);
  const [errors, setErrors] = useState<ValidationErrors>({});

  const editUrl = `/projects/${project.slug}/sections/${section.id}/edit`;

  function handleDelete() {
    // TODO finish.
  }

  function handleSubmit(formData: FormData) {
    // Do an XHR request so we can handle validation errors
    // inside the modal.
    axios
      .post(editUrl, formData)
      .then(() => {
        setEditing(false);
        Inertia.reload();
      })
      .catch(error => {
        if (error.response) {
          setErrors(error.response.data.errors);
        }
      });
  }

  return (
    <div className="section-container" data-testid="section">
      <div className="controls">
        {editing ? (
          <SectionQuickForm
            url={editUrl}
            section={section}
            onSubmit={handleSubmit}
            onCancel={() => setEditing(false)}
            errors={errors}
          />
        ) : (
          <React.Fragment>
            <h3 className="heading">{section.name}</h3>
            <ContextMenu tooltip={t('Section actions')}>
              <MenuItem onSelect={() => setEditing(true)} className="edit">
                <InlineIcon icon="pencil" />
                {t('Edit Section')}
              </MenuItem>
              <MenuItem onSelect={handleDelete} className="delete">
                <InlineIcon icon="trash" />
                {t('Delete Section')}
              </MenuItem>
            </ContextMenu>
          </React.Fragment>
        )}
      </div>
      {children}
    </div>
  );
}

export default SectionContainer;
