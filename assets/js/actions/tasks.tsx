import axios, {AxiosResponse} from 'axios';

import {Task} from 'app/types';

export function updateTaskField(
  task: Task,
  field: keyof Task,
  value: string | number | null
): Promise<AxiosResponse<undefined>> {
  const data = {
    [field]: value,
  };

  return axios.post(`/tasks/${task.id}/edit`, data);
}

export function updateTask(
  task: Task,
  data: FormData | Record<keyof Task, string | number | null>
): Promise<AxiosResponse<undefined>> {
  return axios.post(`/tasks/${task.id}/edit`, data);
}
