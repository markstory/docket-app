<?php
declare(strict_types=1);
/**
 * Renders the view with the application sidebar.
 */
$this->extend('default');

// TODO make expanded work. Perhaps with an htmx
// extension that maintains the state?
?>
<main
    class="layout-three-quarter"
    data-expanded="false"
    data-testid="loggedin"
>
    <section class="sidebar">
        <div class="menu">
            <div>
                <?= $this->element('profile_menu') ?>
                <div class="project-filter">
                    <ul class="links">
                        <li>
                            <a href="<?= $this->Url->build(['_name' => 'tasks:today']) ?>">
                                <i class="today"><?= $this->element('icons/clippy16') ?></i>
                                Today
                            </a>
                        </li>
                        <li>
                            <a href="<?= $this->Url->build(['_name' => 'tasks:upcoming']) ?>">
                                <i class="upcoming"><?= $this->element('icons/calendar16') ?></i>
                                Upcoming
                            </a>
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
                    </div>
                </div>
            </div>
            <?= $this->Html->image('docket-logo-translucent.svg', ['width' => 30, 'height' => 30]) ?>
        </div>
        <button
            class="expander"
            title="Show project menu"
        >
            <!-- Need to get the svg for this.
          <Icon icon="kebab" width="large" />
            -->
            ...
        </button>
    </section>
    <section class="content">
        <?= $this->fetch('content'); ?>
    </section>
</main>
