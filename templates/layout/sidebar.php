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
                <?= $this->cell('ProfileMenu', ) ?>
                <!-- add profile and project menu -->
              <ProfileMenu />
              <ProjectFilter />
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
