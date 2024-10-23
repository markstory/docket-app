<?php
declare(strict_types=1);

namespace App\Policy;

use App\Model\Entity\FeedItem;
use Authorization\IdentityInterface;

/**
 * FeedItem policy
 */
class FeedItemPolicy
{
    /**
     * Check if $user can add FeedItem
     *
     * @param \Authorization\IdentityInterface $user The user.
     * @param \App\Model\Entity\FeedItem $feedItem
     * @return bool
     */
    public function canAdd(IdentityInterface $user, FeedItem $feedItem)
    {
    }

    /**
     * Check if $user can edit FeedItem
     *
     * @param \Authorization\IdentityInterface $user The user.
     * @param \App\Model\Entity\FeedItem $feedItem
     * @return bool
     */
    public function canEdit(IdentityInterface $user, FeedItem $feedItem)
    {
    }

    /**
     * Check if $user can delete FeedItem
     *
     * @param \Authorization\IdentityInterface $user The user.
     * @param \App\Model\Entity\FeedItem $feedItem
     * @return bool
     */
    public function canDelete(IdentityInterface $user, FeedItem $feedItem)
    {
    }

    /**
     * Check if $user can view FeedItem
     *
     * @param \Authorization\IdentityInterface $user The user.
     * @param \App\Model\Entity\FeedItem $feedItem
     * @return bool
     */
    public function canView(IdentityInterface $user, FeedItem $feedItem)
    {
        // User has to be one of the subscribers. Generally
        // this should be a loop of 1 item.
        foreach ((array)$feedItem->feed_subscriptions as $sub) {
            if ($sub->user_id == $user->id) {
                return true;
            }
        }

        return false;
    }
}
