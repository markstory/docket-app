import { useEffect, useState, useRef } from 'react';
import * as React from 'react';
import classnames from 'classnames';

import {FlashMessage} from 'app/types';
import {InlineIcon} from 'app/components/icon';

type Props = {
  flash: FlashMessage | null;
};

const TIMEOUT = 5000;

export default function FlashMessages({flash}: Props) {
  if (!flash || !flash.message) {
    return null;
  }
  let mounted = true;
  const hideTimer = useRef<number | undefined>(undefined);
  const showTimer = useRef<number | undefined>(undefined);
  const [showing, setShowing] = useState(false);
  const [hovering, setHovering] = useState(false);

  // Set a hide delay when hovering or showing is changed.
  useEffect(
    function () {
      if (hovering || !showing || !mounted) {
        return;
      }
      hideTimer.current = window.setTimeout(function () {
        setShowing(false);
      }, TIMEOUT);
      return function cleanup() {
        clearTimeout(hideTimer.current);
      };
    },
    [hovering, showing]
  );

  // Toggle state on mount to animate in.
  useEffect(
    function () {
      showTimer.current = window.setTimeout(function () {
        if (mounted) {
          setShowing(true);
        }
      }, 0);
      return function cleanup() {
        mounted = false;
        clearTimeout(showTimer.current);
      };
    },
    [flash]
  );

  const className = classnames('flash-message', flash.element);

  let icon: React.ReactNode = null;
  if (flash.element === 'flash-success') {
    icon = <InlineIcon icon="checkcircle" />;
  } else if (flash.element === 'flash-error') {
    icon = <InlineIcon icon="alert" />;
  }

  function handleMouseEnter() {
    window.clearTimeout(showTimer.current);
    window.clearTimeout(hideTimer.current);
    if (mounted) {
      setHovering(true);
    }
  }

  return (
    <div
      data-state={showing ? 'visible' : 'hidden'}
      className={className}
      onMouseEnter={handleMouseEnter}
      onMouseLeave={() => setHovering(false)}
    >
      {icon}
      {flash.message}
    </div>
  );
}
