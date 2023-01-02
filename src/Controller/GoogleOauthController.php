<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Event\EventInterface;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Response;
use Cake\I18n\FrozenTime;
use Cake\ORM\Exception\PersistenceFailedException;
use Google\Client as GoogleClient;
use Google\Exception as GoogleException;
use Google\Service\Oauth2 as GoogleOauth2;

class GoogleOauthController extends AppController
{
    public const MOBILE_KEY = 'oauth-mobile';

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

        $this->Authorization->skipAuthorization();

        return null;
    }

    protected function useInertia()
    {
        return false;
    }

    public function authorize(GoogleClient $client)
    {
        if ($this->request->getQuery('mobile')) {
            $this->request->getSession()->write(self::MOBILE_KEY, true);
        }
        $this->redirect($client->createAuthUrl());
    }

    public function callback(GoogleClient $client)
    {
        $this->loadModel('CalendarProviders');

        $code = $this->request->getQuery('code');
        if (!is_string($code)) {
            throw new BadRequestException('Missing authorization code.');
        }

        $data = $client->fetchAccessTokenWithAuthCode($code);
        if (!$data || !isset($data['access_token'])) {
            throw new BadRequestException('Could not fetch OAuth Access token');
        }
        $client->setAccessToken($data['access_token']);

        try {
            $oauth2 = new GoogleOauth2($client);
            $googleUser = $oauth2->userinfo->get();
        } catch (GoogleException $e) {
            throw new BadRequestException('Could not fetch user profile data.');
        }
        $user = $this->request->getAttribute('identity');

        try {
            $provider = $this->CalendarProviders->findOrCreate([
                'user_id' => $user->id,
                'kind' => 'google',
                'identifier' => $googleUser->id,
            ], function ($entity) use ($data, $googleUser) {
                $entity->display_name = "{$googleUser->name} ({$googleUser->email})";
                $entity->access_token = $data['access_token'];
                $entity->token_expiry = FrozenTime::parse("+{$data['expires_in']} seconds");

                if (!isset($data['refresh_token'])) {
                    throw new PersistenceFailedException($entity, 'Missing refresh_token');
                }
                $entity->refresh_token = $data['refresh_token'];
            });
            $this->CalendarProviders->saveOrFail($provider);
        } catch (PersistenceFailedException $e) {
            $this->Flash->error(
                __('Could not link google account. Try removing authorization in google and re-connecting')
            );
        }
        if ($this->request->getSession()->read(self::MOBILE_KEY)) {
            $this->request->getSession()->delete(self::MOBILE_KEY);

            return $this->render('complete');
        }

        $this->redirect(['_name' => 'calendarproviders:index']);
    }
}
