<?php
declare(strict_types=1);
/**
 * @var \App\Model\Entity\FeedCategory $feedCategory
 */
// configure layout
$this->set('closable', false);

$this->setLayout('modal');

echo $this->element('confirm_dialog', [
    'target' => ['_name' => 'feedcategories:delete', 'id' => $feedCategory->id],
    'title' => 'Are you sure?',
    'description' => 'Feeds in this category will also be removed',
]);
