<?php
declare(strict_types=1);

namespace Calendar\Model\Entity;

use Cake\ORM\Entity;

/**
 * CalendarProvider Entity
 *
 * @property int $id
 * @property int $user_id
 * @property string $kind
 * @property string $identifier
 * @property string $display_name
 * @property string $access_token
 * @property string $refresh_token
 * @property ?bool $broken_auth
 * @property \Cake\I18n\DateTime $token_expiry
 *
 * @property \App\Model\Entity\User $user
 * @property \Calendar\Model\Entity\CalendarSource[] $calendar_sources
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
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'user_id' => true,
        'kind' => true,
        'identifier' => true,
        'display_name' => true,
        'access_token' => true,
        'refresh_token' => true,
        'token_expiry' => true,
        'user' => true,
        'calendar_sources' => true,
    ];

    protected array $_virtual = ['broken_auth'];

    /**
     * @var array<array-key, string>
     */
    protected array $_hidden = [
        'user_id',
        'access_token',
        'refresh_token',
        'token_expiry',
    ];
}
