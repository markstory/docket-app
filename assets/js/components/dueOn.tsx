import React from 'react';
import classnames from 'classnames';

import {InlineIcon} from 'app/components/icon';
import {t} from 'app/locale';
import {formatCompactDate, getDiff} from 'app/utils/dates';

type Props = {
  value: null | undefined | string | Date;
  showNull?: boolean;
};

function DueOn({value, showNull}: Props): JSX.Element | null {
  if (!value) {
    return showNull ? <React.Fragment>{t('No Due Date')}</React.Fragment> : null;
  }
  const diff = getDiff(value);
  const formatted = formatCompactDate(value);
  const className = classnames('due-on', {
    overdue: diff < 0,
    today: diff >= 0 && diff < 1,
    tomorrow: diff >= 1 && diff < 2,
    week: diff >= 2 && diff < 8,
  });
  return (
    <time className={className} dateTime={formatted}>
      {' '}
      <InlineIcon icon="calendar" width="xsmall" />
      {formatted}
    </time>
  );
}

export default DueOn;
