<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\User;
use DateTime;
use Google\Client as GoogleClient;
use Google\Service\Calendar;

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
    /**
     * @var \Google\Client $client
     */
    private $client;

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

    public function syncEvents(User $user, $calendarId = 'primary')
    {
        $calendar = new Calendar($this->client);

        $time = new DateTime('-3 months');
        // Check if the user has a sync token for this source.
        // If so use it to continue syncing.
        $options = [
            'timeMin' => $time->format(DateTime::RFC3339),
        ];

        do {
            // Fetch events one page at a time.
            // A 410 means we need to start over again.
            $results = $calendar->events->listEvents($calendarId, $options);
            foreach ($results as $event) {
                $this->syncEvent($user, $calendarId, $event);
            }
            $pageToken = $results->getNextPageToken();
        } while ($pageToken !== null);

        // Save the nextSyncToken for our next sync
        $syncToken = $results->getNextSyncToken();
    }

    public function syncEvent(User $user, string $calendarId, $event)
    {
        debug($event);
    }
}
