<?php
declare(strict_types=1);
/**
 * Renders a view with feed category sidebar
 *
 * @var \App\Model\Entity\User $identity
 */
$feedAddUrl = $this->Url->build(['_name' => 'feedsubscriptions:add']);
$categoryAddUrl = $this->Url->build(['_name' => 'feedcategories:add']);

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
?>
<div class="layout-three-quarter" data-testid="loggedin" hx-ext="hotkeys">
    <side-bar class="sidebar" data-expanded="false">
        <div class="menu">
            <div>
                <?= $this->element('profile_menu') ?>
                <div class="project-filter">
                    <ul class="links">
                        <li class="icon-secondary">
                            <?= $this->Html->link(
                                $this->element('icons/clippy16') . ' Today',
                                ['_name' => 'tasks:today'],
                                [
                                    'escape' => false,
                                    'class' => 'button-muted button',
                                    'data-hotkey' => 't',
                                    'hx-boost' => '1',
                                ]
                            ) ?>
                        </li>
                        <li class="icon-today">
                            <?= $this->Html->link(
                                $this->element('icons/rss16') . ' All Feeds',
                                ['_name' => 'feedsubscriptions:index'],
                                [
                                    'escape' => false,
                                    'class' => 'button-muted button',
                                    'data-hotkey' => 'f',
                                    'hx-boost' => '1',
                                ]
                            ) ?>
                        </li>
                    </ul>
                    <h3>Categories</h3>
                    <?= $this->cell('FeedCategoryMenu', ['identity' => $identity]) ?>

                    <ul class="links">
                        <li>
                            <a class="action-primary"
                                href="<?= $feedAddUrl ?>"
                                hx-get="<?= $feedAddUrl ?>"
                                hx-target="main.main"
                                hx-swap="beforeend"
                            >
                                <?= $this->element('icons/plus16') ?>
                                New Feed
                            </a>
                        </li>
                        <li>
                            <a class="action-secondary"
                               href="<?= $categoryAddUrl ?>"
                               hx-get="<?= $categoryAddUrl ?>"
                               hx-target="main.main"
                               hx-swap="beforeend"
                            >
                                <?= $this->element('icons/directory16') ?>
                                New Category
                            </a>
                        </li>
                    </ul>
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
            <?= $this->Html->image('docket-logo-translucent.svg', ['width' => 30, 'height' => 30]) ?>
        </div>
        <button class="expander" title="Show sidebar menu" data-expander="1">
            <?= $this->element('icons/kebab16') ?>
        </button>
    </side-bar>
    <section class="content">
        <?= $this->fetch('content'); ?>
    </section>
</div>