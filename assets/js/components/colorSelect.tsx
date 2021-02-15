import React from 'react';
import Autocomplete, {Option} from 'app/components/autocomplete';
import {PROJECT_COLORS} from 'app/constants';
import {InlineIcon} from 'app/components/icon';
import {t} from 'app/locale';

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
  /**
   * Default value.
   */
  value?: number;
  onChange?: (value: number | string) => void;
};

function ColorSelect({value, onChange}: Props): JSX.Element {
  const options: Option[] = PROJECT_COLORS.map(color => ({
    value: color.id,
    text: color.name,
    label: <Color color={color.code} name={color.name} />,
  }));
  return (
    <Autocomplete
      label={t('Choose a color')}
      name="color"
      value={value}
      options={options}
      onChange={onChange}
    />
  );
}

export default ColorSelect;
