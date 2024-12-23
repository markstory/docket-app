<?php
declare(strict_types=1);
/**
 * Render the profile menu in the sidebar
 *
 * @var \Cake\View\View $this
 * @var \App\Model\Entity\User $identity
 * @var string $activeFocus
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

<drop-down class="focus-menu">
    <button
        class="button-muted"
        aria-haspopup="true"
        aria-controls="focus-menu"
        type="button"
    >
    <?php if ($activeFocus == "tasks") : ?>
        <span class="icon-today">
            <?= $this->element('icons/clippy16') . ' Tasks' ?>
        </span>
    <?php elseif ($activeFocus == "feeds") : ?>
        <span class="icon-week">
            <?= $this->element('icons/rss16') . ' Feeds' ?>
        </span>
    <?php endif; ?>
    </button>
    <drop-down-menu id="focus-menu">
        <?= $this->Html->link(
            $this->element('icons/clippy16') . ' Tasks',
            ['_name' => 'tasks:today'],
            [
                'escape' => false,
                'hx-boost' => '1',
                'role' => 'menuitem',
                'class' => 'icon-today',
            ]
        ) ?>
        <?= $this->Html->link(
            $this->element('icons/rss16') . ' Feeds',
            ['_name' => 'feedsubscriptions:home'],
            [
                'escape' => false,
                'hx-boost' => '1',
                'role' => 'menuitem',
                'class' => 'icon-week',
            ]
        ) ?>
    </drop-down-menu>
</drop-down>
