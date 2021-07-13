import {Inertia} from '@inertiajs/inertia';

import {t} from 'app/locale';
import LoggedIn from 'app/layouts/loggedIn';
import Modal from 'app/components/modal';
import {CalendarProvider} from 'app/types';
import {InlineIcon} from '@iconify/react';

type Props = {
  calendarProviders: CalendarProvider[];
  referer: string;
};

function CalendarProvidersIndex({referer, calendarProviders}: Props) {
  function handleClose() {
    Inertia.visit(referer);
  }

  const title = t('Linked Calendars');
  return (
    <LoggedIn title={title}>
      <Modal onClose={handleClose} label={title}>
        <h2>{title}</h2>
        <p>
          {t(
            'Events from linked calendars will be displayed in "today" and "upcoming" views.'
          )}
        </p>
        <ul className="list-items">
          {calendarProviders.map(calendarProvider => {
            return (
              <CalendarProviderItem
                key={calendarProvider.id}
                provider={calendarProvider}
              />
            );
          })}
        </ul>
        <div className="button-bar">
          <a className="button-primary" href="/auth/google/authorize">
            <InlineIcon icon="plus" />
            {t('Add account')}
          </a>
        </div>
      </Modal>
    </LoggedIn>
  );
}
export default CalendarProvidersIndex;

type ProviderProps = {
  provider: CalendarProvider;
};
function CalendarProviderItem({provider}: ProviderProps) {
  return (
    <li>
      {provider.kind} - {provider.identifier}
      <div className="button-bar-inline">
        <a href={`/calendars/${provider.id}/sources/add`} className="button-bare">
          {t('Manage Calendars')}
        </a>
      </div>
    </li>
  );
}
