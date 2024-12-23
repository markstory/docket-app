<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * SavedFeedItem Entity
 *
 * @property int $id
 * @property int $feed_subscription_id
 * @property string $title
 * @property string $body
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime|null $modified
 *
 * @property \App\Model\Entity\FeedSubscription $feed_subscription
 */
class SavedFeedItem extends Entity
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
        'title' => true,
        'body' => true,
        'created' => true,
        'modified' => true,
        'feed_subscription' => true,
    ];
}
