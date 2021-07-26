<?php
declare(strict_types=1);

namespace App\Policy;

use App\Model\Entity\User;
use Cake\ORM\Query;

/**
 * CalendarProvidersTable policy
 */
class CalendarProvidersTablePolicy
{
    /**
     * @param \App\Model\Entity\User $user
     * @param \Cake\ORM\Query $query
     */
    public function scopeIndex(User $user, Query $query): Query
    {
        return $query->where(['CalendarProviders.user_id' => $user->id]);
    }
}
