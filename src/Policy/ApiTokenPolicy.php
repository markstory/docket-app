<?php
declare(strict_types=1);

namespace App\Policy;

use App\Model\Entity\ApiToken;
use Authorization\IdentityInterface;

/**
 * ApiToken policy
 */
class ApiTokenPolicy
{
    /**
     * Check if $user can add ApiToken
     *
     * @param \Authorization\IdentityInterface $user The user.
     * @param \App\Model\Entity\ApiToken $apiToken
     * @return bool
     */
    public function canAdd(IdentityInterface $user, ApiToken $apiToken)
    {
        return true;
    }

    /**
     * Check if $user can delete ApiToken
     *
     * @param \Authorization\IdentityInterface $user The user.
     * @param \App\Model\Entity\ApiToken $apiToken
     * @return bool
     */
    public function canDelete(IdentityInterface $user, ApiToken $apiToken)
    {
        return $user->id === $apiToken->user_id;
    }
}
