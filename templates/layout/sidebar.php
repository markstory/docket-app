<?php
declare(strict_types=1);
/**
 * Renders the view with the application sidebar.
 *
 * @var \App\Model\Entity\User $identity
 * @var bool? $showGlobalAdd
 * @var array $globalAddContext
 */

// If we're not handling an HX-Request wrap the layout
// in page chrome. When we're handling htmx requests,
// we swap main.main.
if ($this->request->is('htmx')) : ?>
<div class="flash-messages">
    <?= $this->Flash->render() ?>
</div>
<?php else :
    $this->extend('default');
endif;

$calendarActive = str_contains($this->request->getPath(), '/calendars');
$archivedActive = str_contains($this->request->getPath(), '/projects/archived');
$todayActive = str_contains($this->request->getPath(), '/tasks/today');
$upcomingActive = str_contains($this->request->getPath(), '/tasks/upcoming');
$trashActive = str_contains($this->request->getPath(), '/tasks/deleted');
?>
<div class="layout-three-quarter" data-testid="loggedin" hx-ext="hotkeys">
    <side-bar class="sidebar" data-expanded="false">
        <div class="menu">
            <div>
                <div class="profile-menu-container">
                    <?= $this->element('profile_menu', ['activeFocus' => 'tasks']) ?>
                </div>
                <div class="project-filter">
                    <ul class="links">
                        <li class="icon-today">
                            <?= $this->Html->link(
                                $this->element('icons/clippy16') . ' Today',
                                ['_name' => 'tasks:today'],
                                [
                                    'escape' => false,
                                    'class' => $todayActive ? 'active' : '',
                                    'data-hotkey' => 't',
                                    'hx-boost' => '1',
                                ]
                            ) ?>
                        </li>
                        <li class="icon-tomorrow">
                            <?= $this->Html->link(
                                $this->element('icons/calendar16') . ' Upcoming',
                                ['_name' => 'tasks:upcoming'],
                                [
                                    'escape' => false,
                                    'class' => $upcomingActive ? 'active' : '',
                                    'data-hotkey' => 'u',
                                    'hx-boost' => '1',
                                ]
                            ) ?>
                        </li>
                    </ul>
                    <h3>Projects</h3>
                    <?= $this->cell('Tasks.ProjectsMenu', ['identity' => $identity]) ?>

                    <ul class="links">
                        <li>
                            <a class="action-primary"
                                href="<?= $this->Url->build(['_name' => 'projects:add']) ?>"
                                hx-boost="1"
                            >
                                <?= $this->element('icons/plus16') ?>
                                New Project
                            </a>
                            </li>
                        <li>
                            <a class="action-secondary <?= $archivedActive ? 'active' : '' ?>"
                                href="<?= $this->Url->build(['_name' => 'projects:archived']) ?>"
                                hx-boost="1"
                            >
                                <?= $this->element('icons/archive16') ?>
                                Archived Projects
                            </a>
                        </li>
                        <li>
                            <a class="action-secondary <?= $calendarActive ? 'active' : '' ?>"
                                href="<?= $this->Url->build(['_name' => 'calendarproviders:index']) ?>"
                                hx-boost="1"
                            >
                                <?= $this->element('icons/calendar16') ?>
                                Calendars
                            </a>
                        </li>
                        <li>
                            <a class="action-secondary <?= $trashActive ? 'active' : '' ?>"
                               href="<?= $this->Url->build(['_name' => 'tasks:deleted']) ?>"
                               hx-boost="1"
                            >
                                <?= $this->element('icons/trash16') ?>
                                Trash Bin
                            </a>
                        </li>
                    </ul>
                    <a href="#"
                        class="layout-show-help"
                        hx-get="<?= $this->Url->build([
                            'plugin' => false,
                            'controller' => 'Pages',
                            'action' => 'display',
                            'help',
                        ]) ?>"
                        hx-target="main.main"
                        hx-swap="beforeend"
                        data-hotkey="shift+?"
                    >
                        Show keyboard shortcuts
                    </a>
                </div>
            </div>
            <?= $this->Html->image('docket-logo-translucent.svg', ['width' => 30, 'height' => 30]) ?>
        </div>
        <button class="expander" title="Show project menu" data-expander="1">
            <?= $this->element('icons/kebab16') ?>
        </button>
    </side-bar>
    <section class="content">
        <?= $this->fetch('content'); ?>
        <?php if (isset($showGlobalAdd) && $showGlobalAdd) : ?>
            <?= $this->Html->link(
                $this->element('icons/plus16', ['size' => 64]),
                ['_name' => 'tasks:add', '?' => $globalAddContext ?? []],
                [
                    'escape' => false,
                    'class' => 'button-global-add button-primary',
                    'data-testid' => 'global-task-add',
                    'data-hotkey' => 'c',
                    'hx-get' => $this->Url->build(['_name' => 'tasks:add', '?' => $globalAddContext ?? []]),
                    'hx-target' => 'main.main',
                    'hx-swap' => 'beforeend',
                ]
            ) ?>
        <?php endif; ?>
    </section>
</div>
