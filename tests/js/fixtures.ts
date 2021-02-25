import {Task, Project} from 'app/types';

export function makeTask(props?: Partial<Task>): Task {
  const defaults: Task = {
    id: 1,
    project_section_id: null,
    title: '',
    body: '',
    completed: false,
    evening: false,
    due_on: null,
    day_order: 0,
    child_order: 0,
    subtask_count: 0,
    complete_subtask_count: 0,
    created: '',
    modified: '',
    project: makeProject(props?.project ?? {}),
  };
  return {
    ...defaults,
    ...props,
  };
}

export function makeProject(props?: Partial<Project>): Project {
  const defaults: Project = {
    id: 1,
    name: 'Work',
    slug: 'work',
    color: 0,
    favorite: false,
    archived: false,
    incomplete_task_count: 0,
    sections: [],
  };
  return {
    ...defaults,
    ...props,
  };
}
