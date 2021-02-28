import {Inertia} from '@inertiajs/inertia';

import {TaskProject, ProjectSection} from 'app/types';
import {confirm} from 'app/components/confirm';

export function archiveProject(project: TaskProject) {
  return Inertia.post(`/projects/${project.slug}/archive`);
}

export function unarchiveProject(project: TaskProject) {
  return Inertia.post(`/projects/${project.slug}/unarchive`);
}

export async function deleteProject(project: TaskProject) {
  if (
    await confirm(
      'Are you sure?',
      'This will delete all the tasks in this project as well.'
    )
  ) {
    return Inertia.post(`/projects/${project.slug}/delete`);
  }
}

export async function deleteSection(project: TaskProject, section: ProjectSection) {
  if (
    await confirm(
      'Are you sure?',
      'All tasks will be moved out of the section, but remain in the project.'
    )
  ) {
    return Inertia.post(`/projects/${project.slug}/sections/${section.id}/delete`);
  }
}
