<?php
declare(strict_types=1);

namespace Calendar\Controller;

use App\Controller\AppController;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Event\EventInterface;
use Cake\Http\Exception\BadRequestException;
use Cake\I18n\DateTime;
use Cake\Log\Log;
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
        $resourceId = $this->request->getHeaderLine('X-Goog-Resource-ID');
        if (!$subscriptionId || !$token) {
            throw new BadRequestException('Missing channel-id or token');
        }
        $tokenData = [];
        parse_str($token, $tokenData);
        if (!isset($tokenData['verifier'])) {
            throw new BadRequestException('Missing verifier');
        }
        Log::info("Receive update from google for resource={$resourceId} subscriptionId={$subscriptionId} verifier={$tokenData['verifier']}");

        try {
            $source = $service->getSourceForSubscription($subscriptionId, $tokenData['verifier']);
        } catch (RecordNotFoundException $e) {
            // If we've received an invalid/stale subscription delete from google.
            $source = $service->getSourceByResourceId($resourceId);
            if ($source) {
                $service->setAccessToken($source->calendar_provider);
                $service->cancelSubscriptionById($subscriptionId, $resourceId);
            }

            return $this->response->withStringBody('ok');
        }

        $service->setAccessToken($source->calendar_provider);
        $service->syncEvents($source);

        return $this->response->withStringBody('ok');
    }
}
