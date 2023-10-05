<?php
declare(strict_types=1);
/**
 * Render the profile menu in the sidebar
 */

$avatarUrl = "https://www.gravatar.com/avatar/{$identity->avatar_hash}?s=50&default=retro";
?>
<div
    class="profile-menu"
    hx-ext="dropdown"
    dropdown-reveal="#profile-menu"
    dropdown-trigger="button.avatar"
>
    <button
        class="avatar" 
        aria-haspopup="true"
        aria-controls="profile-menu"
        type="button"
    >
        <?= $this->Html->image($avatarUrl, ['height' => 50, 'width' => 50]) ?>
    </button>
    <div id="profile-menu" style="position: absolute; display:none;">
        <div role="menu" data-reach-menu-list="">
            <div class="dropdown-item-text"><?= h($identity->name) ?></div>
            <div class="separator"></div>
            <?= $this->Html->link(
                $this->element('icons/pencil16') . ' Edit Profile',
                ['_path' => 'Users::edit'],
                ['class' => 'edit', 'escape' => false, 'role' => 'menuitem', 'data-reach-menu-item' => '',]
            ) ?>
            <?= $this->Html->link(
                $this->element('icons/calendar16') . ' Calendars',
                ['_path' => 'CalendarProviders::index'],
                ['class' => 'calendar', 'escape' => false, 'role' => 'menuitem', 'data-reach-menu-item' => '']
            ) ?>
            <?= $this->Html->link(
                $this->element('icons/lock16') . ' Update Password',
                ['_path' => 'Users::updatePassword'],
                ['class' => 'lock', 'escape' => false, 'role' => 'menuitem', 'data-reach-menu-item' => '',]
            ) ?>
            <div class="separator"></div>
            <?= $this->Html->link(
                'Logout',
                ['_path' => 'Users::logout'],
                ['role' => 'menuitem', 'data-reach-menu-item' => '',]
            ) ?>
        </div>
    </div>
</div>
