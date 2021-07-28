import {Fragment} from 'react';
import {Inertia} from '@inertiajs/inertia';

import {t} from 'app/locale';
import {deleteSource} from 'app/actions/calendars';
import {InlineIcon} from 'app/components/icon';
import ColorSelect from 'app/components/colorSelect';
import Modal from 'app/components/modal';
import {PROJECT_COLORS} from 'app/constants';
import LoggedIn from 'app/layouts/loggedIn';
import {CalendarProviderDetailed, CalendarSource} from 'app/types';

type Props = {
  calendarProvider: CalendarProviderDetailed;
  unlinked: CalendarSource[];
};

function CalendarSourcesAdd({calendarProvider, unlinked}: Props) {
  function handleClose() {
    history.back();
  }

  const title = t('Linked Calendars');
  return (
    <LoggedIn title={title}>
      <Modal onClose={handleClose} label={title}>
        <h2>{t('Synced Calendars for {name}', {name: calendarProvider.display_name})}</h2>
        <p>
          {t(
            `The following calendars are synced into Docket.
             You should see calendar events in your 'today' and 'upcoming' views.`
          )}
        </p>
        <ul className="list-items">
          {calendarProvider.calendar_sources.map(source => {
            return (
              <CalendarSourceItem
                key={`l:${source.provider_id}`}
                source={source}
                provider={calendarProvider}
                mode="edit"
              />
            );
          })}
          {calendarProvider.calendar_sources.length == 0 && (
            <li className="list-item-empty">
              <InlineIcon icon="alert" width="large" />
              {t('You have no synchronized calendars in this provider. Add one below.')}
            </li>
          )}
        </ul>
        <h2>{t('Unwatched Calendars')}</h2>
        <ul className="list-items">
          {unlinked.map(source => {
            return (
              <CalendarSourceItem
                key={source.provider_id}
                source={source}
                provider={calendarProvider}
                mode="create"
              />
            );
          })}
        </ul>
      </Modal>
    </LoggedIn>
  );
}
export default CalendarSourcesAdd;

type ItemProps = {
  source: CalendarSource;
  provider: CalendarProvider;
  mode: 'create' | 'edit';
};

function CalendarSourceItem({source, mode, provider}: ItemProps) {
  function handleSync(event: React.MouseEvent) {
    event.stopPropagation();
    Inertia.post(`/calendars/${provider.id}/sources/${source.id}/sync`);
  }

  async function handleDelete(event: React.MouseEvent) {
    event.stopPropagation();
    return deleteSource(provider, source);
  }

  function handleCreate(event: React.MouseEvent) {
    event.stopPropagation();
    const data = {
      calendar_provider_id: provider.id,
      provider_id: source.provider_id,
      name: source.name,
      color: source.color,
    };
    Inertia.post(`/calendars/${provider.id}/sources/add`, data);
  }

  function handleChange(color: number | string) {
    const data = {
      color,
    };
    Inertia.post(`/calendars/${provider.id}/sources/${source.id}/edit`, data);
  }

  return (
    <li>
      <span className="list-item-block">
        {source.id ? (
          <ColorSelect value={source.color} onChange={handleChange} hideLabel />
        ) : (
          <InlineIcon icon="dot" color={PROJECT_COLORS[15].code} />
        )}
        {source.name}
      </span>
      <div className="button-bar-inline">
        {mode === 'edit' && (
          <Fragment>
            <button className="button-secondary" onClick={handleSync}>
              <InlineIcon icon="sync" />
              {t('Refresh')}
            </button>
            <button className="button-danger" onClick={handleDelete}>
              <InlineIcon icon="trash" />
              {t('Unlink')}
            </button>
          </Fragment>
        )}
        {mode === 'create' && (
          <button className="button-primary" onClick={handleCreate}>
            <InlineIcon icon="plus" />
            {t('Add')}
          </button>
        )}
      </div>
    </li>
  );
}
