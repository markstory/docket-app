import React from 'react';
import {InertiaLink} from '@inertiajs/inertia-react';

import ProjectBadge from 'app/components/projectBadge';
import ProjectsContext from 'app/components/projectsContext';
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
        <ProjectsContext.Consumer>
          {projects =>
            projects.map(project => (
              <li key={project.slug}>
                <InertiaLink href={`/projects/${project.slug}/todos`}>
                  <ProjectBadge project={project} />
                </InertiaLink>
              </li>
            ))
          }
        </ProjectsContext.Consumer>
        <li>
          <button onClick={showNewProject}>Create Project</button>
        </li>
      </ul>
      <NewProjectModal showModal={showModal} onClose={() => setShowModal(false)} />
    </div>
  );
}

export default ProjectFilter;
