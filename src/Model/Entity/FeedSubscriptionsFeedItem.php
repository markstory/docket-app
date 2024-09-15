<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * FeedSubscriptionsFeedItem Entity
 *
 * @property int $id
 * @property int $feed_subscription_id
 * @property int $feed_item_id
 * @property bool $is_read
 * @property bool $is_saved
 * @property bool $is_hidden
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime|null $modified
 *
 * @property \App\Model\Entity\FeedSubscription $feed_subscription
 * @property \App\Model\Entity\FeedItem $feed_item
 */
class FeedSubscriptionsFeedItem extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'feed_subscription_id' => true,
        'feed_item_id' => true,
        'is_read' => true,
        'is_saved' => true,
        'is_hidden' => true,
        'created' => true,
        'modified' => true,
        'feed_subscription' => true,
        'feed_item' => true,
    ];
}
