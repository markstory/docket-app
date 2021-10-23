import {Fragment, useState} from 'react';
import {createPortal} from 'react-dom';
import {DragOverlay} from '@dnd-kit/core';
import {InertiaLink} from '@inertiajs/inertia-react';

import {t} from 'app/locale';
import {Project, Task} from 'app/types';
import {Icon} from 'app/components/icon';
import LoggedIn from 'app/layouts/loggedIn';
import ProjectMenu from 'app/components/projectMenu';
import DragHandle from 'app/components/dragHandle';
import SectionAddForm from 'app/components/sectionAddForm';
import SectionContainer from 'app/components/sectionContainer';
import ProjectSectionSorter from 'app/components/projectSectionSorter';
import ProjectRenameForm from 'app/components/projectRenameForm';
import TaskGroup from 'app/components/taskGroup';
import TaskList from 'app/components/taskList';
import TaskRow from 'app/components/taskRow';
import useKeyboardListNav from 'app/hooks/useKeyboardListNav';

type Props = {
  project: Project;
  tasks: Task[];
  completed?: Task[];
};

export default function ProjectsView({completed, project, tasks}: Props): JSX.Element {
  const [showAddSection, setShowAddSection] = useState(false);
  const [editingName, setEditingName] = useState(false);

  function handleCancelSection() {
    setShowAddSection(false);
  }

  function handleCancelRename() {
    setEditingName(false);
  }
  const [focusedIndex] = useKeyboardListNav(tasks.length);
  let focused: null | Task = null;
  if (focusedIndex >= 0 && tasks[focusedIndex] !== undefined) {
    focused = tasks[focusedIndex];
  }

  return (
    <LoggedIn title={t('{project} Project', {project: project.name})}>
      <div className="project-view">
        <div className="heading" data-archived={project.archived}>
          {editingName ? (
            <ProjectRenameForm project={project} onCancel={handleCancelRename} />
          ) : (
            <h1 className="heading-icon editable" onClick={() => setEditingName(true)}>
              {project.archived && <Icon icon="archive" />}
              {project.name}
            </h1>
          )}

          <ProjectMenu
            project={project}
            onAddSection={() => setShowAddSection(true)}
            showDetailed
          />
        </div>

        <ProjectSectionSorter project={project} tasks={tasks}>
          {({groups, activeTask, activeSection}) => {
            const elements = groups.map(({key, section, tasks}) => {
              if (section === undefined) {
                return (
                  <TaskGroup
                    key={key}
                    dropId={key}
                    activeTask={activeTask}
                    tasks={tasks}
                    focusedTask={focused}
                    defaultTaskValues={{project_id: project.id}}
                    showAdd={!project.archived}
                    showDueOn
                  />
                );
              }
              return (
                <SectionContainer
                  key={key}
                  id={key}
                  active={activeSection}
                  project={project}
                  section={section}
                >
                  <TaskGroup
                    dropId={key}
                    activeTask={activeTask}
                    tasks={tasks}
                    defaultTaskValues={{section_id: section.id, project_id: project.id}}
                    showAdd={!project.archived}
                    focusedTask={focused}
                    showDueOn
                  />
                </SectionContainer>
              );
            });

            return (
              <Fragment>
                {elements}
                {createPortal(
                  <DragOverlay>
                    {activeTask ? (
                      <div className="dnd-item dnd-item-dragging">
                        <DragHandle />
                        <TaskRow task={activeTask} showDueOn={true} />
                      </div>
                    ) : activeSection ? (
                      <div className="dnd-item dnd-item-dragging">
                        <DragHandle />
                        <h3>{activeSection.name}</h3>
                      </div>
                    ) : null}
                  </DragOverlay>,
                  document.body
                )}
              </Fragment>
            );
          }}
        </ProjectSectionSorter>
        {showAddSection && (
          <SectionAddForm project={project} onCancel={handleCancelSection} />
        )}
        {completed && (
          <Fragment>
            <TaskList title={t('Completed')} tasks={completed} showDueOn />
            <div className="button-bar">
              <InertiaLink
                className="button button-muted"
                href={`/projects/${project.slug}`}
              >
                {t('Hide completed tasks')}
              </InertiaLink>
            </div>
          </Fragment>
        )}
      </div>
    </LoggedIn>
  );
}
