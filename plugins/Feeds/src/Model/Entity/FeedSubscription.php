<?php
declare(strict_types=1);

namespace Feeds\Model\Entity;

use Cake\ORM\Entity;

/**
 * FeedSubscription Entity
 *
 * Contains all the persistent preferences for a user's subscription.
 * Subscription view preferences are local to the subscription.
 *
 * @property int $id
 * @property int $feed_id
 * @property int $user_id
 * @property int $feed_category_id
 * @property string $alias
 * @property int $ranking
 * @property int $unread_item_count
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime|null $modified
 *
 * @property \Feeds\Model\Entity\Feed $feed
 * @property \Feeds\Model\Entity\User $user
 * @property \Feeds\Model\Entity\FeedCategory $feed_category
 * @property \Feeds\Model\Entity\SavedFeedItem[] $saved_feed_items
 * @property \Feeds\Model\Entity\FeedItem[] $feed_items
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
        'user_id' => false,
        'feed_category_id' => true,
        'alias' => true,
        'ranking' => true,
        'unread_item_count' => false,
        'created' => false,
        'modified' => false,
        'feed' => false,
        'user' => false,
        'feed_category' => true,
        'saved_feed_items' => false,
        'feed_items' => false,
    ];
}
