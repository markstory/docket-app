import classnames from 'classnames';

import {InlineIcon} from 'app/components/icon';
import {t} from 'app/locale';
import {Task} from 'app/types';
import {formatCompactDate, getDiff} from 'app/utils/dates';

type Props = {
  task: Task;
  showNull?: boolean;
};

function DueOn({task, showNull = false}: Props): JSX.Element | null {
  const value = task.due_on;
  if (!value) {
    return showNull ? <span className="due-on none">{t('No Due Date')}</span> : null;
  }
  const diff = getDiff(value);
  const thisEvening = diff >= 0 && diff < 1 && task.evening;

  const className = classnames('due-on', {
    overdue: diff < 0,
    today: diff >= 0 && diff < 1 && task.evening === false,
    evening: thisEvening,
    tomorrow: diff >= 1 && diff < 2,
    week: diff >= 2 && diff < 8,
  });
  const formatted = thisEvening ? t('This evening') : formatCompactDate(value);

  return (
    <time className={className} dateTime={formatted}>
      {task.evening ? (
        <InlineIcon icon="moon" />
      ) : (
        <InlineIcon icon="calendar" width="xsmall" />
      )}
      {formatted}
    </time>
  );
}

export default DueOn;
