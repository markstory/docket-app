import React from 'react';

type Props = {
  onLabel: React.ReactNode;
  offLabel: React.ReactNode;
  name: string;
  value?: boolean;
  onChange?: (event: React.ChangeEvent<HTMLInputElement>) => void;
};

function ToggleCheckbox({name, onLabel, offLabel, value, onChange}: Props): JSX.Element {
  return (
    <span className="toggle-checkbox">
      <input
        id={`toggle-${name}`}
        type="checkbox"
        name={name}
        checked={value}
        onChange={onChange}
      />
      <label htmlFor={`toggle-${name}`} className="switch">
        <span className="on">{onLabel}</span>
        <span className="knob"></span>
        <span className="off">{offLabel}</span>
      </label>
    </span>
  );
}

export default ToggleCheckbox;
