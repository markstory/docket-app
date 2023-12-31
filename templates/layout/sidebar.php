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
if ($this->request->is('htmx')) :
    echo $this->Flash->render();
else :
    $this->extend('default');
endif;

$todayActive = strpos($this->request->getPath(), '/tasks/today') !== false;
$upcomingActive = strpos($this->request->getPath(), '/tasks/upcoming') !== false;

// TODO make expanded work. Perhaps with an htmx
// extension that maintains the state?
?>
<div class="layout-three-quarter" data-testid="loggedin" hx-ext="hotkeys">
    <section id="sidebar" class="sidebar" data-expanded="false">
        <div class="menu">
            <div>
                <?= $this->element('profile_menu') ?>
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
                                ]
                            ) ?>
                        </li>
                    </ul>
                    <h3>Projects</h3>
                    <?= $this->cell('ProjectsMenu', ['identity' => $identity]) ?>

                    <div className="secondary-actions">
                        <a class="action-primary" href="<?= $this->Url->build(['_name' => 'projects:add']) ?>">
                            <?= $this->element('icons/plus16') ?>
                            New Project
                        </a>
                        <a class="action-secondary" href="<?= $this->Url->build(['_name' => 'projects:archived']) ?>">
                            <?= $this->element('icons/archive16') ?>
                            Archived Projects
                        </a>
                        <a class="action-secondary" href="<?= $this->Url->build(['_name' => 'tasks:deleted']) ?>">
                            <?= $this->element('icons/trash16') ?>
                            Trash Bin
                        </a>

                        <a href="#"
                            class="layout-show-help"
                            hx-get="<?= $this->Url->build(['controller' => 'Pages', 'action' => 'display', 'help']) ?>"
                            hx-target="main.main"
                            hx-swap="beforeend"
                            data-hotkey="shift+?"
                        >
                            Show keyboard shortcuts
                        </a>
                    </div>
                </div>
            </div>
            <?= $this->Html->image('docket-logo-translucent.svg', ['width' => 30, 'height' => 30]) ?>
        </div>
        <button class="expander" title="Show project menu" id="sidebar-expander">
            <?= $this->element('icons/kebab16') ?>
        </button>
    </section>
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
<?= $this->Html->scriptStart() ?>
(function () {
    const button = document.getElementById('sidebar-expander');
    const sidebar = document.getElementById('sidebar');
    button.addEventListener('click', function (evt) {
        evt.preventDefault();
        const current = sidebar.dataset.expanded;
        sidebar.dataset.expanded = current === 'false' ? 'true' : 'false';
    });
})();
<?= $this->Html->scriptEnd() ?>
