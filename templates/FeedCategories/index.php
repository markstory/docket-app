<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\FeedCategory> $feedCategories
 */
?>
<div class="feedCategories index content">
    <?= $this->Html->link(__('Add Category'), ['action' => 'add'], ['class' => 'button float-right']) ?>
    <h3><?= __('Feed Categories') ?></h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th><?= $this->Paginator->sort('color') ?></th>
                    <th><?= $this->Paginator->sort('title') ?></th>
                    <th><?= $this->Paginator->sort('created') ?></th>
                    <th><?= $this->Paginator->sort('modified') ?></th>
                    <th class="actions"><?= __('Actions') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($feedCategories as $feedCategory): ?>
                <tr>
                    <td><?= $this->Number->format($feedCategory->color) ?></td>
                    <td><?= h($feedCategory->title) ?></td>
                    <td><?= h($feedCategory->created) ?></td>
                    <td><?= h($feedCategory->modified) ?></td>
                    <td class="actions">
                        <?= $this->Html->link(__('Edit'), ['action' => 'edit', $feedCategory->id]) ?>
                        <?= $this->Form->postLink(__('Delete'), ['action' => 'delete', $feedCategory->id], ['confirm' => __('Are you sure you want to delete {0}?', $feedCategory->title)]) ?>
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
</div>
