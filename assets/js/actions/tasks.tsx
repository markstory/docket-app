import axios, {AxiosResponse} from 'axios';

import {Task} from 'app/types';
import {UpdaterCallback, UpdateData} from 'app/components/taskGroupedSorter';

export function updateTask(
  task: Task,
  data: FormData | Partial<Record<keyof Task, string | number | null | boolean>>
): Promise<AxiosResponse<undefined>> {
  return axios.post(`/tasks/${task.id}/edit`, data);
}

export const daySortUpdater: UpdaterCallback = (
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
  if (isEvening !== task.evening) {
    data.evening = isEvening;
  }
  if (newDate !== task.due_on) {
    data.due_on = newDate;
  }
  return data;
};
