<?php
declare(strict_types=1);
/**
 * Render the profile menu in the sidebar
 */

$avatarUrl = "https://www.gravatar.com/avatar/{$identity->avatar_hash}?s=50&default=retro";
?>
<drop-down class="profile-menu">
    <button
        class="avatar"
        aria-haspopup="true"
        aria-controls="profile-menu"
        type="button"
    >
        <?= $this->Html->image($avatarUrl, ['height' => 50, 'width' => 50]) ?>
    </button>
    <drop-down-menu id="profile-menu" role="menu">
        <div class="dropdown-item-text"><?= h($identity->name) ?></div>
        <div class="separator"></div>
        <?= $this->Html->link(
            $this->element('icons/pencil16') . ' Edit Profile',
            ['_path' => 'Users::edit'],
            ['class' => 'icon-edit', 'escape' => false, 'role' => 'menuitem', 'hx-boost' => '1']
        ) ?>
        <?= $this->Html->link(
            $this->element('icons/calendar16') . ' Calendars',
            ['_path' => 'CalendarProviders::index'],
            ['class' => 'icon-today', 'escape' => false, 'role' => 'menuitem', 'hx-boost' => '1']
        ) ?>
        <?= $this->Html->link(
            $this->element('icons/lock16') . ' Update Password',
            ['_path' => 'Users::updatePassword'],
            ['class' => 'icon-lock', 'escape' => false, 'role' => 'menuitem', 'hx-boost' => '1']
        ) ?>
        <div class="separator"></div>
        <?= $this->Html->link(
            'Logout',
            ['_path' => 'Users::logout'],
            ['role' => 'menuitem']
        ) ?>
    </drop-down-menu>
</drop-down>
