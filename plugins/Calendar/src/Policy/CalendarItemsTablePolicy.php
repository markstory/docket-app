<?php
declare(strict_types=1);

namespace Calendar\Policy;

use App\Model\Entity\User;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Query;

/**
 * CalendarItems policy
 */
class CalendarItemsTablePolicy
{
    use LocatorAwareTrait;

    public function scopeIndex(User $user, Query $query): Query
    {
        $sources = $this->fetchTable('Calendar.CalendarSources');

        $sourceQuery = $sources
            ->subquery()
            ->select(['CalendarSources.id'])
            ->innerJoinWith('CalendarProviders')
            ->where(['CalendarProviders.user_id' => $user->id]);

        return $query->where(['CalendarItems.calendar_source_id IN' => $sourceQuery]);
    }
}
