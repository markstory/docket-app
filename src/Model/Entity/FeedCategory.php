<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * FeedCategory Entity
 *
 * @property int $id
 * @property int $user_id
 * @property string $title
 * @property int $color
 * @property int $ranking
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime|null $modified
 *
 * @property \App\Model\Entity\User $user
 * @property \App\Model\Entity\FeedSubscription[] $feed_subscriptions
 */
class FeedCategory extends Entity
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
        'user_id' => true,
        'title' => true,
        'color' => true,
        'ranking' => true,
        'created' => true,
        'modified' => true,
        'user' => true,
        'feed_subscriptions' => true,
    ];
}