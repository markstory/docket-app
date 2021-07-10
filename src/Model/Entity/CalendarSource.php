<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * CalendarSource Entity
 *
 * @property int $id
 * @property string $name
 * @property int $calendar_provider_id
 * @property string $provider_id
 * @property string $color
 * @property \Cake\I18n\FrozenTime $last_sync
 * @property string|null $sync_token
 * @property \Cake\I18n\FrozenTime $created
 * @property \Cake\I18n\FrozenTime $modified
 *
 * @property \App\Model\Entity\CalendarProvider $calendar_provider
 * @property \App\Model\Entity\CalendarItem[] $calendar_items
 */
class CalendarSource extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array
     */
    protected $_accessible = [
        'name' => true,
        'calendar_provider_id' => true,
        'provider_id' => true,
        'color' => true,
        'last_sync' => true,
        'sync_token' => true,
        'created' => true,
        'modified' => true,
        'calendar_provider' => true,
        'calendar_items' => true,
    ];
}
