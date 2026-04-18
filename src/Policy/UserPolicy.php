<?php
declare(strict_types=1);

namespace App\Policy;

use App\Model\Entity\User;
use Authorization\IdentityInterface;
use Cake\Core\Configure;

/**
 * User policy
 */
class UserPolicy
{
    /**
     * Check if $user can be created
     *
     * @param \App\Model\Entity\User|null  $identity The user.
     * @param \App\Model\Entity\User $user
     * @return bool
     */
    public function canAdd(IdentityInterface | null $identity, User $user): bool
    {
        // TODO use DI container to get feature manager
        return Configure::read('Features.create-user') === true;
    }

    /**
     * Check if $user can be edited
     *
     * @param \App\Model\Entity\User  $identity The user.
     * @param \App\Model\Entity\User $user
     * @return bool
     */
    public function canEdit(IdentityInterface $identity, User $user): bool
    {
        return $user->id === $identity->id;
    }
}
