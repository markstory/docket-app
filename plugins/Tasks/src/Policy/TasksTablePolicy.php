<?php
declare(strict_types=1);

namespace Tasks\Policy;

use App\Model\Entity\User;
use Cake\ORM\Query;

/**
 * Tasks policy
 */
class TasksTablePolicy
{
    /**
     * @param \App\Model\Entity\User $user
     * @param \Cake\ORM\Query $query
     */
    public function scopeIndex(User $user, Query $query): Query
    {
        return $query->innerJoinWith('Projects')
            ->where(['Projects.user_id' => $user->id]);
    }
}
