<?php
declare(strict_types=1);

namespace Feeds\Model\Entity;

use Cake\ORM\Entity;

/**
 * FeedItem Entity
 *
 * @property int $id
 * @property int $feed_id
 * @property string $guid
 * @property string $url
 * @property string $title
 * @property string $summary
 * @property string $content
 * @property string $author
 * @property \Cake\I18n\DateTime $published_at
 * @property string|null $thumbnail_image_url
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime|null $modified
 *
 * @property \Feeds\Model\Entity\Feed $feed
 * @property \Feeds\Model\Entity\FeedSubscription $feed_subscription
 * @property \Feeds\Model\Entity\FeedItemUser|null $feed_item_user
 */
class FeedItem extends Entity
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
        'feed_id' => false,
        'guid' => true,
        'url' => true,
        'title' => true,
        'summary' => true,
        'content' => true,
        'author' => true,
        'published_at' => true,
        'thumbnail_image_url' => true,
        'created' => false,
        'modified' => false,
        'feed' => false,
        'feed_subscription' => false,
        'feed_item_user' => false,
    ];
}
