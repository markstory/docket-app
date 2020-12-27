import React, {useEffect, useState, useRef} from 'react';
import classnames from 'classnames';
import {Icon} from './icon';

type ButtonProps = {
  onClick: (event: React.MouseEvent) => void;
};

type Props = {
  children: React.ReactNode;
  alignMenu?: 'left' | 'right';
  className?: string;
  button?: (props: ButtonProps) => React.ReactNode;
};

function defaultButton(props: ButtonProps) {
  return <button {...props}>Open</button>;
}

function DropdownMenu({button, className, children, alignMenu = 'left'}: Props) {
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

  const buttonProps = {
    onClick: handleClick,
  };
  const containerClass = classnames('dropdown-menu', className);

  // TODO handle esc to close menu.
  return (
    <div ref={menuRef} data-align={alignMenu} className={containerClass}>
      {button ? button(buttonProps) : defaultButton(buttonProps)}
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
export default DropdownMenu;
