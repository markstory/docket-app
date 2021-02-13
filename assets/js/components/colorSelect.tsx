import React, {useState} from 'react';
import {
  Combobox,
  ComboboxInput,
  ComboboxPopover,
  ComboboxList,
  ComboboxOption,
} from '@reach/combobox';

import {PROJECT_COLORS} from 'app/constants';
import {t} from 'app/locale';
import {InlineIcon} from 'app/components/icon';

type ColorProps = {
  name: string;
  color: string;
};

function Color({name, color}: ColorProps) {
  return (
    <span className="color">
      <InlineIcon icon="dot" color={color} width="medium" />
      <span>{name}</span>
    </span>
  );
}

type Props = {
  value?: number;
  onChange?: (value: number) => void;
};

function ColorSelect({value, onChange}: Props): JSX.Element {
  let selected = '';
  if (value) {
    selected = PROJECT_COLORS.find(color => color.id === value)?.name ?? '';
  } else {
    selected = PROJECT_COLORS[0].name;
  }
  const [options, setOptions] = useState(PROJECT_COLORS);
  const [term, setTerm] = useState(selected);

  function handleChange(event: React.ChangeEvent<HTMLInputElement>) {
    const {value} = event.target;
    setOptions(
      value
        ? PROJECT_COLORS.filter(color =>
            color.name.toLowerCase().includes(value.toLowerCase())
          )
        : [...PROJECT_COLORS]
    );
    setTerm(value);
  }

  function handleSelect(value: string) {
    const selected = PROJECT_COLORS.find(color => color.name === value);
    if (!selected) {
      return;
    }
    onChange?.(selected.id);
  }

  return (
    <Combobox aria-label={t('Choose a color')} onSelect={handleSelect} openOnFocus>
      <input type="hidden" value={value} name="color" />
      <ComboboxInput defaultValue={term} onChange={handleChange} selectOnClick />
      <ComboboxPopover>
        <ComboboxList persistSelection>
          {options.map(color => (
            <ComboboxOption key={color.id} value={color.name}>
              <Color name={color.name} color={color.code} />
            </ComboboxOption>
          ))}
          {!options.length && <div className="combobox-empty">{t('No results')}</div>}
        </ComboboxList>
      </ComboboxPopover>
    </Combobox>
  );
}

export default ColorSelect;
