<?php
declare(strict_types=1);
?>
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
