<?php
declare(strict_types=1);

namespace App\Identifier;

use Authentication\Identifier\AbstractIdentifier;
use Cake\I18n\FrozenTime;
use Cake\ORM\Locator\LocatorAwareTrait;

class ApiTokenIdentifier extends AbstractIdentifier
{
    use LocatorAwareTrait;

    public function identify(array $credentials)
    {
        if (!isset($credentials['token'])) {
            return null;
        }

        $apiTokens = $this->fetchTable('ApiTokens');
        $tokenUser = $apiTokens->find()
            ->where(['ApiTokens.token' => $credentials['token']])
            ->contain('Users')->first();

        if (!$tokenUser) {
            return null;
        }

        // Update the last used timestamp.
        $tokenUser->last_used = new FrozenTime();
        $apiTokens->save($tokenUser);

        return $tokenUser->user;
    }
}
