<?php
declare(strict_types=1);

namespace App\Policy;

use App\Model\Entity\User;
use Authorization\IdentityInterface;

/**
 * User policy
 */
class UserPolicy
{
    /**
     * Check if $user can edit Task
     *
     * @param \App\Model\Entity\User  $identity The user.
     * @param \App\Model\Entity\User $user
     * @return bool
     */
    public function canEdit(IdentityInterface $identity, User $user)
    {
        return $user->id === $identity->id;
    }
}
