<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\CalendarService;
use Cake\Event\EventInterface;
use Cake\Http\Exception\BadRequestException;
use Cake\I18n\FrozenTime;

/**
 * Receives push notifications from google calendar to sync events
 */
class GoogleNotificationsController extends AppController
{
    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);
        $this->Authentication->allowUnauthenticated(['update']);
    }

    public function update(CalendarService $service)
    {
        $this->request->allowMethod('post');

        $this->Authorization->skipAuthorization();
        $subscriptionId = $this->request->getHeaderLine('X-Goog-Channel-ID');
        $token = $this->request->getHeaderLine('X-Goog-Channel-Token');
        $expiration = $this->request->getHeaderLine('X-Goog-Channel-Expiration');
        if (!$subscriptionId || !$token) {
            throw new BadRequestException('Missing channel-id');
        }
        $tokenData = [];
        parse_str($token, $tokenData);
        if (!isset($tokenData['verifier'])) {
            throw new BadRequestException('Missing channel-id');
        }
        $source = $service->getSourceForSubscription($subscriptionId, $tokenData['verifier']);
        $service->syncEvents($source);

        $expires = FrozenTime::parse($expiration);
        $soon = FrozenTime::parse('+1 hour');
        if ($expires->lessThan($soon)) {
            $service->createSubscription($source);
        }

        return $this->response->withStringBody('ok');
    }
}
