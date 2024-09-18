<?php
declare(strict_types=1);

namespace App\Policy;

use App\Model\Entity\User;
use Cake\ORM\Query\SelectQuery;

/**
 * FeedCategories policy
 */
class FeedCategoriesTablePolicy
{
    public function scopeIndex(User $user, SelectQuery $query): SelectQuery
    {
        return $query->where(['FeedCategories.user_id' => $user->id]);
    }
}
