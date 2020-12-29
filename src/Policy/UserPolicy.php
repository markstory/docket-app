<?php
declare(strict_types=1);

namespace App\Policy;

use App\Model\Entity\User;
use Authorization\IdentityInterface;
use RuntimeException;

/**
 * User policy
 */
class UserPolicy
{
    /**
     * Check if $user can create Task
     *
     * @param \Authorization\IdentityInterface $user The user.
     * @param \App\Model\Entity\Task $task
     * @return bool
     */
    public function canAdd(IdentityInterface $identity, User $user)
    {
        return true;
    }

    /**
     * Check if $user can edit Task
     *
     * @param \Authorization\IdentityInterface $user The user.
     * @param \App\Model\Entity\Task $task
     * @return bool
     */
    public function canEdit(IdentityInterface $identity, User $user)
    {
        return $user->id === $identity->id;
    }
}
