import React from 'react';
import {usePage, InertiaLink} from '@inertiajs/inertia-react';

import {t} from 'app/locale';
import {User} from 'app/types';
import {InlineIcon} from 'app/components/icon';
import DropdownMenu from 'app/components/dropdownMenu';

type SharedProps = {
  identity: User;
};

export default function ProfileMenu() {
  const {identity} = usePage().props as SharedProps;
  const avatarUrl = `https://www.gravatar.com/avatar/${identity.avatar_hash}?s=50&default=retro`;
  return (
    <div className="profile-menu">
      <DropdownMenu
        alignMenu="left"
        button={props => (
          <button className="avatar" {...props}>
            <img src={avatarUrl} width="50" height="50" />
          </button>
        )}
      >
        <ul>
          <li className="context-item-text separator">{identity.name}</li>
          <li>
            <InertiaLink className="context-item" href="/users/profile">
              <InlineIcon icon="pencil" />
              {t('Edit Profile')}
            </InertiaLink>
          </li>
          <li>
            <InertiaLink className="context-item" href="/users/updatePassword">
              <InlineIcon icon="lock" />
              {t('Update Password')}
            </InertiaLink>
          </li>
          <li>
            <InertiaLink className="context-item" href="/logout">
              {t('Logout')}
            </InertiaLink>
          </li>
        </ul>
      </DropdownMenu>
    </div>
  );
}
