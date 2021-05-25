import {MenuButton} from '@reach/menu-button';

import {Icon} from './icon';
import DropdownMenu from './dropdownMenu';
import Tooltip from 'app/components/tooltip';

type Props = {
  /**
   * Contents of the menu.
   */
  children: React.ReactNode;
  /**
   * Custom icon name.
   */
  icon?: string;
  /**
   * Tooltip text.
   */
  tooltip?: string;
  /**
   * Attached to the dropdown menu button.
   */
  onClick?: (event: React.MouseEvent<HTMLButtonElement>) => void;
};

function ContextMenu({
  children,
  onClick,
  icon = 'kebab',
  tooltip = '',
}: Props): JSX.Element {
  return (
    <DropdownMenu
      button={() => (
        <Tooltip label={tooltip}>
          <MenuButton
            className="button-icon button-default"
            aria-label={tooltip}
            onClick={onClick}
          >
            <Icon icon={icon} />
          </MenuButton>
        </Tooltip>
      )}
    >
      {children}
    </DropdownMenu>
  );
}
export default ContextMenu;
