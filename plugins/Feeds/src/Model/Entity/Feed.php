<?php
declare(strict_types=1);

namespace Feeds\Model\Entity;

use Cake\ORM\Entity;

/**
 * Feed Entity
 *
 * @property int $id
 * @property string $url
 * @property string $favicon_url
 * @property int $refresh_interval
 * @property \Cake\I18n\DateTime|null $last_refresh
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime|null $modified
 *
 * @property \Feeds\Model\Entity\FeedItem[] $feed_items
 * @property \Feeds\Model\Entity\FeedSubscription[] $feed_subscriptions
 */
class Feed extends Entity
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
        'default_alias' => true,
        'url' => true,
        'favicon_url' => true,
        'refresh_interval' => true,
        'last_refresh' => true,
        'created' => true,
        'modified' => true,
        'feed_items' => false,
        'feed_subscriptions' => false,
    ];
}
