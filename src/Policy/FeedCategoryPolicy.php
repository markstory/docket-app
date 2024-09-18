<?php
declare(strict_types=1);

namespace App\Policy;

use App\Model\Entity\FeedCategory;
use Authorization\IdentityInterface;

/**
 * FeedCategory policy
 */
class FeedCategoryPolicy
{
    /**
     * Check if $user can add FeedCategory
     *
     * @param \Authorization\IdentityInterface $user The user.
     * @param \App\Model\Entity\FeedCategory $feedCategory
     * @return bool
     */
    public function canAdd(IdentityInterface $user, FeedCategory $feedCategory)
    {
        return true;
    }

    /**
     * Check if $user can edit FeedCategory
     *
     * @param \Authorization\IdentityInterface $user The user.
     * @param \App\Model\Entity\FeedCategory $feedCategory
     * @return bool
     */
    public function canEdit(IdentityInterface $user, FeedCategory $feedCategory)
    {
        return $user->id == $feedCategory->user_id;
    }

    /**
     * Check if $user can delete FeedCategory
     *
     * @param \Authorization\IdentityInterface $user The user.
     * @param \App\Model\Entity\FeedCategory $feedCategory
     * @return bool
     */
    public function canDelete(IdentityInterface $user, FeedCategory $feedCategory)
    {
        return $user->id == $feedCategory->user_id;
    }

    /**
     * Check if $user can view FeedCategory
     *
     * @param \Authorization\IdentityInterface $user The user.
     * @param \App\Model\Entity\FeedCategory $feedCategory
     * @return bool
     */
    public function canView(IdentityInterface $user, FeedCategory $feedCategory)
    {
        return $user->id == $feedCategory->user_id;
    }
}
