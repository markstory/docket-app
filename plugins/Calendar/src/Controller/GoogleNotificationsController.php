<?php
declare(strict_types=1);

namespace Calendar\Controller;

use App\Controller\AppController;
use Cake\Event\EventInterface;
use Cake\Http\Exception\BadRequestException;
use Cake\I18n\DateTime;
use Calendar\Service\CalendarService;

/**
 * Receives push notifications from google calendar to sync events
 */
class GoogleNotificationsController extends AppController
{
    public function beforeFilter(EventInterface $event): void
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
        $service->setAccessToken($source->calendar_provider);
        $service->syncEvents($source);

        $expires = DateTime::parse($expiration);
        $soon = DateTime::parse('+1 hour');
        if ($expires->lessThan($soon)) {
            $service->createSubscription($source);
        }

        return $this->response->withStringBody('ok');
    }
}
