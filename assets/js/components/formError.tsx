import {ValidationErrors} from 'app/types';

type Props = {
  errors: ValidationErrors | null | undefined;
  field: string;
};

export default function FormError({errors, field}: Props) {
  if (!errors || !errors.hasOwnProperty(field)) {
    return null;
  }
  return <div className="form-error">{errors[field]}</div>;
}
