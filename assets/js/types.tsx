export type FlashMessage = {
  message: string;
  key: string;
  element: string;
  params: Record<string, any>;
};

export type Project = {
  id: number;
  name: string;
  slug: string;
  color: string;
  favorite: boolean;
  archived: boolean;
  incomplete_task_count: number;
};

export type Task = {
  id: number;
  title: string;
  body: string;
  due_on: null | string;
  day_order: number;
  child_order: number;
  completed: boolean;
  subtask_count: number;
  complete_subtask_count: number;
  created: string;
  modified: string;
  project: Project;
};

export type Subtask = {
  id: number;
  title: string;
  body: string;
  completed: boolean;
};

export type TaskDetailed = Task & {
  subtasks: Subtask[];
};

export type User = {
  id: number;
  email: string;
  created: string;
  modified: string;
};

export type ValidationErrors = Record<string, string>;
