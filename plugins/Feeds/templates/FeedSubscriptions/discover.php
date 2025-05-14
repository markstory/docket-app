<?php
/**
 * Step 1 of feed discovery
 *
 * @var string $error
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

<?php if ($error) : ?>
<div class="flash-message flash-error">
    <?= $this->element('icons/alert16') ?>
    <?= h($error) ?>
</div>
<?php endif; ?>
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
        <div class="discover-feeds">
            <p class="empty">No feeds were found. Provide a URL above to get started.</p>
        </div>
    <?php endif; ?>
<?php endif; ?>
