<?php
declare(strict_types=1);

namespace App\Identifier;

use ArrayAccess;
use Authentication\Identifier\AbstractIdentifier;
use Cake\I18n\DateTime;
use Cake\ORM\Locator\LocatorAwareTrait;

class ApiTokenIdentifier extends AbstractIdentifier
{
    use LocatorAwareTrait;

    public function identify(array $credentials): ArrayAccess|array|null
    {
        if (!isset($credentials['token'])) {
            return null;
        }
        $apiTokens = $this->fetchTable('ApiTokens');

        /** @var \App\Model\Entity\ApiToken|null $tokenUser */
        $tokenUser = $apiTokens->find()
            ->where(['ApiTokens.token' => $credentials['token']])
            ->contain('Users')
            ->first();

        if (!$tokenUser) {
            return null;
        }

        // Update the last used timestamp.
        $tokenUser->last_used = new DateTime();
        $apiTokens->save($tokenUser);

        return $tokenUser->user;
    }
}
