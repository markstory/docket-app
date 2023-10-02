<?php
declare(strict_types=1);
/**
 * Render the profile menu in the sidebar
 */

$avatarUrl = "https://www.gravatar.com/avatar/{$identity->avatar_hash}?s=50&default=retro";
?>
<div class="profile-menu">
    <button
        class="avatar" 
        aria-haspopup="true" 
        aria-controls="profile-menu"
        type="button"
        hx-get="<?= $this->Url->build(['_path' => 'Users::profileMenu']) ?>"
        hx-target="profile-menu"
    >
        <?= $this->Html->image($imageUrl, ['height' => 50, 'width' => 50]) ?>
    </button>
    <div id="profile-menu", role="menu">
    <?php if (isset($isOpen)) : ?>
        <div class="dropdown-item-text"><?= h($identity->name) ?></div>
        <div class="separator" />
        <?= $this->Html->link(
            $this->element('icons/pencil16') . ' Edit Profile',
            ['_path' => 'Users::edit'],
            ['class' => 'edit']
        ) ?>
        <?= $this->Html->link(
            $this->element('icons/calendar16') . ' Calendars',
            ['_path' => 'CalendarProviders::index'],
            ['class' => 'calendar']
        ) ?>
        <?= $this->Html->link(
            $this->element('icons/lock16') . ' Update Password',
            ['_path' => 'Users::updatePassword'],
            ['class' => 'lock']
        ) ?>
        <div class="separator" />
        <?= $this->Html->link(
            'Logout',
            ['_path' => 'Users::logout'],
        ) ?>
        <MenuLink as={InertiaLink} href="/logout">
          {t('Logout')}
        </MenuLink>
    <?php endif; ?>
    </div>
</div>
