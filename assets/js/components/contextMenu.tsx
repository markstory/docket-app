import React from 'react';
import {MenuButton} from '@reach/menu-button';

import {Icon} from './icon';
import DropdownMenu from './dropdownMenu';

type Props = {
  /**
   * Contents of the menu.
   */
  children: React.ReactNode;
  /**
   * Attached to the dropdown menu button.
   */
  onClick?: (event: React.MouseEvent<HTMLButtonElement>) => void;
};

function ContextMenu({children, onClick}: Props): JSX.Element {
  return (
    <DropdownMenu
      button={() => (
        <MenuButton className="button-icon button-default" onClick={onClick}>
          <Icon icon="kebab" />
        </MenuButton>
      )}
    >
      {children}
    </DropdownMenu>
  );
}
export default ContextMenu;
