import React from 'react';
import {Inertia} from '@inertiajs/inertia';
import {InertiaLink} from '@inertiajs/inertia-react';

import {Project} from 'app/types';
import ContextMenu from 'app/components/contextMenu';
import DragContainer from 'app/components/dragContainer';
import ProjectBadge from 'app/components/projectBadge';
import ProjectSorter from 'app/components/projectSorter';
import NewProjectModal from 'app/components/modals/newProjectModal';

function ProjectFilter() {
  const [showModal, setShowModal] = React.useState(false);
  const showNewProject = () => {
    setShowModal(true);
  };

  function handleArchive(project: Project) {
    Inertia.post(`/projects/${project.slug}/archive`);
  }

  return (
    <div className="project-filter">
      <ul>
        <li>
          <InertiaLink href="/todos/today">Today</InertiaLink>
        </li>
        <li>
          <InertiaLink href="/todos/upcoming">Upcoming</InertiaLink>
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
                <div className="project-item" key={project.slug}>
                  <InertiaLink
                    key={project.slug}
                    href={`/projects/${project.slug}/todos`}
                  >
                    <ProjectBadge project={project} />
                  </InertiaLink>
                  <ContextMenu>
                    <li>Edit Project</li>
                    <li>
                      <button
                        className="button-default"
                        onClick={() => handleArchive(project)}
                      >
                        Archive Project
                      </button>
                    </li>
                    <li>Delete Project</li>
                  </ContextMenu>
                </div>
              )}
              onChange={handleOrderChange}
            />
          )}
        </ProjectSorter>
      </ul>
      <button className="button-secondary" onClick={showNewProject}>
        Create Project
      </button>
      <a href="/projects/archived">Archived Projects</a>
      <NewProjectModal showModal={showModal} onClose={() => setShowModal(false)} />
    </div>
  );
}

export default ProjectFilter;
