<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * CalendarProvider Entity
 *
 * @property int $id
 * @property int $user_id
 * @property string $kind
 * @property string $identifier
 * @property string $access_token
 * @property string $refresh_token
 * @property \Cake\I18n\FrozenTime $token_expiry
 *
 * @property \App\Model\Entity\User $user
 * @property \App\Model\Entity\CalendarSource[] $calendar_sources
 */
class CalendarProvider extends Entity
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
        'user_id' => true,
        'kind' => true,
        'identifier' => true,
        'access_token' => true,
        'refresh_token' => true,
        'token_expiry' => true,
        'user' => true,
        'calendar_sources' => true,
    ];
}
