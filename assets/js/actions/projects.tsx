import {Inertia} from '@inertiajs/inertia';

import {Project} from 'app/types';
import {confirm} from 'app/components/confirm';

export function archiveProject(project: Project) {
  return Inertia.post(`/projects/${project.slug}/archive`);
}

export function unarchiveProject(project: Project) {
  return Inertia.post(`/projects/${project.slug}/unarchive`);
}

export async function deleteProject(project: Project) {
  if (
    await confirm(
      'Are you sure?',
      'This will destroy all the todos this project contains'
    )
  ) {
    return Inertia.post(`/projects/${project.slug}/delete`);
  }
}
