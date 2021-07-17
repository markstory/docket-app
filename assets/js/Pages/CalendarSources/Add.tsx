import {Fragment} from 'react';
import {Inertia} from '@inertiajs/inertia';

import {t} from 'app/locale';
import {confirm} from 'app/components/confirm';
import {InlineIcon} from 'app/components/icon';
import ColorSelect from 'app/components/colorSelect';
import Modal from 'app/components/modal';
import {PROJECT_COLORS} from 'app/constants';
import LoggedIn from 'app/layouts/loggedIn';
import {CalendarProviderDetailed, CalendarSource} from 'app/types';

type Props = {
  calendarProvider: CalendarProviderDetailed;
  unlinked: CalendarSource[];
  referer: string;
};

function CalendarSourcesAdd({calendarProvider, referer, unlinked}: Props) {
  function handleClose() {
    Inertia.visit(referer);
  }

  const title = t('Linked Calendars');
  return (
    <LoggedIn title={title}>
      <Modal onClose={handleClose} label={title}>
        <h2>{t('{name} Synced Calendars', {name: calendarProvider.identifier})}</h2>
        <p>
          {t(
            `The following calendars are synced into docket periodically.
             You should see calendar events in your 'today' and 'upcoming' views.`
          )}
        </p>
        <ul className="list-items">
          {calendarProvider.calendar_sources.map(source => {
            return (
              <CalendarSourceItem
                key={`u:${source.name}`}
                source={source}
                providerId={calendarProvider.id}
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
                key={source.id}
                source={source}
                providerId={calendarProvider.id}
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
  providerId: number;
  mode: 'create' | 'edit';
};

function CalendarSourceItem({source, mode, providerId}: ItemProps) {
  const color = PROJECT_COLORS[source.color].code ?? PROJECT_COLORS[0].code;

  function handleSync(event: React.MouseEvent) {
    event.stopPropagation();
    Inertia.post(`/calendars/${providerId}/sources/${source.id}/sync`);
  }

  async function handleDelete(event: React.MouseEvent) {
    event.stopPropagation();
    if (
      await confirm(
        'Are you sure?',
        'This will stop automatic updates for this calendar.'
      )
    ) {
      return Inertia.post(`/calendars/${providerId}/sources/${source.id}/delete`);
    }
  }

  function handleCreate(event: React.MouseEvent) {
    event.stopPropagation();
    const data = {
      calendar_provider_id: providerId,
      provider_id: source.provider_id,
      name: source.name,
      color: source.color,
    };
    Inertia.post(`/calendars/${providerId}/sources/add`, data);
  }

  function handleChange(color: number | string) {
    const data = {
      color,
    };
    Inertia.post(`/calendars/${providerId}/sources/${source.id}/edit`, data);
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
