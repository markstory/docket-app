<?php
declare(strict_types=1);

namespace App\Policy;

use App\Model\Table\TodoItemsTable;
use App\Model\Entity\User;
use Cake\ORM\Query;

/**
 * TodoItems policy
 */
class TodoItemsTablePolicy
{
    public function scopeIndex(User $user, Query $query): Query
    {
        return $query->innerJoinWith('Projects')
            ->where(['Projects.user_id' => $user->id]);
    }
}
