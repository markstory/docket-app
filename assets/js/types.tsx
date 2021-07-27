export type FlashMessage = {
  message: string;
  key: string;
  element: string;
  params: Record<string, string | number | null>;
};

export interface ProjectSection {
  id: number;
  name: string;
}

export interface CalendarProvider {
  id: number;
  kind: string;
  identifier: string;
}

export interface CalendarProviderDetailed extends CalendarProvider {
  calendar_sources: CalendarSource[];
}

export interface CalendarSource {
  id: number;
  name: string;
  color: number;
  provider_id: string;
  last_sync: string;
  sync_token: string;
}

export interface CalendarSourceDetailed extends CalendarSource {
  calendar_provider: CalendarProvider;
}

export type CalendarItem =
  | {
      id: number;
      title: string;
      color: number;
      html_link: string;
      all_day: false;
      start_time: string;
      end_time: string;
    }
  | {
      id: number;
      title: string;
      color: number;
      html_link: string;
      all_day: true;
      end_date: string;
      start_date: string;
    };

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
  body: null | string;
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

export interface DefaultTaskValues {
  section_id?: null | number;
  title?: string;
  evening?: boolean;
  due_on?: null | string;
  project_id?: number;
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
  theme: string;
  created: string;
  modified: string;
};

export type ValidationErrors = Record<string, string>;
