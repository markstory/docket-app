<?php
declare(strict_types=1);

namespace App\View\Helper;

use Cake\I18n\FrozenDate;
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
    protected $_defaultConfig = [];

    public function formatCompact(FrozenDate $date): string
    {
        $diff = FrozenDate::today()->diffInDays($date, false);
        // In the past? Show the date
        if ($diff < -90) {
            return $date->i18nFormat('MMM d yyyy');
        }
        if ($diff < 0) {
            return $date->i18nFormat('MMM d');
        }
        if ($diff < 1) {
            return 'Today';
        }
        if ($diff < 2) {
            return 'Tomorrow';
        }
        if ($diff < 7) {
            return $date->i18nFormat('EEEE');
        }

        return $date->i18nFormat('MMM d');
    }
}
