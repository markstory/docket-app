import React from 'react';
import {InertiaLink} from '@inertiajs/inertia-react';

import {Project} from 'app/types';
import DragContainer from 'app/components/dragContainer';
import ProjectItem from 'app/components/projectItem';
import ProjectSorter from 'app/components/projectSorter';
import NewProjectModal from 'app/components/modals/newProjectModal';
import {InlineIcon} from './icon';

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
      <ul className="drag-container-left-offset">
        <ProjectSorter>
          {({projects, handleOrderChange}) => (
            <DragContainer
              itemElement={<li />}
              items={projects}
              renderItem={(project: Project) => (
                <ProjectItem key={project.slug} project={project} />
              )}
              onChange={handleOrderChange}
            />
          )}
        </ProjectSorter>
      </ul>
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
