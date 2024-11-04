<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * FeedItemUser Entity
 *
 * @property int $id
 * @property int $user_id
 * @property int $feed_item_id
 * @property \Cake\I18n\DateTime $read_at
 * @property \Cake\I18n\DateTime $saved_at
 *
 * @property \App\Model\Entity\User $user
 * @property \App\Model\Entity\FeedItem $feed_item
 */
class FeedItemUser extends Entity
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
        'feed_item_id' => true,
        'read_at' => true,
        'saved_at' => true,
        'user' => true,
        'feed_item' => true,
    ];
}
