<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\CalendarService;
use Cake\Event\EventInterface;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Response;
use Google\Client as GoogleClient;

class GoogleOauthController extends AppController
{
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
        $code = $this->request->getQuery('code');
        $data = $client->fetchAccessTokenWithAuthCode($code);
        if (!$data || !isset($data['access_token'])) {
            throw new BadRequestException('Could not fetch OAuth Access token');
        }
        $token = $data['access_token'];
        $refresh = $data['refresh_token'];
        $this->request->getSession()->write('CalendarSync', compact('token', 'refresh'));

        $this->redirect(['_name' => 'googleauth:sync']);
    }

    public function sync(CalendarService $service)
    {
        $token = $this->request->getSession()->read('CalendarSync.token');
        if (!$token) {
            throw new BadRequestException('No access token found in session.');
        }
        $service->setAccessToken($token);
        $service->syncEvents($this->request->getAttribute('identity'), 1);
    }
}
