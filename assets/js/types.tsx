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
