<?php
declare(strict_types=1);

namespace Calendar\Model\Entity;

use Cake\Core\Configure;
use Cake\I18n\Date;
use Cake\I18n\DateTime;
use Cake\ORM\Entity;

/**
 * CalendarItem Entity
 *
 * @property int $id
 * @property int $calendar_source_id
 * @property string $provider_id
 * @property string $title
 * @property \Cake\I18n\DateTime|null $start_time Stored in UTC
 * @property \Cake\I18n\DateTime|null $end_time Stored in UTC
 * @property \Cake\I18n\Date|null $start_date Stored as user timezone.
 * @property \Cake\I18n\Date|null $end_date Stored as user timezone.
 * @property bool $all_day
 * @property string|null $html_link
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 * @property string $color
 * @property string $color_hex
 *
 * @property \App\Model\Entity\CalendarSource $calendar_source
 * @property \App\Model\Entity\Provider $provider
 */
class CalendarItem extends Entity
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
        'provider_id' => true,
        'title' => true,
        'start_date' => true,
        'start_time' => true,
        'end_date' => true,
        'end_time' => true,
        'all_day' => true,
        'html_link' => true,
        'created' => true,
        'modified' => true,
        'calendar_source' => true,
        'provider' => true,
    ];

    /**
     * @var array<array-key, string>
     */
    protected array $_hidden = ['calendar_source'];

    /**
     * @var array<array-key, string>
     */
    protected array $_virtual = ['color'];

    public function getStart(): Date|DateTime
    {
        if ($this->start_date) {
            return $this->start_date;
        }

        return $this->start_time;
    }

    public function getEnd(): Date|DateTime
    {
        if ($this->end_date) {
            return $this->end_date;
        }

        return $this->end_time;
    }

    public function getKey(?string $timezone = null): string
    {
        if ($this->start_date) {
            return $this->start_date->format('Y-m-d');
        }
        $start = $this->start_time;
        if (!$start) {
            return '';
        }
        if ($timezone) {
            $start = $start->setTimezone($timezone);
        }

        return $start->format('Y-m-d');
    }

    public function getFormattedTime(?string $timezone = null): string
    {
        if ($this->start_date) {
            return '';
        }
        $start = $this->start_time;
        if (!$start) {
            return '';
        }
        if ($timezone) {
            $start = $start->setTimezone($timezone);
        }

        return $start->format('H:i');
    }

    protected function _getColor()
    {
        if (isset($this->calendar_source)) {
            return $this->calendar_source->color;
        }

        return 1;
    }

    protected function _getColorHex(): string
    {
        $colors = Configure::read('Colors');

        return $colors[$this->color]['code'];
    }
}
