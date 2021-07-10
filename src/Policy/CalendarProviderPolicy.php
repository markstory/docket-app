<?php
declare(strict_types=1);

namespace App\Policy;

use App\Model\Entity\CalendarProvider;
use App\Model\Entity\User;

/**
 * CalendarProvider policy
 */
class CalendarProviderPolicy
{
    /**
     * Check if $user can sync CalendarProvider
     *
     * @param \Authorization\IdentityInterface $user The user.
     * @param \App\Model\Entity\CalendarProvider $calendarProvider
     * @return bool
     */
    public function canSync(User $user, CalendarProvider $calendarProvider)
    {
        return $user->id == $calendarProvider->user_id;
    }

    /**
     * Check if $user can delete CalendarProvider
     *
     * @param \Authorization\IdentityInterface $user The user.
     * @param \App\Model\Entity\CalendarProvider $calendarProvider
     * @return bool
     */
    public function canDelete(User $user, CalendarProvider $calendarProvider)
    {
        return $user->id == $calendarProvider->user_id;
    }

    /**
     * Check if $user can view CalendarProvider
     *
     * @param \Authorization\IdentityInterface $user The user.
     * @param \App\Model\Entity\CalendarProvider $calendarProvider
     * @return bool
     */
    public function canView(User $user, CalendarProvider $calendarProvider)
    {
        return $user->id == $calendarProvider->user_id;
    }
}
