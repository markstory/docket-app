<?php
/**
 * @var \App\View\AppView $this
 * @var array<\App\Model\Entity\Feed> $feeds
 */

use App\Model\Entity\FeedSubscription;

$isHtmx = $this->request->is('htmx');

$this->setLayout('sidebar');
if ($isHtmx) {
    $this->setLayout('sheet');
}

$this->assign('title', 'Discover Feeds');
?>
<div class="modal-title">
    <h2>Discover Feeds</h2>
    <button class="modal-close" modal-close="true">&#x2715;</button>
</div>
<?php
// TODO this form might need to be compact
echo $this->Form->create(null);
echo $this->Form->control('url', ['label' => 'Page or Domain']);
?>
<div class="button-bar">
    <?= $this->Form->submit('Continue', ['class' => 'button button-primary']) ?>
    <a href="<?= h($referer) ?>" class="button button-muted">
        Cancel
    </a>
</div>
<?= $this->Form->end() ?>

<?php if (count($feeds) == 0) : ?>
<div class="discover-feeds feed-list">
    <p class="empty">No feeds. Provide a URL above to get started.</p>
</div>
<?php else : ?>
<div class="discover-feeds feed-list">
    <?php foreach ($feeds as $feed) : ?>
    <div class="feed-item feed-discovered">
        <h4><?= h($feed->default_alias) ?></h4>
        <span class="url"><?= h($feed->default_alias) ?></span>
        <?php
        $feedSub = new FeedSubscription();
        echo $this->Form->create($feedSub, [
            'url' => ['_name' => 'feedsubscriptions:add'],
        ]);
        echo $this->Form->control('feed_category_id', [
            'options' => $feedCategories,
        ]);
        echo $this->Form->control('url', ['value' => $feed->url, 'type' => 'hidden']);
        echo $this->Form->control('alias', [
            'value' => $feed->default_alias || $feed->url,
            'type' => 'hidden',
        ]);
        echo $this->Form->end();
        ?>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
