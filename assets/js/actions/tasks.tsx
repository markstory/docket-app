import axios, {AxiosResponse} from 'axios';
import {Inertia} from '@inertiajs/inertia';

import {DefaultTaskValues, Task} from 'app/types';
import {UpdaterCallback, UpdateData} from 'app/components/taskGroupedSorter';

export function createTask(data: FormData | Task): Promise<boolean> {
  const promise = new Promise<boolean>((resolve, reject) => {
    Inertia.post('/tasks/add', data, {
      onSuccess: () => {
        resolve(true);
      },
      onError: errors => {
        reject(errors);
      },
      preserveScroll: true,
    });
  });

  return promise;
}

export function makeTaskFromDefaults(defaults: DefaultTaskValues | undefined): Task {
  const task: Task = {
    id: -1,
    section_id: null,
    title: '',
    body: '',
    due_on: null,
    completed: false,
    evening: false,
    day_order: 0,
    child_order: 0,
    created: '',
    modified: '',
    complete_subtask_count: 0,
    subtask_count: 0,
    project: {
      id: defaults?.project_id ? Number(defaults.project_id) : 0,
      name: '',
      slug: '',
      color: 0,
    },
    ...defaults,
  };
  if (task.due_on === undefined) {
    task.due_on = null;
  }

  return task;
}

export function updateTask(
  task: Task,
  data: FormData | Partial<Record<string, string | number | null | boolean>>
): Promise<AxiosResponse<undefined>> {
  return axios.post(`/tasks/${task.id}/edit`, data);
}

export const sortUpdater: UpdaterCallback = (
  task: Task,
  newIndex: number,
  destinationKey: string
): UpdateData => {
  const data: UpdateData = {
    day_order: newIndex,
  };

  let isEvening = false;
  let newDate = destinationKey;
  if (newDate.includes('evening:')) {
    isEvening = true;
    newDate = newDate.substring(8);
  }
  if (isEvening !== task.evening || isEvening) {
    data.evening = isEvening;
  }
  if (newDate !== task.due_on) {
    data.due_on = newDate;
  }
  return data;
};
