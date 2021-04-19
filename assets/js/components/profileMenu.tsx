import React from 'react';
import {MenuLink, MenuButton} from '@reach/menu-button';
import {usePage, InertiaLink} from '@inertiajs/inertia-react';

import {t} from 'app/locale';
import {User} from 'app/types';
import {InlineIcon} from 'app/components/icon';
import DropdownMenu from 'app/components/dropdownMenu';

type SharedProps = {
  identity: User;
};

export default function ProfileMenu(): JSX.Element {
  const {identity} = usePage().props as SharedProps;
  const avatarUrl = `https://www.gravatar.com/avatar/${identity.avatar_hash}?s=50&default=retro`;
  return (
    <div className="profile-menu">
      <DropdownMenu
        button={() => (
          <MenuButton className="avatar">
            <img src={avatarUrl} width="50" height="50" />
          </MenuButton>
        )}
      >
        <div className="dropdown-item-text">{identity.name}</div>
        <div className="separator" />
        <MenuLink as={InertiaLink} className="edit" href="/users/profile">
          <InlineIcon icon="pencil" />
          {t('Edit Profile')}
        </MenuLink>
        <MenuLink as={InertiaLink} className="lock" href="/users/updatePassword">
          <InlineIcon icon="lock" />
          {t('Update Password')}
        </MenuLink>
        <div className="separator" />
        <MenuLink as={InertiaLink} href="/logout">
          {t('Logout')}
        </MenuLink>
      </DropdownMenu>
    </div>
  );
}
