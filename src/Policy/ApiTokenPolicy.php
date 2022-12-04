<?php
declare(strict_types=1);

namespace App\Policy;

use App\Model\Entity\ApiToken;
use App\Model\Entity\User;

/**
 * ApiToken policy
 */
class ApiTokenPolicy
{
    /**
     * Check if $user can delete ApiToken
     *
     * @param \App\Model\Entity\User $user The user.
     * @param \App\Model\Entity\ApiToken $apiToken
     * @return bool
     */
    public function canDelete(User $user, ApiToken $apiToken)
    {
        return $user->id === $apiToken->user_id;
    }
}
