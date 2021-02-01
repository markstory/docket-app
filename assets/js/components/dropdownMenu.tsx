import React, {useEffect, useState, useRef} from 'react';
import {createPortal} from 'react-dom';
import classnames from 'classnames';
import {usePopper} from 'react-popper';

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
  const arrowRef = useRef<HTMLDivElement>(null);

  const {styles, attributes, update} = usePopper(triggerRef.current, menuRef.current, {
    placement: alignMenu === 'left' ? 'bottom-start' : 'bottom-end',
    modifiers: [
      {name: 'arrow', options: {element: arrowRef.current}},
      {name: 'offset', options: {offset: alignMenu === 'left' ? [-10, 20] : [10, 20]}},
    ],
  });

  function handleClick(event: React.MouseEvent) {
    event.preventDefault();
    const nextVal = !isShowing;
    setIsShowing(nextVal);
    if (nextVal) {
      onOpen?.();
    } else {
      onClose?.();
    }
    // Necessary to ensure menus on right side are positioned well.
    update?.();
  }

  function handleOutsideClick(event: MouseEvent) {
    if (!menuRef.current || !triggerRef.current || !event.target) {
      return;
    }
    const target = event.target as HTMLElement;
    // Ignore clicks in the trigger and the menu.
    if (triggerRef.current.contains(target) || menuRef.current.contains(target)) {
      return;
    }
    setIsShowing(false);
    onClose?.();
  }

  useEffect(() => {
    document.addEventListener('click', handleOutsideClick, true);
    return function cleanup() {
      document.removeEventListener('click', handleOutsideClick, true);
    };
  });

  // Append dropdowns to shared portal so that we get around
  // siblings or descendants that have position:relative.
  let portal = document.querySelector('#dropdown-portal');
  if (!portal) {
    portal = document.createElement('div');
    portal.id = 'dropdown-portal';
    document.body.appendChild(portal);
  }

  const buttonProps = {
    ref: triggerRef,
    onClick: handleClick,
    'data-active': isShowing,
  };
  const containerClass = classnames('dropdown-menu', className);

  return (
    <div data-align={alignMenu} className={containerClass}>
      {button ? button(buttonProps) : defaultButton(buttonProps)}
      {createPortal(
        <div
          ref={menuRef}
          className="dropdown-menu-menu"
          style={styles.popper}
          {...attributes.popper}
          onClick={handleClick}
          data-visible={isShowing}
        >
          <div ref={arrowRef} className="arrow" style={styles.arrow} />
          {isShowing && children}
        </div>,
        portal
      )}
    </div>
  );
}
export default DropdownMenu;
