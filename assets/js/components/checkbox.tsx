import React from 'react';
import {InlineIcon} from './icon';

type Props = {
  name: string;
  checked?: boolean;
  value?: string | number;
  onChange?: (event: React.ChangeEvent<HTMLInputElement>) => void;
};

function Checkbox({name, checked, onChange, value = '1'}: Props): JSX.Element {
  const uniqId = Math.round(Math.random() * 500);
  const fullId = `checkbox-${uniqId}-${name}`;
  return (
    <label htmlFor={fullId} className="checkbox">
      <input
        id={fullId}
        type="checkbox"
        name={name}
        checked={checked}
        value={value}
        onChange={onChange}
      />
      <InlineIcon icon="check" className="check" width="small" />
    </label>
  );
}

export default Checkbox;
