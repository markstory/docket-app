import React, {useState} from 'react';
import {SortableContext, verticalListSortingStrategy} from '@dnd-kit/sortable';
import {InertiaLink} from '@inertiajs/inertia-react';

import {t} from 'app/locale';
import {Project, ProjectSection, Task} from 'app/types';
import {Icon} from 'app/components/icon';
import LoggedIn from 'app/layouts/loggedIn';
import ProjectMenu from 'app/components/projectMenu';
import TaskGroup from 'app/components/taskGroup';
import TaskList from 'app/components/taskList';
import SectionQuickForm from 'app/components/sectionQuickForm';
import TaskGroupedSorter, {
  GroupedItems,
  UpdaterCallback,
} from 'app/components/taskGroupedSorter';

const ROOT = '_root_';

function grouper(sections: ProjectSection[]) {
  return function (items: Task[]): GroupedItems {
    const sectionTable = items.reduce<Record<string, Task[]>>((acc, task) => {
      const key = task.section_id === null ? ROOT : String(task.section_id);
      if (!acc[key]) {
        acc[key] = [];
      }
      acc[key].push(task);
      return acc;
    }, {});

    return [
      {
        key: ROOT,
        items: sectionTable[ROOT] ?? [],
        ids: (sectionTable[ROOT] ?? []).map(task => String(task.id)),
      },
      ...sections.map(section => {
        return {
          key: String(section.id),
          items: sectionTable[section.id] ?? [],
          ids: (sectionTable[section.id] ?? []).map(task => String(task.id)),
        };
      }),
    ];
  };
}

const updater: UpdaterCallback = (_task, newIndex, destinationKey) => {
  return {
    section_id: destinationKey === ROOT ? null : destinationKey,
    child_order: newIndex,
  };
};

type Props = {
  project: Project;
  tasks: Task[];
  completed?: Task[];
};

export default function ProjectsView({completed, project, tasks}: Props): JSX.Element {
  const [showAddSection, setShowAddSection] = useState(false);
  function handleCancelSection() {
    setShowAddSection(false);
  }

  const sectionMap = project.sections.reduce<Record<string, ProjectSection>>(
    (acc, section) => {
      acc[section.id] = section;
      return acc;
    },
    {}
  );
  return (
    <LoggedIn title={t('{project} Project', {project: project.name})}>
      <div className="project-view">
        <div className="heading" data-archived={project.archived}>
          <h1>
            {project.archived && <Icon icon="archive" />}
            {project.name}
          </h1>

          <ProjectMenu
            project={project}
            onAddSection={() => setShowAddSection(true)}
            showDetailed
          />
        </div>

        <div className="attributes">
          {project.archived && <span className="archived">{t('Archived')}</span>}
        </div>
        <TaskGroupedSorter
          tasks={tasks}
          grouper={grouper(project.sections)}
          updater={updater}
          showDueOn
        >
          {({groupedItems, activeTask}) => {
            return (
              <React.Fragment>
                {groupedItems.map(({key, ids, items}) => {
                  if (key === ROOT) {
                    return (
                      <SortableContext
                        key={key}
                        items={ids}
                        strategy={verticalListSortingStrategy}
                      >
                        <TaskGroup
                          dropId={ROOT}
                          activeTask={activeTask}
                          tasks={items}
                          defaultProjectId={project.id}
                          showAdd={!project.archived}
                          showDueOn
                        />
                      </SortableContext>
                    );
                  }
                  const section = sectionMap[key];
                  return (
                    <SectionControls key={key} section={section}>
                      <SortableContext items={ids} strategy={verticalListSortingStrategy}>
                        <TaskGroup
                          dropId={key}
                          activeTask={activeTask}
                          tasks={items}
                          defaultProjectId={project.id}
                          showAdd={!project.archived}
                          showDueOn
                        />
                      </SortableContext>
                    </SectionControls>
                  );
                })}
              </React.Fragment>
            );
          }}
        </TaskGroupedSorter>
        {showAddSection && (
          <SectionQuickForm project={project} onCancel={handleCancelSection} />
        )}
        {completed && (
          <React.Fragment>
            <TaskList title={t('Completed')} tasks={completed} showDueOn />
            <div className="button-bar">
              <InertiaLink
                className="button button-muted"
                href={`/projects/${project.slug}`}
              >
                {t('Hide completed tasks')}
              </InertiaLink>
            </div>
          </React.Fragment>
        )}
      </div>
    </LoggedIn>
  );
}

type SectionProps = React.PropsWithChildren<{
  section: ProjectSection;
}>;

function SectionControls({children, section}: SectionProps) {
  return (
    <div className="section-controls" data-testid="section">
      <h3>{section.name}</h3>
      {children}
    </div>
  );
}
