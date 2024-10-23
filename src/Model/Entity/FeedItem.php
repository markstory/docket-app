<?php
declare(strict_types=1);

namespace App\Model\Entity;

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
 * @property \Cake\I18n\DateTime $published_at
 * @property string|null $thumbnail_image_url
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime|null $modified
 *
 * @property \App\Model\Entity\Feed $feed
 * @property \App\Model\Entity\FeedSubscription[] $feed_subscriptions
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
        'published_at' => true,
        'thumbnail_image_url' => true,
        'created' => false,
        'modified' => false,
        'feed' => false,
        'feed_subscriptions' => false,
    ];
}
