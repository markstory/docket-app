<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\CalendarSource;
use App\Model\Entity\User;
use Cake\Datasource\ModelAwareTrait;
use DateTime;
use DateTimeZone;
use Google\Client as GoogleClient;
use Google\Service\Calendar;
use Google\Service\Calendar\Event as GoogleEvent;

/**
 * Provides Calendar syncing.
 *
 * Longer term this should become abstract
 * from vendor details, but for now it is somewhat coupled to
 * google calendar as I don't have enough context on what
 * the right abstractions are.
 */
class CalendarService
{
    use ModelAwareTrait;

    /**
     * @var \Google\Client $client
     */
    private $client;

    /**
     * @var \App\Model\Table\CalendarSourcesTable
     */
    private $CalendarSources;

    /**
     * @var \App\Model\Table\CalendarItemsTable
     */
    private $CalendarItems;

    public function __construct(GoogleClient $client)
    {
        $this->client = $client;
    }

    public function setAccessToken(string $token): void
    {
        $this->client->setAccessToken($token);
    }

    /**
     * Get a list of calendars in the user's account.
     *
     * This is used to build the list of calendars that the user can
     * add to their task views.
     *
     * @return \App\Model\Entity\CalendarSource[]
     */
    public function listCalendars()
    {
        return [];
    }

    public function syncEvents(User $user, $calendarSourceId)
    {
        $this->loadModel('CalendarSources');
        $this->loadModel('CalendarItems');
        $source = $this->CalendarSources->get($calendarSourceId);

        $calendar = new Calendar($this->client);

        $time = new DateTime('-1 month');

        $options = [
            'timeMin' => $time->format(DateTime::RFC3339),
        ];
        // Check if the user has a sync token for this source.
        // If so use it to continue syncing.
        if ($source->sync_token) {
            $options['syncToken'] = $source->sync_token;
        }

        do {
            // Fetch events one page at a time.
            // A 410 means we need to start over again.
            try {
                $results = $calendar->events->listEvents($source->provider_id, $options);
            } catch (\Exception $e) {
                unset($options['syncToken']);
            }
            foreach ($results as $event) {
                $this->syncEvent($source, $event);
            }
            $pageToken = $results->getNextPageToken();
        } while ($pageToken !== null);

        // Save the nextSyncToken for our next sync.
        $source->sync_token = $results->getNextSyncToken();
        $this->CalendarSources->saveOrFail($source);
    }

    private function syncEvent(CalendarSource $source, GoogleEvent $event)
    {
        $tz = new DateTimeZone(date_default_timezone_get());
        $start = $event->getStart()->getDateTime();
        if ($start) {
            $start = new DateTime($start, $tz);
        }
        $end = $event->getEnd()->getDateTime();
        if ($end) {
            $end = new DateTime($end, $tz);
        }

        if ($start === null && $end === null) {
            return;
        }

        $item = $this->CalendarItems->newEntity([
            'calendar_source_id' => $source->id,
            'provider_id' => $event->id,
            'title' => $event->summary,
            'start_time' => $start,
            'end_time' => $end,
            'html_link' => $event->htmlLink,
        ]);
        $this->CalendarItems->saveOrFail($item);
    }
}
