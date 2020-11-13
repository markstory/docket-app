<?php
declare(strict_types=1);

namespace App\Policy;

use App\Model\Table\ProjectsTable;
use App\Model\Entity\User;
use Authorization\IdentityInterface;
use Cake\ORM\Query;

/**
 * Projects policy
 */
class ProjectsTablePolicy
{
    public function scopeIndex(User $user, Query $query): Query
    {
        return $query->where(['Projects.user_id' => $user->id]);
    }
}
