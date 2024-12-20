<?php
/**
 * @var \App\View\AppView $this
 * @var array<\App\Model\Entity\Feed> $feeds
 */

use App\Model\Entity\FeedSubscription;

$isHtmx = $this->request->is('htmx');

$this->setLayout('feedreader');
if ($isHtmx) {
    $this->setLayout('modal');
}

$this->assign('title', 'Discover Feeds');
?>
<div class="modal-title">
    <h2>Discover Feeds</h2>
    <button class="modal-close" modal-close="true">&#x2715;</button>
</div>
<?php
echo $this->Form->create(null, [
    'hx-post' => $this->Url->build(['_name' => 'feedsubscriptions:discover', '?' => $this->request->getQueryParams()]),
    'hx-target' => 'modal-window',
    'hx-swap' => 'outerHTML',
]);
echo $this->Form->control('url', ['label' => 'Page or Domain']);
?>
<div class="button-bar">
    <?= $this->Form->submit('Continue', ['class' => 'button button-primary']) ?>
    <a href="<?= h($referer) ?>" class="button button-muted">
        Cancel
    </a>
</div>
<?= $this->Form->end() ?>

<?php if ($this->request->getData('url')) : ?>
    <?php if (count($feeds) == 0) : ?>
        <div class="discover-feeds feed-list">
            <p class="empty">No feeds. Provide a URL above to get started.</p>
        </div>
    <?php else : ?>
        <div class="discover-feeds feed-list">
            <?php foreach ($feeds as $feed) : ?>
            <div class="feed-item feed-discovered">
                <h4><?= h($feed->default_alias) ?></h4>
                <span class="alias"><?= h($feed->default_alias) ?></span>
                <span class="url"><?= h($feed->url) ?></span>
                <?php
                $feedSub = new FeedSubscription();
                echo $this->Form->create($feedSub, [
                    'url' => ['_name' => 'feedsubscriptions:add'],
                ]);
                echo $this->Form->control('feed_category_id', [
                    'options' => $feedCategories,
                    'value' => $this->request->getQuery('feed_category_id'),
                ]);
                echo $this->Form->control('favicon_url', ['value' => $feed->favicon_url, 'type' => 'hidden']);
                echo $this->Form->control('url', ['value' => $feed->url, 'type' => 'hidden']);
                echo $this->Form->control('alias', [
                    'value' => $feed->default_alias ?: $feed->url,
                    'type' => 'hidden',
                ]);
                echo $this->Form->button(
                    $this->element('icons/plus16') . ' Add',
                    [
                        'type' => 'submit',
                        'escapeTitle' => false,
                        'class' => 'button-primary',
                    ]
                );
                echo $this->Form->end();
                ?>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>
