import axios, {AxiosResponse} from 'axios';

import {Task} from 'app/types';

export function updateTask(
  task: Task,
  data: FormData | Partial<Record<keyof Task, string | number | null | boolean>>
): Promise<AxiosResponse<undefined>> {
  return axios.post(`/tasks/${task.id}/edit`, data);
}
