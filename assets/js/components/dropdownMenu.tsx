import React, {useEffect, useState, useRef} from 'react';
import classnames from 'classnames';

type ButtonProps = {
  ref: React.Ref<HTMLButtonElement>;
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
  onOpen?: () => void;
  onClose?: () => void;
};

function defaultButton(props: ButtonProps) {
  return <button {...props}>Open</button>;
}

function DropdownMenu({
  button,
  className,
  children,
  onOpen,
  onClose,
  alignMenu = 'left',
  show = false,
}: Props): JSX.Element {
  const [isShowing, setIsShowing] = useState<boolean>(show);
  const menuRef = useRef<HTMLDivElement>(null);
  const triggerRef = useRef<HTMLButtonElement>(null);

  function handleClick(event: React.MouseEvent) {
    event.preventDefault();
    const nextShowing = !isShowing;
    setIsShowing(nextShowing);
    if (nextShowing) {
      onOpen?.();
    } else {
      onClose?.();
    }
  }

  function handleOutsideClick(event: MouseEvent) {
    if (!menuRef.current || !triggerRef.current) {
      return;
    }
    const target = event.target as HTMLElement;
    if (triggerRef.current.contains(target) || menuRef.current.contains(target)) {
      return;
    }
    setIsShowing(false);
    onClose?.();
  }

  useEffect(() => {
    if (isShowing) {
      document.addEventListener('click', handleOutsideClick, true);
    }
    return function cleanup() {
      document.removeEventListener('click', handleOutsideClick, true);
    };
  });

  const buttonProps = {
    ref: triggerRef,
    onClick: handleClick,
    'data-active': isShowing,
  };
  const containerClass = classnames('dropdown-menu', className);

  return (
    <div data-align={alignMenu} className={containerClass}>
      {button ? button(buttonProps) : defaultButton(buttonProps)}
      {isShowing && (
        <div ref={menuRef} className="dropdown" onClick={handleClick}>
          {children}
        </div>
      )}
    </div>
  );
}
export default DropdownMenu;
