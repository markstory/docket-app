export type FlashMessage = {
  message: string;
  key: string;
  element: string;
  params: Record<string, any>;
};

export interface ProjectSection {
  id: number;
  name: string;
}

/**
 * Project shape embedded on tasks.
 */
export interface TaskProject {
  id: number;
  name: string;
  slug: string;
  color: number;
}

/**
 * Detailed project with all attributes.
 */
export interface Project extends TaskProject {
  favorite: boolean;
  archived: boolean;
  incomplete_task_count: number;
  sections: ProjectSection[];
}

export interface Task {
  id: number;
  section_id: null | number;
  title: string;
  body: string;
  due_on: null | string;
  day_order: number;
  child_order: number;
  completed: boolean;
  evening: boolean;
  subtask_count: number;
  complete_subtask_count: number;
  created: string;
  modified: string;
  project: TaskProject;
}

export type Subtask = {
  id: number;
  title: string;
  body: string;
  completed: boolean;
};

export interface TaskDetailed extends Task {
  subtasks: Subtask[];
}

export type User = {
  id: number;
  name: string;
  email: string;
  unverified_email: string;
  avatar_hash: string;
  timezone: string;
  created: string;
  modified: string;
};

export type ValidationErrors = Record<string, string>;
