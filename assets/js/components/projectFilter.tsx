import React from 'react';
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
      <ul>
        <ProjectSorter>
          {({projects, handleOrderChange}) => (
            <DragContainer
              itemElement={<li />}
              items={projects}
              renderItem={(project: Project) => (
                <div key={project.slug}>
                  <InertiaLink
                    key={project.slug}
                    href={`/projects/${project.slug}/todos`}
                  >
                    <ProjectBadge project={project} />
                  </InertiaLink>
                  <ContextMenu>
                    <li>Edit Project</li>
                    <li>Archive Project</li>
                    <li>Delete Project</li>
                  </ContextMenu>
                </div>
              )}
              onChange={handleOrderChange}
            />
          )}
        </ProjectSorter>
      </ul>
      <button onClick={showNewProject}>Create Project</button>
      <NewProjectModal showModal={showModal} onClose={() => setShowModal(false)} />
    </div>
  );
}

export default ProjectFilter;
