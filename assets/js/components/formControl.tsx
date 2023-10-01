import classnames from 'classnames';

import {ValidationErrors} from 'app/types';
import FormError from 'app/components/formError';

type InputAttrs = Pick<Props, 'name' | 'id' | 'required' | 'value'>;

type InputType = 'text' | 'email' | 'password' | ((attrs: InputAttrs) => React.ReactNode);

type Props = {
  name: string;
  label: React.ReactNode;
  type: InputType;
  className?: string;
  placeholder?: string;
  value?: string | number;
  id?: string;
  required?: boolean;
  help?: React.ReactNode;
  errors?: ValidationErrors;
};

function FormControl({
  className,
  errors,
  help,
  id,
  label,
  name,
  placeholder,
  required,
  type,
  value,
}: Props): JSX.Element {
  id = id ?? name;

  let input: React.ReactNode;
  if (typeof type === 'string') {
    input = (
      <input
        id={id}
        name={name}
        type={type}
        required={required}
        defaultValue={value}
        placeholder={placeholder}
      />
    );
  } else if (typeof type === 'function') {
    const inputAttrs = {name, id, required};
    input = type(inputAttrs);
  }
  className = classnames('form-control', className, {
    'is-error': errors && errors[name] !== undefined,
  });

  return (
    <div className={className}>
      <div className="form-label-group">
        <label htmlFor={id} data-required={required}>
          {label}
        </label>
        {help && <p className="form-help">{help}</p>}
      </div>
      <div className="form-input">
        {input}
        <FormError errors={errors} field={name} />
      </div>
    </div>
  );
}

export default FormControl;
