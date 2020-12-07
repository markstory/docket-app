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
};

export type TodoItem = {
  id: number;
  title: string;
  body: string;
  due_on: null | string;
  completed: boolean;
  created: string;
  modified: string;
  project: Project;
};

export type TodoSubtask = {
  id: number;
  title: string;
  body: string;
};

export type TodoItemDetailed = TodoItem & {
  subtasks: TodoSubtask[];
};

export type User = {
  id: number;
  email: string;
  created: string;
  modified: string;
};

export type ValidationErrors = Record<string, string>;
