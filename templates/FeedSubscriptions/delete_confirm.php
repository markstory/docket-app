<?php
declare(strict_types=1);
/**
 * @var \App\Model\Entity\FeedSubscription $feedSubscription
 */
// configure layout
$this->set('closable', false);
$this->set('open', true);

$this->setLayout('modal');

echo $this->element('confirm_dialog', [
    'target' => ['_name' => 'feedsubscriptions:delete', 'id' => $feedSubscription->id],
    'title' => 'Are you sure?',
    'description' => 'You can always subscribe again later.',
]);
