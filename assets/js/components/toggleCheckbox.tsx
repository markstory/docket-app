import * as React from 'react';

type Props = {
  name: string;
  label?: React.ReactNode;
  value?: string;
  checked?: boolean;
  knobIcon?: React.ReactNode;
  onChange?: (event: React.ChangeEvent<HTMLInputElement>) => void;
};

function ToggleCheckbox({
  name,
  label,
  knobIcon,
  checked,
  onChange,
  value = '1',
}: Props): JSX.Element {
  return (
    <label htmlFor={`toggle-${name}`} className="toggle-checkbox">
      <input
        id={`toggle-${name}`}
        type="checkbox"
        name={name}
        defaultChecked={checked}
        value={value}
        onChange={onChange}
      />
      <span className="knob">{knobIcon}</span>
      <span className="track"></span>
      {label}
    </label>
  );
}

export default ToggleCheckbox;
