import React from 'react';
import {MenuButton} from '@reach/menu-button';

import {Icon} from './icon';
import DropdownMenu from './dropdownMenu';

type Props = {
  children: React.ReactNode;
};

function ContextMenu({children}: Props): JSX.Element {
  return (
    <DropdownMenu
      button={() => (
        <MenuButton className="button-icon button-default">
          <Icon icon="kebab" />
        </MenuButton>
      )}
    >
      {children}
    </DropdownMenu>
  );
}
export default ContextMenu;
