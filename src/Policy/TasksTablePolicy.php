<?php
declare(strict_types=1);

namespace App\Policy;

use App\Model\Entity\User;
use Cake\ORM\Query;

/**
 * Tasks policy
 */
class TasksTablePolicy
{
    public function scopeIndex(User $user, Query $query): Query
    {
        return $query->innerJoinWith('Projects')
            ->where(['Projects.user_id' => $user->id]);
    }
}
