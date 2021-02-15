import React, {useState} from 'react';
import {
  Combobox,
  ComboboxInput,
  ComboboxPopover,
  ComboboxList,
  ComboboxOption,
} from '@reach/combobox';

import {Icon} from 'app/components/icon';
import {t} from 'app/locale';

/**
 * The options in an autocomplete
 */
export interface Option {
  /**
   * The selection value. Will be part of the onChange
   * value.
   */
  value: number | string;
  /**
   * The text displayed in the input box
   * when this option is selected.
   * Also used to filter options.
   */
  text: string;
  /**
   * The display element. If undefined text will be used.
   */
  label?: React.ReactNode;
}

type Props<OptionType> = {
  /**
   * Hidden input name.
   */
  name: string;
  /**
   * Option list. Will be filtered based on the search text.
   */
  options: OptionType[];
  /**
   * The current value.
   */
  value: number | string | null | undefined;
  /**
   * Fired when a selection is made.
   */
  onChange?: (value: string | number) => void;
  label?: string;
};

function Autocomplete<OptionType extends Option = Option>({
  label,
  name,
  options,
  value,
  onChange,
}: Props<OptionType>): JSX.Element {
  const [current, setCurrent] = useState<Props<OptionType>['value']>(value ?? undefined);
  const [term, setTerm] = useState<string>('');
  const [filtered, setFiltered] = useState(options);

  let selected = '';
  if (term) {
    selected = options.find(option => option.text === term)?.text ?? '';
  } else if (value) {
    selected = options.find(option => option.value === value)?.text ?? '';
  }
  let defaultValue: OptionType['value'] = '';
  if (!selected && options.length > 0) {
    selected = options[0].text;
    defaultValue = options[0].value;
  }

  function handleChange(event: React.ChangeEvent<HTMLInputElement>) {
    const {value} = event.target;
    setFiltered(
      value
        ? options.filter(option =>
            option.text.toLowerCase().includes(value.toLowerCase())
          )
        : [...options]
    );
    setTerm(value);
  }

  function handleSelect(value: string) {
    const selected = options.find(option => option.text === value);
    if (!selected) {
      return;
    }
    setTerm(selected.text);
    setCurrent(selected.value);
    onChange?.(selected.value);
  }

  return (
    <Combobox aria-label={label} onSelect={handleSelect} openOnFocus>
      <input type="hidden" value={current || defaultValue} name={name} />
      <div className="combobox-wrapper">
        <ComboboxInput value={term || selected} onChange={handleChange} selectOnClick />
        <Icon className="combobox-arrow" icon="chevrondown" />
      </div>
      <ComboboxPopover>
        <ComboboxList persistSelection>
          {filtered.map(option => (
            <ComboboxOption key={option.value} value={option.text}>
              {option.label || option.text}
            </ComboboxOption>
          ))}
          {!options.length && (
            <div className="combobox-empty">{t('No options for that text')}</div>
          )}
        </ComboboxList>
      </ComboboxPopover>
    </Combobox>
  );
}
export default Autocomplete;
