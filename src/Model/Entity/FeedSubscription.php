<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * FeedSubscription Entity
 *
 * @property int $id
 * @property int $feed_id
 * @property int $user_id
 * @property int $feed_category_id
 * @property string $alias
 * @property int $ranking
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime|null $modified
 *
 * @property \App\Model\Entity\Feed $feed
 * @property \App\Model\Entity\User $user
 * @property \App\Model\Entity\FeedCategory $feed_category
 * @property \App\Model\Entity\SavedFeedItem[] $saved_feed_items
 * @property \App\Model\Entity\FeedItem[] $feed_items
 */
class FeedSubscription extends Entity
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
        'feed_id' => true,
        'user_id' => true,
        'feed_category_id' => true,
        'alias' => true,
        'ranking' => true,
        'created' => true,
        'modified' => true,
        'feed' => true,
        'user' => true,
        'feed_category' => true,
        'saved_feed_items' => true,
        'feed_items' => true,
    ];
}