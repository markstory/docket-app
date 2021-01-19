/** @jsx jsx */
import {jsx} from '@emotion/core';
import Select, {
  OptionTypeBase,
  ValueType,
  OptionProps,
  SingleValueProps,
} from 'react-select';

import {PROJECT_COLORS} from 'app/constants';
import {InlineIcon} from 'app/components/icon';

function ColorOption(props: OptionProps<OptionTypeBase, false>) {
  const {getStyles, innerRef, innerProps, data} = props;
  return (
    <div css={getStyles('option', props)} ref={innerRef} {...innerProps}>
      <Color color={data.color} name={data.label} />
    </div>
  );
}

function ColorValue(props: SingleValueProps<OptionTypeBase>) {
  const {getStyles, innerProps, data} = props;
  return (
    <div css={getStyles('singleValue', props)} {...innerProps}>
      <Color color={data.color} name={data.label} />
    </div>
  );
}

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

type OptionType = ValueType<OptionTypeBase, false>;

type Props = {
  value?: number;
  onChange?: (value: OptionType) => void;
};

function ColorSelect({value, onChange}: Props) {
  const options = PROJECT_COLORS.map(item => ({
    value: item.id,
    label: item.name,
    color: item.code,
  }));
  const selected = value !== undefined ? value : options[0].value;
  const valueOption = options.find(opt => opt.value === selected);

  return (
    <Select
      classNamePrefix="color-select"
      defaultValue={valueOption}
      menuPlacement="auto"
      name="color"
      options={options}
      onChange={onChange}
      components={{
        Option: ColorOption,
        SingleValue: ColorValue,
      }}
    />
  );
}

export default ColorSelect;
