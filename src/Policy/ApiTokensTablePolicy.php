<?php
declare(strict_types=1);

namespace App\Policy;

use App\Model\Entity\User;
use Cake\ORM\Query;

/**
 * ApiTokens policy
 */
class ApiTokensTablePolicy
{
    public function scopeIndex(User $user, Query $query): Query
    {
        return $query->where(['ApiTokens.user_id' => $user->id]);
    }
}
