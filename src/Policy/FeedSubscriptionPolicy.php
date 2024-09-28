<?php
declare(strict_types=1);

namespace App\Policy;

use App\Model\Entity\FeedSubscription;
use Authorization\IdentityInterface;

/**
 * FeedSubscription policy
 */
class FeedSubscriptionPolicy
{
    /**
     * Check if $user can add FeedSubscription
     *
     * @param \Authorization\IdentityInterface $user The user.
     * @param \App\Model\Entity\FeedSubscription $feedSubscription
     * @return bool
     */
    public function canAdd(IdentityInterface $user, FeedSubscription $feedSubscription)
    {
        return $feedSubscription->user_id == $user->id;
    }

    /**
     * Check if $user can edit FeedSubscription
     *
     * @param \Authorization\IdentityInterface $user The user.
     * @param \App\Model\Entity\FeedSubscription $feedSubscription
     * @return bool
     */
    public function canEdit(IdentityInterface $user, FeedSubscription $feedSubscription)
    {
        return $feedSubscription->user_id == $user->id;
    }

    /**
     * Check if $user can delete FeedSubscription
     *
     * @param \Authorization\IdentityInterface $user The user.
     * @param \App\Model\Entity\FeedSubscription $feedSubscription
     * @return bool
     */
    public function canDelete(IdentityInterface $user, FeedSubscription $feedSubscription)
    {
        return $feedSubscription->user_id == $user->id;
    }

    /**
     * Check if $user can view FeedSubscription
     *
     * @param \Authorization\IdentityInterface $user The user.
     * @param \App\Model\Entity\FeedSubscription $feedSubscription
     * @return bool
     */
    public function canView(IdentityInterface $user, FeedSubscription $feedSubscription)
    {
        return $feedSubscription->user_id == $user->id;
    }
}
