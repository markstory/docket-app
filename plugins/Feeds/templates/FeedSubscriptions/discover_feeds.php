<?php
/**
 * @var string $error
 * @var \App\View\AppView $this
 * @var array<\Feeds\Model\Entity\Feed> $feeds
 */

use Feeds\Model\Entity\FeedSubscription;

$isHtmx = $this->request->is('htmx');
$discoverUrl = ['_name' => 'feedsubscriptions:discover'];

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
<div class="modal-title">
    <?= $this->Html->link(
        $this->element('icons/arrowleft16') . ' Try a different URL',
        $discoverUrl,
        [
            'escape' => false,
            'hx-get' => $this->Url->build($discoverUrl),
            'hx-target' => 'modal-window',
            'class' => 'discover-back',
        ]
    ) ?>
</div>

<?php if ($error) : ?>
<div class="flash-message flash-error">
    <?= $this->element('icons/alert16') ?>
    <?= h($error) ?>
</div>
<?php endif; ?>

<?php if (count($feeds) == 0) : ?>
    <div class="discover-feeds">
        <p class="not-found">
            No feeds discovered.
            <?= $this->Html->link(
                'Try a different URL',
                $discoverUrl,
                [
                    'hx-get' => $this->Url->build($discoverUrl),
                    'hx-target' => 'main.main',
                    'hx-swap' => 'beforeend',
                ]
            ) ?>
        </p>
    </div>
<?php else : ?>
    <div class="discover-feeds feed-list">
        <?php foreach ($feeds as $feed) : ?>
        <div class="feed-item feed-discovered">
            <h4><?= h($feed->default_alias) ?></h4>
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
