import {Inertia} from '@inertiajs/inertia';

import {t} from 'app/locale';
import {deleteProvider} from 'app/actions/calendars';
import LoggedIn from 'app/layouts/loggedIn';
import Modal from 'app/components/modal';
import OverflowActionBar from 'app/components/overflowActionBar';
import {CalendarProviderDetailed, CalendarSource} from 'app/types';
import {InlineIcon} from 'app/components/icon';

import CalendarSources from './calendarSources';

type Props = {
  calendarProviders: CalendarProviderDetailed[];
  activeProvider: CalendarProviderDetailed;
  referer: string;
  unlinked: null | CalendarSource[];
};

function CalendarProvidersIndex({
  activeProvider,
  referer,
  calendarProviders,
  unlinked,
}: Props) {
  function handleClose() {
    Inertia.visit(referer);
  }

  const title = t('Synced Calendars');
  return (
    <LoggedIn title={title}>
      <Modal onClose={handleClose} label={title} className="calendar-add">
        <h2>{title}</h2>
        <p>
          {t(
            'Events from linked calendars will be displayed in "today" and "upcoming" views.'
          )}
        </p>
        <div className="button-bar">
          <a className="button-primary" href="/auth/google/authorize">
            <InlineIcon icon="plus" />
            {t('Add Google Account')}
          </a>
        </div>

        <h3>{t('Connected Calendar Accounts')}</h3>
        <ul className="list-items">
          {calendarProviders.map(calendarProvider => {
            return (
              <CalendarProviderItem
                key={calendarProvider.id}
                isActive={activeProvider && calendarProvider.id === activeProvider.id}
                provider={calendarProvider}
                unlinked={activeProvider ? unlinked : null}
              />
            );
          })}
        </ul>
      </Modal>
    </LoggedIn>
  );
}
export default CalendarProvidersIndex;

type ProviderProps = {
  provider: CalendarProvider;
  isActive: boolean;
  unlinked: Props['unlinked'];
};

function CalendarProviderItem({isActive, provider, unlinked}: ProviderProps) {
  async function handleDelete() {
    await deleteProvider(provider);
  }

  function handleManage() {
    Inertia.visit(`/calendars/${provider.id}/sources/add`);
  }

  return (
    <li className="list-item-panel">
      <div className="list-item-panel-header">
        <span className="list-item-block">
          <ProviderIcon provider={provider} /> {provider.display_name}
        </span>
        <div className="list-item-block">
          <OverflowActionBar
            label={t('Calendar Actions')}
            foldWidth={700}
            items={[
              {
                buttonClass: 'button-danger',
                menuItemClass: 'delete',
                onSelect: handleDelete,
                label: t('Unlink'),
                icon: <InlineIcon icon="trash" />,
              },
            ]}
          />
        </div>
      </div>
      {unlinked && (
        <div className="list-item-panel-item">
          <CalendarSources calendarProvider={provider} unlinked={unlinked} />
        </div>
      )}
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
