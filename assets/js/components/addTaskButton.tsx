import {useContext, useRef} from 'react';
import {t} from 'app/locale';
import {DefaultTaskValues} from 'app/types';

import {DefaultTaskValuesContext} from 'app/providers/defaultTaskValues';
import {InlineIcon} from './icon';
import Tooltip from './tooltip';

type Props = {
  defaultValues: DefaultTaskValues;
};

function AddTaskButton({defaultValues}: Props) {
  const ref = useRef<HTMLButtonElement>(null);
  const [_, updateDefaultValues] = useContext(DefaultTaskValuesContext);

  function handleClick(e: React.MouseEvent) {
    e.preventDefault();
    updateDefaultValues({
      type: 'reset',
      data: defaultValues,
    });
    if (!ref.current) {
      return;
    }

    // trigger the keyboard shortcut.
    const eventOptions = {
      key: 'c',
      bubbles: true,
      cancelable: true,
    };
    let event = new KeyboardEvent('keydown', eventOptions);
    ref.current.dispatchEvent(event);
  }

  return (
    <Tooltip label={t('Add task')}>
      <button
        className="button-icon-primary"
        data-test-id="add-task"
        onClick={handleClick}
        ref={ref}
      >
        <InlineIcon icon="plus" />
      </button>
    </Tooltip>
  );
}

export default AddTaskButton;
