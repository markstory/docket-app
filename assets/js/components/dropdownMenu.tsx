import React, {useEffect, useState, useRef} from 'react';
import classnames from 'classnames';

type ButtonProps = {
  onClick: (event: React.MouseEvent) => void;
};

type Props = {
  children: React.ReactNode;
  /**
   * Which side of the button to align the menu with.
   */
  alignMenu?: 'left' | 'right';
  className?: string;
  /**
   * A render function that must return an element
   * to trigger showing the menu. The `props` parameter
   * has event handlers.
   */
  button?: (props: ButtonProps) => React.ReactNode;
  /**
   * Should the menu show by default?
   */
  show?: boolean;
};

function defaultButton(props: ButtonProps) {
  return <button {...props}>Open</button>;
}

function DropdownMenu({
  button,
  className,
  children,
  alignMenu = 'left',
  show = false,
}: Props) {
  let mounted = true;
  const [isShowing, setIsShowing] = useState<boolean>(show);
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
    'data-active': isShowing,
  };
  const containerClass = classnames('dropdown-menu', className);

  return (
    <div ref={menuRef} data-align={alignMenu} className={containerClass}>
      {button ? button(buttonProps) : defaultButton(buttonProps)}
      {isShowing && (
        <div className="hitbox">
          <div className="dropdown" onClick={handleClick}>
            {children}
          </div>
        </div>
      )}
    </div>
  );
}
export default DropdownMenu;
