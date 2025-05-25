<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\Feeds\Model\Entity\FeedCategory> $feedCategories
 */
$this->setLayout('sidebar');

$addUrl = $this->Url->build(['_name' => 'feedcategories:add']);
?>
<h3 class="heading-icon">
    <?= __('Feed Categories') ?>
    <?= $this->Html->link(
        $this->element('icons/plus16'),
        ['action' => 'add'],
        [
            'escape' => false,
            'class' => 'button-icon-primary',
            'data-testid' => 'add-task',
            'hx-get' => $addUrl,
            'hx-target' => 'main.main',
            'hx-swap' => 'beforeend',
        ]
    ) ?>
</h3>
<div class="table-responsive">
    <table>
        <thead>
            <tr>
                <th><?= $this->Paginator->sort('title') ?></th>
                <th><?= $this->Paginator->sort('created') ?></th>
                <th><?= $this->Paginator->sort('modified') ?></th>
                <th class="actions"><?= __('Actions') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php
            foreach ($feedCategories as $feedCategory):
                $menuId = "feed-category-{$feedCategory->id}";
                $deleteConfirmUrl = ['_name' => 'feedcategories:deleteconfirm', 'id' => $feedCategory->id];
            ?>
            <tr>
                <td>
                    <?= $this->element('icons/dot16', ['color' => $feedCategory->color_hex]) ?>
                    <?= h($feedCategory->title) ?>
                </td>
                <td><?= h($feedCategory->created) ?></td>
                <td><?= h($feedCategory->modified) ?></td>
                <td class="actions">
                    <drop-down>
                        <button
                            class="button-icon button-default"
                            aria-haspopup="true"
                            aria-controls="<?= h($menuId) ?>"
                            type="button"
                        >
                            <?= $this->element('icons/kebab16') ?>
                        </button>
                        <drop-down-menu id="<?= h($menuId) ?>">
                            <?= $this->Html->link(
                                $this->element('icons/pencil16') . ' Edit',
                                ['action' => 'edit', 'id' => $feedCategory->id],
                                [
                                    'class' => 'icon-edit',
                                    'escape' => false,
                                    'role' => 'menuitem',
                                ]
                            ) ?>
                            <?= $this->Html->link(
                                $this->element('icons/trash16') . ' Delete Category',
                                $deleteConfirmUrl,
                                [
                                    'class' => 'icon-delete',
                                    'escape' => false,
                                    'role' => 'menuitem',
                                    'data-testid' => 'delete',
                                    'dropdown-close' => true,
                                    'hx-get' => $this->Url->build($deleteConfirmUrl),
                                    'hx-target' => 'body',
                                    'hx-swap' => 'beforeend',
                                ]
                            ) ?>
                        </drop-down-menu>
                    </drop-down>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<div class="paginator">
    <ul class="pagination">
        <?= $this->Paginator->first('<< ' . __('first')) ?>
        <?= $this->Paginator->prev('< ' . __('previous')) ?>
        <?= $this->Paginator->numbers() ?>
        <?= $this->Paginator->next(__('next') . ' >') ?>
        <?= $this->Paginator->last(__('last') . ' >>') ?>
    </ul>
    <p><?= $this->Paginator->counter(__('Page {{page}} of {{pages}}, showing {{current}} record(s) out of {{count}} total')) ?></p>
</div>
