import React, {useState} from 'react';
import axios from 'axios';
import {Inertia} from '@inertiajs/inertia';
import {MenuItem} from '@reach/menu-button';

import {t} from 'app/locale';
import {Project, ProjectSection, ValidationErrors} from 'app/types';
import {deleteSection} from 'app/actions/projects';

import ContextMenu from './contextMenu';
import SectionQuickForm from './sectionQuickForm';
import {InlineIcon} from './icon';
import SortableItem from './sortableItem';

type SectionProps = React.PropsWithChildren<{
  section: ProjectSection;
  project: Project;
}>;

function SectionContainer({children, project, section}: SectionProps) {
  const [editing, setEditing] = useState(false);
  const [errors, setErrors] = useState<ValidationErrors>({});

  const editUrl = `/projects/${project.slug}/sections/${section.id}/edit`;

  async function handleDelete() {
    await deleteSection(project, section);
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
    <SortableItem id={`s:${section.id}`} tag="div">
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
    </SortableItem>
  );
}

export default SectionContainer;
