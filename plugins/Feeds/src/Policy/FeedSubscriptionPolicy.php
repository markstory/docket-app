<?php
declare(strict_types=1);

namespace Feeds\Policy;

use App\Model\Entity\User;
use Feeds\Model\Entity\FeedSubscription;

/**
 * FeedSubscription policy
 */
class FeedSubscriptionPolicy
{
    /**
     * Check if $user can add FeedSubscription
     *
     * @param \App\Model\Entity\User $user The user.
     * @param \Feeds\Model\Entity\FeedSubscription $feedSubscription
     * @return bool
     */
    public function canAdd(User $user, FeedSubscription $feedSubscription): bool
    {
        return $feedSubscription->user_id == $user->id;
    }

    /**
     * Check if $user can edit FeedSubscription
     *
     * @param \App\Model\Entity\User $user The user.
     * @param \Feeds\Model\Entity\FeedSubscription $feedSubscription
     * @return bool
     */
    public function canEdit(User $user, FeedSubscription $feedSubscription): bool
    {
        return $feedSubscription->user_id == $user->id;
    }

    /**
     * Check if $user can delete FeedSubscription
     *
     * @param \App\Model\Entity\User $user The user.
     * @param \Feeds\Model\Entity\FeedSubscription $feedSubscription
     * @return bool
     */
    public function canDelete(User $user, FeedSubscription $feedSubscription): bool
    {
        return $feedSubscription->user_id == $user->id;
    }

    /**
     * Check if $user can view FeedSubscription
     *
     * @param \App\Model\Entity\User $user The user.
     * @param \Feeds\Model\Entity\FeedSubscription $feedSubscription
     * @return bool
     */
    public function canView(User $user, FeedSubscription $feedSubscription): bool
    {
        return $feedSubscription->user_id == $user->id;
    }
}
