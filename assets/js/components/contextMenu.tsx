import React, {useEffect, useState, useRef} from 'react';

type Props = {
  children: React.ReactNode;
};

function ContextMenu({children}: Props) {
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
    setIsShowing(false);
    document.body.removeEventListener('click', handleOutsideClick, true);
  }

  useEffect(() => {
    if (isShowing) {
      document.body.addEventListener('click', handleOutsideClick, true);
    }
  }, [isShowing]);

  // TODO fix accessibility on this.
  return (
    <div ref={menuRef} className="context-menu">
      <button onClick={handleClick}>{'\u2016'}</button>
      {isShowing && (
        <div className="context-dropdown">
          <ul>{children}</ul>
        </div>
      )}
    </div>
  );
}
export default ContextMenu;
