<?php
declare(strict_types=1);

namespace Feeds\Policy;

use App\Model\Entity\User;
use Feeds\Model\Entity\FeedCategory;

/**
 * FeedCategory policy
 */
class FeedCategoryPolicy
{
    /**
     * Check if $user can add FeedCategory
     *
     * @param \App\Model\Entity\User $user The user.
     * @param \Feeds\Model\Entity\FeedCategory $feedCategory
     * @return bool
     */
    public function canAdd(User $user, FeedCategory $feedCategory): bool
    {
        return true;
    }

    /**
     * Check if $user can edit FeedCategory
     *
     * @param \App\Model\Entity\User $user The user.
     * @param \Feeds\Model\Entity\FeedCategory $feedCategory
     * @return bool
     */
    public function canEdit(User $user, FeedCategory $feedCategory): bool
    {
        return $user->id == $feedCategory->user_id;
    }

    /**
     * Check if $user can delete FeedCategory
     *
     * @param \App\Model\Entity\User $user The user.
     * @param \Feeds\Model\Entity\FeedCategory $feedCategory
     * @return bool
     */
    public function canDelete(User $user, FeedCategory $feedCategory): bool
    {
        return $user->id == $feedCategory->user_id;
    }

    /**
     * Check if $user can view FeedCategory
     *
     * @param \App\Model\Entity\User $user The user.
     * @param \Feeds\Model\Entity\FeedCategory $feedCategory
     * @return bool
     */
    public function canView(User $user, FeedCategory $feedCategory): bool
    {
        return $user->id == $feedCategory->user_id;
    }
}
