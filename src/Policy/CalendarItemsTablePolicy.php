<?php
declare(strict_types=1);

namespace App\Policy;

use App\Model\Entity\User;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;

/**
 * CalendarItems policy
 */
class CalendarItemsTablePolicy
{
    public function scopeIndex(User $user, Query $query): Query
    {
        $sources = TableRegistry::get('CalendarSources');
        $sourceQuery = $sources
            ->subquery()
            ->select(['CalendarSources.id'])
            ->innerJoinWith('CalendarProviders')
            ->where(['CalendarProviders.user_id' => $user->id]);

        return $query->where(['CalendarItems.calendar_source_id IN' => $sourceQuery]);
    }
}
