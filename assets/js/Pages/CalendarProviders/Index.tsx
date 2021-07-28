import {Inertia} from '@inertiajs/inertia';

import {t} from 'app/locale';
import {deleteProvider} from 'app/actions/calendars';
import LoggedIn from 'app/layouts/loggedIn';
import Modal from 'app/components/modal';
import {CalendarProvider} from 'app/types';
import {InlineIcon} from 'app/components/icon';

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
            {t('Add Google Account')}
          </a>
        </div>
      </Modal>
    </LoggedIn>
  );
}
export default CalendarProvidersIndex;

type ProviderProps = {provider: CalendarProvider};

function CalendarProviderItem({provider, referer}: ProviderProps) {
  async function handleDelete(event: React.MouseEvent) {
    event.stopPropagation();
    await deleteProvider(provider);
  }

  return (
    <li>
      <span className="list-item-block">
        <ProviderIcon provider={provider} /> {provider.identifier}
      </span>
      <div className="button-bar-inline">
        <a href={`/calendars/${provider.id}/sources/add`} className="button-secondary">
          {t('Manage Calendars')}
        </a>
        <button onClick={handleDelete} className="button-danger">
          <InlineIcon icon="trash" />
          {t('Unlink')}
        </button>
      </div>
    </li>
  );
}

type ProviderIconProps = {
  provider: CalendarProvider;
};
function ProviderIcon({provider}: ProviderIconProps) {
  if (provider.kind === 'google') {
    return (
      <img
        src="/img/google-calendar-logo.svg"
        alt="Google Calendar logo"
        width="30"
        height="30"
      />
    );
  }
  return <InlineIcon icon="calendar" />;
}
