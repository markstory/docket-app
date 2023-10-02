<?php
declare(strict_types=1);
/**
 * Render the profile menu in the sidebar
 */

$avatarUrl = "https://www.gravatar.com/avatar/{$identity->avatar_hash}?s=50&default=retro";
?>
<div class="profile-menu">
    <DropdownMenu
        button={() => (
          <MenuButton className="avatar">
            <?= $this->Html->image($imageUrl, ['height' => 50, 'width' => 50]) ?>
          </MenuButton>
        )}
    >
        <div class="dropdown-item-text"><?= h($identity->name) ?></div>
        <div class="separator" />
    <MenuLink as={InertiaLink} className="edit" href="/users/profile">
      <InlineIcon icon="pencil" />
      {t('Edit Profile')}
    </MenuLink>
    <MenuLink as={InertiaLink} className="calendar" href="/calendars">
      <InlineIcon icon="calendar" />
      {t('Calendars')}
    </MenuLink>
    <MenuLink className="lock" href="/users/updatePassword">
      <InlineIcon icon="lock" />
      {t('Update Password')}
    </MenuLink>
    <div className="separator" />
    <MenuLink as={InertiaLink} href="/logout">
      {t('Logout')}
    </MenuLink>
  </DropdownMenu>
</div>
