<?php
declare(strict_types=1);

namespace App\Policy;

use App\Model\Entity\User;
use Cake\ORM\Query\SelectQuery;

/**
 * Projects policy
 */
class ProjectsTablePolicy
{
    public function scopeIndex(User $user, SelectQuery $query): SelectQuery
    {
        return $query->where(['Projects.user_id' => $user->id]);
    }
}
