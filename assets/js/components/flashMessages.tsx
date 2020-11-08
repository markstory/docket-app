import React from 'react';

import {FlashMessage} from 'app/types';

type Props = {
  flash: FlashMessage | null;
};

export default function FlashMessages({flash}: Props) {
  if (!flash || !flash.message) {
    return null;
  }
  return <div className="message error">{flash.message}</div>;
}
