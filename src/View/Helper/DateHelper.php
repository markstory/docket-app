<?php
declare(strict_types=1);

namespace App\View\Helper;

use Cake\I18n\Date;
use Cake\View\Helper;

/**
 * Date helper
 */
class DateHelper extends Helper
{
    /**
     * Default configuration.
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'timezone' => null,
    ];

    /**
     * Get today in the user's timezone.
     */
    public function today(): Date
    {
        return Date::today($this->getConfig('timezone'));
    }

    /**
     * Format a date into the compact date format used across the app.
     */
    public function formatCompact(?Date $date, bool $evening = false): string
    {
        if ($date === null) {
            return 'No due date';
        }
        $diff = $this->today()->diffInDays($date, false);
        // In the past? Show the date
        if ($diff < -90) {
            return (string)$date->i18nFormat('MMM d yyyy');
        }
        if ($diff < 0) {
            return (string)$date->i18nFormat('MMM d');
        }
        // TODO should this include the icon?
        if ($diff < 1 && $evening) {
            return 'This evening';
        }
        if ($diff < 1) {
            return 'Today';
        }
        if ($diff < 2) {
            return 'Tomorrow';
        }
        if ($diff < 7) {
            return (string)$date->i18nFormat('cccc');
        }

        return (string)$date->i18nFormat('MMM d');
    }

    public function formatDateHeading(Date $date): array
    {
        $delta = $date->diffInDays($this->today());
        $shortDate = $date->i18nFormat('MMM d');
        if ($delta < 1) {
            return ['Today', $shortDate];
        } elseif ($delta < 2) {
            return ['Tomorrow', $shortDate];
        } elseif ($delta < 7) {
            return [$date->i18nFormat('EEEE'), $shortDate];
        }

        return [$shortDate, ''];
    }
}
