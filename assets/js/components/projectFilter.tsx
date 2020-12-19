import React from 'react';
import {InertiaLink} from '@inertiajs/inertia-react';
import {Droppable, Draggable} from 'react-beautiful-dnd';

import ProjectItem from 'app/components/projectItem';
import ProjectSorter from 'app/components/projectSorter';
import NewProjectModal from 'app/components/modals/newProjectModal';
import {Icon, InlineIcon} from './icon';

function ProjectFilter() {
  const [showModal, setShowModal] = React.useState(false);
  const showNewProject = () => {
    setShowModal(true);
  };

  return (
    <div className="project-filter">
      <ul>
        <li>
          <InertiaLink href="/todos/today">
            <InlineIcon icon="clippy" />
            Today
          </InertiaLink>
        </li>
        <li>
          <InertiaLink href="/todos/upcoming">
            <InlineIcon icon="calendar" />
            Upcoming
          </InertiaLink>
        </li>
      </ul>
      <h3>Projects</h3>
      <ProjectSorter>
        {({projects}) => (
          <Droppable droppableId="projects" type="project">
            {provided => (
              <ul
                ref={provided.innerRef}
                className="dnd-dropper-left-offset"
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
            )}
          </Droppable>
        )}
      </ProjectSorter>
      <div className="button-bar-vertical">
        <button className="button-secondary" onClick={showNewProject}>
          <InlineIcon icon="plus" />
          Create Project
        </button>
        <InertiaLink className="button button-muted" href="/projects/archived">
          Archived Projects
        </InertiaLink>
      </div>
      <NewProjectModal showModal={showModal} onClose={() => setShowModal(false)} />
    </div>
  );
}

export default ProjectFilter;
