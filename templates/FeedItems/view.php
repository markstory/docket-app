<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\FeedItem $feedItem
 */
$this->setLayout('feedreader');
?>
<div class="row">
    <div class="column column-80">
        <div class="feedItems view content">
            <h3><?= h($feedItem->title) ?></h3>
            <table>
                <tr>
                    <th><?= __('Feed') ?></th>
                    <td><?= $feedItem->hasValue('feed') ? $this->Html->link($feedItem->feed->url, ['controller' => 'Feeds', 'action' => 'view', $feedItem->feed->id]) : '' ?></td>
                </tr>
                <tr>
                    <th><?= __('Title') ?></th>
                    <td><?= h($feedItem->title) ?></td>
                </tr>
                <tr>
                    <th><?= __('Thumbnail Image Url') ?></th>
                    <td><?= h($feedItem->thumbnail_image_url) ?></td>
                </tr>
                <tr>
                    <th><?= __('Guid') ?></th>
                    <td><?= h($feedItem->guid) ?></td>
                </tr>
                <tr>
                    <th><?= __('Url') ?></th>
                    <td><?= h($feedItem->url) ?></td>
                </tr>
                <tr>
                    <th><?= __('Id') ?></th>
                    <td><?= $this->Number->format($feedItem->id) ?></td>
                </tr>
                <tr>
                    <th><?= __('Published At') ?></th>
                    <td><?= h($feedItem->published_at) ?></td>
                </tr>
                <tr>
                    <th><?= __('Created') ?></th>
                    <td><?= h($feedItem->created) ?></td>
                </tr>
                <tr>
                    <th><?= __('Modified') ?></th>
                    <td><?= h($feedItem->modified) ?></td>
                </tr>
            </table>
            <div class="text">
                <strong><?= __('Summary') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($feedItem->summary)); ?>
                </blockquote>
            </div>
            <div class="text">
                <strong><?= __('Content') ?></strong>
                <blockquote>
                    <?= $this->Text->autoParagraph(h($feedItem->content)); ?>
                </blockquote>
            </div>
</div>
