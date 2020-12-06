import React from 'react';

import {ValidationErrors} from 'app/types';

type Props = {
  errors: ValidationErrors | null | undefined;
  field: string;
};

export default function FormError({errors, field}: Props) {
  console.log('form error', errors, field, errors?.hasOwnProperty(field));
  if (!errors || !errors.hasOwnProperty(field)) {
    return null;
  }
  return <div className="form-error">{errors[field]}</div>;
}
