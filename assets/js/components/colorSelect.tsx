import { Fragment } from 'react';
import classnames from 'classnames';
import Select, {ValueType, OptionProps, SingleValueProps} from 'react-select';
import {PROJECT_COLORS} from 'app/constants';
import {InlineIcon} from 'app/components/icon';
import {t} from 'app/locale';

type ColorItem = {
  id: number;
  name: string;
  code: string;
};

function ColorOption(props: OptionProps<ColorItem, false>) {
  const {innerRef, innerProps, data} = props;
  const className = classnames({
    'is-selected': props.isSelected,
    'is-focused': props.isFocused,
  });
  return (
    <div className={className} ref={innerRef} {...innerProps}>
      <Color color={data.code} name={data.name} />
    </div>
  );
}

function ColorValue(props: SingleValueProps<ColorItem>) {
  const {innerProps, data} = props;
  return (
    <div {...innerProps}>
      <Color color={data.code} name={data.name} />
    </div>
  );
}

type ColorProps = {
  name: string;
  color: string;
};

function Color({name, color}: ColorProps) {
  return (
    <Fragment>
      <InlineIcon icon="dot" color={color} width="medium" />
      <span>{name}</span>
    </Fragment>
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
  const selected = value !== undefined ? value : PROJECT_COLORS[0].id;
  const valueOption = PROJECT_COLORS.find(opt => opt.id === selected);

  function handleChange(selected: ValueType<ColorItem, false>) {
    if (selected && onChange) {
      onChange(selected.id);
    }
  }

  return (
    <Select
      classNamePrefix="select"
      placeholder={t('Choose a color')}
      name="color"
      defaultValue={valueOption}
      options={PROJECT_COLORS}
      onChange={handleChange}
      getOptionValue={option => String(option.id)}
      components={{
        Option: ColorOption,
        SingleValue: ColorValue,
        IndicatorSeparator: null,
      }}
      menuPlacement="auto"
    />
  );
}

export default ColorSelect;
