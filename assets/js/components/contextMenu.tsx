import React, {useEffect, useState, useRef} from 'react';
import {Icon} from './icon';
import DropdownMenu from './dropdownMenu';

type Props = {
  children: React.ReactNode;
  alignMenu?: 'left' | 'right';
};

function ContextMenu({children, alignMenu = 'left'}: Props) {
  return (
    <DropdownMenu
      alignMenu={alignMenu}
      button={props => (
        <button className="button-icon button-default" {...props}>
          <Icon icon="kebab" />
        </button>
      )}
    >
      <ul>{children}</ul>
    </DropdownMenu>
  );
}
export default ContextMenu;
