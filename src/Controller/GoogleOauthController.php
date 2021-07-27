<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Event\EventInterface;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Response;
use Cake\I18n\FrozenTime;
use Google\Client as GoogleClient;

class GoogleOauthController extends AppController
{
    /**
     * @var \App\Model\Table\CalendarProvidersTable
     */
    protected $CalendarProviders;

    /**
     * @var \App\Model\Table\CalendarSourcesTable
     */
    protected $CalendarSources;

    public function beforeFilter(EventInterface $event): ?Response
    {
        parent::beforeFilter($event);

        // TODO fix this.
        $this->Authorization->skipAuthorization();

        return null;
    }

    public function authorize(GoogleClient $client)
    {
        $this->redirect($client->createAuthUrl());
    }

    public function callback(GoogleClient $client)
    {
        $this->loadModel('CalendarProviders');

        $code = $this->request->getQuery('code');
        $data = $client->fetchAccessTokenWithAuthCode($code);
        if (!$data || !isset($data['access_token'])) {
            throw new BadRequestException('Could not fetch OAuth Access token');
        }

        $user = $this->request->getAttribute('identity');

        $provider = $this->CalendarProviders->findOrCreate([
            'user_id' => $user->id,
            'kind' => 'google',
            // TODO need to get the current google user instead of the user.
            // one user could have multiple google accounts.
            'identifier' => $user->email,
        ], function ($entity) use ($data) {
            $entity->access_token = $data['access_token'];
            $entity->refresh_token = $data['refresh_token'];
            $entity->token_expiry = FrozenTime::parse("+{$data['expires_in']} seconds");
        });
        $this->CalendarProviders->saveOrFail($provider);

        $this->redirect(['_name' => 'calendarproviders:index']);
    }
}
