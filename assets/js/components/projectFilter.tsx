import React from 'react';
import {InertiaLink} from '@inertiajs/inertia-react';
import {Droppable, Draggable} from 'react-beautiful-dnd';
import classnames from 'classnames';

import {t} from 'app/locale';
import ProjectItem from 'app/components/projectItem';
import ProjectSorter from 'app/components/projectSorter';
import {Icon, InlineIcon} from './icon';

function ProjectFilter() {
  return (
    <div className="project-filter">
      <ul className="links">
        <li>
          <InertiaLink href="/tasks/today">
            <InlineIcon icon="clippy" className="today" />
            {t('Today')}
          </InertiaLink>
        </li>
        <li>
          <InertiaLink href="/tasks/upcoming">
            <InlineIcon icon="calendar" className="upcoming" />
            {t('Upcoming')}
          </InertiaLink>
        </li>
      </ul>
      <h3>{t('Projects')}</h3>
      <ProjectSorter>
        {({projects}) => (
          <Droppable droppableId="projects" type="project">
            {(provided, snapshot) => {
              const className = classnames('dnd-dropper-left-offset', {
                'dnd-dropper-active': snapshot.isDraggingOver,
              });
              return (
                <ul
                  ref={provided.innerRef}
                  className={className}
                  {...provided.droppableProps}
                >
                  {projects.map((project, index) => (
                    <Draggable key={project.id} draggableId={project.slug} index={index}>
                      {(provided: any, snapshot: any) => {
                        let className = 'dnd-item';
                        if (snapshot.isDragging) {
                          className += ' dnd-item-dragging';
                        }
                        return (
                          <li
                            ref={provided.innerRef}
                            className={className}
                            {...provided.draggableProps}
                          >
                            <button
                              className="dnd-handle"
                              aria-label="Drag to reorder"
                              {...provided.dragHandleProps}
                            >
                              <Icon icon="grabber" width="large" />
                            </button>
                            <ProjectItem key={project.slug} project={project} />
                          </li>
                        );
                      }}
                    </Draggable>
                  ))}
                  {provided.placeholder}
                </ul>
              );
            }}
          </Droppable>
        )}
      </ProjectSorter>
      <div className="button-bar-vertical">
        <InertiaLink className="button-sidebar-action-primary " href="/projects/add">
          <InlineIcon icon="plus" />
          {t('New Project')}
        </InertiaLink>
        <InertiaLink className="button-sidebar-action" href="/projects/archived">
          {t('Archived Projects')}
        </InertiaLink>
      </div>
    </div>
  );
}

export default ProjectFilter;
