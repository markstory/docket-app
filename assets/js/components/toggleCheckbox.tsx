import React from 'react';

type Props = {
  label: React.ReactNode;
  name: string;
  value?: boolean;
  knobIcon?: React.ReactNode;
  onChange?: (event: React.ChangeEvent<HTMLInputElement>) => void;
};

function ToggleCheckbox({name, label, knobIcon, value, onChange}: Props): JSX.Element {
  return (
    <label htmlFor={`toggle-${name}`} className="toggle-checkbox">
      <input
        id={`toggle-${name}`}
        type="checkbox"
        name={name}
        checked={value}
        onChange={onChange}
      />
      <span className="knob">{knobIcon}</span>
      <span className="track"></span>
      {label}
    </label>
  );
}

export default ToggleCheckbox;
