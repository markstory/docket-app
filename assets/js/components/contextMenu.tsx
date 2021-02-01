import React from 'react';
import {Icon} from './icon';
import DropdownMenu from './dropdownMenu';

type DropdownMenuProps = React.ComponentProps<typeof DropdownMenu>;

type Props = {
  children: React.ReactNode;
  alignMenu?: 'left' | 'right';
  onOpen?: DropdownMenuProps['onOpen'];
  onClose?: DropdownMenuProps['onClose'];
};

function ContextMenu({
  children,
  onOpen,
  onClose,
  alignMenu = 'left',
}: Props): JSX.Element {
  return (
    <DropdownMenu
      onOpen={onOpen}
      onClose={onClose}
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
