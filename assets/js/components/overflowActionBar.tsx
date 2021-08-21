import {ReactNode, useEffect, useState} from 'react';
import {MenuItem} from '@reach/menu-button';

import ContextMenu from './contextMenu';

type ActionItem = {
  /**
   * Label text or element.
   */
  label: ReactNode;
  /**
   * Fired when the menu item/button is clicked.
   */
  onSelect: () => void;
  /**
   * Optional icon for the item.
   * Combine with className to color icons.
   */
  icon?: ReactNode;
  buttonClass?: string;
  menuItemClass?: string;
  dataTestId?: string;
};

type Props = {
  /**
   * The items to render.
   */
  items: ActionItem[];
  /**
   * The window width in px that this menu should become
   * a ContextMenu
   */
  foldWidth: Number;
  /**
   * The tooltip for the collapsed menu.
   */
  label?: string;
};

/**
 * A container for buttons that when `foldWidth`
 * is reached will collapse into a context menu.
 */
function OverflowActionBar({items, foldWidth, label}: Props) {
  const [isCompact, setCompact] = useState<boolean>(false);

  // Toggle state based on media query listeners.
  // Start listeners on mount and remove during unmount.
  useEffect(() => {
    const query = window.matchMedia(`(max-width: ${foldWidth}px)`);
    const handler = () => setCompact(query.matches ? true : false);
    query.addEventListener('change', handler);

    if (query.matches) {
      setCompact(true);
    }

    // cleanup
    return () => {
      query.removeEventListener('change', handler);
    };
  }, []);

  function handleClick(item: ActionItem) {
    return function (event: React.MouseEvent<HTMLButtonElement>) {
      event.stopPropagation();
      item.onSelect();
    };
  }

  // Render context menu.
  if (isCompact) {
    return (
      <ContextMenu tooltip={label}>
        {items.map((item, index) => (
          <MenuItem
            key={index}
            data-testid={item.dataTestId}
            className={item.menuItemClass}
            onSelect={item.onSelect}
          >
            {item.icon}
            {item.label}
          </MenuItem>
        ))}
      </ContextMenu>
    );
  }

  // Render button bar.
  return (
    <div className="button-bar-inline">
      {items.map((item, index) => (
        <button
          key={index}
          className={item.buttonClass}
          data-testid={item.dataTestId}
          onClick={handleClick(item)}
        >
          {item.icon}
          {item.label}
        </button>
      ))}
    </div>
  );
}

export default OverflowActionBar;
