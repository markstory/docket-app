import { useState } from 'react';
import * as React from 'react';
import axios from 'axios';
import classnames from 'classnames';
import {useSortable} from '@dnd-kit/sortable';
import {CSS} from '@dnd-kit/utilities';
import {Inertia} from '@inertiajs/inertia';
import {MenuItem} from '@reach/menu-button';

import {t} from 'app/locale';
import {Project, ProjectSection, ValidationErrors} from 'app/types';
import {deleteSection} from 'app/actions/projects';

import ContextMenu from './contextMenu';
import DragHandle from './dragHandle';
import SectionQuickForm from './sectionQuickForm';
import {InlineIcon} from './icon';

type SectionProps = React.PropsWithChildren<{
  id: string;
  active?: ProjectSection;
  section: ProjectSection;
  project: Project;
}>;

function SectionContainer({active, children, id, project, section}: SectionProps) {
  const [editing, setEditing] = useState(false);
  const [errors, setErrors] = useState<ValidationErrors>({});

  const editUrl = `/projects/${project.slug}/sections/${section.id}/edit`;
  const activeId = active ? `s:${active.id}` : undefined;

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
  const {attributes, listeners, setNodeRef, transform, transition} = useSortable({
    id,
  });
  const style = {
    transform: CSS.Transform.toString(transform),
    transition: transition ?? undefined,
  };
  const className = classnames('section-container', {
    'dnd-ghost': id === activeId,
  });

  return (
    <div className={className} data-testid="section" ref={setNodeRef} style={style}>
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
            <h3 className="heading">
              <DragHandle attributes={attributes} listeners={listeners} />
              <span className="editable" onClick={() => setEditing(true)}>
                {section.name}
              </span>
            </h3>
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
