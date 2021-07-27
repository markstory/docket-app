import {Inertia} from '@inertiajs/inertia';

import {confirm} from 'app/components/confirm';
import {CalendarProvider, CalendarSource} from 'app/types';

export async function deleteProvider(provider: CalendarProvider) {
  if (
    await confirm('Are you sure?', 'This will delete all synced calendars and events.')
  ) {
    return Inertia.post(`/calendars/${provider.id}/delete`);
  }
}

export async function deleteSource(provider: CalendarProvider, source: CalendarSource) {
  if (
    await confirm('Are you sure?', 'This will stop automatic updates for this calendar.')
  ) {
    return Inertia.post(`/calendars/${provider.id}/sources/${source.id}/delete`);
  }
}
