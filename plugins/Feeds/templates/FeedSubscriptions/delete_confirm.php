<?php
declare(strict_types=1);
/**
 * @var \App\Model\Entity\FeedSubscription $feedSubscription
 * @var \Cake\View\View $this
 */
echo $this->element('confirm_dialog', [
    'target' => ['_name' => 'feedsubscriptions:delete', 'id' => $feedSubscription->id],
    'title' => 'Are you sure?',
    'description' => 'You can always subscribe again later.',
]);
