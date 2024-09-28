<?php
declare(strict_types=1);

namespace App\Policy;

use App\Model\Entity\User;
use Cake\ORM\Query\SelectQuery;

/**
 * FeedSubscriptions policy
 */
class FeedSubscriptionsTablePolicy
{
    public function scopeIndex(User $user, SelectQuery $query): SelectQuery
    {
        return $query->where(['FeedSubscriptions.user_id' => $user->id]);
    }
}
