import React, {useEffect, useState, useRef} from 'react';
import {Icon} from './icon';

type Props = {
  children: React.ReactNode;
  alignMenu?: 'left' | 'right';
};

function ContextMenu({children, alignMenu = 'left'}: Props) {
  let mounted = true;
  const [isShowing, setIsShowing] = useState<boolean>(false);
  const menuRef = useRef<HTMLDivElement>(null);

  function handleClick(event: React.MouseEvent) {
    event.preventDefault();
    setIsShowing(!isShowing);
  }

  function handleOutsideClick(event: MouseEvent) {
    if (!event.target) {
      return;
    }
    if (!(event.target instanceof HTMLElement)) {
      return;
    }
    if (menuRef?.current?.contains(event.target)) {
      return;
    }
    if (mounted) {
      setIsShowing(false);
    }
    document.body.removeEventListener('click', handleOutsideClick, true);
  }

  useEffect(() => {
    if (isShowing) {
      document.body.addEventListener('click', handleOutsideClick, true);
    }
    return function cleanup() {
      mounted = false;
    };
  }, [isShowing]);

  // TODO extract the dropdown aspect of this into a
  // component that takes button & items.
  return (
    <div ref={menuRef} className={`context-menu align-${alignMenu}`}>
      <button onClick={handleClick}>
        <Icon icon="kebab" />
      </button>
      {isShowing && (
        <div className="hitbox">
          <div className="dropdown">
            <ul>{children}</ul>
          </div>
        </div>
      )}
    </div>
  );
}
export default ContextMenu;
