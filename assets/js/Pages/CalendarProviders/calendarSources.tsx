import {Inertia} from '@inertiajs/inertia';

import {t} from 'app/locale';
import {deleteSource} from 'app/actions/calendars';
import {InlineIcon} from 'app/components/icon';
import ColorSelect from 'app/components/colorSelect';
import Modal from 'app/components/modal';
import OverflowActionBar from 'app/components/overflowActionBar';
import {PROJECT_COLORS} from 'app/constants';
import LoggedIn from 'app/layouts/loggedIn';
import {CalendarProviderDetailed, CalendarSource} from 'app/types';

type Props = {
  calendarProvider: CalendarProviderDetailed;
  unlinked: CalendarSource[];
};

function CalendarSources({calendarProvider, unlinked}: Props) {
  return (
    <ul className="list-items full-width">
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
  );
}
export default CalendarSources;

type ItemProps = {
  source: CalendarSource;
  provider: CalendarProviderDetailed;
  mode: 'create' | 'edit';
};

function CalendarSourceItem({source, mode, provider}: ItemProps) {
  function handleSync() {
    Inertia.post(`/calendars/${provider.id}/sources/${source.id}/sync`);
  }

  async function handleDelete() {
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
        <span className="calendar-name">
          {source.name}
        </span>
      </span>
      <div className="list-item-block">
        {mode === 'edit' && (
          <OverflowActionBar
            label={t('Calendar actions')}
            foldWidth={700}
            items={[
              {
                buttonClass: 'button-secondary',
                onSelect: handleSync,
                menuItemClass: 'complete',
                icon: <InlineIcon icon="sync" />,
                label: t('Refresh'),
              },
              {
                buttonClass: 'button-danger',
                menuItemClass: 'delete',
                onSelect: handleDelete,
                icon: <InlineIcon icon="trash" />,
                label: t('Unlink'),
              },
            ]}
          />
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
