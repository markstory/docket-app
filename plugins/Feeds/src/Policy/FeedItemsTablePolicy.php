<?php
declare(strict_types=1);

namespace Feeds\Policy;

use App\Model\Entity\User;
use Cake\ORM\Query\SelectQuery;

/**
 * FeedItems policy
 */
class FeedItemsTablePolicy
{
    public function scopeMarkRead(User $user, SelectQuery $query): SelectQuery
    {
        return $query
            ->innerJoinWith('FeedSubscriptions')
            ->where(['FeedSubscriptions.user_id' => $user->id]);
    }
}
