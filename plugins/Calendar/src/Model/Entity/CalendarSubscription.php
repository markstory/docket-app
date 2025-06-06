<?php
declare(strict_types=1);

namespace Calendar\Model\Entity;

use Cake\ORM\Entity;

/**
 * CalendarSubscription Entity
 *
 * @property int $id
 * @property int $calendar_source_id
 * @property string $identifier
 * @property string $resource_id
 * @property string $verifier
 * @property string $channel_token
 * @property \Cake\I18n\DateTime|null $expires_at
 * @property \Cake\I18n\DateTime|null $created
 * @property \Cake\I18n\DateTime|null $modified
 *
 * @property \Calendar\Model\Entity\CalendarSource $calendar_source
 */
class CalendarSubscription extends Entity
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
        'calendar_source_id' => true,
        'identifier' => true,
        'resource_id' => true,
        'verifier' => true,
        'expires_at' => true,
        'created' => true,
        'modified' => true,
        'calendar_source' => true,
    ];

    protected function _getChannelToken(): string
    {
        return http_build_query(['verifier' => $this->verifier]);
    }
}
